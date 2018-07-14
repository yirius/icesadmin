<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:32
 */

namespace icesadmin\extend\ices;


use icesadmin\common\logic\Backtool;
use think\Controller;
use think\facade\Env;
use think\facade\View;

/**
 * Class Backview
 * @package icesadmin\extend\ices
 */
class Backview
{
    /**
     * 从facede里面获取到的view参数
     * @type mixed|null|\think\App
     */
    protected $view = null;
    /**
     * 预存的tablename
     * @type string
     */
    protected $tablename = '';
    /**
     * 预存的formname
     * @type string
     */
    protected $formname = '';
    /**
     * 所有界面的title
     * @type array
     */
    protected $pagetitle = ['新开界面'];
    /**
     * 用来记录自定义的css
     * @type string
     */
    protected $pageStyle = "";
    /**
     * 用来记录自定义的js
     * @type string
     */
    protected $pageScript = "";

    /**
     * @title Backview constructor.
     */
    public function __construct(){
        $this->view = app("view");
        $this->view->config("view_path", ices_root . DS . "view" .DS);
        $this->view->engine->config("cache_path", Env::get('runtime_path').DS."icesadmin".DS);
        /**
         * 为了防止是一个单独的展示界面,不设置name,需要预先定义
         */
        $this->tablename = "icesadmin_table_" . time();
        $this->formname = "icesadmin_form_" . time();
    }

    /**
     * @title 设置界面上方的标题
     * @description 设置界面上方的标题
     * @createtime: 2018/7/12 15:08
     * @param array $crumb 界面上方标题栏目,建议每个界面都设置,方便标识,是一个无key的数组 true [] ''
     * @return $this
     */
    public function setPageBreadcrumb($crumb){
        $this->pagetitle = !is_array($crumb)?[$crumb]:$crumb;
        return $this;
    }

    /**
     * @title 设置界面的样式
     * @description 设置界面的样式,会自动对css进行压缩
     * @createtime: 2018/7/12 15:19
     * @param string $style 设置的css内容,可以多次设置 true [] ''
     * @return $this
     */
    public function setPageStyle($style){
        $this->pageStyle .= $style;
        return $this;
    }

    /**
     * @title 设置界面的逻辑
     * @description 设置界面的逻辑,会自动对js进行压缩
     * @createtime: 2018/7/12 15:19
     * @param string $script 设置的js内容,可以多次设置 true [] ''
     * @return $this
     */
    public function setPageScript($script){
        $this->pageScript .= $script;
        return $this;
    }

    /**
     * @title 暴露assign方法
     * @description 暴露assign方法
     * @createtime: 2018/7/12 15:21
     * @param $name
     * @param $value
     * @return $this
     */
    protected function assign($name, $value){
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * @title 暴露fetch转render
     * @description 暴露fetch转render
     * @createtime: 2018/7/12 15:22
     * @param $template
     * @param array $vars
     * @param array $replace
     * @param array $config
     * @param bool $renderContent
     * @return \think\Response
     */
    protected function render($template, $vars = [], $replace = [], $config = [], $renderContent = false){
        if($this->pageStyle){
            $this->view->assign("_pagestyle", Backtool::css_compress($this->pageStyle));
        }
        if($this->pageScript){
            $this->view->assign("_pagescript", Backtool::js_compress($this->pageScript));
        }
        $this->view->assign("_pagetitle", $this->pagetitle);
        return response($this->view->fetch($template, $vars, $replace, $config, $renderContent));
    }
}
