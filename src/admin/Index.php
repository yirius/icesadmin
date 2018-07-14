<?php
/**
 * User: Yirius
 * Date: 2018/6/28
 * Time: 14:08
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminMember;
use icesadmin\extend\auth\Auth;
use icesadmin\extend\jwt\Adminjwt;
use think\Db;
use think\facade\Response;
use think\facade\Validate;
use traits\controller\Jump;

/**
 * Class Admin
 * this is admin's api for layuiadmin
 * @package icesadmin\admin
 */
class Index
{
    use Jump;

    /**
     * @title 登录接口
     * @description admin的默认登录接口
     * @createtime: 2018/7/10 15:54
     * @param string $username 用户名 true '' ''
     * @param string $password 密码 true '' ''
     * @param string $vercode 验证码,可以通过config/capatch.php配置 true '' ''
     * @return \\Response::result
     */
    public function login($username, $password, $vercode){
        //首先验证验证码输入
        if(!captcha_check($vercode)){
            $this->result([], 0, "验证码输入不正确, 请您重新输入", "json");
        }
        //利用Auth来校验用户信息
        $userinfo = Auth::instance()->checkUserInfo($username);
        if(empty($userinfo)){
            $this->result([], 0, "查无此用户", "json");
        }else{
            //如果用户密码错误的话
            if($userinfo['password'] != sha1($password . $userinfo['salt'])){
                $this->result([], 0, "用户登录账号密码错误", "json");
            }
            //否则的话直接返回对应的jwt
            $this->result([
                'username' => $userinfo['username'],
                'userphone' => $userinfo['phone'],
                'id' => $userinfo['id'],
                'access_token' => Adminjwt::instance()->encodeHmac([
                    'username' => $userinfo['username'],
                    'userphone' => $userinfo['phone'],
                    'id' => $userinfo['id']
                ])
            ], 1, "登录成功", "json");
        }
    }

    /**
     * @title 退出登录接口
     * @description admin的默认退出登录接口,现阶段默认只返回了一个成功,无其他操作,后期需要可以加入用户的记录
     * @createtime: 2018/7/10 15:54
     * @return \\Response::result
     */
    public function logout(){
        $this->result([], 1, "退出登录成功", 'json');
    }

    /**
     * @title 获取后台菜单
     * @description 获取后台菜单的接口
     * @createtime: 2018/7/10 15:59
     * @param string $access_token jwt_token,利用Adminjwt生成 true '' ''
     */
    public function menu($access_token){
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $menuinfo = Auth::instance()->getAuthMenu($userinfo['id']);
        /**
         * 检验一下配置没配置menu
         */
        $spread = config("icesadmin.menu.spread") or false;
        if($spread === false){

        }else{
            $menuinfo[$spread]['spread'] = "true";
        }
        //判断是否开启了topmenu的选项
        $topmenu = config("icesadmin.menu.topmenu") or false;
        if($topmenu){
            $menuinfo = [$menuinfo[0]];
        }
        $this->result($menuinfo, 1, "suc", 'json');
    }

    /**
     * @title 顶部菜单
     * @description 顶部菜单
     * @createtime: 2018/7/13 21:34
     * @param $access_token
     */
    public function topmenu($access_token){
        //首先判断开没开这个选项
        $topmenu = config("icesadmin.menu.topmenu") or false;
        if(!$topmenu){
            $this->result([], 1, "suc", 'json');
        }
        //开了就返回,没开就不返回
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $menuinfo = Auth::instance()->getAuthMenu($userinfo['id']);
        $this->result($menuinfo, 1, "suc", 'json');
    }

    /**
     * @title 获取用户信息
     * @description 登录之后获取用户个人信息的界面,会保存在admin.html中,username必须返回
     * @createtime: 2018/7/10 16:11
     * @param string $access_token jwt_token,利用Adminjwt生成 true '' ''
     */
    public function userinfo($access_token){
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $uinfo = Auth::instance()->getUserInfo($userinfo['id']);
        $resultInfo = [
            'username' => $uinfo['username']
        ];
        /**
         * 检验一下配置没配置menu,如果配置了返回的用户相关信息,就直接返回
         */
        $infoArr = config("icesadmin.menu.userinfo") ? : [];
        foreach($infoArr as $i => $v){
            if(isset($uinfo[$v])){
                $resultInfo[$v] = $uinfo[$v];
            }
        }
        $this->result($resultInfo, 1, "suc", 'json');
    }

    /**
     * @title 判断是否存在新的信息
     * @description 判断是否存在新的信息, 如果存在则newmsg返回大于零的任何数字即可
     * @createtime: 2018/7/10 16:15
     */
    public function message(){
        $message = config("icesadmin.menu.message") or 0;
        $this->result([
            'newmsg' => $message
        ], 1, "suc", 'json');
    }

    /**
     * @title 获取到角色的列表
     * @description
     * @createtime: 2018/6/29 18:45
     * @param int $page
     * @param int $limit
     * @return \think\response
     */
    public function adminroleList($page = 1, $limit = 10){
        $adminGroup = Auth::instance()->getConfig("auth_group");
        /**
         * 角色
         */
        $mList = Db::name($adminGroup)->page($page, $limit)->select();
        /**
         * 列表
         */
        $mListCount = Db::name($adminGroup)->count();
        return Response::create([
            'data' => $mList,
            'code' => 1,
            'count' => $mListCount,
            'msg' => "查询角色信息成功"
        ], "json");
    }

