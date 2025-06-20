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

use WeChatPay\Builder;
use WeChatPay\Formatter;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Util\PemUtil;
use app\common\library\Log;
use app\common\library\helper;
use app\common\enum\Client as ClientEnum;
use cores\traits\ErrorTrait;
use cores\exception\BaseException;
use Psr\Http\Message\ResponseInterface;

/**
 * 微信支付驱动 [V3]
 * Class Wechat
 * @package app\common\library\payment\gateway\driver
 */
class V3
{
    use ErrorTrait;

    /**
     * 支付的客户端
     * @var string|null
     */
    protected ?string $client = null;

    /**
     * 支付配置参数
     * @var array
     */
    protected array $config = [];

    // 统一下单API的返回结果
    private array $result;

    // 异步通知的请求参数 (由第三方支付发送)
    private array $notifyParams;

    /**
     * 设置支付配置参数
     * @param array $options 配置信息
     * @param string $client 下单客户端
     * @return static|null
     */
    public function setOptions(array $options, string $client): ?V3
    {
        $this->client = $client ?: null;
        $this->config = $this->getConfig($options);
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
            'out_trade_no' => $outTradeNo,
            'description' => '线上商城商品',
            'notify_url' => $this->notifyUrl(), // 支付结果异步通知地址
            'amount' => ['total' => (int)helper::bcmul($totalFee, 100), 'currency' => 'CNY'],
            'scene_info' => ['payer_client_ip' => \request()->ip()]
        ];
        // 普通商户参数和服务商支付参数
        if ($this->isProvider()) {
            $params['sp_appid'] = $this->config['app_id'];
            $params['sp_mchid'] = $this->config['mch_id'];
            $params['sub_appid'] = $this->config['sub_appid'];
            $params['sub_mchid'] = $this->config['sub_mchid'];
        } else {
            $params['appid'] = $this->config['app_id'];
            $params['mchid'] = $this->config['mch_id'];
        }
        // 用户的openid (只有JSAPI支付时需要)
        if ($this->tradeType() === 'jsapi') {
            $params['payer'][$this->isProvider() ? 'sub_openid' : 'openid'] = $extra['openid'];
        }
        // H5info
        if ($this->tradeType() === 'h5') {
            $params['scene_info']['h5_info'] = ['type' => 'Wap'];
        }
        try {
            // 统一下单API
            // Doc: https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
            $resp = $this->getApp()
                ->chain($this->getUnifyApiUrl())
                ->post(['json' => $params]);
            // 记录api返回的数据
            $unifyResult = helper::jsonDecode((string)$resp->getBody());
            $this->result = $unifyResult;
            // 生成app支付的配置
            if ($this->client === ClientEnum::APP) {
                $this->result = $this->appConfig($unifyResult['prepay_id']);
            }
            // 生成jssdk支付的配置
            if (in_array($this->client, [ClientEnum::MP_WEIXIN])) {
                $this->result = $this->bridgeConfig($unifyResult['prepay_id']);
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
            // 异常处理
            $message = $this->getThrowMessage($e);
            $this->throwError('unify', "微信支付API下单失败：{$message}");
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
        // 下单的参数
        $params = [];
        // 普通商户参数和服务商支付参数
        if ($this->isProvider()) {
            $params['sp_mchid'] = $this->config['mch_id'];
            $params['sub_mchid'] = $this->config['sub_mchid'];
        } else {
            $params['mchid'] = $this->config['mch_id'];
        }
        try {
            // 订单查询API
            // Doc: https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_2.shtml
            $resp = $this->getApp()
                ->chain($this->getTradeApiUrl($outTradeNo))
                ->get(['query' => $params]);
            // 记录api返回的数据
            $result = helper::jsonDecode((string)$resp->getBody());
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
            // 异常处理
            $message = $this->getThrowMessage($e);
            $this->throwError('tradeQuery', "微信支付交易查询失败：{$message}");
        }
        return null;
    }

    /**
     * 支付成功后的异步通知
     * @param string $apiV3Key 微信支付v3秘钥
     * @param string $platformCertificateOrPublicKeyFilePath 微信支付公钥或平台证书路径
     * @return bool|string
     */
    public function notify(string $apiV3Key, string $platformCertificateOrPublicKeyFilePath)
    {
        // 微信异步通知参数
        $header = \request()->header();
        $inBody = file_get_contents('php://input');
        // 微信支付平台证书
        $platformPublicKeyInstance = Rsa::from("file://{$platformCertificateOrPublicKeyFilePath}", Rsa::KEY_TYPE_PUBLIC);
        // 检查通知时间偏移量，允许5分钟之内的偏移
        // $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $timeOffsetStatus = true;
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($header['wechatpay-timestamp'], $header['wechatpay-nonce'], $inBody),
            $header['wechatpay-signature'],
            $platformPublicKeyInstance
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiV3Key, $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $this->notifyParams = helper::jsonDecode($inBodyResource);
            // 记录日志
            Log::append('Wechat-notify', ['message' => '微信异步回调验证成功', 'notifyParams' => $this->notifyParams]);
            return true;
        }
        return false;
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
        // 下单的参数
        $params = [
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => time() . '-' . uniqid(),
            'amount' => [
                'refund' => (int)helper::bcmul($refundAmount, 100),
                'total' => (int)helper::bcmul($extra['totalFee'], 100),
                'currency' => 'CNY',
            ],
        ];
        // 普通商户参数和服务商支付参数
        if ($this->isProvider()) {
            $params['sub_mchid'] = $this->config['sub_mchid'];
        }
        try {
            // 申请退款API
            // Doc: https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_3_9.shtml
            $resp = $this->getApp()
                ->chain($this->getRefundApiUrl())
                ->post(['json' => $params]);
            // 记录api返回的数据
            $result = helper::jsonDecode((string)$resp->getBody());
            // 记录日志
            Log::append('Wechat-refund', [
                'outTradeNo' => $outTradeNo,
                'refundAmount' => $refundAmount,
                'result' => $result
            ]);
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            // 异常处理
            $message = $this->getThrowMessage($e);
            $this->throwError('tradeQuery', "微信退款api请求失败：{$message}");
        }
        return false;
    }

