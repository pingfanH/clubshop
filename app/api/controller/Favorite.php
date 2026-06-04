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

namespace app\api\controller;

use think\response\Json;
use app\api\model\UserFavorite as UserFavoriteModel;
use cores\exception\BaseException;

/**
 * 商品收藏管理
 * Class Favorite
 * @package app\api\controller
 */
class Favorite extends Controller
{
    /**
     * 收藏列表
     * @param int $page 当前页码
     * @param int $listRows 每页数量
     * @return Json
     * @throws BaseException
     */
    public function list(int $page = 1, int $listRows = 10): Json
    {
        $model = new UserFavoriteModel;
        $result = $model->getList($page, $listRows);
        return $this->renderSuccess($result);
    }

    /**
     * 添加收藏
     * @param int $goodsId 商品ID
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(int $goodsId): Json
    {
        $model = new UserFavoriteModel;
        if (!$model->add($goodsId)) {
            return $this->renderError($model->getError() ?: '收藏失败');
        }
        return $this->renderSuccess([], '收藏成功');
    }

    /**
     * 取消收藏
     * @param int $goodsId 商品ID
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function remove(int $goodsId): Json
    {
        $model = new UserFavoriteModel;
        if (!$model->remove($goodsId)) {
            return $this->renderError($model->getError() ?: '取消收藏失败');
        }
        return $this->renderSuccess([], '取消收藏成功');
    }

    /**
     * 检查是否已收藏
     * @param int $goodsId 商品ID
     * @return Json
     * @throws BaseException
     */
    public function check(int $goodsId): Json
    {
        $model = new UserFavoriteModel;
        $isFavorite = $model->checkIsFavorite($goodsId);
        return $this->renderSuccess(['is_favorite' => $isFavorite]);
    }
}
