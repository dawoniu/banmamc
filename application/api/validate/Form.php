<?php
namespace app\index\validate;
use think\Validate;

class Form extends Validate
{
    protected $rule = [
        'linkman'  =>  'require',
        'mobile'=>'require',
        'code'=>'require|captcha'
    ];

    protected $message = [
        'linkman.require'  =>  '请填写联系人',
        'mobile.require' =>  '请填写联系电话',
        'email.require' =>  '请填写邮箱',
        'email.email' =>  '请填写正确的邮箱',
        'content.require' =>  '请填写合作需求',
        'code.require'=>'请填写验证码',
        'code.captcha'=>'请填写正确的验证码',
    ];

    protected $scene = [
        'mini'  =>  ['linkman','mobile'],
        'phone'  =>  ['linkman','mobile'],
    ];



}
