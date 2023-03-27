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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class CommonArgumentsTest extends TestCase
{
    /**
     * @var CommonArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CommonArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithRetentionModifier(): void
    {
        $this->arguments->retention(10);

        $this->assertSame(['RETENTION', 10], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithChunkSizeModifier(): void
    {
        $this->arguments->chunkSize(100);

        $this->assertSame(['CHUNK_SIZE', 100], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithDuplicatePolicyModifier(): void
    {
        $this->arguments->duplicatePolicy(CommonArguments::POLICY_FIRST);

        $this->assertSame(['DUPLICATE_POLICY', CommonArguments::POLICY_FIRST], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLabelsModifier(): void
    {
        $this->arguments->labels('label1', 1, 'label2', 2);

        $this->assertSame(['LABELS', 'label1', 1, 'label2', 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithEncodingModifier(): void
    {
        $this->arguments->encoding(CommonArguments::ENCODING_UNCOMPRESSED);

        $this->assertSame(['ENCODING', CommonArguments::ENCODING_UNCOMPRESSED], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLatestModifier(): void
    {
        $this->arguments->latest();

        $this->assertSame(['LATEST'], $this->arguments->toArray());
    }
}
