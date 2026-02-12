<?php
declare (strict_types=1);

namespace app\store\controller;

use app\store\model\User as UserModel;
use app\common\model\Merchant as MerchantModel;
use think\response\Json;

/**
 * 商家管理控制器
 * Class Merchant
 * @package app\store\controller
 */
class Merchant extends Controller
{
    /**
     * 商家列表 (待审核/已审核)
     * @return Json
     */
    public function list(): Json
    {
        $model = new MerchantModel;
        $list = $model->with(['user', 'logo'])
            ->order(['create_time' => 'desc'])
            ->paginate($this->request->param('listRows', 15));
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 审核商家
     * @return Json
     */
    public function audit(): Json
    {
        $data = $this->postForm();
        if (!isset($data['merchant_id']) || !isset($data['status'])) {
            return $this->renderError('参数错误');
        }
        
        $merchant = MerchantModel::detail((int)$data['merchant_id']);
        if (!$merchant) {
            return $this->renderError('商家不存在');
        }

        // 审核逻辑
        if ($data['status'] == 10) {
            // 通过：调用 User::setMerchant 逻辑，自动创建管理员账号
            $userModel = UserModel::detail($merchant['user_id']);
            if ($userModel->setMerchant(1, $this->storeId)) {
                return $this->renderSuccess('审核通过');
            }
            return $this->renderError($userModel->getError() ?: '操作失败');
        } elseif ($data['status'] == 30) {
            // 驳回
            if ($merchant->save(['status' => 30])) {
                return $this->renderSuccess('已驳回');
            }
            return $this->renderError('操作失败');
        }

        return $this->renderError('未知状态');
    }
}
