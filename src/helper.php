<?php
/**
 * User: Yirius
 * Date: 2018/6/24
 * Time: 23:48
 */

use think\Console;

defined("DS") or define("DS", DIRECTORY_SEPARATOR);

defined("ices_root") or define("ices_root", __DIR__);

function icesRandom($len = 6, $format = 'NUMBER')
{
    $format = strtoupper($format);
    switch ($format) {
        case 'ALL':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
            break;
        case 'CHAR':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
            break;
        case 'NUMBER':
            $chars = '0123456789';
            break;
        default :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
            break;
    }
    $password = "";
    while (strlen($password) < $len)
        $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
    return $password;
}

Route::alias('icesadmin', "\\icesadmin\\admin\\Index", ['deny_ext' => 'php|.htacess']);
Route::alias('icesuser', "\\icesadmin\\admin\\User", ['deny_ext' => 'php|.htacess']);
Route::alias('icesrole', "\\icesadmin\\admin\\Role", ['deny_ext' => 'php|.htacess']);
Route::alias('icesrule', "\\icesadmin\\admin\\Rule", ['deny_ext' => 'php|.htacess']);
Route::alias('icesmenu', "\\icesadmin\\admin\\Menu", ['deny_ext' => 'php|.htacess']);
Route::alias('icestools', "\\icesadmin\\admin\\Tools", ['deny_ext' => 'php|.htacess']);
Route::any('icesueditor', "\\icesadmin\\admin\\Tools@ueditor", ['deny_ext' => 'php|.htacess']);
Route::alias('icesadminview', "\\icesadmin\\admin\\AdminView", ['deny_ext' => 'php|.htacess']);

//加入以下console
Console::addDefaultCommands([
    "icesadmin\\command\\Init",
    "icesadmin\\command\\Assets"
]);
