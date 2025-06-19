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

namespace app\common\listener\order;

use app\common\model\Order as OrderModel;
use app\common\service\Message as MessageService;
use app\common\service\order\Printer as PrinterService;
use app\common\enum\OrderType as OrderTypeEnum;
use app\common\enum\order\OrderScene as OrderSceneEnum;
use app\common\enum\order\OrderSource as OrderSourceEnum;
use cores\exception\BaseException;

/**
 * 订单支付成功后扩展类
 * Class PaySuccess
 * @package app\api\behavior\order
 */
class PaySuccess
{
    // 订单信息
    private ?OrderModel $order;

    // 订单类型
    private int $orderType;

    // 当前商城ID
    private int $storeId;

    /**
     * 订单来源回调业务映射类
     * @var array
     */
    protected array $sourceCallbackClass = [
        OrderSourceEnum::MAIN => \app\common\service\main\order\PaySuccess::class,
    ];

    /**
     * 执行句柄
     * @param array $params
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle(array $params): bool
    {
        // 解构赋值: 订单模型、订单类型
        ['order' => $order, 'orderType' => $orderType] = $params;
        // 设置当前类的属性
        $this->setAttribute($order, $orderType);
        // 订单公共事件
        $this->onCommonEvent();
        // 订单来源回调业务
        $this->onSourceCallback();
        return true;
    }

    /**
     * 设置当前类的属性
     * @param $order
     * @param int $orderType
     */
    private function setAttribute($order, int $orderType = OrderTypeEnum::ORDER)
    {
        $this->order = $order;
        $this->storeId = $this->order['store_id'];
        $this->orderType = $orderType;
    }

    /**
     * 订单公共业务
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function onCommonEvent()
    {
        // 发送消息通知
        MessageService::send('order.payment', [
            'order' => $this->order,
            'order_type' => $this->orderType,
        ], $this->storeId);
        // 小票打印
        (new PrinterService)->printTicket($this->order, OrderSceneEnum::PAYMENT);
    }

    /**
     * 订单来源回调业务
     */
    private function onSourceCallback(): void
    {
        if (!isset($this->order['order_source'])) {
            return;
        }
        if (!isset($this->sourceCallbackClass[$this->order['order_source']])) {
            return;
        }
        $class = $this->sourceCallbackClass[$this->order['order_source']];
        !empty($class) && (new $class)->onPaySuccess($this->order);
    }
}