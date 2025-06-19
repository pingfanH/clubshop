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

namespace app\common\library\payment\gateway;

use cores\traits\ErrorTrait;
use cores\exception\BaseException;

/**
 * 第三方支付驱动基类
 * Class Driver
 * @package app\common\library\payment\gateway\driver
 */
abstract class Driver
{
    use ErrorTrait;

    /**
     * 驱动句柄
     * @var Driver|null
     */
    protected ?Driver $handler = null;

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
    public function setOptions(array $options, string $client): ?Driver
    {
        $this->client = $client ?: null;
        $this->options = $options;
        return $this;
    }

    /**
     * 统一下单API
     * @param string $outTradeNo 第三方交易单号
     * @param string $totalFee 实际付款金额
     * @param array $extra 附加的数据 (需要携带openid)
     * @return bool
     * @throws BaseException
     */
    abstract public function unify(string $outTradeNo, string $totalFee, array $extra = []): bool;

    /**
     * 交易查询 (主动查询订单支付状态)
     * @param string $outTradeNo 第三方交易单号
     * @return array|null
     * @throws BaseException
     */
    abstract public function tradeQuery(string $outTradeNo): ?array;

    /**
     * 获取异步回调的请求参数
     * @return array
     */
    abstract public function getNotifyParams(): array;

    /**
     * 返回异步通知结果的输出内容
     * @return mixed
     */
    abstract public function getNotifyResponse();

    /**
     * 统一下单API
     * @param string $outTradeNo 第三方交易单号
     * @param string $refundAmount 退款金额
     * @param array $extra 附加的数据 (需要携带订单付款总金额)
     * @return bool
     * @throws BaseException
     */
    abstract public function refund(string $outTradeNo, string $refundAmount, array $extra = []): bool;
}