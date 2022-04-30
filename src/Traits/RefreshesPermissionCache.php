<?php

namespace Moahada\Permission\Traits;

use Moahada\Permission\PermissionRegistrar;

/**
 * Trait RefreshesPermissionCache
 * @package Moahada\Permission\Traits
 */
trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache()
    {
        static::saved(function () {
            \app(\config('permission.models.permission'))->forgetCachedPermissions();
        });

        static::deleted(function () {
            \app(\config('permission.models.permission'))->forgetCachedPermissions();
        });
    }
}