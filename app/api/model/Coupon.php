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
use app\common\model\Coupon as CouponModel;
use app\common\enum\coupon\ApplyRange as ApplyRangeEnum;
use cores\exception\BaseException;

/**
 * 优惠券模型
 * Class Coupon
 * @package app\api\model
 */
class Coupon extends CouponModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'total_num',
        'receive_num',
        'status',
        'is_delete',
        'store_id',
        'create_time',
        'update_time',
    ];

    /**
     * 获取优惠券列表
     * @param int|null $limit 获取的数量
     * @param bool $onlyReceive 只获取可领取的优惠券
     * @param int|null $goodsId 只获取指定商品ID可用的优惠券
     * @param array|null $couponIds 指定优惠券ID集
     * @return array|\think\Collection
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(int $limit = null, bool $onlyReceive = false, ?int $goodsId = null, ?array $couponIds = [])
    {
        // 查询构造器
        $query = $this->getNewQuery();
        // 只显示可领取(未过期,未发完)的优惠券
        if ($onlyReceive) {
            $query->where('IF ( `total_num` > - 1, `receive_num` < `total_num`, 1 = 1 )')
                ->where('IF ( `expire_type` = 20, (`end_time` + 86400) >= ' . time() . ', 1 = 1 )');
        }
        // 指定优惠券ID集
        if (!empty($couponIds)) {
            $query->where('coupon_id', 'in', $couponIds);
            $query->orderRaw('field(coupon_id, ' . implode(',', $couponIds) . ')');
        } else {
            $limit > 0 && $query->limit($limit);
            $query->order(['sort', 'create_time' => 'desc']);
        }
        // 优惠券列表
        $list = $query->where('status', '=', 1)
            ->where('is_delete', '=', 0)
            ->select();
        // 获取用户已领取的优惠券
        $list = $this->setIsReceive($list);
        // 筛选指定商品ID可用的优惠券
        return $this->screenByGoodsId($list, $goodsId);
    }

    /**
     * 筛选指定商品ID可用的优惠券
     * @param iterable $couponList
     * @param int|null $goodsId
     * @return array|iterable
     */
    private function screenByGoodsId(iterable $couponList, ?int $goodsId = null)
    {
        if (empty($goodsId)) {
            return $couponList;
        }
        $list = [];
        foreach ($couponList as $item) {
            // 优惠券指定商品可用
            if ($item['apply_range'] == ApplyRangeEnum::SOME
                && $goodsId > 0
                && !\in_array($goodsId, $item['apply_range_config']['goodsIds'])) {
                continue;
            }
            // 优惠券指定商品不可用
            if ($item['apply_range'] == ApplyRangeEnum::EXCLUDE
                && $goodsId > 0
                && \in_array($goodsId, $item['apply_range_config']['goodsIds'])) {
                continue;
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 获取用户已领取的优惠券
     * @param mixed $couponList
     * @return \think\Collection
     * @throws BaseException
     */
    private function setIsReceive($couponList)
    {
        // 获取用户已领取的优惠券
        $userInfo = UserService::getCurrentLoginUser(false);
        if ($userInfo !== false) {
            $UserCouponModel = new UserCoupon;
            $userCouponIds = $UserCouponModel->getUserCouponIds($userInfo['user_id']);
            foreach ($couponList as $key => $item) {
                $couponList[$key]['is_receive'] = in_array($item['coupon_id'], $userCouponIds);
            }
        }
        return $couponList;
    }
}
