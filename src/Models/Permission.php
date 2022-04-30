<?php

namespace Moahada\Permission\Models;

use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Moahada\Permission\Contracts\PermissionInterface;
use Moahada\Permission\Exceptions\PermissionAlreadyExists;
use Moahada\Permission\Exceptions\PermissionDoesNotExist;
use Moahada\Permission\Guard;
use Moahada\Permission\Helpers;
use Moahada\Permission\PermissionRegistrar;
use Moahada\Permission\Traits\HasRoles;
use Moahada\Permission\Traits\RefreshesPermissionCache;

/**
 * Class Permission
 * @package Moahada\Permission\Models
 */
class Permission extends Model implements PermissionInterface
{
    use HasRoles;
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected $helpers;

    /**
     * Permission constructor.
     *
     * @param array $attributes
     *
     * @throws \ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);

        parent::__construct($attributes);

        $this->helpers = new Helpers();

        $this->setTable(config('permission.collection_names.permissions'));
    }

    /**
     * Create new Permission
     *
     * @param array $attributes
     *
     * @return $this|\Illuminate\Database\Eloquent\Model
     * @throws \Moahada\Permission\Exceptions\PermissionAlreadyExists
     * @throws \ReflectionException
     */
    public static function create(array $attributes = [])
    {
        $helpers = new Helpers();
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);

        if (static::getPermissions()->where('name', $attributes['name'])->where(
            'guard_name',
            $attributes['guard_name']
        )->first()) {
            $name = (string)$attributes['name'];
            $guardName = (string)$attributes['guard_name'];
            throw new PermissionAlreadyExists($helpers->getPermissionAlreadyExistsMessage($name, $guardName));
        }

        return $helpers->checkVersion() ? parent::create($attributes) : static::query()->create($attributes);
    }

    /**
     * Find or create permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string $guardName
     *
     * @return PermissionInterface
     * @throws \Moahada\Permission\Exceptions\PermissionAlreadyExists
     * @throws \ReflectionException
     */
    public static function findOrCreate(string $name, string $guardName = null): PermissionInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $permission = static::getPermissions()->filter(function ($permission) use ($name, $guardName) {
            return $permission->name === $name && $permission->guard_name === $guardName;
        })->first();

        if (!$permission) {
            $permission = static::create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $permission;
    }

    /**
     * A permission can be applied to roles.
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.role'));
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany($this->helpers->getModelForGuard($this->attributes['guard_name']));
    }

    /**
     * Find a permission by its name (and optionally guardName).
     *
     * @param string $name
     * @param string $guardName
     *
     * @return PermissionInterface
     * @throws PermissionDoesNotExist
     * @throws \ReflectionException
     */
    public static function findByName(string $name, string $guardName = null): PermissionInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $permission = static::getPermissions()->filter(function ($permission) use ($name, $guardName) {
            return $permission->name === $name && $permission->guard_name === $guardName;
        })->first();

        if (!$permission) {
            $helpers = new Helpers();
            throw new PermissionDoesNotExist($helpers->getPermissionDoesNotExistMessage($name, $guardName));
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     * @return Collection
     */
    protected static function getPermissions(): Collection
    {
        return \app(PermissionRegistrar::class)->getPermissions();
    }
}