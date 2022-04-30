<?php

namespace Moahada\Permission\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * Class MoahadaException
 * @package Moahada\Permission\Exceptions
 */
class MoahadaException extends InvalidArgumentException
{
    /**
     * MoahadaException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if (\config('permission.log_registration_exception')) {
            $logger = \app('log');
            $logger->alert($message);
        }
    }
}