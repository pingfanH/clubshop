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

use think\facade\Filesystem;
use cores\exception\BaseException;
use app\common\library\helper;
use app\store\model\Payment as PaymentModel;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\model\PaymentTemplate as PaymentTemplateModel;
use app\common\library\wechat\payment\PlatformCertDown;

/**
 * 模型类：支付方式记录
 * Class PaymentTemplate
 * @package app\store\model
 */
class PaymentTemplate extends PaymentTemplateModel
{
    /**
     * 获取列表
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(): \think\Paginator
    {
        return $this->where('is_delete', '=', 0)
            ->order(['sort' => 'asc', $this->getPk()])
            ->paginate(15);
    }

    /**
     * 新增记录
     * @param array $data
     * @return bool|false
     * @throws BaseException
     */
    public function add(array $data): bool
    {
        // 整理保存的数据
        $config = helper::jsonDecode(htmlspecialchars_decode($data['config']));
        $methodConfig = $config[$data['method']];
        $data['store_id'] = self::$storeId;
        // 写入证书文件
        $data['config'] = [$data['method'] => $this->certFileName($data['method'], $methodConfig)];
        // 记录微信支付v3平台证书序号或微信支付公钥ID
        $data['wechatpay_serial'] = $this->wechatpaySerial($data);
        // 写入到数据库
        return $this->save($data);
    }

    /**
     * 更新记录
     * @param array $data
     * @return bool
     * @throws BaseException
     */
    public function edit(array $data): bool
    {
        // 整理保存的数据
        $config = helper::jsonDecode(htmlspecialchars_decode($data['config']));
        $methodConfig = $config[$data['method']];
        $data['store_id'] = self::$storeId;
        // 记录证书文件名
        $data['config'] = [
            $data['method'] => \array_merge($methodConfig, $this->certFileName($data['method'], $methodConfig))
        ];
        // 记录微信支付v3平台证书序号或微信支付公钥ID
        $data['wechatpay_serial'] = $this->wechatpaySerial($data);
        // 更新到数据库
        return $this->save($data) !== false;
    }

    /**
     * 记录微信支付v3平台证书序号或微信支付公钥ID
     * @param array $data
     * @return string
     */
    private function wechatpaySerial(array $data): string
    {
        if ($data['method'] === 'wechat' && $data['config']['wechat']['version'] === 'v3') {
            $mchType = $data['config']['wechat']['mchType'];
            $field1 = $mchType == 'normal' ? 'signatureMethod' : 'spSignatureMethod';
            $field2 = $mchType == 'normal' ? 'publicKeyId' : 'spPublicKeyId';
            return $data['config']['wechat'][$mchType][$field1] == 'publicKey'
                ? $data['config']['wechat'][$mchType][$field2]
                : $data['config']['wechat'][$mchType]['platformCertSerial'];
        }
        return '';
    }

    /**
     * 记录证书文件名
     * @param string $method 支付方式类型
     * @param array $config 支付配置
     * @return array
     * @throws BaseException
     */
    private function certFileName(string $method, array $config): array
    {
        if ($method === PaymentMethodEnum::WECHAT) {
            $config['normal'] = $this->saveCertFileNames($config['normal'], $method, [
                'publicKey',
                'apiclientCert',
                'apiclientKey'
            ], 'pem');
            $config['provider'] = $this->saveCertFileNames($config['provider'], $method, [
                'spPublicKey',
                'spApiclientCert',
                'spApiclientKey'
            ], 'pem');
            // 微信支付v3验签方式是平台证书时, 需自动下载平台证书
            if ($config['version'] === 'v3') {
                $mchType = $config['mchType'];
                $field1 = $mchType == 'normal' ? 'signatureMethod' : 'spSignatureMethod';
                $config[$mchType][$field1] == 'platformCert' && $this->downloadPlatformCert($config);
            }
        }
        if ($method === PaymentMethodEnum::ALIPAY && $config['signMode'] == 10) {
            $config = $this->saveCertFileNames($config, $method, [
                'appCertPublicKey',
                'alipayCertPublicKey',
                'alipayRootCert'
            ], 'crt');
        }
        return $config;
    }

    /**
     * 微信支付v3下载 [微信平台证书]
     * @param array $config
     * @return void
     * @throws BaseException
     */
    private function downloadPlatformCert(array &$config): void
    {
        // 配置项的字段（用于区分普通商户和子商户）
        $field = $config['mchType'] === 'normal' ? ['normal', 'apiclientCert', 'apiclientKey', 'apiKey', 'mchId']
            : ['provider', 'spApiclientCert', 'spApiclientKey', 'spApiKey', 'spMchId'];
        // 微信商户API证书 [公钥]
        $publicKey = parent::realPathCertFile(PaymentMethodEnum::WECHAT, $config[$field[0]][$field[1]], self::$storeId);
        // 微信商户API证书 [私钥]
        $privatekey = parent::realPathCertFile(PaymentMethodEnum::WECHAT, $config[$field[0]][$field[2]], self::$storeId);
        // 生成平台证书文件名
        $platformCertName = $this->buildCertFileName('platformCert', 'pem');
        // 证书保存的目录
        $certFolder = parent::certFolder(PaymentMethodEnum::WECHAT, self::$storeId);
        // 下载平台证书
        $downloader = new PlatformCertDown([
            'key' => $config[$field[0]][$field[3]],
            'mchid' => $config[$field[0]][$field[4]],
            'privatekey' => $privatekey,
            'publicKey' => $publicKey,
            'output' => $certFolder,
            'fileName' => $platformCertName
        ]);
        $downloader->run();
        // 记录平台证书
        $config[$field[0]]['platformCert'] = $platformCertName;
        $config[$field[0]]['platformCertSerial'] = $downloader->getPlatformCertSerial();
    }

    /**
     * 批量设置证书文件名
     * @param array $config 配置项
     * @param string $method 支付方式类型
     * @param array $certNames
     * @param string $ext 文件后缀
     * @return array
     */
    private function saveCertFileNames(array $config, string $method, array $certNames, string $ext): array
    {
        foreach ($certNames as $name) {
            $config[$name] = $this->writeFile($method, $name, $ext) ?: $config[$name];
        }
        return $config;
    }

    /**
     * 写入证书文件
     * @param string $method 支付方式类型
     * @param string $name 证书名称
     * @param string $ext 文件后缀
     * @return string|false
     */
    private function writeFile(string $method, string $name, string $ext)
    {
        // 接收上传的文件
        $file = request()->file($name);
        if (empty($file)) {
            return false;
        }
        // 证书文件夹路径
        $dirPath = "payment/{$method}/" . self::$storeId;
        // 证书文件名
        $fileName = $this->buildCertFileName($name, $ext);
        // 写入到本地服务器
        Filesystem::disk('data')->putFileAs($dirPath, $file, $fileName);
        return $fileName;
    }

    /**
     * 生成证书文件名
     * @param string $name
     * @param string $ext
     * @return string
     */
    private function buildCertFileName(string $name, string $ext): string
    {
        $hash = str_substr(md5(get_guid_v4()), 8);
        return "{$name}-{$hash}.{$ext}";
    }

    /**
     * 删除记录
     * @return bool
     */
    public function setDelete(): bool
    {
        // 验证模板是否被引用
        if (PaymentModel::existsTemplateId($this['template_id'])) {
            $this->error = '当前支付模板已被引用，需解除后才可删除';
            return false;
        }
        // 标记为已删除
        return $this->save(['is_delete' => 1]);
    }
}
