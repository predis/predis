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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftcreate
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtCreate extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.CREATE';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		$flattenedArguments = array($arguments[0]);
		if ($arguments[1] !== null)
		{
			foreach ($arguments[1] as $value)
			{
				$flattenedArguments[] = $value;
			}
		}
		$flattenedArguments[] = 'SCHEMA';
		foreach ($arguments[2] as $field => $value)
		{
			if (is_array($value))
			{
				$flattenedArguments[] = $field;
				$flattenedArguments = array_merge($flattenedArguments, $value);
			} else
			{
				$flattenedArguments[] = $field;
				$flattenedArguments[] = $value;
			}
		}

		return $flattenedArguments;
	}
}
