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

namespace app\api\service\order;

use app\api\model\{
    User as UserModel,
    Goods as GoodsModel,
    Order as OrderModel,
    Setting as SettingModel,
    UserCoupon as UserCouponModel,
};
use app\api\service\{
    User as UserService,
    user\Grade as UserGradeService,
    coupon\GoodsDeduct as GoodsDeductService,
    order\PaySuccess as OrderPaySuccesService,
    points\GoodsDeduct as PointsDeductService,
    order\source\checkout\Factory as CheckoutFactory,
};
use app\common\enum\{
    Setting as SettingEnum,
    DiscountType as DiscountTypeEnum,
    payment\Method as PaymentMethodEnum,
    order\OrderType as OrderTypeEnum,
    order\OrderStatus as OrderStatusEnum,
    order\OrderSource as OrderSourceEnum,
    order\DeliveryType as DeliveryTypeEnum,
};
use app\common\service\{
    BaseService,
    delivery\Express as ExpressService,
    goods\source\Factory as StockFactory
};
use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 订单结算台服务类
 * Class Checkout
 * @package app\api\service\order
 */
class Checkout extends BaseService
{
    /* $model OrderModel 订单模型 */
    public OrderModel $model;

    /* @var UserModel $user 当前用户信息 */
    private $user;

    // 订单结算商品列表
    private $goodsList;

    /**
     * 订单结算api参数
     * @var array
     */
    private array $param = [
        'delivery' => null,     // 配送方式
        'couponId' => 0,        // 用户的优惠券ID
        'isUsePoints' => 0,     // 是否使用积分抵扣
        'remark' => '',         // 买家留言
    ];

    /**
     * 订单结算的规则
     * @var array
     */
    private array $checkoutRule = [
        'isUserGrade' => true,            // 会员等级折扣
        'isCoupon' => true,               // 优惠券折扣
        'isUsePoints' => true,            // 积分抵扣
        'isUseFullDiscount' => true,      // 满额立减
        'isUseFullFree' => true,          // 满额包邮
        'isDealer' => true,               // 开启分销
    ];

    /**
     * 订单来源
     * @var array
     */
    private array $orderSource = [
        'source' => OrderSourceEnum::MAIN,
        'sourceId' => 0,
        'sourceData' => [],
    ];

    /**
     * 订单结算数据
     * @var array
     */
    private array $orderData = [];

    // 是否已支付 (仅积分商品兑换时)
    private bool $isPaySuccess = false;

    /**
     * 构造函数
     * Checkout constructor.
     * @throws BaseException
     */
    public function __construct()
    {
        parent::__construct();
        $this->user = UserService::getCurrentLoginUser(true);
        $this->model = new OrderModel;
        $this->storeId = $this->getStoreId();
    }

    /**
     * 设置结算台请求的参数
     * @param $param
     * @return array
     */
    public function setParam($param): array
    {
        $this->param = \array_merge($this->param, $param);
        return $this->getParam();
    }

    /**
     * 获取结算台请求的参数
     * @return array
     */
    public function getParam(): array
    {
        return $this->param;
    }

    /**
     * 订单结算的规则
     * @param $data
     * @return $this
     */
    public function setCheckoutRule($data): Checkout
    {
        $this->checkoutRule = \array_merge($this->checkoutRule, $data);
        return $this;
    }

    /**
     * 设置订单来源(普通订单)
     * @param $data
     * @return $this
     */
    public function setOrderSource($data): Checkout
    {
        $this->orderSource = \array_merge($this->orderSource, $data);
        return $this;
    }

