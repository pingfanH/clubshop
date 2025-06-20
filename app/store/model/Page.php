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

use app\common\model\Page as PageModel;
use app\common\enum\page\PageType as PageTypeEnum;

/**
 * 店铺页面模型
 * Class Page
 * @package app\common\model
 */
class Page extends PageModel
{
    /**
     * 获取列表
     * @param array $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = []): \think\Paginator
    {
        // 检索查询条件
        $filter = $this->getFilter($param);
        // 获取列表信息
        return $this->withoutField('page_data')
            ->where($filter)
            ->where(['is_delete' => 0])
            ->order(['create_time' => 'desc', $this->getPk()])
            ->paginate(15);
    }

    /**
     * 检索查询条件
     * @param array $param
     * @return array
     */
    private function getFilter(array $param = []): array
    {
        $filter = [];
        $params = $this->setQueryDefaultValue($param, [
            'name' => '',   // 页面名称
        ]);
        !empty($params['name']) && $filter[] = ['page_name', 'like', "%{$params['name']}%"];
        return $filter;
    }

    /**
     * 新增页面
     * @param array $data
     * @return bool
     */
    public function add(array $data): bool
    {
        return $this->save([
            'page_type' => 20,
            'page_name' => $data['page']['params']['name'],
            'page_data' => $data,
            'store_id' => self::$storeId
        ]);
    }

    /**
     * 更新页面
     * @param array $data
     * @return bool
     */
    public function edit(array $data): bool
    {
        $pageData = $this->getFilterPageData($data);
        return $this->save([
                'page_name' => $pageData['page']['params']['name'],
                'page_data' => $pageData
            ]) !== false;
    }

    /**
     * 过滤页面数据
     * @param array $data
     * @return array
     */
    private function getFilterPageData(array $data): array
    {
        foreach ($data['items'] as &$item) {
            if ($item['type'] === 'richText') {
                $item['params']['content'] = htmlspecialchars_decode($item['params']['content']);
            }
        }
        return $data;
    }

    /**
     * 删除记录
     * @return bool
     */
    public function setDelete(): bool
    {
        if ($this['page_type'] == PageTypeEnum::HOME) {
            $this->error = '默认首页不可以删除';
            return false;
        }
        // 删除记录
        return $this->save(['is_delete' => 1]);
    }

    /**
     * 设为默认首页
     * @return bool
     */
    public function setHome(): bool
    {
        // 取消原默认首页
        $this->where(['page_type' => PageTypeEnum::HOME])->update(['page_type' => PageTypeEnum::CUSTOM]);
        return $this->save(['page_type' => PageTypeEnum::HOME]);
    }
}
