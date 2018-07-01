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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftalter
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtAlter extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.ALTER';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		$flattenedArguments = array($arguments[0], $arguments[1]);
		if (is_array($arguments[2]))
		{
			$flattenedArguments = array_merge($flattenedArguments, $arguments[2]);
		} else
		{
			$flattenedArguments[] = $arguments[2];
		}

		return $flattenedArguments;
	}
}
