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

namespace app\api\service;

use app\api\model\Payment as PaymentModel;
use app\api\model\Setting as SettingModel;
use app\api\model\recharge\Plan as PlanModel;
use app\api\service\User as UserService;
use app\api\service\recharge\Payment as PaymentService;
use app\common\service\BaseService;
use cores\exception\BaseException;

/**
 * 用户余额充值服务
 * Class Recharge
 * @package app\api\controller
 */
class Recharge extends BaseService
{
    // 提示信息
    private string $message = '';

    // 支付方式 (微信支付、支付宝)
    private string $method;

    // 下单的客户端
    private string $client;

    /**
     * 设置当前支付方式
     * @param string $method 支付方式
     * @return $this
     */
    public function setMethod(string $method): Recharge
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置下单的客户端
     * @param string $client 客户端
     * @return $this
     */
    public function setClient(string $client): Recharge
    {
        $this->client = $client;
        return $this;
    }

    /**
     * 充值中心页面数据
     * @param string $client 当前客户端
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function center(string $client): array
    {
        // 当期用户信息
        $userInfo = UserService::getCurrentLoginUser(true);
        // 获取充值方案列表
        $PlanModel = new PlanModel;
        $planList = $PlanModel->getList();
        // 根据指定客户端获取可用的支付方式
        $PaymentModel = new PaymentModel;
        $methods = $PaymentModel->getMethodsByClient($client, true);
        // 充值设置
        $setting = SettingModel::getRecharge();
        // 返回数据
        return [
            'setting' => $setting,
            'personal' => $userInfo,
            'planList' => $planList,
            'paymentMethods' => $methods
        ];
    }

    /**
     * 确认订单支付事件
     * @param int|null $planId 方案ID
     * @param string|null $customMoney 自定义金额
     * @param array $extra 附加数据
     * @return array[]
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPay(?int $planId = null, string $customMoney = null, array $extra = []): array
    {
        $PaymentService = new PaymentService;
        $result = $PaymentService->setMethod($this->method)
            ->setClient($this->client)
            ->orderPay($planId, $customMoney, $extra);
        $this->message = $PaymentService->getMessage();
        return $result;
    }

    /**
     * 交易查询
     * 查询第三方支付订单是否付款成功
     * @param string $outTradeNo 商户订单号
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tradeQuery(string $outTradeNo): bool
    {
        $PaymentService = new PaymentService;
        return $PaymentService->setMethod($this->method)->setClient($this->client)->tradeQuery($outTradeNo);
    }

    /**
     * 返回消息提示
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}