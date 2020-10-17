<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\food\api;

use app\food\model\FoodPostModel;
use think\db\Query;

class PageApi
{
    /**
     * 页面列表 用于模板设计
     * @param array $param
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function index($param = [])
    {
        $foodPostModel = new FoodPostModel();

        $where = [
            'post_type'      => 2,
            'post_status'    => 1,
            'delete_time'    => 0
        ];

        //返回的数据必须是数据集或数组,item里必须包括id,name,如果想表示层级关系请加上 parent_id
        return $foodPostModel->field('id,post_title AS name')
            ->where($where)
            ->where('published_time',['<', time()], ['> time', 0],'and')
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('post_title', 'like', "%{$param['keyword']}%");
                }
            })->select();
    }

    /**
     * 页面列表 用于导航选择
     * @return array
     */
    public function nav()
    {
        $foodPostModel = new FoodPostModel();

        $where = [
            'post_type'      => 2,
            'post_status'    => 1,
            'delete_time'    => 0
        ];


        $pages = $foodPostModel->field('id,post_title AS name')
            ->where('published_time',['<', time()], ['> time', 0],'and')
            ->where($where)->select();

        $return = [
            'rule'  => [
                'action' => 'food/Page/index',
                'param'  => [
                    'id' => 'id'
                ]
            ],//url规则
            'items' => $pages //每个子项item里必须包括id,name,如果想表示层级关系请加上 parent_id
        ];

        return $return;
    }

}