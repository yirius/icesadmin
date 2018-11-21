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

    /**
     * 平台发送数据json的接口
     * @param $data
     * @param int|string $code
     * @param string $msg
     * @param int $status
     * @param array $header
     * @param array $options
     */
    function send($data, $code = 1, $msg = "", $status = 200, $header = [], $options = []){
        //判断是不是icesList的传参
        if(empty($data['code'])){
            $result = [
                'code' => $code,
                'msg' => $msg,
                'data' => $data
            ];
        }else{
            $result = $data;
            $result['msg'] = $code;
        }
        Response::create($result, "json", $status, $header, $options)->send();
        exit;
    }

    /**
     * 对提交的数据进行where条件的拼装
     * @param $post
     * @param $param
     * @return array
     */
    function checkWhereParam($post, $param){
        $where = [];
        foreach($param as $i => $v){
            if(is_array($v)){
                $name = strpos($v[0], ".")?explode(".", $v[0])[1]:$v[0];
                $_name = is_numeric($i) ? $name : $i;
                if(isset($post[$_name]) && $post[$_name] != ""){
                    $where[] = [$v[0], $v[1], str_replace("_var", $post[$_name], $v[2])];
                }
            }else{
                $name = strpos($v, ".")?explode(".", $v)[1]:$v;
                $_name = is_numeric($i) ? $name : $i;
                if(isset($post[$_name]) && $post[$_name] != ""){
                    $where[] = [$v, "=", $post[$_name]];
                }
            }
        }
        return $where;
    }

    /**
     * @title 设置一个等待时间
     * @description
     * @createtime: 2018/3/21 01:11
     * @param $name
     * @param int $second
     * @return bool
     */
    function setTimeout($name, $second = 60){
        session($name, null);
        $canNext = session($name);
        if(empty($canNext)){
            /**
             * 如果不存在这个标记, 那就说明原来没进行过, 可以进行下一步
             */
            session($name, time());
            return true;
        }else{
            /**
             * 如果日期大于记录时间seconds, 重新记录然后可以返回下一步
             */
            if((time() - intval($canNext)) > $second){
                session($name, time());
                return true;
            }else{
                //事件记录还没到
                return false;
            }
        }
    }

    /**
     * 把一个时间格式化城几天之前
     * @param $time
     * @param float|int $prevTime
     * @param string $formateStr
     * @return string
     */
    function formatDate($time, $prevTime = 86400 * 30, $formateStr = "m-d H:i"){
        if(empty($time)){
            return "无时间";
        }
        if(is_string($time)){
            $time = strtotime($time);
        }
        $_time = time() - $time;
        $isNextTime = false;
        if($_time < 0){
            $isNextTime = true;
            $_time = abs($_time);
        }
        $dateFormate = [
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '星期',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒'
        ];
        //如果是一个月之前的，直接显示时间
        if($_time > $prevTime){
            return date($formateStr, $time);
        }
        $result = "无时间";
        foreach ($dateFormate as $k => $v)    {
            if (0 != $c = floor($_time/(int)$k)) {
                $result = $c.$v.($isNextTime?'后':'前');
                break;
            }
        }
        return $result;
    }

    /**
     * 判断是否是cli模式
     * @return bool
     */
    function isCli(){
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }
}
