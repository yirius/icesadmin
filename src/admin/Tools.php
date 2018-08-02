<?php
/**
 * User: Yirius
 * Date: 2018/7/13
 * Time: 17:28
 */

namespace icesadmin\admin;


use think\facade\Env;
use think\facade\Response;
use think\File;

class Tools
{
    /**
     * 给ueditor返回的参数
     * @type array
     */
    protected $config = [
        'imageActionName' => 'uploadimage',
        "imageFieldName" => 'upfile',
        "imageMaxSize" => 2048000,
        "imageAllowFiles" => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
        "imageCompressEnable" => 1,
        "imageCompressBorder" => 1600,
        "imageInsertAlign" => "none",
        "imageUrlPrefix" => '',
        //涂鸦相关
        "scrawlActionName" => 'uploadscrawl',
        "scrawlFieldName" => 'upfile',
        "scrawlMaxSize" => 2048000,
        "scrawlUrlPrefix" => '',
        "scrawlInsertAlign" => "none",
        //视频相关
        "videoActionName" => 'uploadvideo',
        "videoFieldName" => 'upfile',
        "videoPathFormat" => '/ueditor/php/upload/video/{yyyy}{mm}{dd}/{time}{rand:6}',
        "videoUrlPrefix" => '',
        "videoMaxSize" => 102400000,
        "videoAllowFiles" => [".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"],
        //上传文件
        "fileActionName" => 'uploadfile',
        "fileFieldName" => 'upfile',
        "filePathFormat" => '/ueditor/php/upload/file/{yyyy}{mm}{dd}/{time}{rand:6}',
        "fileUrlPrefix" => '',
        "fileMaxSize" => 51200000,
        "fileAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"]
    ];

    protected $images = [
        'water' => false,
        'validate' => [
            'size' => 1024*1024,
            'ext' => "jpg,png,gif,jpeg,do,bmp"
        ]
    ];

    protected $files = [
        'size' => 1024*1024,
        'ext' => "png,jpg,jpeg,gif,bmp,flv,swf,mkv,avi,rm,rmvb,mpeg,mpg,ogg,ogv,mov,wmv,mp4,webm,mp3,wav,mid,rar,zip,tar,gz,7z,bz2,cab,iso,doc,docx,xls,xlsx,ppt,pptx,pdf,txt,md,xml"
    ];

    protected $imageError = null;

    protected $isUeditor = false;

    public function ueditor($action)
    {
        $this->isUeditor = true;
        $response = null;
        switch($action){
            case 'config':
                $response = Response::create($this->config, 'json');
                break;
                /* 上传图片 */
            case 'uploadimage':
                $response = $this->images();
                break;
            /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $response = $this->uploads();
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $response = $this->imagesbase64();
                break;
            /* 列出图片 */
            case 'listimage':
                $result = include("action_list.php");
                break;
            /* 列出文件 */
//            case 'listfile':
//                $result = include("action_list.php");
//                break;
//
//            /* 抓取远程文件 */
//            case 'catchimage':
//                $result = include("action_crawler.php");
//                break;

            default:
                $response = "非法行为";
                break;
        }
        return $response;
    }

    /**
     * @title 图片上传接口
     * @description 图片上传接口
     * @createtime: 2018/7/13 18:04
     * @return \think\response
     */
    public function images(){
        $result = [];
        $this->images = array_merge($this->images, config("icesadmin.upload.images"));
        /**
         * 循环计算一下
         */
        foreach($_FILES as $key => $temp){
            $isEmpty = $this->checkFileEmpty($temp);
            if($isEmpty === false){
                $imageInfo = $this->_upload($temp, $this->images['validate']);
                if($imageInfo === false){
                    return Response::create($this->imageError, "json");
                }else{
                    if($this->isUeditor){
                        $result = $imageInfo;
                    }else{
                        $result[$key] = $imageInfo;
                    }
                }
            }else{
                if($this->isUeditor){
                    $result = $isEmpty;
                }else{
                    $result[$key] = $isEmpty;
                }
            }
        }
        return Response::create($result, "json");
    }

    /**
     * @title 上传base64格式图片
     * @description
     * @createtime: 2018/7/13 18:32
     * @return \think\response
     */
    public function imagesbase64(){
        $result = [];
        $base64Data = input('post.upfile');
        $img = base64_decode($base64Data);
        $fileState = "SUCCESS";$fileUrl = '';$fileTitle = "";$fileType = "png";$fileSize = strlen($img);
        if ($fileSize > $this->images['validate']['size']) {
            $fileState = "文件上传大小不符!";
        }else{
            $dir = md5($img);
            $filename = substr($dir, 2);
            $dir = substr($dir, 0, 2);
            $dirname = Env::get("root_path") . '/public/uploads/' . $dir;
            if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                $fileState = "创建目录失败!";
            }else{
                if (!is_writeable($dirname)) {
                    $fileState = "目录不可写!";
                }else{
                    if (!(file_put_contents($dirname . "/" . $filename . ".png", $img)
                        && file_exists($dirname . "/" . $filename . ".png"))) { //移动失败
                        $fileState = "目录不可写!";
                    } else { //移动成功
                        $fileUrl = '/uploads/' . $dir . "/" . $filename . "." . $fileType;
                    }
                }
            }
        }
        return Response::create([
            "state" => $fileState,
            "url" =>$fileUrl,
            "title" => $fileTitle,
            "original" => $fileTitle,
            "type" => "png",
            "size" => $fileSize
        ], "json");
    }

    /**
     * @title 检查指定文件的md5在服务器是否存在
     * @description
     * @createtime: 2018/7/13 17:51
     * @param $temp
     * @return bool|string
     */
    protected function checkFileEmpty($temp){
        if(!empty(pathinfo($temp['name'])['extension'])){
            $ext = pathinfo($temp['name'])['extension'];
            $dir = md5_file($temp['tmp_name']);
            $filename = substr($dir, 2);
            $dir = substr($dir, 0, 2);
            //如果相同文件存在,不在二次上传,直接返回路径
            if(file_exists(Env::get("root_path") . '/public/uploads/' . $dir . "/" . $filename . "." . $ext)){
                if($this->isUeditor){
                    return [
                        "state" => "SUCCESS",
                        "url" => '/uploads/' . $dir . "/" . $filename . "." . $ext,
                        "title" => $temp['name'],
                        "original" => $temp['name'],
                        "type" => $ext,
                        "size" => $temp['size']
                    ];
                }else{
                    return '/uploads/' . $dir . "/" . $filename . "." . $ext;
                }
            }
        }
        return false;
    }

    /**
     * @title 打水印
     * @description
     * @createtime: 2018/7/13 17:57
     * @param $path
     */
    protected function _water($path){
        $image = \think\Image::open($path);
        $thumbName = null;
        $water = $this->images['water'];
        if(!is_array($water)){
            $water = [$water];
        }
        $image->water($water[0], !isset($water[1])?3:$water[0], !isset($water[2])?20:$water[2])->save($path);
    }

    public function uploads(){
        $result = [];
        $this->files = array_merge($this->files, config("icesadmin.upload.files"));
        /**
         * 循环计算一下
         */
        foreach($_FILES as $key => $temp){
            $isEmpty = $this->checkFileEmpty($temp);
            if($isEmpty === false){
                $imageInfo = $this->_upload($temp, $this->files, false);
                if($imageInfo === false){
                    return Response::create($this->imageError, "json");
                }else{
                    if($this->isUeditor){
                        $result = $imageInfo;
                    }else{
                        $result[$key] = $imageInfo;
                    }
                }
            }else{
                if($this->isUeditor){
                    $result = $isEmpty;
                }else{
                    $result[$key] = $isEmpty;
                }
            }
        }
        return Response::create($result, "json");
    }

    /**
     * @title 上传业内方法
     * @description
     * @createtime: 2018/7/13 18:37
     * @param $temp
     * @param $validate
     * @param bool $isImage
     * @return array|bool|string
     */
    protected function _upload($temp, $validate, $isImage = true){
        $file = (new File($temp['tmp_name']))->setUploadInfo($temp);
        //先记录一下上传地址
        $uploadPath = Env::get("root_path") . '/public/uploads/';
        $info = $file->validate($validate)->rule("md5")->move($uploadPath);
        if($info){
            if($isImage){
                if($this->images['water'] !== false){
                    $this->_water($uploadPath . $info->getSaveName());
                }
            }
            if($this->isUeditor){
                return [
                    "state" => "SUCCESS",
                    "url" => '/uploads/' . $info->getSaveName(),
                    "title" => $temp['name'],
                    "original" => $temp['name'],
                    "type" => $info->getExtension(),
                    "size" => $info->getSize()
                ];
            }else{
                return '/uploads/' . $info->getSaveName();
            }
        }else{
            // 上传失败获取错误信息
            if($this->isUeditor){
                $this->imageError = [
                    "state" => $file->getError(),
                    "url" => '',
                    "title" => $temp['name'],
                    "original" => $temp['name'],
                    "type" => '',
                    "size" => $temp['size']
                ];
            }else{
                $this->imageError = [
                    'code' => 0,
                    'msg' => $file->getError()
                ];
            }
            return false;
        }
    }
}
