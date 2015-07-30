<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PredisTestCase;

/**
 * @todo Parameters::define();
 * @todo Parameters::undefine();
 */
class ParametersTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultValues()
    {
        $defaults = $this->getDefaultParametersArray();
        $parameters = new Parameters();

        $this->assertEquals($defaults['scheme'], $parameters->scheme);
        $this->assertEquals($defaults['host'], $parameters->host);
        $this->assertEquals($defaults['port'], $parameters->port);
    }

    /**
     * @group disconnected
     */
    public function testIsSet()
    {
        $parameters = new Parameters();

        $this->assertTrue(isset($parameters->scheme));
        $this->assertFalse(isset($parameters->unknown));
    }

    public function sharedTestsWithArrayParameters(Parameters $parameters)
    {
        $this->assertTrue(isset($parameters->scheme));
        $this->assertSame('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->port));
        $this->assertSame(7000, $parameters->port);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);

        $this->assertFalse(isset($parameters->unknown));
        $this->assertNull($parameters->unknown);
    }

    /**
     * @group disconnected
     */
    public function testConstructWithArrayParameters()
    {
        $parameters = new Parameters(array(
            'port' => 7000,
            'custom' => 'foobar',
        ));

        $this->sharedTestsWithArrayParameters($parameters);
    }

    /**
     * @group disconnected
     */
    public function testCreateWithArrayParameters()
    {
        $parameters = new Parameters(array(
            'port' => 7000,
            'custom' => 'foobar',
        ));

        $this->sharedTestsWithArrayParameters($parameters);
    }

    /**
     * @group disconnected
     */
    public function testCreateWithUriString()
    {
        $overrides = array(
            'port' => 7000,
            'database' => 5,
            'custom' => 'foobar',
        );

        $uriString = $this->getParametersString($overrides);
        $parameters = Parameters::create($uriString);

        $this->sharedTestsWithArrayParameters($parameters);
        $this->assertEquals($overrides['database'], $parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testToArray()
    {
        $additional = array('port' => 7000, 'custom' => 'foobar');
        $parameters = new Parameters($additional);

        $this->assertEquals($this->getParametersArray($additional), $parameters->toArray());
    }

    /**
     * @group disconnected
     */
    public function testSerialization()
    {
        $parameters = new Parameters(array('port' => 7000, 'custom' => 'foobar'));
        $unserialized = unserialize(serialize($parameters));

        $this->assertEquals($parameters->scheme, $unserialized->scheme);
        $this->assertEquals($parameters->port, $unserialized->port);

        $this->assertTrue(isset($unserialized->custom));
        $this->assertEquals($parameters->custom, $unserialized->custom);

        $this->assertFalse(isset($unserialized->unknown));
        $this->assertNull($unserialized->unknown);
    }

    /**
     * @group disconnected
     */
    public function testParsingURI()
    {
        $uri = 'tcp://10.10.10.10:6400?timeout=0.5&persistent=1&database=5&password=secret';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'port' => 6400,
            'timeout' => '0.5',
            'persistent' => '1',
            'database' => '5',
            'password' => 'secret',
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithRedisScheme()
    {
        $uri = 'redis://:secret@10.10.10.10:6400/5?timeout=0.5&persistent=1';

        $expected = array(
            'scheme' => 'redis',
            'host' => '10.10.10.10',
            'port' => 6400,
            'timeout' => '0.5',
            'persistent' => '1',
            'password' => 'secret',
            'database' => '5',
        );

        $parameters = Parameters::parse($uri);

        // TODO: parse_url() in PHP >= 5.6 returns an empty "user" entry in the
        // dictionary when no username has been provided in the URI string. This
        // actually makes sense, but let's keep the test ugly & simple for now.
        unset($parameters['user']);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithRedisSchemeMustPreserveRemainderOfPath()
    {
        $uri = 'redis://10.10.10.10/5/rest/of/path';

        $expected = array(
            'scheme' => 'redis',
            'host' => '10.10.10.10',
            'path' => '/rest/of/path',
            'database' => '5',
        );

        $parameters = Parameters::parse($uri);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @group disconnected
     */
    public function testRedisSchemeOverridesPasswordAndDatabaseInQueryString()
    {
        $parameters = Parameters::parse('redis://:secret@10.10.10.10/5?password=ignored&database=4');

        $this->assertSame('secret', $parameters['password']);
        $this->assertSame('5', $parameters['database']);
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithUnixDomainSocket()
    {
        $uri = 'unix:///tmp/redis.sock?timeout=0.5&persistent=1';

        $expected = array(
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
            'timeout' => '0.5',
            'persistent' => '1',
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithUnixDomainSocketOldWay()
    {
        $uri = 'unix:/tmp/redis.sock?timeout=0.5&persistent=1';

        $expected = array(
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
            'timeout' => '0.5',
            'persistent' => '1',
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithIncompletePairInQueryString()
    {
        $uri = 'tcp://10.10.10.10?persistent=1&foo=&bar';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'persistent' => '1',
            'foo' => '',
            'bar' => '',
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithMoreThanOneEqualSignInQueryStringPairValue()
    {
        $uri = 'tcp://10.10.10.10?foobar=a=b=c&persistent=1';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'foobar' => 'a=b=c',
            'persistent' => '1',
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWhenQueryStringHasBracketsInFieldnames()
    {
        $uri = 'tcp://10.10.10.10?persistent=1&metavars[]=foo&metavars[]=hoge';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'persistent' => '1',
            'metavars' => array('foo', 'hoge'),
        );

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithEmbeddedIPV6AddressShouldStripBracketsFromHost()
    {
        $expected = array('scheme' => 'tcp', 'host' => '::1', 'port' => 7000);
        $this->assertSame($expected, Parameters::parse('tcp://[::1]:7000'));

        $expected = array('scheme' => 'tcp', 'host' => '2001:db8:0:f101::1', 'port' => 7000);
        $this->assertSame($expected, Parameters::parse('tcp://[2001:db8:0:f101::1]:7000'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid parameters URI: tcp://invalid:uri
     */
    public function testParsingURIThrowOnInvalidURI()
    {
        Parameters::parse('tcp://invalid:uri');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultParametersArray()
    {
        return array(
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        );
    }

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param array $parameters array of connection parameters.
     *
     * @return string URI string.
     */
    protected function getParametersString(array $parameters)
    {
        $defaults = $this->getDefaultParametersArray();

        $scheme = isset($parameters['scheme']) ? $parameters['scheme'] : $defaults['scheme'];
        $host = isset($parameters['host']) ? $parameters['host'] : $defaults['host'];
        $port = isset($parameters['port']) ? $parameters['port'] : $defaults['port'];

        unset($parameters['scheme'], $parameters['host'], $parameters['port']);
        $uriString = "$scheme://$host:$port/?";

        foreach ($parameters as $k => $v) {
            $uriString .= "$k=$v&";
        }

        return $uriString;
    }
}
