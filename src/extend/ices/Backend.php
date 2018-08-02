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
     * @param int $access_type 获取到数据库的类型 false '' ''
     * @param callable $encryptFunc 自己采用的加密方式 false '' ''
     * @return mixed 如果失败,直接返回json的输出,如果成功,返回用户信息
     */
    protected function checkJwtPassword($password, $access_type = 0, $encryptFunc = null){
        $access_token = input("param.access_token", '');
        if(empty($access_token)){
            Response::create([
                'code' => 0,
                'msg' => "您尚未登录, 无法删除数据"
            ], "json")->send();
            exit;
        }
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $uinfo = Auth::instance()->getUserInfo($userinfo['id'], $access_type);
        //在这里，判断一下加密是否存在,如果存在方法，就使用方法去加密，如果不存在，就是用默认加密
        $encryptData = is_null($encryptFunc) ?
            sha1($password . $uinfo['salt']) :
            $encryptFunc($password, $uinfo);
        //判断加密
        if($uinfo['password'] != $encryptData){
            Response::create([
                'code' => 0,
                'msg' => "您输入的密码不正确"
            ], "json")->send();
            exit;
        }
        return $uinfo;
    }
}
