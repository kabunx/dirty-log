<?php


namespace Golly\DirtyLog\Models;


use Golly\DirtyLog\Contracts\DirtyLogInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/**
 * Class Loggable
 * @property string $name
 * @property string $description
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property Collection $properties
 * @property string|null $causer_type
 * @property string|null $causer_id
 * @property string $created_at
 * @property string $updated_at
 * @property mixed $subject
 * @property mixed $causer
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
    public $guarded = [];

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
     * @param string $propertyName
     * @return mixed
     */
    public function getExtraProperty(string $propertyName)
    {
        return $this->properties->get($propertyName);
    }

    /**
     * 获取变动属性
     *
     * @return Collection
     */
    public function changes(): Collection
    {
        if (!$this->properties instanceof Collection) {
            return new Collection();
        }

        return $this->properties->only(['keys', 'new', 'old']);
    }

    /**
     * 获取变动属性
     *
     * @return Collection
     */
    public function getChangeAttrs(): Collection
    {
        return $this->changes();
    }

    /**
     * @param Builder $query
     * @param mixed ...$logNames
     * @return Builder
     */
    public function scopeInLog(Builder $query, ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('name', $logNames);
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
