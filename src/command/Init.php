<?php
/**
 * User: Yirius
 * Date: 2018/1/7
 * Time: 22:20
 */

namespace icesadmin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Init extends Command
{
    protected function configure()
    {
        $this->setName('ices:init')->setDescription('this command is to create database and add the [icesadmin.php] and [captcha.php] to config path');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->comment("set database names");
        try{
            db()->query('SET NAMES utf8;');
            db()->query('SET FOREIGN_KEY_CHECKS = 0;');
        }catch(\Exception $err){
            $output->error("Your config/database.php not config or config is wrong, pls config the connection");
            exit;
        }
        //创建用户组
        $this->createGroup($output);
        //创建用户对应组关系
        $this->createGroupAccess($output);
        //创建用户表
        $this->createMember($output);
        //创建菜单
        $this->createMenu($output);
        //创建菜单
        $this->createRule($output);
        //完成
        db()->query('SET FOREIGN_KEY_CHECKS = 1;');
        $output->comment("create successful");
    }

    protected function createGroup(Output $output){
        $output->comment("create auth_group");
        db()->query('DROP TABLE IF EXISTS `ices_admin_group`;');
        db()->query("CREATE TABLE `ices_admin_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `rules` longtext,
  `createtime` datetime DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `deletetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;");
        $output->comment("insert admin_group default rows");
        db()->query("INSERT INTO `ices_admin_group` VALUES ('1', '超级管理员', '1', '1,2,3,4,5,6,7,8', null, null, null), ('2', '系统管理员', '1', '1,2', null, null, null), ('3', '其他管理员', '1', '1', null, '2018-07-01 16:26:13', '2018-07-01 16:26:13'), ('5', '系统管理员', '0', '1,2', null, '2018-07-01 16:27:52', '2018-07-01 16:27:52');");
    }

    protected function createGroupAccess(Output $output){
        $output->comment("create admin_group end, start create group_access");
        db()->query('DROP TABLE IF EXISTS `ices_admin_group_access`;');
        db()->query("CREATE TABLE `ices_admin_group_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  `type` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`,`type`) USING BTREE,
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;");
        $output->comment("insert group_access default rows");
        db()->query("INSERT INTO `ices_admin_group_access` VALUES ('1', '1', '1', '0');");
    }

    protected function createMember(Output $output){
        $output->comment("create group_access end, start create admin_member");
        db()->query('DROP TABLE IF EXISTS `ices_admin_member`;');
        db()->query("CREATE TABLE `ices_admin_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `realname` varchar(100) DEFAULT NULL,
  `password` varchar(41) NOT NULL,
  `salt` varchar(10) DEFAULT '',
  `createtime` datetime DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `deletetime` datetime DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `theme` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;");
        $output->comment("insert admin_member default rows");
        db()->query("INSERT INTO `ices_admin_member` VALUES ('1', 'admin', '11111111111', '超级管理员', '7c1d9ac16bced96952624e0aaaa038d882b36245', '478543', '2018-06-28 15:27:50', '2018-06-29 17:07:09', null, '1', null);");
    }

    protected function createMenu(Output $output){
        $output->comment("create admin_member end, start create admin_menu");
        db()->query('DROP TABLE IF EXISTS `ices_admin_menu`;');
        db()->query("CREATE TABLE `ices_admin_menu` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `pid` smallint(6) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `title` varchar(20) NOT NULL COMMENT '菜单名称',
  `jump` varchar(50) DEFAULT NULL COMMENT '链接',
  `icon` varchar(50) DEFAULT NULL COMMENT '字体图标',
  `sort` smallint(6) DEFAULT NULL,
  `createtime` datetime DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `deletetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COMMENT='菜单表';");
        $output->comment("insert admin_menu default rows");
        db()->query("INSERT INTO `ices_admin_menu` VALUES ('1', '0', 'user', '用户设置', 'user', 'layui-icon-user', '1', null, '2018-07-14 15:07:13', null), ('2', '1', 'administrators-list', '后台管理员', './admin/list', null, '2', null, null, null), ('3', '1', 'administrators-rule', '角色管理', './admin/role', null, '3', null, null, null), ('4', '1', 'user-rule', '规则设置', '../../icesadminview/rule', null, '4', null, null, null), ('5', '1', 'menu', '菜单设置', '../../icesadminview/menu', null, '5', null, '2018-07-02 18:45:11', null), ('32', '0', 'showtest', '后台演示', 'showtest', 'layui-icon-util', '2', '2018-07-11 13:08:06', '2018-07-11 13:08:06', null), ('33', '32', 'showtest-form', 'Form演示', '../../manage/view/form', 'layui-icon-form', '3', '2018-07-11 13:12:02', '2018-07-11 13:12:02', null), ('34', '32', 'showtest-table', 'Table演示', '../../manage/view/table', 'layui-icon-table', '4', '2018-07-11 13:12:35', '2018-07-11 13:12:35', null);");
    }

    protected function createRule(Output $output){
        $output->comment("create admin_menu end, start create admin_rule");
        db()->query('DROP TABLE IF EXISTS `ices_admin_rule`;');
        db()->query("CREATE TABLE `ices_admin_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',
  `mid` int(8) NOT NULL,
  `type` tinyint(2) NOT NULL DEFAULT '1',
  `createtime` datetime DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `deletetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;");
        $output->comment("insert admin_menu default rows");
        db()->query("INSERT INTO `ices_admin_rule` VALUES ('1', 'user', '后台用户设置', '1', '', '0', '1', null, '2018-07-11 13:04:10', null), ('2', './admin/list', '后台管理员设置', '1', '', '1', '1', null, null, null), ('3', './admin/role', '角色设置', '1', '', '1', '1', null, null, null), ('4', '../../icesadminview/rule', '规则设置', '1', '', '1', '1', null, null, null), ('5', '../../icesadminview/menu', '菜单设置', '1', '', '1', '1', null, '2018-07-02 18:33:53', null), ('6', 'showtest', '后台演示', '1', '', '0', '1', '2018-07-11 13:08:26', '2018-07-11 13:08:26', null), ('7', '../../manage/view/form', 'Form演示', '1', '', '6', '1', '2018-07-11 13:12:59', '2018-07-11 13:12:59', null), ('8', '../../manage/view/table', 'Table演示', '1', '', '6', '1', '2018-07-12 12:21:20', '2018-07-12 12:21:20', null);");
    }
}
