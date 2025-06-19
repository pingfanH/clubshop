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

namespace app\timer\controller;

use cores\BaseTimer;
use think\facade\Event;
use app\timer\model\Store as StoreModel;
use app\common\service\system\Process as SystemProcessService;

/**
 * 商城定时任务
 * Class Store
 * @package app\timer\controller
 */
class Store extends BaseTimer
{
    /**
     * 任务处理
     */
    public function handle()
    {
        // 记录定时任务最后执行时间
        SystemProcessService::setLastWorkingTime('timer');
        // 遍历商城列表并执行定时任务
        foreach (StoreModel::getStoreIds() as $storeId) {
            // 定时任务：商城订单
            Event::trigger('Order', ['storeId' => $storeId]);
            // 定时任务：用户优惠券
            Event::trigger('UserCoupon', ['storeId' => $storeId]);
            // 定时任务：会员等级
            Event::trigger('UserGrade', ['storeId' => $storeId]);
        }
    }
}