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
use app\common\library\helper;
use app\common\enum\payment\Method as PaymentMethodEnum;

/**
 * 模型类：支付模板记录
 * Class PaymentTemplate
 * @package app\common\model
 */
class PaymentTemplate extends BaseModel
{
    // 定义表名
    protected $name = 'payment_template';

    // 定义主键
    protected $pk = 'template_id';

    /**
     * 获取器：支付配置
     * @param string $value
     * @return array
     */
    public function getConfigAttr(string $value): array
    {
        $data = helper::jsonDecode($value);
        return \resetOptions((new static)->defaultData(), $data);
    }

    /**
     * 修改器：支付配置
     * @param $value
     * @return string
     */
    public function setConfigAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 默认配置
     * @return array
     */
    public function defaultData(): array
    {
        return [
            PaymentMethodEnum::WECHAT => [
                // 微信商户号类型（normal普通商户 provider子商户）
                'mchType' => 'normal',
                // 微信支付API版本（v2和v3）
                'version' => 'v2',
                'normal' => [
                    'appId' => '',
                    'mchId' => '',
                    'apiKey' => '',
                    'signatureMethod' => 'platformCert',   // 验签方式（platformCert平台证书 publicKey微信支付公钥）
                    'publicKeyId' => '',
                    'publicKey' => '',
                    'apiclientCert' => '',
                    'apiclientKey' => '',
                ],
                'provider' => [
                    'spAppId' => '',
                    'spMchId' => '',
                    'spApiKey' => '',
                    'subAppId' => '',
                    'subMchId' => '',
                    'spSignatureMethod' => 'platformCert', // 验签方式（platformCert平台证书 publicKey微信支付公钥）
                    'spPublicKeyId' => '',
                    'spPublicKey' => '',
                    'spApiclientCert' => '',
                    'spApiclientKey' => ''
                ]
            ],
            PaymentMethodEnum::ALIPAY => [
                'appId' => '',
                'signType' => 'RSA2',
                'signMode' => 10,
                'alipayPublicKey' => '',
                'appCertPublicKey' => '',
                'alipayCertPublicKey' => '',
                'alipayRootCert' => '',
                'merchantPrivateKey' => ''
            ],
        ];
    }

    /**
     * 支付方式详情
     * @param int $templateId
     * @return static|array|null
     */
    public static function detail(int $templateId)
    {
        return self::get($templateId);
    }

    /**
     * 获取全部支付模板
     * @return array|static[]|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll()
    {
        return $this->where('is_delete', '=', 0)
            ->order(['sort' => 'asc', $this->getPk()])
            ->select();
    }

    /**
     * 获取支付模板
     * @param int $templateId 支付模板ID
     * @return array
     * @throws BaseException
     */
    public function getTemplateInfo(int $templateId): array
    {
        // 支付模板记录
        $template = static::detail($templateId);
        if (empty($template) || $template['is_delete']) {
            throwError('很抱歉，当前不存在支付模板');
        }
        // 格式化为数组格式
        $info = $template->toArray();
        // 记录证书文件名
        $methodConfig = $info['config'][$info['method']];
        $info['config'] = [$info['method'] => \array_merge(
            $methodConfig,
            $this->certFileName($info['method'], $methodConfig, $template['store_id'])
        )];
        return $info;
    }

    /**
     * 记录证书文件名
     * @param string $method 支付方式类型
     * @param array $config 支付配置
     * @param int $storeId
     * @return array
     */
    private function certFileName(string $method, array $config, int $storeId): array
    {
        if ($method === PaymentMethodEnum::WECHAT) {
            $config['normal'] = $this->setCertFileNames($config['normal'], $method, [
                'publicKey',
                'apiclientCert',
                'apiclientKey',
                'platformCert'
            ], $storeId);
            $config['provider'] = $this->setCertFileNames($config['provider'], $method, [
                'spPublicKey',
                'spApiclientCert',
                'spApiclientKey',
                'platformCert'
            ], $storeId);
        }
        if ($method === PaymentMethodEnum::ALIPAY && $config['signMode'] == 10) {
            $config = $this->setCertFileNames($config, $method, [
                'appCertPublicKey',
                'alipayCertPublicKey',
                'alipayRootCert'
            ], $storeId);
        }
        return $config;
    }

    /**
     * 批量设置证书文件名
     * @param array $config 配置项
     * @param string $method 支付方式类型
     * @param array $certNames
     * @param int $storeId 商城ID
     * @return array
     */
    private function setCertFileNames(array $config, string $method, array $certNames, int $storeId): array
    {
        foreach ($certNames as $name) {
            // 此处的判断是兼容V2模式下没有platformCert
            if (isset($config[$name])) {
                $config["{$name}Path"] = self::realPathCertFile($method, $config[$name], $storeId);
            }
        }
        return $config;
    }

    /**
     * 获取证书文件绝对路径
     * @param string $method 支付方式类型
     * @param string $fileName 证书文件名称
     * @return string|false
     */
    public static function realPathCertFile(string $method, string $fileName, int $storeId)
    {
        // 文件路径
        $filePath = self::certFolder($method, $storeId) . "/{$fileName}";
        return !empty($fileName) ? realpath($filePath) : '';
    }

    /**
     * 获取证书文件所在目录
     * @param string $method
     * @param int $storeId
     * @return string
     */
    public static function certFolder(string $method, int $storeId): string
    {
        return data_path() . "payment/{$method}/{$storeId}";
    }
}