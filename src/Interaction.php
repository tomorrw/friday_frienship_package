<?php

namespace Tomorrow\FridayFriendship;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use stdClass;

/**
 * Class Interaction.
 */
class Interaction
{

    public static function getFullModelName($modelClassName)
    {
        if (class_exists($modelClassName)) {
            return Str::studly($modelClassName);
        }

        $namespace = config('FridayFriendship.model_namespace', 'App');

        return empty($namespace)
            ? Str::studly($modelClassName)
            : $namespace . '\\' . Str::studly($modelClassName);
    }

    public static function getFriendshipModelName()
    {
        return Interaction::getFullModelName(
            config(
                'FridayFriendship.models.friendship',
                \Tomorrow\FridayFriendship\Models\Friendship::class
            )
        );
    }
}
