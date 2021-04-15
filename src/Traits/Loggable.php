<?php


namespace Golly\DirtyLog\Traits;


use Exception;
use Golly\DirtyLog\DirtyLogger;
use Golly\DirtyLog\Exceptions\CouldNotLogException;
use Golly\DirtyLog\Models\DirtyLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Trait Loggable
 * @package Golly\DirtyLog\Traits
 * @property \Illuminate\Database\Eloquent\Collection $dirtyLogs
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
    protected $defaultLogTemplate = 'The subject :subject_type[:subject_id] was :handled by :causer_type[:causer_id]';

    /**
     * 模型数据变更日志
     *
     * @return void
     */
    protected static function bootLoggable()
    {
        static::eventsToBeLogged()->each(function ($eventName) {
            return static::$eventName(function ($model) use ($eventName) {
                try {
                    /** @var Model|self $model */
                    $logName = $model->getLogName($model, $eventName);
                    $template = $model->getLogTemplate($eventName);
                    $logger = (new DirtyLogger())->setName($logName)->on($model);
                    if ($model->causer) {
                        $logger->by($model->causer);
                    }
                    $logger->write($template);
                } catch (CouldNotLogException $e) {

                }
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
        return tap($this->newInstance($attributes), function ($instance) use ($causer) {
            $instance->setCauser($causer);
            $instance->save();
        });
    }

    /**
     * @param array $attributes
     * @param Model|null $causer
     * @return bool
     */
    public function updateByCauser(array $attributes = [], Model $causer = null)
    {
        $this->setCauser($causer);

        return $this->update($attributes);
    }

    /**
     * @param Model|null $causer
     * @return bool|null
     */
    public function deleteByCauser(Model $causer = null)
    {
        $this->setCauser($causer);
        try {
            return $this->delete();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return MorphMany
     */
    public function dirtyLogs()
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
     * @return $this
     */
    public function setCauser(Model $causer = null)
    {
        $this->causer = $causer;

        return $this;
    }

    /**
     * Get the event names that should be recorded.
     *
     * @return Collection
     */
    protected static function eventsToBeLogged(): Collection
    {
        $events = collect(['created', 'updated', 'deleted']);

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
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
    protected function getLogTemplate(string $eventName): string
    {
        $template = $this->logTemplate ?? $this->defaultLogTemplate;

        return str_replace(':handled', $eventName, $template);
    }

}
