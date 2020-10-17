<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\food\api;

use app\food\model\FoodCategoryModel;
use think\db\Query;

class CategoryApi
{
    /**
     * 分类列表 用于模板设计
     * @param array $param
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($param = [])
    {
        $foodCategoryModel = new FoodCategoryModel();

        $where = ['delete_time' => 0];

        //返回的数据必须是数据集或数组,item里必须包括id,name,如果想表示层级关系请加上 parent_id
        return $foodCategoryModel->where($where)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $query->where('name', 'like', "%{$param['keyword']}%");
                }
            })->select();
    }

    /**
     * 分类列表 用于导航选择
     * @return array
     */
    public function nav()
    {
        $foodCategoryModel = new FoodCategoryModel();

        $where = ['delete_time' => 0];

        $categories = $foodCategoryModel->where($where)->select();

        $return = [
            //'name'  => '食品库分类',
            'rule'  => [
                'action' => 'food/List/index',
                'param'  => [
                    'id' => 'id'
                ]
            ],//url规则
            'items' => $categories //每个子项item里必须包括id,name,如果想表示层级关系请加上 parent_id
        ];

        return $return;
    }

}