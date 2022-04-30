<?php

namespace Moahada\Permission\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsToMany;
use Moahada\Permission\Contracts\RoleInterface;
use Moahada\Permission\Exceptions\GuardDoesNotMatch;
use Moahada\Permission\Exceptions\RoleAlreadyExists;
use Moahada\Permission\Exceptions\RoleDoesNotExist;
use Moahada\Permission\Guard;
use Moahada\Permission\Helpers;
use Moahada\Permission\Traits\HasPermissions;
use Moahada\Permission\Traits\RefreshesPermissionCache;
use ReflectionException;

/**
 * Class Role
 * @package Moahada\Permission\Models
 */
class Role extends Model implements RoleInterface
{
    use HasPermissions;
    use RefreshesPermissionCache;

    public $guarded = ['id'];
    protected $helpers;

    /**
     * Role constructor.
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

        $this->setTable(config('permission.collection_names.roles'));
    }

    /**
     * @param array $attributes
     *
     * @return $this|Model
     * @throws RoleAlreadyExists
     * @internal param array $attributes§
     *
     * @throws \ReflectionException
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? (new Guard())->getDefaultName(static::class);
        $helpers = new Helpers();

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            $name = (string)$attributes['name'];
            $guardName = (string)$attributes['guard_name'];
            throw new RoleAlreadyExists($helpers->getRoleAlreadyExistsMessage($name, $guardName));
        }

        return $helpers->checkVersion() ? parent::create($attributes) : static::query()->create($attributes);
    }

    /**
     * Find or create role by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return RoleInterface
     * @throws \Moahada\Permission\Exceptions\RoleAlreadyExists
     * @throws \ReflectionException
     */
    public static function findOrCreate(string $name, $guardName = null): RoleInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $role = static::create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $role;
    }

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return RoleInterface
     * @throws RoleDoesNotExist
     * @throws \ReflectionException
     */
    public static function findByName(string $name, $guardName = null): RoleInterface
    {
        $guardName = $guardName ?? (new Guard())->getDefaultName(static::class);

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$role) {
            $helpers = new Helpers();
            throw new RoleDoesNotExist($helpers->getRoleDoesNotExistMessage($name, $guardName));
        }

        return $role;
    }

    /**
     * A role belongs to some users of the model associated with its guard.
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany($this->helpers->getModelForGuard($this->attributes['guard_name']));
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws GuardDoesNotMatch
     * @throws ReflectionException
     */
    public function hasPermissionTo($permission): bool
    {
        if (\is_string($permission)) {
            $permission = $this->getPermissionClass()->findByName($permission, $this->getDefaultGuardName());
        }

        if (!$this->getGuardNames()->contains($permission->guard_name)) {
            $expected = $this->getGuardNames();
            $given = $permission->guard_name;

            throw new GuardDoesNotMatch($this->helpers->getGuardDoesNotMatchMessage($expected, $given));
        }

        return $this->permissions->contains('id', $permission->id);
    }
}