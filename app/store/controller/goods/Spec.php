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

namespace app\store\controller\goods;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\GoodsSpecRel as GoodsSpecRelModel;

/**
 * 商品规格控制器
 * Class Spec
 * @package app\store\controller
 */
class Spec extends Controller
{
    /**
     * 商品规格属性列表
     * @param int $goodsId 商品ID
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list(int $goodsId): Json
    {
        // 商品规格属性列表
        $list = GoodsSpecRelModel::getSpecList($goodsId);
        return $this->renderSuccess(compact('list'));
    }
}
