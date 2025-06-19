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

use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\model\PaymentTemplate as PaymentTemplateModel;

/**
 * 模型类：支付方式记录
 * Class PaymentTemplate
 * @package app\api\model
 */
class PaymentTemplate extends PaymentTemplateModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'name',
        'remarks',
        'sort',
        'is_delete',
        'store_id',
        'create_time',
        'update_time'
    ];

    /**
     * 根据微信支付V3平台证书序号或微信支付公钥ID查找记录
     * @param string $serial
     * @return static|array|null
     */
    public static function findByWechatpaySerial(string $serial)
    {
        return static::get(['method' => PaymentMethodEnum::WECHAT, 'wechatpay_serial' => $serial]);
    }
}
