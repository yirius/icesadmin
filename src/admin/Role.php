<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:10
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminGroup;
use icesadmin\extend\auth\Auth;
use icesadmin\extend\auth\AuthData;
use icesadmin\extend\ices\Backend;
use icesadmin\extend\jwt\Adminjwt;
use think\Db;
use think\facade\Response;
use think\facade\Validate;
use traits\controller\Jump;

class Role
{
    use Backend;
    use Jump;

    /**
     * @title 获取角色列表
     * @description 获取角色列表
     * @createtime: 2018/7/10 16:57
     * @return \think\response
     */
    public function lists($access_token){
        $logininfo = Adminjwt::instance()->decodeHmac($access_token);
        if(!Auth::instance()->check("./admin/role", $logininfo['id'])){
            $this->result([], 0, '您暂无权限查看角色', 'json');
        }
        $result = IcesAdminGroup::icesList();
        $result['msg'] = "获取角色列表成功";
        return Response::create($result, "json");
    }

    /**
     * @title 更新用户信息
     * @description 更新用户信息
     * @createtime: 2018/7/10 16:58
     * @param int $id
     * @return \think\response
     */
    public function update($id = 0){
        $logininfo = Adminjwt::instance()->decodeHmac(input('param.access_token', ''));
        if(!Auth::instance()->check("./admin/role", $logininfo['id'])){
            $this->result([], 0, '您暂无权限修改角色信息', 'json');
        }
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
                $rules = Db::name("ices_admin_rule")->field("id")->select();
                $temp = [];
                foreach($rules as $i => $v){
                    $temp[] = $v['id'];
                }
                $flag = Db::name(Auth::instance()->getConfig("auth_group"))->where('id', $id)->update([
                    'rules' => implode(",", $temp)
                ]);
                if($flag){
                    $this->success("修改角色成功");
                }else{
                    $this->error("修改角色失败");
                }
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

    /**
     * @title 角色删除
     * @description 角色删除
     * @createtime: 2018/7/10 16:59
     */
    public function delete(){
        $pass = $this->checkJwtPassword(input('param.password', ''));
        if(!Auth::instance()->check("./admin/role", $pass['id'])){
            $this->result([], 0, '您暂无权限查看角色', 'json');
        }
        $config = Auth::instance()->getConfig();
        $post = input('param.');
        //判断不可删除的
        $delData = $post['deldata'];
        $deleteData = [];
        foreach($delData as $i => $v){
            $deleteData[] = $v['id'];
        }
        //如果有人在使用就无法删除
        $canNotDelArr = Db::name($config['auth_group_access'])
            ->field("count(*) as count,group_id as gid")
            ->where('group_id', 'in', $deleteData)
            ->select();
        $canNotDel = [1];
        foreach($delData as $i => $v){
            if(!empty($v['count']))
                $canNotDel[] = $v['gid'];
        }
        $errorDelete = IcesAdminGroup::icesDelete($delData, $canNotDel, "id", true);

        if(!empty($errorDelete)){
            $this->success("删除完成, 存在【" . implode(",", $canNotDelArr) . "】有使用无法删除");
        }else{
            $this->success("删除完成");
        }
    }

    /**
     * @title 角色规则树
     * @description 角色的规则树,要渲染来判断该角色是不是已经拥有指定规则
     * @createtime: 2018/7/10 17:00
     * @param int $id
     */
    public function rule_tree($id = 0){
        $checkRules = [];
        //如果当前用户的id存在并且是一个数字
        if(!empty($id) && is_numeric($id)){
            $groups = IcesAdminGroup::get(['id' => $id]);
            $checkRules = explode(",", $groups['rules']);
        }
        /**
         * 取出来所有的可用角色, 然后判断是否已选
         */
        $ruleList = Db::name(Auth::instance()->getConfig('auth_rule'))
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
