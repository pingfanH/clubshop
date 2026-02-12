<?php
declare (strict_types=1);

namespace app\common\model;

use cores\BaseModel;
use think\model\relation\HasOne;
use think\model\relation\BelongsTo;

/**
 * 商户模型
 * Class Merchant
 * @package app\common\model
 */
class Merchant extends BaseModel
{
    // 定义表名
    protected $name = 'merchant';

    // 定义主键
    protected $pk = 'merchant_id';

    /**
     * 关联Logo图片
     * @return HasOne
     */
    public function logo(): HasOne
    {
        return $this->hasOne('UploadFile', 'file_id', 'logo_id');
    }

    /**
     * 关联用户
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('User', 'user_id', 'user_id');
    }

    /**
     * 获取商户详情
     * @param int $merchantId
     * @return static|array|null
     */
    public static function detail(int $merchantId)
    {
        return self::with(['logo'])->find($merchantId);
    }
}
