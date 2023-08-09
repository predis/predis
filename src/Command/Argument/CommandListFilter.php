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

namespace Predis\Command\Argument;

class CommandListFilter implements ArrayableArgument
{
    /**
     * @var string[]
     */
    private $arguments = ['FILTERBY'];

    /**
     * Get the commands that belong to the module specified by module-name.
     *
     * @param  string $moduleName
     * @return void
     */
    public function filterByModule(string $moduleName): self
    {
        array_push($this->arguments, 'MODULE', $moduleName);

        return $this;
    }

    /**
     * Get the commands in the ACL category specified by category.
     *
     * @param  string $category
     * @return void
     */
    public function filterByACLCategory(string $category): self
    {
        array_push($this->arguments, 'ACLCAT', $category);

        return $this;
    }

    /**
     * Get the commands that match the given glob-like pattern.
     *
     * @param  string $pattern
     * @return void
     */
    public function filterByPattern(string $pattern): self
    {
        array_push($this->arguments, 'PATTERN', $pattern);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
