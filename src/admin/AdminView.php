<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:29
 */

namespace icesadmin\admin;


use icesadmin\common\model\IcesAdminMenu;
use icesadmin\common\model\IcesAdminRule;
use icesadmin\extend\ices\Form;
use icesadmin\extend\ices\Table;

class AdminView
{
    /**
     * @title 规则界面
     * @description 规则界面,用作例子
     * @createtime: 2018/7/10 19:15
     * @return \think\Response
     */
    public function rule(){
        $table = new Table();
        return $table
            ->addColumn("id", "ID")
            ->addColumn("_name", "规则名称")
            ->addColumn("status", "规则状态")
            ->addToolColumn("操作", 150)
            ->addCheckbox()
            ->addTableBtn(['add', 'del'])
            ->addToolbar(["edit", ['del', 'd._child == true || [1,2,3,4,5].indexOf(d.id)']])
            ->setTableform("../../icesadminview/ruleForm",
                "添加角色规则",
                './icesrule/update',
                'icesadmin-ruleform-submit',
                "form.render(null, 'icesadmin-ruleform')"
            )
            ->setPageBreadcrumb(['用户设置', "规则设置"])
            ->show("./icesrule/lists.html", './icesrule/delete');
    }

    /**
     * @title 规则的填写内容界面
     * @description 规则的填写内容界面
     * @createtime: 2018/7/10 20:22
     * @param int $id
     * @return \think\Response
     */
    public function ruleForm($id = 0){
        $ruleinfo = $id != 0? IcesAdminRule::get(['id' => $id])->toArray() : [];
        $form = new Form();
        return $form
            ->setFormValue($ruleinfo)
            ->addText('name', '规则英文名', "请输入规则name, 和其他使用对应")
            ->addText('title', '规则名称', "请输入规则中文名")
            ->addSwitch('status', '规则状态')
            ->addTextarea('condition', '附加条件', "请输入该规则的附加条件, 无则不输入")
            ->addText('mid', '上级编号', "请输入上级编号")
            ->setFormname("icesadmin-ruleform")
            ->show();
    }

    /**
     * @title 菜单界面
     * @description 菜单的列表界面
     * @createtime: 2018/7/10 20:25
     * @return \think\Response
     */
    public function menu(){
        $table = new Table();
        return $table
            ->addColumn("id", "ID")
            ->addColumn("_name", "规则名称")
            ->addColumn("jump", "跳转地址")
            ->addColumn("sort", "排序")
            ->addToolColumn("操作", 150)
            ->addCheckbox()
            ->addTableBtn(['add', 'del'])
            ->addToolbar(["edit", ['del', 'd._child == true || [1,2,3,4,5].indexOf(d.id)']])
            ->setTableform("../../icesadminview/menuForm",
                "添加菜单",
                './icesmenu/update',
                'icesadmin-menuform-submit',
                "form.render(null, 'icesadmin-menuform')"
            )
            ->setPageBreadcrumb(['用户设置', "菜单设置"])
            ->show("./icesmenu/lists.html", './icesmenu/delete');
    }

    /**
     * @title 菜单的资料填写界面
     * @description
     * @createtime: 2018/7/10 20:25
     * @param int $id
     * @return \think\Response
     */
    public function menuForm($id = 0){
        $ruleinfo = $id != 0? IcesAdminMenu::get(['id' => $id])->toArray() : [];
        $form = new Form();
        return $form
            ->setFormValue($ruleinfo)
            ->addText('name', '菜单英文名', "请输入菜单name, 和其他使用对应")
            ->addText('title', '菜单名称', "请输入菜单中文名")
            ->addText('jump', '跳转地址', "请输入跳转地址, 需要和规则内的英文相对应")
            ->addText('icon', '图标', "请输入菜单对应的图标")
            ->addText('pid', '上级编号', "请输入上级编号, 不输入默认为0")
            ->addText('sort', '排序编号', "请输入排序编号")
            ->setFormname("icesadmin-menuform")
            ->show();
    }
}
