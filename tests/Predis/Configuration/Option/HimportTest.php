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

namespace Predis\Configuration\Option;

use InvalidArgumentException;
use Predis\Configuration\OptionsInterface;
use Predis\Himport\FieldsetRegistry;
use Predis\Himport\HimportOptions;
use PredisTestCase;

class HimportTest extends PredisTestCase
{
    /**
     * @return OptionsInterface
     */
    private function getOptions()
    {
        return $this->getMockBuilder(OptionsInterface::class)->getMock();
    }

    /**
     * @group disconnected
     */
    public function testDefaultEnablesAutoPrepareWithEmptyRegistry(): void
    {
        $option = new Himport();
        $default = $option->getDefault($this->getOptions());

        $this->assertInstanceOf(HimportOptions::class, $default);
        $this->assertTrue($default->isAutoPrepareEnabled());
        $this->assertSame([], $default->getRegistry()->all());
    }

    /**
     * @group disconnected
     */
    public function testFilterReturnsHimportOptionsInstanceAsIs(): void
    {
        $option = new Himport();
        $value = new HimportOptions(new FieldsetRegistry(), false);

        $this->assertSame($value, $option->filter($this->getOptions(), $value));
    }

    /**
     * @group disconnected
     */
    public function testFilterReadsAutoPrepareFlag(): void
    {
        $option = new Himport();

        $this->assertFalse($option->filter($this->getOptions(), ['auto_prepare' => false])->isAutoPrepareEnabled());
        $this->assertTrue($option->filter($this->getOptions(), ['auto_prepare' => true])->isAutoPrepareEnabled());
        $this->assertTrue($option->filter($this->getOptions(), [])->isAutoPrepareEnabled());
    }

    /**
     * @group disconnected
     */
    public function testFilterSeedsRegistryFromPreloadedFieldsets(): void
    {
        $option = new Himport();

        $result = $option->filter($this->getOptions(), [
            'fieldsets' => [
                'users' => ['name', 'email', 'age'],
                'orders' => ['id', 'total'],
            ],
        ]);

        $registry = $result->getRegistry();
        $this->assertTrue($registry->has('users'));
        $this->assertTrue($registry->has('orders'));
        $this->assertSame(['name', 'email', 'age'], $registry->get('users'));
        $this->assertSame(['id', 'total'], $registry->get('orders'));

        // Pre-loading and the auto-prepare flag are independent inputs.
        $this->assertTrue($result->isAutoPrepareEnabled());
    }

    /**
     * @group disconnected
     */
    public function testFilterAcceptsFieldsetsAndAutoPrepareTogether(): void
    {
        $option = new Himport();

        $result = $option->filter($this->getOptions(), [
            'fieldsets' => ['users' => ['name']],
            'auto_prepare' => false,
        ]);

        $this->assertTrue($result->getRegistry()->has('users'));
        $this->assertFalse($result->isAutoPrepareEnabled());
    }

    /**
     * @group disconnected
     */
    public function testFilterPreservesFieldOrderVerbatim(): void
    {
        $option = new Himport();

        $result = $option->filter($this->getOptions(), [
            'fieldsets' => ['ordered' => ['c', 'a', 'b']],
        ]);

        $this->assertSame(['c', 'a', 'b'], $result->getRegistry()->get('ordered'));
    }

    /**
     * @group disconnected
     */
    public function testFilterThrowsOnNonArrayValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Himport())->filter($this->getOptions(), 'invalid');
    }

    /**
     * @group disconnected
     */
    public function testFilterThrowsWhenFieldsetsIsNotAMap(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Himport())->filter($this->getOptions(), ['fieldsets' => 'invalid']);
    }

    /**
     * @group disconnected
     */
    public function testFilterThrowsOnEmptyFieldList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fieldset "users" must be a non-empty list of field names.');

        (new Himport())->filter($this->getOptions(), ['fieldsets' => ['users' => []]]);
    }
}
