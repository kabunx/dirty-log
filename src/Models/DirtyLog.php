<?php


namespace Golly\DirtyLog\Models;


use Golly\DirtyLog\Contracts\DirtyLogInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

/**
 * Class Loggable
 * @property string $name
 * @property string $template
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property \Illuminate\Support\Collection $properties
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property string $created_at
 * @property string $updated_at
 * @property Model $subject
 * @property Model|null $causer
 * @method Builder inLog(string $logName)
 * @method Builder causedBy(Model $model)
 * @method Builder onSubject(Model $model)
 * @package Golly\DirtyLog\Models
 */
class DirtyLog extends Model implements DirtyLogInterface
{

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string[]
     */
    protected $casts = [
        'properties' => 'collection',
    ];

    /**
     * 变动模型
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }


    /**
     * 起因、引发者
     * @return MorphTo
     */
    public function causer(): MorphTo
    {
        return $this->morphTo('causer');
    }

    /**
     * 获取修改属性的字段
     *
     * @param array|string $keys
     * @return \Illuminate\Support\Collection
     */
    public function getChangedProperties($keys)
    {
        return $this->properties->only($keys);
    }

    /**
     * @param Builder $query
     * @param mixed ...$names
     * @return Builder
     */
    public function scopeInLog(Builder $query, ...$names): Builder
    {
        $names = Arr::flatten($names);

        return $query->whereIn('name', $names);
    }

    /**
     * @param Builder $query
     * @param Model $causer
     * @return Builder
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    /**
     * @param Builder $query
     * @param Model $subject
     * @return Builder
     */
    public function scopeOnSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
