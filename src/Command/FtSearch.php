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
 * @link https://oss.redislabs.com/redisearch/Commands/#ftsearch
 *
 * @author Paul Livorsi <paullivorsi@gmail.com>
 */
class FtSearch extends Command {
	/**
	 * {@inheritdoc}
	 */
	public function getId()
	{
		return 'FT.SEARCH';
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

		if (!empty($options['NOCONTENT']) &&
			$options['NOCONTENT'] == true)
		{
			$normalized[] = 'NOCONTENT';
		}

		if (!empty($options['VERBATIM']) &&
			$options['VERBATIM'] == true)
		{
			$normalized[] = 'VERBATIM';
		}
		if (!empty($options['NOCONTENT']) &&
			$options['NOCONTENT'] == true)
		{
			$normalized[] = 'NOCONTENT';
		}

		if (!empty($options['NOSTOPWORDS']) &&
			$options['NOSTOPWORDS'] == true)
		{
			$normalized[] = 'NOSTOPWORDS';
		}
		if (!empty($options['WITHSCORES']) &&
			$options['WITHSCORES'] == true)
		{
			$normalized[] = 'WITHSCORES';
		}

		if (!empty($options['WITHPAYLOADS']) &&
			$options['WITHPAYLOADS'] == true)
		{
			$normalized[] = 'WITHPAYLOADS';
		}
		if (!empty($options['WITHSORTKEYS']) &&
			$options['WITHSORTKEYS'] == true)
		{
			$normalized[] = 'WITHSORTKEYS';
		}
		if (!empty($options['FILTER']))
		{
			$normalized[] = 'FILTER';
			if (isset($options['FILTER']) &&
				is_array($options['FILTER']) &&
				count($options['FILTER']) > 2)
			{
				$normalized = array_merge($normalized, $options['FILTER']);
			}
		}
		if (!empty($options['GEOFILTER']))
		{
			$normalized[] = 'GEOFILTER';
			if (isset($options['GEOFILTER']) &&
				is_array($options['GEOFILTER']) &&
				count($options['GEOFILTER']) == 5)
			{
				$normalized = array_merge($normalized, $options['GEOFILTER']);
			}
		}
		if (!empty($options['INKEYS']))
		{
			$normalized[] = 'INKEYS';
			if (isset($options['INKEYS']) &&
				is_array($options['INKEYS']) &&
				count($options['INKEYS']) > 1)
			{
				$normalized = array_merge($normalized, $options['INKEYS']);
			}
		}
		if (!empty($options['INFIELDS']))
		{
			$normalized[] = 'INFIELDS';
			if (isset($options['INFIELDS']) &&
				is_array($options['INFIELDS']) &&
				count($options['INFIELDS']) > 1)
			{
				$normalized = array_merge($normalized, $options['INFIELDS']);
			}
		}
		if (!empty($options['RETURN']))
		{
			$normalized[] = 'RETURN';
			if (isset($options['RETURN']) &&
				is_array($options['RETURN']))
			{
				$normalized = array_merge($normalized, $options['RETURN']);
			}
		}
		if (!empty($options['SUMMARIZE']) &&
			isset($options['SUMMARIZE']) &&
			is_array($options['SUMMARIZE']))
		{
			$normalized[] = 'SUMMARIZE';

			$options['SUMMARIZE'] = array_change_key_case($options['SUMMARIZE'], CASE_UPPER);
			if (isset($options['SUMMARIZE']['FIELDS']) && is_array($options['SUMMARIZE']['FIELDS']))
			{
				$normalized[] = 'FIELDS';
				$normalized = array_merge($normalized, $options['SUMMARIZE']['FIELDS']);
			}
			if (isset($options['SUMMARIZE']['FRAGS']))
			{
				$normalized[] = 'FRAGS';
				$normalized[] = $options['SUMMARIZE']['FIELDS'];
			}
			if (isset($options['SUMMARIZE']['LEN']))
			{
				$normalized[] = 'LEN';
				$normalized = $options['SUMMARIZE']['LEN'];
			}
			if (isset($options['SUMMARIZE']['SEPARATOR']))
			{
				$normalized[] = 'SEPARATOR';
				$normalized = $options['SUMMARIZE']['SEPARATOR'];
			}
		}
		if (!empty($options['HIGHLIGHT']))
		{
			$normalized[] = 'HIGHLIGHT';

			$options['HIGHLIGHT'] = array_change_key_case($options['HIGHLIGHT'], CASE_UPPER);
			if (isset($options['HIGHLIGHT']['FIELDS']) && is_array($options['HIGHLIGHT']['FIELDS']))
			{
				$normalized[] = 'FIELDS';
				$normalized = array_merge($normalized, $options['HIGHLIGHT']['FIELDS']);
			}
			if (isset($options['HIGHLIGHT']['TAGS']) && is_array($options['HIGHLIGHT']['TAGS']))
			{
				$normalized[] = 'TAGS';
				$normalized[] = $options['SUMMARIZE']['TAGS'][0];
				$normalized[] = $options['SUMMARIZE']['TAGS'][1];
			}
		}
		if (!empty($options['SLOP']))
		{
			$normalized[] = 'SLOP';
			$normalized[] = $options['SLOP'];
		}
		if (!empty($options['INORDER']) &&
			$options['INORDER'] == true)
		{
			$normalized[] = 'INORDER';
		}
		if (!empty($options['LANGUAGE']))
		{
			$normalized[] = 'LANGUAGE';
			$normalized[] = $options['LANGUAGE'];
		}
		if (!empty($options['EXPANDER']))
		{
			$normalized[] = 'EXPANDER';
			$normalized[] = $options['EXPANDER'];
		}

		if (!empty($options['SCORER']))
		{
			$normalized[] = 'SCORER';
			$normalized[] = $options['SCORER'];
		}
		if (!empty($options['PAYLOAD']))
		{
			$normalized[] = 'PAYLOAD';
			$normalized[] = $options['PAYLOAD'];
		}
		if (!empty($options['SORTBY']) && is_array($options['SORTBY']))
		{
			$normalized[] = 'SORTBY';
			$normalized[] = $options['SORTBY'][0];
			$normalized[] = $options['SORTBY'][1];
		}
		if (isset($options['LIMIT']) &&
			is_array($options['LIMIT']) &&
			count($options['LIMIT']) == 2)
		{
			$normalized[] = 'LIMIT';
			$normalized[] = $options['LIMIT'][0];
			$normalized[] = $options['LIMIT'][1];
		}

		return $normalized;
	}
}
