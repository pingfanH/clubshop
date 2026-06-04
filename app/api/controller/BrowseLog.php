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
use app\api\model\UserBrowseLog as UserBrowseLogModel;
use cores\exception\BaseException;

/**
 * 浏览历史管理
 * Class BrowseLog
 * @package app\api\controller
 */
class BrowseLog extends Controller
{
    /**
     * 浏览历史列表
     * @param int $page 当前页码
     * @param int $listRows 每页数量
     * @return Json
     * @throws BaseException
     */
    public function list(int $page = 1, int $listRows = 20): Json
    {
        $model = new UserBrowseLogModel;
        $result = $model->getList($page, $listRows);
        return $this->renderSuccess($result);
    }

    /**
     * 记录浏览历史
     * @param int $goodsId 商品ID
     * @return Json
     * @throws BaseException
     */
    public function add(int $goodsId): Json
    {
        $model = new UserBrowseLogModel;
        if (!$model->add($goodsId)) {
            return $this->renderError($model->getError() ?: '记录失败');
        }
        return $this->renderSuccess([], '记录成功');
    }

    /**
     * 清除浏览历史
     * @param array $ids 记录ID集，为空清除所有
     * @return Json
     * @throws BaseException
     */
    public function clear(array $ids = []): Json
    {
        $model = new UserBrowseLogModel;
        if (!$model->clear($ids)) {
            return $this->renderError($model->getError() ?: '清除失败');
        }
        return $this->renderSuccess([], '清除成功');
    }
}
