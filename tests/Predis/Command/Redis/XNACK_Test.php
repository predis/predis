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

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-stream
 */
class XNACK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XNACK';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XNACK';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
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
    public function testFilterArgumentsThrowsOnInvalidMode(): void
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Mode argument accepts only: silent, fail, fatal values');

        $command = $this->getCommand();
        $command->setArguments(['mystream', 'mygroup', 'INVALID', ['0-1']]);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(2, $this->getCommand()->parseResponse(2));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['mystream', 'mygroup', 'SILENT', ['1526569498055-0', '1526569498055-1']];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:mystream', 'mygroup', 'SILENT', 'IDS', '2', '1526569498055-0', '1526569498055-1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with SILENT mode' => [
                ['mystream', 'mygroup', 'SILENT', ['1526569498055-0', '1526569498055-1']],
                ['mystream', 'mygroup', 'SILENT', 'IDS', '2', '1526569498055-0', '1526569498055-1'],
            ],
            'with FAIL mode' => [
                ['mystream', 'mygroup', 'FAIL', ['1526569498055-0']],
                ['mystream', 'mygroup', 'FAIL', 'IDS', '1', '1526569498055-0'],
            ],
            'with FATAL mode' => [
                ['mystream', 'mygroup', 'FATAL', ['1526569498055-0']],
                ['mystream', 'mygroup', 'FATAL', 'IDS', '1', '1526569498055-0'],
            ],
            'with lowercase mode normalized to uppercase' => [
                ['mystream', 'mygroup', 'silent', ['1526569498055-0']],
                ['mystream', 'mygroup', 'SILENT', 'IDS', '1', '1526569498055-0'],
            ],
            'with RETRYCOUNT option' => [
                ['mystream', 'mygroup', 'FAIL', ['1526569498055-0'], 3],
                ['mystream', 'mygroup', 'FAIL', 'IDS', '1', '1526569498055-0', 'RETRYCOUNT', 3],
            ],
            'with RETRYCOUNT zero' => [
                ['mystream', 'mygroup', 'SILENT', ['1526569498055-0'], 0],
                ['mystream', 'mygroup', 'SILENT', 'IDS', '1', '1526569498055-0', 'RETRYCOUNT', 0],
            ],
            'with FORCE flag' => [
                ['mystream', 'mygroup', 'FAIL', ['1526569498055-0'], null, true],
                ['mystream', 'mygroup', 'FAIL', 'IDS', '1', '1526569498055-0', 'FORCE'],
            ],
            'with RETRYCOUNT and FORCE' => [
                ['mystream', 'mygroup', 'FATAL', ['1526569498055-0', '1526569498055-1'], 5, true],
                ['mystream', 'mygroup', 'FATAL', 'IDS', '2', '1526569498055-0', '1526569498055-1', 'RETRYCOUNT', 5, 'FORCE'],
            ],
        ];
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithSilentMode(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 2, null, false, 'stream', '>');

        $result = $redis->xnack('stream', 'grp', 'SILENT', ['0-1', '1-1']);

        $this->assertSame(2, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithFailMode(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 1, null, false, 'stream', '>');

        $result = $redis->xnack('stream', 'grp', 'FAIL', ['0-1']);

        $this->assertSame(1, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithFatalMode(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 1, null, false, 'stream', '>');

        $result = $redis->xnack('stream', 'grp', 'FATAL', ['0-1']);

        $this->assertSame(1, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithSomeIdsNotInPel(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 1, null, false, 'stream', '>');

        // Only 0-1 is in PEL, 1-1 is not claimed yet
        $result = $redis->xnack('stream', 'grp', 'FAIL', ['0-1', '1-1', '9-9']);

        $this->assertSame(1, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithRetryCount(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 1, null, false, 'stream', '>');

        $result = $redis->xnack('stream', 'grp', 'FAIL', ['0-1'], 5);

        $this->assertSame(1, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithForceFlag(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xgroup->create('stream', 'grp', '0');

        // FORCE creates PEL entry even if not claimed
        $result = $redis->xnack('stream', 'grp', 'FAIL', ['0-1'], null, true);

        $this->assertSame(1, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackOnNonExistentStreamReturnsError(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/NOGROUP/');

        $redis = $this->getClient();
        $redis->xnack('nonexistent', 'grp', 'FAIL', ['0-1']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithNonExistentGroupReturnsError(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/NOGROUP/');

        $redis = $this->getClient();
        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xnack('stream', 'nonexistent', 'FAIL', ['0-1']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackOnWrongTypeReturnsError(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/WRONGTYPE/');

        $redis = $this->getClient();
        $redis->set('notastream', 'somevalue');
        $redis->xnack('notastream', 'grp', 'FAIL', ['0-1']);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNackWithResp3Protocol(): void
    {
        $redis = $this->getResp3Client();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xgroup->create('stream', 'grp', '0');
        $redis->xreadgroup('grp', 'consumer1', 1, null, false, 'stream', '>');

        $result = $redis->xnack('stream', 'grp', 'SILENT', ['0-1']);

        $this->assertSame(1, $result);
    }
}
