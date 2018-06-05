<?php


namespace Denismitr\Permissions\Exception;


class PermissionDoesNotExist extends \Exception
{
    /**
     * @param string $name
     * @param string $guard
     * @param int|null $teamId
     * @return PermissionDoesNotExist
     */
    public static function create(string $name, string $guard, int $teamId = null): self
    {
        if ( ! $teamId) {
            return new static("A `{$name}` permission does not exist for guard `{$guard}`.");
        }

        return new static("A `{$name}` permission does not exist for guard `{$guard}` and team ID `$teamId`.");
    }

    /**
     * @param int $id
     * @return PermissionDoesNotExist
     */
    public static function createWithId(int $id): self
    {
        return new static("A permission with `{$id}` does not exist.");
    }
}