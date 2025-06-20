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

namespace app\store\model;

use app\common\model\Printer as PrinterModel;

/**
 * 小票打印机模型
 * Class Printer
 * @package app\store\model
 */
class Printer extends PrinterModel
{
    /**
     * 获取全部记录
     * @return Printer[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAll()
    {
        return (new static)->where('is_delete', '=', 0)
            ->order(['sort', 'create_time'])
            ->select();
    }

    /**
     * 获取列表记录
     * @param array $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = []): \think\Paginator
    {
        // 查询模型
        $query = $this->getNewQuery();
        // 查询参数
        $params = $this->setQueryDefaultValue($param, [
            'search' => '',          // 关键词
            'printer_type' => '',    // 打印机类型
        ]);
        // 检索关键词
        !empty($params['search']) && $query->where('printer_name', 'like', "%{$params['search']}%");
        // 检索打印机类型
        !empty($params['printer_type']) && $query->where('printer_type', '=', $params['printer_type']);
        // 查询数据
        return $query->where('is_delete', '=', 0)
            ->order(['sort', 'create_time'])
            ->paginate(15);
    }

    /**
     * 添加新记录
     * @param array $data
     * @return bool|false
     */
    public function add(array $data): bool
    {
        $data['store_id'] = self::$storeId;
        return $this->save($data);
    }

    /**
     * 编辑记录
     * @param array $data
     * @return bool
     */
    public function edit(array $data): bool
    {
        return $this->save($data);
    }

    /**
     * 删除记录
     * @return bool
     */
    public function setDelete(): bool
    {
        return $this->save(['is_delete' => 1]);
    }
}
