<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\Session;

class Login extends Controller
{
    public function index()
    {
        if(Session::has("user_id")){
            $this->redirect("index/index");
        }
        $system=Db::name('s_system')->where('id',1)->find();
        $this->assign('system',$system);
        return $this->fetch();
    }
    public function chklogin(){
        $data=$this->request->post();
        $status=0;
        $request=$this->validate($data,"Login");
        if($request===true){
            unset($data["captcha"]);
            $data["password"]=md5($data["password"]);
            $data["is_delete"]=0;
            $res=Db::name("s_admin")->where($data)->find();
            if($res){
                if($res["status"]!=1){
                    $request="该用户已被禁用";
                }else{
                    //上次登录信息
                    Session::set("old_user_info",$res);
                    $ip=$this->request->ip();

                    Db::name("s_admin")->update(['login_time' => time(),'id'=>$res["id"],'login_ip'=>$ip,'login_count'=>$res["login_count"]+1]);
                    $status=1;
                    $request="正在登陆中...";
                    $res["login_time"]=time();
                    $res["login_count"]=$res["login_count"]+1;
                    $res["login_ip"]=$ip;

                    //本次登录信息
                    Session::set("user_id",$res["id"]);
                    Session::set("user_info",$res);
                }
            }else{
                $request="没有找到该用户";
            }
        }
        $array=[
            "status"=>$status,
            "message"=>$request,
        ];
        return json($array);
    }

    public function logout(){
        Session::delete("user_id");
        Session::delete("user_info");
        $this->success("退出成功,正在返回登陆界面",'index');
    }
}
