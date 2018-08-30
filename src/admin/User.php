<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 14:30
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminMember;
use icesadmin\extend\auth\Auth;
use icesadmin\extend\ices\Backend;
use icesadmin\extend\jwt\Adminjwt;
use think\Db;
use think\facade\Response;
use think\facade\Validate;
use traits\controller\Jump;

class User
{
    use Backend;
    use Jump;

    /**
     * @title 获取到用户的列表
     * @description 获取到后台管理用户的列表
     * @createtime: 2018/7/10 16:36
     * @param string $access_token jwt_token,利用Adminjwt生成 true '' ''
     * @return \think\response
     */
    public function lists($access_token){
        $logininfo = Adminjwt::instance()->decodeHmac($access_token);
        if(!Auth::instance()->check("./admin/list", $logininfo['id'])){
            $this->result([], 0, '您暂无权限查看后台管理员', 'json');
        }
        $where = [];
        /**
         * 判断一下是否存在账号和手机号
         */
        $username = input('param.username', '');
        if(!empty($username)){
            $where[] = ['username', 'like', "%" . $username . "%"];
        }
        $phone = input('param.phone', '');
        if(!empty($phone)){
            $where[] = ['phone', 'like', "%" . $phone . "%"];
        }
        //循环生成列表
        $result = IcesAdminMember::icesList("id,username,phone,createtime,realname,status");
        $result['msg'] = "查询用户信息成功";
        return Response::create($result, "json");
    }

    /**
     * @title 删除管理用户信息
     * @description 删除管理用户信息,1号不可删除
     * @createtime: 2018/7/10 16:48
     * @return \think\response
     */
    public function delete(){
        $uinfo = $this->checkJwtPassword(input('param.password', ''));
        if(!Auth::instance()->check("./admin/list", $uinfo['id'])){
            $this->result([], 0, '您暂无权限删除后台管理员', 'json');
        }
        $post = input('param.');
        $delData = $post['deldata'];
        //循环去删除用户信息和角色信息
        $access_type = Auth::instance()->getConfig("access_type");
        $auth_group_access = Auth::instance()->getConfig("auth_group_access");
        //循环去删除用户信息和角色信息
        $updateFlag = true;$deleteFlag = true;
        foreach($delData as $i => $v){
            //主账号不可删除
            if($v['id'] == 1){
                continue;
            }
            $updateFlag = Db::name(Auth::instance()->getConfig("auth_user")[$access_type])
                ->where('id', $v['id'])
                ->delete();
            $deleteFlag = Db::name($auth_group_access)->where('uid', $v['id'])->where('type', $access_type)->delete();
        }
        /**
         * 返回成功与失败
         */
        if($updateFlag && $deleteFlag){
            $this->success("删除管理员账号成功");
        }else{
            $this->error("删除管理员账号出现了一些问题");
        }
    }

    /**
     * @title 获取用户角色
     * @description 获取用户角色, 根据传过来的id来判断哪个角色是已经选择了的
     * @createtime: 2018/7/10 16:53
     * @param int $id
     * @return \think\response
     */
    public function role($id = 0){
        $checkGroup = [];
        //如果当前用户的id存在并且是一个数字
        if(!empty($id) && is_numeric($id)){
            $groups = Auth::instance()->getGroups($id);
            foreach($groups as $i => $v){
                $checkGroup[] = $v['group_id'];
            }
        }
        /**
         * 取出来所有的可用角色, 然后判断是否已选
         */
        $roleList = Db::name(Auth::instance()->getConfig("auth_group"))
            ->field("id as value, title as text")
            ->where('status', 1)
            ->select();
        foreach($roleList as $i => $v){
            if(in_array($v['value'], $checkGroup)){
                $roleList[$i]['checked'] = 1;
            }else{
                $roleList[$i]['checked'] = 0;
            }
        }
        $this->result($roleList, 1, "获取管理员用户组成功");
    }

    public function editpwd($oldPassword, $password){
        $logininfo = Adminjwt::instance()->decodeHmac(input('param.access_token', ''));
        $access_type = empty($logininfo['access_type']) ? 0 : $logininfo['access_type'];
        $userinfo = Auth::instance()->getUserInfo($logininfo['id'], $access_type);
        if(sha1($oldPassword.$userinfo['salt']) != $userinfo['password']){
            $this->error("很抱歉, 您输入的原密码不正确,无法修改");
        }else{
            if($access_type == 0){
                IcesAdminMember::icesSave([
                    'password' => sha1($password . $userinfo['salt'])
                ], ['id' => $logininfo['id']]);
            }else{
                Db::name(Auth::instance()->getConfig("auth_user")[$access_type])->where('id', "=", $logininfo['id'])->update(['password' => sha1($password . $userinfo['salt'])]);
            }
            $this->success("修改密码成功");
        }
    }

