<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

/**
 * Manages Predis options with filtering, conversion and lazy initialization of
 * values using a mini-DI container approach.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Options implements OptionsInterface
{
    protected $options = array();
    protected $input;
    protected $handlers;

    /**
     * @param array $options Array of options with their values
     */
    public function __construct(array $options = array())
    {
        $this->input = $options;
        $this->options = array();
        $this->handlers = $this->getHandlers();
    }

    /**
     * Ensures that the default options are initialized.
     *
     * @return array
     */
    protected function getHandlers()
    {
        return array(
            'aggregate' => 'Predis\Configuration\Option\Aggregate',
            'cluster' => 'Predis\Configuration\Option\Cluster',
            'replication' => 'Predis\Configuration\Option\Replication',
            'connections' => 'Predis\Configuration\Option\Connections',
            'commands' => 'Predis\Configuration\Option\Commands',
            'exceptions' => 'Predis\Configuration\Option\Exceptions',
            'prefix' => 'Predis\Configuration\Option\Prefix',
            'crc16' => 'Predis\Configuration\Option\CRC16',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault($option)
    {
        if (isset($this->handlers[$option])) {
            $handler = $this->handlers[$option];
            $handler = new $handler();

            return $handler->getDefault($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function defined($option)
    {
        return
            array_key_exists($option, $this->options) ||
            array_key_exists($option, $this->input)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($option)
    {
        return (
            array_key_exists($option, $this->options) ||
            array_key_exists($option, $this->input)
        ) && $this->__get($option) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($option)
    {
        if (isset($this->options[$option]) || array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        if (isset($this->input[$option]) || array_key_exists($option, $this->input)) {
            $value = $this->input[$option];
            unset($this->input[$option]);

            if (isset($this->handlers[$option])) {
                $handler = $this->handlers[$option];
                $handler = new $handler();
                $value = $handler->filter($this, $value);
            } elseif (is_object($value) && method_exists($value, '__invoke')) {
                $value = $value($this);
            }

            return $this->options[$option] = $value;
        }

        if (isset($this->handlers[$option])) {
            return $this->options[$option] = $this->getDefault($option);
        }

        return;
    }
}
