<?php

namespace Undraw\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpException extends UndrawException
{
    public function __construct(
        string $message,
        public readonly ?RequestInterface $request = null,
        public readonly ?ResponseInterface $response = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
