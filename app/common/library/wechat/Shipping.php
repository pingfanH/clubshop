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

namespace app\common\library\wechat;

use cores\exception\BaseException;

/**
 * 发货信息管理接口
 * Class Shipping
 * @package app\library
 */
class Shipping extends WxBase
{
    /**
     * 发货信息录入接口
     * api文档: https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/order-shipping/order-shipping.html
     * @throws BaseException
     */
    public function uploadShippingInfo(array $params)
    {
        // 微信接口url
        $accessToken = $this->getAccessToken();
        $apiUrl = "https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token={$accessToken}";
        // 执行请求
        $result = $this->post($apiUrl, $this->jsonEncode($params));
        // 记录日志
        log_record(['name' => '微信小程序-发货信息录入接口', 'url' => $apiUrl, 'params' => $params, 'result' => $result]);
        // 返回结果
        $response = $this->jsonDecode($result);
        if (!isset($response['errcode'])) {
            $this->error = 'not found errcode';
            return false;
        }
        if ($response['errcode'] != 0) {
            $this->error = $response['errmsg'];
            return false;
        }
        return $response;
    }

    /**
     * 消息跳转路径设置接口
     * api文档: https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/order-shipping/order-shipping.html#%E5%85%AD%E3%80%81%E6%B6%88%E6%81%AF%E8%B7%B3%E8%BD%AC%E8%B7%AF%E5%BE%84%E8%AE%BE%E7%BD%AE%E6%8E%A5%E5%8F%A3
     * @throws BaseException
     */
    public function setMsgJumpPath(array $params)
    {
        // 微信接口url
        $accessToken = $this->getAccessToken();
        $apiUrl = "https://api.weixin.qq.com/wxa/sec/order/set_msg_jump_path?access_token={$accessToken}";
        // 执行请求
        $result = $this->post($apiUrl, $this->jsonEncode($params));
        // 记录日志
        log_record(['name' => '微信小程序-消息跳转路径设置接口', 'url' => $apiUrl, 'params' => $params, 'result' => $result]);
        // 返回结果
        $response = $this->jsonDecode($result);
        if (!isset($response['errcode'])) {
            $this->error = 'not found errcode';
            return false;
        }
        if ($response['errcode'] != 0) {
            $this->error = $response['errmsg'];
            return false;
        }
        return $response;
    }
}