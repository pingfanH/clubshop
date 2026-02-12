<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Merchant as MerchantModel;

/**
 * 商户控制器
 * Class Merchant
 * @package app\api\controller
 */
class Merchant extends Controller
{
    /**
     * 商户详情
     * @param $merchantId
     * @return \think\response\Json
     */
    public function detail($merchantId)
    {
        $detail = MerchantModel::detail((int)$merchantId);
        return $this->renderSuccess(compact('detail'));
    }

    /**
     * 申请成为商家
     */
    public function apply()
    {
        $user = $this->getLoginUser();
        $data = $this->request->post();
        
        // 检查是否已经是商家
        // 注意：getLoginUser 返回的是 app\api\model\User 对象，已经包含了 is_merchant 属性（在 Action 3.2 中添加）
        if ($user['is_merchant']) {
            return $this->renderError('您已经是商家了');
        }
        
        // 简单的申请逻辑：提交申请，状态为待审核 (20)
        $model = new MerchantModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'name' => $data['name'] ?? '我的店铺',
            'store_id' => $this->storeId,
            'status' => 20 // 待审核
        ])) {
            return $this->renderSuccess([], '申请已提交，请等待管理员审核');
        }
        return $this->renderError('申请失败');
    }

    /**
     * 获取当前商家的信息
     */
    public function info() 
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) {
            return $this->renderError('您还不是商家');
        }
        $detail = MerchantModel::detail($user['merchant_id']);
        return $this->renderSuccess(compact('detail'));
    }
}
