<?php
/**
 * User: Yirius
 * Date: 2018/6/24
 * Time: 23:47
 */
return [
    'config' => [
        'home_path' => "icesadmin/welcome",
        'view_assets' => "/icesadmin/assets"
    ],
    'menu' => [
        'spread' => 0,//如果是false,就是默认全不展开,如果是0及以上,就是展开着一个
        'userinfo' => []//需要返回的用户信息字段
    ],
    'jwt' => [
        'type' => "",//HS256,HS512,HS384,RS256,RS384,RS512
        'key' => "",
        'rsakey' => [
            'privatekey' => '',
            'publickey' => '',
        ],
        'time' => 86400
    ]
];
