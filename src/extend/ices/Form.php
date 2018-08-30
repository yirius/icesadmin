<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 16:30
 */

namespace icesadmin\extend\ices;


use think\Exception;

class Form extends Backview
{
    protected $formIsInLine = false;

    protected $forminlinecontrols = [];

    protected $formcontrols = [];

    protected $formConsoleJs = ['admin', 'form'];

    protected $formValue = [];

    protected $formJavascript = null;

    /**
     * @title 作为基础方法,也是其他方法最后调用的
     * @description 作为基础方法,也是其他方法最后调用的,如果需要自定义的话,只需在type处写HTML
     * @createtime: 2018/7/11 00:32
     * @param string $type 定义的类型,有其他类型,也可以直接在此处写html true '' ''
     * @param string $label 前面显示的标题内容 false '' ''
     * @param array $info 这个组件的一些参数,不会渲染到html中 false [] ''
     * @param array $options 这个组件的HTML中attr false [] ''
     * @return $this
     */
    public function addControl($type, $label = '', $info = [], $options = []){
        //如果在inline状态下
        if($this->formIsInLine){
            $this->forminlinecontrols[] = [
                'type' => $type,
                'label' => $label,
                'options' => $options,
                'info' => $info
            ];
        }else{
            $this->formcontrols[] = [
                [
                    'type' => $type,
                    'label' => $label,
                    'options' => $options,
                    'info' => $info
                ]
            ];
        }
        return $this;
    }