    public function adminroleUpdate($access_token, $id = 0){
        $logininfo = Adminjwt::instance()->decodeHmac($access_token);
        $validate = Validate::make([
            'title' => "require",
            'rules' => "require|array"
        ], [
            'title.require' => "角色名称必须填写",
            'rules.require' => "权限范围必须选择",
            'rules.array' => "权限范围格式出现了错误"
        ]);
        /**
         * 校验
         */
        $post = input('post.');
        if(!$validate->check($post)){
            $this->error($validate->getError());
        }
        //更新或者新增
        if(!empty($id) && is_numeric($id)){
            //去更新
            if($id == 1){//编号为1是超级管理, 不可更改
                $this->success("修改角色成功");
            }else{
                $flag = Db::name(Auth::instance()->getConfig("auth_group"))->where('id', $id)->update([
                    'title' => $post['title'],
                    'status' => empty($post['status'])?0:1,
                    'rules' => implode(",", $post['rules'])
                ]);
                if($flag){
                    $this->success("修改角色成功");
                }else{
                    $this->error("修改角色失败");
                }
            }
        }else{
            //新增一个
            $flag = Db::name(Auth::instance()->getConfig("auth_group"))->insert([
                'title' => $post['title'],
                'status' => empty($post['status'])?0:1,
                'rules' => implode(",", $post['rules'])
            ]);
            if($flag){
                $this->success("新增角色成功");
            }else{
                $this->error("新增角色失败");
            }
        }
    }

    public function adminroleDelete($access_token, $password){
        $userinfo = Adminjwt::instance()->decodeHmac($access_token);
        $uinfo = Auth::instance()->getUserInfo($userinfo['id']);
        if($uinfo['password'] != sha1($password . $uinfo['salt'])){
            $this->error("您输入的密码不正确");
        }
        $config = Auth::instance()->getConfig();
        $post = input('post.');
        $delData = $post['deldata'];
        $canNotDelArr = [];
        foreach($delData as $i => $v){
            if($v['id'] == 1){
                continue;
            }else{
                $count = Db::name($config['auth_group_access'])
                    ->where('group_id', $v['id'])
                    ->count();
                if($count){
                    $canNotDelArr[] = $v['title'];
                }else{
                    Db::name($config['auth_group'])
                        ->where('id', $v['id'])
                        ->delete();
                }
            }
        }
        if(!empty($canNotDelArr)){
            $this->success("删除完成, 存在【" . implode(",", $canNotDelArr) . "】有使用无法删除");
        }else{
            $this->success("删除完成");
        }
    }

    public function adminroleRules($id = 0){
        $checkRules = [];
        //如果当前用户的id存在并且是一个数字
        $config = Auth::instance()->getConfig();
        if(!empty($id) && is_numeric($id)){
            $groups = Db::name($config['auth_group'])->where('id', $id)->find();
            $checkRules = explode(",", $groups['rules']);
        }
        /**
         * 取出来所有的可用角色, 然后判断是否已选
         */
        $ruleList = Db::name($config['auth_rule'])
            ->field("id as value, title as text")
            ->where('status', 1)
            ->select();
        foreach($ruleList as $i => $v){
            if(in_array($v['value'], $checkRules)){
                $ruleList[$i]['checked'] = 1;
            }else{
                $ruleList[$i]['checked'] = 0;
            }
        }
        $this->result($ruleList, 1, "获取角色权限范围成功");
    }

    public function adminroleRulesTree($id = 0){
        $checkRules = [];
        //如果当前用户的id存在并且是一个数字
        $config = Auth::instance()->getConfig();
        if(!empty($id) && is_numeric($id)){
            $groups = Db::name($config['auth_group'])->where('id', $id)->find();
            $checkRules = explode(",", $groups['rules']);
        }
        /**
         * 取出来所有的可用角色, 然后判断是否已选
         */
        $ruleList = Db::name($config['auth_rule'])
            ->field("id as value, title as name, mid")
            ->where('status', 1)
            ->select();
        /**
         * 按照自己的id进行序号的编写
         */
        $tempRules = [];
        for($i = 0;$i < count($ruleList); $i++){
            if(in_array($ruleList[$i]['value'], $checkRules)){
                $ruleList[$i]['checked'] = 1;
            }else{
                $ruleList[$i]['checked'] = 0;
            }
            $tempRules[$ruleList[$i]['value']] = $ruleList[$i];
        }
        /**
         * 首先利用指针, 把所有的穿起来
         */
        foreach($tempRules as $i => $v){
            if(!empty($tempRules[$v['mid']])){
                if(empty($tempRules[$v['mid']]['children'])) $tempRules[$v['mid']]['children'] = [];
                $tempRules[$v['mid']]['children'][] = &$tempRules[$i];
            }
        }
        $resultRules = [];
        foreach($tempRules as $i => $v){
            if($v['mid'] == 0){
                $resultRules[] = $v;
            }
        }
        $this->result($resultRules, 1, "获取规则对应的树成功");
    }
}
