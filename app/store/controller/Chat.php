<?php
declare (strict_types=1);

namespace app\store\controller;

use think\response\Json;
use app\common\model\ChatMessage as ChatMessageModel;
use app\common\model\User as UserModel;
use app\common\model\Merchant as MerchantModel;
use app\common\model\Store as StoreModel;
use app\common\model\UploadFile as UploadFileModel;
use cores\exception\BaseException;

/**
 * 聊天管理控制器
 * Class Chat
 * @package app\store\controller
 */
class Chat extends Controller
{
    /**
     * 获取会话列表（与当前店铺的对话）
     * @return Json
     * @throws BaseException
     */
    public function sessions(): Json
    {
        // 获取当前店铺下的所有商家
        $merchantIds = MerchantModel::where('store_id', $this->storeId)
            ->column('merchant_id');
        
        if (empty($merchantIds)) {
            return $this->renderSuccess(['list' => []]);
        }
        
        // 获取与当前店铺商家的所有会话
        $sessions = ChatMessageModel::where('merchant_id', 'in', $merchantIds)
            ->where('store_id', $this->storeId)
            ->field('user_id, merchant_id, MAX(create_time) as last_message_time, COUNT(*) as message_count')
            ->group('user_id, merchant_id')
            ->order('last_message_time', 'desc')
            ->select();
        
        $list = [];
        foreach ($sessions as $session) {
            // 获取用户信息
            $user = UserModel::detail((int)$session['user_id']);
            if (empty($user)) continue;
            
            // 获取最后一条消息
            $lastMessage = ChatMessageModel::where('user_id', $session['user_id'])
                ->where('merchant_id', $session['merchant_id'])
                ->where('store_id', $this->storeId)
                ->order('create_time', 'desc')
                ->find();
            
            // 计算未读消息数（用户发送的未读消息）
            $unreadCount = ChatMessageModel::where('user_id', $session['user_id'])
                ->where('merchant_id', $session['merchant_id'])
                ->where('store_id', $this->storeId)
                ->where('sender_type', 10) // 用户发送的
                ->where('is_read', 0)
                ->count();
            
            $list[] = [
                'user_id' => (int)$session['user_id'],
                'merchant_id' => (int)$session['merchant_id'],
                'user_name' => $user['nick_name'] ?: '用户' . $session['user_id'],
                'user_avatar' => $user['avatar_url'] ?? '',
                'user_mobile' => $user['mobile'] ?? '',
                'last_message' => $lastMessage ? $lastMessage['content'] : '',
                'last_message_type' => $lastMessage ? (int)$lastMessage['type'] : 10,
                'last_message_time' => (int)$session['last_message_time'],
                'message_count' => (int)$session['message_count'],
                'unread_count' => (int)$unreadCount,
            ];
        }
        
        return $this->renderSuccess(['list' => $list]);
    }

    /**
     * 获取聊天记录
     * @return Json
     * @throws BaseException
     */
    public function messages(): Json
    {
        $userId = $this->request->get('user_id', 0);
        $merchantId = $this->request->get('merchant_id', 0);
        $limit = $this->request->get('limit', 50);
        
        if (empty($userId) || empty($merchantId)) {
            return $this->renderError('参数错误');
        }
        
        // 获取消息列表
        $list = ChatMessageModel::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->order('create_time', 'desc')
            ->paginate($limit);
        
        // 标记用户消息为已读
        ChatMessageModel::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->where('sender_type', 10)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
        
        // 获取用户信息
        $user = UserModel::detail((int)$userId);
        // 获取店铺信息
        $store = StoreModel::detail((int)$this->storeId);
        
        return $this->renderSuccess([
            'list' => $list->items(),
            'total' => $list->total(),
            'user' => $user ? [
                'user_id' => (int)$user['user_id'],
                'nick_name' => $user['nick_name'],
                'avatar_url' => $user['avatar_url'] ?? '',
                'mobile' => $user['mobile'],
            ] : null,
            'store' => $store ? [
                'store_id' => (int)$store['store_id'],
                'store_name' => $store['store_name'],
            ] : null,
        ]);
    }

