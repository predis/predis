<?php

$PREDIS_HEADER = <<<EOS
This file is part of the Predis package.

(c) Daniele Alessandri <suppakilla@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOS;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setUsingCache(true)
    ->setRules([
        '@Symfony' => true,
        'header_comment' => [
            'header' => $PREDIS_HEADER,
        ],
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'single_space',
                '=' => 'single_space',
            ],
        ],
        'array_syntax' => ['syntax' => 'long'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->notPath('vendor')
            ->in(__DIR__)
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
