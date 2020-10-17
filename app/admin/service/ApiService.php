<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\admin\service;

use think\Db;

class ApiService
{
    /**
     * 获取所有友情链接
     */
    public static function links()
    {
        return Db::name('link')->where('status', 1)->order('list_order ASC')->select();
    }

    /**
     * 获取所有幻灯片
     * @param $slideId
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function slides($slideId)
    {
        $slideCount = Db::name('slide')->where('id', $slideId)->where(['status' => 1, 'delete_time' => 0])->count();

        if ($slideCount == 0) {
            return [];
        }

        $slides = Db::name('slide_item')->where('status', 1)->where('slide_id', $slideId)->order('list_order ASC')->select();

        return $slides;
    }
}