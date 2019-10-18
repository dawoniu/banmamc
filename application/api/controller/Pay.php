<?php
namespace app\api\controller;

use think\Db;
use think\Config;

class Pay extends Base
{
    public function pay(){
        $appid = Config::get('appid');
        $secret = Config::get('secret');
        $mch_id = Config::get('mch_id');
        $wx_key=Config::get('wx_key');    //申请支付后有给予一个商户账号和密码，登陆后自己设置key

        $code=$this->request->post('code');
        $orderID=$this->request->post('orderID');

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $secret . "&js_code=" . $code . "&grant_type=authorization_code";
        $infos = json_decode(file_get_contents($url));
        $openid = $infos->openid;

        $order=Db::name('u_order')->where('id',$orderID)->find();
        $total_fee=$order['sumTotal']*100;
        $total_fee=1;
        $body = '小程序支付';

        $notify_url = 'http://banmamc.banmayg.com/api/order/payorder';
        $spbill_create_ip = '106.12.14.157';

        $out_trade_no = $order['orderSN'];//商户订单号
        $nonce_str = $this->nonce_str();//随机字符串

        $trade_type = 'JSAPI';//交易类型 默认

        //这里是按照顺序的 因为下面的签名是按照顺序 排序错误 肯定出错
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;//随机字符串
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = $spbill_create_ip;//终端的ip
        $post['total_fee'] = $total_fee;//总金额 最低为一块钱 必须是整数
        $post['trade_type'] = $trade_type;
        $sign = $this->sign($post,$wx_key);//签名

        $post_xml = '<xml>
        <appid>'.$appid.'</appid>
        <body>'.$body.'</body>
        <mch_id>'.$mch_id.'</mch_id>
        <nonce_str>'.$nonce_str.'</nonce_str>
        <notify_url>'.$notify_url.'</notify_url>
        <openid>'.$openid.'</openid>
        <out_trade_no>'.$out_trade_no.'</out_trade_no>
        <spbill_create_ip>'.$spbill_create_ip.'</spbill_create_ip>
        <total_fee>'.$total_fee.'</total_fee>
        <trade_type>'.$trade_type.'</trade_type>
        <sign>'.$sign.'</sign>
        </xml> ';
        //统一接口prepay_id
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url,$post_xml);

        $array = xmlToArray($xml);//全要大写
        if($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS') {
            $time = time();
            $tmp=[];//临时数组用于签名
            $tmp['appId'] = $appid;
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id='.$array['prepay_id'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";
            $data['state'] = 1;
            $data['timeStamp'] = "$time";//时间戳
            $data['nonceStr'] = $nonce_str;//随机字符串
            $data['signType'] = 'MD5';//签名算法，暂支持 MD5
            $data['package'] = 'prepay_id='.$array['prepay_id'];//统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*
            $data['paySign'] = $this->sign($tmp,$wx_key);//签名,具体签名方案参见微信公众号支付帮助文档;
            $data['out_trade_no'] = $out_trade_no;
        }else{
            $data['state'] = 0;
            $data['text'] = "错误";
            $data['RETURN_CODE'] = $array['return_code'];
            $data['RETURN_MSG'] = $array['return_code'];
        }
        return json($data);    //小程序需要的数据 返回前端
    }

    function nonce_str(){
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i=0;$i<32;$i++){
            $result .= $str[rand(0,48)];
        }
        return $result;
    }

    //签名函数
    function sign($data,$wx_key){
        $stringA = '';
        foreach ($data as $key=>$value){
            if(!$value) continue;
            if($stringA) $stringA .= '&'.$key."=".$value;
            else $stringA = $key."=".$value;
        }
        $stringSignTemp = $stringA.'&key='.$wx_key;//申请支付后有给予一个商户账号和密码，登陆后自己设置key 
        return strtoupper(md5($stringSignTemp));
    }

    //curl请求啊
    function http_request($url,$data = null,$headers=array()){
        $curl = curl_init();
        if( count($headers) >= 1 ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
