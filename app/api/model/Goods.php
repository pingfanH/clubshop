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

use app\api\service\Goods as GoodsService;
use app\api\service\user\Grade as UserGradeService;
use app\api\model\GoodsSku as GoodsSkuModel;
use app\api\model\GoodsSpecRel as GoodsSpecRelModel;
use app\common\model\Goods as GoodsModel;
use app\common\enum\goods\Status as GoodsStatusEnum;
use cores\exception\BaseException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品模型
 * Class Goods
 * @package app\api\model
 */
class Goods extends GoodsModel
{
    /**
     * 隐藏字段
     * @var array
     */
    public $hidden = [
        'images',
        'delivery',
        'deduct_stock_type',
        'sales_initial',
        'sales_actual',
        'sort',
        'is_delete',
        'store_id',
        'create_time',
        'update_time'
    ];

    // 是否启用会员折扣价
    private bool $enableGradeMoney = true;

    /**
     * 商品详情：HTML实体转换回普通字符
     * @param $value
     * @return string
     */
    public function getContentAttr($value): string
    {
        return \htmlspecialchars_decode((string)$value);
    }

    /**
     * 设置是否启用会员折扣价
     * @param bool $value
     * @return $this
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function setEnableGradeMoney(bool $value): Goods
    {
        $this->enableGradeMoney = $value;
        return $this;
    }

    /**
     * 获取是否启用会员折扣价
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getEnableGradeMoney(): bool
    {
        return $this->enableGradeMoney;
    }

    /**
     * 获取商品列表
     * @param array $param 查询条件
     * @param int $listRows 分页数量
     * @return mixed|\think\model\Collection|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = [], int $listRows = 15)
    {
        // 整理查询参数
        $params = \array_merge($param, ['status' => GoodsStatusEnum::ON_SALE]);
        // 获取商品列表
        $list = parent::getList($params, $listRows);
        if ($list->isEmpty()) {
            return $list;
        }
        // 隐藏冗余的属性
        $list->hidden(static::getHidden(['content', 'goods_images', 'images']));
        // 整理列表数据并返回
        return $this->setGoodsListDataFromApi($list);
    }

    /**
     * 获取商品详情 (详细数据用于页面展示)
     * @param int $goodsId 商品ID
     * @param bool $verifyStatus 是否验证商品状态(上架)
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetails(int $goodsId, bool $verifyStatus = true)
    {
        // 关联查询(商品图片、sku列表)
        $with = ['images.file', 'skuList.image', 'video', 'videoCover'];
        // 获取商品记录
        $goodsInfo = $this->getGoodsMain($goodsId, $with, $verifyStatus);
        // 商品规格列表
        $goodsInfo['specList'] = GoodsSpecRelModel::getSpecList($goodsInfo['goods_id']);
        return $goodsInfo->hidden(static::getHidden(['images']));
    }

    /**
     * 获取商品详情 (仅包含主商品信息和商品图片)
     * @param int $goodsId 商品ID
     * @param bool $verifyStatus 是否验证商品状态(上架)
     * @return mixed
     * @throws BaseException
     */
    public function getBasic(int $goodsId, bool $verifyStatus = true)
    {
        // 关联查询(商品图片)
        $with = ['images.file'];
        // 获取商品记录
        return $this->getGoodsMain($goodsId, $with, $verifyStatus);
    }

    /**
     * 获取商品规格数据
     * @param int $goodsId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecData(int $goodsId): array
    {
        $data = [];
        // 商品SKU列表
        $data['skuList'] = GoodsSkuModel::getSkuList($goodsId, true);
        // 商品规格列表
        $data['specList'] = GoodsSpecRelModel::getSpecList($goodsId);
        return $data;
    }

    /**
     * 获取商品主体信息
     * @param int $goodsId 商品ID
     * @param array $with 关联查询
     * @param bool $verifyStatus 是否验证商品状态(上架)
     * @return mixed
     * @throws BaseException
     */
    private function getGoodsMain(int $goodsId, array $with = [], bool $verifyStatus = true)
    {
        // 获取商品记录
        $goodsInfo = static::detail($goodsId, $with);
        // 判断商品是否存在
        if (empty($goodsInfo) || $goodsInfo['is_delete']) {
            throwError('很抱歉，商品信息不存在');
        }
        // 判断商品状态 (上架)
        if ($verifyStatus && $goodsInfo['status'] == GoodsStatusEnum::OFF_SALE) {
            throwError('很抱歉，当前商品已下架');
        }
        // 整理商品数据并返回
        return $this->setGoodsDataFromApi($goodsInfo);
    }

