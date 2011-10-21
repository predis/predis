<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RedisCommandTestSuite extends PHPUnit_Framework_TestCase
{
    public $redis;

    // TODO: instead of an boolean assertion against the return value
    //       of RC::sameValuesInArrays, we should extend PHPUnit with
    //       a new assertion, e.g. $this->assertSameValues();
    // TODO: an option to skip certain tests such as testflushdbs
    //       should be provided.
    // TODO: missing test with float values for a few commands

    protected function setUp()
    {
        $this->redis = RC::getConnection();
        $this->redis->flushdb();
    }

    protected function tearDown()
    {
    }

    protected function onNotSuccessfulTest(Exception $exception)
    {
        // drops and reconnect to a redis server on uncaught exceptions
        RC::resetConnection();

        parent::onNotSuccessfulTest($exception);
    }

    /* miscellaneous commands */

    function testPing()
    {
        $this->assertTrue($this->redis->ping());
    }

    function testEcho()
    {
        $string = 'This is an echo test!';
        $this->assertEquals($string, $this->redis->echo($string));
    }

    function testQuit()
    {
        $this->redis->quit();
        $this->assertFalse($this->redis->isConnected());
    }

    function testMultiExec()
    {
        // NOTE: due to a limitation in the current implementation of Predis\Client,
        //       the replies returned by Predis\Commands\Exec are not parsed by their
        //       respective Predis\Commands\Command::parseResponse methods. If you
        //       need that kind of behaviour, you should use an instance of
        //       Predis\MultiExecBlock.

        $this->assertTrue($this->redis->multi());
        $this->assertInstanceOf('Predis\ResponseQueued', $this->redis->ping());
        $this->assertInstanceOf('Predis\ResponseQueued', $this->redis->echo('hello'));
        $this->assertInstanceOf('Predis\ResponseQueued', $this->redis->echo('redis'));
        $this->assertEquals(array('PONG', 'hello', 'redis'), $this->redis->exec());

        $this->assertTrue($this->redis->multi());
        $this->assertEquals(array(), $this->redis->exec());

        // should throw an exception when trying to EXEC without having previously issued MULTI
        RC::testForServerException($this, RC::EXCEPTION_EXEC_NO_MULTI, function($test) {
            $test->redis->exec();
        });
    }

    function testDiscard()
    {
        $this->assertTrue($this->redis->multi());
        $this->assertInstanceOf('Predis\ResponseQueued', $this->redis->set('foo', 'bar'));
        $this->assertInstanceOf('Predis\ResponseQueued', $this->redis->set('hoge', 'piyo'));
        $this->assertEquals(true, $this->redis->discard());

        // should throw an exception when trying to EXEC after a DISCARD
        RC::testForServerException($this, RC::EXCEPTION_EXEC_NO_MULTI, function($test) {
            $test->redis->exec();
        });

        $this->assertFalse($this->redis->exists('foo'));
        $this->assertFalse($this->redis->exists('hoge'));
    }

    /* commands operating on string values */

    function testSet()
    {
        $this->assertTrue($this->redis->set('foo', 'bar'));
        $this->assertEquals('bar', $this->redis->get('foo'));
    }

    function testGet()
    {
        $this->redis->set('foo', 'bar');
        $this->assertEquals('bar', $this->redis->get('foo'));

        $this->assertTrue($this->redis->set('foo', ''));
        $this->assertEquals('', $this->redis->get('foo'));

        $this->assertNull($this->redis->get('fooDoesNotExist'));

        // should throw an exception when trying to do a GET on non-string types
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->get('metavars');
        });
    }

    function testExists()
    {
        $this->redis->set('foo', 'bar');

        $this->assertTrue($this->redis->exists('foo'));
        $this->assertFalse($this->redis->exists('key_does_not_exist'));
    }

    function testSetPreserve()
    {
        $multi = RC::getKeyValueArray();

        $this->assertTrue($this->redis->setnx('foo', 'bar'));
        $this->assertFalse($this->redis->setnx('foo', 'rab'));
        $this->assertEquals('bar', $this->redis->get('foo'));
    }

    function testMultipleSetAndGet()
    {
        $multi = RC::getKeyValueArray();

        // key=>value pairs via array instance
        $this->assertTrue($this->redis->mset($multi));
        $multiRet = $this->redis->mget(array_keys($multi));
        $this->assertEquals($multi, array_combine(array_keys($multi), array_values($multiRet)));

        // key=>value pairs via function arguments
        $this->assertTrue($this->redis->mset('a', 1, 'b', 2, 'c', 3));
        $this->assertEquals(array(1, 2, 3), $this->redis->mget('a', 'b', 'c'));
    }

    function testSetMultiplePreserve()
    {
        $multi    = RC::getKeyValueArray();
        $newpair  = array('hogehoge' => 'piyopiyo');
        $hijacked = array('foo' => 'baz', 'hoge' => 'fuga');

        // successful set
        $expectedResult = array_merge($multi, $newpair);
        $this->redis->mset($multi);
        $this->assertTrue($this->redis->msetnx($newpair));
        $this->assertEquals(
            array_values($expectedResult),
            $this->redis->mget(array_keys($expectedResult))
        );

        $this->redis->flushdb();

        // unsuccessful set
        $expectedResult = array_merge($multi, array('hogehoge' => null));
        $this->redis->mset($multi);
        $this->assertFalse($this->redis->msetnx(array_merge($newpair, $hijacked)));
        $this->assertEquals(
            array_values($expectedResult),
            $this->redis->mget(array_keys($expectedResult))
        );
    }

    function testGetSet()
    {
        $this->assertNull($this->redis->getset('foo', 'bar'));
        $this->assertEquals('bar', $this->redis->getset('foo', 'barbar'));
        $this->assertEquals('barbar', $this->redis->getset('foo', 'baz'));
    }

    function testIncrementAndIncrementBy()
    {
        // test subsequent increment commands
        $this->assertEquals(1, $this->redis->incr('foo'));
        $this->assertEquals(2, $this->redis->incr('foo'));

        // test subsequent incrementBy commands
        $this->assertEquals(22, $this->redis->incrby('foo', 20));
        $this->assertEquals(10, $this->redis->incrby('foo', -12));
        $this->assertEquals(-100, $this->redis->incrby('foo', -110));
    }

    function testDecrementAndDecrementBy()
    {
        // test subsequent decrement commands
        $this->assertEquals(-1, $this->redis->decr('foo'));
        $this->assertEquals(-2, $this->redis->decr('foo'));

        // test subsequent decrementBy commands
        $this->assertEquals(-22, $this->redis->decrby('foo', 20));
        $this->assertEquals(-10, $this->redis->decrby('foo', -12));
        $this->assertEquals(100, $this->redis->decrby('foo', -110));
    }

    function testDelete()
    {
        $this->redis->set('foo', 'bar');
        $this->assertEquals(1, $this->redis->del('foo'));
        $this->assertFalse($this->redis->exists('foo'));
        $this->assertEquals(0, $this->redis->del('foo'));
    }

    function testType()
    {
        $this->assertEquals('none', $this->redis->type('fooDoesNotExist'));

        $this->redis->set('fooString', 'bar');
        $this->assertEquals('string', $this->redis->type('fooString'));

        $this->redis->rpush('fooList', 'bar');
        $this->assertEquals('list', $this->redis->type('fooList'));

        $this->redis->sadd('fooSet', 'bar');
        $this->assertEquals('set', $this->redis->type('fooSet'));

        $this->redis->zadd('fooZSet', 0, 'bar');
        $this->assertEquals('zset', $this->redis->type('fooZSet'));

        $this->redis->hset('fooHash', 'value', 'bar');
        $this->assertEquals('hash', $this->redis->type('fooHash'));
    }

    function testAppend()
    {
        $this->redis->set('foo', 'bar');
        $this->assertEquals(5, $this->redis->append('foo', '__'));
        $this->assertEquals(8, $this->redis->append('foo', 'bar'));
        $this->assertEquals('bar__bar', $this->redis->get('foo'));

        $this->assertEquals(4, $this->redis->append('hoge', 'piyo'));
        $this->assertEquals('piyo', $this->redis->get('hoge'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->append('metavars', 'bar');
        });
    }

    function testSetRange()
    {
        $this->assertEquals(6, $this->redis->setrange('var', 0, 'foobar'));
        $this->assertEquals('foobar', $this->redis->get('var'));
        $this->assertEquals(6, $this->redis->setrange('var', 3, 'foo'));
        $this->assertEquals('foofoo', $this->redis->get('var'));
        $this->assertEquals(16, $this->redis->setrange('var', 10, 'barbar'));
        $this->assertEquals("foofoo\x00\x00\x00\x00barbar", $this->redis->get('var'));

        $this->assertEquals(4, $this->redis->setrange('binary', 0, pack('l', -2147483648)));
        list($unpacked) = array_values(unpack('l', $this->redis->get('binary')));
        $this->assertEquals(-2147483648, $unpacked);

        RC::testForServerException($this, RC::EXCEPTION_OFFSET_RANGE, function($test) {
            $test->redis->setrange('var', -1, 'bogus');
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->setrange('metavars', 0, 'hoge');
        });
    }

    function testSubstr()
    {
        $this->redis->set('var', 'foobar');
        $this->assertEquals('foo', $this->redis->substr('var', 0, 2));
        $this->assertEquals('bar', $this->redis->substr('var', 3, 5));
        $this->assertEquals('bar', $this->redis->substr('var', -3, -1));

        $this->assertEquals('', $this->redis->substr('var', 5, 0));

        $this->redis->set('numeric', 123456789);
        $this->assertEquals(12345, $this->redis->substr('numeric', 0, 4));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->substr('metavars', 0, 3);
        });
    }

    function testStrlen()
    {
        $this->redis->set('var', 'foobar');
        $this->assertEquals(6, $this->redis->strlen('var'));
        $this->assertEquals(9, $this->redis->append('var', '___'));
        $this->assertEquals(9, $this->redis->strlen('var'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->strlen('metavars');
        });
    }

    function testSetBit()
    {
        $this->assertEquals(0, $this->redis->setbit('binary', 31, 1));
        $this->assertEquals(0, $this->redis->setbit('binary', 0, 1));
        $this->assertEquals(4, $this->redis->strlen('binary'));
        $this->assertEquals("\x80\x00\00\x01", $this->redis->get('binary'));

        $this->assertEquals(1, $this->redis->setbit('binary', 0, 0));
        $this->assertEquals(0, $this->redis->setbit('binary', 0, 0));
        $this->assertEquals("\x00\x00\00\x01", $this->redis->get('binary'));

        RC::testForServerException($this, RC::EXCEPTION_BIT_OFFSET, function($test) {
            $test->redis->setbit('binary', -1, 1);
        });

        RC::testForServerException($this, RC::EXCEPTION_BIT_OFFSET, function($test) {
            $test->redis->setbit('binary', 'invalid', 1);
        });

        RC::testForServerException($this, RC::EXCEPTION_BIT_VALUE, function($test) {
            $test->redis->setbit('binary', 15, 255);
        });

        RC::testForServerException($this, RC::EXCEPTION_BIT_VALUE, function($test) {
            $test->redis->setbit('binary', 15, 'invalid');
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->setbit('metavars', 0, 1);
        });
    }

    function testGetBit()
    {
        $this->redis->set('binary', "\x80\x00\00\x01");

        $this->assertEquals(1, $this->redis->getbit('binary', 0));
        $this->assertEquals(0, $this->redis->getbit('binary', 15));
        $this->assertEquals(1, $this->redis->getbit('binary', 31));
        $this->assertEquals(0, $this->redis->getbit('binary', 63));

        RC::testForServerException($this, RC::EXCEPTION_BIT_OFFSET, function($test) {
            $test->redis->getbit('binary', -1);
        });

        RC::testForServerException($this, RC::EXCEPTION_BIT_OFFSET, function($test) {
            $test->redis->getbit('binary', 'invalid');
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->getbit('metavars', 0);
        });
    }

    /* commands operating on the key space */

    function testKeys()
    {
        $keyValsNs     = RC::getNamespacedKeyValueArray();
        $keyValsOthers = array('aaa' => 1, 'aba' => 2, 'aca' => 3);
        $allKeyVals    = array_merge($keyValsNs, $keyValsOthers);

        $this->redis->mset($allKeyVals);

        $this->assertEquals(array(), $this->redis->keys('nokeys:*'));

        $keysFromRedis = $this->redis->keys('metavar:*');
        $this->assertEquals(array(), array_diff(array_keys($keyValsNs), $keysFromRedis));

        $keysFromRedis = $this->redis->keys('*');
        $this->assertEquals(array(), array_diff(array_keys($allKeyVals), $keysFromRedis));

        $keysFromRedis = $this->redis->keys('a?a');
        $this->assertEquals(array(), array_diff(array_keys($keyValsOthers), $keysFromRedis));
    }

    function testRandomKey()
    {
        $keyvals = RC::getKeyValueArray();

        $this->assertNull($this->redis->randomkey());

        $this->redis->mset($keyvals);
        $this->assertTrue(in_array($this->redis->randomkey(), array_keys($keyvals)));
    }

    function testRename()
    {
        $this->redis->mset(array('foo' => 'bar', 'foofoo' => 'barbar'));

        // rename existing keys
        $this->assertTrue($this->redis->rename('foo', 'foofoo'));
        $this->assertFalse($this->redis->exists('foo'));
        $this->assertEquals('bar', $this->redis->get('foofoo'));

        // should throw an excepion then trying to rename non-existing keys
        RC::testForServerException($this, RC::EXCEPTION_NO_SUCH_KEY, function($test) {
            $test->redis->rename('hoge', 'hogehoge');
        });
    }

    function testRenamePreserve()
    {
        $this->redis->mset(array('foo' => 'bar', 'hoge' => 'piyo', 'hogehoge' => 'piyopiyo'));

        $this->assertTrue($this->redis->renamenx('foo', 'foofoo'));
        $this->assertFalse($this->redis->exists('foo'));
        $this->assertEquals('bar', $this->redis->get('foofoo'));

        $this->assertFalse($this->redis->renamenx('hoge', 'hogehoge'));
        $this->assertTrue($this->redis->exists('hoge'));

        // should throw an excepion then trying to rename non-existing keys
        RC::testForServerException($this, RC::EXCEPTION_NO_SUCH_KEY, function($test) {
            $test->redis->renamenx('fuga', 'baz');
        });
    }

    function testExpirationAndTTL()
    {
        $this->redis->set('foo', 'bar');

        // check for key expiration
        $this->assertTrue($this->redis->expire('foo', 1));
        $this->assertEquals(1, $this->redis->ttl('foo'));
        $this->assertTrue($this->redis->exists('foo'));
        sleep(2);
        $this->assertFalse($this->redis->exists('foo'));
        $this->assertEquals(-1, $this->redis->ttl('foo'));

        // check for consistent TTL values
        $this->redis->set('foo', 'bar');
        $this->assertTrue($this->redis->expire('foo', 100));
        sleep(3);
        $this->assertEquals(97, $this->redis->ttl('foo'));

        // delete key on negative TTL
        $this->redis->set('foo', 'bar');
        $this->assertTrue($this->redis->expire('foo', -100));
        $this->assertFalse($this->redis->exists('foo'));
        $this->assertEquals(-1, $this->redis->ttl('foo'));
    }

    function testPersist()
    {
        $this->redis->set('foo', 'bar');

        $this->assertTrue($this->redis->expire('foo', 1));
        $this->assertEquals(1, $this->redis->ttl('foo'));
        $this->assertTrue($this->redis->persist('foo'));
        $this->assertEquals(-1, $this->redis->ttl('foo'));

        $this->assertFalse($this->redis->persist('foo'));
        $this->assertFalse($this->redis->persist('foobar'));
    }

    function testSetExpire()
    {
        $this->assertTrue($this->redis->setex('foo', 10, 'bar'));
        $this->assertTrue($this->redis->exists('foo'));
        $this->assertEquals(10, $this->redis->ttl('foo'));

        $this->assertTrue($this->redis->setex('hoge', 1, 'piyo'));
        sleep(2);
        $this->assertFalse($this->redis->exists('hoge'));

        // TODO: do not check the error message RC::EXCEPTION_VALUE_NOT_INT for now
        RC::testForServerException($this, null, function($test) {
            $test->redis->setex('hoge', 2.5, 'piyo');
        });
        RC::testForServerException($this, RC::EXCEPTION_SETEX_TTL, function($test) {
            $test->redis->setex('hoge', 0, 'piyo');
        });
        RC::testForServerException($this, RC::EXCEPTION_SETEX_TTL, function($test) {
            $test->redis->setex('hoge', -10, 'piyo');
        });
    }

    function testDatabaseSize()
    {
        // TODO: is this really OK?
        $this->assertEquals(0, $this->redis->dbsize());
        $this->redis->mset(RC::getKeyValueArray());
        $this->assertGreaterThan(0, $this->redis->dbsize());
    }

    /* commands operating on lists */

    function testPushTail()
    {
        // NOTE: List push operations return the list length since Redis commit 520b5a3
        $this->assertEquals(1, $this->redis->rpush('metavars', 'foo'));
        $this->assertTrue($this->redis->exists('metavars'));
        $this->assertEquals(2, $this->redis->rpush('metavars', 'hoge'));

        // should throw an exception when trying to do a RPUSH on non-list types
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->rpush('foo', 'bar');
        });
    }

    function testPushTailX()
    {
        $this->assertEquals(0, $this->redis->rpushx('numbers', 1));
        $this->assertEquals(1, $this->redis->rpush('numbers', 2));
        $this->assertEquals(2, $this->redis->rpushx('numbers', 3));

        $this->assertEquals(2, $this->redis->llen('numbers'));
        $this->assertEquals(array(2, 3), $this->redis->lrange('numbers', 0, -1));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->rpushx('foo', 'bar');
        });
    }

    function testPushHead()
    {
        // NOTE: List push operations return the list length since Redis commit 520b5a3
        $this->assertEquals(1, $this->redis->lpush('metavars', 'foo'));
        $this->assertTrue($this->redis->exists('metavars'));
        $this->assertEquals(2, $this->redis->lpush('metavars', 'hoge'));

        // should throw an exception when trying to do a LPUSH on non-list types
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lpush('foo', 'bar');
        });
    }

    function testPushHeadX()
    {
        $this->assertEquals(0, $this->redis->lpushx('numbers', 1));
        $this->assertEquals(1, $this->redis->lpush('numbers', 2));
        $this->assertEquals(2, $this->redis->lpushx('numbers', 3));

        $this->assertEquals(2, $this->redis->llen('numbers'));
        $this->assertEquals(array(3, 2), $this->redis->lrange('numbers', 0, -1));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lpushx('foo', 'bar');
        });
    }

    function testListLength()
    {
        $this->assertEquals(1, $this->redis->rpush('metavars', 'foo'));
        $this->assertEquals(2, $this->redis->rpush('metavars', 'hoge'));
        $this->assertEquals(2, $this->redis->llen('metavars'));

        $this->assertEquals(0, $this->redis->llen('doesnotexist'));

        // should throw an exception when trying to do a LLEN on non-list types
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->llen('foo');
        });
    }

    function testListRange()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers());

        $this->assertEquals(
            array_slice($numbers, 0, 4),
            $this->redis->lrange('numbers', 0, 3)
        );
        $this->assertEquals(
            array_slice($numbers, 4, 5),
            $this->redis->lrange('numbers', 4, 8)
        );
        $this->assertEquals(
            array_slice($numbers, 0, 1),
            $this->redis->lrange('numbers', 0, 0)
        );
        $this->assertEquals(
            array(),
            $this->redis->lrange('numbers', 1, 0)
        );
        $this->assertEquals(
            $numbers,
            $this->redis->lrange('numbers', 0, -1)
        );
        $this->assertEquals(
            array(5),
            $this->redis->lrange('numbers', 5, -5)
        );
        $this->assertEquals(
            array(),
            $this->redis->lrange('numbers', 7, -5)
        );
        $this->assertEquals(
            array_slice($numbers, -5, -1),
            $this->redis->lrange('numbers', -5, -2)
        );
        $this->assertEquals(
            $numbers,
            $this->redis->lrange('numbers', -100, 100)
        );

        $this->assertEquals(
            array(),
            $this->redis->lrange('keyDoesNotExist', 0, 1)
        );

        // should throw an exception when trying to do a LRANGE on non-list types
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lrange('foo', 0, -1);
        });
    }

    function testListTrim()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers());
        $this->assertTrue($this->redis->ltrim('numbers', 0, 2));
        $this->assertEquals(
            array_slice($numbers, 0, 3),
            $this->redis->lrange('numbers', 0, -1)
        );

        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers(), RC::WIPE_OUT);
        $this->assertTrue($this->redis->ltrim('numbers', 5, 9));
        $this->assertEquals(
            array_slice($numbers, 5, 5),
            $this->redis->lrange('numbers', 0, -1)
        );

        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers(), RC::WIPE_OUT);
        $this->assertTrue($this->redis->ltrim('numbers', 0, -6));
        $this->assertEquals(
            array_slice($numbers, 0, -5),
            $this->redis->lrange('numbers', 0, -1)
        );

        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers(), RC::WIPE_OUT);
        $this->assertTrue($this->redis->ltrim('numbers', -5, -3));
        $this->assertEquals(
            array_slice($numbers, 5, 3),
            $this->redis->lrange('numbers', 0, -1)
        );

        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers(), RC::WIPE_OUT);
        $this->assertTrue($this->redis->ltrim('numbers', -100, 100));
        $this->assertEquals(
            $numbers,
            $this->redis->lrange('numbers', 0, -1)
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->ltrim('foo', 0, 1);
        });
    }

    function testListIndex()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers());

        $this->assertEquals(0, $this->redis->lindex('numbers', 0));
        $this->assertEquals(5, $this->redis->lindex('numbers', 5));
        $this->assertEquals(9, $this->redis->lindex('numbers', 9));
        $this->assertNull($this->redis->lindex('numbers', 100));

        $this->assertEquals(0, $this->redis->lindex('numbers', -0));
        $this->assertEquals(9, $this->redis->lindex('numbers', -1));
        $this->assertEquals(7, $this->redis->lindex('numbers', -3));
        $this->assertNull($this->redis->lindex('numbers', -100));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lindex('foo', 0);
        });
    }

    function testListSet()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers());

        $this->assertTrue($this->redis->lset('numbers', 5, -5));
        $this->assertEquals(-5, $this->redis->lindex('numbers', 5));

        RC::testForServerException($this, RC::EXCEPTION_OUT_OF_RANGE, function($test) {
            $test->redis->lset('numbers', 99, 99);
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lset('foo', 0, 0);
        });
    }

    function testListRemove()
    {
        $mixed = array(0, '_', 2, '_', 4, '_', 6, '_');

        RC::pushTailAndReturn($this->redis, 'mixed', $mixed);
        $this->assertEquals(2, $this->redis->lrem('mixed', 2, '_'));
        $this->assertEquals(array(0, 2, 4, '_', 6, '_'), $this->redis->lrange('mixed', 0, -1));

        RC::pushTailAndReturn($this->redis, 'mixed', $mixed, RC::WIPE_OUT);
        $this->assertEquals(4, $this->redis->lrem('mixed', 0, '_'));
        $this->assertEquals(array(0, 2, 4, 6), $this->redis->lrange('mixed', 0, -1));

        RC::pushTailAndReturn($this->redis, 'mixed', $mixed, RC::WIPE_OUT);
        $this->assertEquals(2, $this->redis->lrem('mixed', -2, '_'));
        $this->assertEquals(array(0, '_', 2, '_', 4, 6), $this->redis->lrange('mixed', 0, -1));

        RC::pushTailAndReturn($this->redis, 'mixed', $mixed, RC::WIPE_OUT);
        $this->assertEquals(0, $this->redis->lrem('mixed', 2, '|'));
        $this->assertEquals($mixed, $this->redis->lrange('mixed', 0, -1));

        $this->assertEquals(0, $this->redis->lrem('listDoesNotExist', 2, '_'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lrem('foo', 0, 0);
        });
    }

    function testListPopFirst()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', array(0, 1, 2, 3, 4));

        $this->assertEquals(0, $this->redis->lpop('numbers'));
        $this->assertEquals(1, $this->redis->lpop('numbers'));
        $this->assertEquals(2, $this->redis->lpop('numbers'));

        $this->assertEquals(array(3, 4), $this->redis->lrange('numbers', 0, -1));

        $this->redis->lpop('numbers');
        $this->redis->lpop('numbers');
        $this->assertNull($this->redis->lpop('numbers'));

        $this->assertNull($this->redis->lpop('numbers'));

        $this->assertNull($this->redis->lpop('listDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->lpop('foo');
        });
    }

    function testListPopLast()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', array(0, 1, 2, 3, 4));

        $this->assertEquals(4, $this->redis->rpop('numbers'));
        $this->assertEquals(3, $this->redis->rpop('numbers'));
        $this->assertEquals(2, $this->redis->rpop('numbers'));

        $this->assertEquals(array(0, 1), $this->redis->lrange('numbers', 0, -1));

        $this->redis->rpop('numbers');
        $this->redis->rpop('numbers');
        $this->assertNull($this->redis->rpop('numbers'));

        $this->assertNull($this->redis->rpop('numbers'));

        $this->assertNull($this->redis->rpop('listDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->rpop('foo');
        });
    }

    function testListPopLastPushHead()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', array(0, 1, 2));
        $this->assertEquals(0, $this->redis->llen('temporary'));
        $this->assertEquals(2, $this->redis->rpoplpush('numbers', 'temporary'));
        $this->assertEquals(1, $this->redis->rpoplpush('numbers', 'temporary'));
        $this->assertEquals(0, $this->redis->rpoplpush('numbers', 'temporary'));
        $this->assertEquals(0, $this->redis->llen('numbers'));
        $this->assertEquals(3, $this->redis->llen('temporary'));
        $this->assertEquals(array(), $this->redis->lrange('numbers', 0, -1));
        $this->assertEquals($numbers, $this->redis->lrange('temporary', 0, -1));

        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', array(0, 1, 2));
        $this->redis->rpoplpush('numbers', 'numbers');
        $this->redis->rpoplpush('numbers', 'numbers');
        $this->redis->rpoplpush('numbers', 'numbers');
        $this->assertEquals($numbers, $this->redis->lrange('numbers', 0, -1));

        $this->assertNull($this->redis->rpoplpush('listDoesNotExist1', 'listDoesNotExist2'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->rpoplpush('foo', 'hoge');
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->rpoplpush('temporary', 'foo');
        });
    }

    function testListBlockingPopFirst()
    {
        // TODO: this test does not cover all the aspects of BLPOP/BRPOP as it
        //       does not run with a concurrent client pushing items on lists.
        RC::helperForBlockingPops('blpop');

        // BLPOP on one key
        $start = time();
        $item = $this->redis->blpop('blpop3', 5);
        $this->assertEquals((float)(time() - $start), 0, '', 1);
        $this->assertEquals($item, array('blpop3', 'c'));

        // BLPOP on more than one key
        $poppedItems = array();
        while ($item = $this->redis->blpop('blpop1', 'blpop2', 1)) {
            $poppedItems[] = $item;
        }
        $this->assertEquals(
            array(array('blpop1', 'a'), array('blpop1', 'd'), array('blpop2', 'b')),
            $poppedItems
        );

        // check if BLPOP timeouts as expected on empty lists
        $start = time();
        $this->redis->blpop('blpop4', 2);
        $this->assertEquals((float)(time() - $start), 2, '', 1);
    }

    function testListBlockingPopLast()
    {
        // TODO: this test does not cover all the aspects of BLPOP/BRPOP as it
        //       does not run with a concurrent client pushing items on lists.
        RC::helperForBlockingPops('brpop');

        // BRPOP on one key
        $start = time();
        $item = $this->redis->brpop('brpop3', 5);
        $this->assertEquals((float)(time() - $start), 0, '', 1);
        $this->assertEquals($item, array('brpop3', 'c'));

        // BRPOP on more than one key
        $poppedItems = array();
        while ($item = $this->redis->brpop('brpop1', 'brpop2', 1)) {
            $poppedItems[] = $item;
        }
        $this->assertEquals(
            array(array('brpop1', 'd'), array('brpop1', 'a'), array('brpop2', 'b')),
            $poppedItems
        );

        // check if BRPOP timeouts as expected on empty lists
        $start = time();
        $this->redis->brpop('brpop4', 2);
        $this->assertEquals((float)(time() - $start), 2, '', 1);
    }

    function testListBlockingPopLastPushHead()
    {
        // TODO: this test does not cover all the aspects of BLPOP/BRPOP as it
        //       does not run with a concurrent client pushing items on lists.
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', array(1, 2, 3));
        $src_count = count($numbers);
        $dst_count = 0;

        while ($item = $this->redis->brpoplpush('numbers', 'temporary', 1)) {
            $this->assertEquals(--$src_count, $this->redis->llen('numbers'));
            $this->assertEquals(++$dst_count, $this->redis->llen('temporary'));
            $this->assertEquals(array_pop($numbers), $this->redis->lindex('temporary', 0));
        }

        $start = time();
        $this->assertNull($this->redis->brpoplpush('numbers', 'temporary', 2));
        $this->assertEquals(2, (float)(time() - $start), '', 1);

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->del('numbers');
            $test->redis->del('temporary');
            $test->redis->set('numbers', 'foobar');
            $test->redis->brpoplpush('numbers', 'temporary', 1);
        });
    }

    function testListInsert()
    {
        $numbers = RC::pushTailAndReturn($this->redis, 'numbers', RC::getArrayOfNumbers());

        $this->assertEquals(11, $this->redis->linsert('numbers', 'before', 0, -2));
        $this->assertEquals(12, $this->redis->linsert('numbers', 'after', -2, -1));
        $this->assertEquals(array(-2, -1, 0, 1), $this->redis->lrange('numbers', 0, 3));

        $this->assertEquals(-1, $this->redis->linsert('numbers', 'after', 100, 200));
        $this->assertEquals(-1, $this->redis->linsert('numbers', 'before', 100, 50));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->linsert('foo', 'before', 0, 0);
        });
    }

    /* commands operating on sets */

    function testSetAdd()
    {
        $this->assertTrue($this->redis->sadd('set', 0));
        $this->assertTrue($this->redis->sadd('set', 1));
        $this->assertFalse($this->redis->sadd('set', 0));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->sadd('foo', 0);
        });
    }

    function testSetRemove()
    {
        $set = RC::setAddAndReturn($this->redis, 'set', array(0, 1, 2, 3, 4));

        $this->assertTrue($this->redis->srem('set', 0));
        $this->assertTrue($this->redis->srem('set', 4));
        $this->assertFalse($this->redis->srem('set', 10));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->srem('foo', 0);
        });
    }

    function testSetPop()
    {
        $set = RC::setAddAndReturn($this->redis, 'set', array(0, 1, 2, 3, 4));

        $this->assertTrue(in_array($this->redis->spop('set'), $set));

        $this->assertNull($this->redis->spop('setDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->spop('foo');
        });
    }

    function testSetMove()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(5, 6, 7, 8, 9, 10));

        $this->assertTrue($this->redis->smove('setA', 'setB', 0));
        $this->assertFalse($this->redis->srem('setA', 0));
        $this->assertTrue($this->redis->srem('setB', 0));

        $this->assertTrue($this->redis->smove('setA', 'setB', 5));
        $this->assertFalse($this->redis->smove('setA', 'setB', 100));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->smove('foo', 'setB', 5);
        });
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->smove('setA', 'foo', 5);
        });
    }

    function testSetCardinality()
    {
        RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5));

        $this->assertEquals(6, $this->redis->scard('setA'));

        // empty set
        $this->redis->sadd('setB', 0);
        $this->redis->spop('setB');
        $this->assertEquals(0, $this->redis->scard('setB'));

        // non-existing set
        $this->assertEquals(0, $this->redis->scard('setDoesNotExist'));

        // wrong type
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->scard('foo');
        });
    }

    function testSetIsMember()
    {
        RC::setAddAndReturn($this->redis, 'set', array(0, 1, 2, 3, 4, 5));

        $this->assertTrue($this->redis->sismember('set', 3));
        $this->assertFalse($this->redis->sismember('set', 100));

        $this->assertFalse($this->redis->sismember('setDoesNotExist', 0));

        // wrong type
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->sismember('foo', 0);
        });
    }

    function testSetMembers()
    {
        $set = RC::setAddAndReturn($this->redis, 'set', array(0, 1, 2, 3, 4, 5, 6));

        $this->assertTrue(RC::sameValuesInArrays($set, $this->redis->smembers('set')));

        $this->assertEquals(array(), $this->redis->smembers('setDoesNotExist'));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->smembers('foo');
        });
    }

    function testSetIntersection()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->sinter('setA')
        ));

        $this->assertTrue(RC::sameValuesInArrays(
            array_intersect($setA, $setB),
            $this->redis->sinter('setA', 'setB')
        ));

        $this->assertEquals(array(), $this->redis->sinter('setA', 'setDoesNotExist'));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sinter('foo');
        });
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sinter('setA', 'foo');
        });
    }

    function testSetIntersectionStore()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertEquals(count($setA), $this->redis->sinterstore('setC', 'setA'));
        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->smembers('setC')
        ));

        $this->redis->del('setC');
        $this->assertEquals(4, $this->redis->sinterstore('setC', 'setA', 'setB'));
        $this->assertTrue(RC::sameValuesInArrays(
            array(1, 3, 4, 6),
            $this->redis->smembers('setC')
        ));

        $this->redis->del('setC');
        $this->assertEquals(array(), $this->redis->sinter('setC', 'setDoesNotExist'));
        $this->assertFalse($this->redis->exists('setC'));

        // existing keys are replaced by SINTERSTORE
        $this->redis->set('foo', 'bar');
        $this->assertEquals(count($setA), $this->redis->sinterstore('foo', 'setA'));

        // accepts an array for the list of source keys
        $this->assertEquals(4, $this->redis->sinterstore('setC', array('setA', 'setB')));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sinterstore('setA', 'foo');
        });
    }

    function testSetUnion()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->sunion('setA')
        ));

        $this->assertTrue(RC::sameValuesInArrays(
            array_union($setA, $setB),
            $this->redis->sunion('setA', 'setB')
        ));

        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->sunion('setA', 'setDoesNotExist')
        ));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sunion('foo');
        });
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sunion('setA', 'foo');
        });
    }

    function testSetUnionStore()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertEquals(count($setA), $this->redis->sunionstore('setC', 'setA'));
        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->smembers('setC')
        ));

        $this->redis->del('setC');
        $this->assertEquals(9, $this->redis->sunionstore('setC', 'setA', 'setB'));
        $this->assertTrue(RC::sameValuesInArrays(
            array_union($setA, $setB),
            $this->redis->smembers('setC')
        ));

        // non-existing keys are considered empty sets
        $this->redis->del('setC');
        $this->assertEquals(0, $this->redis->sunionstore('setC', 'setDoesNotExist'));
        $this->assertFalse($this->redis->exists('setC'));
        $this->assertEquals(0, $this->redis->scard('setC'));

        // existing keys are replaced by SUNIONSTORE
        $this->redis->set('foo', 'bar');
        $this->assertEquals(count($setA), $this->redis->sunionstore('foo', 'setA'));

        // accepts an array for the list of source keys
        $this->assertEquals(9, $this->redis->sunionstore('setC', array('setA', 'setB')));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sunionstore('setA', 'foo');
        });
    }

    function testSetDifference()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->sdiff('setA')
        ));

        $this->assertTrue(RC::sameValuesInArrays(
            array_diff($setA, $setB),
            $this->redis->sdiff('setA', 'setB')
        ));

        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->sdiff('setA', 'setDoesNotExist')
        ));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sdiff('foo');
        });
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sdiff('setA', 'foo');
        });
    }

    function testSetDifferenceStore()
    {
        $setA = RC::setAddAndReturn($this->redis, 'setA', array(0, 1, 2, 3, 4, 5, 6));
        $setB = RC::setAddAndReturn($this->redis, 'setB', array(1, 3, 4, 6, 9, 10));

        $this->assertEquals(count($setA), $this->redis->sdiffstore('setC', 'setA'));
        $this->assertTrue(RC::sameValuesInArrays(
            $setA,
            $this->redis->smembers('setC')
        ));

        $this->redis->del('setC');
        $this->assertEquals(3, $this->redis->sdiffstore('setC', 'setA', 'setB'));
        $this->assertTrue(RC::sameValuesInArrays(
            array_diff($setA, $setB),
            $this->redis->smembers('setC')
        ));

        // non-existing keys are considered empty sets
        $this->redis->del('setC');
        $this->assertEquals(0, $this->redis->sdiffstore('setC', 'setDoesNotExist'));
        $this->assertFalse($this->redis->exists('setC'));
        $this->assertEquals(0, $this->redis->scard('setC'));

        // existing keys are replaced by SDIFFSTORE
        $this->redis->set('foo', 'bar');
        $this->assertEquals(count($setA), $this->redis->sdiffstore('foo', 'setA'));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->sdiffstore('setA', 'foo');
        });
    }

    function testRandomMember()
    {
        $set = RC::setAddAndReturn($this->redis, 'set', array(0, 1, 2, 3, 4, 5, 6));

        $this->assertTrue(in_array($this->redis->srandmember('set'), $set));

        $this->assertNull($this->redis->srandmember('setDoesNotExist'));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->srandmember('foo');
        });
    }

    /* commands operating on sorted sets */

    function testZsetAdd()
    {
        $this->assertTrue($this->redis->zadd('zset', 0, 'a'));
        $this->assertTrue($this->redis->zadd('zset', 1, 'b'));

        $this->assertTrue($this->redis->zadd('zset', -1, 'c'));

        // TODO: floats?
        //$this->assertTrue($this->redis->zadd('zset', -1.0, 'c'));
        //$this->assertTrue($this->redis->zadd('zset', -1.0, 'c'));

        $this->assertFalse($this->redis->zadd('zset', 2, 'b'));
        $this->assertFalse($this->redis->zadd('zset', -2, 'b'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zadd('foo', 0, 'a');
        });
    }

    function testZsetIncrementBy()
    {
        $this->assertEquals(1, $this->redis->zincrby('zsetDoesNotExist', 1, 'foo'));
        $this->assertEquals('zset', $this->redis->type('zsetDoesNotExist'));

        RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());
        $this->assertEquals(-5, $this->redis->zincrby('zset', 5, 'a'));
        $this->assertEquals(1, $this->redis->zincrby('zset', 1, 'b'));
        $this->assertEquals(10, $this->redis->zincrby('zset', 0, 'c'));
        $this->assertEquals(0, $this->redis->zincrby('zset', -20, 'd'));
        $this->assertEquals(2, $this->redis->zincrby('zset', 2, 'd'));
        $this->assertEquals(-10, $this->redis->zincrby('zset', -30, 'e'));
        $this->assertEquals(1, $this->redis->zincrby('zset', 1, 'x'));

        // wrong type
        $this->redis->set('foo', 'bar');
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->zincrby('foo', 1, 'a');
        });
    }

    function testZsetRemove()
    {
        RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertTrue($this->redis->zrem('zset', 'a'));
        $this->assertFalse($this->redis->zrem('zset', 'x'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrem('foo', 'bar');
        });
    }

    function testZsetRange()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(
            array_slice(array_keys($zset), 0, 4),
            $this->redis->zrange('zset', 0, 3)
        );

        $this->assertEquals(
            array_slice(array_keys($zset), 0, 1),
            $this->redis->zrange('zset', 0, 0)
        );

        $this->assertEquals(
            array(),
            $this->redis->zrange('zset', 1, 0)
        );

        $this->assertEquals(
            array_values(array_keys($zset)),
            $this->redis->zrange('zset', 0, -1)
        );

        $this->assertEquals(
            array_slice(array_keys($zset), 3, 1),
            $this->redis->zrange('zset', 3, -3)
        );

        $this->assertEquals(
            array(),
            $this->redis->zrange('zset', 5, -3)
        );

        $this->assertEquals(
            array_slice(array_keys($zset), -5, -1),
            $this->redis->zrange('zset', -5, -2)
        );

        $this->assertEquals(
            array_values(array_keys($zset)),
            $this->redis->zrange('zset', -100, 100)
        );

        $this->assertEquals(
            array_values(array_keys($zset)),
            $this->redis->zrange('zset', -100, 100)
        );

        $this->assertEquals(
            array(array('a', -10), array('b', 0), array('c', 10)),
            $this->redis->zrange('zset', 0, 2, 'withscores')
        );

        $this->assertEquals(
            array(array('a', -10), array('b', 0), array('c', 10)),
            $this->redis->zrange('zset', 0, 2, array('withscores' => true))
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrange('foo', 0, -1);
        });
    }

    function testZsetReverseRange()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(
            array_slice(array_reverse(array_keys($zset)), 0, 4),
            $this->redis->zrevrange('zset', 0, 3)
        );

        $this->assertEquals(
            array_slice(array_reverse(array_keys($zset)), 0, 1),
            $this->redis->zrevrange('zset', 0, 0)
        );

        $this->assertEquals(
            array(),
            $this->redis->zrevrange('zset', 1, 0)
        );

        $this->assertEquals(
            array_values(array_reverse(array_keys($zset))),
            $this->redis->zrevrange('zset', 0, -1)
        );

        $this->assertEquals(
            array_slice(array_reverse(array_keys($zset)), 3, 1),
            $this->redis->zrevrange('zset', 3, -3)
        );

        $this->assertEquals(
            array(),
            $this->redis->zrevrange('zset', 5, -3)
        );

        $this->assertEquals(
            array_slice(array_reverse(array_keys($zset)), -5, -1),
            $this->redis->zrevrange('zset', -5, -2)
        );

        $this->assertEquals(
            array_values(array_reverse(array_keys($zset))),
            $this->redis->zrevrange('zset', -100, 100)
        );

        $this->assertEquals(
            array(array('f', 30), array('e', 20), array('d', 20)),
            $this->redis->zrevrange('zset', 0, 2, 'withscores')
        );

        $this->assertEquals(
            array(array('f', 30), array('e', 20), array('d', 20)),
            $this->redis->zrevrange('zset', 0, 2, array('withscores' => true))
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrevrange('foo', 0, -1);
        });
    }

    function testZsetRangeByScore()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(
            array('a'),
            $this->redis->zrangebyscore('zset', -10, -10)
        );

        $this->assertEquals(
            array('c', 'd', 'e', 'f'),
            $this->redis->zrangebyscore('zset', 10, 30)
        );

        $this->assertEquals(
            array('d', 'e'),
            $this->redis->zrangebyscore('zset', 20, 20)
        );

        $this->assertEquals(
            array(),
            $this->redis->zrangebyscore('zset', 30, 0)
        );

        $this->assertEquals(
            array(array('c', 10), array('d', 20), array('e', 20)),
            $this->redis->zrangebyscore('zset', 10, 20, 'withscores')
        );

        $this->assertEquals(
            array(array('c', 10), array('d', 20), array('e', 20)),
            $this->redis->zrangebyscore('zset', 10, 20, array('withscores' => true))
        );

        $this->assertEquals(
            array('d', 'e'),
            $this->redis->zrangebyscore('zset', 10, 20, array('limit' => array(1, 2)))
        );

        $this->assertEquals(
            array('d', 'e'),
            $this->redis->zrangebyscore('zset', 10, 20, array(
                'limit' => array('offset' => 1, 'count' => 2)
            ))
        );

        $this->assertEquals(
            array(array('d', 20), array('e', 20)),
            $this->redis->zrangebyscore('zset', 10, 20, array(
                'limit'      => array(1, 2),
                'withscores' => true,
            ))
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrangebyscore('foo', 0, 0);
        });
    }

    function testZsetReverseRangeByScore()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(
            array('a'),
            $this->redis->zrevrangebyscore('zset', -10, -10)
        );

        $this->assertEquals(
            array('b', 'a'),
            $this->redis->zrevrangebyscore('zset', 0, -10)
        );

        $this->assertEquals(
            array('e', 'd'),
            $this->redis->zrevrangebyscore('zset', 20, 20)
        );

        $this->assertEquals(
            array('f', 'e', 'd', 'c', 'b'),
            $this->redis->zrevrangebyscore('zset', 30, 0)
        );

        $this->assertEquals(
            array(array('e', 20), array('d', 20), array('c', 10)),
            $this->redis->zrevrangebyscore('zset', 20, 10, 'withscores')
        );

        $this->assertEquals(
            array(array('e', 20), array('d', 20), array('c', 10)),
            $this->redis->zrevrangebyscore('zset', 20, 10, array('withscores' => true))
        );

        $this->assertEquals(
            array('d', 'c'),
            $this->redis->zrevrangebyscore('zset', 20, 10, array('limit' => array(1, 2)))
        );

        $this->assertEquals(
            array('d', 'c'),
            $this->redis->zrevrangebyscore('zset', 20, 10, array(
                'limit' => array('offset' => 1, 'count' => 2)
            ))
        );

        $this->assertEquals(
            array(array('d', 20), array('c', 10)),
            $this->redis->zrevrangebyscore('zset', 20, 10, array(
                'limit'      => array(1, 2),
                'withscores' => true,
            ))
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrevrangebyscore('foo', 0, 0);
        });
    }

    function testZsetUnionStore()
    {
        $zsetA = RC::zsetAddAndReturn($this->redis, 'zseta', array('a' => 1, 'b' => 2, 'c' => 3));
        $zsetB = RC::zsetAddAndReturn($this->redis, 'zsetb', array('b' => 1, 'c' => 2, 'd' => 3));

        // basic ZUNIONSTORE
        $this->assertEquals(4, $this->redis->zunionstore('zsetc', 2, 'zseta', 'zsetb'));
        $this->assertEquals(
            array(array('a', 1), array('b', 3), array('d', 3), array('c', 5)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        $this->assertEquals(3, $this->redis->zunionstore('zsetc', 2, 'zseta', 'zsetbNull'));
        $this->assertEquals(
            array(array('a', 1), array('b', 2), array('c', 3)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        $this->assertEquals(3, $this->redis->zunionstore('zsetc', 2, 'zsetaNull', 'zsetb'));
        $this->assertEquals(
            array(array('b', 1), array('c', 2), array('d', 3)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        $this->assertEquals(0, $this->redis->zunionstore('zsetc', 2, 'zsetaNull', 'zsetbNull'));

        // with WEIGHTS
        $options = array('weights' => array(2, 3));
        $this->assertEquals(4, $this->redis->zunionstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('a', 2), array('b', 7), array('d', 9), array('c', 12)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // with AGGREGATE (min)
        $options = array('aggregate' => 'min');
        $this->assertEquals(4, $this->redis->zunionstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('a', 1), array('b', 1), array('c', 2), array('d', 3)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // with AGGREGATE (max)
        $options = array('aggregate' => 'max');
        $this->assertEquals(4, $this->redis->zunionstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('a', 1), array('b', 2), array('c', 3), array('d', 3)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // using an array to pass the list of source keys
        $sourceKeys = array('zseta', 'zsetb');

        $this->assertEquals(4, $this->redis->zunionstore('zsetc', $sourceKeys));
        $this->assertEquals(
            array(array('a', 1), array('b', 3), array('d', 3), array('c', 5)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // using an array to pass the list of source keys + options array
        $options = array('weights' => array(2, 3));
        $this->assertEquals(4, $this->redis->zunionstore('zsetc', $sourceKeys, $options));
        $this->assertEquals(
            array(array('a', 2), array('b', 7), array('d', 9), array('c', 12)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('zsetFake', 'fake');
            $test->redis->zunionstore('zsetc', 2, 'zseta', 'zsetFake');
        });
    }

    function testZsetIntersectionStore()
    {
        $zsetA = RC::zsetAddAndReturn($this->redis, 'zseta', array('a' => 1, 'b' => 2, 'c' => 3));
        $zsetB = RC::zsetAddAndReturn($this->redis, 'zsetb', array('b' => 1, 'c' => 2, 'd' => 3));

        // basic ZINTERSTORE
        $this->assertEquals(2, $this->redis->zinterstore('zsetc', 2, 'zseta', 'zsetb'));
        $this->assertEquals(
            array(array('b', 3), array('c', 5)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        $this->assertEquals(0, $this->redis->zinterstore('zsetc', 2, 'zseta', 'zsetbNull'));
        $this->assertEquals(0, $this->redis->zinterstore('zsetc', 2, 'zsetaNull', 'zsetb'));
        $this->assertEquals(0, $this->redis->zinterstore('zsetc', 2, 'zsetaNull', 'zsetbNull'));

        // with WEIGHTS
        $options = array('weights' => array(2, 3));
        $this->assertEquals(2, $this->redis->zinterstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('b', 7), array('c', 12)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // with AGGREGATE (min)
        $options = array('aggregate' => 'min');
        $this->assertEquals(2, $this->redis->zinterstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('b', 1), array('c', 2)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // with AGGREGATE (max)
        $options = array('aggregate' => 'max');
        $this->assertEquals(2, $this->redis->zinterstore('zsetc', 2, 'zseta', 'zsetb', $options));
        $this->assertEquals(
            array(array('b', 2), array('c', 3)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // using an array to pass the list of source keys
        $sourceKeys = array('zseta', 'zsetb');

        $this->assertEquals(2, $this->redis->zinterstore('zsetc', $sourceKeys));
        $this->assertEquals(
            array(array('b', 3), array('c', 5)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        // using an array to pass the list of source keys + options array
        $options = array('weights' => array(2, 3));
        $this->assertEquals(2, $this->redis->zinterstore('zsetc', $sourceKeys, $options));
        $this->assertEquals(
            array(array('b', 7), array('c', 12)),
            $this->redis->zrange('zsetc', 0, -1, 'withscores')
        );

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('zsetFake', 'fake');
            $test->redis->zinterstore('zsetc', 2, 'zseta', 'zsetFake');
        });
    }

    function testZsetCount()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(0, $this->redis->zcount('zset', 50, 100));
        $this->assertEquals(6, $this->redis->zcount('zset', -100, 100));
        $this->assertEquals(3, $this->redis->zcount('zset', 10, 20));
        $this->assertEquals(2, $this->redis->zcount('zset', "(10", 20));
        $this->assertEquals(1, $this->redis->zcount('zset', 10, "(20"));
        $this->assertEquals(0, $this->redis->zcount('zset', "(10", "(20"));
        $this->assertEquals(3, $this->redis->zcount('zset', "(0", "(30"));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zcount('foo', 0, 0);
        });
    }

    function testZsetCardinality()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(count($zset), $this->redis->zcard('zset'));

        $this->redis->zrem('zset', 'a');
        $this->assertEquals(count($zset) - 1, $this->redis->zcard('zset'));

        // empty zset
        $this->redis->zadd('zsetB', 0, 'a');
        $this->redis->zrem('zsetB', 'a');
        $this->assertEquals(0, $this->redis->zcard('setB'));

        // non-existing zset
        $this->assertEquals(0, $this->redis->zcard('zsetDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zcard('foo');
        });
    }

    function testZsetScore()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(-10, $this->redis->zscore('zset', 'a'));
        $this->assertEquals(10, $this->redis->zscore('zset', 'c'));
        $this->assertEquals(20, $this->redis->zscore('zset', 'e'));

        $this->assertNull($this->redis->zscore('zset', 'x'));
        $this->assertNull($this->redis->zscore('zsetDoesNotExist', 'a'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zscore('foo', 'bar');
        });
    }

    function testZsetRemoveRangeByScore()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(2, $this->redis->zremrangebyscore('zset', -10, 0));
        $this->assertEquals(
            array('c', 'd', 'e', 'f'),
            $this->redis->zrange('zset', 0, -1)
        );

        $this->assertEquals(1, $this->redis->zremrangebyscore('zset', 10, 10));
        $this->assertEquals(
            array('d', 'e', 'f'),
            $this->redis->zrange('zset', 0, -1)
        );

        $this->assertEquals(0, $this->redis->zremrangebyscore('zset', 100, 100));

        $this->assertEquals(3, $this->redis->zremrangebyscore('zset', 0, 100));
        $this->assertEquals(0, $this->redis->zremrangebyscore('zset', 0, 100));

        $this->assertEquals(0, $this->redis->zremrangebyscore('zsetDoesNotExist', 0, 100));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zremrangebyscore('foo', 0, 0);
        });
    }

    function testZsetRank()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(0, $this->redis->zrank('zset', 'a'));
        $this->assertEquals(1, $this->redis->zrank('zset', 'b'));
        $this->assertEquals(4, $this->redis->zrank('zset', 'e'));

        $this->redis->zrem('zset', 'd');
        $this->assertEquals(3, $this->redis->zrank('zset', 'e'));

        $this->assertNull($this->redis->zrank('zset', 'x'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrank('foo', 'a');
        });
    }

    function testZsetReverseRank()
    {
        $zset = RC::zsetAddAndReturn($this->redis, 'zset', RC::getZSetArray());

        $this->assertEquals(5, $this->redis->zrevrank('zset', 'a'));
        $this->assertEquals(4, $this->redis->zrevrank('zset', 'b'));
        $this->assertEquals(1, $this->redis->zrevrank('zset', 'e'));

        $this->redis->zrem('zset', 'e');
        $this->assertEquals(1, $this->redis->zrevrank('zset', 'd'));

        $this->assertNull($this->redis->zrevrank('zset', 'x'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zrevrank('foo', 'a');
        });
    }

    function testZsetRemoveRangeByRank()
    {
        RC::zsetAddAndReturn($this->redis, 'zseta', RC::getZSetArray());

        $this->assertEquals(3, $this->redis->zremrangebyrank('zseta', 0, 2));
        $this->assertEquals(
            array('d', 'e', 'f'),
            $this->redis->zrange('zseta', 0, -1)
        );

        $this->assertEquals(1, $this->redis->zremrangebyrank('zseta', 0, 0));
        $this->assertEquals(
            array('e', 'f'),
            $this->redis->zrange('zseta', 0, -1)
        );

        RC::zsetAddAndReturn($this->redis, 'zsetb', RC::getZSetArray());
        $this->assertEquals(3, $this->redis->zremrangebyrank('zsetb', -3, -1));
        $this->assertEquals(
            array('a', 'b', 'c'),
            $this->redis->zrange('zsetb', 0, -1)
        );

        $this->assertEquals(1, $this->redis->zremrangebyrank('zsetb', -1, -1));
        $this->assertEquals(
            array('a', 'b'),
            $this->redis->zrange('zsetb', 0, -1)
        );

        $this->assertEquals(2, $this->redis->zremrangebyrank('zsetb', -2, 1));
        $this->assertEquals(
            array(),
            $this->redis->zrange('zsetb', 0, -1)
        );
        $this->assertFalse($this->redis->exists('zsetb'));

        $this->assertEquals(0, $this->redis->zremrangebyrank('zsetc', 0, 0));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->zremrangebyrank('foo', 0, 1);
        });
    }

    /* commands operating on hashes */

    function testHashSet()
    {
        $this->assertTrue($this->redis->hset('metavars', 'foo', 'bar'));
        $this->assertTrue($this->redis->hset('metavars', 'hoge', 'piyo'));
        $this->assertEquals('bar', $this->redis->hget('metavars', 'foo'));
        $this->assertEquals('piyo', $this->redis->hget('metavars', 'hoge'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('test', 'foobar');
            $test->redis->hset('test', 'hoge', 'piyo');
        });
    }

    function testHashGet()
    {
        $this->assertTrue($this->redis->hset('metavars', 'foo', 'bar'));
        $this->assertEquals('bar', $this->redis->hget('metavars', 'foo'));

        $this->assertEquals('bar', $this->redis->hget('metavars', 'foo'));
        $this->assertNull($this->redis->hget('metavars', 'hoge'));
        $this->assertNull($this->redis->hget('hashDoesNotExist', 'field'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->rpush('metavars', 'foo');
            $test->redis->hget('metavars', 'foo');
        });
    }

    function testHashExists()
    {
        $this->assertTrue($this->redis->hset('metavars', 'foo', 'bar'));
        $this->assertTrue($this->redis->hexists('metavars', 'foo'));
        $this->assertFalse($this->redis->hexists('metavars', 'hoge'));
        $this->assertFalse($this->redis->hexists('hashDoesNotExist', 'field'));
    }

    function testHashDelete()
    {
        $this->assertTrue($this->redis->hset('metavars', 'foo', 'bar'));
        $this->assertTrue($this->redis->hexists('metavars', 'foo'));
        $this->assertTrue($this->redis->hdel('metavars', 'foo'));
        $this->assertFalse($this->redis->hexists('metavars', 'foo'));

        $this->assertFalse($this->redis->hdel('metavars', 'hoge'));
        $this->assertFalse($this->redis->hdel('hashDoesNotExist', 'field'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hdel('foo', 'field');
        });
    }

    function testHashLength()
    {
        $this->assertTrue($this->redis->hset('metavars', 'foo', 'bar'));
        $this->assertTrue($this->redis->hset('metavars', 'hoge', 'piyo'));
        $this->assertTrue($this->redis->hset('metavars', 'foofoo', 'barbar'));
        $this->assertTrue($this->redis->hset('metavars', 'hogehoge', 'piyopiyo'));

        $this->assertEquals(4, $this->redis->hlen('metavars'));
        $this->assertEquals(0, $this->redis->hlen('hashDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hlen('foo');
        });
    }

    function testHashSetPreserve()
    {
        $this->assertTrue($this->redis->hsetnx('metavars', 'foo', 'bar'));
        $this->assertFalse($this->redis->hsetnx('metavars', 'foo', 'barbar'));
        $this->assertEquals('bar', $this->redis->hget('metavars', 'foo'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('test', 'foobar');
            $test->redis->hsetnx('test', 'hoge', 'piyo');
        });
    }

    function testHashSetAndGetMultiple()
    {
        $hashKVs = array('foo' => 'bar', 'hoge' => 'piyo');

        // key=>value pairs via array instance
        $this->assertTrue($this->redis->hmset('metavars', $hashKVs));
        $multiRet = $this->redis->hmget('metavars', array_keys($hashKVs));
        $this->assertEquals($hashKVs, array_combine(array_keys($hashKVs), array_values($multiRet)));

        // key=>value pairs via function arguments
        $this->redis->del('metavars');
        $this->assertTrue($this->redis->hmset('metavars', 'foo', 'bar', 'hoge', 'piyo'));
        $this->assertEquals(array('bar', 'piyo'), $this->redis->hmget('metavars', 'foo', 'hoge'));
    }

    function testHashIncrementBy()
    {
        // test subsequent increment commands
        $this->assertEquals(10, $this->redis->hincrby('hash', 'counter', 10));
        $this->assertEquals(20, $this->redis->hincrby('hash', 'counter', 10));
        $this->assertEquals(0, $this->redis->hincrby('hash', 'counter', -20));

        RC::testForServerException($this, RC::EXCEPTION_HASH_VALNOTINT, function($test) {
            $test->redis->hset('hash', 'field', 'stringvalue');
            $test->redis->hincrby('hash', 'field', 10);
        });

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hincrby('foo', 'bar', 1);
        });
    }

    function testHashKeys()
    {
        $hashKVs = array('foo' => 'bar', 'hoge' => 'piyo');
        $this->assertTrue($this->redis->hmset('metavars', $hashKVs));

        $this->assertEquals(array_keys($hashKVs), $this->redis->hkeys('metavars'));
        $this->assertEquals(array(), $this->redis->hkeys('hashDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hkeys('foo');
        });
    }

    function testHashValues()
    {
        $hashKVs = array('foo' => 'bar', 'hoge' => 'piyo');
        $this->assertTrue($this->redis->hmset('metavars', $hashKVs));

        $this->assertEquals(array_values($hashKVs), $this->redis->hvals('metavars'));
        $this->assertEquals(array(), $this->redis->hvals('hashDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hvals('foo');
        });
    }

    function testHashGetAll()
    {
        $hashKVs = array('foo' => 'bar', 'hoge' => 'piyo');
        $this->assertTrue($this->redis->hmset('metavars', $hashKVs));

        $this->assertEquals($hashKVs, $this->redis->hgetall('metavars'));
        $this->assertEquals(array(), $this->redis->hgetall('hashDoesNotExist'));

        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->hgetall('foo');
        });
    }

    /* multiple databases handling commands */

    function testSelectDatabase()
    {
        $this->assertTrue($this->redis->select(0));
        $this->assertTrue($this->redis->select(RC::DEFAULT_DATABASE));

        RC::testForServerException($this, RC::EXCEPTION_INVALID_DB_IDX, function($test) {
            $test->redis->select(32);
        });

        RC::testForServerException($this, RC::EXCEPTION_INVALID_DB_IDX, function($test) {
            $test->redis->select(-1);
        });
    }

    function testMove()
    {
        // TODO: This test sucks big time. Period.
        $otherDb = 5;
        $this->redis->set('foo', 'bar');

        $this->redis->select($otherDb);
        $this->redis->flushdb();
        $this->redis->select(RC::DEFAULT_DATABASE);

        $this->assertTrue($this->redis->move('foo', $otherDb));
        $this->assertFalse($this->redis->move('foo', $otherDb));
        $this->assertFalse($this->redis->move('keyDoesNotExist', $otherDb));

        $this->redis->set('hoge', 'piyo');
        // TODO: shouldn't Redis send an EXCEPTION_INVALID_DB_IDX instead of EXCEPTION_OUT_OF_RANGE?
        RC::testForServerException($this, RC::EXCEPTION_OUT_OF_RANGE, function($test) {
            $test->redis->move('hoge', 32);
        });
    }

    function testFlushdb()
    {
        $this->assertTrue($this->redis->flushdb());
    }

    /* sorting */

    function testSort()
    {
        $unorderedList = RC::pushTailAndReturn($this->redis, 'unordered', array(2, 100, 3, 1, 30, 10));

        // without parameters
        $this->assertEquals(array(1, 2, 3, 10, 30, 100), $this->redis->sort('unordered'));

        // with parameter ASC/DESC
        $this->assertEquals(
            array(100, 30, 10, 3, 2, 1),
            $this->redis->sort('unordered', array(
                'sort' => 'desc'
            ))
        );

        // with parameter LIMIT
        $this->assertEquals(
            array(1, 2, 3),
            $this->redis->sort('unordered', array(
                'limit' => array(0, 3)
            ))
        );
        $this->assertEquals(
            array(10, 30),
            $this->redis->sort('unordered', array(
                'limit' => array(3, 2)
            ))
        );

        // with parameter ALPHA
        $this->assertEquals(
            array(1, 10, 100, 2, 3, 30),
            $this->redis->sort('unordered', array(
                'alpha' => true
            ))
        );

        // with combined parameters
        $this->assertEquals(
            array(30, 10, 3, 2),
            $this->redis->sort('unordered', array(
                'alpha' => false,
                'sort'  => 'desc',
                'limit' => array(1, 4)
            ))
        );

        // with parameter ALPHA
        $this->assertEquals(
            array(1, 10, 100, 2, 3, 30),
            $this->redis->sort('unordered', array(
                'alpha' => true
            ))
        );

        // with parameter STORE
        $this->assertEquals(
            count($unorderedList),
            $this->redis->sort('unordered', array(
                'store' => 'ordered'
            ))
        );
        $this->assertEquals(array(1, 2, 3, 10, 30, 100),  $this->redis->lrange('ordered', 0, -1));

        // with parameter GET
        $this->redis->rpush('uids', 1003);
        $this->redis->rpush('uids', 1001);
        $this->redis->rpush('uids', 1002);
        $this->redis->rpush('uids', 1000);
        $sortget = array(
            'uid:1000' => 'foo',  'uid:1001' => 'bar',
            'uid:1002' => 'hoge', 'uid:1003' => 'piyo'
        );
        $this->redis->mset($sortget);
        $this->assertEquals(
            array_values($sortget),
            $this->redis->sort('uids', array('get' => 'uid:*'))
        );

        // wrong type
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function($test) {
            $test->redis->set('foo', 'bar');
            $test->redis->sort('foo');
        });
    }

    /* remote server control commands */

    function testInfo()
    {
        $serverInfo = $this->redis->info();

        $this->assertInternalType('array', $serverInfo);
        $this->assertNotNull($serverInfo['redis_version']);
        $this->assertGreaterThan(0, $serverInfo['uptime_in_seconds']);
        $this->assertGreaterThan(0, $serverInfo['total_connections_received']);
    }

    function testSlaveOf()
    {
        $masterHost = 'www.google.com';
        $masterPort = 80;

        $this->assertTrue($this->redis->slaveof($masterHost, $masterPort));

        // slave of NO ONE, the implicit way
        $this->assertTrue($this->redis->slaveof());

        // slave of NO ONE, the explicit way
        $this->assertTrue($this->redis->slaveof('NO ONE'));
    }

    /* persistence control commands */

    function testSave()
    {
        $this->assertTrue($this->redis->save());
    }

    function testBackgroundSave()
    {
        $this->assertTrue($this->redis->bgsave());
    }

    function testBackgroundRewriteAppendOnlyFile()
    {
        $this->assertTrue($this->redis->bgrewriteaof());
    }

    function testLastSave()
    {
        $this->assertGreaterThan(0, $this->redis->lastsave());
    }
}
