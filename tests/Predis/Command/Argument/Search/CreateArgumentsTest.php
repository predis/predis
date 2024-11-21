<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreateArgumentsTest extends TestCase
{
    /**
     * @var CreateArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CreateArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithOnModifier(): void
    {
        $this->arguments->on('json');

        $this->assertSame(['ON', 'JSON'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnInvalidModifierValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong modifier value given. Currently supports: HASH, JSON');

        $this->arguments->on('wrong');
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithPrefixModifier(): void
    {
        $this->arguments->prefix(['prefix:', 'prefix1:']);

        $this->assertSame(['PREFIX', 2, 'prefix:', 'prefix1:'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLanguageFieldModifier(): void
    {
        $this->arguments->languageField('language_attribute');

        $this->assertSame(['LANGUAGE_FIELD', 'language_attribute'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithScoreModifier(): void
    {
        $this->arguments->score(10.0);

        $this->assertSame(['SCORE', 10.0], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithScoreFieldModifier(): void
    {
        $this->arguments->scoreField('score_field');

        $this->assertSame(['SCORE_FIELD', 'score_field'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithMaxTestFieldsModifier(): void
    {
        $this->arguments->maxTextFields();

        $this->assertSame(['MAXTEXTFIELDS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoOffsetsModifier(): void
    {
        $this->arguments->noOffsets();

        $this->assertSame(['NOOFFSETS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithTemporaryModifier(): void
    {
        $this->arguments->temporary(1);

        $this->assertSame(['TEMPORARY', 1], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoHlModifier(): void
    {
        $this->arguments->noHl();

        $this->assertSame(['NOHL'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoFieldsModifier(): void
    {
        $this->arguments->noFields();

        $this->assertSame(['NOFIELDS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoFreqsModifier(): void
    {
        $this->arguments->noFreqs();

        $this->assertSame(['NOFREQS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithStopWordsModifier(): void
    {
        $this->arguments->stopWords(['word1', 'word2']);

        $this->assertSame(['STOPWORDS', 2, 'word1', 'word2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesCorrectFTCreateArgumentsSetOnMethodsChainCall(): void
    {
        $this->arguments->prefix(['prefix:', 'prefix1:']);
        $this->arguments->filter('@age>16');
        $this->arguments->stopWords(['hello', 'world']);

        $this->assertSame(
            ['PREFIX', 2, 'prefix:', 'prefix1:', 'FILTER', '@age>16', 'STOPWORDS', 2, 'hello', 'world'],
            $this->arguments->toArray()
        );
    }
}
