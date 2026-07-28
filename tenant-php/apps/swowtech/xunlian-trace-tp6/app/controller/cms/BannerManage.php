<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/15 0015
 * Time: 13:44
 */

namespace app\controller\cms;


use app\model\Banner;
use app\model\BannerItem as BannerItemModel;
use app\validate\IDPostiveInt;
use bases\BaseController;
use exceptions\TipException;
use services\QyFactory;

class BannerManage extends BaseController
{

    /**
     * 添加广告
     * @return
     */
    public function addBanner()
    {
        $rule = ([
            'name' => 'require|chsAlphaNum|max:10',
            'description' => 'require|max:80|chsAlphaNum'
        ]);
        $post = input('post.');
        $this->validate($post, $rule);

        $banner = Banner::where(['name' => $post['name']])->find();
        if($banner) {
            throw new TipException('该广告名称已存在');
        }
        Banner::create($post);
        return app('json')->success();

    }

    /**
     * 删除广告
     * @param $id
     * @return int
     */
    public function deleteBanner($id)
    {
        (new IDPostiveInt)->goCheck();
        $res = Banner::where('id', $id)->delete();
        return app('json')->success();
    }

    /**
     * 修改广告
     * @return int
     */
    public function editBanner()
    {
        (new IDPostiveInt)->goCheck();

        $post = input('post.');
        Banner::where('id', $post['id'])->update($post);
        return app('json')->success();
    }

    /**
     * 更新排序
     * @return int
     */
    public function setSort()
    {
        $arr = input('post.');
        return BannerItemModel::setSort($arr);
    }

    /**
     * cms获取所有广告
     * @return mixed
     */
    public function bannerList()
    {
        $data = Banner::select();
        return app('json')->success($data);
    }

    public function bannerItemList($banner_id)
    {
        $data = BannerItemModel::where('banner_id', $banner_id)->with(['imgs'])->select();
        return app('json')->success($data);
    }
    public function saveBannerItem()
    {
        $rule = ([
            'key_word' => 'require|chsAlphaNum|max:10',
            'img_id' => 'require|integer',
        ]);
        $post = input('post.');
        $this->validate($post, $rule);
        $post['update_time'] = time();

        if(isset($post['id'])&&$post['id']) {
            $res = BannerItemModel::update($post,['id'=> $post['id']]);
        } else {
            $res = BannerItemModel::create($post);
        }
        return app('json')->success($res);
    }

    public function deleteBannerItem($id)
    {
        $res = BannerItemModel::where('id', $id)->delete();
        return app('json')->success($res);
    }

}