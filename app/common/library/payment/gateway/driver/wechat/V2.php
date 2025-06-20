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

namespace app\common\library\payment\gateway\driver\wechat;

use EasyWeChat\Factory;
use EasyWeChat\Payment\Notify\Paid;
use app\common\enum\Client as ClientEnum;
use app\common\library\Log;
use app\common\library\helper;
use cores\traits\ErrorTrait;
use cores\exception\BaseException;

/**
 * 微信支付驱动 [V2]
 * Class Wechat
 * @package app\common\library\payment\gateway\driver
 */
class V2
{
    use ErrorTrait;

    // 统一下单API的返回结果
    private array $result;

    // 异步通知的请求参数 (由第三方支付发送)
    private array $notifyParams;

    /**
     * 异步通知回调结果
     * @var Paid $notifyPaid
     */
    private Paid $notifyPaid;

    /**
     * 支付的客户端
     * @var string|null
     */
    protected ?string $client = null;

    /**
     * 支付配置参数
     * @var array
     */
    protected array $options = [];

    /**
     * 设置支付配置参数
     * @param array $options 配置信息
     * @param string $client 下单客户端
     * @return static|null
     */
    public function setOptions(array $options, string $client): ?V2
    {
        $this->client = $client ?: null;
        $this->options = $options;
        return $this;
    }

