<?php


namespace Golly\DirtyLog;

use Golly\DirtyLog\Contracts\DirtyLogInterface;
use Golly\DirtyLog\Exceptions\CouldNotLogException;
use Golly\DirtyLog\Models\DirtyLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DirtyLogger
 * @package Golly\DirtyLog
 */
class DirtyLogger
{
    /**
     * @var string
     */
    protected $name = 'default';

    /**
     * @var string
     */
    protected $template = 'The subject {{subject}} was modified by {{causer}}.';

    /**
     * @var DirtyLog
     */
    protected $dirtyLog;

    /**
     * @var Model
     */
    protected $subject;

    /**
     * @var Model
     */
    protected $causer;

    /**
     * DirtyLogger constructor.
     */
    public function __construct()
    {
        $this->initDirtyLog();
    }

    /**
     * @return static
     */
    public static function init()
    {
        return new static();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function inLog(string $name)
    {
        return $this->setName($name);
    }

    /**
     * 变动模型
     *
     * @param Model $subject
     * @return $this
     */
    public function on(Model $subject)
    {
        $this->subject = $subject;
        $this->dirtyLog->subject()->associate($subject);

        return $this;
    }

    /**
     * 触发者
     *
     * @param Model|null $causer
     * @return $this
     */
    public function by(Model $causer = null)
    {
        if (is_null($causer)) {
            return $this->byAnonymous();
        }
        $this->causer = $causer;
        $this->dirtyLog->causer()->associate($causer);

        return $this;
    }

    /**
     * 匿名用户操作
     *
     * @return $this
     */
    public function byAnonymous()
    {
        $this->dirtyLog->causer_id = null;
        $this->dirtyLog->causer_type = null;

        return $this;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function changes(array $properties)
    {
        return $this->setProperties($properties);
    }

    /**
     * 写入日志
     *
     * @param string|null $template
     * @return $this
     * @throws CouldNotLogException
     */
    public function write(string $template = null)
    {
        if (is_null($this->subject)) {
            throw new CouldNotLogException('监控模型不存在');
        }
        // 新建模型
        if ($this->subject->wasRecentlyCreated) {
            $changes = $this->subject->attributesToArray();
        } else {
            $changes = $this->subject->getChanges();
        }
        $this->setProperties($changes);
        // 数据发生了变化
        if ($this->dirtyLog->properties->isNotEmpty()) {
            if (is_null($this->dirtyLog->name)) {
                $this->setName($this->name);
            }
            $template = $template ?: $this->template;
            $this->setTemplate($template);
            $this->dirtyLog->save();
        }

        // 重置
        $this->initDirtyLog();

        return $this;
    }

    /**
     * 日志名称
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->dirtyLog->name = $name;

        return $this;
    }

    /**
     * 变动属性
     *
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        $this->dirtyLog->properties = collect($properties);

        return $this;
    }

    /**
     * 补充属性
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function addProperty(string $key, $value)
    {
        $this->dirtyLog->properties->put($key, $value);

        return $this;
    }

    /**
     * 保存描述信息
     *
     * @param string $template
     * @return $this
     */
    public function setTemplate(string $template)
    {
        $this->dirtyLog->template = $template;

        return $this;
    }

    /**
     * @return DirtyLogInterface
     */
    public function initDirtyLog(): DirtyLogInterface
    {
        $this->dirtyLog = new DirtyLog();
        $this->dirtyLog->properties = collect();
        if ($user = auth()->user()) {
            $this->by($user);
        }
        $this->subject = null;

        return $this->dirtyLog;
    }
}
