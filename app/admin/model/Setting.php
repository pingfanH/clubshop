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

namespace app\admin\model;

use app\common\library\helper;
use app\common\model\store\Setting as SettingModel;

/**
 * 商城设置模型
 * Class Setting
 * @package app\admin\model
 */
class Setting extends SettingModel
{
    /**
     * 新增默认配置
     * @param int $storeId
     * @return bool
     */
    public function insertDefault(int $storeId): bool
    {
        // 添加商城默认设置记录
        $data = [];
        foreach ($this->defaultData() as $key => $item) {
            $item['values'] = helper::jsonEncode($item['values']);
            $item['store_id'] = $storeId;
            $data[] = $item;
        }
        return $this->addAll($data) !== false;
    }
}
