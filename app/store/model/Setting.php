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

namespace app\store\model;

use think\facade\Cache;
use app\common\model\store\Setting as SettingModel;
use app\common\enum\Setting as SettingEnum;

/**
 * 系统设置模型
 * Class Setting
 * @package app\store\model
 */
class Setting extends SettingModel
{
    /**
     * 更新系统设置
     * @param string $key
     * @param array $values
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit(string $key, array $values): bool
    {
        // 设置项详情记录
        $model = self::detail($key) ?: $this;
        // 数据验证
        if (!$this->validValues($key, $values)) {
            return false;
        }
        // 删除系统设置缓存
        Cache::delete('setting_' . self::$storeId);
        // 更新记录
        return $model->save([
                'key' => $key,
                'describe' => SettingEnum::data()[$key]['describe'],
                'values' => $values,
                'update_time' => time(),
                'store_id' => self::$storeId,
            ]) !== false;
    }

    /**
     * 数据验证
     * @param string $key
     * @param array $values
     * @return bool
     */
    private function validValues(string $key, array $values): bool
    {
        $callback = [
            'store' => function ($values) {
                return $this->validStore($values);
            },
            'printer' => function ($values) {
                return $this->validPrinter($values);
            },
        ];
        // 验证商城设置
        return isset($callback[$key]) ? $callback[$key]($values) : true;
    }

    /**
     * 验证商城设置
     * @param array $values
     * @return bool
     */
    private function validStore(array $values): bool
    {
        if (empty($values['delivery_type'])) {
            $this->error = '配送方式至少选择一个';
            return false;
        }
        return true;
    }

    /**
     * 验证小票打印机设置
     * @param array $values
     * @return bool
     */
    private function validPrinter(array $values): bool
    {
        if (!$values['is_open']) {
            return true;
        }
        if (!$values['printer_id']) {
            $this->error = '请选择订单打印机';
            return false;
        }
        if (empty($values['order_status'])) {
            $this->error = '请选择订单打印方式';
            return false;
        }
        return true;
    }
}
