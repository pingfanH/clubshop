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

namespace app\store\model\h5;

use think\facade\Cache;
use app\common\model\h5\Setting as SettingModel;

/**
 * H5设置模型
 * Class Setting
 * @package app\store\model\h5
 */
class Setting extends SettingModel
{
    /**
     * 设置项描述
     * @var array
     */
    private array $describe = [
        'basic' => '基础设置',
    ];

    /**
     * 更新系统设置
     * @param string $key
     * @param array $values
     * @return bool
     */
    public function edit(string $key, array $values): bool
    {
        $model = self::detail($key) ?: $this;
        // 删除系统设置缓存
        Cache::delete('h5_setting_' . self::$storeId);
        return $model->save([
                'key' => $key,
                'describe' => $this->describe[$key],
                'values' => $values,
                'update_time' => time(),
                'store_id' => self::$storeId,
            ]) !== false;
    }
}