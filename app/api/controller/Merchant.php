<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\Merchant as MerchantModel;
use app\common\model\UploadFile as UploadFileModel;

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
        
        if ($user['is_merchant']) {
            return $this->renderError('您已经是商家了');
        }
        
        $model = new MerchantModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'name' => $data['name'] ?? '我的店铺',
            'store_id' => $this->storeId,
            'status' => 20
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

    /**
     * 更新商家头像
     */
    public function updateLogo()
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) {
            return $this->renderError('您还不是商家');
        }
        
        $fileId = (int)$this->request->post('file_id');
        if (empty($fileId)) {
            return $this->renderError('参数错误');
        }
        
        $merchant = MerchantModel::detail($user['merchant_id']);
        if (empty($merchant)) {
            return $this->renderError('商家不存在');
        }
        
        $merchant->save(['logo_id' => $fileId]);
        
        $file = UploadFileModel::detail($fileId);
        $previewUrl = $file ? $file['preview_url'] : '';
        
        return $this->renderSuccess(['preview_url' => $previewUrl], '更新成功');
    }
}