    /**
     * 订单确认-结算台
     * @param $goodsList
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onCheckout($goodsList): array
    {
        // 订单确认-立即购买
        $this->goodsList = $goodsList;
        return $this->checkout();
    }

    /**
     * 订单结算台
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkout(): array
    {
        // 整理订单数据
        $this->orderData = $this->getOrderData();
        // 验证商品状态, 是否允许购买
        $this->validateGoodsList();
        // 订单商品总数量
        $orderTotalNum = (int)helper::getArrayColumnSum($this->goodsList, 'total_num');
        // 设置订单商品会员折扣价
        $this->setOrderGoodsGradeMoney();
        // 设置订单商品总金额(不含优惠折扣)
        $this->setOrderTotalPrice();
        // 当前用户可用的优惠券列表
        $couponList = $this->getCouponListByOrder((float)$this->orderData['orderTotalPrice']);
        // 计算优惠券抵扣
        $this->setOrderCouponMoney($couponList, (int)$this->param['couponId']);
        // 计算可用积分抵扣
        $this->setOrderPoints();
        // 计算订单商品的实际付款金额
        $this->setOrderGoodsPayPrice();
        // 设置订单配送信息
        $this->setDelivery();
        // 计算订单最终金额
        $this->setOrderPayPrice();
        // 计算订单积分赠送数量
        $this->setOrderPointsBonus();
        // 返回订单数据
        return \array_merge([
            'goodsList' => $this->goodsList,   // 商品信息
            'orderTotalNum' => $orderTotalNum,        // 商品总数量
            'couponList' => \array_values($couponList), // 优惠券列表
            'hasError' => $this->hasError(),
            'errorMsg' => $this->getError(),
        ], $this->orderData);
    }

    /**
     * 设置订单配送信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setDelivery()
    {
        // 设置默认配送方式
        if (!$this->param['delivery']) {
            $deliveryType = SettingModel::getItem(SettingEnum::DELIVERY)['delivery_type'];
            $this->param['delivery'] = current($deliveryType);
        }
        // 处理配送方式
        if ($this->param['delivery'] == DeliveryTypeEnum::EXPRESS) {
            $this->setOrderExpress();
        }
        // 判断商品是否支持配送
        $deliveryName = DeliveryTypeEnum::data()[$this->param['delivery']]['name'];
        foreach ($this->goodsList as $goods) {
            if ($goods['is_ind_delivery_type'] && !in_array($this->param['delivery'], $goods['delivery_type'])) {
                $this->setError("很抱歉，商品 [{$goods['goods_name']}] 不支持{$deliveryName}");
            }
        }
    }

    /**
     * 计算订单可用积分抵扣
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setOrderPoints(): void
    {
        // 设置默认的商品积分抵扣信息
        $this->setDefaultGoodsPoints();
        // 积分设置
        $setting = SettingModel::getItem('points');
        // 条件：后台开启下单使用积分抵扣
        if (
            !$this->checkoutRule['isUsePoints']
            || !$setting['is_shopping_discount']
        ) {
            return;
        }
        // 条件：订单金额满足[?]元
        if (helper::bccomp($setting['discount']['full_order_price'], $this->orderData['orderTotalPrice']) === 1) {
            return;
        }
        // 计算订单商品最多可抵扣的积分数量
        $this->setOrderGoodsMaxPointsNum();
        // 订单最多可抵扣的积分总数量
        $maxPointsNumCount = (int)helper::getArrayColumnSum($this->goodsList, 'max_points_num');
        // 实际可抵扣的积分数量
        $actualPointsNum = min($maxPointsNumCount, $this->user['points']);
        if ($actualPointsNum < 1) {
            return;
        }
        // 计算订单商品实际抵扣的积分数量和金额
        $GoodsDeduct = new PointsDeductService($this->goodsList);
        $GoodsDeduct->setGoodsPoints($maxPointsNumCount, $actualPointsNum);
        // 积分抵扣总金额
        $orderPointsMoney = helper::getArrayColumnSum($this->goodsList, 'points_money');
        $this->orderData['pointsMoney'] = helper::number2($orderPointsMoney);
        // 积分抵扣总数量
        $this->orderData['pointsNum'] = $actualPointsNum;
        // 允许积分抵扣
        $this->orderData['isAllowPoints'] = true;
    }

    /**
     * 计算订单商品最多可抵扣的积分数量
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setOrderGoodsMaxPointsNum(): void
    {
        // 积分设置
        $setting = SettingModel::getItem('points');
        foreach ($this->goodsList as &$goods) {
            // 商品不允许积分抵扣
            if (!$goods['is_points_discount']) continue;
            // 积分抵扣比例
            $deductionRatio = helper::bcdiv($setting['discount']['max_money_ratio'], 100);
            // 最多可抵扣的金额
            $totalPayPrice = helper::bcsubs([$goods['total_price'], $goods['coupon_money']]);
            $maxPointsMoney = helper::bcmul($totalPayPrice, $deductionRatio);
            // 最多可抵扣的积分数量
            $goods['max_points_num'] = helper::bcdiv($maxPointsMoney, $setting['discount']['discount_ratio'], 0);
        }
    }

    /**
     * 设置默认的商品积分抵扣信息
     * @return void
     */
    private function setDefaultGoodsPoints(): void
    {
        foreach ($this->goodsList as &$goods) {
            // 最多可抵扣的积分数量
            $goods['max_points_num'] = 0;
            // 实际抵扣的积分数量
            $goods['points_num'] = 0;
            // 实际抵扣的金额
            $goods['points_money'] = 0;
        }
    }

