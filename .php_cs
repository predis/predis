<?php

$PREDIS_HEADER = <<<EOS
This file is part of the Predis package.

(c) Daniele Alessandri <suppakilla@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOS;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($PREDIS_HEADER);

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array(
        // Symfony
        '-unalign_equals',
        '-unalign_double_arrow',

        // Contribs
        'header_comment',
        'ordered_use',
        'phpdoc_order',
        'long_array_syntax',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__.'/bin')
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->in(__DIR__.'/examples')
    );