    /**
     * @title 用户资料更新
     * @description 用户资料更新接口, 需要传递username,phone,realname,role这几个参数
     * @createtime: 2018/7/1 16:09
     * @return \think\response
     */
    public function update(){
        $logininfo = Adminjwt::instance()->decodeHmac(input('param.access_token', ''));;
        if(!Auth::instance()->check("./admin/list", $logininfo['id'])){
            $this->result([], 0, '您暂无权限更新后台管理员', 'json');
        }
        $validate = Validate::make([
            'username' => "require|max:20",
            'phone' => "require|mobile",
            'realname' => "require|chs",
            'role' => "require|array"
        ], [
            'username.require' => "登录账户名称必须填写",
            'username.max' => "登录账户名称不能超过20位",
            'phone.require' => "用户对应手机号必须填写",
            'phone.mobile' => "用户手机号必须填写正确格式",
            'realname.require' => "真实姓名必须填写",
            'realname.chs' => "真实姓名必须是汉字",
            'role.require' => "用户权限必须选择",
            'role.array' => "用户权限格式错误"
        ]);
        /**
         * 验证用户是否信息格式正确
         */
        $post = input("post.");
        if(!$validate->check($post)){
            $this->error($validate->getError());
        }else{
            $role = $post['role'];
            $password = $post['password'];
            $id = empty($post['id'])?0:$post['id'];
            //构造保存数据
            $saveData = [
                'username' => $post['username'],
                'realname' => $post['realname'],
                'phone' => $post['phone'],
                'status' => empty($post['status'])?0:1
            ];
            if(!empty($id) && is_numeric($id)){
                //这个说明是修改
                //首先检查一下是否存在对group的角色修改
                $groups = Auth::instance()->getGroups($id);
                $checkGroup = [];
                foreach($groups as $i => $v){
                    $checkGroup[] = $v['group_id'];
                }
                //通过diff找到那个需要更新, 那个需要删除
                $deleteRole = array_diff($checkGroup, $role);
                $addRole = array_diff($role, $checkGroup);
                //超级管理员1号不允许和添加权限
                if($id == 1){ $deleteRole = [];$addRole = [];}
                //判断是否需要修改密码
                if(!empty($password)){
                    $userinfo = Auth::instance()->getUserInfo($id);
                    $saveData['password'] = sha1($password . $userinfo['salt']);
                }
                //首先更新用户数据
                $access_type = Auth::instance()->getConfig("access_type");
                $saveData['updatetime'] = date("Y-m-d H:i:s");
                $adminUserDb = Db::name(Auth::instance()->getConfig("auth_user")[$access_type]);
                $updateFlag = $adminUserDb->where('id', $id)->update($saveData);
                if($updateFlag){
                    //然后去判断是否存在删除的角色数据和添加的角色数据
                    $auth_group_access = Auth::instance()->getConfig("auth_group_access");
                    $deleteFlag = true;
                    if(!empty($deleteRole)){
                        $deleteFlag = Db::name($auth_group_access)
                            ->where('uid', $id)
                            ->where('type', $access_type)
                            ->where('group_id', "in", $deleteRole)
                            ->delete();
                    }
                    /**
                     * 给新增用户添加角色
                     */
                    $addFlag = true;
                    if(!empty($addRole)){
                        $insertData = [];
                        foreach($addRole as $i => $v){
                            $insertData[] = [
                                'uid' => $id,
                                'type' => $access_type,
                                'group_id' => $v
                            ];
                        }
                        $addFlag = Db::name($auth_group_access)->insertAll($insertData);
                    }
                    if($addFlag && $deleteFlag){
                        $this->success("更新管理员数据和管理员权限成功");
                    }else{
                        $this->success("更新管理员数据成功, 更新管理员权限出现问题");
                    }
                }else{
                    $this->error("更新管理员数据出现了一些问题, 请您联系技术人员");
                }
            }else{
                //这个说明是新增
                if(empty($password))
                    $this->error("新增管理员必须填写密码");
                else{
                    if(strlen($password) < 6)
                        $this->error("新增管理员填写密码必须大于6位");
                }
                $saveData['salt'] = icesRandom();
                $saveData['password'] = sha1($password . $saveData['salt']);
                $saveData['createtime'] = date("Y-m-d H:i:s");
                //首先添加用户
                $access_type = Auth::instance()->getConfig("access_type");
                $adminUserDb = Db::name(Auth::instance()->getConfig("auth_user")[$access_type]);
                $insertFlag = $adminUserDb->insert($saveData);
                if($insertFlag){
                    $userid = $adminUserDb->getLastInsID();
                    $insertData = [];
                    foreach($role as $i => $v){
                        $insertData[] = [
                            'uid' => $userid,
                            'type' => $access_type,
                            'group_id' => $v
                        ];
                    }
                    //然后去判断是否存在删除的角色数据和添加的角色数据
                    $auth_group_access = Auth::instance()->getConfig("auth_group_access");
                    $addFlag = Db::name($auth_group_access)->insertAll($insertData);
                    if($addFlag){
                        $this->success("更新管理员数据和管理员权限成功");
                    }else{
                        $this->success("更新管理员数据成功, 管理员权限添加失败");
                    }
                }else{
                    $this->error("新增管理员数据出现了一些问题, 请您联系技术人员");
                }
            }
        }
    }
}
