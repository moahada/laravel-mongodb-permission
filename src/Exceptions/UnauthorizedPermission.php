<?php

namespace Moahada\Permission\Exceptions;

/**
 * Class UnauthorizedPermission
 * @package Moahada\Permission\Exceptions
 */
class UnauthorizedPermission extends UnauthorizedException
{
    /**
     * UnauthorizedPermission constructor.
     *
     * @param $statusCode
     * @param string $message
     * @param array $requiredPermissions
     */
    public function __construct($statusCode, string $message = null, array $requiredPermissions = [])
    {
        parent::__construct($statusCode, $message, [], $requiredPermissions);
    }
}