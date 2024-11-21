<?php

$PREDIS_HEADER = <<<EOS
This file is part of the Predis package.

(c) 2009-2020 Daniele Alessandri
(c) 2021-2024 Till Krüss

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOS;

return (new PhpCsFixer\Config)
    ->setRules([
        '@PHP71Migration' => true,
        'header_comment' => ['header' => $PREDIS_HEADER],
        '@Symfony' => true,
        'phpdoc_separation' => false,
        'phpdoc_annotation_without_dot' => false,
        'no_superfluous_phpdoc_tags' => false,
        'no_unneeded_curly_braces' => false,
        'no_unneeded_braces' => false,
        'global_namespace_import' => true,
        'yoda_style' => false,
        'single_line_throw' => false,
        'concat_space' => ['spacing' => 'one'],
        'increment_style' => false,
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['array_destructuring', 'arrays']]
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/bin')
            ->in(__DIR__ . '/examples')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
    );
