<?php


namespace Golly\DirtyLog;


use Carbon\Carbon;
use Golly\DirtyLog\Contracts\DirtyLogInterface;
use Golly\DirtyLog\Models\DirtyLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

/**
 * Class DirtyLogger
 * @package Golly\DirtyLog
 */
class DirtyLogger
{
    use Macroable;

    /**
     * 默认日志名称
     *
     * @var string
     */
    protected $defaultLogName = 'default';

    /**
     * @var DirtyLog
     */
    protected $activity;


    /**
     * 变动模型
     *
     * @param Model $model
     * @return $this
     */
    public function performedOn(Model $model)
    {
        $this->getActivity()->subject()->associate($model);

        return $this;
    }

    /**
     * 变动模型
     *
     * @param Model $model
     * @return $this
     */
    public function on(Model $model)
    {
        return $this->performedOn($model);
    }


    /**
     * 触发者
     *
     * @param mixed $causer
     * @return $this
     */
    public function causedBy(Model $causer = null)
    {
        if ($causer === null) {
            return $this;
        }
        $this->getActivity()->causer()->associate($causer);

        return $this;
    }

    /**
     * 触发者
     *
     * @param Model|null $model
     * @return $this
     */
    public function by(Model $model = null)
    {
        return $this->causedBy($model);
    }


    /**
     * 不指定触发者
     *
     * @return $this
     */
    public function causedByAnonymous()
    {
        $this->activity->causer_id = null;
        $this->activity->causer_type = null;

        return $this;
    }

    /**
     * 不指定触发者
     *
     * @return $this
     */
    public function byAnonymous()
    {
        return $this->causedByAnonymous();
    }

    /**
     * 变动属性集
     *
     * @param $properties
     * @return $this
     */
    public function withProperties($properties)
    {
        $this->getActivity()->properties = collect($properties);

        return $this;
    }

    /**
     * 变体属性
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function withProperty(string $key, $value)
    {
        $this->getActivity()->properties = $this->getActivity()->properties->put($key, $value);

        return $this;
    }

    /**
     * 创建时间
     *
     * @param Carbon $dateTime
     * @return $this
     */
    public function createdAt(Carbon $dateTime)
    {
        $this->getActivity()->created_at = $dateTime;

        return $this;
    }

    /**
     * 日志名称
     *
     * @param string $logName
     * @return $this
     */
    public function useLog(string $logName)
    {
        $this->getActivity()->name = $logName;

        return $this;
    }

    /**
     * 日志名称
     *
     * @param string $logName
     * @return $this
     */
    public function inLog(string $logName)
    {
        return $this->useLog($logName);
    }

    /**
     * @param callable $callback
     * @param string|null $eventName
     * @return $this
     */
    public function tap(callable $callback, string $eventName = null)
    {
        call_user_func($callback, $this->getActivity(), $eventName);

        return $this;
    }


    /**
     * 保存描述信息
     *
     * @param string $description
     * @return DirtyLogInterface
     */
    public function log(string $description)
    {
        $activity = $this->activity;

        $activity->description = $this->replacePlaceholders(
            $activity->description ?? $description,
            $activity
        );
        $activity->save();

        $this->activity = null;

        return $activity;
    }

    /**
     * 描述模版替换(The subject :subject.name was handled by :causer.name and Laravel is :properties.laravel)
     *
     * @param string $description
     * @param DirtyLogInterface $activity
     * @return string
     */
    protected function replacePlaceholders(string $description, DirtyLogInterface $activity): string
    {
        return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($activity) {
            $match = $match[0];

            $attribute = Str::before(Str::after($match, ':'), '.');

            if (!in_array($attribute, ['subject', 'causer', 'properties'])) {
                return $activity->$attribute ?? $match;
            }

            $propertyName = substr($match, strpos($match, '.') + 1);

            $attributeValue = $activity->$attribute;

            if (is_null($attributeValue)) {
                return $match;
            }

            $attributeValue = $attributeValue->toArray();

            return Arr::get($attributeValue, $propertyName, $match);
        }, $description);
    }

    /**
     * @return DirtyLogInterface
     */
    protected function getActivity(): DirtyLogInterface
    {
        if (!$this->activity instanceof DirtyLogInterface) {
            $user = auth()->user();
            $this->activity = new DirtyLog();
            $this->useLog($this->defaultLogName)
                ->withProperties([])
                ->causedBy($user);
        }

        return $this->activity;
    }
}
