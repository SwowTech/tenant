<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 11:38
 */

namespace app\controller\cms;


use app\model\Article as ArticleModel;
use bases\BaseController;
use services\QyFactory;

class ArticleManage extends BaseController
{
    /**
     * 增加文章
     * @return mixed
     */
    public function addArticle()
    {
        $rule = ([
            'title' => 'require',
            'content' => 'require',
            'author' => 'require'

        ]);
        $post = input('post.');
        $this->validate($post, $rule);
        return ArticleModel::addArticle($post);
    }

    /**
     * 修改文章
     * @return mixed
     */
    public function editArticle()
    {
        $data = $this->request->param('');
        return ArticleModel::editArticle($data);
    }

    /**
     * 删除文章
     * @param int
     * @return mixed
     */
    public function deleteArticle($id)
    {
        return ArticleModel::deleteArticle($id);
    }

    /**
     * cms 获取所有文章
     * @return mixed
     */
    public function adminGetAllArticle($key_word='',$page=1,$size=10)
    {
        $obj = ArticleModel::order('id', 'desc');
        if ($key_word) {
            $obj->where('title', 'like', '%' . $key_word . '%');
        }
        $res = $obj->paginate($size);

        return app('json')->success($res);
    }
    /**
     * cms获取所有文章名称ID
     * @return \think\Collection
     */
    public function allArticleName()
    {
        $res = ArticleModel::field('id,title')->select();
        return app('json')->success($res);
    }
}