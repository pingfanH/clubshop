<?php
// +----------------------------------------------------------------------
// | 萤火商城系统 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2025 https://www.yiovo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author: 萤火科技 <admin@yiovo.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace cores;

use think\facade\Cache;

/**
 * 定时任务监听器
 * Class BaseTimer
 * @package app\timer\controller
 */
class BaseTimer
{
    // 当前任务唯一标识 (由子类定义)
    protected string $taskKey = '';

    // 任务执行间隔时长 (单位:秒)
    protected int $taskExpire = 60 * 30;

    // 当前商城ID
    protected int $storeId;

    /**
     * 定时执行任务 (支持自定义时间间隔)
     * @param int $storeId 商城ID
     * @param string $key 任务标识
     * @param int|null $expire 定时间隔 (单位秒)
     * @param callable $callback 回调方法
     * @return mixed
     */
    protected final function setInterval(int $storeId, string $key, int $expire, callable $callback)
    {
        if (!$this->hasTaskId($storeId, $key)) {
            $this->setTaskId($storeId, $key, $expire);
            return call_user_func($callback);
        }
        return null;
    }

    /**
     * 获取任务ID
     * @param int $storeId 商城ID
     * @param string $key 任务标识
     * @return bool
     */
    protected final function hasTaskId(int $storeId, string $key): bool
    {
        return Cache::has("Listener:$storeId:$key");
    }

    /**
     * 设置任务ID
     * 用于实现定时任务的间隔时间, 如果任务ID存在并未过期, 则不执行任务
     * @param int $storeId 商城ID
     * @param string $key 任务标识
     * @param int $expire 定时间隔 (单位:秒)
     * @return bool
     */
    protected final function setTaskId(int $storeId, string $key, int $expire = 60): bool
    {
        return Cache::set("Listener:$storeId:$key", true, $expire);
    }

    /**
     * 任务处理 (cores)
     * @param array $param 参数 (storeId)
     * @param callable $callback 回调方法
     * @return mixed|null
     */
    protected final function handleTask(array $param, callable $callback)
    {
        ['storeId' => $this->storeId] = $param;
        return $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () use ($callback) {
            echo $this->taskKey . PHP_EOL;
            return call_user_func($callback);
        });
    }
}