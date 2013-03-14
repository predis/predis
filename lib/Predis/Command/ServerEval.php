<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @link http://redis.io/commands/eval
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerEval extends AbstractCommand implements PrefixableCommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'EVAL';
    }

    /**
     * {@inheritdoc}
     */
    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();

        if ('EVAL' === $this->getId() && empty($arguments)) {
            /* this is needed because when executing an EVAL following an EVALSHA which gives an
               error, arguments are not passed to CreateCommand EVAL to avoid a double filterArguments
               (they are set afterwards using setRawArguments)
            */
            return;
        }

        for ($i = 2; $i < $arguments[1] + 2; $i++) {
            $arguments[$i] = "$prefix{$arguments[$i]}";
        }

        $this->setRawArguments($arguments);
    }

    /**
     * Calculates the SHA1 hash of the body of the script.
     *
     * @return string SHA1 hash.
     */
    public function getScriptHash()
    {
        return sha1($this->getArgument(0));
    }
}
