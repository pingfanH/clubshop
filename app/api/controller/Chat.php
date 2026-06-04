<?php
declare (strict_types=1);

namespace app\api\controller;

use app\common\model\ChatMessage as ChatMessageModel;
use app\common\model\Merchant as MerchantModel;
use app\common\model\Store as StoreModel;

/**
 * 聊天控制器
 * Class Chat
 * @package app\api\controller
 */
class Chat extends Controller
{
    /**
     * 获取当前店铺的默认商家（平台自营）
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function getDefaultMerchant()
    {
        // 获取当前店铺信息
        $store = \app\common\model\Store::detail((int)$this->storeId);
        
        // 查询当前店铺的"平台自营"商家
        $merchant = MerchantModel::where('store_id', $this->storeId)
            ->where('name', '平台自营')
            ->find();
        
        // 如果不存在则自动创建
        if (empty($merchant)) {
            $merchant = new MerchantModel;
            $merchant->save([
                'user_id' => 0,
                'store_id' => $this->storeId,
                'name' => '平台自营',
                'status' => 10,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }
        
        // 返回店铺信息而不是商家信息
        return $this->renderSuccess([
            'merchant_id' => (int)$merchant['merchant_id'],
            'merchant_name' => $store ? $store['store_name'] : '店铺客服',
            'merchant_logo' => '', // 店铺logo需要单独处理
            'store_name' => $store ? $store['store_name'] : '店铺',
        ]);
    }

    /**
     * 获取会话列表
     * @return \think\response\Json
     * @throws \cores\exception\BaseException
     */
    public function sessions()
    {
        $user = $this->getLoginUser();
        
        // 获取当前店铺信息
        $store = \app\common\model\Store::detail((int)$this->storeId);
        $storeName = $store ? $store['store_name'] : '店铺';
        
        // 获取店铺logo
        $storeLogo = '';
        if ($store && !empty($store['logo_id'])) {
            $logoFile = \app\common\model\UploadFile::detail($store['logo_id']);
            $storeLogo = $logoFile ? $logoFile['preview_url'] : '';
        }
        
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
            
            // 计算未读消息数
            $unreadCount = ChatMessageModel::where('user_id', $user['user_id'])
                ->where('merchant_id', $session['merchant_id'])
                ->where('store_id', $this->storeId)
                ->where('sender_type', 20) // 商家/管理员发送的
                ->where('is_read', 0)
                ->count();
            
            $list[] = [
                'merchant_id' => (int)$session['merchant_id'],
                'merchant_name' => $storeName,
                'merchant_logo' => $storeLogo,
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

        // 获取用户头像
        $userAvatar = '';
        if (!empty($user['avatar_id'])) {
            $uploadFile = \app\common\model\UploadFile::detail($user['avatar_id']);
            $userAvatar = $uploadFile ? $uploadFile['preview_url'] : '';
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'store_user_id' => 0,
            'sender_type' => 10, // User
            'sender_name' => $user['nick_name'] ?: '用户' . $user['user_id'],
            'sender_avatar' => $userAvatar,
            'content' => $param['content'],
            'type' => $param['type'] ?? 10,
            'store_id' => $this->storeId,
            'is_read' => 0,
            'create_time' => time(),
        ])) {
            return $this->renderSuccess([
                'message_id' => $model['message_id'],
                'create_time' => $model['create_time'],
            ], '发送成功');
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

        // 获取用户头像
        $userAvatar = '';
        if (!empty($user['avatar_id'])) {
            $uploadFile = \app\common\model\UploadFile::detail($user['avatar_id']);
            $userAvatar = $uploadFile ? $uploadFile['preview_url'] : '';
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'store_user_id' => 0,
            'sender_type' => 10,
            'sender_name' => $user['nick_name'] ?: '用户' . $user['user_id'],
            'sender_avatar' => $userAvatar,
            'content' => $param['image_url'],
            'type' => 20, // 图片消息
            'store_id' => $this->storeId,
            'is_read' => 0,
            'create_time' => time(),
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

        // 获取用户头像
        $userAvatar = '';
        if (!empty($user['avatar_id'])) {
            $uploadFile = \app\common\model\UploadFile::detail($user['avatar_id']);
            $userAvatar = $uploadFile ? $uploadFile['preview_url'] : '';
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $param['merchant_id'],
            'store_user_id' => 0,
            'sender_type' => 10,
            'sender_name' => $user['nick_name'] ?: '用户' . $user['user_id'],
            'sender_avatar' => $userAvatar,
            'content' => json_encode($goodsData),
            'type' => 30, // 商品卡片
            'store_id' => $this->storeId,
            'is_read' => 0,
            'create_time' => time(),
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
        
        // 标记消息为已读
        ChatMessageModel::where('user_id', $user['user_id'])
            ->where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->where('sender_type', 20)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
        
        $list = ChatMessageModel::where('user_id', $user['user_id'])
            ->where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->order('create_time', 'asc')
            ->select();
            
        // 获取商户信息
        $merchant = MerchantModel::detail((int)$merchantId);
        // 获取店铺信息
        $store = \app\common\model\Store::detail((int)$this->storeId);
        
        // 获取店铺logo
        $storeLogo = '';
        if ($store && !empty($store['logo_id'])) {
            $logoFile = \app\common\model\UploadFile::detail($store['logo_id']);
            $storeLogo = $logoFile ? $logoFile['preview_url'] : '';
        }
        
        // 构建返回的商家信息（使用店铺名称和logo）
        $merchantInfo = [
            'merchant_id' => (int)$merchantId,
            'name' => $store ? $store['store_name'] : ($merchant ? $merchant['name'] : '客服'),
            'logo' => [
                'preview_url' => $storeLogo
            ],
        ];
            
        return $this->renderSuccess([
            'list' => $list,
            'merchant' => $merchantInfo,
        ]);
    }
}
