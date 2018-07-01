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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftaggregate
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtAggregate extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.AGGREGATE';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function filterArguments(array $arguments)
	{
		if (count($arguments) === 3 && is_array($arguments[2]))
		{
			$options = $this->prepareOptions(array_pop($arguments));
			$arguments = array_merge($arguments, $options);
		}

		return $arguments;
	}

	/**
	 * Returns a list of options and modifiers compatible with Redis.
	 *
	 * @param array $options List of options.
	 *
	 * @return array
	 */
	protected function prepareOptions($options)
	{
		$options = array_change_key_case($options, CASE_UPPER);
		$normalized = array();

		if (!empty($options['WITHSCHEMA']) &&
			$options['WITHSCHEMA'] == true)
		{
			$normalized[] = 'WITHSCHEMA';
		}
		if (!empty($options['VERBATIM']) &&
			$options['VERBATIM'] == true)
		{
			$normalized[] = 'VERBATIM';
		}
		if (isset($options['LOAD']) && is_array($options['LOAD']))
		{
			$normalized[] = 'LOAD';
			$normalized = array_merge($normalized, $options['LOAD']);
		}
		if (isset($options['GROUPBY']) && is_array($options['GROUPBY']))
		{
			foreach ($options['GROUPBY'] as $v)
			{
				$normalized[] = 'GROUPBY';
				$normalized[] = $v;
			}
		}
		if (isset($options['SORTBY']) && is_array($options['SORTBY']))
		{
			$normalized[] = 'SORTBY';
			$normalized = array_merge($normalized, $options['SORTBY']);
		}
		if (isset($options['APPLY']) && is_array($options['APPLY']))
		{
			foreach ($options['APPLY'] as $k => $v)
			{
				$normalized[] = 'APPLY';
				$normalized[] = $k;
				$normalized[] = 'AS';
				$normalized[] = $v;
			}
		}
		if (isset($options['LIMIT']) && is_array($options['LIMIT']))
		{
			foreach ($options['LIMIT'] as $k => $v)
			{
				$normalized[] = 'LIMIT';
				$normalized[] = $k;
				$normalized[] = $v;
			}
		}
		if (isset($options['FILTER']) && is_array($options['FILTER']))
		{
			foreach ($options['FILTER'] as $v)
			{
				$normalized[] = 'FILTER';
				$normalized[] = $v;
			}
		}

		return $normalized;
	}
}
