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
namespace app\common\library\sms;

use Overtrue\EasySms\Strategies\OrderStrategy;

/**
 * EasySms配置类
 * Class Config
 * @package app\common\library\sms
 */
class Config
{
    /**
     * 生成EasySms的配置项
     * @param array $smsConfig
     * @return array
     */
    public static function getEasySmsConfig(array $smsConfig): array
    {
        return [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,

            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [$smsConfig['default']],
            ],
            // 可用的网关配置
            'gateways' => [
                'aliyun' => [
                    'access_key_id' => $smsConfig['engine']['aliyun']['AccessKeyId'],
                    'access_key_secret' => $smsConfig['engine']['aliyun']['AccessKeySecret'],
                    'sign_name' => $smsConfig['engine']['aliyun']['sign'],
                ],
                'qcloud' => [
                    'sdk_app_id' => $smsConfig['engine']['qcloud']['SdkAppID'],
                    'secret_id' => $smsConfig['engine']['qcloud']['AccessKeyId'],
                    'secret_key' => $smsConfig['engine']['qcloud']['AccessKeySecret'],
                    'sign_name' => $smsConfig['engine']['qcloud']['sign'],
                ],
                'qiniu' => [
                    'access_key' => $smsConfig['engine']['qiniu']['AccessKey'],
                    'secret_key' => $smsConfig['engine']['qiniu']['SecretKey'],
                ],
            ]
        ];
    }
}