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

namespace app\common\library\payment\gateway\driver;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use app\common\enum\Client as ClientEnum;
use app\common\library\Log;
use app\common\library\payment\gateway\Driver;
use cores\exception\BaseException;

/**
 * 微信支付驱动
 * Class Alipay
 * @package app\common\library\payment\gateway\driver
 */
class Alipay extends Driver
{
    // 统一下单API的返回结果
    private $result;

    // 异步通知的请求参数 (由第三方支付发送)
    private $notifyParams;

    // 异步通知的验证结果
    private $notifyResult;

    /**
     * 统一下单API
     * @param string $outTradeNo 交易订单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (需要携带H5端支付成功后跳转的url)
     * @return bool
     * @throws BaseException
     */
    public function unify(string $outTradeNo, string $totalFee, array $extra = []): bool
    {
        try {
            $result = null;
            // 发起API调用 H5端
            if ($this->client === ClientEnum::H5) {
                // https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
                $result = Factory::payment()->wap()->pay(
                    $outTradeNo,
                    $outTradeNo,
                    $totalFee,
                    '',
                    $this->extraAsUnify($extra)['returnUrl']
                );
            }
            // 发起API调用 APP端
            if ($this->client === ClientEnum::APP) {
                $result = Factory::payment()->app()->pay(
                    $outTradeNo,
                    $outTradeNo,
                    $totalFee
                );
            }
            // 处理响应或异常
            empty($result) && $this->throwError('result不存在');
            $responseChecker = new ResponseChecker();
            if (!$responseChecker->success($result)) {
                $this->throwError($result->msg . "，" . $result->subMsg);
            }
            // 记录返回的结果
            $this->result['out_trade_no'] = $outTradeNo;
            if (in_array($this->client, [ClientEnum::H5, ClientEnum::APP])) {
                $this->result['body'] = $result->body;
            }
            // 记录日志
            Log::append('Alipay-unify', ['client' => $this->client, 'result' => $this->result]);
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            $this->throwError('支付宝API下单失败：' . $e->getMessage(), true, 'unify');
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
            // 发起API调用
            // https://opendocs.alipay.com/apis/028pxp
            $result = Factory::payment()->common()->query($outTradeNo);
            // 记录日志
            Log::append('Alipay-tradeQuery', ['outTradeNo' => $outTradeNo, 'result' => $result->toMap()]);
            // 处理响应或异常
            $responseChecker = new ResponseChecker();
            if (!$responseChecker->success($result)) {
                $this->throwError($result->msg . "，" . $result->subMsg);
            }
            // 返回查询成功的结果
            return [
                // 支付状态: true成功 false失败
                'paySuccess' => $result->tradeStatus === 'TRADE_SUCCESS',
                // 第三方交易流水号
                'tradeNo' => $result->tradeNo
            ];
        } catch (\Throwable $e) {
            $this->throwError('支付宝API交易查询失败：' . $e->getMessage(), true, 'tradeQuery');
        }
        return null;
    }

    /**
     * 支付成功后的异步通知
     * @return bool
     */
    public function notify(): bool
    {
        // 接收表单数据
        $this->notifyParams = request()->filter([])->post();
        // 验证异步请求的参数是否合法
        // https://opendocs.alipay.com/open/270/105902
        $verifyNotify = Factory::payment()->common()->verifyNotify($this->notifyParams);
        // 判断交易单状态必须是支付成功
        $this->notifyResult = $verifyNotify && $this->notifyParams['trade_status'] === 'TRADE_SUCCESS';
        // 记录日志
        Log::append('Alipay-notify', [
            'params' => $this->notifyParams,
            'verifyNotify' => $verifyNotify,
            'response' => $this->getNotifyResponse(),
            'result' => $this->notifyResult,
            'message' => '支付宝异步回调验证' . ($this->notifyResult ? '成功' : '失败')
        ]);
        return $this->notifyResult;
    }

    /**
     * 支付宝退款API
     * @param string $outTradeNo 第三方交易单号
     * @param string $refundAmount 退款金额
     * @param array $extra 附加的数据
     * @return bool
     * @throws BaseException
     */
    public function refund(string $outTradeNo, string $refundAmount, array $extra = []): bool
    {
        try {
            // 发起API调用
            // https://opendocs.alipay.com/apis/028xqg
            $outRequestNo = (string)time();
            $result = Factory::payment()->common()->refund($outTradeNo, $refundAmount, $outRequestNo);
            // 记录日志
            Log::append('Alipay-refund', [
                'outTradeNo' => $outTradeNo,
                'refundAmount' => $refundAmount,
                'result' => $result->toMap()
            ]);
            // 处理响应或异常
            empty($result) && $this->throwError('API无返回结果');
            $responseChecker = new ResponseChecker();
            if (!$responseChecker->success($result)) {
                $this->throwError($result->msg . "，" . $result->subMsg);
            }
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            $this->throwError('支付宝API退款请求：' . $e->getMessage(), true, 'refund');
        }
        return false;
    }

