<?php
declare (strict_types=1);

namespace app\common\model;

use cores\BaseModel;
use think\model\relation\BelongsTo;

/**
 * 聊天消息模型
 * Class ChatMessage
 * @package app\common\model
 */
class ChatMessage extends BaseModel
{
    // 定义表名
    protected $name = 'chat_message';

    // 定义主键
    protected $pk = 'message_id';

    /**
     * 关联用户
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('app\common\model\User', 'user_id', 'user_id');
    }

    /**
     * 关联商户
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo('app\common\model\Merchant', 'merchant_id', 'merchant_id');
    }
}
