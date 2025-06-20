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

use app\common\model\Order as OrderModel;
use app\common\service\Order as OrderService;
use app\common\service\order\Refund as RefundService;
use app\common\service\order\Printer as PrinterService;
use app\common\service\order\PaySuccess as OrderPaySuccesService;
use app\common\enum\order\{
    DataType as DataTypeEnum,
    PayStatus as PayStatusEnum,
    OrderStatus as OrderStatusEnum,
    ReceiptStatus as ReceiptStatusEnum,
    DeliveryStatus as DeliveryStatusEnum
};
use app\common\enum\payment\Method as PaymentMethod;
use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 订单管理
 * Class Order
 * @package app\store\model
 */
class Order extends OrderModel
{
    /**
     * 订单详情页数据
     * @param int $orderId
     * @return Order|array|null
     */
    public function getDetail(int $orderId)
    {
        return static::detail($orderId, [
            'user', 'address', 'express',
            'goods.image',
            'delivery' => ['goods', 'express'],
            'trade',
        ]);
    }

    /**
     * 订单列表
     * @param array $param
     * @return mixed
     */
    public function getList(array $param = [])
    {
        // 检索查询条件
        $filter = $this->getQueryFilter($param);
        // 设置订单类型条件
        $dataTypeFilter = $this->getFilterDataType($param['dataType']);
        // 获取数据列表
        return $this->with(['goods.image', 'user.avatar', 'address', 'trade'])
            ->alias('order')
            ->field('order.*')
            ->leftJoin('user', 'user.user_id = order.user_id')
            ->leftJoin('order_address address', 'address.order_id = order.order_id')
            ->leftJoin('payment_trade trade', 'trade.trade_id = order.trade_id')
            ->where($dataTypeFilter)
            ->where($filter)
            ->where('order.is_delete', '=', 0)
            ->order(['order.create_time' => 'desc'])
            ->paginate(10);
    }

    /**
     * 订单列表(全部)
     * @param array $param
     * @return iterable|\think\model\Collection|\think\Paginator
     */
    public function getListAll(array $param = [])
    {
        // 检索查询条件
        $queryFilter = $this->getQueryFilter($param);
        // 设置订单类型条件
        $dataTypeFilter = $this->getFilterDataType($param['dataType']);
        // 获取数据列表
        return $this->with(['goods.image', 'address', 'user.avatar', 'express', 'trade'])
            ->alias('order')
            ->field('order.*')
            ->join('user', 'user.user_id = order.user_id')
            ->where($dataTypeFilter)
            ->where($queryFilter)
            ->where('order.is_delete', '=', 0)
            ->order(['order.create_time' => 'desc'])
            ->select();
    }

    /**
     * 设置检索查询条件
     * @param array $param
     * @return array
     */
    private function getQueryFilter(array $param): array
    {
        // 默认参数
        $params = $this->setQueryDefaultValue($param, [
            'searchType' => '',     // 关键词类型 (10订单号 20会员昵称 30会员ID 40收货人姓名 50收货人电话 60第三方支付订单号)
            'searchValue' => '',    // 关键词内容
            'orderSource' => -1,    // 订单来源
            'payMethod' => '',      // 支付方式
            'deliveryType' => -1,   // 配送方式
            'betweenTime' => [],    // 起止时间
            'userId' => 0,          // 会员ID
        ]);
        // 检索查询条件
        $filter = [];
        // 关键词
        if (!empty($params['searchValue'])) {
            $searchWhere = [
                10 => ['order.order_no', 'like', "%{$params['searchValue']}%"],
                20 => ['user.nick_name', 'like', "%{$params['searchValue']}%"],
                30 => ['order.user_id', '=', (int)$params['searchValue']],
                40 => ['address.name', 'like', "%{$params['searchValue']}%"],
                50 => ['address.phone', 'like', "%{$params['searchValue']}%"],
                60 => ['trade.out_trade_no', 'like', "%{$params['searchValue']}%"],
            ];
            \array_key_exists($params['searchType'], $searchWhere) && $filter[] = $searchWhere[$params['searchType']];
        }
        // 起止时间
        if (!empty($params['betweenTime'])) {
            $times = between_time($params['betweenTime']);
            $filter[] = ['order.create_time', '>=', $times['start_time']];
            $filter[] = ['order.create_time', '<', $times['end_time'] + 86400];
        }
        // 订单来源
        $params['orderSource'] > -1 && $filter[] = ['order.order_source', '=', (int)$params['orderSource']];
        // 支付方式
        !empty($params['payMethod']) && $filter[] = ['order.pay_method', '=', $params['payMethod']];
        // 配送方式
        $params['deliveryType'] > -1 && $filter[] = ['order.delivery_type', '=', (int)$params['deliveryType']];
        // 会员ID
        $params['userId'] > 0 && $filter[] = ['order.user_id', '=', (int)$params['userId']];
        return $filter;
    }

