<?php

namespace Predis\Transaction\Response;

use Predis\Response\ResponseInterface;

/**
 * Wrapper for the responses that associated with commands executed bypassing transaction logic.
 */
class BypassTransactionResponse implements ResponseInterface
{
    /**
     * @var mixed
     */
    private $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
