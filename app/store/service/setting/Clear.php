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

namespace app\store\service\setting;

use think\facade\Cache as CacheDrive;
use app\common\service\BaseService;

/**
 * 清理后台缓存服务
 * Class Clear
 * @package app\store\service\setting
 */
class Clear extends BaseService
{
    const ITEM_TYPE_CACHE = 'cache';
    const ITEM_TYPE_FILE = 'file';

    /**
     * 数据缓存项目(只显示key和name)
     * @return array
     */
    public function items(): array
    {
        $data = $this->getItems();
        $items = [];
        foreach ($data as $key => $item) {
            $items[] = [
                'key' => $key,
                'name' => $item['name']
            ];
        }
        return $items;
    }

    /**
     * 数据缓存项目
     * @return array
     */
    private function getItems(): array
    {
        $storeId = $this->getStoreId();
        return [
            'category' => [
                'type' => self::ITEM_TYPE_CACHE,
                'key' => "category_{$storeId}",
                'name' => '商品分类'
            ],
            'setting' => [
                'type' => self::ITEM_TYPE_CACHE,
                'key' => "setting_{$storeId}",
                'name' => '商城设置'
            ],
            'wxapp' => [
                'type' => self::ITEM_TYPE_CACHE,
                'key' => "wxapp_setting_{$storeId}",
                'name' => '微信小程序设置',
            ],
            'h5' => [
                'type' => self::ITEM_TYPE_CACHE,
                'key' => "wxapp_h5_{$storeId}",
                'name' => 'H5端设置',
            ],
            'payment' => [
                'type' => self::ITEM_TYPE_CACHE,
                'key' => "payment_{$storeId}",
                'name' => '支付设置'
            ],
            'temp' => [
                'type' => self::ITEM_TYPE_FILE,
                'name' => '临时图片',
                'dirPath' => [
                    'web' => web_path() . "temp/{$storeId}/",
                    'runtime' => runtime_root_path() . "/image/{$storeId}/",
                ]
            ],
            'local' => [
                'type' => self::ITEM_TYPE_FILE,
                'name' => '临时文件',
                'dirPath' => [
                    'batch-delivery' => runtime_root_path() . "local/batch-delivery/{$storeId}/",
                    'batch-goods' => runtime_root_path() . "local/batch-goods/{$storeId}/",
                ]
            ],
        ];
    }

    /**
     * 删除缓存
     * @param array $keys
     */
    public function rmCache(array $keys)
    {
        $cacheList = $this->getItems();
        $keys = \array_intersect(\array_keys($cacheList), $keys);
        foreach ($keys as $key) {
            $item = $cacheList[$key];
            if ($item['type'] === self::ITEM_TYPE_CACHE) {
                CacheDrive::has($item['key']) && CacheDrive::delete($item['key']);
            } elseif ($item['type'] === 'file') {
                $this->deltree($item['dirPath']);
            }
        }
    }

    /**
     * 删除目录下所有文件
     * @param string|array $dirPath
     */
    private function deltree($dirPath)
    {
        if (!is_array($dirPath)) {
            $this->deleteFolder($dirPath);
            return;
        }
        foreach ($dirPath as $path) {
            $this->deleteFolder($path);
        }
    }

    /**
     * 递归删除指定目录下所有文件
     * @param string $path
     * @return void
     */
    private function deleteFolder(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        // 扫描一个文件夹内的所有文件夹和文件
        foreach (scandir($path) as $val) {
            // 排除目录中的.和..
            if (!in_array($val, ['.', '..'])) {
                // 如果是目录则递归子目录，继续操作
                if (is_dir("{$path}{$val}")) {
                    // 子目录中操作删除文件夹和文件
                    $this->deleteFolder("{$path}{$val}/");
                    // 目录清空后删除空文件夹
                    rmdir("{$path}{$val}/");
                } else {
                    // 如果是文件直接删除
                    unlink("{$path}{$val}");
                }
            }
        }
    }
}