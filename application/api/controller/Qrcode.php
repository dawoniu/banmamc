<?php
namespace app\api\controller;

use think\Config;
use think\Cache;

class Qrcode extends Base
{
    public function getqrcode(){
        $check=parent::check();
        if($check['code']<0){
            return json($check);
        }else{
            $userid=$check['data'];
        }
        $groupID=$userid;

        $file ="uploads/group/".$groupID.".jpg";
        if (file_exists($file)) {
            return json(['code'=>1,'data'=>'https://banmamc.banmayg.com/'.$file]);
        }else{
            $appid=Config::get('appid');
            $secret=Config::get('secret');

            $res = $this->getAccessToken($appid,$secret,'client_credential');
            if ($res == 'success') {
                $token = Cache::get('wx_token');
                $access_token = $token['access_token'];
            }else{
                return json($res);
            }
            if (empty($access_token)) {
                return json(['code'=>-5001,'data'=>'access_token为空，无法获取二维码']);
            }
            $path = 'pages/auth/index?groupID='.$groupID;
            $width = 430;
            $res2 = $this->getWXACodeUnlimit($access_token,$path,$width);

            file_put_contents('./'.$file,$res2);
            if (file_exists($file)) {
                return json(['code'=>1,'data'=>'https://banmamc.banmayg.com/'.$file]);
            }else{
                return json(['code'=>5002,'data'=>'生成二维码失败']);
            }
        }
    }

    // 发送access_token
    public function getAccessToken($appid,$secret,$grant_type){
        if (empty($appid)||empty($secret)||empty($grant_type)) {
            return '参数错误';
        }
        // https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=".$grant_type."&appid=".$appid."&secret=".$secret;
        if (Cache::get('wx_token')) {
            $token = Cache::get('wx_token');
            return 'success';
        }
        $json = https_request($url);
        $data=json_decode($json,true);
        if (empty($data['access_token'])) {
            return $data;
        }
        Cache::set('wx_token',$data,3600);
        return 'success';
    }
    // 获取带参数的二维码
    //A类菊花型码 B类测试无效 C类正方形码

    public function getWXACodeUnlimit($access_token,$path='',$width=430){
        if (empty($access_token)||empty($path)) {
            return 'error';
        }
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token=".$access_token;
        $data = array();
        $data['path'] = $path;
        //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
        $data['width'] = $width;
        //二维码的宽度，默认为 430px
        $json = https_request($url,json_encode($data));
        return $json;
    }
}
