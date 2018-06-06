<?php

namespace Denismitr\LTP\Models;

use Denismitr\LTP\Contracts\HasGuard;
use Denismitr\LTP\Contracts\UserRole;
use Denismitr\LTP\Exceptions\GuardMismatch;
use Denismitr\LTP\Exceptions\PermissionDoesNotExist;
use Denismitr\LTP\Exceptions\RoleAlreadyExists;
use Denismitr\LTP\Exceptions\RoleDoesNotExist;
use Denismitr\LTP\Guard;
use Denismitr\LTP\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model implements HasGuard, UserRole
{
    use HasPermissions;

    protected $guarded = ['id'];

    /**
     * Role constructor.
     * @param array $attributes
     * @throws \ReflectionException
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);

        parent::__construct($attributes);
    }

    /**
     * @param array $attributes
     * @return $this|Model
     * @throws RoleAlreadyExists
     * @throws \ReflectionException
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard'] = $attributes['guard'] ?? Guard::getDefault(static::class);

        if (static::whereName($attributes['name'])->whereGuard($attributes['guard'])->first()) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard']);
        }

        return static::query()->create($attributes);
    }

    /**
     * @param string $name
     * @param string|null $guard
     * @return Role
     * @throws RoleDoesNotExist
     * @throws \ReflectionException
     */
    public static function findByName(string $name, string $guard = null): self
    {
        $guard = $guard ?: Guard::getDefault(static::class);

        $role = static::query()->whereName($name)->whereGuard($guard)->first();

        if ( ! $role ) {
            throw RoleDoesNotExist::create($name, $guard);
        }

        return $role;
    }

    /**
     * @param int $id
     * @return Role
     * @throws RoleDoesNotExist
     */
    public static function findById(int $id): self
    {
        /** @var Role $role */
        $role = static::query()->find($id);

        if ( ! $role ) {
            throw RoleDoesNotExist::createWithId($id);
        }

        return $role;
    }

    /**
     * @param string $name
     * @param null $guard
     * @return UserRole
     * @throws RoleAlreadyExists
     * @throws \ReflectionException
     */
    public static function findOrCreate(string $name, $guard = null): UserRole
    {
        $guard = $guard ?? Guard::getDefault(static::class);

        $role = static::query()->whereName('name', $name)->whereGuard($guard)->first();

        if ( ! $role) {
            return static::create(['name' => $name, 'guard' => $guard]);
        }

        return $role;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permissions.models.permission'),
            config('permissions.table_names.role_permissions')
        );
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            Guard::getModelFor($this->attributes['guard']),
            'user',
            'user_roles',
            'role_id',
            'user_id'
        );
    }

    /**
     * @return string
     */
    public function getGuard(): string
    {
        return $this->guard;
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

    /**
     * @param HasGuard $roleOrPermission
     * @throws GuardMismatch
     * @throws \ReflectionException
     */
    protected function verifySharedGuard(HasGuard $roleOrPermission)
    {
        if ( ! Guard::getNames($this)->contains($roleOrPermission->getGuard()) ) {
            throw GuardMismatch::create($roleOrPermission->getGuard(), Guard::getNames($this));
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    protected function getGuards()
    {
        return Guard::getNames($this);
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    protected function getDefaultGuard(): string
    {
        return Guard::getDefault($this);
    }
}
