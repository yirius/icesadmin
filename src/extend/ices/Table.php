<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:30
 */

namespace icesadmin\extend\ices;


class Table extends Backview
{
    /**
     * 表头设置
     * @type array
     */
    protected $columns = [];
    /**
     * 尾部的toolbar设置
     * @type string
     */
    protected $toolbar = '';
    /**
     * Table上方的按钮
     * @type string
     */
    protected $tablebtn = '';
    /**
     * 左侧是否打开选择, 如果有delete的话左侧必须打开选择
     * @type bool
     */
    protected $checkbox = false;
    /**
     * 表格对应的编辑或者新增界面
     * @type array
     */
    protected $tableform = [];

    /**
     * 存放需要加入的js
     * @type array
     */
    protected $tableConsoleJs = ['admin', 'table', 'form', 'think', 'util'];

    /**
     * 存放table的模板
     * @type array
     */
    protected $tableTemplate = [];

    /**
     * 给table加入javascript,和Backview的不是一回事
     * @type null
     */
    protected $tableJavascript = null;

    /**
     * 存放\Form对应实例
     * @type array
     */
    protected $tableSearchForm = [];

    /**
     * 如果有text的编辑,需要自己写一下编辑的方法
     * @type null
     */
    protected $tableEditEvent = null;

    protected $tableConfig = [];
    /**
     * @title 在左侧添加一个checkbox
     * @description 在左侧添加一个checkbox
     * @createtime: 2018/7/13 19:23
     * @return $this
     */
    public function addCheckbox(){
        $this->checkbox = true;
        return $this;
    }
    
    /**
     * @title 添加一列
     * @description 添加一列
     * @createtime: 2018/7/13 19:20
     * @param string $field 对应的返回data中的字段 true '' ''
     * @param string $title 对应的上方显示的title true '' ''
     * @param bool $sort 是否可以排列,排列提交到服务端进行 false false true|false
     * @param array $config 其他配置参考,会自动带入到cols中 false [] ''
     * @return $this
     */
    public function addColumn($field, $title, $sort = false, $config = []){
        $config = array_merge([
            'field' => $field,
            'title' => $title,
            'sort' => $sort,
            'width' => '',
            'type' => 'normal',//checkbox, space,numbers
            'LAY_CHECKED' => 'false',
            'fixed' => '',
            'unresize' => 'false',
            'edit' => '',
            'event' => '',
            'styles' => '',
            'align' => '',
            'colspan' => '',
            'rowspan' => '',
            'templet' => '',
            'toolbar' => ''
        ], $config);
        $this->columns[] = array_filter($config);
        return $this;
    }

    /**
     * @title 添加一个固定在右侧的toolbar
     * @description 添加一个固定在右侧的toolbar,只有width和title需要设置,如果有更改tablename,需要在此之前
     * @createtime: 2018/7/13 19:23
     * @param string $title 这一列上面名字
     * @param int $width 列的长度,默认是150
     * @return Table
     */
    public function addToolColumn($title, $width = 150){
        return $this->addColumn('', $title, false, [
            'width' => $width,
            'align' => "center",
            'fixed' => 'right',
            'toolbar' => "#" . $this->tablename . "-toolbar"
        ]);
    }

    /**
     * @title 添加一个js文件
     * @description 当不是直接在PHP内手写JS,JS较多,新开了一个.js文件,需要引用一下
     * @createtime: 2018/7/11 12:58
     * @param string $js 请把js文件放在/public/app/controller内,然后只添加js名称即可 true '' ''
     * @return $this
     */
    public function addConsoleJs($js){
        if(is_array($js)){
            $this->tableConsoleJs = $js;
        }else{
            $this->tableConsoleJs[] = $js;
        }
        return $this;
    }

    /**
     * @title 添加toolbar对应内容
     * @description 添加toolbar对应内容
     * @createtime: 2018/7/13 19:32
     * @param string $html 这个可以为html,也是可以为edit/del这两个默认定义的 true '' ''
     * @param bool $condition 这个是专门针对del方法设置的,需要使用比如d.id>10这种来判断 false false string
     * @return $this
     */
    public function addToolbar($html, $condition = false){
        if(is_array($html)){
            foreach($html as $i => $v){
                if(is_array($v)){
                    $this->addToolbar($v[0], $v[1]);
                }else{
                    $this->addToolbar($v);
                }
            }
        }else{
            if($html == "edit"){
                $this->toolbar .= '<a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="icesadmin-edit"><i class="layui-icon layui-icon-edit"></i>编辑</a>';
            }elseif($html == "del"){
                $del = '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="icesadmin-del"><i class="layui-icon layui-icon-delete"></i>删除</a>';
                if($condition != false){
                    $this->toolbar .= '{{#  if('.$condition.'){ }}<a class="layui-btn layui-btn-disabled layui-btn-xs"><i class="layui-icon layui-icon-delete"></i>删除</a>{{#  } else { }}'.$del.'{{#  } }}';
                }else{
                    $this->toolbar .= $del;
                }
            }else{
                $this->toolbar .= $html;
            }
        }
        return $this;
    }

    /**
     * @title 添加一个template,在column中使用
     * @description 添加一个template,在column中使用
     * @createtime: 2018/7/13 19:34
     * @param string $id template对应的id,界面最好不要重复 true '' ''
     * @param string $content 需要的内容 true '' ''
     * @return $this
     */
    public function addTemplate($id, $content){
        $this->tableTemplate[] = [
            'id' => $id,
            'content' => $content
        ];
        return $this;
    }