    /**
     * 商家转账到零钱API
     * @param string $outTradeNo 交易订单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (需要携带openid、desc)
     * @return bool
     * @throws BaseException
     */
    public function transfers(string $outTradeNo, string $totalFee, array $extra = []): bool
    {
        // 下单的参数
        $params = [
            'appid' => $this->config['app_id'],
            'out_batch_no' => $outTradeNo,
            'batch_name' => $extra['desc'],
            'batch_remark' => $extra['desc'],
            'total_amount' => (int)helper::bcmul($totalFee, 100), // 转账金额，单位：分
            'total_num' => 1,   // 转账总笔数
            'transfer_detail_list' => [
                [
                    'out_detail_no' => time() . uniqid(),
                    'transfer_amount' => (int)helper::bcmul($totalFee, 100),
                    'transfer_remark' => $extra['desc'],
                    'openid' => $extra['openid'],
                ]
            ]
        ];
        try {
            // 商家转账到零钱API
            // Doc: https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml
            $resp = $this->getApp()
                ->chain($this->getTransfersUrl())
                ->post(['json' => $params]);
            // 记录api返回的数据
            $result = helper::jsonDecode((string)$resp->getBody());
            // 记录日志
            Log::append('Wechat-transfers', ['outTradeNo' => $outTradeNo, 'result' => $result]);
            // 请求成功
            return true;
        } catch (\Throwable $e) {
            // 异常处理
            $message = $this->getThrowMessage($e);
            $this->throwError('transfers', "商家转账到零钱api请求失败：{$message}");
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
        return 'SUCCESS';
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
            'nonce_str', 'prepay_id', 'sign', 'trade_type', 'mweb_url', 'h5_url',
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
            ClientEnum::H5 => 'h5',
            ClientEnum::MP_WEIXIN => 'jsapi',
            ClientEnum::APP => 'app'
        ];
        if (!isset($tradeTypes[$this->client])) {
            $this->throwError('tradeType', '未找到当前客户端适配的微信支付方式');
        }
        return $tradeTypes[$this->client];
    }

