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

namespace app\common\service\message;

use cores\traits\ErrorTrait;
use app\common\service\BaseService;
use app\common\library\wechat\WxSubMsg;
use app\common\library\sms\Driver as SmsDriver;
use app\common\model\store\Setting as SettingModel;
use app\common\model\wxapp\Setting as WxappSettingModel;

/**
 * 消息通知服务[基类]
 * Class Basics
 * @package app\common\service\message
 */
abstract class Basics extends BaseService
{
    use ErrorTrait;

    // 参数列表
    protected $param = [];

    // 商城ID
    protected ?int $storeId;

    /**
     * 构造方法
     * Basics constructor.
     * @param int $storeId
     */
    public function __construct(int $storeId)
    {
        parent::__construct();
        $this->storeId = $storeId;
    }

    /**
     * 发送消息通知
     * @param array $param 参数
     * @return mixed
     */
    abstract public function send(array $param);

    /**
     * 发送短信通知
     * @param string $sceneValue 短信发送场景
     * @param string $acceptPhone 接收的手机号
     * @param array $templateParams 短信模板参数
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function sendSms(string $sceneValue, string $acceptPhone, array $templateParams): bool
    {
        // 短信通知设置
        $smsConfig = $this->getSmsConfig();
        // 短信服务
        $SmsDriver = new SmsDriver($smsConfig);
        try {
            // 判断短信服务是否开启
            $this->isEnableSms($smsConfig, $sceneValue);
            // 获取短信模板ID
            $templateCode = $this->getSmsTemplateCode($smsConfig, $sceneValue);
            // 执行发送短信
            $SmsDriver->sendSms($acceptPhone, $templateCode, $templateParams);
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取短信通知设置
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getSmsConfig()
    {
        return SettingModel::getItem('sms', $this->storeId);
    }

    /**
     * 判断短信服务是否开启
     * @param array $smsConfig 短信设置
     * @param string $sceneValue 短信发送场景
     * @throws \cores\exception\BaseException
     */
    private function isEnableSms(array $smsConfig, string $sceneValue)
    {
        if (!$smsConfig['scene'][$sceneValue]['isEnable']) {
            throwError('短信通知服务未开启，请在商户后台中设置');
        }
    }

    /**
     * 获取短信模板ID
     * @param array $smsConfig 短信设置
     * @param string $sceneValue 短信发送场景
     * @return string
     */
    private function getSmsTemplateCode(array $smsConfig, string $sceneValue): string
    {
        return $smsConfig['scene'][$sceneValue]['templateCode'];
    }

    /**
     * 字符串截取前20字符
     * [用于兼容thing数据类型]
     * @param string $content
     * @param int $length
     * @return bool|string
     */
    protected function getSubstr(string $content, int $length = 20)
    {
        return str_substr($content, $length);
    }
}
