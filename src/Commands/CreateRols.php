<?php

namespace Moahada\Permission\Commands;

use Illuminate\Console\Command;
use Moahada\Permission\Contracts\RoleInterface as Role;

/**
 * Class CreateRole
 * @package Moahada\Permission\Commands
 */
class CreateRole extends Command
{
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}
        {--permission=* : The name of the permission}';

    protected $description = 'Create a role';

    public function handle()
    {
        $roleClass       = \app(\config('permission.models.role'));

        $name        = $this->argument('name');
        $guard       = $this->argument('guard');
        $permissions = $this->option('permission');

        $role = $roleClass::create([
            'name'       => $name,
            'guard_name' => $guard
        ]);

        $this->info("Role `{$role->name}` created");

        $role->givePermissionTo($permissions);
        $permissionsStr = $role->permissions->implode('name', '`, `');
        $this->info("Permissions `{$permissionsStr}` has been given to role `{$role->name}`");
    }
}