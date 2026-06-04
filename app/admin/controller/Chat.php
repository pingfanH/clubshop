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

namespace app\admin\controller;

use think\response\Json;
use app\common\model\ChatMessage as ChatMessageModel;
use app\common\model\Merchant as MerchantModel;
use app\common\model\User as UserModel;
use app\common\model\Store as StoreModel;
use cores\exception\BaseException;

/**
 * 聊天管理控制器
 * Class Chat
 * @package app\admin\controller
 */
class Chat extends Controller
{
    /**
     * 获取会话列表
     * @return Json
     * @throws BaseException
     */
    public function sessions(): Json
    {
        $storeId = $this->request->get('store_id', 0);
        
        // 查询条件
        $where = [];
        if ($storeId > 0) {
            $where[] = ['store_id', '=', $storeId];
        }
        
        // 获取所有会话（按用户和商家分组）
        $sessions = ChatMessageModel::where($where)
            ->field('user_id, merchant_id, store_id, MAX(create_time) as last_message_time, COUNT(*) as message_count')
            ->group('user_id, merchant_id, store_id')
            ->order('last_message_time', 'desc')
            ->select();
        
        $list = [];
        foreach ($sessions as $session) {
            // 获取商家信息
            $merchant = MerchantModel::detail((int)$session['merchant_id']);
            // 获取用户信息
            $user = UserModel::detail((int)$session['user_id']);
            // 获取店铺信息
            $store = StoreModel::detail((int)$session['store_id']);
            
            if (empty($merchant) || empty($user)) continue;
            
            // 获取最后一条消息
            $lastMessage = ChatMessageModel::where('user_id', $session['user_id'])
                ->where('merchant_id', $session['merchant_id'])
                ->where('store_id', $session['store_id'])
                ->order('create_time', 'desc')
                ->find();
            
            $list[] = [
                'user_id' => (int)$session['user_id'],
                'user_name' => $user['nick_name'] ?: '用户' . $session['user_id'],
                'user_avatar' => $user['avatar_url'] ?? '',
                'merchant_id' => (int)$session['merchant_id'],
                'merchant_name' => $merchant['name'],
                'merchant_logo' => $merchant['logo'] ? $merchant['logo']['preview_url'] : '',
                'store_id' => (int)$session['store_id'],
                'store_name' => $store ? $store['store_name'] : '',
                'last_message' => $lastMessage ? $lastMessage['content'] : '',
                'last_message_type' => $lastMessage ? (int)$lastMessage['type'] : 10,
                'last_message_time' => (int)$session['last_message_time'],
                'message_count' => (int)$session['message_count'],
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
        $storeId = $this->request->get('store_id', 0);
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 50);
        
        if (empty($userId) || empty($merchantId)) {
            return $this->renderError('参数错误');
        }
        
        // 查询条件
        $where = [
            ['user_id', '=', $userId],
            ['merchant_id', '=', $merchantId],
        ];
        if ($storeId > 0) {
            $where[] = ['store_id', '=', $storeId];
        }
        
        // 获取消息列表
        $list = ChatMessageModel::where($where)
            ->order('create_time', 'desc')
            ->paginate($limit);
        
        // 获取用户信息
        $user = UserModel::detail((int)$userId);
        // 获取商家信息
        $merchant = MerchantModel::detail((int)$merchantId);
        
        return $this->renderSuccess([
            'list' => $list->items(),
            'total' => $list->total(),
            'user' => $user ? [
                'user_id' => (int)$user['user_id'],
                'nick_name' => $user['nick_name'],
                'avatar_url' => $user['avatar_url'] ?? '',
                'mobile' => $user['mobile'],
            ] : null,
            'merchant' => $merchant ? [
                'merchant_id' => (int)$merchant['merchant_id'],
                'name' => $merchant['name'],
                'logo' => $merchant['logo'] ? $merchant['logo']['preview_url'] : '',
            ] : null,
        ]);
    }

    /**
     * 管理员发送消息（代表商家）
     * @return Json
     * @throws BaseException
     */
    public function send(): Json
    {
        $userId = $this->request->post('user_id', 0);
        $merchantId = $this->request->post('merchant_id', 0);
        $storeId = $this->request->post('store_id', 0);
        $content = $this->request->post('content', '');
        $type = $this->request->post('type', 10);
        
        if (empty($userId) || empty($merchantId) || empty($content)) {
            return $this->renderError('参数错误');
        }
        
        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $userId,
            'merchant_id' => $merchantId,
            'sender_type' => 20, // 商家
            'content' => $content,
            'type' => $type,
            'store_id' => $storeId,
            'create_time' => time(),
        ])) {
            return $this->renderSuccess([], '发送成功');
        }
        
        return $this->renderError('发送失败');
    }

    /**
     * 获取店铺列表
     * @return Json
     */
    public function stores(): Json
    {
        $list = StoreModel::where('is_delete', 0)
            ->where('is_recycle', 0)
            ->field('store_id, store_name')
            ->select();
        
        return $this->renderSuccess(['list' => $list]);
    }

    /**
     * 获取商家列表
     * @return Json
     */
    public function merchants(): Json
    {
        $storeId = $this->request->get('store_id', 0);
        
        $where = [];
        if ($storeId > 0) {
            $where[] = ['store_id', '=', $storeId];
        }
        
        $list = MerchantModel::where($where)
            ->where('status', 10) // 审核通过
            ->field('merchant_id, name, store_id')
            ->select();
        
        return $this->renderSuccess(['list' => $list]);
    }
}
