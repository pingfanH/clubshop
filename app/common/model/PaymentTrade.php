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

namespace app\common\model;

use cores\BaseModel;
use cores\exception\BaseException;
use app\common\enum\payment\trade\TradeStatus as TradeStatusEnum;

/**
 * 模型类：第三方支付交易记录
 * Class PaymentTrade
 * @package app\common\model
 */
class PaymentTrade extends BaseModel
{
    // 定义表名
    protected $name = 'payment_trade';

    // 定义主键
    protected $pk = 'trade_id';

    /**
     * 交易记录详情
     * @param $where
     * @return static|array|null
     */
    public static function detail($where)
    {
        return static::get($where);
    }

    /**
     * 查询第三方支付交易记录详情
     * @param string $outTradeNo 交易订单号
     * @return static|array|null
     * @throws BaseException
     */
    public static function detailByOutTradeNo(string $outTradeNo)
    {
        $detail = static::detail(['out_trade_no' => $outTradeNo]);
        if (empty($detail)) {
            throwError("第三方支付交易记录不存在 {$outTradeNo}");
        }
        return $detail;
    }

    /**
     * 将第三方交易记录更新为已支付状态
     * @param int $tradeId 交易记录ID
     * @param string $tradeNo 第三方交易流水号
     * @return bool
     */
    public static function updateToPaySuccess(int $tradeId, string $tradeNo): bool
    {
        return static::updateBase([
            'trade_no' => $tradeNo,
            'trade_state' => TradeStatusEnum::SUCCESS
        ], $tradeId);
    }

    /**
     * 将第三方交易记录更新为已退款状态
     * @param int $tradeId 交易记录ID
     * @return bool
     */
    public static function updateToRefund(int $tradeId): bool
    {
        return static::updateBase(['trade_state' => TradeStatusEnum::REFUND], $tradeId);
    }
}