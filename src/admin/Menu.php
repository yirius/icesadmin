<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:29
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminMenu;
use icesadmin\common\model\IcesAdminRule;
use icesadmin\extend\auth\Auth;
use icesadmin\extend\auth\AuthData;
use icesadmin\extend\ices\Backend;
use icesadmin\extend\jwt\Adminjwt;
use think\facade\Response;
use think\facade\Validate;
use traits\controller\Jump;

class Menu
{
    use Backend;
    use Jump;

    /**
     * @title 后台菜单列表
     * @description
     * @createtime: 2018/7/10 17:09
     * @param $access_token
     * @return \think\response
     */
    public function lists($access_token){
        $logininfo = Adminjwt::instance()->decodeHmac($access_token);
        if(!Auth::instance()->check("../../icesadminview/menu", $logininfo['id'])){
            $this->result([], 0, '您暂无权限查看后台菜单', 'json');
        }
        $result = IcesAdminMenu::icesList();
        $result['data'] = AuthData::tree($result['data'], 'title');
        $result['msg'] = "suc";
        return Response::create($result, "json");
    }

    /**
     * @title 删除菜单
     * @description
     * @createtime: 2018/7/10 17:09
     */
    public function delete(){
        $pass = $this->checkJwtPassword(input('param.password', ''));
        if(!Auth::instance()->check("../../icesadminview/menu", $pass['id'])){
            $this->result([], 0, '您暂无权限删除后台菜单', 'json');
        }
        $post = input('param.');
        //判断不可删除的
        $delData = $post['deldata'];
        $errorDelete = IcesAdminMenu::icesDelete($delData, [1,2,3,4,5], 'id', true);
        if(!empty($errorDelete)){
            $this->success("删除完成, 存在【" . implode(",", $errorDelete) . "】有使用无法删除");
        }else{
            $this->success("删除完成");
        }
    }

    /**
     * @title 修改后台菜单
     * @description 修改后台菜单
     * @createtime: 2018/7/10 17:11
     * @param int $id
     */
    public function update($id = 0){
        $logininfo = Adminjwt::instance()->decodeHmac(input("param.access_token"));
        if(!Auth::instance()->check("../../icesadminview/menu", $logininfo['id'])){
            $this->result([], 0, '您暂无权限修改后台菜单', 'json');
        }
        $validate = Validate::make([
            'name' => "require",
            'title' => "require",
            'jump' => "require",
            'pid' => "number",
            'sort' => "require|number"
        ], [
            'name.require' => "菜单英文名必须上传",
            'title.require' => "菜单中文名必须上传",
            'jump.require' => "菜单跳转地址必须上传",
            'pid.number' => "上级编号必须是数字",
            'sort.require' => "排序编号必须上传",
            'sort.number' => "排序编号必须是数字"
        ]);
        $post = input('post.');
        if(!$validate->check($post)){
            $this->error($validate->getError());
        }
        $result = IcesAdminMenu::icesSave([
            'name' => $post['name'],
            'title' => $post['title'],
            'jump' => $post['jump'],
            'pid' => empty($post['pid'])?0:$post['pid'],
            'sort' => empty($post['sort'])?0:$post['sort'],
            'icon' => empty($post['icon'])?null:$post['icon']
        ], $id == 0?[]:['id' => $id]);

        $this->success($id == 0?"新增菜单成功":"修改菜单成功");
    }
}
