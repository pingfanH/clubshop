<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\ChatMessage as ChatMessageModel;
use app\common\model\Merchant as MerchantModel;

/**
 * 聊天控制器
 * Class Chat
 * @package app\api\controller
 */
class Chat extends Controller
{
    /**
     * 发送消息
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function send()
    {
        // 强制验证登录
        $user = $this->getLoginUser();
        $param = $this->request->post();
        
        // 验证参数
        if (empty($param['merchant_id']) || empty($param['content'])) {
            return $this->renderError('参数错误');
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'sender_type' => 10, // User
            'content' => $param['content'],
            'type' => $param['type'] ?? 10,
            'store_id' => $this->storeId,
        ])) {
            return $this->renderSuccess([], '发送成功');
        }
        return $this->renderError('发送失败');
    }

    /**
     * 获取消息列表
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function list()
    {
        $user = $this->getLoginUser();
        $merchantId = $this->request->get('merchant_id');
        
        if (empty($merchantId)) {
            return $this->renderError('参数错误');
        }
        
        $list = ChatMessageModel::where('user_id', $user['user_id'])
            ->where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->order('create_time', 'asc')
            ->select();
            
        // 获取商户信息
        $merchant = MerchantModel::detail((int)$merchantId);
            
        return $this->renderSuccess(compact('list', 'merchant'));
    }
}
