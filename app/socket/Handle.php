<?php
namespace app\socket;

use workerman\connection\TcpConnection;
use app\api\model\User as UserModel;
use app\common\model\ChatMessage;
use app\common\model\Merchant;

class Handle
{
    // 存储客户端连接 user_id => connection
    protected static $users = [];

    public function onWorkerStart($worker)
    {
        echo "WebSocket Worker starting...\n";
    }

    public function onConnect(TcpConnection $connection)
    {
        // 可以在这里做一些初始化
    }

    public function onMessage(TcpConnection $connection, $data)
    {
        $data = json_decode($data, true);
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'login':
                $this->handleLogin($connection, $data);
                break;
            case 'chat':
                $this->handleChat($connection, $data);
                break;
            case 'ping':
                $connection->send(json_encode(['type' => 'pong']));
                break;
        }
    }

    public function onClose(TcpConnection $connection)
    {
        if (isset($connection->uid)) {
            unset(self::$users[$connection->uid]);
        }
    }

    /**
     * 处理登录
     */
    protected function handleLogin($connection, $data)
    {
        if (empty($data['token'])) {
            return;
        }
        try {
            // 这里我们手动调用 UserModel::getUserByToken
            // 注意：由于是在 Worker 环境下，可能没有 HTTP Request 上下文
            // UserModel::getUserByToken 依赖 Cache，通常没问题
            $user = UserModel::getUserByToken($data['token']);
            if ($user) {
                $connection->uid = $user['user_id'];
                self::$users[$user['user_id']] = $connection;
                $connection->send(json_encode(['type' => 'login', 'status' => 'success', 'user_id' => $user['user_id']]));
            } else {
                $connection->send(json_encode(['type' => 'login', 'status' => 'fail', 'msg' => 'Invalid token']));
            }
        } catch (\Exception $e) {
            $connection->send(json_encode(['type' => 'login', 'status' => 'fail', 'msg' => $e->getMessage()]));
        }
    }

    /**
     * 处理聊天消息
     */
    protected function handleChat($connection, $data)
    {
        if (!isset($connection->uid)) {
            $connection->send(json_encode(['type' => 'error', 'msg' => 'Not logged in']));
            return;
        }

        $fromUserId = $connection->uid;
        $content = $data['content'] ?? '';
        $storeId = 10001; // 默认商城ID

        if (empty($content)) return;

        // 场景 A: 用户发给商家
        if (isset($data['merchant_id']) && !empty($data['merchant_id'])) {
            $merchantId = $data['merchant_id'];
            
            // 1. 保存消息
            $model = new ChatMessage;
            $saveData = [
                'user_id' => $fromUserId,
                'merchant_id' => $merchantId,
                'sender_type' => 10, // 用户发送
                'content' => $content,
                'type' => 10, // 文本
                'store_id' => $storeId
            ];
            $model->save($saveData);
            
            // 2. 查找商家对应的 user_id 并推送
            $merchant = Merchant::detail($merchantId);
            if ($merchant && isset(self::$users[$merchant['user_id']])) {
                // 推送给商家
                $pushData = $saveData;
                $pushData['create_time'] = date('Y-m-d H:i:s');
                self::$users[$merchant['user_id']]->send(json_encode(['type' => 'chat_message', 'data' => $pushData]));
            }
            
            // 3. 回复发送者确认
            $connection->send(json_encode(['type' => 'chat_ack', 'status' => 'success', 'data' => $saveData]));
        }
        
        // 场景 B: 商家发给用户 (假设当前连接用户是商家)
        // 需要前端传递 to_user_id
        elseif (isset($data['to_user_id']) && !empty($data['to_user_id'])) {
            $toUserId = $data['to_user_id'];
            
            // 检查当前用户是否是商家
            $merchant = Merchant::where('user_id', $fromUserId)->find();
            if (!$merchant) {
                $connection->send(json_encode(['type' => 'error', 'msg' => 'You are not a merchant']));
                return;
            }

            // 1. 保存消息
            $model = new ChatMessage;
            $saveData = [
                'user_id' => $toUserId, // 目标用户ID
                'merchant_id' => $merchant['merchant_id'],
                'sender_type' => 20, // 商家发送
                'content' => $content,
                'type' => 10,
                'store_id' => $storeId
            ];
            $model->save($saveData);

            // 2. 推送给目标用户
            if (isset(self::$users[$toUserId])) {
                $pushData = $saveData;
                $pushData['create_time'] = date('Y-m-d H:i:s');
                self::$users[$toUserId]->send(json_encode(['type' => 'chat_message', 'data' => $pushData]));
            }

            // 3. 回复发送者确认
            $connection->send(json_encode(['type' => 'chat_ack', 'status' => 'success', 'data' => $saveData]));
        }
    }
}
