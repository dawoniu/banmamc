<?php
namespace app\admin\validate;
use think\Validate;

class Login extends Validate
{
    protected $rule = [
        'name'  =>  'require',
        'password' =>  'require',
        'captcha'=>'require|captcha'
    ];

    protected $message = [
        'name.require'  =>  '请填写账户',
        'password.require' =>  '请填写密码',
        'captcha.require'=>'请填写验证码',
        'captcha.captcha'=>'请填写正确的验证码',
    ];

    /*protected $scene = [
        'add'   =>  ['name','email'],
        'edit'  =>  ['email'],
    ];*/
}
