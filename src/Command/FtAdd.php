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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftadd
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtAdd extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.ADD';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		$flattenedArguments = array($arguments[0], $arguments[1], $arguments[2]);
		if (isset($arguments[3]) &&
			$arguments[3] !== null)
		{
			$flattenedArguments = array_merge($flattenedArguments, $arguments[3]);
		}

		$flattenedArguments[] = 'FIELDS';
		foreach ($arguments[4] as $field => $value)
		{
			$flattenedArguments[] = $field;
			$flattenedArguments[] = $value;
		}

		return $flattenedArguments;
	}
}
