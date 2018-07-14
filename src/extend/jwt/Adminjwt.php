<?php
/**
 * User: Yirius
 * Date: 2018/6/28
 * Time: 00:30
 */

namespace icesadmin\extend\jwt;


use icesjwt\BeforeValidException;
use icesjwt\ExpiredException;
use icesjwt\Jwt;
use icesjwt\SignatureException;
use traits\controller\Jump;

class Adminjwt
{
    use Jump;

    protected $config = [
        'type' => "HS256",
        'key' => "457E01C781E3CED815D89952",
        'rsakey' => [
            'privatekey' => '',
            'publickey' => '',
        ],
        'notbefore' => 0,
        'expire' => 43200//0标识不过期
    ];

    protected static $algs_can_use = [
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'HS384' => ['hash_hmac', 'SHA384'],
        'RS256' => ['openssl', 'SHA256'],
        'RS384' => ['openssl', 'SHA384'],
        'RS512' => ['openssl', 'SHA512']
    ];

    protected static $instance = null;

    function __construct()
    {
        if($jwt = config("icesadmin.jwt")){
            $this->config = array_merge($this->config, $jwt);
        }
    }

    /**
     * @title instance
     * @description
     * @createtime: 2018/6/28 00:39
     * @return null|static
     */
    public static function instance(){
        if(self::$instance == null){
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @title 设置加密使用的参数
     * @description 设置加密使用的参数
     * @createtime: 2018/7/10 23:40
     * @param string $type 加密使用的类型 true '' HS256,HS512,HS384,RS256,RS384,RS512
     * @return bool|Adminjwt|null
     */
    public static function setEncodeType($type){
        if(empty(self::$algs_can_use[$type])){
            return false;
        }
        self::instance()->config['type'] = $type;
        return self::instance();
    }

    /**
     * @title 设置加密使用的key
     * @description 设置加密使用的key,强制要求必须24位,为了兼容nodejs
     * @createtime: 2018/7/10 23:42
     * @param string $key 加密的key,需要24位 true '' ''
     * @return bool|Adminjwt|null
     */
    public static function setKey($key){
        if(mb_strlen($key) != 24){
            return false;
        }
        self::instance()->config['key'] = $key;
        return self::instance();
    }

    /**
     * @title 设置过期时间
     * @description 设置过期时间
     * @createtime: 2018/7/10 23:43
     * @param int $time 秒钟,如果是0就是永远不过期
     * @return bool|Adminjwt|null
     */
    public static function setExpire($time){
        if(intval($time) <= 0){
            return false;
        }
        self::instance()->config['expire'] = $time;
        return self::instance();
    }

    /**
     * @title 加密成json-web-token
     * @description 加密成json-web-token,base64之后的
     * @createtime: 2018/7/10 23:43
     * @param array $encryptData 需要加密的内容,可以使字符串可以是数组 true '' ''
     * @return string
     */
    public function encodeHmac($encryptData){
        $payload = [
            'payload' => $encryptData
        ];
        /**
         * 设置创建时间
         */
        $payload['iat'] = time();
        /**
         * 如果存在notbefore,就设置
         */
        if(self::instance()->config['notbefore']){
            $payload['nbf'] = $payload['iat'] + self::instance()->config['notbefore'];
        }
        if(self::instance()->config['expire']){
            $payload['exp'] = $payload['iat'] + self::instance()->config['expire'];
        }
        return Jwt::encode($payload, self::instance()->config['key'], self::instance()->config['type']);
    }

    /**
     * @title 对jwt字段进行解析
     * @description 解析jwt字段,得到字符串或者数组,针对后台,所以设置code=1001是登录失败或者已经过期,如果有自己的判断,需要更换或者对1001做出反应
     * @createtime: 2018/7/10 23:45
     * @param string $jwt jwt字符串 true '' ''
     * @param bool $return_array 是否返回数组,false返回object false true true|false
     * @return mixed
     */
    public function decodeHmac($jwt, $return_array = true){
        try{
            if($return_array){
                $result = Jwt::decode($jwt, self::instance()->config['key']);
                return json_decode(json_encode($result->payload), true);
            }else{
                return Jwt::decode($jwt, self::instance()->config['key'])->payload;
            }
        }catch(ExpiredException $err){
            $this->result([], 1001, "很抱歉, 登录状态已过期, 您需要重新登录", "json");
        }catch(SignatureException $err){
            $this->result([], 1001, "很抱歉, 登录签名校验不正确, 您需要重新登录", "json");
        }catch(BeforeValidException $err){
            $this->result([], 1001, "很抱歉, 您需要重新登录", "json");
        }catch(\Exception $err){
            $this->result([], 1001, "很抱歉, 您需要重新登录, 原因未知, 请联系客服人员", "json");
        }
    }

    public function __call($method, $args)
    {
        return call_user_func_array([self::instance(), $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([self::instance(), $method], $args);
    }
}
