<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/27
 * Time: 9:49
 */
use think\Db;

//验证token方法
function checktoken($access_token){
    if(!$access_token){
        return ['code'=>-2,'data'=>'登录验证出错'];
    }
    $array=explode("+",$access_token);
    $nowtime=$array[0];
    $userid=$array[1];

    if(time()-$nowtime>7200){
        return ['code'=>-1,'data'=>'登录超时'];
    }
    $token=Db::name('s_user')->where('id',$userid)->value('token');
    if($access_token!=$token){
        return ['code'=>-2,'data'=>'登录验证出错'];
    }
    return ['code'=>1,'data'=>$userid];
}
//将XML转为array
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

function https_request($url,$data = null){
    if(function_exists('curl_init')){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }else{
        return false;
    }
}

?>