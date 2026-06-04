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
     * 获取会话列表
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function sessions()
    {
        $user = $this->getLoginUser();
        
        // 获取用户的所有会话（按商家分组，获取最后一条消息）
        $sessions = ChatMessageModel::where('user_id', $user['user_id'])
            ->where('store_id', $this->storeId)
            ->field('merchant_id, MAX(create_time) as last_message_time, COUNT(*) as message_count')
            ->group('merchant_id')
            ->order('last_message_time', 'desc')
            ->select();
        
        $list = [];
        foreach ($sessions as $session) {
            // 获取商家信息
            $merchant = MerchantModel::detail((int)$session['merchant_id']);
            if (empty($merchant)) continue;
            
            // 获取最后一条消息
            $lastMessage = ChatMessageModel::where('user_id', $user['user_id'])
                ->where('merchant_id', $session['merchant_id'])
                ->where('store_id', $this->storeId)
                ->order('create_time', 'desc')
                ->find();
            
            $list[] = [
                'merchant_id' => (int)$session['merchant_id'],
                'merchant_name' => $merchant['name'],
                'merchant_logo' => $merchant['logo'] ? $merchant['logo']['preview_url'] : '',
                'last_message' => $lastMessage ? $lastMessage['content'] : '',
                'last_message_time' => (int)$session['last_message_time'],
                'message_count' => (int)$session['message_count'],
                'unread_count' => 0, // 暂时不实现未读计数
            ];
        }
        
        return $this->renderSuccess(['list' => $list]);
    }

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
     * 发送图片消息
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function sendImage()
    {
        $user = $this->getLoginUser();
        $param = $this->request->post();
        
        if (empty($param['merchant_id']) || empty($param['image_url'])) {
            return $this->renderError('参数错误');
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'sender_type' => 10,
            'content' => $param['image_url'],
            'type' => 20, // 图片消息
            'store_id' => $this->storeId,
        ])) {
            return $this->renderSuccess([], '发送成功');
        }
        return $this->renderError('发送失败');
    }

    /**
     * 发送商品卡片
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function sendGoods()
    {
        $user = $this->getLoginUser();
        $param = $this->request->post();
        
        if (empty($param['merchant_id']) || empty($param['goods_id'])) {
            return $this->renderError('参数错误');
        }

        // 获取商品信息
        $goods = \app\api\model\Goods::detail((int)$param['goods_id']);
        if (empty($goods)) {
            return $this->renderError('商品不存在');
        }

        // 构建商品卡片数据
        $goodsData = [
            'goods_id' => $goods['goods_id'],
            'goods_name' => $goods['goods_name'],
            'goods_image' => $goods['goods_image'] ?? '',
            'goods_price' => $goods['goods_price_min'],
        ];

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'sender_type' => 10,
            'content' => json_encode($goodsData),
            'type' => 30, // 商品卡片
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
