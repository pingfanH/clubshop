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
use cores\exception\BaseException;
use think\facade\Cache;
use think\model\relation\BelongsTo;
use app\common\library\helper;
use app\common\model\PaymentTemplate as PaymentTemplateModel;
use app\common\enum\Client as ClientEnum;
use app\common\enum\payment\Method as PaymentMethodEnum;

/**
 * 模型类：支付方式记录
 * Class Payment
 * @package app\common\model
 */
class Payment extends BaseModel
{
    // 定义表名
    protected $name = 'payment';

    // 定义主键
    protected $pk = 'payment_id';

    /**
     * 关联支付模板记录表
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo('PaymentTemplate');
    }

    /**
     * 获取器：其他选项
     * @param $value
     * @return array
     */
    public function getOthersAttr($value): array
    {
        return helper::jsonDecode($value);
    }

    /**
     * 修改器：其他选项
     * @param $value
     * @return string
     */
    public function setOthersAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 支付方式详情
     * @param int $paymentId
     * @return static|array|null
     */
    public static function detail(int $paymentId)
    {
        return self::get($paymentId);
    }

    /**
     * 获取支付方式配置
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\DataNotFoundException
     */
    public static function getAll(int $storeId): array
    {
        // 实例化当前模型
        $model = new static;
        // 默认的支付方式数据
        $defaultData = $model->defaultData();
        if (!$data = Cache::get("payment_{$storeId}")) {
            // 获取所有支付方式
            $data = $model->dataByStorage($storeId, $defaultData);
            // 写入缓存中
            Cache::tag('cache')->set("payment_{$storeId}", $data);
        }
        return static::resetOptions($defaultData, $data);
    }

    /**
     * 获取指定客户端的支付方式
     * @param string $client
     * @param int $storeId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getItem(string $client, int $storeId): array
    {
        return static::getAll($storeId)[$client];
    }

    /**
     * 重组缓存数据 (多维)
     * @param array $defaultData
     * @param array $data
     * @return array
     */
    private static function resetOptions(array $defaultData, array $data): array
    {
        $data = \resetOptions($defaultData, $data);
        foreach ($data as &$item) {
            $item['methods'] = array_values($item['methods']);
        }
        return $data;
    }

    /**
     * 获取所有支付方式 (从数据库中)
     * @param int $storeId
     * @param array $defaultData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function dataByStorage(int $storeId, array $defaultData): array
    {
        // 获取数据库中所有的支付方式
        $list = $this->where('store_id', '=', $storeId)->select();
        if ($list->isEmpty()) {
            return [];
        }
        // 客户端标识合集
        $clientKeys = helper::getArrayColumn($defaultData, 'client');
        // 整理数据格式
        $data = [];
        $listArr = $list->toArray();
        foreach ($clientKeys as $client) {
            $listAsClient = $this->listAsClient($listArr, $client);
            $methods = empty($listAsClient) ? [] : $this->buildMethods($client, $listAsClient);
            if (!empty($methods)) {
                $data[$client] = ['client' => $client, 'methods' => $this->buildMethods($client, $listAsClient)];
            }
        }
        return $data;
    }

    /**
     * 格式化支付方式数据 (赋值key用于合并默认数据)
     * @param string $client
     * @param array $listAsClient
     * @return array
     */
    private function buildMethods(string $client, array $listAsClient): array
    {
        $methods = helper::getArrayColumns($listAsClient, [
            'method', 'client', 'is_must_template', 'template_id',
            'is_enable', 'is_default', 'others'
        ]);
        $data = [];
        foreach ($methods as $item) {
            if ($item['client'] === $client) {
                $data["$client-{$item['method']}"] = $item;
            }
        }
        return $data;
    }

    /**
     * 默认的支付方式数据
     * @return array[]
     */
    protected function defaultData(): array
    {
        $data = [
            ClientEnum::MP_WEIXIN => $this->defaultGroup(ClientEnum::MP_WEIXIN, [
                PaymentMethodEnum::WECHAT,
                PaymentMethodEnum::BALANCE,
            ]),
            ClientEnum::H5 => $this->defaultGroup(ClientEnum::H5, [
                PaymentMethodEnum::WECHAT,
                PaymentMethodEnum::ALIPAY,
                PaymentMethodEnum::BALANCE,
            ]),
            ClientEnum::WXOFFICIAL => $this->defaultGroup(ClientEnum::WXOFFICIAL, [
                // PaymentMethodEnum::WECHAT,
                PaymentMethodEnum::BALANCE,
            ]),
            ClientEnum::APP => $this->defaultGroup(ClientEnum::APP, [
                PaymentMethodEnum::WECHAT,
                PaymentMethodEnum::ALIPAY,
                PaymentMethodEnum::BALANCE,
            ]),
        ];
        return $data;
    }