    /**
     * 整理订单数据(结算台初始化)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getOrderData(): array
    {
        return [
            // 当前订单类型
            'orderType' => $this->getOrderType(),
            // 当前配送方式
            'delivery' => $this->getDeliveryType(),
            // 默认地址
            'address' => $this->user['address_default'],
            // 是否存在收货地址
            'existAddress' => $this->user['address_id'] > 0,
            // 配送费用
            'expressPrice' => '0.00',
            // 当前用户收货城市是否存在配送规则中
            'isIntraRegion' => true,
            // 是否允许使用积分抵扣
            'isAllowPoints' => false,
            // 是否使用积分抵扣
            'isUsePoints' => (bool)$this->param['isUsePoints'],
            // 积分抵扣金额
            'pointsMoney' => '0.00',
            // 赠送的积分数量
            'pointsBonus' => 0,
        ];
    }

    /**
     * 获取当前配送方式
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getDeliveryType()
    {
        // 系统默认的配送方式
        $setting = SettingModel::getItem(SettingEnum::DELIVERY)['delivery_type'];
        // 配送方式：实物订单
        if ($this->getOrderType() == OrderTypeEnum::PHYSICAL) {
            return $this->param['delivery'] > 0 ? $this->param['delivery'] : $setting[0];
        }
        // 配送方式：无需配送
        return DeliveryTypeEnum::NOTHING;
    }

    /**
     * 获取订单类型
     * @return mixed
     */
    private function getOrderType()
    {
        return $this->goodsList[0]['goods_type'];
    }

    /**
     * 获取订单页面中使用到的系统设置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSetting(): array
    {
        // 系统支持的配送方式 (后台设置)
        $deliveryType = SettingModel::getItem(SettingEnum::DELIVERY)['delivery_type'];
        // 积分设置
        $pointsSetting = SettingModel::getItem(SettingEnum::POINTS);
        return [
            'deliveryType' => $deliveryType,                       // 支持的配送方式
            'points_name' => $pointsSetting['points_name'],        // 积分名称
            'points_describe' => $pointsSetting['describe'],       // 积分说明
        ];
    }

    /**
     * 获取订单结算时的个人信息
     * @return array
     */
    public function getPersonal(): array
    {
        return [
            'user_id' => $this->user['user_id'],
            'balance' => $this->user['balance'],
            'points' => $this->user['points'],
            'address_id' => $this->user['address_id'],
        ];
    }

    /**
     * 获取订单是否已支付成功
     * @return bool
     */
    public function isPaySuccess(): bool
    {
        return $this->isPaySuccess;
    }

    /**
     * 当前用户可用的优惠券列表
     * @param float $orderTotalPrice 订单商品总金额
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getCouponListByOrder(float $orderTotalPrice): array
    {
        // 是否开启优惠券折扣
        if (!$this->checkoutRule['isCoupon']) {
            return [];
        }
        // 整理当前订单所有商品ID集
        $orderGoodsIds = helper::getArrayColumn($this->goodsList, 'goods_id');
        // 当前用户可用的优惠券列表
        $couponList = UserCouponModel::getListByOrder($this->user['user_id'], $orderTotalPrice);
        // 判断当前优惠券是否满足订单使用条件 (优惠券适用范围)
        return UserCouponModel::couponListApplyRange($couponList, $orderGoodsIds);
    }

    /**
     * 验证订单商品的状态
     * @return void
     */
    private function validateGoodsList(): void
    {
        $Checkout = CheckoutFactory::getFactory(
            $this->user,
            $this->goodsList,
            $this->orderSource['source']
        );
        if (!$Checkout->validateGoodsList()) {
            $this->setError($Checkout->getError());
        }
    }

