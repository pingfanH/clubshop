<?php
/**
 * 事件定义文件
 * 注：此处定义的定时任务, 执行句柄在 app/timer/controller/Store.php
 */
return [
    'bind' => [],
    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],

        // 订单支付成功事件 (处理订单来源相关业务)
        'OrderPaySuccess' => [\app\common\listener\order\PaySuccess::class],

        // 定时任务：商城模块
        'StoreTask' => [\app\timer\controller\Store::class],
        // 定时任务：商城订单
        'Order' => [\app\timer\controller\Order::class],
        // 定时任务：用户优惠券
        'UserCoupon' => [\app\timer\controller\UserCoupon::class],
        // 定时任务：会员等级
        'UserGrade' => [\app\timer\controller\UserGrade::class]
    ],
];
