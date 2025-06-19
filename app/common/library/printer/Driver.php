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

namespace app\common\library\printer;

use app\common\library\printer\engine\Feie;
use app\common\library\printer\engine\PrintCenter;
use app\common\enum\setting\PrinterType as PrinterTypeEnum;
use cores\exception\BaseException;

/**
 * 小票打印机驱动
 * Class driver
 * @package app\common\library\printer
 */
class Driver
{
    private $printer;    // 当前打印机
    private $engine;     // 当前打印机引擎类

    // 打印机引擎列表
    private static array $engineList = [
        PrinterTypeEnum::FEI_E_YUN => Feie::class,
        PrinterTypeEnum::PRINT_CENTER => PrintCenter::class,
    ];

    /**
     * 构造方法
     * @param $printer
     * @throws BaseException
     */
    public function __construct($printer)
    {
        // 当前打印机
        $this->printer = $printer;
        // 实例化当前打印机引擎
        $this->engine = $this->getEngineClass();
    }

    /**
     * 执行打印请求
     * @param string $content
     * @return bool
     */
    public function printTicket(string $content): bool
    {
        return $this->engine->printTicket($content);
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->engine->getError();
    }

    /**
     * 获取当前的打印机引擎类
     * @return mixed
     * @throws BaseException
     */
    private function getEngineClass()
    {
        $engineClass = self::$engineList[$this->printer['printer_type']];
        if (!class_exists($engineClass)) {
            throwError("未找到打印机引擎类: {$engineClass}");
        }
        return new $engineClass($this->printer['printer_config'], (int)$this->printer['print_times']);
    }
}