    /**
     * 设置订单的商品总金额(不含优惠折扣)
     */
    private function setOrderTotalPrice()
    {
        // 订单商品的总金额(不含优惠券折扣)
        $this->orderData['orderTotalPrice'] = helper::number2(helper::getArrayColumnSum($this->goodsList, 'total_price'));
    }

    /**
     * 设置订单的实际支付金额(含配送费)
     */
    private function setOrderPayPrice()
    {
        // 订单金额(含优惠折扣)
        $this->orderData['orderPrice'] = helper::number2(helper::getArrayColumnSum($this->goodsList, 'total_pay_price'));
        // 订单实付款金额(订单金额 + 运费)
        $this->orderData['orderPayPrice'] = helper::number2(helper::bcadd($this->orderData['orderPrice'], $this->orderData['expressPrice']));
    }

    /**
     * 计算订单积分赠送数量
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setOrderPointsBonus(): void
    {
        // 初始化商品积分赠送数量
        foreach ($this->goodsList as &$goods) {
            $goods['points_bonus'] = 0;
        }
        // 积分设置
        $setting = SettingModel::getItem('points');
        // 条件：后台开启开启购物送积分
        if (!$setting['is_shopping_gift']) {
            return;
        }
        // 设置商品积分赠送数量
        foreach ($this->goodsList as &$goods) {
            // 积分赠送比例
            $ratio = helper::bcdiv($setting['gift_ratio'], 100);
            // 计算抵扣积分数量
            $goods['points_bonus'] = !$goods['is_points_gift'] ? 0 : helper::bcmul($goods['total_pay_price'], $ratio, 0);
        }
        //  订单积分赠送数量
        $this->orderData['pointsBonus'] = (int)helper::getArrayColumnSum($this->goodsList, 'points_bonus');
    }

    /**
     * 计算订单商品的实际付款金额
     * @return void
     */
    private function setOrderGoodsPayPrice(): void
    {
        // 商品总价 - 优惠抵扣
        foreach ($this->goodsList as &$goods) {
            // 减去优惠券抵扣金额
            $value = helper::bcsubs([$goods['total_price'], $goods['coupon_money']]);
            // 减去积分抵扣金额
            if ($this->orderData['isAllowPoints'] && $this->orderData['isUsePoints']) {
                $value = helper::bcsub($value, $goods['points_money']);
            }
            // 订单商品的实际付款金额
            $goods['total_pay_price'] = helper::number2($value);
        }
    }

