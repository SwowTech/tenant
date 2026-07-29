<?php


namespace bases;

use app\model\SysConfig;
use think\Model;
use app\services\TokenService;
use Exception;
use Throwable;
use app\Request;
use think\facade\Log;
class  BaseModel extends Model
{

    protected function baseSetImgUrl ($value,$data){
        $finalUrl = $value;
        if($data['from'] == 1){
            $api_url = SysConfig::where('key','api_url')->value('value');
            $value=str_replace("\\","/",$value);
            $finalUrl = $api_url.$value;
        }
        return $finalUrl;
    }

    public function getCreateTimeAttr($v)
    {
        return date("Y-m-d H:i:s",$v);
    }
}