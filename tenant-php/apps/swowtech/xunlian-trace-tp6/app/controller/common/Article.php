<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/22 0022
 * Time: 8:58
 */

namespace app\controller\common;


use bases\BaseController;
use app\model\Article as ArticleModel;
use services\QyFactory;

class Article extends BaseController
{
    /**
     *  获取所有文章
     * @return mixed
     * @throws \exceptions\BaseException
     */
    public function getAllArticle()
    {
        $article=(new QyFactory())->instance('UserService');
        $data=$article->get_article_list();
        return app('json')->success($data);
    }

    /**
     * 获取某一篇公告
     * @return mixed
     */
    public function getArticle()
    {
        return ArticleModel::getArticle();
    }

    /**
     * 获取文章详情
     * @return mixed
     */
    public function getOneArticle($id)
    {
        return ArticleModel::getOneArticle($id);
    }
     /**
     * 文章点赞
     * @return mixed
     */
    public function giveThumbsUp($id)
    {
        return ArticleModel::giveThumbsUp($id);
    }
    /**
 * 文章浏览
 * @return mixed
 */
    public function visit($id)
    {
        return ArticleModel::visit($id);
    }
    /**
     * 获取最新文章
     * @return mixed
     */
    public function getRecent()
    {
        return ArticleModel::getResent();
    }
    /**
     * 用户分类文章
     * @param $type
     * @return mixed
     */
    public function getTypeArticle($type,$page=1,$size=10)
    {
        $data=ArticleModel::with('img')
            ->where('is_hidden',0)
            ->where('category_id',$type)
            ->limit(($page-1)*$size,$size)
            ->select();
        $total=ArticleModel::where('is_hidden',0)
            ->where('category_id',$type)->count();
        $res['data'] = $data;
        foreach($data as $k => &$v){
            $v['content'] = mb_substr(strip_tags($v['content']),0,50);
        }
        $res['total'] = $total;
        return app('json')->success($res);
    }

    /**
     * 用户获取文章
     * @return mixed
     */
    public function userArticle()
    {
        return ArticleModel::userArticle();
    }
}