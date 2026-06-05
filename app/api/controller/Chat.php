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
        $userId = (int)$user['user_id'];
        $userMerchantId = !empty($user['merchant_id']) ? (int)$user['merchant_id'] : 0;
        
        // 获取当前店铺信息
        $store = \app\common\model\Store::detail((int)$this->storeId);
        $storeName = $store ? $store['store_name'] : '店铺';
        $storeLogo = '';
        if ($store && !empty($store['logo_image_id'])) {
            $logoFile = \app\common\model\UploadFile::detail($store['logo_image_id']);
            $storeLogo = $logoFile ? $logoFile['preview_url'] : '';
        }
        
        $list = [];
        $seenMerchantIds = [];
        
        // 1. 当前用户参与的会话（按商家分组，与不同商家的聊天）
        $mySessions = ChatMessageModel::where('user_id', $userId)
            ->where('store_id', $this->storeId)
            ->field('merchant_id, MAX(create_time) as last_message_time, COUNT(*) as message_count')
            ->group('merchant_id')
            ->select();
        foreach ($mySessions as $session) {
            $mid = (int)$session['merchant_id'];
            $merchant = MerchantModel::detail($mid);
            if (empty($merchant)) continue;
            $seenMerchantIds[] = $mid;
            
            $lastMessage = ChatMessageModel::where('user_id', $userId)
                ->where('merchant_id', $mid)
                ->where('store_id', $this->storeId)
                ->order('create_time', 'desc')
                ->find();
            
            $unreadCount = ChatMessageModel::where('user_id', $userId)
                ->where('merchant_id', $mid)
                ->where('store_id', $this->storeId)
                ->where('sender_type', 20)
                ->where('is_read', 0)
                ->count();
            
            $isRealMerchant = !empty($merchant['user_id']);
            if ($isRealMerchant) {
                $mLogo = '';
                if ($merchant && !empty($merchant['logo_id'])) {
                    $f = \app\common\model\UploadFile::detail($merchant['logo_id']);
                    $mLogo = $f ? $f['preview_url'] : '';
                }
                $name = $merchant['name'];
                $logo = $mLogo;
            } else {
                $name = $storeName;
                $logo = $storeLogo;
            }
            
            $list[] = [
                'session_type' => 'user', // 用户维度的会话
                'merchant_id' => $mid,
                'merchant_name' => $name,
                'merchant_logo' => $logo,
                'last_message' => $lastMessage ? $lastMessage['content'] : '',
                'last_message_type' => $lastMessage ? (int)$lastMessage['type'] : 10,
                'last_message_time' => (int)$session['last_message_time'],
                'message_count' => (int)$session['message_count'],
                'unread_count' => (int)$unreadCount,
            ];
        }
        
        // 2. 如果当前用户是商家，获取发给其商家的会话（按用户分组）
        if ($userMerchantId > 0) {
            $customerSessions = ChatMessageModel::where('merchant_id', $userMerchantId)
                ->where('store_id', $this->storeId)
                ->where('user_id', '<>', $userId)
                ->field('user_id, MAX(create_time) as last_message_time, COUNT(*) as message_count')
                ->group('user_id')
                ->order('last_message_time', 'desc')
                ->select();
            
            foreach ($customerSessions as $session) {
                $customerId = (int)$session['user_id'];
                $customer = \app\common\model\User::detail($customerId);
                if (empty($customer)) continue;
                
                $customerAvatar = '';
                if (!empty($customer['avatar_id'])) {
                    $f = \app\common\model\UploadFile::detail($customer['avatar_id']);
                    $customerAvatar = $f ? $f['preview_url'] : '';
                }
                
                $lastMessage = ChatMessageModel::where('merchant_id', $userMerchantId)
                    ->where('user_id', $customerId)
                    ->where('store_id', $this->storeId)
                    ->order('create_time', 'desc')
                    ->find();
                
                $unreadCount = ChatMessageModel::where('merchant_id', $userMerchantId)
                    ->where('user_id', $customerId)
                    ->where('store_id', $this->storeId)
                    ->where('sender_type', 10)
                    ->where('is_read', 0)
                    ->count();
                
                $list[] = [
                    'session_type' => 'customer', // 客户维度的会话
                    'merchant_id' => $userMerchantId,
                    'user_id' => $customerId,
                    'customer_name' => $customer['nick_name'] ?: '用户' . $customerId,
                    'customer_avatar' => $customerAvatar,
                    'merchant_name' => '',
                    'merchant_logo' => '',
                    'last_message' => $lastMessage ? $lastMessage['content'] : '',
                    'last_message_type' => $lastMessage ? (int)$lastMessage['type'] : 10,
                    'last_message_time' => (int)$session['last_message_time'],
                    'message_count' => (int)$session['message_count'],
                    'unread_count' => (int)$unreadCount,
                ];
            }
        }
        
        // 按最后消息时间降序排序
        usort($list, function ($a, $b) {
            return $b['last_message_time'] - $a['last_message_time'];
        });
        
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

        $merchantId = (int)$param['merchant_id'];
        $senderType = (int)($param['sender_type'] ?? 10);
        
        // 如果以商家身份发送，验证当前用户是否为该商家所有者
        if ($senderType === 20) {
            $merchant = MerchantModel::where('merchant_id', $merchantId)
                ->where('user_id', $user['user_id'])
                ->find();
            if (empty($merchant)) {
                return $this->renderError('您不是该商家的所有者');
            }
            $senderName = $merchant['name'];
            $senderAvatar = '';
            if (!empty($merchant['logo_id'])) {
                $uploadFile = \app\common\model\UploadFile::detail($merchant['logo_id']);
                $senderAvatar = $uploadFile ? $uploadFile['preview_url'] : '';
            }
        } else {
            // 用户身份发送
            $senderName = $user['nick_name'] ?: '用户' . $user['user_id'];
            $senderAvatar = '';
            if (!empty($user['avatar_id'])) {
                $uploadFile = \app\common\model\UploadFile::detail($user['avatar_id']);
                $senderAvatar = $uploadFile ? $uploadFile['preview_url'] : '';
            }
        }

        $model = new ChatMessageModel;
        if ($model->save([
            'user_id' => $user['user_id'],
            'merchant_id' => $merchantId,
            'store_user_id' => 0,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'sender_avatar' => $senderAvatar,
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
        $merchantId = (int)$this->request->get('merchant_id');
        
        if (empty($merchantId)) {
            return $this->renderError('参数错误');
        }
        
        // 标记该商家发来的未读消息为已读
        ChatMessageModel::where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->where('sender_type', 20)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
        
        // 获取该商家的所有消息（不限user_id，双方都能看到全部）
        $list = ChatMessageModel::where('merchant_id', $merchantId)
            ->where('store_id', $this->storeId)
            ->order('create_time', 'asc')
            ->select();
            
        // 获取商户信息
        $merchant = MerchantModel::detail($merchantId);
        // 获取店铺信息
        $store = \app\common\model\Store::detail((int)$this->storeId);
        
        // 根据商家类型返回不同信息
        $isRealMerchant = $merchant && !empty($merchant['user_id']);
        if ($isRealMerchant) {
            // 真实商家：显示商家名称和logo
            $merchantLogo = '';
            if ($merchant && !empty($merchant['logo_id'])) {
                $logoFile = \app\common\model\UploadFile::detail($merchant['logo_id']);
                $merchantLogo = $logoFile ? $logoFile['preview_url'] : '';
            }
            $merchantInfo = [
                'merchant_id' => (int)$merchantId,
                'name' => $merchant['name'],
                'logo' => [
                    'preview_url' => $merchantLogo
                ],
            ];
        } else {
            // 平台自营：显示店铺名称和logo
            $storeLogo = '';
            if ($store && !empty($store['logo_image_id'])) {
                $logoFile = \app\common\model\UploadFile::detail($store['logo_image_id']);
                $storeLogo = $logoFile ? $logoFile['preview_url'] : '';
            }
            $merchantInfo = [
                'merchant_id' => (int)$merchantId,
                'name' => $store ? $store['store_name'] : ($merchant ? $merchant['name'] : '客服'),
                'logo' => [
                    'preview_url' => $storeLogo
                ],
            ];
        }
            
        return $this->renderSuccess([
            'list' => $list,
            'merchant' => $merchantInfo,
        ]);
    }
}
