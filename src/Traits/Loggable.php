<?php


namespace Golly\DirtyLog\Traits;


use Exception;
use Golly\DirtyLog\DirtyLogger;
use Golly\DirtyLog\Models\DirtyLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Trait Loggable
 * @package Golly\DirtyLog\Traits
 * @mixin Model
 */
trait Loggable
{

    /**
     * @var Model|null
     */
    protected $causer;

    /**
     * 默认日志描述
     *
     * @var string
     */
    protected $defaultLogDescription = 'The subject :subject_type[:subject_id] was :handle by :causer.name';

    /**
     * 模型数据变更日志
     */
    protected static function bootLoggable()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            return static::$eventName(function (Model $model) use ($eventName) {
                /** @var Model|self $model */
                if (!$model->shouldLogEvent($eventName)) {
                    return;
                }
                $logName = $model->getLogName($model, $eventName);
                $description = $model->getLogDescription($eventName);
                $props = $model->getChangeProps();
                $logger = (new DirtyLogger())->useLog($logName)->on($model)->withProperties($props);
                if ($model->causer) {
                    $logger->by($model->causer);
                }
                $logger->log($description);
            });
        });
    }

    /**
     * 创建日志的时候附带执行者
     *
     * @param array $attributes
     * @param Model|null $causer
     * @return Model|$this
     */
    public function createByCauser(array $attributes = [], Model $causer = null)
    {
        return tap($this->newModelInstance($attributes), function ($instance) use ($causer) {
            $instance->setCauser($causer);
            $instance->save();
        });
    }

    /**
     * @param Model|null $causer
     * @return bool|null
     */
    public function deleteByCauser(Model $causer = null)
    {
        $this->setCauser($causer);
        try {
            return parent::delete();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return MorphMany
     */
    public function logs()
    {
        return $this->morphMany(
            DirtyLog::class,
            'subject'
        );
    }

    /**
     * 在某些条件下需要指定执行者
     *
     * @param Model|null $causer
     */
    public function setCauser(Model $causer = null)
    {
        $causer && $this->causer = $causer;
    }

    /**
     * Get the event names that should be recorded.
     *
     * @return Collection
     */
    protected static function eventsToBeRecorded(): Collection
    {
        $events = collect(['created', 'updated', 'deleted']);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }


    /**
     * @param string $eventName
     * @return bool
     */
    protected function shouldLogEvent(string $eventName): bool
    {
        if (!in_array($eventName, ['created', 'updated'])) {
            return true;
        }

        if (Arr::has($this->getDirty(), 'deleted_at')) {
            if ($this->getDirty()['deleted_at'] === null) {
                return false;
            }
        }

        return (bool)count($this->getDirty());
    }


    /**
     * @param $model
     * @param string $eventName
     * @return string
     */
    protected function getLogName($model, string $eventName = ''): string
    {
        return $model->logName ?? $eventName;
    }

    /**
     * @param string $eventName
     * @return string
     */
    protected function getLogDescription(string $eventName): string
    {
        $description = $this->logDescription ?? $this->defaultLogDescription;

        return str_replace(':handle', $eventName, $description);
    }


    /**
     * @return array
     */
    protected function getChangeProps(): array
    {
        return [
            'new' => $this->getAttributes(),
            'old' => $this->getRawOriginal(),
            'dirty' => $this->getDirty()
        ];
    }

}