    /**
     * 发送消息
     * @return Json
     * @throws BaseException
     */
    public function send(): Json
    {
        $userId = $this->request->post('user_id', 0);
        $merchantId = $this->request->post('merchant_id', 0);
        $content = $this->request->post('content', '');
        $type = $this->request->post('type', 10);
        $useStoreIdentity = $this->request->post('use_store_identity', 1); // 1使用店铺身份 0使用个人身份
        
        // 如果没有传merchant_id，使用店铺关联的商家
        if (empty($merchantId)) {
            $merchantId = $this->store['merchant_id'] ?? 0;
        }
        
        if (empty($userId) || empty($merchantId) || empty($content)) {
            return $this->renderError('参数错误');
        }
        
        // 获取当前管理员信息
        $storeUser = $this->store;
        $store = StoreModel::detail((int)$this->storeId);
        
        // 根据选择的身份设置发送者信息
        if ($useStoreIdentity) {
            // 使用店铺身份
            $senderName = $store['store_name'] ?? '店铺';
            $senderAvatar = '';
            if ($store && !empty($store['logo_id'])) {
                $logoFile = UploadFileModel::detail($store['logo_id']);
                $senderAvatar = $logoFile ? $logoFile['preview_url'] : '';
            }
        } else {
            // 使用个人身份
            $senderName = $storeUser['real_name'] ?: $storeUser['user_name'];
            $senderAvatar = '';
        }
        
        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $userId,
            'merchant_id' => $merchantId,
            'store_user_id' => $storeUser['store_user_id'] ?? 0,
            'sender_type' => 20, // 商家/管理员
            'sender_name' => $senderName,
            'sender_avatar' => $senderAvatar,
            'content' => $content,
            'type' => $type,
            'store_id' => $this->storeId,
            'is_read' => 1,
            'create_time' => time(),
        ])) {
            return $this->renderSuccess([
                'message_id' => $model['message_id'],
                'sender_name' => $senderName,
            ], '发送成功');
        }
        
        return $this->renderError('发送失败');
    }

    /**
     * 发送图片消息
     * @return Json
     * @throws BaseException
     */
    public function sendImage(): Json
    {
        $userId = $this->request->post('user_id', 0);
        $merchantId = $this->request->post('merchant_id', 0);
        $imageUrl = $this->request->post('image_url', '');
        $useStoreIdentity = $this->request->post('use_store_identity', 1);
        
        // 如果没有传merchant_id，使用店铺关联的商家
        if (empty($merchantId)) {
            $merchantId = $this->store['merchant_id'] ?? 0;
        }
        
        if (empty($userId) || empty($merchantId) || empty($imageUrl)) {
            return $this->renderError('参数错误');
        }
        
        // 获取当前管理员信息
        $storeUser = $this->store;
        $store = StoreModel::detail((int)$this->storeId);
        
        // 根据选择的身份设置发送者信息
        if ($useStoreIdentity) {
            $senderName = $store['store_name'] ?? '店铺';
            $senderAvatar = '';
            if ($store && !empty($store['logo_id'])) {
                $logoFile = UploadFileModel::detail($store['logo_id']);
                $senderAvatar = $logoFile ? $logoFile['preview_url'] : '';
            }
        } else {
            $senderName = $storeUser['real_name'] ?: $storeUser['user_name'];
            $senderAvatar = '';
        }
        
        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $userId,
            'merchant_id' => $merchantId,
            'store_user_id' => $storeUser['store_user_id'] ?? 0,
            'sender_type' => 20,
            'sender_name' => $senderName,
            'sender_avatar' => $senderAvatar,
            'content' => $imageUrl,
            'type' => 20, // 图片消息
            'store_id' => $this->storeId,
            'is_read' => 1,
            'create_time' => time(),
        ])) {
            return $this->renderSuccess([], '发送成功');
        }
        
        return $this->renderError('发送失败');
    }

    /**
     * 获取未读消息总数
     * @return Json
     * @throws BaseException
     */
    public function unreadCount(): Json
    {
        $merchantId = $this->store['merchant_id'] ?? 0;
        
        if (empty($merchantId)) {
            return $this->renderSuccess(['count' => 0]);
        }
        
        $count = ChatMessageModel::where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->where('sender_type', 10)
            ->where('is_read', 0)
            ->count();
        
        return $this->renderSuccess(['count' => (int)$count]);
    }
}
