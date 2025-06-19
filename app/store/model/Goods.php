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

namespace app\store\model;

use app\common\library\helper;
use app\store\model\Spec as SpecModel;
use app\common\model\Goods as GoodsModel;
use app\store\model\GoodsSku as GoodsSkuModel;
use app\store\model\GoodsImage as GoodsImageModel;
use app\store\model\GoodsSpecRel as GoodsSpecRelModel;
use app\store\model\goods\ServiceRel as GoodsServiceRelModel;
use app\store\model\GoodsCategoryRel as GoodsCategoryRelModel;
use app\store\service\Goods as GoodsService;
use app\common\enum\goods\SpecType as GoodsSpecTypeEnum;
use app\common\enum\goods\Status as GoodsStatusEnum;
use cores\exception\BaseException;

/**
 * 商品模型
 * Class Goods
 * @package app\store\model
 */
class Goods extends GoodsModel
{
    /**
     * 获取商品详情
     * @param int $goodsId
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDetail(int $goodsId)
    {
        // 获取商品基础信息
        $goodsInfo = $this->getBasic($goodsId);
        // 分类ID集
        $goodsInfo['categoryIds'] = GoodsCategoryRelModel::getCategoryIds($goodsInfo['goods_id']);
        // 商品多规格属性列表
        if ($goodsInfo['spec_type'] == GoodsSpecTypeEnum::MULTI) {
            $goodsInfo['specList'] = GoodsSpecRelModel::getSpecList($goodsInfo['goods_id']);
        }
        // 服务与承诺
        $goodsInfo['serviceIds'] = GoodsServiceRelModel::getServiceIds($goodsInfo['goods_id']);
        // 商品规格是否锁定(锁定状态下不允许编辑规格)
        $goodsInfo['isSpecLocked'] = GoodsService::checkSpecLocked($goodsId);
        // 返回商品详细信息
        return $goodsInfo;
    }

    /**
     * 获取商品基础信息
     * @param int $goodsId
     * @return mixed
     * @throws BaseException
     */
    public function getBasic(int $goodsId)
    {
        // 关联查询
        $with = ['images.file', 'skuList.image', 'video', 'videoCover'];
        // 获取商品记录
        $goodsInfo = static::detail($goodsId, $with);
        empty($goodsInfo) && throwError('很抱歉，商品信息不存在');
        // 整理商品数据并返回
        return parent::setGoodsData($goodsInfo);
    }

    /**
     * 添加商品
     * @param array $data
     * @return bool
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(array $data): bool
    {
        // 创建商品数据
        $data = $this->createData($data);
        // 事务处理
        $this->transaction(function () use ($data) {
            // 添加商品
            $this->save($data);
            // 新增商品与分类关联
            GoodsCategoryRelModel::increased((int)$this['goods_id'], $data['categoryIds']);
            // 新增商品与图片关联
            GoodsImageModel::increased((int)$this['goods_id'], $data['imagesIds']);
            // 新增商品与规格关联
            GoodsSpecRelModel::increased((int)$this['goods_id'], $data['newSpecList']);
            // 新增商品sku信息
            GoodsSkuModel::add((int)$this['goods_id'], $data['spec_type'], $data['newSkuList']);
            // 新增服务与承诺关联
            GoodsServiceRelModel::increased((int)$this['goods_id'], $data['serviceIds']);
        });
        return true;
    }

    /**
     * 编辑商品
     * @param array $data
     * @return bool
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit(array $data): bool
    {
        // 创建商品数据
        $data = $this->createData($data);
        // 事务处理
        $this->transaction(function () use ($data) {
            // 更新商品
            $this->save($data);
            // 更新商品与分类关联
            GoodsCategoryRelModel::updates((int)$this['goods_id'], $data['categoryIds']);
            // 更新商品与图片关联
            GoodsImageModel::updates((int)$this['goods_id'], $data['imagesIds']);
            // 更新商品与规格关联
            GoodsSpecRelModel::updates((int)$this['goods_id'], $data['newSpecList']);
            // 更新商品sku信息
            GoodsSkuModel::edit((int)$this['goods_id'], $data['spec_type'], $data['newSkuList']);
            // 更新服务与承诺关联
            GoodsServiceRelModel::updates((int)$this['goods_id'], $data['serviceIds']);
        });
        return true;
    }

    /**
     * 修改商品状态
     * @param array $goodsIds 商品id集
     * @param bool $state 为true表示上架
     * @return bool|false
     */
    public function setStatus(array $goodsIds, bool $state): bool
    {
        // 批量更新记录
        return static::updateBase(['status' => $state ? 10 : 20], [['goods_id', 'in', $goodsIds]]);
    }

