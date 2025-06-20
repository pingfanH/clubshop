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

namespace app\common\service\message\order;

use app\common\service\message\Basics;
use app\common\model\store\Setting as SettingModel;
use app\common\enum\setting\sms\Scene as SettingSmsScene;

/**
 * 消息通知服务 [订单支付成功]
 * Class Payment
 * @package app\common\service\message\order
 */
class Payment extends Basics
{
    /**
     * 参数列表
     * @var array
     */
    protected $param = [
        'order' => []
    ];

    /**
     * 发送消息通知
     * @param array $param
     * @return mixed|void
     * @throws \cores\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function send(array $param)
    {
        // 记录参数
        $this->param = $param;
        // 短信通知商家
        $this->onSendSms();
    }

    /**
     * 短信通知商家
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function onSendSms(): void
    {
        $orderInfo = $this->param['order'];
        // 获取商家的手机号 (用于接受短信通知)
        $acceptPhone = $this->getStoreAcceptPhone();
        if (empty($acceptPhone)) {
            return;
        }
        // 发送短信通知
        $this->sendSms(SettingSmsScene::ORDER_PAY, $acceptPhone, ['order_no' => $orderInfo['order_no']]);
    }

    /**
     * 获取商家手机号(用于接受短信通知)
     * @return string|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getStoreAcceptPhone()
    {
        // 短信通知设置
        $smsConfig = SettingModel::getItem('sms', $this->storeId);
        // 商家手机号 (后台设置)
        return $smsConfig['scene'][SettingSmsScene::ORDER_PAY]['acceptPhone'] ?? false;
    }
}