    /**
     * 请求错误时错误信息
     */
    private function resultError(ResponseInterface $resp)
    {

    }

    /**
     * 获取微信支付应用类
     * @return \WeChatPay\BuilderChainable
     * @throws BaseException
     */
    private function getApp(): \WeChatPay\BuilderChainable
    {
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyInstance = $this->getMerchantPrivateKeyInstance();
        // 从本地文件中加载「微信支付平台证书」或者「微信支付平台公钥」，用来验证微信支付应答的签名
        $platformCertificateOrPublicKeyFilePath = $this->getPlatformCertificateOrPublicKeyFilePath();
        try {
            $platformPublicKeyInstance = Rsa::from("file://{$platformCertificateOrPublicKeyFilePath}", Rsa::KEY_TYPE_PUBLIC);
        } catch (\UnexpectedValueException $e) {
            $platformPublicKeyInstance = null;
            throwError('平台证书文件或微信支付平台公钥不正确');
        }
        // 「平台证书序列号」及/或「平台公钥ID」
        $platformCertificateSerialOrPublicKeyId = $this->platformCertificateSerialOrPublicKeyId($platformCertificateOrPublicKeyFilePath);
        // 构造一个 APIv3 客户端实例
        return Builder::factory([
            // 微信支付商户号
            'mchid' => $this->config['mch_id'],
            // 「商户API证书」的「证书序列号」
            'serial' => $this->serialno($this->config['cert_path']),
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerialOrPublicKeyId => $platformPublicKeyInstance,
            ],
        ]);
    }

    /**
     * 获取「微信支付平台证书」或者「微信支付平台公钥」的路径
     * @return string
     */
    private function getPlatformCertificateOrPublicKeyFilePath(): string
    {
        return $this->config['signature_method'] == 'publicKey' ? $this->config['public_key_path']
            : $this->config['platform_cert_path'];
    }

    /**
     * 获取「平台证书序列号」及/或「平台公钥ID」
     * @param string $platformCertificateOrPublicKeyFilePath 「微信支付平台证书」或者「微信支付平台公钥」的路径
     * @return mixed|string
     */
    private function platformCertificateSerialOrPublicKeyId(string $platformCertificateOrPublicKeyFilePath)
    {
        return $this->config['signature_method'] == 'publicKey' ? $this->config['public_key_id']
            : PemUtil::parseCertificateSerialNo("file://{$platformCertificateOrPublicKeyFilePath}");
    }

    /**
     * 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
     * @return mixed|\OpenSSLAsymmetricKey|resource
     * @throws BaseException
     */
    private function getMerchantPrivateKeyInstance()
    {
        try {
            return Rsa::from("file://{$this->config['key_path']}", Rsa::KEY_TYPE_PRIVATE);
        } catch (\UnexpectedValueException $e) {
            throwError('证书文件(KEY)不正确');
        }
    }

    /**
     * 读取公钥中的序列号
     * @param string $publicKey
     * @return mixed
     * @throws BaseException
     */
    private function serialno(string $publicKey)
    {
        $content = file_get_contents($publicKey);
        $plaintext = !empty($content) ? openssl_x509_parse($content) : false;
        empty($plaintext) && throwError('证书文件(CERT)不正确');
        return $plaintext['serialNumberHex'];
    }

    /**
     * 构建微信支付配置
     * @return string[]
     */
    private function getConfig($options): array
    {
        if ($options['mchType'] === 'provider') {
            return [
                'mch_type' => 'provider',
                'app_id' => $options['provider']['spAppId'],
                'mch_id' => $options['provider']['spMchId'],
                'key' => $options['provider']['spApiKey'],
                'cert_path' => $options['provider']['spApiclientCertPath'],
                'key_path' => $options['provider']['spApiclientKeyPath'],
                'sub_mchid' => $options['provider']['subMchId'],
                'sub_appid' => $options['provider']['subAppId'],
                'signature_method' => $options['provider']['spSignatureMethod'],
                'public_key_id' => $options['provider']['spPublicKeyId'] ?? '',
                'public_key_path' => $options['provider']['spPublicKeyPath'] ?? '',
                'platform_cert_path' => $options['provider']['platformCertPath'] ?? ''
            ];
        } else {
            return [
                'mch_type' => 'normal',
                'app_id' => $options['normal']['appId'],
                'mch_id' => $options['normal']['mchId'],
                'key' => $options['normal']['apiKey'],
                'cert_path' => $options['normal']['apiclientCertPath'],
                'key_path' => $options['normal']['apiclientKeyPath'],
                'signature_method' => $options['normal']['signatureMethod'],
                'public_key_id' => $options['normal']['publicKeyId'] ?? '',
                'public_key_path' => $options['normal']['publicKeyPath'] ?? '',
                'platform_cert_path' => $options['normal']['platformCertPath'] ?? ''
            ];
        }
    }

    /**
     * 异步回调地址
     * @return string
     */
    private function notifyUrl(): string
    {
        // 例如：https://www.xxxx.com/wxpayV3.php
        return base_url() . 'notice/wxpayV3.php';
    }

    /**
     * 当前是否为服务商模式
     * @return bool
     */
    private function isProvider(): bool
    {
        return $this->config['mch_type'] === 'provider';
    }

    /**
     * Generate app payment parameters.
     * @param string $prepayId
     * @return array
     * @throws BaseException
     */
    private function appConfig(string $prepayId): array
    {
        $params = [
            'appid' => $this->config['app_id'],
            'timestamp' => (string)Formatter::timestamp(),
            'noncestr' => Formatter::nonce(),
            'prepayid' => $prepayId,
        ];
        return \array_merge($params, [
            'sign' => Rsa::sign(
                Formatter::joinedByLineFeed(...array_values($params)),
                $this->getMerchantPrivateKeyInstance()
            ),
            'partnerid' => $this->config['mch_id'],
            'package' => 'Sign=WXPay',
        ]);
    }

    /**
     * [WeixinJSBridge] Generate js config for payment.
     *
     * <pre>
     * WeixinJSBridge.invoke(
     *  'getBrandWCPayRequest',
     *  ...
     * );
     * </pre>
     *
     * @param string $prepayId
     * @return string|array
     * @throws BaseException
     */
    private function bridgeConfig(string $prepayId)
    {
        $params = [
            'appId' => $this->isProvider() ? $this->config['sub_appid'] : $this->config['app_id'],
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr' => Formatter::nonce(),
            'package' => "prepay_id=$prepayId",
        ];
        $params += ['paySign' => Rsa::sign(
            Formatter::joinedByLineFeed(...array_values($params)),
            $this->getMerchantPrivateKeyInstance()
        ), 'signType' => 'RSA'];
        return $params;
    }

    /**
     * 处理API的异常
     * @param \Throwable $e
     * @return mixed|string
     */
    private function getThrowMessage(\Throwable $e)
    {
        $message = $e->getMessage();
        if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
            $body = (string)$e->getResponse()->getBody();
            if (!empty($body)) {
                $result = helper::jsonDecode($body);
                isset($result['message']) && $message = $result['message'];
            }
        }
        return $message;
    }

    /**
     * 统一下单API的Url [需判断是否为服务商支付以及客户端]
     * @return string
     * @throws BaseException
     */
    private function getUnifyApiUrl(): string
    {
        $partnerNodo = $this->isProvider() ? 'partner/' : '';
        return "v3/pay/{$partnerNodo}transactions/" . $this->tradeType();
    }

    /**
     * 订单查询API的Url [需判断是否为服务商支付以及客户端]
     * @param string $outTradeNo
     * @return string
     */
    private function getTradeApiUrl(string $outTradeNo): string
    {
        $partnerNodo = $this->isProvider() ? 'partner/' : '';
        return "v3/pay/{$partnerNodo}transactions/out-trade-no/{$outTradeNo}";
    }

    /**
     * 申请退款API的Url
     * @return string
     */
    private function getRefundApiUrl(): string
    {
        return 'v3/refund/domestic/refunds';
    }

    /**
     * 商家转账到零钱API的Url
     * @return string
     */
    private function getTransfersUrl(): string
    {
        return 'v3/transfer/batches';
    }
}