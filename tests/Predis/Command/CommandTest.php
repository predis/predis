<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\Redis\BloomFilter\BFADD;
use Predis\Command\Redis\CountMinSketch\CMSINFO;
use Predis\Command\Redis\CuckooFilter\CFADD;
use Predis\Command\Redis\GET;
use Predis\Command\Redis\Json\JSONSET;
use Predis\Command\Redis\MGET;
use Predis\Command\Redis\Search\FTSEARCH;
use Predis\Command\Redis\TDigest\TDIGESTADD;
use Predis\Command\Redis\TimeSeries\TSGET;
use Predis\Command\Redis\TopK\TOPKQUERY;
use Predis\Command\Redis\ZADD;
use PredisTestCase;
use stdClass;
use UnexpectedValueException;

class CommandTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testImplementsCorrectInterface(): void
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
    }

    /**
     * @group disconnected
     */
    public function testGetEmptyArguments(): void
    {
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEmpty($command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testSetRawArguments(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     *
     * @todo We cannot set an expectation for Command::filterArguments() when we
     *       invoke Command::setArguments() because it is protected.
     */
    public function testSetArguments(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetArgumentAtIndex(): void
    {
        $arguments = ['1st', '2nd', '3rd'];

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setArguments($arguments);

        $this->assertEquals($arguments[0], $command->getArgument(0));
        $this->assertEquals($arguments[2], $command->getArgument(2));
        $this->assertNull($command->getArgument(10));
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $response = 'response-buffer';
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEquals($response, $command->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testParseResp3Response(): void
    {
        $response = 'response-buffer';
        $command = $this->getMockForAbstractClass('Predis\Command\Command');

        $this->assertEquals($response, $command->parseResp3Response($response));
    }

    /**
     * @group disconnected
     */
    public function testSetAndGetSlot(): void
    {
        $slot = 1024;

        $command = $this->getMockForAbstractClass('Predis\Command\Command');
        $command->setRawArguments(['key']);

        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $this->assertSame($slot, $command->getSlot());

        $command->setArguments(['key']);
        $this->assertNull($command->getSlot());

        $command->setSlot($slot);
        $command->setRawArguments(['key']);
        $this->assertNull($command->getSlot());
    }

    /**
     * @group disconnected
     */
    public function testNormalizeArguments(): void
    {
        $arguments = ['arg1', 'arg2', 'arg3', 'arg4'];

        $this->assertSame($arguments, Command::normalizeArguments($arguments));
        $this->assertSame($arguments, Command::normalizeArguments([$arguments]));

        $arguments = [[], []];
        $this->assertSame($arguments, Command::normalizeArguments($arguments));

        $arguments = [new stdClass()];
        $this->assertSame($arguments, Command::normalizeArguments($arguments));
    }

    /**
     * @group disconnected
     */
    public function testNormalizeVariadic(): void
    {
        $arguments = ['key', 'value1', 'value2', 'value3'];

        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
        $this->assertSame($arguments, Command::normalizeVariadic(['key', ['value1', 'value2', 'value3']]));

        $arguments = [new stdClass()];
        $this->assertSame($arguments, Command::normalizeVariadic($arguments));
    }

    /**
     * @group disconnected
     */
    public function testSerializeCommand(): void
    {
        $command = new class extends Command {
            public function getId()
            {
                return 'Test';
            }

            public function getArguments()
            {
                return ['foo', 'bar'];
            }
        };

        $this->assertSame(
            "*3\r\n\$4\r\nTest\r\n\$3\r\nfoo\r\n\$3\r\nbar\r\n",
            $command->serializeCommand()
        );
    }

    /**
     * @dataProvider deserializeCommandProvider
     * @group disconnected
     */
    public function testDeserializeCommand(string $class, array $arguments): void
    {
        $command = new $class();
        $command->setArguments($arguments);

        $deserializedCommand = Command::deserializeCommand($command->serializeCommand());

        $this->assertInstanceOf($class, $deserializedCommand);
        $this->assertSame($command->getArguments(), $deserializedCommand->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDeserializeCommandThrowsException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid serializing format');

        Command::deserializeCommand('foobar');
    }

    public function deserializeCommandProvider(): array
    {
        return [
            'GET' => [
                GET::class,
                ['arg1'],
            ],
            'MGET' => [
                MGET::class,
                ['arg1', 'arg2', 'arg3'],
            ],
            'ZADD' => [
                ZADD::class,
                ['key', 'value', 'key1', 'value1'],
            ],
            'JSONSET' => [
                JSONSET::class,
                ['key', '$', '{"key":"value"}'],
            ],
            'BFADD' => [
                BFADD::class,
                ['key', 'value'],
            ],
            'CFADD' => [
                CFADD::class,
                ['key', 'value'],
            ],
            'CMSINFO' => [
                CMSINFO::class,
                ['key', 'value'],
            ],
            'FTSEARCH' => [
                FTSEARCH::class,
                ['key', 'value'],
            ],
            'TDIGESTADD' => [
                TDIGESTADD::class,
                ['key', 'value'],
            ],
            'TSGET' => [
                TSGET::class,
                ['key'],
            ],
            'TOPKQUERY' => [
                TOPKQUERY::class,
                ['key'],
            ],
        ];
    }
}