    /**
     * 设置订单商品会员折扣价
     * @return void
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setOrderGoodsGradeMoney(): void
    {
        // 设置默认数据
        helper::setDataAttribute($this->goodsList, [
            // 标记参与会员折扣
            'is_user_grade' => false,
            // 会员等级抵扣的金额
            'grade_ratio' => 0,
            // 会员折扣的商品单价
            'grade_goods_price' => 0,
            // 会员折扣的总额差
            'grade_total_money' => 0,
        ], true);
        // 是否开启会员等级折扣
        if (!$this->checkoutRule['isUserGrade']) {
            return;
        }
        // 获取当前登录用户的会员等级信息
        $gradeInfo = UserGradeService::getCurrentGradeInfo();
        // 判断商品是否参与会员折扣
        if (empty($gradeInfo)) {
            return;
        }
        // 计算抵扣金额
        foreach ($this->goodsList as &$goods) {
            // 判断商品是否参与会员折扣
            if (!$goods['is_enable_grade']) {
                continue;
            }
            // 折扣比例
            $discountRatio = $gradeInfo['equity']['discount'];
            // 商品单独设置了会员折扣
            if ($goods['is_alone_grade'] && isset($goods['alone_grade_equity'][$this->user['grade_id']])) {
                $discountRatio = $goods['alone_grade_equity'][$gradeInfo['grade_id']];
            }
            if (empty($discountRatio)) {
                continue;
            }
            // 会员折扣后的商品总金额
            $gradeTotalPrice = UserGradeService::getDiscountPrice($goods['total_price'], $discountRatio);
            helper::setDataAttribute($goods, [
                'is_user_grade' => true,
                'grade_ratio' => $discountRatio,
                'grade_goods_price' => UserGradeService::getDiscountPrice($goods['goods_price'], $discountRatio),
                'grade_total_money' => helper::bcsub($goods['total_price'], $gradeTotalPrice),
                'total_price' => $gradeTotalPrice,
            ], false);
        }
    }

    /**
     * 设置订单优惠券抵扣信息
     * @param array $couponList 当前用户可用的优惠券列表
     * @param int $userCouponId 当前选择的优惠券ID
     * @return void
     * @throws BaseException
     */
    private function setOrderCouponMoney(array $couponList, int $userCouponId): void
    {
        // 设置默认数据：订单信息
        helper::setDataAttribute($this->orderData, [
            'couponId' => 0,       // 用户的优惠券ID
            'couponMoney' => 0,    // 优惠券抵扣金额
        ], false);
        // 设置默认数据：订单商品列表
        helper::setDataAttribute($this->goodsList, [
            'coupon_money' => 0,    // 优惠券抵扣金额
        ], true);
        // 验证选择的优惠券ID是否合法
        if (!$this->verifyOrderCouponId($userCouponId, $couponList)) {
            return;
        }
        // 获取优惠券信息
        $couponInfo = $this->getCouponInfo($userCouponId, $couponList);
        // 商品列表信息格式化
        $goodsListTemp = helper::getArrayColumns($this->goodsList, ['goods_id', 'goods_sku_id', 'total_price']);
        // 计算订单商品优惠券抵扣金额
        $CouponMoney = new GoodsDeductService;
        $rangeGoodsList = $CouponMoney->setGoodsList($goodsListTemp)
            ->setCouponInfo($couponInfo)
            ->setGoodsCouponMoney()
            ->getRangeGoodsList();
        // 分配订单商品优惠券抵扣金额
        foreach ($this->goodsList as &$goods) {
            $goodsKey = "{$goods['goods_id']}-{$goods['goods_sku_id']}";
            if (isset($rangeGoodsList[$goodsKey])) {
                $goods['coupon_money'] = helper::bcdiv($rangeGoodsList[$goodsKey]['coupon_money'], 100);
            }
        }
        // 记录订单优惠券信息
        $this->orderData['couponId'] = $userCouponId;
        $this->orderData['couponMoney'] = helper::number2(helper::bcdiv($CouponMoney->getActualReducedMoney(), 100));
    }

    /**
     * 验证用户选择的优惠券ID是否合法
     * @param int $userCouponId
     * @param $couponList
     * @return bool
     * @throws BaseException
     */
    private function verifyOrderCouponId(int $userCouponId, $couponList): bool
    {
        // 是否开启优惠券折扣
        if (!$this->checkoutRule['isCoupon']) {
            return false;
        }
        // 如果没有可用的优惠券，直接返回
        if ($userCouponId <= 0 || empty($couponList)) {
            return false;
        }
        // 判断优惠券是否存在
        $couponInfo = $this->getCouponInfo($userCouponId, $couponList);
        if (!$couponInfo) {
            throwError('未找到优惠券信息');
        }
        // 判断优惠券适用范围是否合法
        if (!$couponInfo['is_apply']) {
            throwError($couponInfo['not_apply_info']);
        }
        return true;
    }

    /**
     * 查找指定的优惠券信息
     * @param int $userCouponId 用户优惠券ID
     * @param array $couponList 优惠券列表
     * @return false|mixed
     */
    private function getCouponInfo(int $userCouponId, array $couponList)
    {
        return helper::getArrayItemByColumn($couponList, 'user_coupon_id', $userCouponId);
    }

