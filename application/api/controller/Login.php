<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Config;

class Login extends Controller
{
    //小程序能够获取到unionid的前提
    //1.小程序必须绑定在微信开放平台上，不绑定是没有的（PS：绑定开放平台需要开发者资质认证，认证收费的奥）
    //
    //2.需要微信用户授权小程序

    //小程序用户初始默认登录
    public function login(){
        $appid=Config::get('appid');
        $secret=Config::get('secret');
        $code = $this->request->get('code');
        $url='https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        //初始化
        $h=curl_init();
        //设置参数，这里非常重要，参数非常多，curl用的厉不厉害就看这里。
        //设置url
        curl_setopt($h,CURLOPT_URL,$url);
        //设置是否输出结果
        curl_setopt($h,CURLOPT_RETURNTRANSFER,1);
        //启用时会将头文件的信息作为数据流输出。
        curl_setopt($h,CURLOPT_HEADER,0);
        //执行一个curl会话，成功时返回true，失败时返回false,当CURLOPT_RETURNTRANSFER被设置时，成功时会返回执行的结果，失败时返回false
        $response = curl_exec($h);
        //关闭curl
        curl_close($h);

        $data=json_decode($response,true);
        //查询该微信号是否已经注册。
        $openID = $data["openid"];
        $unionID = isset($data["unionid"])?$data["unionid"]:'';
        if(empty($openID) && empty($unionID)){
            return json(['code'=>-1001,'data'=>'对不起，无法获取当前设备登陆的微信信息']);
        }else{
            //优先使用unionID,其次是openID
            if(!empty($unionID)){
                $rs=Db::name('s_user')->where('wxUnionID',$unionID)->find();
                if(!$rs&&!empty($openID)){
                    $rs=Db::name('s_user')->where('miniOpenID',$openID)->find();
                }
            }else{
                $rs=Db::name('s_user')->where('miniOpenID',$openID)->find();
            }
        }

        if($rs){
            $salt=Config::get('salt');
            //更新token
            $thetime=time();
            $token=$thetime.'+'.$rs['id'].'+'.$salt;
            $token=md5($token);
            $token=$thetime.'+'.$rs['id'].'+'.$token;

            Db::name('s_user')->where('id',$rs['id'])->update(['token'=>$token]);
            $rdata['id'] = $rs['id'];
            $rdata['nickName'] = $rs['nickName'];
            $rdata['thumb'] = $rs['thumb'];
            $rdata['_wxminitoken_'] = $token;
            $rdata['groupID'] = $rs['groupID'];
            $rdata['role'] = $rs['role'];

            //满多少可购买
            $rdata['price'] = Db::name('s_system')->where('id',1)->value('price');

            //该用户如果绑定了小区，则还需返回小区信息
            if($rs['groupID']>0){
                $group=Db::name('s_group')->alias('g')->join('s_user u','g.userID = u.id')->field('g.name,g.address,g.linkman,g.phone,u.thumb')->where('g.id',$rs['groupID'])->where('g.is_delete',0)->find();
                $rdata['group'] = $group;
            }

            return json(['code'=>1,'data'=>$rdata]);
        }else{
            return json(['code'=>1,'data'=>'']);
        }
    }


