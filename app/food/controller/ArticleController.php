<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\food\controller;

use cmf\controller\HomeBaseController;
use app\food\model\FoodCategoryModel;
use app\food\service\PostService;
use app\food\model\FoodPostModel;
use think\Db;

class ArticleController extends HomeBaseController
{
    /**
     * 食品库详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $foodCategoryModel = new FoodCategoryModel();
        $postService         = new PostService();

        $articleId  = $this->request->param('id', 0, 'intval');
        $categoryId = $this->request->param('cid', 0, 'intval');
        $article    = $postService->publishedArticle($articleId, $categoryId);

        if (empty($article)) {
            abort(404, '食品库不存在!');
        }


        $prevArticle = $postService->publishedPrevArticle($articleId, $categoryId);
        $nextArticle = $postService->publishedNextArticle($articleId, $categoryId);

        $tplName = 'article';

        if (empty($categoryId)) {
            $categories = $article['categories'];

            if (count($categories) > 0) {
                $this->assign('category', $categories[0]);
            } else {
                abort(404, '食品库未指定分类!');
            }

        } else {
            $category = $foodCategoryModel->where('id', $categoryId)->where('status', 1)->find();

            if (empty($category)) {
                abort(404, '食品库不存在!');
            }

            $this->assign('category', $category);

            $tplName = empty($category["one_tpl"]) ? $tplName : $category["one_tpl"];
        }

        Db::name('food_post')->where('id', $articleId)->setInc('post_hits');


        hook('food_before_assign_article', $article);

        $this->assign('article', $article);
        $this->assign('prev_article', $prevArticle);
        $this->assign('next_article', $nextArticle);

        $tplName = empty($article['more']['template']) ? $tplName : $article['more']['template'];

        return $this->fetch("/$tplName");
    }

    // 食品库点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');


        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('food_post')->where('id', $articleId)->setInc('post_like');

            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

}
