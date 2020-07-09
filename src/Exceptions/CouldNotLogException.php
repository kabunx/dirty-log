<?php


namespace Golly\DirtyLog\Exceptions;


use Exception;

/**
 * Class CouldNotLogException
 * @package Golly\DirtyLog\Exceptions
 */
class CouldNotLogException extends Exception
{

    /**
     * @param $id
     * @return static
     */
    public static function couldNotDetermineUser($id)
    {
        return new static("Could not determine a user with identifier `{$id}`.");
    }

    /**
     * @param $attribute
     * @return static
     */
    public static function invalidAttribute($attribute)
    {
        return new static("Cannot log attribute `{$attribute}`. Can only log attributes of a model or a directly related model.");
    }
}
