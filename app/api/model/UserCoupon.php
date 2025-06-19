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

use app\api\service\User as UserService;
use app\api\model\Coupon as CouponModel;
use app\common\library\Log;
use app\common\model\UserCoupon as UserCouponModel;
use app\common\enum\coupon\CouponType as CouponTypeEnum;
use app\common\enum\coupon\ApplyRange as ApplyRangeEnum;
use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 用户优惠券模型
 * Class UserCoupon
 * @package app\api\model
 */
class UserCoupon extends UserCouponModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'store_id',
        'create_time',
        'update_time'
    ];

    /**
     * 获取用户优惠券列表 (分页)
     * @param int $userId
     * @param array $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(int $userId, array $param): \think\Paginator
    {
        $filter = $this->getFilter($param);
        return $this->where($filter)
            ->where('user_id', '=', $userId)
            ->paginate(15);
    }

    /**
     * 获取用户优惠券列表 (全部)
     * @param int $userId
     * @param array $param
     * @return UserCoupon[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAllList(int $userId, array $param)
    {
        $model = new static;
        $filter = $model->getFilter($param);
        return $model->where($filter)
            ->where('user_id', '=', $userId)
            ->select();
    }

    /**
     * 检索查询条件
     * @param array $param
     * @return array
     */
    private function getFilter(array $param = []): array
    {
        // 设置默认查询参数
        $params = $this->setQueryDefaultValue($param, [
            'dataType' => 'all',     // all:全部 isUsable:可用的 isExpire:已过期 isUse:已使用
            'amount' => null,        // 订单消费金额
        ]);
        // 检索列表类型
        $filter = [];
        // 可用的优惠券
        if ($params['dataType'] === 'isUsable') {
            $filter[] = ['is_use', '=', 0];
            $filter[] = ['is_expire', '=', 0];
            $filter[] = ['start_time', '<=', time()];
            $filter[] = ['end_time', '>', time()];
        }
        // 未使用的优惠券
        if ($params['dataType'] === 'isUnused') {
            $filter[] = ['is_use', '=', 0];
            $filter[] = ['is_expire', '=', 0];
            $filter[] = ['end_time', '>', time()];
        }
        // 已过期的优惠券
        if ($params['dataType'] === 'isExpire') {
            $filter[] = ['is_expire', '=', 1];
        }
        // 已使用的优惠券
        if ($params['dataType'] === 'isUse') {
            $filter[] = ['is_use', '=', 1];
        }
        // 订单消费金额
        $params['amount'] > 0 && $filter[] = ['min_price', '<=', $params['amount']];
        return $filter;
    }

    /**
     * 获取用户优惠券总数量(可用)
     * @param int $userId
     * @return int
     */
    public function getCount(int $userId): int
    {
        return $this->where('user_id', '=', $userId)
            ->where('is_use', '=', 0)
            ->where('is_expire', '=', 0)
            ->where('end_time', '>', time())
            ->count();
    }

    /**
     * 获取用户优惠券ID集
     * @param int $userId
     * @return array
     */
    public function getUserCouponIds(int $userId): array
    {
        return $this->where('user_id', '=', $userId)->column('coupon_id');
    }

    /**
     * 领取优惠券
     * @param int $couponId 优惠券ID
     * @return bool
     * @throws BaseException
     */
    public function receive(int $couponId): bool
    {
        // 当前用户ID
        $userId = UserService::getCurrentLoginUserId(true);
        // 获取优惠券信息
        $couponInfo = CouponModel::detail($couponId);
        // 验证优惠券是否可领取
        if (!$this->checkReceive($userId, $couponInfo)) {
            return false;
        }
        // 添加领取记录
        return $this->add($userId, $couponInfo);
    }

    /**
     * 验证优惠券是否可领取
     * @param int $userId 当前用户ID
     * @param CouponModel $couponInfo 优惠券详情
     * @return bool
     */
    private function checkReceive(int $userId, CouponModel $couponInfo): bool
    {
        // 验证优惠券状态是否可领取
        $model = new CouponModel;
        if (!$model->checkReceive($couponInfo)) {
            $this->error = $model->getError() ?: '优惠券状态不可领取';
            return false;
        }
        // 验证当前用户是否已领取
        if (static::checktUserCoupon($couponInfo['coupon_id'], $userId)) {
            $this->error = '当前用户已领取该优惠券';
            return false;
        }
        return true;
    }

    /**
     * 根据订单获取可用的优惠券列表 (用于订单结算台)
     * @param int $userId 用户id
     * @param float $orderPayPrice 订单商品总金额
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getListByOrder(int $userId, float $orderPayPrice): array
    {
        // 判断订单商品总金额不能为1分
        if ($orderPayPrice <= 0.01) {
            return [];
        }
        // 获取用户可用的优惠券列表
        $list = static::getAllList($userId, ['dataType' => 'isUsable', 'amount' => $orderPayPrice]);
        $data = $list->isEmpty() ? [] : $list->toArray();
        foreach ($data as &$item) {
            // 计算最大能折扣的金额
            if ($item['coupon_type'] == CouponTypeEnum::DISCOUNT) {
                $reducePrice = helper::bcmul($orderPayPrice, $item['discount'] / 10);
                $item['reduced_price'] = helper::bcsub($orderPayPrice, $reducePrice);
            } else {
                $item['reduced_price'] = $item['reduce_price'];
            }
        }
        // 根据折扣金额排序并返回
        return !empty($data) ? array_sort($data, 'reduced_price', true) : [];
    }

    /**
     * 判断当前优惠券是否满足订单使用条件
     * @param array $couponList
     * @param array $orderGoodsIds 订单商品ID集
     * @return array
     */
    public static function couponListApplyRange(array $couponList, array $orderGoodsIds): array
    {
        // 名词解释(is_apply)：允许用于抵扣当前订单
        foreach ($couponList as $key => &$item) {
            if ($item['apply_range'] == ApplyRangeEnum::ALL) {
                // 1. 全部商品
                $item['is_apply'] = true;
            } elseif ($item['apply_range'] == ApplyRangeEnum::SOME) {
                // 2. 指定商品, 判断订单商品是否存在可用
                $applyGoodsIds = array_intersect($item['apply_range_config']['goodsIds'], $orderGoodsIds);
                $item['is_apply'] = !empty($applyGoodsIds);
            } elseif ($item['apply_range'] == ApplyRangeEnum::EXCLUDE) {
                // 2. 排除商品, 判断订单商品是否全部都在排除行列
                $excludedGoodsIds = array_intersect($item['apply_range_config']['goodsIds'], $orderGoodsIds);
                $item['is_apply'] = count($excludedGoodsIds) != count($orderGoodsIds);
            }
            !$item['is_apply'] && $item['not_apply_info'] = '该优惠券不支持当前订单';
            // 此处改为: 将不支持的优惠券剔除
            if (!$item['is_apply']) {
                unset($couponList[$key]);
            }
        }
        return $couponList;
    }
}
