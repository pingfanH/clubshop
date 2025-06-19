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

namespace app\api\model\recharge;

use app\api\service\User as UserService;
use app\api\model\Setting as SettingModel;
use app\api\model\recharge\Plan as PlanModel;
use app\api\model\recharge\OrderPlan as OrderPlanModel;
use app\common\model\recharge\Order as OrderModel;
use app\common\service\Order as OrderService;
use app\common\enum\recharge\order\PayStatus as PayStatusEnum;
use app\common\enum\recharge\order\RechargeType as RechargeTypeEnum;
use cores\exception\BaseException;
use app\common\library\helper;

/**
 * 用户充值订单模型
 * Class Order
 * @package app\api\model\recharge
 */
class Order extends OrderModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'transaction_id',
        'store_id',
        'create_time',
        'update_time',
    ];

    /**
     * 获取订单列表
     * @return \think\Paginator
     * @throws BaseException
     * @throws \think\db\exception\DbException
     */
    public function getList(): \think\Paginator
    {
        // 当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 获取列表数据
        return $this->where('user_id', '=', $userId)
            ->where('pay_status', '=', PayStatusEnum::SUCCESS)
            ->order(['create_time' => 'desc'])
            ->paginate(15);
    }

    /**
     * 获取订单详情 (待付款状态)
     * @param string $orderNo 订单号
     * @return array|null|static
     */
    public static function getPayDetail(string $orderNo)
    {
        return self::detail(['order_no' => $orderNo]);
    }

    /**
     * 创建充值订单
     * @param int|null $planId 方案ID
     * @param string|null $customMoney 自定义金额
     * @return bool|int
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(?int $planId = null, ?string $customMoney = null)
    {
        // 确定充值方式
        $rechargeType = $planId > 0 ? RechargeTypeEnum::PLAN : RechargeTypeEnum::CUSTOM;
        // 验证用户输入
        if (!$this->validateForm($rechargeType, $planId, $customMoney)) {
            $this->error = $this->error ?: '数据验证错误';
            return false;
        }
        // 获取订单数据
        $data = $this->getOrderData($rechargeType, $planId, $customMoney);
        // 记录订单信息
        return $this->saveOrder($data);
    }

    /**
     * 保存订单记录
     * @param array $data
     * @return bool
     */
    private function saveOrder(array $data): bool
    {
        // 写入订单记录
        $this->save($data['order']);
        // 记录订单套餐快照
        if (!empty($data['plan'])) {
            $PlanModel = new OrderPlanModel;
            return $PlanModel->add($this['order_id'], $data['plan']);
        }
        return true;
    }

    /**
     * 生成充值订单
     * @param int $rechargeType 充值方式
     * @param int $planId 方案ID
     * @param string $customMoney 自定义金额
     * @return array|array[]|bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getOrderData(int $rechargeType, int $planId, string $customMoney)
    {
        // 订单信息
        $data = [
            'order' => [
                'user_id' => UserService::getCurrentLoginUserId(),
                'order_no' => 'RC' . OrderService::createOrderNo(),
                'recharge_type' => $rechargeType,
                'gift_money' => 0.00,
                'platform' => \getPlatform(),
                'store_id' => self::$storeId,
            ],
            'plan' => []    // 订单套餐快照
        ];
        // 自定义金额充值
        if ($rechargeType == RechargeTypeEnum::CUSTOM) {
            $data = $this->createDataByCustom($data, $customMoney);
        }
        // 套餐充值
        if ($rechargeType == RechargeTypeEnum::PLAN) {
            $data = $this->createDataByPlan($data, $planId);
        }
        // 实际到账金额
        $data['order']['actual_money'] = helper::bcadd($data['order']['pay_price'], $data['order']['gift_money']);
        return $data;
    }

    /**
     * 创建套餐充值订单数据
     * @param array $order 订单数据
     * @param int $planId 方案ID
     * @return array
     * @throws BaseException
     */
    private function createDataByPlan(array $order, int $planId): array
    {
        // 获取套餐详情
        $planInfo = PlanModel::detail($planId);
        if (empty($planInfo)) {
            throwError('充值套餐不存在');
        }
        $order['plan'] = $planInfo;
        $order['order']['plan_id'] = $planInfo['plan_id'];
        $order['order']['gift_money'] = $planInfo['gift_money'];
        $order['order']['pay_price'] = $planInfo['money'];
        return $order;
    }

    /**
     * 创建自定义充值订单数据
     * @param array $order
     * @param string $customMoney 自定义金额
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function createDataByCustom(array $order, string $customMoney)
    {
        // 用户支付金额
        $order['order']['pay_price'] = $customMoney;
        // 充值设置
        $setting = SettingModel::getItem('recharge');
        if (!$setting['is_custom']) {
            return true;
        }
        // 根据自定义充值金额匹配满足的套餐
        if ($setting['is_match_plan']) {
            $matchPlanInfo = (new PlanModel)->getMatchPlan($customMoney);
            if (!empty($matchPlanInfo)) {
                $order['plan'] = $matchPlanInfo;
                $order['order']['plan_id'] = $matchPlanInfo['plan_id'];
                $order['order']['gift_money'] = $matchPlanInfo['gift_money'];
            }
        }
        return $order;
    }

    /**
     * 表单验证
     * @param int $rechargeType
     * @param int $planId 方案ID
     * @param string $customMoney 自定义金额
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function validateForm(int $rechargeType, int $planId, string $customMoney): bool
    {
        if (empty($planId) && $customMoney <= 0) {
            $this->error = '请选择充值套餐或输入充值金额';
            return false;
        }
        // 验证自定义的金额
        if ($rechargeType == RechargeTypeEnum::CUSTOM && !$this->validateFormCustom($customMoney)) {
            return false;
        }
        return true;
    }

    /**
     * 表单验证 [自定义充值]
     * @param string $customMoney 充值金额
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function validateFormCustom(string $customMoney): bool
    {
        // 充值设置
        $setting = SettingModel::getItem('recharge');
        if (!$setting['is_custom']) {
            $this->error = '很抱歉，当前不允许充值自定义金额';
            return false;
        }
        if ($customMoney <= 0) {
            $this->error = '请输入正确的充值金额';
            return false;
        }
        // 验证最低充值金额
        if (helper::bccomp($customMoney, $setting['lowest_money']) === -1) {
            $this->error = "很抱歉，当前最低充值金额不能低于{$setting['lowest_money']}元";
            return false;
        }
        return true;
    }
}