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

namespace app\common\model;

use cores\BaseModel;
use think\model\relation\BelongsTo;

/**
 * 售后单图片模型
 * Class OrderRefundImage
 * @package app\common\model
 */
class OrderRefundImage extends BaseModel
{
    // 定义表名
    protected $name = 'order_refund_image';

    // 定义主键
    protected $pk = 'id';

    protected $updateTime = false;

    /**
     * 关联文件库
     * @return BelongsTo
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo('UploadFile', 'image_id', 'file_id')->bind(['image_url' => 'preview_url']);
    }
}
