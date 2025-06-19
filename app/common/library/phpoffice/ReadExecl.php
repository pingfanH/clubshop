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

namespace app\common\library\phpoffice;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReadExecl
{
    /**
     * 使用PHPEXECL导入
     * @param string $file 文件地址
     * @param int $sheet 工作表sheet(传0则获取第一个sheet)
     * @param int $columnCnt 列数(传0则自动获取最大列)
     * @param int $rowCnt 行数(传0则自动获取最大行)
     * @param array $options 操作选项
     *                          array mergeCells 合并单元格数组
     *                          array formula    公式数组
     *                          array format     单元格格式数组
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \cores\exception\BaseException
     */
    public static function load(string $file, int $sheet = 0, int $columnCnt = 0, int $rowCnt = 0, array &$options = []): array
    {
        /* 转码 */
        // $file = iconv("utf-8", "gb2312", $file);
        if (empty($file) or !file_exists($file)) {
            throwError('文件不存在!');
        }
        // 创建阅读器
        $objRead = self::createXlsxReader($file);
        /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
        empty($options) && $objRead->setReadDataOnly(true);
        /* 建立excel对象 */
        $obj = $objRead->load($file);
        /* 获取指定的sheet表 */
        $currSheet = $obj->getSheet($sheet);
        if (isset($options['mergeCells'])) {
            /* 读取合并行列 */
            $options['mergeCells'] = $currSheet->getMergeCells();
        }
        if (0 == $columnCnt) {
            /* 取得最大的列号 */
            $columnH = $currSheet->getHighestColumn();
            /* 兼容原逻辑，循环时使用的是小于等于 */
            $columnCnt = Coordinate::columnIndexFromString($columnH);
        }
        /* 获取总行数 */
        $rowCnt = $rowCnt ?: $currSheet->getHighestRow();
        $data = [];
        /* 读取内容 */
        for ($_row = 1; $_row <= $rowCnt; $_row++) {
            $isNull = true;
            for ($_column = 1; $_column <= $columnCnt; $_column++) {
                $cellName = Coordinate::stringFromColumnIndex($_column);
                $cellId = $cellName . $_row;
                $cell = $currSheet->getCell($cellId);
                if (isset($options['format'])) {
                    /* 获取格式 */
                    $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                    /* 记录格式 */
                    $options['format'][$_row][$cellName] = $format;
                }
                if (isset($options['formula'])) {
                    /* 获取公式，公式均为=号开头数据 */
                    $formula = $currSheet->getCell($cellId)->getValue();
                    if (0 === strpos($formula, '=')) {
                        $options['formula'][$cellName . $_row] = $formula;
                    }
                }
                if (isset($format) && 'm/d/yyyy' == $format) {
                    /* 日期格式翻转处理 */
                    $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
                }
                $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());
                if (!empty($data[$_row][$cellName])) {
                    $isNull = false;
                }
            }
            /* 判断是否整行数据为空，是的话删除该行数据 */
            if ($isNull) {
                unset($data[$_row]);
            }
        }
        return $data;
    }

    /**
     * 创建Xlsx阅读器
     * @param string $file
     * @return \PhpOffice\PhpSpreadsheet\Reader\IReader|Xlsx
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \cores\exception\BaseException
     */
    private static function createXlsxReader(string $file)
    {
        /** @var Xlsx $IReader */
        $IReader = IOFactory::createReader('Xlsx');
        if (!$IReader->canRead($file)) {
            /** @var Xls $objRead */
            $IReader = IOFactory::createReader('Xls');
            if (!$IReader->canRead($file)) {
                throwError('只支持导入Excel文件!');
            }
        }
        return $IReader;
    }
}