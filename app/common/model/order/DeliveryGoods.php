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
use think\model\relation\BelongsTo;

/**
 * 订单发货单商品模型
 * Class DeliveryGoods
 * @package app\common\model\order
 */
class DeliveryGoods extends BaseModel
{
    // 定义表名
    protected $name = 'order_delivery_goods';

    // 定义主键
    protected $pk = 'id';

    protected $updateTime = false;

    /**
     * 关联订单商品记录
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\OrderGoods");
    }
}