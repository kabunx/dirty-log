<?php


namespace Golly\DirtyLog\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Interface DirtyLogInterface
 * @package Golly\DirtyLog\Contracts
 */
interface DirtyLogInterface
{

    /**
     * @return MorphTo
     */
    public function subject(): MorphTo;

    /**
     * @return MorphTo
     */
    public function causer(): MorphTo;

    /**
     * @param $keys
     * @return \Illuminate\Support\Collection
     */
    public function getChangedProperties($keys);

    /**
     * @param Builder $query
     * @param ...$names
     * @return Builder
     */
    public function scopeInLog(Builder $query, ...$names): Builder;

    /**
     * @param Builder $query
     * @param Model $causer
     * @return Builder
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder;

    /**
     * @param Builder $query
     * @param Model $subject
     * @return Builder
     */
    public function scopeOnSubject(Builder $query, Model $subject): Builder;

}
