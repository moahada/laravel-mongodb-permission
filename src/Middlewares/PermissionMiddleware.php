<?php

namespace Moahada\Permission\Middlewares;

use Closure;
use Moahada\Permission\Exceptions\UnauthorizedPermission;
use Moahada\Permission\Exceptions\UserNotLoggedIn;
use Moahada\Permission\Helpers;

/**
 * Class PermissionMiddleware
 * @package Moahada\Permission\Middlewares
 */
class PermissionMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $permission
     *
     * @return mixed
     * @throws \Moahada\Permission\Exceptions\UnauthorizedException
     */
    public function handle($request, Closure $next, $permission)
    {
        if (app('auth')->guest()) {
            $helpers = new Helpers();
            throw new UserNotLoggedIn(403, $helpers->getUserNotLoggedINMessage());
        }

        $permissions = \is_array($permission) ? $permission : \explode('|', $permission);


        if (! app('auth')->user()->hasAnyPermission($permissions)) {
            $helpers = new Helpers();
            throw new UnauthorizedPermission(
                403,
                $helpers->getUnauthorizedPermissionMessage(implode(', ', $permissions)),
                $permissions
            );
        }

        return $next($request);
    }
}