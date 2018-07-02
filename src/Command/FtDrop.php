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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftdrop
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtDrop extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.DROP';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		if (count($arguments) == 2 && is_array($arguments[1]))
		{
			$options = array_pop($arguments);
			$arguments = array_merge($arguments, $options);
		}elseif($arguments[1] == null)
		{
			array_pop($arguments);
		}

		return $arguments;
	}
}
