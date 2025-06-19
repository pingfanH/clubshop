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

use app\common\library\payment\gateway\Driver;
use app\common\library\payment\gateway\driver\wechat\V2;
use app\common\library\payment\gateway\driver\wechat\V3;
use cores\exception\BaseException;

/**
 * 微信支付驱动
 * Class Wechat
 * @package app\common\library\payment\gateway\driver
 */
class Wechat extends Driver
{
    // 当前支付应用
    /* @var $app V2|V3 */
    private $app;

    /**
     * 微信支付驱动 [区分版本v2和v3]
     * @var string[]
     */
    private array $provider = [
        'v2' => V2::class,
        'v3' => V3::class
    ];

    /**
     * 获取微信支付应用
     * @return V2|V3
     */
    private function getApp()
    {
        if (!$this->app) {
            $this->app = new $this->provider[$this->options['version']];
            $this->app->setOptions($this->options, $this->client);
        }
        return $this->app;
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
        if (!$this->getApp()->setOptions($this->options, $this->client)->unify($outTradeNo, $totalFee, $extra)) {
            $this->setError($this->getApp()->getError());
            return false;
        }
        return true;
    }

    /**
     * 交易查询 (主动查询订单支付状态)
     * @param string $outTradeNo 交易订单号
     * @return array|null
     * @throws BaseException
     */
    public function tradeQuery(string $outTradeNo): ?array
    {
        return $this->getApp()->tradeQuery($outTradeNo);
    }

    /**
     * 支付成功后的异步通知
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function notify(): bool
    {
        if (!$this->getApp()->notify()) {
            $this->setError($this->getApp()->getError());
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
        if (!$this->getApp()->refund($outTradeNo, $refundAmount, $extra)) {
            $this->setError($this->getApp()->getError());
            return false;
        }
        return true;
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
        if (!$this->getApp()->transfers($outTradeNo, $totalFee, $extra)) {
            $this->setError($this->getApp()->getError());
            return false;
        }
        return true;
    }

    /**
     * 获取异步回调的请求参数
     * @return array
     */
    public function getNotifyParams(): array
    {
        return $this->getApp()->getNotifyParams();
    }

    /**
     * 返回异步通知结果的输出内容
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function getNotifyResponse(): string
    {
        return $this->getApp()->getNotifyResponse();
    }

    /**
     * 返回统一下单API的结果 (用于前端)
     * @return array
     * @throws BaseException
     */
    public function getUnifyResult(): array
    {
        return $this->getApp()->getUnifyResult();
    }
}