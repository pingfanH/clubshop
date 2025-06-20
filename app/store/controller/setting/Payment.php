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
use app\store\controller\Controller;
use app\store\model\Payment as PaymentModel;

/**
 * 商城支付方式配置
 * Class Payment
 * @package app\store\controller
 */
class Payment extends Controller
{
    /**
     * 获取支付配置选项
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function options(): Json
    {
        $options = PaymentModel::getAll($this->storeId);
        return $this->renderSuccess(compact('options'));
    }

    /**
     * 更新支付配置
     * @return Json
     */
    public function update(): Json
    {
        $model = new PaymentModel;
        if ($model->updateOptions($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }
}