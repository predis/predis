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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\ExplainArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTEXPLAIN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTEXPLAIN::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTEXPLAIN';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testExplainReturnsExecutionPlanForGivenQuery(): void
    {
        $redis = $this->getClient();
        $expectedResponse = <<<EOT
INTERSECT {
  UNION {
    INTERSECT {
      UNION {
        foo
        +foo(expanded)
      }
      UNION {
        bar
        +bar(expanded)
      }
    }
    INTERSECT {
      UNION {
        hello
        +hello(expanded)
      }
      UNION {
        world
        +world(expanded)
      }
    }
  }
  UNION {
    NUMERIC {100.000000 <= @date <= 200.000000}
    NUMERIC {500.000000 <= @date <= inf}
  }
}

EOT;

        $schema = [new TextField('text_field')];

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));
        $this->assertEquals(
            $expectedResponse,
            $redis->ftexplain('index', '(foo bar)|(hello world) @date:[100 200]|@date:[500 +inf]')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testExplainReturnsExecutionPlanForGivenQueryResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = <<<EOT
INTERSECT {
  UNION {
    INTERSECT {
      UNION {
        foo
        +foo(expanded)
      }
      UNION {
        bar
        +bar(expanded)
      }
    }
    INTERSECT {
      UNION {
        hello
        +hello(expanded)
      }
      UNION {
        world
        +world(expanded)
      }
    }
  }
  UNION {
    NUMERIC {100.000000 <= @date <= 200.000000}
    NUMERIC {500.000000 <= @date <= inf}
  }
}

EOT;

        $schema = [new TextField('text_field')];

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));
        $this->assertEquals(
            $expectedResponse,
            $redis->ftexplain('index', '(foo bar)|(hello world) @date:[100 200]|@date:[500 +inf]')
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('index: no such index');

        $redis->ftexplain('index', 'query');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', 'query', null],
                ['index', 'query'],
            ],
            'with DIALECT' => [
                ['index', 'query', (new ExplainArguments())->dialect('dialect')],
                ['index', 'query', 'DIALECT', 'dialect'],
            ],
        ];
    }
}