    /**
     * 软删除
     * @param array $goodsIds
     * @return bool
     */
    public function setDelete(array $goodsIds): bool
    {
        foreach ($goodsIds as $goodsId) {
            if (!GoodsService::checkIsAllowDelete($goodsId)) {
                $this->error = '当前商品正在参与其他活动，不允许删除';
                return false;
            }
        }
        // 批量更新记录
        return static::updateBase(['is_delete' => 1], [['goods_id', 'in', $goodsIds]]);
    }

    // 获取已售罄的商品
    public function getSoldoutGoodsTotal(): int
    {
        $filter = [
            ['stock_total', '=', 0],
            ['status', '=', GoodsStatusEnum::ON_SALE]
        ];
        return $this->getGoodsTotal($filter);
    }

    /**
     * 获取当前商品总数
     * @param array $where
     * @return int
     */
    public function getGoodsTotal(array $where = []): int
    {
        return $this->where($where)->where('is_delete', '=', 0)->count();
    }

    /**
     * 创建商品数据
     * @param array $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    private function createData(array $data): array
    {
        // 默认数据
        $data = \array_merge($data, [
            'line_price' => $data['line_price'] ?? 0,
            'content' => $data['content'] ?? '',
            'newSpecList' => [],
            'newSkuList' => [],
            'store_id' => self::$storeId,
        ]);
        // 整理商品的价格和库存总量
        if ($data['spec_type'] == GoodsSpecTypeEnum::MULTI) {
            $data['stock_total'] = GoodsSkuModel::getStockTotal($data['specData']['skuList']);
            [$data['goods_price_min'], $data['goods_price_max']] = GoodsSkuModel::getGoodsPrices($data['specData']['skuList']);
            [$data['line_price_min'], $data['line_price_max']] = GoodsSkuModel::getLinePrices($data['specData']['skuList']);
        } elseif ($data['spec_type'] == GoodsSpecTypeEnum::SINGLE) {
            $data['goods_price_min'] = $data['goods_price_max'] = $data['goods_price'];
            $data['line_price_min'] = $data['line_price_max'] = $data['line_price'];
            $data['stock_total'] = $data['stock_num'];
        }
        // 规格和sku数据处理
        if ($data['spec_type'] == GoodsSpecTypeEnum::MULTI) {
            // 验证规格值是否合法
            SpecModel::checkSpecData($data['specData']['specList']);
            // 生成多规格数据 (携带id)
            $data['newSpecList'] = SpecModel::getNewSpecList($data['specData']['specList'], self::$storeId);
            // 生成skuList (携带goods_sku_id)
            $data['newSkuList'] = GoodsSkuModel::getNewSkuList($data['newSpecList'], $data['specData']['skuList']);
        } elseif ($data['spec_type'] == GoodsSpecTypeEnum::SINGLE) {
            // 生成skuItem
            $data['newSkuList'] = helper::pick($data, ['goods_price', 'line_price', 'stock_num', 'goods_weight']);
        }
        // 单独设置折扣的配置
        $data['is_enable_grade'] == 0 && $data['is_alone_grade'] = 0;
        $aloneGradeEquity = [];
        if ($data['is_alone_grade'] == 1) {
            if (empty($data['alone_grade_equity'])) {
                throwError('很抱歉，请先添加会员等级后再设置会员折扣价');
            }
            foreach ($data['alone_grade_equity'] as $key => $value) {
                $gradeId = str_replace('grade_id:', '', $key);
                $aloneGradeEquity[$gradeId] = $value;
            }
        }
        $data['alone_grade_equity'] = $aloneGradeEquity;
        return $data;
    }
}
