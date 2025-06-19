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

namespace app\store\model\order;

use app\common\model\order\Export as ExportModel;

/**
 * 订单导出Excel记录模型
 * Class Export
 * @package app\store\model\order
 */
class Export extends ExportModel
{
    /**
     * 获取导出记录
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(): \think\Paginator
    {
        // 获取列表记录
        $list = $this->order(['create_time' => 'desc'])->paginate(10);
        // 生成下载url
        foreach ($list as &$item) {
            $item['download_url'] = base_url() . $item['file_path'];
        }
        return $list;
    }

    /**
     * 新增数据
     * @param $data
     * @return bool
     */
    public function add($data): bool
    {
        return $this->save($data);
    }
}