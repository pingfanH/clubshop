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

namespace app\common\library\wechat\payment;

use WeChatPay\Builder;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\ClientDecoratorInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use cores\exception\BaseException;

/**
 * 下载「微信支付平台证书」
 * PlatformCertDown class
 * @package app\common\library\wechat
 */
class PlatformCertDown
{
    // 微信支付API网关
    private const DEFAULT_BASE_URI = 'https://api.mch.weixin.qq.com/';

    // 配置参数
    private array $opts = [];

    /**
     * 构造方法
     * PlatformCertDown constructor.
     * @param array $opts
     * @throws BaseException
     */
    public function __construct(array $opts)
    {
        $this->opts = $opts + [
                // 读取公钥中的序列号
                'serialno' => $this->serialno($opts['publicKey'])
            ];
    }

    /**
     * 执行下载
     */
    public function run(): void
    {
        // 执行下载
        $this->job($this->opts);
    }

    /**
     * 读取公钥中的序列号
     * @return mixed
     * @throws BaseException
     */
    public function getPlatformCertSerial()
    {
        $outputDir = $this->opts['output'] ?? \sys_get_temp_dir();
        return $this->serialno($outputDir . \DIRECTORY_SEPARATOR . $this->opts['fileName']);
    }

    /**
     * 读取公钥中的序列号
     * @param string $publicKey
     * @return mixed
     * @throws BaseException
     */
    private function serialno(string $publicKey)
    {
        $content = file_get_contents($publicKey);
        $plaintext = !empty($content) ? openssl_x509_parse($content) : false;
        empty($plaintext) && throwError('证书文件(CERT)不正确');
        return $plaintext['serialNumberHex'];
    }

    /**
     * @param array<string,string|true> $opts
     *
     * @return void
     */
    private function job(array $opts): void
    {
        static $certs = ['any' => null];

        $outputDir = $opts['output'] ?? \sys_get_temp_dir();
        $apiv3Key = (string)$opts['key'];

        $instance = Builder::factory([
            'mchid' => $opts['mchid'],
            'serial' => $opts['serialno'],
            'privateKey' => \file_get_contents((string)$opts['privatekey']),
            'certs' => &$certs,
            'base_uri' => (string)($opts['baseuri'] ?? self::DEFAULT_BASE_URI),
        ]);

        /** @var \GuzzleHttp\HandlerStack $stack */
        $stack = $instance->getDriver()->select(ClientDecoratorInterface::JSON_BASED)->getConfig('handler');
        // The response middle stacks were executed one by one on `FILO` order.
        $stack->after('verifier', Middleware::mapResponse(self::certsInjector($apiv3Key, $certs)), 'injector');
        $stack->before('verifier', Middleware::mapResponse(
            self::certsRecorder((string)$outputDir, $opts['fileName'], $certs)),
            'recorder'
        );

        $instance->chain('v3/certificates')->getAsync(
//            ['debug' => true]
        )->otherwise(static function ($exception) {
            if ($exception instanceof RequestException && $exception->hasResponse()) {
                /** @var ResponseInterface $response */
                $response = $exception->getResponse();
                throwError('平台证书文件获取失败：' . (string)$response->getBody());
            }
            throwError($exception->getMessage());
        })->wait();
    }

    /**
     * 在`verifier`执行之前, 解密平台证书
     *
     * @param string $apiv3Key
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsInjector(string $apiv3Key, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($apiv3Key, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{encrypt_certificate:object{serial_no:string,nonce:string,associated_data:string}}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_map(static function ($row) use ($apiv3Key, &$certs) {
                $cert = $row->encrypt_certificate;
                try {
                    $certs[$row->serial_no] = AesGcm::decrypt($cert->ciphertext, $apiv3Key, $cert->nonce, $cert->associated_data);
                } catch (\Throwable $e) {
                    throwError('支付密钥(APIKEY) 或 证书文件(KEY)不正确，请重新输入');
                }
            }, $data);

            return $response;
        };
    }

    /**
     * 将平台证书写入硬盘
     *
     * @param string $outputDir
     * @param string fileName
     * @param array<string,?string> $certs
     *
     * @return callable(ResponseInterface)
     */
    private static function certsRecorder(string $outputDir, string $fileName, array &$certs): callable
    {
        return static function (ResponseInterface $response) use ($outputDir, $fileName, &$certs): ResponseInterface {
            $body = (string)$response->getBody();
            /** @var object{data:array<object{effective_time:string,expire_time:string:serial_no:string}>} $json */
            $json = \json_decode($body);
            $data = \is_object($json) && isset($json->data) && \is_array($json->data) ? $json->data : [];
            \array_walk($data, static function ($row, $index, $certs) use ($outputDir, $fileName) {
                $serialNo = $row->serial_no;
                $outpath = $outputDir . \DIRECTORY_SEPARATOR . $fileName;

//                self::prompt(
//                    'Certificate #' . $index . ' {',
//                    '    Serial Number: ' . $serialNo,
//                    '    Not Before: ' . (new \DateTime($row->effective_time))->format('Y-m-d H:i:s'),
//                    '    Not After: ' . (new \DateTime($row->expire_time))->format('Y-m-d H:i:s'),
//                    '    Saved to: ' . $outpath,
//                    '    Content:', $certs[$serialNo] ?? '',
//                    '}'
//                );

                \file_put_contents($outpath, $certs[$serialNo]);
            }, $certs);

            return $response;
        };
    }

    /**
     * 输出信息
     * @param string $messages
     */
    private static function prompt(...$messages): void
    {
        \array_walk($messages, static function (string $message): void {
            \printf('%s%s', $message, \PHP_EOL);
        });
    }
}