    /**
     * 默认的支付客户端分组
     * @param string $client
     * @param array $designated
     * @return array
     */
    private function defaultGroup(string $client, array $designated): array
    {
        return [
            'client' => $client,
            'name' => ClientEnum::getName($client),
            'desc' => '在' . ClientEnum::getName($client) . '付款时使用',
            'methods' => $this->defaultMethods($client, $designated)
        ];
    }

    /**
     * 默认的methods数据
     * @param string $client
     * @param array $designated
     * @return array
     */
    private function defaultMethods(string $client, array $designated): array
    {
        $record = [
            'key' => 1,
            'method' => PaymentMethodEnum::WECHAT,  // 支付方式
            'is_must_template' => true, // 是否必须使用模板
            'template_id' => 0,         // 模板ID
            'is_enable' => false,       // 是否启用该支付方式
            'is_default' => false,      // 是否为默认支付方式
            'others' => []              // 其他配置
        ];
        $defaultMethods = [];
        foreach ($designated as $key => $method) {
            $defaultMethods["{$client}-{$method}"] = \array_merge($record, [
                'key' => $key + 1,
                'method' => $method,
                'is_must_template' => !\in_array($method, [PaymentMethodEnum::BALANCE]),
                'is_enable' => $method == PaymentMethodEnum::BALANCE,
            ]);
        }
        return $defaultMethods;
    }

    /**
     * 根据client过滤list
     * @param array $listArr
     * @param string $client
     * @return array|iterable
     */
    private function listAsClient(array $listArr, string $client)
    {
        $listAsClient = helper::arrayFilterAsVal($listArr, 'client', $client);
        foreach ($listAsClient as &$item) {
            $item['is_must_template'] = (bool)$item['is_must_template'];
            $item['is_enable'] = (bool)$item['is_enable'];
            $item['is_default'] = (bool)$item['is_default'];
        }
        return $listAsClient;
    }

    /**
     * 根据指定客户端获取可用的支付方式
     * @param string $client 客户端来源
     * @param bool $isRecharge 是否用于余额充值
     * @param int|null $storeId 商城ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws BaseException
     */
    public function getMethodsByClient(string $client, bool $isRecharge = false, int $storeId = null): array
    {
        $storeId = $storeId ?: self::$storeId;
        $group = static::getItem($client, $storeId);
        $methods = [];
        foreach ($group['methods'] as $method) {
            if ($method['is_enable']) {
                // 条件: 余额充值时余额支付和线下支付不可用
                if ($isRecharge && \in_array($method['method'], [PaymentMethodEnum::BALANCE])) {
                    continue;
                }
                $methods[] = $method;
            }
        }
        if (empty($methods)) {
            throwError('很抱歉，当前没有可用的支付方式，请检查后台支付设置');
        }
        return $methods;
    }

    /**
     * 获取指定的支付方式及模板
     * @param string $method 支付方式
     * @param string $client 客户端来源
     * @param int|null $storeId 商城ID
     * @return bool|mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPaymentInfo(string $method, string $client, int $storeId = null)
    {
        // 获取当前指定的支付方式
        $methodInfo = $this->getCurrentMethod($method, $client, $storeId);
        // 获取支付模板信息
        $methodInfo['template'] = !$methodInfo['is_must_template'] ? [] : $this->getTemplateInfo($methodInfo['template_id']);
        return $methodInfo;
    }

    /**
     * 获取支付模板
     * @param int $templateId 支付模板ID
     * @return array
     * @throws BaseException
     */
    private function getTemplateInfo(int $templateId): array
    {
        return (new PaymentTemplateModel)->getTemplateInfo($templateId);
    }

    /**
     * 获取当前指定的支付方式
     * @param string $method 指定的支付方式
     * @param string $client 客户端来源
     * @param int|null $storeId 商城ID
     * @return bool|mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getCurrentMethod(string $method, string $client, int $storeId = null)
    {
        $methods = $this->getMethodsByClient($client, false, $storeId);
        $method = helper::arraySearch($methods, 'method', $method);
        if (empty($method)) {
            throwError('很抱歉，当前未找到指定的支付方式');
        }
        return $method;
    }
}