    /**
     * 单笔转账接口
     * @param string $outTradeNo 交易订单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (ALIPAY_LOGON_ID支付宝登录号，支持邮箱和手机号格式; name参与方真实姓名)
     * @return bool
     */
    public function transfers(string $outTradeNo, string $totalFee, array $extra = []): bool
    {
        // https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer
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
            'tradeNo' => $this->notifyParams['trade_no']
        ];
    }

    /**
     * 返回异步通知结果的输出内容
     * @return string
     */
    public function getNotifyResponse(): string
    {
        return $this->notifyResult ? 'success' : 'FAIL';
    }

    /**
     * 返回统一下单API的结果 (用于前端)
     * @return array
     * @throws BaseException
     */
    public function getUnifyResult(): array
    {
        if (empty($this->result)) {
            $this->throwError('当前没有unify结果', true, 'getUnifyResult');
        }
        // 整理返回的数据
        $result = ['out_trade_no' => $this->result['out_trade_no']];
        // H5端使用的支付数据
        if ($this->client === ClientEnum::H5) {
            $result['formHtml'] = $this->deleteHtmlTags(['script'], $this->result['body']);
        }
        // APP端使用的支付数据
        if ($this->client === ClientEnum::APP) {
            $result['orderInfo'] = $this->result['body'];
        }
        return $result;
    }

    /**
     * 设置支付宝配置信息（全局只需设置一次）
     * @param array $options 支付宝配置信息
     * @param string $client 下单客户端
     * @return Driver|null
     */
    public function setOptions(array $options, string $client): ?Driver
    {
        $this->client = $client ?: null;
        $Config = new Config();
        $Config->protocol = 'https';
        $Config->gatewayHost = 'openapi.alipay.com';
        $Config->signType = $options['signType'];
        $Config->appId = $options['appId'];
        // 应用私钥
        $Config->merchantPrivateKey = $options['merchantPrivateKey'];
        // # 加签模式为公钥证书模式时（推荐）
        if ($options['signMode'] == 10) {
            $Config->alipayCertPath = $options['alipayCertPublicKeyPath'];
            $Config->alipayRootCertPath = $options['alipayRootCertPath'];
            $Config->merchantCertPath = $options['appCertPublicKeyPath'];
        }
        // # 加签模式为公钥模式时
        // 注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        if ($options['signMode'] == 20) {
            $Config->alipayPublicKey = $options['alipayPublicKey'];
        }
        // 可设置异步通知接收服务地址（可选）
        $Config->notifyUrl = $this->notifyUrl();
        // 可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $Config->encryptKey = "";
        // 设置参数（全局只需设置一次）
        Factory::setOptions($Config);
        return $this;
    }

    /**
     * 输出错误信息
     * @param string $errMessage 错误信息
     * @param bool $isLog 是否记录日志
     * @param string $action 当前的操作
     * @throws BaseException
     */
    private function throwError(string $errMessage, bool $isLog = false, string $action = '')
    {
        $this->error = $errMessage;
        $isLog && Log::append("Alipay-{$action}", ['errMessage' => $errMessage]);
        throwError($errMessage);
    }

    /**
     * 获取和验证下单接口所需的附加数据
     * @param array $extra
     * @return array
     * @throws BaseException
     */
    private function extraAsUnify(array $extra): array
    {
        if ($this->client === ClientEnum::H5) {
            if (!array_key_exists('returnUrl', $extra)) {
                $this->throwError('returnUrl参数不存在');
            }
        }
        return $extra;
    }

    /**
     * 删除HTML中的指定标签
     * @param array $tags
     * @param $string
     * @return array|string|string[]|null
     */
    private function deleteHtmlTags(array $tags, $string)
    {
        $preg = [];
        foreach ($tags as $key => $value) {
            $preg[$key] = "/<({$value}.*?)>(.*?)<(\/{$value}.*?)>/si";
        }
        return preg_replace($preg, '', $string);
    }

    /**
     * 异步回调地址
     * @return string
     */
    private function notifyUrl(): string
    {
        // 例如：https://www.xxxx.com/alipay.php
        return base_url() . 'notice/alipay.php';
    }
}