    //小程序授权登录
    public function authlogin(){

        $appid=Config::get('appid');
        $secret=Config::get('secret');
        $code = $this->request->get('code');
        $groupID=$this->request->get('groupID');

        $url='https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        //初始化
        $h=curl_init();
        curl_setopt($h,CURLOPT_URL,$url);
        curl_setopt($h,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($h,CURLOPT_HEADER,0);
        $response = curl_exec($h);
        curl_close($h);
        $code2Session=json_decode($response,true);

        $sessionKey=$code2Session["session_key"];

        $iv = $this->request->get('iv');
        $encryptedData = $this->request->get('encryptedData');


        $errCode = $this->decryptData($appid,$sessionKey,$encryptedData, $iv, $data );

        if ($errCode == 0) {
            $data=json_decode($data,true);
            $data["unionId"]=isset($data["unionId"])?$data["unionId"]:"";

            //先查询一下数据库里是否有该用户，如果有，则直接返回用户信息，没有再插入
            if(!empty($data["unionId"])){
                $rs=Db::name('s_user')->where('wxUnionID',$data["unionId"])->find();
                if(!$rs&&!empty($data['openId'])){
                    $rs=Db::name('s_user')->where('miniOpenID',$data['openId'])->find();
                }
            }else{
                $rs=Db::name('s_user')->where('miniOpenID',$data['openId'])->find();
            }

            $salt=Config::get('salt');

            if(!$rs){
                //在这里把用户信息插入数据库，并返回用户信息数组
                $insertdata['thumb']=$data['avatarUrl'];
                $insertdata['nickName']=$data['nickName'];
                $insertdata['miniOpenID']=$data['openId'];
                $insertdata['wxUnionID']=$data['unionId'];
                $insertdata['regTime']=time();
                $insertdata['groupID']=$groupID;

                $userid=Db::name('s_user')->insertGetId($insertdata);

                if($userid>0){

                    //更新token
                    $thetime=time();
                    $token=$thetime.'+'.$userid.'+'.$salt;
                    $token=md5($token);
                    $token=$thetime.'+'.$userid.'+'.$token;

                    Db::name('s_user')->where('id',$userid)->update(['token'=>$token]);
                    $rdata['id'] = $userid;
                    $rdata['nickName'] = $data['nickName'];
                    $rdata['thumb'] = $data['avatarUrl'];
                    $rdata['_wxminitoken_'] = $token;
                    //这里有问题，如果带团长参数过来注册，则直接可以填。
                    $rdata['groupID'] = $groupID;
                    $rdata['role'] = 0;

                    //满多少可购买
                    $rdata['price'] = Db::name('s_system')->where('id',1)->value('price');

                    if($groupID>0){
                        $group=Db::name('s_group')->alias('g')->join('s_user u','g.userID = u.id')->field('g.name,g.address,g.linkman,g.phone,u.thumb')->where('g.id',$rs['groupID'])->where('g.is_delete',0)->find();
                        if($group){
                            $rdata['group'] = $group;
                            $rdata['groupID'] = $groupID;
                        }else{
                            //用户注册成功，业务员绑定失败
                            $rdata['groupID'] = 0;
                            return json(['code'=>-7,'data'=>$rdata]);
                        }
                    }
                    return json(['code'=>1,'data'=>$rdata]);
                }else{
                    return json(['code'=>-5,'data'=>'用户注册失败']);
                }

            }else{
                //数据库里有查询到用户信息

                //更新token
                $thetime=time();
                $token=$thetime.'+'.$rs['id'].'+'.$salt;
                $token=md5($token);
                $token=$thetime.'+'.$rs['id'].'+'.$token;

                Db::name('s_user')->where('id',$rs['id'])->update(['token'=>$token]);
                $rdata['id'] = $rs['id'];
                $rdata['nickName'] = $rs['nickName'];
                $rdata['thumb'] = $rs['thumb'];
                $rdata['_wxminitoken_'] = $token;
                $rdata['role'] = $rs['role'];

                //满多少可购买
                $rdata['price'] = Db::name('s_system')->where('id',1)->value('price');

                $rdata['groupID'] = $rs['groupID'];
                if($rs['groupID']>0){
                    $group=Db::name('s_group')->alias('g')->join('s_user u','g.userID = u.id')->field('g.name,g.address,g.linkman,g.phone,u.thumb')->where('g.id',$rs['groupID'])->where('g.is_delete',0)->find();
                    $rdata['group'] = $group;
                }
                return json(['code'=>1,'data'=>$rdata]);
            }
            //return json(['code'=>1,'data'=>$data]);
        } else {
            return json(['code'=>$errCode]);
        }

    }



    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($appid,$sessionKey, $encryptedData, $iv, &$data )
    {
        /**
         * error code 说明.
         * <ul>

         *    <li>-41001: encodingAesKey 非法</li>
         *    <li>-41003: aes 解密失败</li>
         *    <li>-41004: 解密后得到的buffer非法</li>
         *    <li>-41005: base64加密失败</li>
         *    <li>-41016: base64解密失败</li>
         * </ul>
         */

        if (strlen($sessionKey) != 24) {
            return -41001;              //$IllegalAesKey
        }
        $aesKey=base64_decode($sessionKey);


        if (strlen($iv) != 24) {
            return -41002;              //$IllegalIv
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return -41003;               //$IllegalBuffer
        }
        if( $dataObj->watermark->appid != $appid )
        {
            return -41003;           //$IllegalBuffer
        }
        $data = $result;
        return 0;           //OK
    }

    public function testlogin(){
        sleep(5);
        return 'ok';
    }

}
