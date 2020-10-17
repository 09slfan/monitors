<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\food\service;

use app\food\model\FoodPostModel;
use think\db\Query;

class PostService
{
    /**
     * 食品库查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminArticleList($filter)
    {
        return $this->adminPostList($filter);
    }

    /**
     * 食品库查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminPostList($filter, $isPage = false) {
        $join = [
            ['__USER__ u', 'a.user_id = u.id']
        ];

        $field = 'a.*,u.user_login,u.user_nickname,u.user_email';

        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__FOOD_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field = 'a.*,b.id AS post_category_id,b.list_order,b.category_id,u.user_login,u.user_nickname,u.user_email';
        }

        $foodPostModel = new FoodPostModel();
        $articles        = $foodPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.delete_time', 0)
            ->where(function (Query $query) use ($filter, $isPage) {

                $category = empty($filter['category']) ? 0 : intval($filter['category']);
                if (!empty($category)) {
                    $query->where('b.category_id', $category);
                }

                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime)) {
                    $query->where('a.published_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.published_time', '<=', $endTime);
                }

                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }
            })
            ->order('id', 'DESC')
            ->paginate(10);

        return $articles;

    }

    /**
     * 已发布食品库查询
     * @param  int $postId     食品库id
     * @param int  $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedArticle($postId, $categoryId = 0)
    {
        $foodPostModel = new FoodPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'post.id'          => $postId
            ];

            $article = $foodPostModel->alias('post')->field('post.*')
                ->where($where)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->find();
        } else {
            $where = [
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id'     => $postId
            ];

            $join    = [
                ['__FOOD_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $article = $foodPostModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->find();
        }


        return $article;
    }

    /**
     * 上一篇食品库
     * @param int $postId     食品库id
     * @param int $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedPrevArticle($postId, $categoryId = 0)
    {
        $foodPostModel = new FoodPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_status' => 1,
                'post.delete_time' => 0,
            ];

            $article = $foodPostModel
                ->alias('post')
                ->field('post.*')
                ->where($where)
                ->where('post.id', '<', $postId)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('id', 'DESC')
                ->find();

        } else {
            $where = [
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
            ];

            $join    = [
                ['__FOOD_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $article = $foodPostModel
                ->alias('post')
                ->field('post.*')
                ->join($join)
                ->where($where)
                ->where('relation.post_id', '<', $postId)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('id', 'DESC')
                ->find();
        }


        return $article;
    }

    /**
     * 下一篇食品库
     * @param int $postId     食品库id
     * @param int $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedNextArticle($postId, $categoryId = 0)
    {
        $foodPostModel = new FoodPostModel();

        if (empty($categoryId)) {

            $where = [
                'post.post_status' => 1,
                'post.delete_time' => 0,
            ];

            $article = $foodPostModel->alias('post')->field('post.*')
                ->where($where)
                ->where('post.id', '>', $postId)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('id', 'ASC')
                ->find();
        } else {
            $where = [
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,

            ];

            $join    = [
                ['__FOOD_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $article = $foodPostModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->where('relation.post_id', '>', $postId)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->order('id', 'ASC')
                ->find();
        }


        return $article;
    }

}