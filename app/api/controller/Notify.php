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

namespace app\api\controller;

use cores\BaseController;
use app\api\service\Notify as NotifyService;

/**
 * 支付成功异步通知接口
 * Class Notify
 * @package app\api\controller
 */
class Notify extends BaseController
{
    /**
     * 支付成功异步通知 (微信支付V2)
     * @return string
     */
    public function wechatV2(): string
    {
        try {
            $NotifyService = new NotifyService;
            return $NotifyService->wechatV2();
        } catch (\Throwable $e) {
            return 'FAIL';
        }
    }

    /**
     * 支付成功异步通知 (微信支付V3)
     * @return string
     */
    public function wechatV3(): string
    {
        try {
            $NotifyService = new NotifyService;
            return $NotifyService->wechatV3();
        } catch (\Throwable $e) {
            return '{"code": "FAIL","message": "失败"}';
        }
    }

    /**
     * 支付成功异步通知 (支付宝)
     * @return string
     */
    public function alipay(): string
    {
        $NotifyService = new NotifyService;
        return $NotifyService->alipay();
    }
}