    /**
     * 设置订单类型条件
     * @param string $dataType
     * @return array
     */
    private function getFilterDataType(string $dataType): array
    {
        // 数据类型
        $filter = [];
        switch ($dataType) {
            case DataTypeEnum::ALL:
                break;
            case DataTypeEnum::PAY:
                $filter[] = ['pay_status', '=', PayStatusEnum::PENDING];
                $filter[] = ['order_status', '=', OrderStatusEnum::NORMAL];
                break;
            case DataTypeEnum::DELIVERY:
                $filter = [
                    ['pay_status', '=', PayStatusEnum::SUCCESS],
                    ['delivery_status', '<>', DeliveryStatusEnum::DELIVERED],
                    ['order_status', 'in', [OrderStatusEnum::NORMAL, OrderStatusEnum::APPLY_CANCEL]]
                ];
                break;
            case DataTypeEnum::RECEIPT:
                $filter = [
                    ['pay_status', '=', PayStatusEnum::SUCCESS],
                    ['delivery_status', '=', DeliveryStatusEnum::DELIVERED],
                    ['receipt_status', '=', ReceiptStatusEnum::NOT_RECEIVED]
                ];
                break;
            case DataTypeEnum::COMPLETE:
                $filter[] = ['order_status', '=', OrderStatusEnum::COMPLETED];
                break;
            case DataTypeEnum::APPLY_CANCEL:
                $filter[] = ['order_status', '=', OrderStatusEnum::APPLY_CANCEL];
                break;
            case DataTypeEnum::CANCEL:
                $filter[] = ['order_status', '=', OrderStatusEnum::CANCELLED];
                break;
        }
        return $filter;
    }

    /**
     * 修改订单价格
     * @param array $data
     * @return bool
     */
    public function updatePrice(array $data): bool
    {
        if ($this['pay_status'] != PayStatusEnum::PENDING) {
            $this->error = '该订单不合法';
            return false;
        }
        // 实际付款金额
        $payPrice = helper::bcadd($data['order_price'], $data['express_price']);
        if ($payPrice <= 0) {
            $this->error = '订单实付款价格不能为0.00元';
            return false;
        }
        // 改价的金额差价
        $updatePrice = helper::bcsub($data['order_price'], $this['order_price']);
        // 更新订单记录
        return $this->save([
                'order_price' => $data['order_price'],
                'pay_price' => $payPrice,
                'update_price' => $updatePrice,
                'express_price' => $data['express_price']
            ]) !== false;
    }

    /**
     * 修改商家备注
     * @param array $data
     * @return bool
     */
    public function updateRemark(array $data): bool
    {
        return $this->save(['merchant_remark' => $data['content'] ?? '']);
    }

