<?php
/**
 * User: Yirius
 * Date: 2018/7/12
 * Time: 15:14
 */

namespace icesadmin\common\logic;


class Backtool
{
    static function css_compress($string){
        $string = str_replace('\r\n',"",$string); //首先去掉换行
        $string = preg_replace('/(\s*\{\s*)/',"{",$string);
        $string = preg_replace('/(\s*\;\s*\}\s*)/',"}",$string); //去掉反括号首位的空格和换行，和最后一个;
        $string = preg_replace('/(\s*\;\s*)/',";",$string);
        return $string;
    }

    static function js_compress($js){
        $h1 = 'http://';
        $s1 = '【:??】';    //标识“http://”,避免将其替换成空
        $h2 = 'https://';
        $s2 = '【s:??】';    //标识“https://”
        preg_match_all('#include\("([^"]*)"([^)]*)\);#isU',$js,$arr);
        if(isset($arr[1])){
            foreach ($arr[1] as $k=>$inc){
                $path = "http://www.xxx.com/";          //这里是你自己的域名路径
                $temp = file_get_contents($path.$inc);
                $js = str_replace($arr[0][$k],$temp,$js);
            }
        }

        $js = preg_replace('#function include([^}]*)}#isU','',$js);//include函数体
        $js = preg_replace('#\/\*.*\*\/#isU','',$js);//块注释
        $js = str_replace($h1,$s1,$js);
        $js = str_replace($h2,$s2,$js);
        $js = preg_replace('#\/\/[^\n]*#','',$js);//行注释
        $js = str_replace($s1,$h1,$js);
        $js = str_replace($s2,$h2,$js);
        $js = str_replace("\t","",$js);//tab
        $js = preg_replace('#\s?(=|>=|\?|:|==|\+|\|\||\+=|>|<|\/|\-|,|\()\s?#','$1',$js);//字符前后多余空格
        $js = str_replace("\t","",$js);//tab
        $js = str_replace("\r\n","",$js);//回车
        $js = str_replace("\r","",$js);//换行
        $js = str_replace("\n","",$js);//换行
        $js = trim($js," ");
        return $js;
    }
}