    /**
     * 统一下单API
     * @param string $outTradeNo 交易订单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (需要携带openid)
     * @return bool
     * @throws BaseException
     */
    public function unify(string $outTradeNo, string $totalFee, array $extra = []): bool
    {
        // 下单的参数
        $params = [
            'body' => $outTradeNo,
            'out_trade_no' => $outTradeNo,
            'total_fee' => (int)helper::bcmul($totalFee, 100),
            'notify_url' => $this->notifyUrl(), // 支付结果异步通知地址
            'trade_type' => $this->tradeType(),
        ];
        // 用户的openid (只有JSAPI支付时需要)
        if ($this->tradeType() === 'JSAPI') {
            $params[$this->isProvider() ? 'sub_openid' : 'openid'] = $extra['openid'];
        }
        try {
            // 实例化微信支付sdk
            $app = $this->getApp();
            // 统一下单API
            // https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1
            $unifyResult = $app->order->unify($params);
            // 判断请求是否失败
            $this->resultError($unifyResult);
            // 记录统一下单api返回的数据
            $this->result = $unifyResult;
            // 生成app支付的配置
            if ($this->client === ClientEnum::APP) {
                $this->result = $app->jssdk->appConfig($unifyResult['prepay_id']);
            }
            // 生成jssdk支付的配置
            if (in_array($this->client, [ClientEnum::MP_WEIXIN])) {
                $this->result = $app->jssdk->bridgeConfig($unifyResult['prepay_id'], false);
            }
            // 记录商户订单号
            $this->result['out_trade_no'] = $outTradeNo;
            // 记录日志
            Log::append('Wechat-unify', [
                'client' => $this->client,
                'params' => $params,
                'extra' => $extra,
                'result' => $this->result
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->throwError('unify', '微信支付API下单失败：' . $e->getMessage());
        }
        return false;
    }

    /**
     * 交易查询 (主动查询订单支付状态)
     * @param string $outTradeNo 交易订单号
     * @return array|null
     * @throws BaseException
     */
    public function tradeQuery(string $outTradeNo): ?array
    {
        try {
            // 微信支付订单的查询
            // https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_2&index=2
            $result = $this->getApp()->order->queryByOutTradeNumber($outTradeNo);
            // 判断请求是否失败
            $this->resultError($result);
            // 记录日志
            Log::append('Wechat-tradeQuery', ['outTradeNo' => $outTradeNo, 'result' => $result]);
            // 判断订单支付成功
            return [
                // 支付状态: true成功 false失败
                'paySuccess' => $result['trade_state'] === 'SUCCESS',
                // 第三方交易流水号
                'tradeNo' => $result['transaction_id'] ?? ''
            ];
        } catch (\Throwable $e) {
            $this->throwError('tradeQuery', '微信支付交易查询失败：' . $e->getMessage());
        }
        return null;
    }

    /**
     * 支付成功后的异步通知
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function notify(): bool
    {
        // 异步通知管理类
        $this->notifyPaid = new Paid($this->getApp());
        try {
            // 异步通知的数据
            $this->notifyParams = $this->notifyPaid->getMessage();
            // 记录日志
            Log::append('Wechat-notify', ['params' => $this->notifyParams]);
            // 通信状态是否成功
            if ($this->notifyParams['return_code'] !== 'SUCCESS') {
                $this->notifyPaidError('通信失败');
                return false;
            }
            // 用户支付失败
            if ($this->notifyParams['result_code'] !== 'SUCCESS') {
                $this->notifyPaidError('订单支付失败', false);
                return false;
            }
            // 记录日志
            Log::append('Wechat-notify', ['message' => '微信异步回调验证成功', 'notifyParams' => $this->notifyParams]);
        } catch (\EasyWeChat\Payment\Kernel\Exceptions\InvalidSignException $e) {
            // 签名验证错误
            $this->notifyPaidError('签名验证错误');
            return false;
        } catch (\Throwable $e) {
            // 其他异常
            $this->notifyPaidError('异步通知错误：' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 微信支付退款API
     * @param string $outTradeNo 第三方交易单号
     * @param string $refundAmount 退款金额
     * @param array $extra 附加数据 (需要携带订单付款总金额)
     * @return bool
     * @throws BaseException
     */
    public function refund(string $outTradeNo, string $refundAmount, array $extra = []): bool
    {
        try {
            // 微信支付订单退款
            // https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_4&index=4
            $refundNumber = time() . '-' . uniqid();
            $result = $this->getApp()->refund->byOutTradeNumber(
                $outTradeNo,
                $refundNumber,
                (int)helper::bcmul($extra['totalFee'], 100),
                (int)helper::bcmul($refundAmount, 100)
            );
            // 判断请求是否失败
            $this->resultError($result);
            // 记录日志
            Log::append('Wechat-refund', [
                'outTradeNo' => $outTradeNo,
                'refundAmount' => $refundAmount,
                'result' => $result
            ]);
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            $this->throwError('refund', '微信退款api请求失败：' . $e->getMessage());
        }
        return false;
    }

    /**
     * 企业付款到零钱API [已废弃]
     * @param string $outTradeNo 交易订单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (需要携带openid、desc)
     * @return bool
     * @throws BaseException
     */
    public function transfers(string $outTradeNo, string $totalFee, array $extra = []): bool
    {
        try {
            // 请求的参数
            $params = [
                'partner_trade_no' => $outTradeNo, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
                'openid' => $extra['openid'],   // 用户的openid
                'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
                // 're_user_name' => '王小帅', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
                'amount' => (int)helper::bcmul($totalFee, 100), // 企业付款金额，单位为分
                'desc' => $extra['desc'], // 企业付款操作说明信息。必填
            ];
            // 微信支付订单退款
            // https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
            $result = $this->getApp()->transfer->toBalance($params);
            // 判断请求是否失败
            $this->resultError($result);
            // 记录日志
            Log::append('Wechat-transfers', ['outTradeNo' => $outTradeNo, 'result' => $result]);
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            $this->throwError('transfers', '企业付款到零钱api请求失败：' . $e->getMessage());
        }
        return false;
    }

    /**
     * 获取异步回调的请求参数
     * @return array
     */
    public function getNotifyParams(): array
    {
        return [
            // 第三方交易流水号
            'tradeNo' => $this->notifyParams['transaction_id']
        ];
    }

    /**
     * 返回异步通知结果的输出内容
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function getNotifyResponse(): string
    {
        return $this->notifyPaid->toResponse()->getContent();
    }

    /**
     * 返回统一下单API的结果 (用于前端)
     * @return array
     * @throws BaseException
     */
    public function getUnifyResult(): array
    {
        if (empty($this->result)) {
            $this->throwError('getUnifyResult', '当前没有unify结果');
        }
        // 允许输出的字段 (防止泄露敏感信息)
        $result = helper::pick($this->result, [
            'out_trade_no',
            'nonce_str', 'prepay_id', 'sign', 'trade_type', 'mweb_url',
            'appid', 'partnerid', 'noncestr', 'prepayid', 'timestamp', 'package', 'sign',
            'appId', 'timeStamp', 'nonceStr', 'package', 'signType', 'paySign',
        ]);
        // 当前的时间戳
        $result['time_stamp'] = (string)time();
        return $result;
    }

    /**
     * 设置异步通知的错误信息
     * @param string $error 错误信息
     * @param bool $outputFail 是否输出fail信息 (会使微信服务器重复发起通知)
     */
    private function notifyPaidError(string $error, bool $outputFail = true)
    {
        $this->error = $error;
        $outputFail && $this->notifyPaid->fail($error);
        Log::append('Wechat-notify', ['errMessage' => $error]);
    }

    /**
     * 输出错误信息
     * @param string $action 当前的操作
     * @param string $errMessage 错误信息
     * @throws BaseException
     */
    private function throwError(string $action, string $errMessage)
    {
        $this->error = $errMessage;
        Log::append("Wechat-{$action}", ['errMessage' => $errMessage]);
        throwError($errMessage);
    }

    /**
     * 根据客户端选择对应的微信支付方式
     * @return string
     * @throws BaseException
     */
    private function tradeType(): string
    {
        $tradeTypes = [
            ClientEnum::H5 => 'MWEB',
            ClientEnum::MP_WEIXIN => 'JSAPI',
            ClientEnum::APP => 'APP'
        ];
        if (!isset($tradeTypes[$this->client])) {
            $this->throwError('tradeType', '未找到当前客户端适配的微信支付方式');
        }
        return $tradeTypes[$this->client];
    }

    /**
     * 请求错误时错误信息
     * @throws BaseException
     */
    private function resultError(array $result)
    {
        // 无请求结果
        empty($result) && throwError('API无返回结果');
        // 请求失败
        if ($result['return_code'] === 'FAIL') {
            $this->throwError('resultError', $result['return_msg'] ?: 'return_code 未知错误');
        }
        if ($result['result_code'] === 'FAIL') {
            $this->throwError('resultError', $result['err_code_des'] ?: 'result_code 未知错误');
        }
    }

    /**
     * 获取微信支付应用类
     * @return \EasyWeChat\Payment\Application
     */
    private function getApp(): \EasyWeChat\Payment\Application
    {
        $config = $this->getConfig();
        return Factory::payment($config);
    }

    /**
     * 构建微信支付配置
     * @return string[]
     */
    private function getConfig(): array
    {
        if ($this->isProvider()) {
            $config = [
                'app_id' => $this->options['provider']['spAppId'],
                'mch_id' => $this->options['provider']['spMchId'],
                'key' => $this->options['provider']['spApiKey'],
                'cert_path' => $this->options['provider']['spApiclientCertPath'],
                'key_path' => $this->options['provider']['spApiclientKeyPath'],
                'sub_mch_id' => $this->options['provider']['subMchId'],
                'sub_appid' => $this->options['provider']['subAppId'],
            ];
        } else {
            $config = [
                'app_id' => $this->options['normal']['appId'],
                'mch_id' => $this->options['normal']['mchId'],
                'key' => $this->options['normal']['apiKey'],
                'cert_path' => $this->options['normal']['apiclientCertPath'],
                'key_path' => $this->options['normal']['apiclientKeyPath'],
            ];
        }
        // 微信支付异步回调地址
        // $config['notify_url'] = $this->notifyUrl();
        return $config;
    }

    /**
     * 异步回调地址
     * @return string
     */
    private function notifyUrl(): string
    {
        // 例如：https://www.xxxx.com/wxpayV2.php
        return base_url() . 'notice/wxpayV2.php';
    }

    /**
     * 当前是否为服务商模式
     * @return bool
     */
    private function isProvider(): bool
    {
        return $this->options['mchType'] === 'provider';
    }
}