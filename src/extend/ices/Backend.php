<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 14:29
 */

namespace icesadmin\extend\ices;

use icesadmin\extend\auth\Auth;
use icesadmin\extend\jwt\Adminjwt;
use think\Db;
use think\facade\Response;

trait Backend
{
    /**
     * @title 检查密码是否正确
     * @description 再删除或者进行其他关键操作的时候,进行验证
     * @createtime: 2018/7/11 00:17
     * @param string $password 用户输入的密码 true '' ''
     * @return mixed 如果失败,直接返回json的输出,如果成功,返回用户信息
     */
    protected function checkJwtPassword($password){
        $access_token = input("param.access_token", '');
        if(empty($access_token)){
            Response::create([
                'code' => 0,
                'msg' => "您尚未登录, 无法删除数据"
            ], "json")->send();
            exit;
        }
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $uinfo = Auth::instance()->getUserInfo($userinfo['id']);
        if($uinfo['password'] != sha1($password . $uinfo['salt'])){
            Response::create([
                'code' => 0,
                'msg' => "您输入的密码不正确"
            ], "json")->send();
            exit;
        }
        return $uinfo;
    }
}
