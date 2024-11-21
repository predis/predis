<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PredisTestCase;

class ParametersTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultValues(): void
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
    public function testIsSet(): void
    {
        $parameters = new Parameters();

        $this->assertTrue(isset($parameters->scheme), 'Parameter `scheme` was expected to be set');
        $this->assertFalse(isset($parameters->notset), 'Parameter `notset` was expected to be not set');
    }

    public function sharedTestsWithArrayParameters(Parameters $parameters): void
    {
        $this->assertTrue(isset($parameters->scheme), 'Parameter `scheme` was expected to be set');
        $this->assertSame('tcp', $parameters->scheme, 'Parameter `scheme` was expected to be set');

        $this->assertTrue(isset($parameters->port), 'Parameter `port` was expected to be set');
        $this->assertSame(7000, $parameters->port, 'Parameter `port` was expected to return 7000');

        $this->assertTrue(isset($parameters->custom), 'Parameter `custom` was expected to be set');
        $this->assertSame('foobar', $parameters->custom, 'Parameter `custom` was expected to return "foobar"');

        $this->assertFalse(isset($parameters->setnull), 'Parameter `setnull` was expected to be not set');
        $this->assertNull($parameters->setnull, 'Parameter `setnull` was expected to return NULL');

        $this->assertFalse(isset($parameters->setemptystring), 'Parameter `setemptystring` was expected to be not set');
        $this->assertNull($parameters->setemptystring, 'Parameter `setemptystring` was expected to return NULL');

        $this->assertFalse(isset($parameters->notset), 'Parameter `notset` was expected to be not set');
        $this->assertNull($parameters->notset, 'Parameter `notset` was expected to return NULL');
    }

    /**
     * @group disconnected
     */
    public function testConstructWithArrayParameters(): void
    {
        $parameters = new Parameters([
            'port' => 7000,
            'custom' => 'foobar',
            'setnull' => null,
            'setemptystring' => '',
        ]);

        $this->sharedTestsWithArrayParameters($parameters);
    }

    /**
     * @group disconnected
     */
    public function testCreateWithArrayParameters(): void
    {
        $parameters = new Parameters([
            'port' => 7000,
            'custom' => 'foobar',
            'setnull' => null,
            'setemptystring' => '',
        ]);

        $this->sharedTestsWithArrayParameters($parameters);
    }

    /**
     * @group disconnected
     */
    public function testCreateWithUriString(): void
    {
        $overrides = [
            'port' => 7000,
            'database' => 5,
            'custom' => 'foobar',
            'setnull' => null,
            'setemptystring' => '',
        ];

        $uriString = $this->getParametersString($overrides);
        $parameters = Parameters::create($uriString);

        $this->sharedTestsWithArrayParameters($parameters);
        $this->assertEquals($overrides['database'], $parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testToArray(): void
    {
        $additional = ['port' => 7000, 'custom' => 'foobar'];
        $parameters = new Parameters($additional);

        $this->assertEquals($this->getParametersArray($additional), $parameters->toArray());
    }

    /**
     * @group disconnected
     */
    public function testSerialization(): void
    {
        $parameters = new Parameters(['port' => 7000, 'custom' => 'foobar']);
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
    public function testParsingURI(): void
    {
        $uri = 'tcp://10.10.10.10:6400?timeout=0.5&persistent=1&database=5&password=secret';

        $expected = [
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'port' => 6400,
            'timeout' => '0.5',
            'persistent' => '1',
            'database' => '5',
            'password' => 'secret',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithRedisScheme(): void
    {
        $uri = 'redis://predis:secret@10.10.10.10:6400/5?timeout=0.5&persistent=1';

        $expected = [
            'scheme' => 'redis',
            'host' => '10.10.10.10',
            'port' => 6400,
            'timeout' => '0.5',
            'persistent' => '1',
            'username' => 'predis',
            'password' => 'secret',
            'database' => '5',
        ];

        $parameters = Parameters::parse($uri);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @group disconnected
     */
    public function testRedisSchemeOverridesUsernameAndPasswordInQueryString(): void
    {
        $parameters = Parameters::parse('redis://predis:secret@10.10.10.10/5?username=ignored&password=ignored');

        $this->assertSame('predis', $parameters['username']);
        $this->assertSame('secret', $parameters['password']);
    }

    /**
     * @group disconnected
     */
    public function testRedisSchemeDoesNotOverridesUsernameAndPasswordInQueryStringOnEmptyAuthFragment(): void
    {
        $parameters = Parameters::parse('redis://:@10.10.10.10/5?username=predis&password=secret');

        $this->assertSame('predis', $parameters['username']);
        $this->assertSame('secret', $parameters['password']);
    }

    /**
     * @group disconnected
     */
    public function testRedisSchemeOverridesDatabaseInQueryString(): void
    {
        $parameters = Parameters::parse('redis://10.10.10.10/5?database=10');

        $this->assertSame('5', $parameters['database']);
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithRedisSchemeMustPreserveRemainderOfPath(): void
    {
        $uri = 'redis://10.10.10.10/5/rest/of/path';

        $expected = [
            'scheme' => 'redis',
            'host' => '10.10.10.10',
            'path' => '/rest/of/path',
            'database' => '5',
        ];

        $parameters = Parameters::parse($uri);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithUnixDomainSocket(): void
    {
        $uri = 'unix:///tmp/redis.sock?timeout=0.5&persistent=1';

        $expected = [
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
            'timeout' => '0.5',
            'persistent' => '1',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithUnixDomainSocketOldWay(): void
    {
        $uri = 'unix:/tmp/redis.sock?timeout=0.5&persistent=1';

        $expected = [
            'scheme' => 'unix',
            'path' => '/tmp/redis.sock',
            'timeout' => '0.5',
            'persistent' => '1',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithIncompletePairInQueryString(): void
    {
        $uri = 'tcp://10.10.10.10?persistent=1&foo=&bar';

        $expected = [
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'persistent' => '1',
            'foo' => '',
            'bar' => '',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithMoreThanOneEqualSignInQueryStringPairValue(): void
    {
        $uri = 'tcp://10.10.10.10?foobar=a=b=c&persistent=1';

        $expected = [
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'foobar' => 'a=b=c',
            'persistent' => '1',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWhenQueryStringHasBracketsInFieldnames(): void
    {
        $uri = 'tcp://10.10.10.10?persistent=1&metavars[]=foo&metavars[]=hoge';

        $expected = [
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'persistent' => '1',
            'metavars' => ['foo', 'hoge'],
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithEmbeddedIPV6AddressShouldStripBracketsFromHost(): void
    {
        $expected = ['scheme' => 'tcp', 'host' => '::1', 'port' => 7000];
        $this->assertSame($expected, Parameters::parse('tcp://[::1]:7000'));

        $expected = ['scheme' => 'tcp', 'host' => '2001:db8:0:f101::1', 'port' => 7000];
        $this->assertSame($expected, Parameters::parse('tcp://[2001:db8:0:f101::1]:7000'));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIThrowOnInvalidURI(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid parameters URI: tcp://invalid:uri');

        Parameters::parse('tcp://invalid:uri');
    }

    /**
     * @group disconnected
     */
    public function testToStringWithDefaultParameters(): void
    {
        $parameters = new Parameters();

        $this->assertSame('tcp://127.0.0.1:6379', (string) $parameters);
    }

    /**
     * @group disconnected
     */
    public function testToStringWithUnixScheme(): void
    {
        $uri = 'unix:/path/to/redis.sock';
        $parameters = Parameters::create("$uri?foo=bar");

        $this->assertSame($uri, (string) $parameters);
    }

    /**
     * @group disconnected
     */
    public function testToStringWithIPv4(): void
    {
        $uri = 'tcp://127.0.0.1:6379';
        $parameters = Parameters::create("$uri?foo=bar");

        $this->assertSame($uri, (string) $parameters);
    }

    /**
     * @group disconnected
     */
    public function testToStringWithIPv6(): void
    {
        $uri = 'tcp://[::1]:6379';
        $parameters = Parameters::create("$uri?foo=bar");

        $this->assertSame($uri, (string) $parameters);
    }

    /**
     * @group disconnected
     */
    public function testSettingRelayOptions(): void
    {
        $uri = 'tcp://10.10.10.10?serializer=igbinary&compression=lz4';

        $expected = [
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'serializer' => 'igbinary',
            'compression' => 'lz4',
        ];

        $this->assertSame($expected, Parameters::parse($uri));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultParametersArray(): array
    {
        return [
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        ];
    }

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param array $parameters array of connection parameters.
     *
     * @return string URI string.
     */
    protected function getParametersString(array $parameters): string
    {
        $defaults = $this->getDefaultParametersArray();

        $scheme = $parameters['scheme'] ?? $defaults['scheme'];
        $host = $parameters['host'] ?? $defaults['host'];
        $port = $parameters['port'] ?? $defaults['port'];

        unset($parameters['scheme'], $parameters['host'], $parameters['port']);
        $uriString = "$scheme://$host:$port/?";

        foreach ($parameters as $k => $v) {
            $uriString .= "$k=$v&";
        }

        return $uriString;
    }
}
