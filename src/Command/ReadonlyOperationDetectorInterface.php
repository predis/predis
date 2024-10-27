<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

interface ReadonlyOperationDetectorInterface
{
    /**
     * Returns TRUE if the provided operation is read-only.
     *
     * @param  CommandInterface $command
     * @return bool
     */
    public function detect(CommandInterface $command): bool;
}
