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

namespace app\store\controller\order;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\order\Export as ExportModel;
use app\store\service\order\Export as ExportService;
use cores\exception\BaseException;

/**
 * 订单导出记录控制器
 * Class Export
 * @package app\store\controller\order
 */
class Export extends Controller
{
    /**
     * 执行订单导出excel
     * @return Json
     * @throws BaseException
     */
    public function exportOrder(): Json
    {
        $model = new ExportService;
        if (!$model->exportOrder($this->request->param())) {
            $this->renderError($model->getError() ?: '导出失败');
        }
        return $this->renderSuccess('导出Excel文件成功，请在导出记录中下载');
    }

    /**
     * 订单导出记录
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new ExportModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }
}