<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:29
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminRule;
use icesadmin\extend\auth\Auth;
use icesadmin\extend\auth\AuthData;
use icesadmin\extend\ices\Backend;
use icesadmin\extend\jwt\Adminjwt;
use think\facade\Response;
use think\facade\Validate;
use traits\controller\Jump;

class Rule
{
    use Backend;
    use Jump;

    /**
     * @title 获取规则列表
     * @description 获取到所有的规则列表
     * @createtime: 2018/7/10 17:05
     * @param $access_token
     * @return \think\response
     */
    public function lists($access_token){
        $logininfo = Adminjwt::instance()->decodeHmac($access_token);
        if(!Auth::instance()->check("../../icesadminview/rule", $logininfo['id'])){
            $this->result([], 0, '您暂无权限查看后台规则', 'json');
        }
        $result = IcesAdminRule::icesList('*', [], [], function($item){
            $item->status = $item->status?"开启":"关闭";
        });
        $result['data'] = AuthData::tree($result['data'], 'title', 'id', 'mid');
        $result['msg'] = "suc";
        return Response::create($result, "json");
    }

    /**
     * @title 删除后台规则
     * @description
     * @createtime: 2018/7/10 17:06
     */
    public function delete(){
        $pass = $this->checkJwtPassword(input('param.password', ''));
        if(!Auth::instance()->check("../../icesadminview/rule", $pass['id'])){
            $this->result([], 0, '您暂无权限删除后台规则', 'json');
        }
        $post = input('param.');
        //判断不可删除的
        $delData = $post['deldata'];
        $errorDelete = IcesAdminRule::icesDelete($delData, [1,2,3,4,5], "id", true);
        if(!empty($errorDelete)){
            $this->success("删除完成, 存在【" . implode(",", $errorDelete) . "】有使用无法删除");
        }else{
            $this->success("删除完成");
        }
    }

    /**
     * @title 后台规则的更新
     * @description
     * @createtime: 2018/7/10 17:07
     * @param int $id
     */
    public function update($id = 0){
        $logininfo = Adminjwt::instance()->decodeHmac(input("param.access_token"));
        if(!Auth::instance()->check("../../icesadminview/rule", $logininfo['id'])){
            $this->result([], 0, '您暂无权限修改后台规则', 'json');
        }
        $validate = Validate::make([
            'name' => "require",
            'title' => "require",
            'mid' => "number"
        ], [
            'name.require' => "规则英文名必须上传",
            'title.require' => "规则中文名必须上传",
            'mid.number' => "上级编号必须是数字"
        ]);
        $post = input('post.');
        if(!$validate->check($post)){
            $this->error($validate->getError());
        }
        $result = IcesAdminRule::icesSave([
            'name' => $post['name'],
            'title' => $post['title'],
            'status' => empty($post['status'])?0:1,
            'mid' => empty($post['mid'])?0:$post['mid']
        ], $id == 0?[]:['id' => $id]);

        $this->success($id == 0?"新增规则成功":"修改规则成功");
    }
}