    /**
     * 订单配送-快递配送
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setOrderExpress(): void
    {
        // 设置默认数据：配送费用
        helper::setDataAttribute($this->goodsList, [
            'expressPrice' => 0,
        ], true);
        // 当前用户收货城市ID
        $cityId = $this->user['address_default'] ? (int)$this->user['address_default']['city_id'] : 0;
        // 初始化配送服务类
        $ExpressService = new ExpressService($cityId, $this->goodsList);
        // 验证商品是否在配送范围
        $isIntraRegion = $ExpressService->isIntraRegion();
        if ($cityId > 0 && !$isIntraRegion) {
            $notInRuleGoodsName = $ExpressService->getNotInRuleGoodsName();
            $this->setError("很抱歉，您的收货地址不在商品 [{$notInRuleGoodsName}] 的配送范围内");
        }
        // 订单总运费金额
        $this->orderData['isIntraRegion'] = $isIntraRegion;
        $this->orderData['expressPrice'] = $ExpressService->getDeliveryFee(true);
    }

    /**
     * 创建新订单
     * @param array $order 订单信息
     * @return bool
     */
    public function createOrder(array $order): bool
    {
        // 表单验证
        if (!$this->validateOrderForm($order)) {
            return false;
        }
        // 创建新的订单
        return $this->model->transaction(function () use ($order) {
            return $this->createOrderEvent($order);
        });
    }

    /**
     * 创建订单事件
     * @param $order
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function createOrderEvent($order): bool
    {
        // 新增订单记录
        $this->add($order, $this->param['remark']);
        // 保存订单数据 (根据订单类型)
        $order['orderType'] == OrderTypeEnum::PHYSICAL && $this->saveOrderByPhysical($order);
        // 保存订单商品信息
        $this->saveOrderGoods($order);
        // 更新商品库存 (针对下单减库存的商品)
        $this->updateGoodsStockNum($order);
        // 设置优惠券使用状态
        $order['couponId'] > 0 && UserCouponModel::setIsUse((int)$order['couponId']);
        // 积分抵扣: 扣除用户可用积分
        if ($order['isAllowPoints'] && $order['isUsePoints'] && $order['pointsNum'] > 0) {
            $describe = "订单抵扣：{$this->model['order_no']}";
            UserModel::setIncPoints($this->user['user_id'], -$order['pointsNum'], $describe);
        }
        // 获取订单详情
        $detail = OrderModel::getDetail((int)$this->model['order_id'], ['goods']);
        return true;
    }

    /**
     * 保存订单数据 (实物商品)
     * @param $order
     * @return void
     */
    private function saveOrderByPhysical($order)
    {
        if ($order['delivery'] == DeliveryTypeEnum::EXPRESS) {
            // 记录收货地址
            $this->saveOrderAddress($order['address']);
        }
    }

    /**
     * 表单验证 (订单提交)
     * @param array $order 订单信息
     * @return bool
     */
    private function validateOrderForm(array $order): bool
    {
        if ($order['orderType'] == OrderTypeEnum::PHYSICAL) {
            return $this->validateOrderFormByPhysical($order);
        }
        return true;
    }

    /**
     * 表单验证：实物订单
     * @param array $order
     * @return bool
     */
    private function validateOrderFormByPhysical(array $order): bool
    {
        if ($order['delivery'] == DeliveryTypeEnum::EXPRESS) {
            if (empty($order['address'])) {
                $this->error = '您还没有选择配送地址';
                return false;
            }
        }
        return true;
    }

    /**
     * 当前订单是否存在和使用积分抵扣
     * @param $order
     * @return bool
     */
    private function isExistPointsDeduction($order): bool
    {
        return $order['isAllowPoints'] && $order['isUsePoints'];
    }

