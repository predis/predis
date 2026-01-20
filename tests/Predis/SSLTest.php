<?php

namespace Predis;

use Predis\Connection\Resource\Exception\StreamInitException;
use PredisTestCase;

class SSLTest extends PredisTestCase
{
    /**
     * @group connected
     * @group ssl
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnection()
    {
        $redis = $this->createClient();
        $this->assertEquals('PONG', $redis->ping());
    }

    /**
     * @group connected
     * @group ssl
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnectionFailsOnIncorrectCertificate()
    {
        $redis = new Client($this->getDefaultParametersArray() + [
            'ssl' => ['cafile' => '/tmp/invalid.crt', 'verify_peer' => true, 'verify_peer_name' => false]]
        );

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage("Error while switching to encrypted communication");

        $redis->ping();
    }

    /**
     * @group connected
     * @group ssl
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testExecuteCommandOverSSLConnectionWithoutSSLConfig()
    {
        $redis = new Client($this->getDefaultParametersArray());

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage("Error while switching to encrypted communication");

        $redis->ping();
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnection()
    {
        $redis = $this->createClient();
        $redis->set('foo', 'bar');
        $this->assertEquals('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnectionFailsOnIncorrectCertificate()
    {
        $redis = new Client($this->getDefaultParametersArray(), [
            'cluster' => 'redis',
            'parameters' => [
                'ssl' => ['cafile' => '/tmp/invalid.crt', 'verify_peer' => true, 'verify_peer_name' => false]
            ]
        ]);

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage("Error while switching to encrypted communication");

        $redis->set('foo', 'bar');
    }

    /**
     * @group connected
     * @group ssl
     * @group cluster
     * @requiresRedisVersion >= 2.0.0
     * @return void
     */
    public function testClusterExecuteCommandOverSSLConnectionWithoutSSLConfig()
    {
        $redis = new Client($this->getDefaultParametersArray(), [
            'cluster' => 'redis',
        ]);

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage("Error while switching to encrypted communication");

        $redis->set('foo', 'bar');
    }
}
