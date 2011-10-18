<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol;

/**
 * Interface that defines a customizable protocol processor that serializes
 * Redis commands and parses replies returned by the server to PHP objects
 * using a pluggable set of classes defining the underlying wire protocol.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IComposableProtocolProcessor extends IProtocolProcessor
{
    /**
     * Sets the command serializer to be used by the protocol processor.
     *
     * @param ICommandSerializer $serializer Command serializer.
     */
    public function setSerializer(ICommandSerializer $serializer);

    /**
     * Returns the command serializer used by the protocol processor.
     *
     * @return ICommandSerializer
     */
    public function getSerializer();

    /**
     * Sets the response reader to be used by the protocol processor.
     *
     * @param IResponseReader $reader Response reader.
     */
    public function setReader(IResponseReader $reader);

    /**
     * Returns the response reader used by the protocol processor.
     *
     * @return IResponseReader
     */
    public function getReader();
}
