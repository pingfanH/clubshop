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

namespace app\api\model;

use app\api\model\{Goods as GoodsModel, OrderRefund as OrderRefundModel, Setting as SettingModel};
use app\api\service\{User as UserService, order\source\Factory as OrderSourceFactory};
use app\common\model\Order as OrderModel;
use app\common\service\{Order as OrderService, order\Complete as OrderCompleteService};
use app\common\enum\{
    Setting as SettingEnum,
    payment\Method as PaymentMethodEnum,
    order\PayStatus as PayStatusEnum,
    order\OrderStatus as OrderStatusEnum,
    order\DeliveryType as DeliveryTypeEnum,
    order\ReceiptStatus as ReceiptStatusEnum,
    order\DeliveryStatus as DeliveryStatusEnum
};
use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 订单模型
 * Class Order
 * @package app\api\model
 */
class Order extends OrderModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'merchant_remark',
        'transaction_id',
        'order_source_data',
        'is_settled',
        'is_delete',
        'store_id',
        'update_time'
    ];

    // 信息提示
    private string $message = '';

    /**
     * 立即购买：获取订单商品列表
     * @param int $goodsId 商品ID
     * @param string $goodsSkuId 商品SKU
     * @param int $goodsNum 购买数量
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderGoodsListByNow(int $goodsId, string $goodsSkuId, int $goodsNum)
    {
        // 获取商品列表
        $model = new GoodsModel;
        $goodsList = $model->setEnableGradeMoney(false)->getListByIdsFromApi([$goodsId]);
        if ($goodsList->isEmpty()) {
            throwError('未找到商品信息');
        }
        // 隐藏冗余的属性
        $goodsList->hidden(GoodsModel::getHidden(['content', 'goods_images', 'images']));
        foreach ($goodsList as &$item) {
            // 商品sku信息
            $item['skuInfo'] = GoodsModel::getSkuInfo($item, $goodsSkuId, false);
            // 商品封面 (优先sku封面)
            $item['goods_image'] = $item['skuInfo']['goods_image'] ?: $item['goods_image'];
            // 商品单价
            $item['goods_price'] = $item['skuInfo']['goods_price'];
            // 商品购买数量
            $item['total_num'] = $goodsNum;
            // 商品SKU索引
            $item['goods_sku_id'] = $item['skuInfo']['goods_sku_id'];
            // 商品购买总金额
            $item['total_price'] = helper::bcmul($item['goods_price'], $goodsNum);
        }
        return $goodsList;
    }

    /**
     * 获取用户订单列表
     * @param string $dataType 订单类型 (all全部 payment待付款 deliver待发货 received待收货 comment待评价)
     * @return \think\Paginator
     * @throws BaseException
     * @throws \think\db\exception\DbException
     */
    public function getList(string $dataType = 'all'): \think\Paginator
    {
        // 设置订单类型条件
        $dataTypeFilter = $this->getFilterDataType($dataType);
        // 当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 查询列表数据
        return $this->with(['goods.image', 'trade'])
            ->where($dataTypeFilter)
            ->where('user_id', '=', $userId)
            ->where('is_delete', '=', 0)
            ->order(['create_time' => 'desc'])
            ->paginate(15);
    }

    /**
     * 取消订单
     * @return bool|mixed
     */
    public function cancel()
    {
        // 判断订单是否允许取消
        $orderSource = OrderSourceFactory::getFactory($this['order_source']);
        if (!$orderSource->checkOrderByCancel($this)) {
            $this->error = $orderSource->getError();
            return false;
        }
        // 订单是否已支付
        $isPay = $this['pay_status'] == PayStatusEnum::SUCCESS;
        // 提示信息
        $this->message = $isPay ? '订单已申请取消，需等待后台审核' : '订单已取消成功';
        // 订单取消事件
        return $this->transaction(function () use ($isPay) {
            // 订单取消事件
            !$isPay && OrderService::cancelEvent($this, false);
            // 更新订单状态: 已付款的订单设置为"待取消", 等待后台审核
            return $this->save(['order_status' => $isPay ? OrderStatusEnum::APPLY_CANCEL : OrderStatusEnum::CANCELLED]);
        });
    }

    /**
     * 确认收货
     * @return bool|mixed
     */
    public function receipt()
    {
        // 验证订单是否合法
        // 条件1: 订单必须已发货
        // 条件2: 订单必须未收货
        if (
            $this['delivery_status'] != DeliveryStatusEnum::DELIVERED
            || $this['receipt_status'] != ReceiptStatusEnum::NOT_RECEIVED
        ) {
            $this->error = '该订单不合法';
            return false;
        }
        return $this->transaction(function () {
            // 更新订单状态
            $status = $this->save([
                'receipt_status' => ReceiptStatusEnum::RECEIVED,
                'receipt_time' => time(),
                'order_status' => OrderStatusEnum::COMPLETED
            ]);
            // 执行订单完成后的操作
            $OrderCompleteService = new OrderCompleteService();
            $OrderCompleteService->complete([$this], static::$storeId);
            return $status;
        });
    }

    /**
     * 获取当前用户订单数量
     * @param string $dataType 订单类型 (all全部 payment待付款 deliver待发货 received待收货 comment待评价)
     * @return int
     * @throws BaseException
     */
    public function getCount(string $dataType = 'all'): int
    {
        // 设置订单类型条件
        $dataTypeFilter = $this->getFilterDataType($dataType);
        // 当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 查询数据
        return $this->where('user_id', '=', $userId)
            ->where('order_status', '<>', 20)
            ->where($dataTypeFilter)
            ->where('is_delete', '=', 0)
            ->count();
    }

    /**
     * 设置订单类型条件
     * @param string $dataType
     * @return array
     */
    private function getFilterDataType(string $dataType): array
    {
        // 筛选条件
        $filter = [];
        // 订单数据类型
        switch ($dataType) {
            case 'all':
                break;
            case 'payment':
                $filter[] = ['pay_status', '=', PayStatusEnum::PENDING];
                $filter[] = ['order_status', '=', OrderStatusEnum::NORMAL];
                break;
            case 'delivery':
                $filter = [
                    ['pay_status', '=', PayStatusEnum::SUCCESS],
                    ['delivery_status', '<>', DeliveryStatusEnum::DELIVERED],
                    ['order_status', 'in', [OrderStatusEnum::NORMAL, OrderStatusEnum::APPLY_CANCEL]]
                ];
                break;
            case 'received':
                $filter = [
                    ['pay_status', '=', PayStatusEnum::SUCCESS],
                    ['delivery_status', '=', DeliveryStatusEnum::DELIVERED],
                    ['receipt_status', '=', ReceiptStatusEnum::NOT_RECEIVED],
                    ['order_status', '=', OrderStatusEnum::NORMAL]
                ];
                break;
            case 'comment':
                $filter = [
                    ['is_comment', '=', 0],
                    ['order_status', '=', OrderStatusEnum::COMPLETED]
                ];
                break;
        }
        return $filter;
    }

    /**
     * 获取用户订单详情(含关联数据)
     * @param int $orderId 订单ID
     * @param bool $onlyCurrentUser 只查询当前登录用户的记录
     * @return Order|array|null
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getUserOrderDetail(int $orderId, bool $onlyCurrentUser = true)
    {
        // 查询订单记录
        $with = ['goods' => ['image', 'refund'], 'trade', 'delivery.express', 'address'];
        $order = static::getDetail($orderId, $with, $onlyCurrentUser);
        // 该订单是否允许申请售后
        $order['isAllowRefund'] = static::isAllowRefund($order);
        return $order;
    }

    /**
     * 获取未支付的订单详情(用于订单支付)
     * @param int $orderId 订单ID
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getUnpaidOrderDetail(int $orderId): array
    {
        // 获取订单详情
        $orderInfo = static::getDetail($orderId);
        // 验证订单状态
        if ($orderInfo['order_status'] != OrderStatusEnum::NORMAL) {
            throwError('当前订单状态不允许支付');
        }
        // 未支付订单的过期时间
        $orderCloseTime = SettingModel::getOrderCloseTime() * 60 * 60;
        // 订单超时截止时间
        $expirationTime = $orderInfo->getData('create_time') + $orderCloseTime;
        if ($orderCloseTime > 0 && $expirationTime <= time()) {
            throwError('当前订单支付已超时，请重新下单');
        }
        // 仅返回需要的数据
        return [
            'orderId' => $orderInfo['order_id'],
            'order_no' => $orderInfo['order_no'],
            'pay_price' => $orderInfo['pay_price'],
            'pay_status' => $orderInfo['pay_status'],
            'order_status' => $orderInfo['order_status'],
            'create_time' => $orderInfo['create_time'],
            'showExpiration' => $orderCloseTime > 0,
            'expirationTime' => \format_time($expirationTime),
        ];
    }

    /**
     * 获取用户订单详情(仅订单记录)
     * @param int $orderId
     * @param array $with
     * @param bool $onlyCurrentUser 只查询当前登录用户的记录
     * @return Order|array|null
     * @throws BaseException
     */
    public static function getDetail(int $orderId, array $with = [], bool $onlyCurrentUser = true)
    {
        // 查询条件
        $where = ['order_id' => $orderId];
        $onlyCurrentUser && $where['user_id'] = UserService::getCurrentLoginUserId();
        // 查询订单记录
        $order = static::detail($where, $with);
        empty($order) && throwError('订单不存在');
        return $order;
    }

    /**
     * 获取当前用户待处理的订单数量
     * @return array
     * @throws BaseException
     */
    public function getTodoCounts(): array
    {
        return [
            'payment' => $this->getCount('payment'),    // 待付款的订单
            'delivery' => $this->getCount('delivery'),  // 待发货的订单
            'received' => $this->getCount('received'),  // 待收货的订单
            'refund' => OrderRefundModel::getCountByUnderway(),  // 进行中的售后单
        ];
    }

    // 返回提示信息
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 当前订单是否允许申请售后
     * @param Order $order
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function isAllowRefund(self $order): bool
    {
        // 不能是未发货的订单
        if ($order['delivery_status'] == DeliveryStatusEnum::NOT_DELIVERED) {
            return false;
        }
        // 允许申请售后期限(天)
        $refundDays = SettingModel::getItem(SettingEnum::TRADE)['order']['refund_days'];
        // 不允许售后
        if ($refundDays == 0) {
            return false;
        }
        // 当前时间超出允许申请售后期限
        if (
            $order['receipt_status'] == ReceiptStatusEnum::RECEIVED
            && time() > ($order->getData('receipt_time') + ((int)$refundDays * 86400))
        ) {
            return false;
        }
        return true;
    }
}
