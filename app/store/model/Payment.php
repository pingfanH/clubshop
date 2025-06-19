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

use think\facade\Cache;
use app\common\model\Payment as PaymentModel;

/**
 * 模型类：支付方式记录
 * Class Payment
 * @package app\store\model
 */
class Payment extends PaymentModel
{
    /**
     * 更新支付方式设置
     * @param array $form
     * @return bool
     */
    public function updateOptions(array $form): bool
    {
        // 生成写入的数据
        $dataList = $this->buildData($form);
        // 删除所有的支付方式记录
        static::deleteAll([]);
        // 批量写入商品图片记录
        static::increased($dataList);
        // 删除系统设置缓存
        Cache::delete('payment_' . self::$storeId);
        return true;
    }

    /**
     * 验证模板ID是否存在
     * @param int $templateId
     * @return bool
     */
    public static function existsTemplateId(int $templateId): bool
    {
        return (bool)(new static)->where('template_id', '=', $templateId)->count();
    }

    /**
     * 批量写入支付方式记录
     * @param array[] $dataset
     * @return void
     */
    private static function increased(array $dataset): void
    {
        (new static)->addAll($dataset);
    }

    /**
     * 将表单数据生成为数据库格式记录
     * @param array $form
     * @return array[]
     */
    private function buildData(array $form): array
    {
        $data = [];
        foreach ($form as $item) {
            foreach ($item['methods'] as $method) {
                $data[] = [
                    'client' => $item['client'],
                    'method' => $method['method'],
                    'is_must_template' => (int)$method['is_must_template'],
                    'template_id' => $method['template_id'],
                    'is_enable' => (int)$method['is_enable'],
                    'is_default' => (int)$method['is_default'],
                    'others' => [],
                    'store_id' => self::$storeId,
                ];
            }
        }
        return $data;
    }
}
