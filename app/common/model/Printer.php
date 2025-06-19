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
use app\common\library\helper;

/**
 * 小票打印机模型
 * Class Printer
 * @package app\common\model
 */
class Printer extends BaseModel
{
    // 定义表名
    protected $name = 'printer';

    // 定义主键
    protected $pk = 'printer_id';

    /**
     * 自动转换printer_config为array格式
     * @param $value
     * @return array
     */
    public function getPrinterConfigAttr($value): array
    {
        return helper::jsonDecode($value);
    }

    /**
     * 自动转换printer_config为json格式
     * @param $value
     * @return string
     */
    public function setPrinterConfigAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 打印机详情
     * @param int $printerId
     * @return static|array|null
     */
    public static function detail(int $printerId)
    {
        return self::get($printerId);
    }
}
