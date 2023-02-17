<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SearchArgumentsTest extends TestCase
{
    /**
     * @var SearchArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new SearchArguments();
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
    public function testCreatesArgumentsWithFilterModifier(): void
    {
        $this->arguments->filter('@age>16');

        $this->assertSame(['FILTER', '@age>16'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLanguageModifier(): void
    {
        $this->arguments->language('english');

        $this->assertSame(['LANGUAGE', 'english'], $this->arguments->toArray());
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
    public function testCreatesArgumentsWithPayloadFieldModifier(): void
    {
        $this->arguments->payloadField('payload_field');

        $this->assertSame(['PAYLOAD_FIELD', 'payload_field'], $this->arguments->toArray());
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
        $this->arguments->temporary();

        $this->assertSame(['TEMPORARY'], $this->arguments->toArray());
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
    public function testCreatesArgumentsWithStopInitialScanModifier(): void
    {
        $this->arguments->skipInitialScan();

        $this->assertSame(['SKIPINITIALSCAN'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoContentModifier(): void
    {
        $this->arguments->noContent();

        $this->assertSame(['NOCONTENT'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithVerbatimModifier(): void
    {
        $this->arguments->verbatim();

        $this->assertSame(['VERBATIM'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithScoresModifier(): void
    {
        $this->arguments->withScores();

        $this->assertSame(['WITHSCORES'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithPayloadsModifier(): void
    {
        $this->arguments->withPayloads();

        $this->assertSame(['WITHPAYLOADS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithSortKeysModifier(): void
    {
        $this->arguments->withSortKeys();

        $this->assertSame(['WITHSORTKEYS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSearchFilterModifier(): void
    {
        $this->arguments->searchFilter(['numeric_field', 1, 10], ['numeric_field1', 2, 5]);

        $this->assertSame(
            ['FILTER', 'numeric_field', 1, 10, 'FILTER', 'numeric_field1', 2, 5],
            $this->arguments->toArray()
        );
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithGeoFilterModifier(): void
    {
        $this->arguments->geoFilter(
            ['geo_field', 13.2321, 14.2321, 300, 'km'],
            ['geo_field1', 15.231, 16.234, 210, 'km']
        );

        $this->assertSame(
            ['GEOFILTER', 'geo_field', 13.2321, 14.2321, 300, 'km', 'GEOFILTER', 'geo_field1', 15.231, 16.234, 210, 'km'],
            $this->arguments->toArray()
        );
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInKeysModifier(): void
    {
        $this->arguments->inKeys(['key1', 'key2']);

        $this->assertSame(['INKEYS', 2, 'key1', 'key2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInFieldsModifier(): void
    {
        $this->arguments->inFields(['field1', 'field2']);

        $this->assertSame(['INFIELDS', 2, 'field1', 'field2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithReturnModifier(): void
    {
        $this->arguments->addReturn(2, 'identifier', true, 'property', 'identifier2', 'identifier3');

        $this->assertSame(
            ['RETURN', 2, 'identifier', 'AS', 'property', 'identifier2', 'identifier3'],
            $this->arguments->toArray()
        );
    }

    /**
     * @dataProvider summarizeProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithSummarizeModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->summarize(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @dataProvider highlightProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithHighlightModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->highlight(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSlopModifier(): void
    {
        $this->arguments->slop(2);

        $this->assertSame(['SLOP', 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithTimeoutModifier(): void
    {
        $this->arguments->timeout(2);

        $this->assertSame(['TIMEOUT', 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInOrderModifier(): void
    {
        $this->arguments->inOrder();

        $this->assertSame(['INORDER'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithExpanderModifier(): void
    {
        $this->arguments->expander('expander');

        $this->assertSame(['EXPANDER', 'expander'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithScorerModifier(): void
    {
        $this->arguments->scorer('scorer');

        $this->assertSame(['SCORER', 'scorer'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsExplainScoreModifier(): void
    {
        $this->arguments->explainScore();

        $this->assertSame(['EXPLAINSCORE'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithPayloadModifier(): void
    {
        $this->arguments->payload('payload');

        $this->assertSame(['PAYLOAD', 'payload'], $this->arguments->toArray());
    }

    /**
     * @dataProvider sortByProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithSortByModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->sortBy(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnSortByWrongOrderByModifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong order direction value given. Currently supports: ASC, DESC');

        $this->arguments->sortBy('sort_attribute', 'wrong');
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLimitModifier(): void
    {
        $this->arguments->limit(2, 2);

        $this->assertSame(['LIMIT', 2, 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithParamsModifier(): void
    {
        $this->arguments->params(['name1', 'value1', 'name2', 'value2']);

        $this->assertSame(['PARAMS', 4, 'name1', 'value1', 'name2', 'value2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithDialectModifier(): void
    {
        $this->arguments->dialect('dialect');

        $this->assertSame(['DIALECT', 'dialect'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSearchModifier(): void
    {
        $this->arguments->search();

        $this->assertSame(['SEARCH'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithAggregateModifier(): void
    {
        $this->arguments->aggregate();

        $this->assertSame(['AGGREGATE'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithLimitedModifier(): void
    {
        $this->arguments->limited();

        $this->assertSame(['LIMITED'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithQueryModifier(): void
    {
        $this->arguments->query('query');

        $this->assertSame(['QUERY', 'query'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithDistanceModifier(): void
    {
        $this->arguments->distance(2);

        $this->assertSame(['DISTANCE', 2], $this->arguments->toArray());
    }

    /**
     * @dataProvider termsProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithTermsModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->terms(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnInvalidTermsModifierValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong modifier value given. Currently supports: INCLUDE, EXCLUDE');

        $this->arguments->terms('dict', 'wrong');
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

    /**
     * @return void
     */
    public function testCreatesCorrectFTSearchArgumentsSetOnMethodsChainCall(): void
    {
        $this->arguments->withScores();
        $this->arguments->withPayloads();
        $this->arguments->searchFilter(['numeric_field', 1, 10]);
        $this->arguments->addReturn(2, 'identifier', true, 'property');

        $this->assertSame(
            ['WITHSCORES', 'WITHPAYLOADS', 'FILTER', 'numeric_field', 1, 10, 'RETURN', 2, 'identifier', 'AS', 'property'],
            $this->arguments->toArray()
        );
    }

    public function summarizeProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                ['SUMMARIZE'],
            ],
            'with fields only' => [
                [['field1', 'field2']],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with non-default FRAGS' => [
                [['field1', 'field2'], 2],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2],
            ],
            'with non-default LEN' => [
                [['field1', 'field2'], 0, 2],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'LEN', 2],
            ],
            'with non-default SEPARATOR' => [
                [['field1', 'field2'], 0, 0, ','],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'SEPARATOR', ','],
            ],
            'with all arguments' => [
                [['field1', 'field2'], 2, 2, ','],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2, 'LEN', 2, 'SEPARATOR', ','],
            ],
        ];
    }

    public function highlightProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                ['HIGHLIGHT'],
            ],
            'with fields only' => [
                [['field1', 'field2']],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with open tag only' => [
                [['field1', 'field2'], 'openTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with close tag only' => [
                [['field1', 'field2'], '', 'closeTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with both tags' => [
                [['field1', 'field2'], 'openTag', 'closeTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2', 'TAGS', 'openTag', 'closeTag'],
            ],
        ];
    }

    public function sortByProvider(): array
    {
        return [
            'with default arguments' => [
                ['sort_attribute'],
                ['SORTBY', 'sort_attribute', 'ASC'],
            ],
            'with DESC modifier' => [
                ['sort_attribute', 'desc'],
                ['SORTBY', 'sort_attribute', 'DESC'],
            ],
        ];
    }

    public function termsProvider(): array
    {
        return [
            'with INCLUDE modifier' => [
                ['dict', 'INCLUDE', 'term1', 'term2'],
                ['TERMS', 'INCLUDE', 'dict', 'term1', 'term2'],
            ],
            'with EXCLUDE modifier' => [
                ['dict', 'EXCLUDE', 'term1', 'term2'],
                ['TERMS', 'EXCLUDE', 'dict', 'term1', 'term2'],
            ],
        ];
    }
}
