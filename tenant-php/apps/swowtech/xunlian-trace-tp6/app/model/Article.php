<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 11:35
 */

namespace app\model;


use app\services\TokenService;
use bases\BaseModel;

class Article extends BaseModel
{
    /**
     * 增加文章
     * @return mixed
     */
    public static function addArticle($post){
//        $post['author'] = TokenService::getAdminName();

        $res = self::create($post);
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success($res['id']);
    }

    /**
     * 修改文章
     * @return mixed
     */
    public static function editArticle($data)
    {
        $oid = $data['id'];
        unset($data['id']);
        unset($data['img']);
        $data['create_time']= strtotime($data['create_time']);
        self::where('id', $oid)->update($data);
        return app('json')->success();
    }

    /**
     * 删除文章
     * @return mixed
     */
    public static function deleteArticle($id)
    {
        $res = self::where('id', $id)->delete();
        if (!$res) {
            return app('json')->fail();
        }
        return app('json')->success();
//        return $res?1:0;
    }

    /**
     * 获取某一篇公告
     * @return mixed
     */
    public static function getArticle(){
        $data = self::where('type',3)->order('id','desc')->find();
        if(!$data){
            return app('json')->fail();
        }
        return app('json')->success($data);
//        return $data;
    }

    /**
     * 获取文章详情
     * @param int
     * @return mixed
     */
    public static function getOneArticle($id){
        $data = self::with('img')->find($id);
        if(!$data){
            return app('json')->fail('没有找到文章');
        }
        return app('json')->success($data);

    }
    /**
     * 获取文章详情
     * @param int
     * @return mixed
     */
    public static function giveThumbsUp($id){
        $data = self::where('id',$id)->inc('star',1)->update();;
        if(!$data){
            return app('json')->fail();
        }
        return app('json')->success($data);

    }
    /**
     * 浏览量加一
     * @param int
     * @return mixed
     */
        public static function visit($id){
            $data = self::where('id',$id)->inc('visit',1)->update();;
            if(!$data){
                return app('json')->fail();
            }
            return app('json')->success($data);

        }

    /**
     * 获取文章详情
     * @param int
     * @return mixed
     */
    public static function getResent(){
        $data = self::with('img')
            ->where('is_hidden',0)
            ->order('create_time','desc')
            ->limit(6)
            ->select();
        if(!$data){
            return app('json')->fail();
        }
        return app('json')->success($data);

    }
    /**
     * 用户获取文章
     * @return mixed
     */
    public static function userArticle()
    {
        $data = self::where('is_hidden',0)->where('type','in','0,2')->select();
        return app('json')->success($data);
//        return $data;
    }

    public function img(){
        return $this->belongsTo(Image::class,'image','id')->field('id,url');
    }
}