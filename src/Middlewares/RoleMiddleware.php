<?php

namespace Moahada\Permission\Middlewares;

use Closure;
use Moahada\Permission\Exceptions\UnauthorizedRole;
use Moahada\Permission\Exceptions\UserNotLoggedIn;
use Moahada\Permission\Helpers;

/**
 * Class RoleMiddleware
 * @package Moahada\Permission\Middlewares
 */
class RoleMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $role
     *
     * @return mixed
     * @throws \Moahada\Permission\Exceptions\UnauthorizedException
     */
    public function handle($request, Closure $next, $role)
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $roles = \is_array($role) ? $role : \explode('|', $role);

        if (! app('auth')->user()->hasAnyRole($roles)) {
            $helpers = new Helpers();
            throw new UnauthorizedRole(403, $helpers->getUnauthorizedRoleMessage(implode(', ', $roles)), $roles);
        }

        return $next($request);
    }
}