<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/30 0030
 * Time: 13:33
 */

namespace app\controller\cms;


use app\services\CommonService;
use app\services\TokenService;
use bases\BaseController;
use Uedit\controller as UeditController;

class Common extends BaseController
{
    /**
     * 更新不同模型的布尔字段
     * @param $id
     * @param $db
     * @param $field
     * @return int
     */
    public function upValue($id = '', $db = '', $field = '')
    {
        $id = $id !== '' && $id !== null ? $id : input('id');
        $db = $db !== '' && $db !== null ? $db : input('db');
        $field = $field !== '' && $field !== null ? $field : input('field');
        return CommonService::upValue($id, $db, $field);
    }

    /**
     *
     * @return mixed
     */
    public function ueUploads()
    {
        $ue = new UeditController;
        $res = $ue->show();
        return $res;
    }

    /**
     * 上传图片
     * @param string $use
     * @param string $back
     * @param  $cid
     * @return mixed
     */
    public function uploadImg($use = 'other', $back = 'url', $cid = '')
    {
        return CommonService::uploadImg($use, $back, 0, $cid);
    }
    /**
     * 上传视频
     * @param string $use
     * @param string $back
     * @param  $cid
     * @return mixed
     */
    public function uploadVideo($use = 'other', $back = 'url', $cid = '')
    {
        return CommonService::uploadVideo($use, $back, 0, $cid);
    }
    /**
     * 下载图片
     * @param  $url
     * @return mixed
     */
    public function downImg($url)
    {
        return CommonService::downImg($url);
    }


    /**
     * 上传图片返还ID
     * @param string $use
     * @param string $back
     * @param  $cid
     * @return mixed
     */
    public function uploadImgID($use = 'other', $back = 'id', $cid = '')
    {
        $data = CommonService::uploadImg($use, $back, 1, $cid);
        return json($data);
    }
    /**
     * 导出数据
     * @return mixed
     */
    public function exportExcel()
    {
        $data = (new CommonService)->exportExcel();
        if ($data) {
            return app('json')->success($data);
        } else {
            return app('json')->fail();
        }
    }

    /**
     * 检查是否登录
     * @return mixed
     */
    public function checkLogin()
    {
        $aid = TokenService::getCurrentAid();
        if ($aid) {
            return app('json')->success();
        } else {
            return app('json')->fail();
        }
    }


}