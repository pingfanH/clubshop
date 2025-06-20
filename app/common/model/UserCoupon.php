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
use app\common\library\helper;
use app\common\model\Coupon as CouponModel;
use app\common\enum\coupon\ExpireType as ExpireTypeEnum;
use think\model\relation\BelongsTo;

/**
 * 用户优惠券模型
 * Class UserCoupon
 * @package app\common\model
 */
class UserCoupon extends BaseModel
{
    // 定义表名
    protected $name = 'user_coupon';

    // 定义主键
    protected $pk = 'user_coupon_id';

    /**
     * 追加字段
     * @var array
     */
    protected $append = ['state'];

    /**
     * 关联用户表
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('User');
    }

    /**
     * 优惠券状态
     * @param $value
     * @param $data
     * @return array
     */
    public function getStateAttr($value, $data): array
    {
        if ($data['is_use']) {
            return ['text' => '已使用', 'value' => 0];
        }
        if ($data['is_expire']) {
            return ['text' => '已过期', 'value' => 0];
        }
        return ['text' => '正常', 'value' => 1];
    }

    /**
     * 获取器：格式化折扣率
     * @param $value
     * @return float|int
     */
    public function getDiscountAttr($value)
    {
        return $value / 10;
    }

    /**
     * 有效期-开始时间
     * @param $value
     * @return string
     */
    public function getStartTimeAttr($value): string
    {
        return date('Y/m/d', $value);
    }

    /**
     * 有效期-结束时间
     * @param $value
     * @return string
     */
    public function getEndTimeAttr($value): string
    {
        return date('Y/m/d', $value);
    }

    /**
     * 获取器：适用范围配置
     * @param $value
     * @return mixed
     */
    public function getApplyRangeConfigAttr($value)
    {
        $config = $value ? helper::jsonDecode($value) : [];
        // 兼容历史数据 v2.4.6之前
        if (!empty($config['applyGoodsIds'])) {
            $config['goodsIds'] = $config['applyGoodsIds'];
            unset($config['applyGoodsIds']);
        }
        return $config;
    }

    /**
     * 修改器：格式化折扣率
     * @param $value
     * @return float|int
     */
    public function setDiscountAttr($value)
    {
        return $value * 10;
    }

    /**
     * 修改器：适用范围配置
     * @param $array
     * @return mixed
     */
    public function setApplyRangeConfigAttr($array)
    {
        return helper::jsonEncode($array);
    }

    /**
     * 优惠券详情
     * @param $couponId
     * @return static|array|null
     */
    public static function detail($couponId)
    {
        return static::get($couponId);
    }

    /**
     * 设置优惠券使用状态
     * @param int $userCouponId 用户的优惠券ID
     * @param bool $isUse 是否已使用
     * @return bool|false
     */
    public static function setIsUse(int $userCouponId, bool $isUse = true): bool
    {
        return static::updateBase(['is_use' => (int)$isUse], $userCouponId);
    }

    /**
     * 验证指定用户是否已领取优惠券
     * @param int $couponId 优惠券ID
     * @param int $userId 用户ID
     * @return bool
     */
    public static function checktUserCoupon(int $couponId, int $userId): bool
    {
        return (bool)(new static)->where('coupon_id', '=', $couponId)
            ->where('user_id', '=', $userId)
            ->value('user_coupon_id');
    }

    /**
     * 添加领取记录
     * @param int $userId 用户ID
     * @param CouponModel $couponInfo
     * @return bool
     */
    public function add(int $userId, CouponModel $couponInfo): bool
    {
        // 计算有效期
        if ($couponInfo['expire_type'] == ExpireTypeEnum::RECEIVE) {
            $startTime = time();
            $endTime = $startTime + ($couponInfo['expire_day'] * 86400);
        } else {
            $startTime = $couponInfo->getData('start_time');
            $endTime = $couponInfo->getData('end_time');
        }
        // 整理领取记录
        $data = [
            'coupon_id' => $couponInfo['coupon_id'],
            'name' => $couponInfo['name'],
            'coupon_type' => $couponInfo['coupon_type'],
            'reduce_price' => $couponInfo['reduce_price'],
            'discount' => $couponInfo['discount'],
            'min_price' => $couponInfo['min_price'],
            'expire_type' => $couponInfo['expire_type'],
            'expire_day' => $couponInfo['expire_day'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'apply_range' => $couponInfo['apply_range'],
            'apply_range_config' => $couponInfo['apply_range_config'],
            'describe' => $couponInfo['describe'],
            'user_id' => $userId,
            'store_id' => self::$storeId
        ];
        // 事务处理
        return $this->transaction(function () use ($data, $couponInfo) {
            // 添加领取记录
            $this->save($data);
            // 累计优惠券已领取的数量
            CouponModel::setIncReceiveNum($couponInfo['coupon_id']);
            return true;
        });
    }
}
