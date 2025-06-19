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

namespace app\store\controller\setting;

use think\response\Json;
use app\store\service\setting\Clear as ClearService;
use app\store\controller\Controller;

/**
 * 清理缓存
 * Class Index
 * @package app\store\controller
 */
class Cache extends Controller
{
    /**
     * 数据缓存项目(只显示key和name)
     * @return Json
     */
    public function items(): Json
    {
        $ClearService = new ClearService;
        $items = $ClearService->items();
        return $this->renderSuccess(compact('items'));
    }

    /**
     * 清理缓存
     * @return Json
     */
    public function clear(): Json
    {
        $ClearService = new ClearService;
        $ClearService->rmCache($this->postForm()['keys']);
        return $this->renderSuccess('操作成功');
    }
}