    /**
     * @title 添加对应按钮
     * @description 添加对应按钮。默认值可以直接传入add/del,如果是默认值的话需要调用setTableform,否则点击无效
     * @createtime: 2018/7/13 19:39
     * @param string $btn 默认值可以直接传入add/del,或自己的html true '' ''
     * @return $this
     */
    public function addTableBtn($btn){
        if(is_array($btn)){
            foreach($btn as $i => $v){
                $this->addTableBtn($v);
            }
        }else{
            if($btn == "del"){
                $this->tablebtn .= '<button class="layui-btn layui-btn-danger layui-btn-sm layuiadmin-btn-admin" data-type="del">删除</button>';
            }else if($btn == "add"){
                $this->tablebtn .= '<button class="layui-btn layui-btn-sm layuiadmin-btn-admin" data-type="add">添加</button>';
            }else{
                $this->tablebtn .= $btn;
            }
        }
        return $this;
    }

    /**
     * @title 加入table的js
     * @description 加入table的js,不是page的js
     * @createtime: 2018/7/13 20:59
     * @param string $javascript
     * @return $this
     */
    public function addTableJavascript($javascript){
        $this->tableJavascript = $javascript;
        return $this;
    }

    /**
     * @title 设置table对应的编辑或者弹出框
     * @description 设置table对应的编辑或者弹出框,如果有弹出框,弹出框的Form属性isPop最好是true
     * @createtime: 2018/7/13 19:45
     * @param string $view 展示的界面,如果是后端的话需要写成../../才可以 true '' ''
     * @param string $title 题头名称 true '' ''
     * @param string $url 最后提交的地址 true '' ''
     * @param string $btn 表单的提交按钮名称,需要在form中设置,如果不存在,如果是一套后台的话,应该是[您设置的名称-submit],不存在则点击提交table不会刷新且下方无作用 false '' ''
     * @param string $rendercall pop弹窗渲染完成了的js回调 false '' ''
     * @param string $submitcall
     * @param array $area
     * @return $this
     */
    public function setTableform($view, $title, $url, $btn = '', $rendercall = '', $submitcall = '', $area = []){
        if(!is_array($title)){
            $title = [str_replace("编辑", "添加", $title), str_replace("添加", "编辑", $title)];
        }
        $this->tableform = [
            'view' => $view,
            'title' => $title,
            'btn' => $btn,
            'url' => $url,
            'renderDoneCall' => $rendercall,
            'submitCallback' => $submitcall,
            'area' => $area
        ];
        return $this;
    }


    /**
     * @title 设置Table的name
     * @description 设置table的name,方便自定义事件的时候触发
     * @createtime: 2018/7/13 20:57
     * @param string $tablename name true '' ''
     * @return $this
     */
    public function setTablename($tablename){
        $this->tablename = $tablename;
        return $this;
    }

    /**
     * @title 设置table的edit事件
     * @description 当对某一列设置了edit=text之后需要自己处理事件
     * @createtime: 2018/7/13 20:58
     * @param string $event 事件内容 true '' ''
     * @return $this
     */
    public function setEditEvent($event){
        $this->tableEditEvent = $event;
        return $this;
    }

    /**
     * @title 设置table上方查找的form
     * @description 设置table上方查找的form,和Form有一定种类差别
     * @createtime: 2018/7/13 21:00
     * @param Form $tableSearchForm
     * @return $this
     */
    public function setTableSearchForm(Form $tableSearchForm)
    {
        $this->tableSearchForm = $tableSearchForm->getFormcontrols();
        return $this;
    }

    /**
     * @param array $tableConfig
     */
    public function setTableConfig($tableConfig)
    {
        $this->tableConfig = $tableConfig;
        return $this;
    }

    /**
     * @title 返回渲染的html
     * @description 返回渲染的html,必须设置从哪个url获取到列表信息
     * @createtime: 2018/7/13 21:00
     * @param string $tableUrl 获取到列表信息的url,最好配合ices\Model使用 true '' ''
     * @param string $tableDeleteUrl 删除的地址,table默认删除是需要输入密码的 '' '' ''
     * @return \think\Response
     */
    public function show($tableUrl, $tableDeleteUrl = ''){
        empty($tableDeleteUrl) ? null : $this->checkbox = true;
        if($this->checkbox){
            $this->columns = array_merge([
                ['type' => "checkbox", 'fixed' => "left"]
            ], $this->columns);
        }
        return $this
            ->assign("_tablecolumns", $this->columns)
            ->assign("_tablename", $this->tablename)
            ->assign("_tabletoolsbar", $this->toolbar)
            ->assign("_tablebtns", $this->tablebtn)
            ->assign("_tableurl", $tableUrl)
            ->assign("_tabledelurl", $tableDeleteUrl)
            ->assign("_tableformurl", $this->tableform)
            ->assign("_tableconsolejs", array_unique($this->tableConsoleJs))
            ->assign("_tabletemplate", $this->tableTemplate)
            ->assign("_tablejavascript", $this->tableJavascript)
            ->assign("_formcontrols", $this->tableSearchForm)
            ->assign("_tableeditevent", $this->tableEditEvent)
            ->assign("_tableconfig", $this->tableConfig)
            ->render("base/table");
    }
}
