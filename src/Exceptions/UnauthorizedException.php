<?php

namespace Moahada\Permission\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UnauthorizedException
 * @package Moahada\Permission\Exceptions
 */
class UnauthorizedException extends HttpException
{
    private $requiredRoles = [];
    private $requiredPermissions = [];

    /**
     * UnauthorizedException constructor.
     *
     * @param $statusCode
     * @param string $message
     * @param array $requiredRoles
     * @param array $requiredPermissions
     */
    public function __construct(
        $statusCode,
        string $message = null,
        array $requiredRoles = [],
        array $requiredPermissions = []
    ) {
        parent::__construct($statusCode, $message);

        if (\config('permission.log_registration_exception')) {
            $logger = \app('log');
            $logger->alert($message);
        }

        $this->requiredRoles       = $requiredRoles;
        $this->requiredPermissions = $requiredPermissions;
    }

    /**
     * Return Required Roles
     *
     * @return array
     */
    public function getRequiredRoles(): array
    {
        return $this->requiredRoles;
    }

    /**
     * Return Required Permissions
     *
     * @return array
     */
    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public static function forRolesOrPermissions(array $rolesOrPermissions): self
    {
        $message = 'User does not have any of the necessary access rights.';

        if (config('permission.display_permission_in_exception') && config('permission.display_role_in_exception')) {
            $permStr = implode(', ', $rolesOrPermissions);
            $message = 'User does not have the right permissions. Necessary permissions are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredPermissions = $rolesOrPermissions;

        return $exception;
    }
}