    /**
     * 新增订单记录
     * @param $order
     * @param string $remark
     * @return void
     */
    private function add($order, string $remark = ''): void
    {
        // 当前订单是否存在和使用积分抵扣
        $isExistPointsDeduction = $this->isExistPointsDeduction($order);
        // 订单数据
        $data = [
            'user_id' => $this->user['user_id'],
            'order_no' => $this->model->orderNo(),
            'order_type' => $order['orderType'],
            'total_price' => $order['orderTotalPrice'],
            'order_price' => $order['orderPrice'],
            'coupon_id' => $order['couponId'],
            'coupon_money' => $order['couponMoney'],
            'points_money' => $isExistPointsDeduction ? $order['pointsMoney'] : 0,
            'points_num' => $isExistPointsDeduction ? $order['pointsNum'] : 0,
            'pay_price' => $order['orderPayPrice'],
            'delivery_type' => $order['delivery'],
            'buyer_remark' => trim($remark),
            'order_source' => $this->orderSource['source'],
            'order_source_id' => $this->orderSource['sourceId'],
            'order_source_data' => $this->orderSource['sourceData'],
            'points_bonus' => $order['pointsBonus'],
            'order_status' => OrderStatusEnum::NORMAL,
            'platform' => \getPlatform(),
            'store_id' => $this->storeId,
        ];
        if ($order['delivery'] == DeliveryTypeEnum::EXPRESS) {
            $data['express_price'] = $order['expressPrice'];
        }
        // 保存订单记录
        $this->model->save($data);
    }

    /**
     * 保存订单商品信息
     * @param $order
     * @return void
     */
    private function saveOrderGoods($order): void
    {
        // 当前订单是否存在和使用积分抵扣
        $isExistPointsDeduction = $this->isExistPointsDeduction($order);
        // 订单商品列表
        $goodsList = [];
        foreach ($order['goodsList'] as $goods) {
            /* @var GoodsModel $goods */
            $item = [
                'user_id' => $this->user['user_id'],
                'store_id' => $this->storeId,
                'goods_id' => $goods['goods_id'],
                'goods_type' => $goods['goods_type'],
                'goods_name' => $goods['goods_name'],
                'goods_no' => $goods['goods_no'] ?: '',
                'image_id' => $this->getGoodsImageId($goods),
                'deduct_stock_type' => $goods['deduct_stock_type'],
                'spec_type' => $goods['spec_type'],
                'goods_sku_id' => $goods['skuInfo']['goods_sku_id'],
                'goods_props' => $goods['skuInfo']['goods_props'] ?: '',
                'content' => $goods['content'] ?? '',
                'goods_sku_no' => $goods['skuInfo']['goods_sku_no'] ?: '',
                'goods_price' => $goods['skuInfo']['goods_price'],
                'line_price' => $goods['skuInfo']['line_price'],
                'goods_weight' => $goods['skuInfo']['goods_weight'],
                'is_user_grade' => (int)$goods['is_user_grade'],
                'grade_ratio' => $goods['grade_ratio'],
                'grade_goods_price' => $goods['grade_goods_price'],
                'grade_total_money' => $goods['grade_total_money'],
                'coupon_money' => $goods['coupon_money'],
                'points_money' => $isExistPointsDeduction ? $goods['points_money'] : 0,
                'points_num' => $isExistPointsDeduction ? $goods['points_num'] : 0,
                'points_bonus' => $goods['points_bonus'],
                'total_num' => $goods['total_num'],
                'total_price' => $goods['total_price'],
                'total_pay_price' => $goods['total_pay_price'],
            ];
            // 记录订单商品来源ID
            $item['goods_source_id'] = isset($goods['goods_source_id']) ? $goods['goods_source_id'] : 0;
            $goodsList[] = $item;
        }
        $this->model->goods()->saveAll($goodsList) !== false;
    }

    /**
     * 获取订单商品的封面图（优先sku封面图）
     * @param $goods
     * @return int
     */
    private function getGoodsImageId($goods): int
    {
        return $goods['skuInfo']['image_id'] ?: (int)current($goods['goods_images'])['file_id'];
    }

    /**
     * 更新商品库存 (针对下单减库存的商品)
     * @param $order
     * @return void
     */
    private function updateGoodsStockNum($order): void
    {
        StockFactory::getFactory($this->model['order_source'])->updateGoodsStock($order['goodsList']);
    }

    /**
     * 记录收货地址
     * @param $address
     * @return void
     */
    private function saveOrderAddress($address): void
    {
        $this->model->address()->save([
            'user_id' => $this->user['user_id'],
            'store_id' => $this->storeId,
            'name' => $address['name'],
            'phone' => $address['phone'],
            'province_id' => $address['province_id'],
            'city_id' => $address['city_id'],
            'region_id' => $address['region_id'],
            'detail' => $address['detail'],
        ]);
    }
}
