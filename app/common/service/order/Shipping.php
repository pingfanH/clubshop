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

namespace app\common\service\order;

use app\common\library\helper;
use app\common\model\Express as ExpressModel;
use app\common\model\UserOauth as UserOauthModel;
use app\common\model\wxapp\Setting as WxappSettingModel;
use app\common\enum\Client as ClientEnum;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\enum\order\DeliveryStatus as DeliveryStatusEnum;
use app\common\library\wechat\Shipping as WechatShippingApi;
use app\common\service\BaseService;
use cores\exception\BaseException;

/**
 * 微信小程序-发货信息管理
 * Class Shipping
 * @package app\common\service\order
 */
class Shipping extends BaseService
{
    // 物流配送
    const DELIVERY_EXPRESS = 1;

    // 虚拟商品
    const DELIVERY_VIRTUAL = 3;

    /**
     * 发货信息同步微信平台
     * @param mixed $completed 订单详情
     * @param array $param 发货信息参数
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncMpWeixinShipping($completed, array $param): bool
    {
        // 构建发货信息参数
        $param = $this->buildParam($completed, $param);
        // 仅微信小程序端并且使用微信支付的订单才可以同步
        if (
            !$param['syncMpWeixinShipping']
            || $completed['pay_method'] !== PaymentMethodEnum::WECHAT
            || $completed['platform'] !== ClientEnum::MP_WEIXIN
        ) {
            return false;
        }
        // 订单全部发货时再同步, 分包发货时不同步
        if ($completed['delivery_status'] !== DeliveryStatusEnum::DELIVERED) {
            return false;
        }
        if (empty($completed['trade'])) {
            throwError('很抱歉，该订单不存在微信支付交易记录');
        }
        // 请求微信API接口
        return $this->request($this->buildApiParam($completed, $param), $completed['store_id']);
    }

    /**
     * 请求微信API接口
     * @param array $apiParam
     * @param int $storeId
     * @return true
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function request(array $apiParam, int $storeId): bool
    {
        // 小程序配置信息
        $wxConfig = WxappSettingModel::getConfigBasic($storeId);
        // 请求API数据
        $WechatShippingApi = new WechatShippingApi($wxConfig['app_id'], $wxConfig['app_secret']);
        // 处理返回结果
        $response = $WechatShippingApi->uploadShippingInfo($apiParam);
        empty($response) && throwError('微信API请求失败：' . $WechatShippingApi->getError());
        return true;
    }

    /**
     * 构建发货信息参数
     * @param $completed
     * @param array $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function buildParam($completed, array $param): array
    {
        // 设置默认的参数
        $param = helper::setQueryDefaultValue($param, [
            // 同步至微信小程序《发货信息管理》
            'syncMpWeixinShipping' => 1,
            // 物流模式：1物流配送 3虚拟商品 4用户自提
            'logisticsType' => self::DELIVERY_EXPRESS,
            // 物流公司ID
            'expressId' => '',
            // 物流单号
            'expressNo' => '',
        ]);
        // 是否开启发货信息管理
        !WxappSettingModel::isEnableShipping($completed['store_id']) && $param['syncMpWeixinShipping'] = 0;
        return $param;
    }

    /**
     * 构建API参数
     * @param $completed
     * @param array $param
     * @return array
     * @throws BaseException
     */
    private function buildApiParam($completed, array $param): array
    {
        // 获取物流公司编码
        $param['expressId'] > 0 && $expressCode = $this->getExpressCode($param['expressId']);
        return [
            'order_key' => [
                // 订单单号类型：1使用下单商户号和商户侧单号 2使用微信支付单号
                'order_number_type' => 2,
                // 支付交易对应的微信订单号
                'transaction_id' => $completed['trade']['trade_no'],
            ],
            // 物流模式：1物流配送 3虚拟商品 4用户自提
            'logistics_type' => $param['logisticsType'],
            // 发货模式：1、UNIFIED_DELIVERY（统一发货）2、SPLIT_DELIVERY（分拆发货）
            'delivery_mode' => 1,
            // 用于标识分拆发货模式下是否已全部发货完成 示例值: true/false
            'is_all_delivered' => true,
            'shipping_list' => [
                [
                    'express_company' => $expressCode ?? '',                // 物流公司编码
                    'tracking_no' => $param['expressNo'],                   // 物流单号
                    'item_desc' => $completed['goods'][0]['goods_name'],    // 商品信息
                ]
            ],
            // 上传时间 (RFC3339格式)
            'upload_time' => \date(DATE_RFC3339),
            // 微信用户openid
            'payer' => ['openid' => $this->getUserOpenId($completed['user_id'])],
        ];
    }

    /**
     * 获取物流公司编码 [这里用快递鸟编码格式]
     * @param int $expressId
     * @return mixed
     * @throws BaseException
     */
    private function getExpressCode(int $expressId)
    {
        $detail = ExpressModel::detail($expressId);
        empty($detail) && throwError('很抱歉，未找到指定的物流公司');
        return $detail['kdniao_code'];
    }

    /**
     * 获取微信小程序用户openid
     * @param int $userId
     * @return mixed
     * @throws BaseException
     */
    private function getUserOpenId(int $userId)
    {
        $openid = UserOauthModel::getOauthIdByUserId($userId, ClientEnum::MP_WEIXIN);
        empty($openid) && throwError('很抱歉，未找到当前用户的openid');
        return $openid;
    }
}