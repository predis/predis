<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Container\BLESS as Container;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-generic
 */
class BLESS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return BLESS::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BLESS';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame([], $this->getCommand()->parseResponse([]));
        $this->assertSame(['a', 'b'], $this->getCommand()->parseResponse(['a', 'b']));
    }

    /**
     * @group disconnected
     * @dataProvider prefixKeysProvider
     */
    public function testPrefixKeys(array $arguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider containerProvider
     */
    public function testContainerBuildsSubcommand(string $method, array $methodArguments, array $expectedArguments, $response): void
    {
        $client = $this->getMockBuilder('Predis\ClientInterface')->getMock();
        $command = $this->getCommand();

        // The container forwards the subcommand followed by the raw method arguments;
        // argument normalisation (e.g. COUNT expansion) happens inside the command.
        $rawArguments = array_merge([strtoupper($method)], $methodArguments);

        $client
            ->expects($this->once())
            ->method('createCommand')
            ->with('BLESS', $rawArguments)
            ->willReturnCallback(function () use ($command, $rawArguments) {
                $command->setArguments($rawArguments);

                return $command;
            });

        $client
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->willReturn($response);

        $container = new Container($client);

        $this->assertSame('BLESS', $container->getContainerCommandId());
        $this->assertSame($response, $container->$method(...$methodArguments));
        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.12.0
     */
    public function testSetGetScanAndClearFlags(): void
    {
        $this->assertBlessRoundTrip($this->getClient());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.12.0
     */
    public function testSetGetScanAndClearFlagsResp3(): void
    {
        $this->assertBlessRoundTrip($this->getResp3Client());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.12.0
     */
    public function testThrowsExceptionOnMissingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR no such key');

        $redis->bless->get('missing');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.12.0
     */
    public function testThrowsExceptionOnUnknownFlag(): void
    {
        $redis = $this->getClient();
        $redis->set('foo', 'bar');

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR syntax error');

        $redis->bless->set('foo', 'BOGUS');
    }

    private function assertBlessRoundTrip($redis): void
    {
        $redis->set('foo', 'bar');
        $redis->set('baz', 'qux');
        $redis->set('plain', 'value');

        $this->assertSame([], $redis->bless->get('foo'));

        $this->assertSame(1, $redis->bless->set('foo', 'NO-EVICT'));
        $this->assertSame(0, $redis->bless->set('foo', 'NO-EVICT'));
        $this->assertSame(1, $redis->bless->set('baz', 'NO-EVICT'));

        $this->assertSame(['NO-EVICT'], $redis->bless->get('foo'));
        $this->assertSame([], $redis->bless->get('plain'));

        // Full scan in one call.
        [$cursor, $keys] = $redis->bless->scan(0, 'NO-EVICT');
        $this->assertSame('0', $cursor);
        $this->assertEqualsCanonicalizing(['foo', 'baz'], $keys);

        // Iterating with COUNT must eventually visit every flagged key (duplicates are allowed, as with SCAN).
        $cursor = 0;
        $seen = [];
        do {
            [$cursor, $keys] = $redis->bless->scan($cursor, 'NO-EVICT', ['COUNT' => 1]);
            $seen = array_merge($seen, $keys);
        } while ($cursor !== '0');
        $this->assertEqualsCanonicalizing(['foo', 'baz'], array_unique($seen));

        $this->assertSame(1, $redis->bless->clear('foo', 'NO-EVICT'));
        $this->assertSame(0, $redis->bless->clear('foo', 'NO-EVICT'));
        $this->assertSame([], $redis->bless->get('foo'));

        [$cursor, $keys] = $redis->bless->scan(0, 'NO-EVICT');
        $this->assertSame('0', $cursor);
        $this->assertSame(['baz'], $keys);
    }

    public function argumentsProvider(): array
    {
        return [
            'with GET subcommand' => [
                ['GET', 'key'],
                ['GET', 'key'],
            ],
            'with SET subcommand' => [
                ['SET', 'key', 'flag'],
                ['SET', 'key', 'flag'],
            ],
            'with CLEAR subcommand' => [
                ['CLEAR', 'key', 'flag'],
                ['CLEAR', 'key', 'flag'],
            ],
            'with lowercase subcommand' => [
                ['get', 'key'],
                ['get', 'key'],
            ],
            'with SCAN subcommand' => [
                ['SCAN', 0, 'flag'],
                ['SCAN', 0, 'flag'],
            ],
            'with SCAN subcommand and COUNT option' => [
                ['SCAN', 0, 'flag', ['COUNT' => 10]],
                ['SCAN', 0, 'flag', 'COUNT', 10],
            ],
            'with SCAN subcommand and lowercase option key' => [
                ['SCAN', '17', 'flag', ['count' => 10]],
                ['SCAN', '17', 'flag', 'COUNT', 10],
            ],
            'with SCAN subcommand and empty options' => [
                ['SCAN', 0, 'flag', []],
                ['SCAN', 0, 'flag'],
            ],
            'with SCAN subcommand and unknown option' => [
                ['SCAN', 0, 'flag', ['MATCH' => 'x*']],
                ['SCAN', 0, 'flag'],
            ],
            'with lowercase SCAN subcommand and COUNT option' => [
                ['scan', 0, 'flag', ['COUNT' => 10]],
                ['scan', 0, 'flag', 'COUNT', 10],
            ],
        ];
    }

    public function prefixKeysProvider(): array
    {
        return [
            'with GET subcommand' => [
                ['GET', 'key'],
                ['GET', 'prefix:key'],
            ],
            'with SET subcommand' => [
                ['SET', 'key', 'flag'],
                ['SET', 'prefix:key', 'flag'],
            ],
            'with CLEAR subcommand' => [
                ['CLEAR', 'key', 'flag'],
                ['CLEAR', 'prefix:key', 'flag'],
            ],
            'with lowercase subcommand' => [
                ['set', 'key', 'flag'],
                ['set', 'prefix:key', 'flag'],
            ],
            'with keyed subcommand and no key' => [
                ['GET'],
                ['GET'],
            ],
            'with non-keyed subcommand' => [
                ['HELP'],
                ['HELP'],
            ],
            'with SCAN subcommand (cursor is not a key)' => [
                ['SCAN', 0, 'flag', ['COUNT' => 10]],
                ['SCAN', 0, 'flag', 'COUNT', 10],
            ],
        ];
    }

    public function containerProvider(): array
    {
        return [
            'get' => ['get', ['key'], ['GET', 'key'], []],
            'set' => ['set', ['key', 'flag'], ['SET', 'key', 'flag'], 1],
            'clear' => ['clear', ['key', 'flag'], ['CLEAR', 'key', 'flag'], 1],
            'scan' => ['scan', [0, 'flag'], ['SCAN', 0, 'flag'], ['0', ['key1', 'key2']]],
            'scan with count' => ['scan', [0, 'flag', ['COUNT' => 10]], ['SCAN', 0, 'flag', 'COUNT', 10], ['0', ['key1']]],
        ];
    }
}