    /**
     * 根据商品id集获取商品列表
     * @param array $goodsIds
     * @return mixed
     */
    public function getListByIdsFromApi(array $goodsIds)
    {
        // 获取商品列表
        $data = $this->getListByIds($goodsIds, GoodsStatusEnum::ON_SALE);
        // 整理列表数据并返回
        return $this->setGoodsListDataFromApi($data);
    }

    /**
     * 获取商品指定的sku信息并且设置商品的会员价
     * @param mixed $goodsInfo 商品信息
     * @param string $goodsSkuId 商品SKUID
     * @param bool $enableGradeMoney 是否设置会员折扣价
     * @return \app\common\model\GoodsSku|array|null
     * @throws BaseException
     */
    public static function getSkuInfo($goodsInfo, string $goodsSkuId, bool $enableGradeMoney = true)
    {
        $goodsInfo['skuInfo'] = GoodsService::getSkuInfo($goodsInfo['goods_id'], $goodsSkuId);
        $enableGradeMoney && (new static)->setGoodsGradeMoney($goodsInfo);
        return $goodsInfo['skuInfo'];
    }

    /**
     * 设置商品展示的数据 api模块
     * @param $data
     * @return mixed
     */
    private function setGoodsListDataFromApi($data)
    {
        return $this->setGoodsListData($data, function ($goods) {
            // 整理商品数据 api模块
            $this->setGoodsDataFromApi($goods);
        });
    }

    /**
     * 整理商品数据 api模块
     * @param $goodsInfo
     * @return mixed
     */
    private function setGoodsDataFromApi($goodsInfo)
    {
        return $this->setGoodsData($goodsInfo, function ($goods) {
            // 计算并设置商品会员价
            $this->getEnableGradeMoney() && $this->setGoodsGradeMoney($goods);
        });
    }

    /**
     * 设置商品的会员价
     * @param Goods $goods
     * @throws BaseException
     */
    private function setGoodsGradeMoney(self $goods)
    {
        // 设置当前商品是否使用会员等级折扣价
        $goods['is_user_grade'] = false;
        // 获取当前登录用户的会员等级信息
        $gradeInfo = UserGradeService::getCurrentGradeInfo();
        // 判断商品是否参与会员折扣
        if (empty($gradeInfo) || !$goods['is_enable_grade']) {
            return;
        }
        // 默认的折扣比例
        $discountRatio = $gradeInfo['equity']['discount'];
        // 商品单独设置了会员折扣
        if ($goods['is_alone_grade'] && isset($goods['alone_grade_equity'][$gradeInfo['grade_id']])) {
            $discountRatio = $goods['alone_grade_equity'][$gradeInfo['grade_id']];
        }
        if (empty($discountRatio)) {
            return;
        }
        // 标记参与会员折扣
        $goods['is_user_grade'] = true;
        // 会员折扣价: 商品基础价格
        $goods['goods_price_min'] = UserGradeService::getDiscountPrice($goods['goods_price_min'], $discountRatio);
        $goods['goods_price_max'] = UserGradeService::getDiscountPrice($goods['goods_price_max'], $discountRatio);
        // 会员折扣价: 商品sku列表
        if ($goods->getRelation('skuList')) {
            foreach ($goods['skuList'] as &$skuItem) {
                $skuItem['goods_price'] = UserGradeService::getDiscountPrice($skuItem['goods_price'], $discountRatio);
            }
        }
        // 会员折扣价: 已选择的商品sku（用于购物车）
        if ($goods->getAttr('skuInfo')) {
            $goods['skuInfo']['goods_price'] = UserGradeService::getDiscountPrice($goods['skuInfo']['goods_price'], $discountRatio);
        }
    }
}
