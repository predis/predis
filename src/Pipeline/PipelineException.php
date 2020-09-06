<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\PredisException;
use Throwable;

/**
 * Exception class for pipeline errors.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PipelineException extends PredisException
{
    protected $pipeline;

    /**
     * @param Pipeline  $connection Pipeline associated to the exception
     * @param string    $message    Exception message
     * @param integer   $code       Exception code
     * @param Throwable $previous   Previous exception for exception chaining
     */
    public function __construct(Pipeline $pipeline, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->pipeline = $pipeline;
    }

    /**
     * Returns the pipeline associated to the exception.
     *
     * @return Pipeline
     */
    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }
}
