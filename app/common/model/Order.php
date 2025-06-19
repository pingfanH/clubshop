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

namespace app\common\model;

use cores\BaseModel;
use app\common\service\Order as OrderService;
use app\common\enum\order\PayStatus as PayStatusEnum;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\enum\order\OrderStatus as OrderStatusEnum;
use app\common\enum\order\ReceiptStatus as ReceiptStatusEnum;
use app\common\enum\order\DeliveryStatus as DeliveryStatusEnum;
use app\common\library\helper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasOne;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;

/**
 * 订单模型
 * Class Order
 * @package app\common\model
 */
class Order extends BaseModel
{
    // 定义表名
    protected $name = 'order';

    // 定义表名(外部引用)
    public static string $tableName = 'order';

    // 定义主键
    protected $pk = 'order_id';

    // 定义别名
    protected string $alias = 'order';

    /**
     * 追加字段
     * @var array
     */
    protected $append = [
        'state_text',   // 售后单状态文字描述
    ];

    /**
     * 订单商品列表
     * @return HasMany
     */
    public function goods(): HasMany
    {
        $module = self::getCalledModule();
        return $this->hasMany("app\\{$module}\\model\\OrderGoods")->withoutField('content');
    }

    /**
     * 关联订单发货单
     * @return hasMany
     */
    public function delivery(): HasMany
    {
        $module = self::getCalledModule();
        return $this->hasMany("app\\{$module}\\model\\order\\Delivery");
    }

    /**
     * 关联订单收货地址表
     * @return HasOne
     */
    public function address(): HasOne
    {
        $module = self::getCalledModule();
        return $this->hasOne("app\\{$module}\\model\\OrderAddress");
    }

    /**
     * 关联用户表
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\User");
    }

    /**
     * 关联物流公司表 (仅用于兼容旧物流数据)
     * @return BelongsTo
     */
    public function express(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\Express");
    }

    /**
     * 关联模型：第三方交易记录
     * @return BelongsTo
     */
    public function trade(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\PaymentTrade", 'trade_id', 'trade_id');
    }

    /**
     * 获取器：订单状态文字描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getStateTextAttr($value, $data): string
    {
        // 订单状态
        if ($data['order_status'] != OrderStatusEnum::NORMAL) {
            return OrderStatusEnum::data()[$data['order_status']]['name'];
        }
        // 付款状态
        if ($data['pay_status'] == PayStatusEnum::PENDING) {
            return '待支付';
        }
        // 发货状态
        if ($data['delivery_status'] != DeliveryStatusEnum::DELIVERED) {
            $enum = [DeliveryStatusEnum::NOT_DELIVERED => '待发货', DeliveryStatusEnum::PART_DELIVERED => '部分发货'];
            return $enum[$data['delivery_status']];
        }
        // 收货状态
        if ($data['receipt_status'] == ReceiptStatusEnum::NOT_RECEIVED) {
            return '待收货';
        }
        return $value;
    }

    /**
     * 获取器：订单金额(含优惠折扣)
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderPriceAttr($value, $data): string
    {
        // 兼容旧数据：订单金额
        if ($value == 0) {
            return helper::bcadd(helper::bcsub($data['total_price'], $data['coupon_money']), $data['update_price']);
        }
        return $value;
    }

    /**
     * 获取器：改价金额（差价）
     * @param $value
     * @return array
     */
    public function getUpdatePriceAttr($value): array
    {
        return [
            'symbol' => $value < 0 ? '-' : '+',
            'value' => sprintf('%.2f', abs((float)$value))
        ];
    }

    /**
     * 获取器：付款时间
     * @param $value
     * @return false|string
     */
    public function getPayTimeAttr($value)
    {
        return \format_time($value);
    }

    /**
     * 获取器：发货时间
     * @param $value
     * @return false|string
     */
    public function getDeliveryTimeAttr($value)
    {
        return \format_time($value);
    }

    /**
     * 获取器：收货时间
     * @param $value
     * @return false|string
     */
    public function getReceiptTimeAttr($value)
    {
        return \format_time($value);
    }

    /**
     * 获取器：来源记录的参数
     * @param $json
     * @return array
     */
    public function getOrderSourceDataAttr($json): array
    {
        return $json ? helper::jsonDecode($json) : [];
    }

    /**
     * 修改器：来源记录的参数
     * @param array $data
     * @return string
     */
    public function setOrderSourceDataAttr(array $data): string
    {
        return helper::jsonEncode($data);
    }

    /**
     * 生成订单号
     * @return string
     */
    public function orderNo(): string
    {
        return OrderService::createOrderNo();
    }

    /**
     * 订单详情
     * @param $where
     * @param array $with
     * @return static|array|null
     */
    public static function detail($where, array $with = [])
    {
        is_array($where) ? $filter = $where : $filter['order_id'] = (int)$where;
        return self::get($filter, $with);
    }

    /**
     * 待支付订单详情
     * @param string $orderNo 订单号
     * @return null|static
     */
    public static function getPayDetail(string $orderNo): ?Order
    {
        $where = ['order_no' => $orderNo, 'is_delete' => 0];
        return static::detail($where, ['goods', 'user']);
    }

    /**
     * 批量获取订单列表
     * @param array $orderIds
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getListByIds(array $orderIds, array $with = []): array
    {
        $data = $this->getListByInArray('order_id', $orderIds, $with);
        return helper::arrayColumn2Key($data, 'order_id');
    }

    /**
     * 批量获取订单列表
     * @param $field
     * @param $data
     * @param array $with
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getListByInArray($field, $data, array $with = []): \think\Collection
    {
        return $this->with($with)
            ->where($field, 'in', $data)
            ->where('is_delete', '=', 0)
            ->select();
    }

    /**
     * 根据订单号批量查询
     * @param $orderNos
     * @param array $with
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByOrderNos($orderNos, array $with = []): \think\Collection
    {
        return $this->getListByInArray('order_no', $orderNos, $with);
    }

    /**
     * 批量更新订单
     * @param $orderIds
     * @param $data
     * @return bool|false
     */
    public function onBatchUpdate($orderIds, $data): bool
    {
        return static::updateBase($data, [['order_id', 'in', $orderIds]]);
    }

    /**
     * 更新订单来源记录ID
     * @param int $orderId
     * @param int $soureId
     * @return bool
     */
    public static function updateOrderSourceId(int $orderId, int $soureId): bool
    {
        return static::updateBase(['order_source_id' => $soureId], $orderId);
    }

    /**
     * 记录是否已同步微信小程序发货信息管理
     * @param int $orderId
     * @param bool $status
     * @return bool
     */
    public static function updateSyncWeixinShipping(int $orderId, bool $status): bool
    {
        return static::updateBase(['sync_weixin_shipping' => (int)$status], $orderId);
    }
}
