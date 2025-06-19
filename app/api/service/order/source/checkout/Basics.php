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

namespace app\api\service\order\source\checkout;

use app\common\service\BaseService;
use app\api\model\User as UserModel;
use app\api\service\Order as OrderService;

/**
 * 订单结算台扩展基类
 * Class Basics
 * @package app\api\service\order\source\checkout
 */
abstract class Basics extends BaseService
{
    /* @var UserModel $user 当前用户信息 */
    protected UserModel $user;

    // 订单结算商品列表
    protected iterable $goodsList;

    /**
     * 构造方法
     * Checkout constructor.
     * @param UserModel $user
     * @param iterable $goodsList
     */
    public function __construct(UserModel $user, iterable $goodsList)
    {
        parent::__construct();
        $this->user = $user;
        $this->goodsList = $goodsList;
    }

    /**
     * 验证商品列表
     * @return mixed
     */
    abstract public function validateGoodsList();


    /**
     * 验证商品限购
     * @param int $orderSource 商品来源
     * @return bool
     */
    public function validateRestrict(int $orderSource): bool
    {
        foreach ($this->goodsList as $goods) {
            // 不限购
            if (!$goods['is_restrict']) return true;
            // 拼团商品单次限购数量
            if ($goods['total_num'] > $goods['restrict_single']) {
                $this->error = "很抱歉，该商品限购{$goods['restrict_single']}件，请修改购买数量";
                return false;
            }
            // 获取用户已下单的件数（未取消 订单来源）
            $alreadyBuyNum = OrderService::getGoodsBuyNum($this->user['user_id'], $goods['goods_id'], $orderSource);
            // 情况1: 已购买0件, 实际想购买5件
            if ($alreadyBuyNum == 0 && $goods['total_num'] > $goods['restrict_total']) {
                $this->error = "很抱歉，该商品限购{$goods['restrict_total']}件，请修改购买数量";
                return false;
            }
            // 情况2: 已购买3件, 实际想购买1件
            if ($alreadyBuyNum >= $goods['restrict_total']) {
                $this->error = "很抱歉，该商品限购{$goods['restrict_total']}件，您当前已下单{$alreadyBuyNum}件，无法购买";
                return false;
            }
            // 情况3: 已购买2件, 实际想购买2件
            if (($alreadyBuyNum + $goods['total_num']) > $goods['restrict_total']) {
                $diffNum = ($alreadyBuyNum + $goods['total_num']) - $goods['restrict_total'];
                $this->error = "很抱歉，该商品限购{$goods['restrict_total']}件，您最多能再购买{$diffNum}件";
                return false;
            }
        }
        return true;
    }
}