    /**
     * @title 添加一个Text组件
     * @description 添加一个Text组件,input[type=text]
     * @createtime: 2018/7/11 00:38
     * @param string $name 组件要使用的input的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 在组件使用的时候需要的条件 false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addText($name, $label, $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'placeholder' => $placeholder,
            'autocomplete' => "off",
            'class' => "layui-input",
            'style' => "",
            'name' => $name
        ], $options);
        //判断需不需要填充内容
        if(!isset($options['value'])
            && isset($options['name'])
            && isset($this->formValue[$options['name']])
        ){
            $options['value'] = $this->formValue[$options['name']];
        }
        //没有用的都去掉
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("text", $label, array_merge([
            'inline' => "layui-input-block"
        ], $info), $options);
    }

    /**
     * @title 加入一个textarea组件
     * @description 加入一个textarea组件,<textarea></textarea>
     * @createtime: 2018/7/11 01:10
     * @param string $name 组件要使用的input的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 在组件使用的时候需要的条件,这个value需要写在info中 false [] ''
     * @param array $options 直接渲染在input上面的各种attr false [] ''
     * @return Form
     */
    public function addTextarea($name, $label, $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'placeholder' => $placeholder,
            'autocomplete' => "off",
            'class' => "layui-textarea",
            'style' => "",
            'name' => $name
        ], $options);
        //判断存不存在value,存在的话,就把他赋值给info
        if(!isset($info['value'])
            && isset($options['name'])
            && isset($this->formValue[$options['name']])
        ){
            $info['value'] = $this->formValue[$options['name']];
        }
        //去掉空值
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("textarea", $label, array_merge([
            'inline' => "layui-input-block",
            'value' => ""
        ], $info), $options);
    }

    /**
     * @title 添加一个密码输入框组件
     * @description 添加一个密码输入框组件
     * @createtime: 2018/7/11 01:12
     * @param string $name 组件要使用的input的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 在组件使用的时候需要的条件 false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addPassword($name, $label, $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'placeholder' => $placeholder,
            'autocomplete' => "off",
            'class' => "layui-input",
            'style' => "",
            'name' => $name
        ], $options);
        //判断需不需要填充内容
        if(!isset($options['value'])
            && isset($options['name'])
            && isset($this->formValue[$options['name']])
        ){
            $options['value'] = $this->formValue[$options['name']];
        }
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("password", $label, array_merge([
            'inline' => "layui-input-block",
        ], $info), $options);
    }

    /**
     * @title 添加一个上传图片组件
     * @description 添加一个上传图片组件
     * @createtime: 2018/7/11 01:12
     * @param string $name 组件要使用的input的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addUpload($name, $label, $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'class' => "layui-input",
            'style' => "",
            'data-url' => "./icestools/images",
            'data-method' => "POST",
            'data-data' => "",
            'data-headers' => "",
            'data-accept' => "images",
            'data-acceptMime' => "",
            'data-exts' => "",
            'data-auto' => "",
            'data-bindAction' => "",
            'data-field' => $name,
            'data-size' => "",
            'data-multiple' => "",
            'data-number' => "",
            'data-drag' => ""
        ], $options);
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        //判断需不需要填充内容
        if(empty($info['options']) && isset($this->formValue[$name])){
            $info['options'] = is_array($this->formValue[$name])?$this->formValue[$name]:explode(",", $this->formValue[$name]);
        }
        $this->formConsoleJs[] = "upload";

        return $this->addControl("upload", $label, array_merge([
            'options' => [],
            'inline' => "layui-input-block",
            'text' => "上传图片"
        ], $info), $options);
    }

    public function addWebuploader($name, $label, $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'class' => "layui-input",
            'style' => "",
            'id' => "icesadmin-" . $name,
            'data-server' => "./icestools/images",
            'data-name' => $name,
            'data-swf' => '/layui/tools/Uploader.swf',
            'data-fileNumLimit' => 5,
            'data-fileSizeLimit' => 1024 * 1024 * 100,
            'data-fileSingleSizeLimit' => 1024 * 1024 * 10,
            'data-label' => "点击选择文件",
            'data-accept' => '{"title":"选择文件","extensions":"gif,jpg,jpeg,bmp,png,xls,xlsx,ppt,pptx,docx,doc,do"}'
        ], $options);
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        //判断需不需要填充内容
        if(empty($info['options']) && isset($this->formValue[$name])){
            $info['options'] = is_array($this->formValue[$name])?implode(",", $this->formValue[$name]):$this->formValue[$name];
            $options['data-thumbs'] = $info['options'];
        }else{
            if(!empty($info['options'])){
                $options['data-thumbs'] = $info['options'];
            }
        }
        $this->formConsoleJs[] = "webupload";

        return $this->addControl("webuploader", $label, array_merge([
            'options' => '',
            'inline' => "layui-input-block",
            'text' => "上传图片"
        ], $info), $options);
    }

    /**
     * @title 添加一个Select组件
     * @description 添加一个Select组件
     * @createtime: 2018/7/11 01:22
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $list 需要显示的options,格式为[["text"=>"","value"=>"","checked"=>false]] false [] ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addSelect($name, $label, $list = [], $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'autocomplete' => "off",
            'lay-search' => "",
            'style' => "",
            'name' => $name
        ], $options);
        $options = array_filter($options, function($var){
            return !($var === "");
        });

        //判断需不需要填充内容
        if(isset($this->formValue[$name])){
            $valueArr = is_array($this->formValue[$name])?$this->formValue[$name]:explode(",", $this->formValue[$name]);
            //只有不是空才回去运算,否则会出现 XX => ''
            if(!empty($valueArr)){
                foreach($list as $i => $v){
                    if(in_array($v['value'], $valueArr)){
                        $list[$i]['checked'] = 1;
                    }
                }
            }
        }

        return $this->addControl("select", $label, array_merge([
            'options' => $list,
            'placeholder' => $placeholder,
            'inline' => "layui-input-block",
        ], $info), $options);
    }

    /**
     * @title 添加一个多选组件
     * @description 添加一个多选组件
     * @createtime: 2018/7/11 01:22
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $list 需要显示的options,格式为[["text"=>"","value"=>"","checked"=>false]] false [] ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addMulSelect($name, $label, $list = [], $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'style' => "",
            'xm-select' => "icesmulsel-" . $name,
            'xm-select-max' => "",
            'xm-select-skin' => "",
            'xm-select-search' => "",
            'xm-select-direction' => "",
            'xm-select-height' => "",
            'xm-select-radio' => "",
            'xm-select-linkage' => "",
            'xm-select-linkage-width' => "",
            'name' => $name
        ], $options);
        //判断hack打开search
        if($options['xm-select-search'] == "true"){
            $options = array_filter($options, function($var){
                return !($var === "");
            });
            $options['xm-select-search'] = "";
        }else{
            $options = array_filter($options, function($var){
                return !($var === "");
            });
        }

        if(isset($this->formValue[$name])){
            $valueArr = is_array($this->formValue[$name])?$this->formValue[$name]:explode(",", $this->formValue[$name]);
            //只有不是空才回去运算,否则会出现 XX => ''
            if(!empty($valueArr)){
                foreach($list as $i => $v){
                    if(in_array($v['value'], $valueArr)){
                        $list[$i]['checked'] = 1;
                    }
                }
            }
        }
        $this->formConsoleJs[] = "formSelects";

        return $this->addControl("mulselect", $label, array_merge([
            'options' => $list,
            'placeholder' => $placeholder,
            'inline' => "layui-input-block",
        ], $info), $options);
    }

    /**
     * @title 添加一个checkbox组件
     * @description 添加一个checkbox组件
     * @createtime: 2018/7/11 12:38
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $list 需要显示的options,格式为[["text"=>"","value"=>"","checked"=>false]] false [] ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addCheckbox($name, $label, $list = [], $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'lay-skin' => 'primary',
            'style' => "",
            'name' => $name . "[]"
        ], $options);
        //判断是否存在值
        if(isset($this->formValue[$name])){
            $valueArr = is_array($this->formValue[$name])?$this->formValue[$name]:explode(",", $this->formValue[$name]);
            //只有不是空才回去运算,否则会出现 XX => ''
            if(!empty($valueArr)){
                foreach($list as $i => $v){
                    if(in_array($v['value'], $valueArr)){
                        $list[$i]['checked'] = 1;
                    }
                }
            }
        }

        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("checkbox", $label, array_merge([
            'options' => $list,
            'inline' => "layui-input-block",
        ], $info), $options);
    }

    /**
     * @title 添加一个左右滑动的组件
     * @description 添加一个左右滑动的组件
     * @createtime: 2018/7/11 12:50
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addSwitch($name, $label, $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'lay-skin' => 'switch',
            'style' => "",
            'value' => 1,
            'name' => $name,
            'lay-text' => "开|关"
        ], $options);
        //判断是否存在值
        if(isset($this->formValue[$name])){
            $options['checked'] = empty($this->formValue[$name])?0:1;
        }
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("switch", $label, array_merge([
            'options' => [],
            'inline' => "layui-input-block"
        ], $info), $options);
    }

    /**
     * @title 添加一个单选radio组件
     * @description 添加一个单选radio组件
     * @createtime: 2018/7/11 12:51
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param array $list 需要显示的options,格式为[["text"=>"","value"=>"","checked"=>false]] false [] ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addRadio($name, $label, $list =[], $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'lay-skin' => 'primary',
            'style' => "",
            'name' => $name
        ], $options);
        //判断是否存在值
        if(isset($this->formValue[$name])){
            $valueArr = is_array($this->formValue[$name])?$this->formValue[$name]:explode(",", $this->formValue[$name]);
            //只有不是空才回去运算,否则会出现 XX => ''
            if(!empty($valueArr)){
                foreach($list as $i => $v){
                    if(in_array($v['value'], $valueArr)){
                        $list[$i]['checked'] = 1;
                    }
                }
            }
        }
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        return $this->addControl("radio", $label, array_merge([
            'options' => $list,
            'inline' => "layui-input-block"
        ], $info), $options);
    }

    /**
     * @title 添加一个日期选择组件
     * @description 添加一个日期选择组件,具体组件参考[http://www.layui.com/doc/modules/laydate.html]
     * @createtime: 2018/7/11 12:53
     * @param string $name 组件要使用的select的name true '' ''
     * @param string $label 组件前面展示的标题 true '' ''
     * @param string $placeholder 显示的placeholder false '' ''
     * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
     * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
     * @return Form
     */
    public function addDate($name, $label, $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'lay-verify' => '',
            'required' => '',
            'placeholder' => $placeholder,
            'autocomplete' => "off",
            'class' => "layui-input",
            'style' => "",
            'name' => $name,
            'data-type' => "",
            'data-range' => "",
            'data-format' => "yyyy-MM-dd",
            'data-value' => "",
            'isInitValue' => "",
            'data-min' => "",
            'data-max' => "",
            'data-trigger' => "",
            'data-showBottom' => "",
            'data-btns' => "",
            'data-lang' => "",
            'data-theme' => "",
            'data-calendar' => "",
            'data-mark' => []
        ], $options);
        $options = array_filter($options, function($var){
            return !($var === "");
        });

        if(isset($this->formValue[$name])){
            $options['value'] = $this->formValue[$name];
        }
        $this->formConsoleJs[] = "laydate";

        return $this->addControl("date", $label, array_merge([
            'inline' => "layui-input-block"
        ], $info), $options);
    }

    /*
    * @title 添加一个日期选择组件
    * @description 添加一个日期选择组件,具体组件参考[http://www.layui.com/doc/modules/laydate.html]
    * @createtime: 2018/7/11 12:53
    * @param string $name 组件要使用的select的name true '' ''
    * @param string $label 组件前面展示的标题 true '' ''
    * @param string $placeholder 显示的placeholder false '' ''
    * @param array $info 数组承载图片内容,多张就[XX,XXX] false [] ''
    * @param array $options 直接渲染在input上面的各种attr,赋值value需要写在options内 false [] ''
    * @return Form
    */
    public function addUeditor($name, $label, $placeholder = '', $info = [], $options = []){
        $options = array_merge([
            'placeholder' => $placeholder,
            'style' => "",
            'name' => $name
        ], $options);
        $options = array_filter($options, function($var){
            return !($var === "");
        });
        if(isset($this->formValue[$name])){
            $options['placeholder'] = $this->formValue[$name];
        }
        return $this->addControl("ueditor", $label, array_merge([
            'inline' => "layui-input-block"
        ], $info), $options);
    }

    /**
     * @title 设置表单的内容
     * @description 设置表单的值,在Form的Curd操作一开始就需要设置
     * @createtime: 2018/7/11 00:25
     * @param array $formValue 表单的值 true [] ''
     * @return $this
     */
    public function setFormValue($formValue)
    {
        $this->formValue = $formValue;
        return $this;
    }

    /**
     * @title 为Table或者其他操作准备
     * @description 在Table中或者其他方式渲染的时候,可以直接引入form.html,然后利用Form获取到内容去渲染
     * @createtime: 2018/7/11 00:27
     * @return array
     */
    public function getFormcontrols()
    {
        return $this->formcontrols;
    }

    /**
     * @title 开始设置内容是inline的
     * @description 开始设置内容是inline的,在结束的时候必须设置是false,否则最后几个无法展示
     * @createtime: 2018/7/11 00:28
     * @param bool $start 开启或者关闭 false true true|false
     * @return $this
     */
    public function startInLine($start = true){
        //首先判断是不是在Inline状态,是的话需要重置一下切换一下行数
        if($this->formIsInLine && $start === true){
            $this->formcontrols[] = $this->forminlinecontrols;
            $this->forminlinecontrols = [];
        }else{
            //否则就设置一下inline
            $this->formIsInLine = $start;
            if($start === false){
                $this->formcontrols[] = $this->forminlinecontrols;
                $this->forminlinecontrols = [];
            }
        }
        return $this;
    }

    /**
     * @title 设置表单名称
     * @description 设置表单名称,当表单是弹出,并且和table联动的时候,也就是点击提交之后table可以刷新等操作的时候需要设置
     * @createtime: 2018/7/11 12:57
     * @param string $formname form的name,不能使用-或者。等,只能使用英文或_ true '' ''
     * @return $this
     */
    public function setFormname($formname){
        $this->formname = $formname;
        return $this;
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
            $this->formConsoleJs = $js;
        }else{
            $this->formConsoleJs[] = $js;
        }
        return $this;
    }

    /**
     * @title 当仅有几句js需要写
     * @description 当仅有几句js需要写,不会影响整体架构的话可以直接在PHP里写js
     * @createtime: 2018/7/11 13:00
     * @param string $javascript 用<<<HTML包含,可以直接写JS true '' ''
     * @return $this
     */
    public function addFormJavascript($javascript){
        $this->formJavascript = $javascript;
        return $this;
    }

    /**
     * @title 最后的输出方法
     * @description 最后的输出方法,在class中必须return $form->show();
     * @createtime: 2018/7/13 15:44
     * @param bool $isPop 是否是浮窗,默认不是浮窗 false true true|false
     * @param bool $submitUrl 如果不是浮窗,需要指定提交的url,否则从table中指定 false false url||false
     * @param null $submitCallBack 如果不是浮窗,同时存在jscallback,会自动执行 false null ''
     * @return \think\Response
     * @throws Exception
     */
    public function show($isPop = true, $submitUrl = false, $submitCallBack = null){
        if($isPop !== true){
            if(empty($submitUrl)){
                throw new Exception("当Form不是浮窗的时候必须指定提交地址");
            }
        }
        return $this
            ->assign("_formname", $this->formname)
            ->assign("_formcontrols", $this->formcontrols)
            ->assign("_formconsolejs", $this->formConsoleJs)
            ->assign("_formjavascript", $this->formJavascript)
            ->assign("_formispop", $isPop)
            ->assign("_formsubmiturl", $submitUrl)
            ->assign("_formsubmitcallback", $submitCallBack)
            ->render($isPop?"base/formpop":"base/form");
    }
}
