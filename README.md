# laravel mongodb permissions

Once installed you can do stuff like this:

```php
// Adding permissions to a user
$user->givePermissionTo('edit articles');

// Adding permissions via a role
$user->assignRole('writer');

$role->givePermissionTo('edit articles');
```

Because all permissions will be registered on [Laravel's gate](https://laravel.com/docs/5.5/authorization), you can test if a user has a permission with Laravel's default `can` function:

```php
$user->can('edit articles');
```

## Installation
You can install the package via composer:

``` bash
composer require moahada/laravel-mongodb-permission
```

You can publish [the migration](database/migrations/create_permission_collections.php.stub) with:

```bash
php artisan vendor:publish --provider="Moahada\Permission\PermissionServiceProvider" --tag="migrations"
```

```bash
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Moahada\Permission\PermissionServiceProvider" --tag="config"
```

When published, the [`config/permission.php`](config/permission.php) config file contains:

```php
return [

    'models' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Moloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Moahada\Permission\Contracts\Permission` contract.
         */

        'permission' => Moahada\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Moloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Moahada\Permission\Contracts\Role` contract.
         */

        'role' => Moahada\Permission\Models\Role::class,

    ],

    'collection_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',
    ],

    /*
     * By default all permissions will be cached for 24 hours unless a permission or
     * role is updated. Then the cache will be flushed immediately.
     */

    'cache_expiration_time' => 60 * 24,

    /*
     * By default we'll make an entry in the application log when the permissions
     * could not be loaded. Normally this only occurs while installing the packages.
     *
     * If for some reason you want to disable that logging, set this value to false.
     */

    'log_registration_exception' => true,
    
    /*
     * When set to true, the required permission/role names are added to the exception
     * message. This could be considered an information leak in some contexts, so
     * the default setting is false here for optimum safety.
     */
    
    'display_permission_in_exception' => false,
];
```


## Usage

First, add the `Moahada\Permission\Traits\HasRoles` trait to your `User` model(s):

```php
use Illuminate\Auth\Authenticatable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Moahada\Permission\Traits\HasRoles;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasRoles;

    // ...
}
```

> Note: that if you need to use `HasRoles` trait with another model ex.`Page` you will also need to add `protected $guard_name = 'web';` as well to that model or you would get an error

```php
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Moahada\Permission\Traits\HasRoles;

class Page extends Model
{
    use HasRoles;

    protected $guard_name = 'web'; // or whatever guard you want to use

    // ...
}
```

This package allows for users to be associated with permissions and roles. Every role is associated with multiple permissions.
A `Role` and a `Permission` are regular Moloquent models. They require a `name` and can be created like this:

```php
use Moahada\Permission\Models\Role;
use Moahada\Permission\Models\Permission;

$role = Role::create(['name' => 'writer']);
$permission = Permission::create(['name' => 'edit articles']);
```

A permission can be assigned to a role using 1 of these methods:

```php
$role->givePermissionTo($permission);
$permission->assignRole($role);
```

Multiple permissions can be synced to a role using 1 of these methods:

```php
$role->syncPermissions($permissions);
$permission->syncRoles($roles);
```

A permission can be removed from a role using 1 of these methods:

```php
$role->revokePermissionTo($permission);
$permission->removeRole($role);
```

If you're using multiple guards the `guard_name` attribute needs to be set as well. Read about it in the [using multiple guards](#using-multiple-guards) section of the readme.

The `HasRoles` trait adds Moloquent relationships to your models, which can be accessed directly or used as a base query:

```php
// get a list of all permissions directly assigned to the user
$permissions = $user->permissions; // Returns a collection

// get all permissions inherited by the user via roles
$permissions = $user->getAllPermissions(); // Returns a collection

// get all permissions names
$permissions = $user->getPermissionNames(); // Returns a collection

// get a collection of all defined roles
$roles = $user->roles->pluck('name'); // Returns a collection

// get all role names
$roles = $user->getRoleNames() // Returns a collection;
```

The `HasRoles` trait also adds a `role` scope to your models to scope the query to certain roles or permissions:

```php
$users = User::role('writer')->get(); // Returns only users with the role 'writer'
$users = User::permission('edit articles')->get(); // Returns only users with the permission 'edit articles'
```

The scope can accept a string, a `\Moahada\Permission\Models\Role` object, a `\Moahada\Permission\Models\Permission` object or an `\Illuminate\Support\Collection` object.


### Using "direct" permissions

A permission can be given to any user with the `HasRoles` trait:

```php
$user->givePermissionTo('edit articles');

// You can also give multiple permission at once
$user->givePermissionTo('edit articles', 'delete articles');

// You may also pass an array
$user->givePermissionTo(['edit articles', 'delete articles']);
```

A permission can be revoked from a user:

```php
$user->revokePermissionTo('edit articles');
```

Or revoke & add new permissions in one go:

```php
$user->syncPermissions(['edit articles', 'delete articles']);
```

You can test if a user has a permission:

```php
$user->hasPermissionTo('edit articles');
```

...or if a user has multiple permissions:

```php
$user->hasAnyPermission(['edit articles', 'publish articles', 'unpublish articles']);
```

Saved permissions will be registered with the `Illuminate\Auth\Access\Gate` class for the default guard. So you can
test if a user has a permission with Laravel's default `can` function:

```php
$user->can('edit articles');
```

### Using permissions via roles

A role can be assigned to any user:

```php
$user->assignRole('writer');

// You can also assign multiple roles at once
$user->assignRole('writer', 'admin');
// or as an array
$user->assignRole(['writer', 'admin']);
```

A role can be removed from a user:

```php
$user->removeRole('writer');
```

Roles can also be synced:

```php
// All current roles will be removed from the user and replaced by the array given
$user->syncRoles(['writer', 'admin']);
```

You can determine if a user has a certain role:

```php
$user->hasRole('writer');
```

You can also determine if a user has any of a given list of roles:

```php
$user->hasAnyRole(Role::all());
```

You can also determine if a user has all of a given list of roles:

```php
$user->hasAllRoles(Role::all());
```

The `assignRole`, `hasRole`, `hasAnyRole`, `hasAllRoles`  and `removeRole` functions can accept a
 string, a `\Moahada\Permission\Models\Role` object or an `\Illuminate\Support\Collection` object.

A permission can be given to a role:

```php
$role->givePermissionTo('edit articles');
```

You can determine if a role has a certain permission:

```php
$role->hasPermissionTo('edit articles');
```

A permission can be revoked from a role:

```php
$role->revokePermissionTo('edit articles');
```

The `givePermissionTo` and `revokePermissionTo` functions can accept a
string or a `Moahada\Permission\Models\Permission` object.

Permissions are inherited from roles automatically.
Additionally, individual permissions can be assigned to the user too. 

For instance:

```php
$role = Role::findByName('writer');
$role->givePermissionTo('edit articles');

$user->assignRole('writer');

$user->givePermissionTo('delete articles');
```

In the above example, a role is given permission to edit articles and this role is assigned to a user.
Now the user can edit articles and additionally delete articles. The permission of `delete articles` is the user's direct permission because it is assigned directly to them.
When we call `$user->hasDirectPermission('delete articles')` it returns `true`, but `false` for `$user->hasDirectPermission('edit articles')`.

This method is useful if one builds a form for setting permissions for roles and users in an application and wants to restrict or change inherited permissions of roles of the user, i.e. allowing to change only direct permissions of the user.

You can list all of these permissions:

```php
// Direct permissions
$user->getDirectPermissions() // Or $user->permissions;

// Permissions inherited from the user's roles
$user->getPermissionsViaRoles();

// All permissions which apply on the user (inherited and direct)
$user->getAllPermissions();
```

All these responses are collections of `Moahada\Permission\Models\Permission` objects.

If we follow the previous example, the first response will be a collection with the `delete article` permission, the
second will be a collection with the `edit article` permission and the third will contain both.

### Using Blade directives
This package also adds Blade directives to verify whether the currently logged in user has all or any of a given list of roles.

Optionally you can pass in the `guard` that the check will be performed on as a second argument.
#### Blade and Roles
Test for a specific role:
```php
@role('writer')
    I am a writer!
@else
    I am not a writer...
@endrole
```
is the same as
```php
@hasrole('writer')
    I am a writer!
@else
    I am not a writer...
@endhasrole
```
Test for any role in a list:
```php
@hasanyrole(Role::all())
    I have one or more of these roles!
@else
    I have none of these roles...
@endhasanyrole
// or
@hasanyrole('writer|admin')
    I am either a writer or an admin or both!
@else
    I have none of these roles...
@endhasanyrole
```
Test for all roles:
```php
@hasallroles(Role::all())
    I have all of these roles!
@else
    I do not have all of these roles...
@endhasallroles
// or
@hasallroles('writer|admin')
    I am both a writer and an admin!
@else
    I do not have all of these roles...
@endhasallroles
```

#### Blade and Permissions
This package doesn't add any permission-specific Blade directives. Instead, use Laravel's native `@can` directive to check if a user has a certain permission.

```php
@can('edit articles')
  //
@endcan
```
or
```php
@if(auth()->user()->can('edit articles') && $some_other_condition)
  //
@endif
```


## Using artisan commands

You can create a role or permission from a console with artisan commands.

```bash
php artisan permission:create-role writer
```

```bash
php artisan permission:create-permission 'edit articles'
```

When creating permissions and roles for specific guards you can specify the guard names as a second argument:

```bash
php artisan permission:create-role writer web
```

```bash
php artisan permission:create-permission 'edit articles' web
```
