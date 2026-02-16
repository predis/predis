<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use PredisTestCase;

/**
 * @group connected
 * @group relay-incompatible
 * @requiresRedisVersion >= 7.2.0
 */
class ClientDriverInfoIntegrationTest extends PredisTestCase
{
    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientSetInfoWithDriverInfo(): void
    {
        $driverInfo = (new DriverInfo())->addUpstreamDriver('my-framework', '1.0.0');
        $client = $this->createClient(['driver_info' => $driverInfo]);

        $clientInfo = $this->parseClientInfo($client->client->info());
        $this->assertArrayHasKey('lib-name', $clientInfo);
        $this->assertArrayHasKey('lib-ver', $clientInfo);
        $this->assertSame('predis(my-framework_v1.0.0)', $clientInfo['lib-name']);
        $this->assertSame(Client::VERSION, $clientInfo['lib-ver']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientSetInfoWithDefaultDriverInfo(): void
    {
        $client = $this->createClient();

        $clientInfo = $this->parseClientInfo($client->client->info());
        $this->assertArrayHasKey('lib-name', $clientInfo);
        $this->assertArrayHasKey('lib-ver', $clientInfo);
        $this->assertSame('predis', $clientInfo['lib-name']);
        $this->assertSame(Client::VERSION, $clientInfo['lib-ver']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientSetInfoWithMultipleUpstreamDrivers(): void
    {
        $driverInfo = (new DriverInfo())
            ->addUpstreamDriver('laravel', '11.0.0')
            ->addUpstreamDriver('symfony', '7.0.0');
        $client = $this->createClient(['driver_info' => $driverInfo]);

        $clientInfo = $this->parseClientInfo($client->client->info());
        $this->assertArrayHasKey('lib-name', $clientInfo);
        $this->assertArrayHasKey('lib-ver', $clientInfo);
        $this->assertSame('predis(symfony_v7.0.0;laravel_v11.0.0)', $clientInfo['lib-name']);
        $this->assertSame(Client::VERSION, $clientInfo['lib-ver']);
    }

    /**
     * Parse CLIENT INFO string response into an associative array.
     *
     * @param  string                $info
     * @return array<string, string>
     */
    private function parseClientInfo(string $info): array
    {
        $result = [];
        $pairs = explode(' ', trim($info));
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $result[$parts[0]] = $parts[1];
            }
        }

        return $result;
    }
}
