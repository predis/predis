<?php

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class GetArgumentsTest extends TestCase
{
    /**
     * @var GetArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new GetArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithEncodingModifier(): void
    {
        $this->arguments->latest();

        $this->assertSame(['LATEST'], $this->arguments->toArray());
    }
}
