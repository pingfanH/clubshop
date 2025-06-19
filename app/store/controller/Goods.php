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

namespace app\store\controller;

use think\response\Json;
use cores\exception\BaseException;
use app\store\model\Goods as GoodsModel;

/**
 * 商品管理控制器
 * Class Goods
 * @package app\store\controller
 */
class Goods extends Controller
{
    /**
     * 商品列表
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        // 获取列表记录
        $model = new GoodsModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 根据商品ID集获取列表记录
     * @param array $goodsIds
     * @return Json
     */
    public function listByIds(array $goodsIds): Json
    {
        // 获取列表记录
        $model = new GoodsModel;
        $list = $model->getListByIds($goodsIds);
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 商品详情(详细信息)
     * @param int $goodsId
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(int $goodsId): Json
    {
        // 获取商品详情
        $model = new GoodsModel;
        $goodsInfo = $model->getDetail($goodsId);
        return $this->renderSuccess(compact('goodsInfo'));
    }

    /**
     * 商品详情(基础信息)
     * @param int $goodsId
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function basic(int $goodsId): Json
    {
        // 获取商品详情
        $model = new GoodsModel;
        $detail = $model->getBasic($goodsId);
        return $this->renderSuccess(compact('detail'));
    }

    /**
     * 添加商品
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(): Json
    {
        $model = new GoodsModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 商品编辑
     * @param int $goodsId
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit(int $goodsId): Json
    {
        // 商品详情
        $model = GoodsModel::detail($goodsId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 修改商品状态(上下架)
     * @param array $goodsIds 商品id集
     * @param bool $state 为true表示上架
     * @return Json
     */
    public function state(array $goodsIds, bool $state): Json
    {
        $model = new GoodsModel;
        if (!$model->setStatus($goodsIds, $state)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }

    /**
     * 删除商品
     * @param array $goodsIds
     * @return Json
     */
    public function delete(array $goodsIds): Json
    {
        $model = new GoodsModel;
        if (!$model->setDelete($goodsIds)) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}
