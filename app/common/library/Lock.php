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

namespace app\common\library;

/**
 * 文件阻塞锁
 * 用于并发情况下保持数据的原子性, 仅适用于轻量并发场景，如果是高并发场景请使用redis
 * Class Lock
 * @package app\common\library
 */
class Lock
{
    // 文件锁资源树
    static $resource = [];

    /**
     * 加锁
     * @param string $uniqueId
     * @return bool
     */
    public static function lockUp(string $uniqueId): bool
    {
        clearstatcache();
        static::$resource[$uniqueId] = fopen(static::getFilePath($uniqueId), 'w+');
        return flock(static::$resource[$uniqueId], LOCK_EX);
    }

    /**
     * 解锁
     * @param string $uniqueId
     * @return bool
     */
    public static function unLock(string $uniqueId): bool
    {
        clearstatcache();
        if (!isset(static::$resource[$uniqueId])) return false;
        flock(static::$resource[$uniqueId], LOCK_UN);
        fclose(static::$resource[$uniqueId]);
        return static::deleteFile($uniqueId);
    }

    /**
     * 获取锁文件的路径
     * @param string $uniqueId
     * @return string
     */
    private static function getFilePath(string $uniqueId): string
    {
        clearstatcache();
        $dirPath = runtime_root_path() . 'lock/';
        !is_dir($dirPath) && mkdir($dirPath, 0755, true);
        return $dirPath . md5($uniqueId) . '.lock';
    }

    /**
     * 删除锁文件
     * @param string $uniqueId
     * @return bool
     */
    private static function deleteFile(string $uniqueId): bool
    {
        clearstatcache();
        $filePath = static::getFilePath($uniqueId);
        return file_exists($filePath) && unlink($filePath);
    }
}
