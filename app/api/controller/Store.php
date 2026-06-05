<?php
declare (strict_types=1);

namespace app\api\controller;

use think\response\Json;
use app\api\service\Store as StoreService;
use app\common\model\Store as StoreModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商城基础信息
 * Class Store
 * @package app\api\controller
 */
class Store extends Controller
{
    /**
     * 获取商城基础信息
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function data(): Json
    {
        $service = new StoreService;
        return $this->renderSuccess($service->data());
    }

    /**
     * 获取附近店铺列表（按距离排序）
     * @param float $longitude 当前经度
     * @param float $latitude 当前纬度
     * @return Json
     */
    public function nearby(float $longitude, float $latitude): Json
    {
        // 获取所有未删除的店铺
        $stores = StoreModel::withoutGlobalScope()
            ->where('is_delete', 0)
            ->where('is_recycle', 0)
            ->where('longitude', '>', 0)
            ->where('latitude', '>', 0)
            ->select();

        $list = [];
        foreach ($stores as $store) {
            // 使用 Haversine 公式计算距离（单位：千米）
            $distance = $this->calculateDistance(
                $latitude, $longitude,
                (float)$store['latitude'], (float)$store['longitude']
            );

            $list[] = [
                'store_id' => (int)$store['store_id'],
                'store_name' => $store['store_name'],
                'distance' => round($distance, 2),
                'distance_text' => $this->formatDistance($distance),
                'longitude' => (float)$store['longitude'],
                'latitude' => (float)$store['latitude'],
                'address' => $store['location_address'] ?? '',
            ];
        }

        // 按距离排序
        usort($list, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return $this->renderSuccess(['list' => $list]);
    }

    /**
     * 计算两点间的距离（Haversine）
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // 地球半径（千米）
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * 格式化距离
     */
    private function formatDistance(float $distance): string
    {
        if ($distance < 1) {
            return round($distance * 1000) . 'm';
        }
        return round($distance, 1) . 'km';
    }
}