<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\portal\service;

use app\portal\model\PortalPostModel;
use think\db\Query;

class PostService {
    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function postList($filter) {
        return $this->adminPostList($filter);
    }
    /**
     * 文章查询
     * @param $filter
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminArticleList($filter) {
        return $this->adminPostList($filter);
    }

    /**
     * 文章查询
     * @param      $filter
     * @param bool $isPage
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function adminPostList($filter, $isPage = false) {
        $join = [ ['__USER__ u', 'a.user_id = u.id'] ];
        if (isset($filter['page_size']) and !empty($filter['page_size'])) {
            $page_size = $filter['page_size'];
        } else {
            $page_size = 10;
        }

        $field = 'a.*,u.user_login,u.user_nickname';

        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
            $field .= ',b.id AS post_category_id,b.list_order,b.category_id';
        }

        $school_id = empty($filter['school_id']) ? 0 : intval($filter['school_id']);
        if (!empty($school_id)) {
            array_push($join, [
                '__PORTAL_SCHOOL_POST__ c', 'a.id = c.post_id'
            ]);
            $field .= ',c.school_id';
        }

        $portalPostModel = new PortalPostModel();
        $articles        = $portalPostModel->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            //->where('a.post_status', 1)
            ->where('a.delete_time', 0)
            ->where(function (Query $query) use ($filter, $isPage) {
                $category = empty($filter['category']) ? 0 : intval($filter['category']);
                if (!empty($category)) {
                    $query->where('b.category_id', $category);
                }
                $school_id = empty($filter['school_id']) ? 0 : intval($filter['school_id']);
                if (!empty($school_id)) {
                    $query->where('c.school_id', $school_id);
                }
                $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
                $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
                if (!empty($startTime)) {
                    $query->where('a.published_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('a.published_time', '<=', $endTime);
                }

                $post_status = empty($filter['post_status']) ? '' : $filter['post_status'];
                if (!empty($post_status)) {
                    $query->where('a.post_status', 'eq', $post_status);
                }

                $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
                if (!empty($keyword)) {
                    $query->where('a.post_title', 'like', "%$keyword%");
                }
            })
            ->order('a.update_time', 'DESC')
            ->paginate($page_size);

        return $articles;

    }

    /**
     * 已发布文章查询
     * @param  int $postId     文章id
     * @param int  $categoryId 分类id
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publishedArticle($postId, $categoryId = 0) {
        $portalPostModel = new PortalPostModel();
        if (empty($categoryId)) {
            $where = [
                'post.post_type'   => 1,
                'post.post_status' => 1,
                'post.delete_time' => 0,
                'post.id'          => $postId
            ];

            $article = $portalPostModel->alias('post')->field('post.*')
                ->where($where)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->find();
        } else {
            $where = [
                'post.post_type'       => 1,
                'post.post_status'     => 1,
                'post.delete_time'     => 0,
                'relation.category_id' => $categoryId,
                'relation.post_id'     => $postId
            ];

            $join    = [
                ['__PORTAL_CATEGORY_POST__ relation', 'post.id = relation.post_id']
            ];
            $article = $portalPostModel->alias('post')->field('post.*')
                ->join($join)
                ->where($where)
                ->where('post.published_time', ['< time', time()], ['> time', 0], 'and')
                ->find();
        }

        return $article;
    }
}