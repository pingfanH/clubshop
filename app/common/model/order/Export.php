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

namespace app\common\model\order;

use cores\BaseModel;

/**
 * 订单导出Excel模型
 * Class Export
 * @package app\common\model\order
 */
class Export extends BaseModel
{
    // 定义表名
    protected $name = 'order_export';

    // 定义主键
    protected $pk = 'id';

    /**
     * 获取器：下单时间(开始)
     * @param $value
     * @return string
     */
    public function getStartTimeAttr($value): string
    {
        return \format_time($value, false);
    }

    /**
     * 获取器：下单时间(结束)
     * @param $value
     * @return string
     */
    public function getEndTimeAttr($value): string
    {
        return \format_time($value, false);
    }

    /**
     * 导出记录详情
     * @param int $id
     * @return static|array|null
     */
    public static function detail(int $id)
    {
        return self::get($id);
    }
}