<?php

namespace Denismitr\Permissions\Models;


use Denismitr\Permissions\Contracts\UserRole;
use Denismitr\Permissions\Exceptions\AuthGroupAlreadyExists;
use Denismitr\Permissions\Exceptions\AuthGroupDoesNotExist;
use Denismitr\Permissions\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class AuthGroup extends Model implements UserRole
{
    use HasPermissions;

    protected $guarded = ['id'];

    /**
     * @param array $attributes
     * @return $this|Model
     * @throws AuthGroupAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        if (static::whereName($attributes['name'])->first()) {
            throw AuthGroupAlreadyExists::create($attributes['name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @param string $name
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function findByName(string $name): self
    {
        $role = static::query()->whereName($name)->first();

        if ( ! $role ) {
            throw AuthGroupDoesNotExist::create($name);
        }

        return $role;
    }

    /**
     * @param int $id
     * @return AuthGroup
     * @throws AuthGroupDoesNotExist
     */
    public static function findById(int $id): self
    {
        /** @var AuthGroup $role */
        $role = static::query()->find($id);

        if ( ! $role ) {
            throw AuthGroupDoesNotExist::createWithId($id);
        }

        return $role;
    }

    /**
     * @param string $name
     * @param null $guard
     * @return UserRole
     * @throws AuthGroupAlreadyExists
     */
    public static function findOrCreate(string $name, $guard = null): UserRole
    {
        $role = static::query()->whereName('name', $name)->first();

        if ( ! $role) {
            return static::create(['name' => $name]);
        }

        return $role;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class,
            config('permissions.table_names.auth_group_permissions')
        );
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('permissions.models.user'),
            'user',
            'user_roles',
            'role_id',
            'user_id'
        );
    }

    /**
     *  Verify if role has a permission
     *
     * @param  string $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        if (is_object($permission)) {
            return $this->permissions->contains('id', $permission->id);
        }

        return !! $this->permissions->where('name', $permission)->count();
    }
}