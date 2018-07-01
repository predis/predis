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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftaddhash
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtAddHash extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.ADDHASH';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		$flattenedArguments = array($arguments[0], $arguments[1], $arguments[2]);
		if ($arguments[3] !== null && is_array($arguments[3]))
		{
			$flattenedArguments = array_merge($flattenedArguments, $arguments[3]);
		}

		return $flattenedArguments;
	}
}