    /**
     * 小票打印
     * @param array $data
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printer(array $data): bool
    {
        // 实例化打印机驱动
        $Printer = new PrinterService;
        // 手动打印小票
        $status = $Printer->printEvent($this, $data['printerId']);
        if ($status === false) {
            $this->error = $Printer->getError();
        }
        return $status;
    }

    /**
     * 审核：用户取消订单
     * @param array $data
     * @return bool|mixed
     */
    public function confirmCancel(array $data)
    {
        // 判断订单是否有效
        if (
            $this['pay_status'] != PayStatusEnum::SUCCESS
            || $this['order_status'] != OrderStatusEnum::APPLY_CANCEL
        ) {
            $this->error = '该订单不合法';
            return false;
        }
        // 订单取消事件
        return $this->transaction(function () use ($data) {
            if ($data['status']) {
                // 执行退款操作
                if (!(new RefundService)->handle($this)) {
                    throwError('执行订单退款失败');
                }
                // 订单取消事件
                OrderService::cancelEvent($this);
            }
            // 更新订单状态
            return $this->save([
                'order_status' => $data['status'] ? OrderStatusEnum::CANCELLED : OrderStatusEnum::NORMAL
            ]);
        });
    }

    /**
     * 将订单记录设置为已删除
     * @return bool
     */
    public function setDelete(): bool
    {
        return $this->save(['is_delete' => 1]);
    }

    /**
     * 获取已付款订单总数 (可指定某天)
     * @param null $startDate
     * @param null $endDate
     * @return int
     */
    public function getPayOrderTotal($startDate = null, $endDate = null): int
    {
        $filter = [
            ['pay_status', '=', PayStatusEnum::SUCCESS],
            ['order_status', '<>', OrderStatusEnum::CANCELLED]
        ];
        if (!is_null($startDate) && !is_null($endDate)) {
            $filter[] = ['pay_time', '>=', strtotime($startDate)];
            $filter[] = ['pay_time', '<', strtotime($endDate) + 86400];
        }
        return $this->getOrderTotal($filter);
    }

    /**
     * 获取未发货订单数量
     * @return int
     */
    public function getNotDeliveredOrderTotal(): int
    {
        $filter = [
            ['pay_status', '=', PayStatusEnum::SUCCESS],
            ['delivery_status', '<>', DeliveryStatusEnum::DELIVERED],
            ['order_status', 'in', [OrderStatusEnum::NORMAL, OrderStatusEnum::APPLY_CANCEL]]
        ];
        return $this->getOrderTotal($filter);
    }

    /**
     * 获取未付款订单数量
     * @return int
     */
    public function getNotPayOrderTotal(): int
    {
        $filter = [
            ['pay_status', '=', PayStatusEnum::PENDING],
            ['order_status', '=', OrderStatusEnum::NORMAL]
        ];
        return $this->getOrderTotal($filter);
    }

    /**
     * 获取订单总数
     * @param array $filter
     * @return int
     */
    private function getOrderTotal(array $filter = []): int
    {
        // 获取订单总数量
        return $this->where($filter)
            ->where('is_delete', '=', 0)
            ->count();
    }

    /**
     * 获取某天的总销售额
     * @param null $startDate
     * @param null $endDate
     * @return float
     */
    public function getOrderTotalPrice($startDate = null, $endDate = null): float
    {
        // 查询对象
        $query = $this->getNewQuery();
        // 设置查询条件
        if (!is_null($startDate) && !is_null($endDate)) {
            $query->where('pay_time', '>=', strtotime($startDate))
                ->where('pay_time', '<', strtotime($endDate) + 86400);
        }
        // 总销售额
        return $query->where('pay_status', '=', PayStatusEnum::SUCCESS)
            ->where('order_status', '<>', OrderStatusEnum::CANCELLED)
            ->where('is_delete', '=', 0)
            ->sum('pay_price');
    }

    /**
     * 获取某天的下单用户数
     * @param string $day
     * @return float|int
     */
    public function getPayOrderUserTotal(string $day)
    {
        $startTime = strtotime($day);
        return $this->field('user_id')
            ->where('pay_time', '>=', $startTime)
            ->where('pay_time', '<', $startTime + 86400)
            ->where('pay_status', '=', PayStatusEnum::SUCCESS)
            ->where('is_delete', '=', '0')
            ->group('user_id')
            ->count();
    }

    /**
     * 根据订单号获取ID集
     * @param array $orderNoArr
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getOrderIds(array $orderNoArr): array
    {
        $list = (new static)->where('order_no', 'in', $orderNoArr)->select();
        $data = [];
        foreach ($list as $item) {
            $data[$item['order_no']] = $item['order_id'];
        }
        return $data;
    }
}
