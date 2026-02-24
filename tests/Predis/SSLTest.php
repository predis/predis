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

namespace Predis;

use Predis\Connection\Resource\Exception\StreamInitException;
use PredisTestCase;

class SSLTest extends PredisTestCase
{
    /**
     * @group connected
     * @group ssl
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnection()
    {
        $redis = $this->createClient([
            'ssl' => [
                'cafile' => getenv('STANDALONE_CA_CERT_PATH'),
                'verify_peer' => true,
                'verify_peer_name' => false,
            ],
        ]);
        $this->assertEquals('PONG', $redis->ping());
    }

    /**
     * @group connected
     * @group ssl
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnectionFailsOnIncorrectCertificate()
    {
        $redis = new Client($this->getDefaultParametersArray() + [
            'ssl' => ['cafile' => '/tmp/invalid.crt', 'verify_peer' => true, 'verify_peer_name' => false]]
        );

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage('Error while switching to encrypted communication');

        $redis->ping();
    }

    /**
     * @group connected
     * @group ssl
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnectionWithoutSSLConfig()
    {
        $redis = new Client($this->getDefaultParametersArray());

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage('Error while switching to encrypted communication');

        $redis->ping();
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnection()
    {
        $redis = $this->createClient(null, [
            'cluster' => 'redis',
            'parameters' => [
                'ssl' => [
                    'cafile' => getenv('CLUSTER_CA_CERT_PATH'),
                    'verify_peer' => true,
                    'verify_peer_name' => false,
                ],
            ],
        ]);
        $redis->set('foo', 'bar');
        $this->assertEquals('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnectionFailsOnIncorrectCertificate()
    {
        $redis = new Client($this->getDefaultParametersArray(), [
            'cluster' => 'redis',
            'parameters' => [
                'ssl' => ['cafile' => '/tmp/invalid.crt', 'verify_peer' => true, 'verify_peer_name' => false],
            ],
        ]);

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage('Error while switching to encrypted communication');

        $redis->set('foo', 'bar');
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnectionWithoutSSLConfig()
    {
        $redis = new Client($this->getDefaultParametersArray(), [
            'cluster' => 'redis',
        ]);

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage('Error while switching to encrypted communication');

        $redis->set('foo', 'bar');
    }

    /**
     * @group connected
     * @group ssl
     * @group relay-incompatible
     * @requiresRedisVersion >= 8.5.0
     * @return void
     */
    public function testAuthWithSSLCertificateWithCNSpecified()
    {
        $redis = $this->createClient();

        $this->assertEquals(
            'OK',
            $redis->acl->setUser('test_user', 'on', '>clientpass', 'allcommands', 'allkeys')
        );

        $redis->disconnect();

        // Remove AUTH
        $redis = $this->createClient(['password' => null]);

        $this->assertEquals(getenv('CN_USER_NAME'), $redis->acl->whoami());
        $this->assertEquals(1, $redis->acl->delUser(getenv('CN_USER_NAME')));
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 8.5.0
     * @return void
     */
    public function testClusterAuthWithSSLCertificateWithCNSpecified()
    {
        $redis = $this->createClient();

        $this->assertEquals(
            'OK',
            $redis->acl->setUser('test_user', 'on', '>clientpass', 'allcommands', 'allkeys')
        );

        $redis->disconnect();

        // Remove AUTH
        $defaultParameters = $this->getDefaultParametersArray();
        $trimmedParameters = array_map(function (string $parameter) {
            return explode('?', $parameter)[0];
        }, $defaultParameters);

        $redis = new Client(
            $trimmedParameters,
            [
                'cluster' => 'redis',
                'parameters' => [
                    'ssl' => [
                        'cafile' => getenv('CLUSTER_CA_CERT_PATH'),
                        'local_cert' => getenv('CLUSTER_LOCAL_CERT_PATH'),
                        'local_pk' => getenv('CLUSTER_LOCAL_PK_PATH'),
                        'verify_peer' => true,
                        'verify_peer_name' => false,
                    ],
                ],
            ]
        );

        $this->assertEquals(getenv('CN_USER_NAME'), $redis->acl->whoami());
        $this->assertEquals(1, $redis->acl->delUser(getenv('CN_USER_NAME')));
    }
}
