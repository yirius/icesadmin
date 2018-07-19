<?php
/**
 * User: Yirius
 * Date: 2018/6/28
 * Time: 14:58
 */

namespace icesadmin\extend\auth;


use think\Db;

class Auth
{
    protected $config = [
        'auth_group' => 'ices_admin_group', // 用户组数据表名
        'auth_group_access' => 'ices_admin_group_access', // 用户-用户组关系表
        'auth_rule' => 'ices_admin_rule', // 权限规则表
        'auth_menu' => 'ices_admin_menu',
        'auth_user' => [
            'ices_admin_member'
        ],
        'access_type' => 0,
        'login_field' => "username|phone"
    ];

    protected static $instance = null;

    /**
     * Auth constructor.
     */
    public function __construct()
    {
        //可设置配置项 auth, 此配置项为数组。
        if ($auth = config("icesadmin.auth")) {
            $this->config = array_merge($this->config, $auth);
        }
        // 初始化request
        $this->request = app("request");
    }

    /**
     * @title 自动初始化方法
     * @description
     * @createtime: 2018/7/10 20:27
     * @param array $options
     * @return null|static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * @title check
     * @description
     * @createtime: 2018/6/28 15:36
     * @param string $rules 判断规则,可以使字符串可以是数组,字符串用【,】分割也可以,判断的标准 true '' ''
     * @param int $uid 需要判断的用户id,判断这个用户是不是在规则的权限里
     * @param int $type 这个type是用来标识rule表内不同规则的一个区分,通常默认为1即可 false 1 ''
     * @param int $access_type 该参数用来判断去读取哪一个member【用户表】,适合拥有多个用户体系的 false 0 ''
     * @param string $mode 检查的模式,通常保持url即可 false url ''
     * @param string $relation 关联规则的模式,是或还是与 or or|and
     * @return bool
     */
    public function check($rules, $uid, $type = 1, $access_type = 0, $mode = 'url', $relation = 'or')
    {
        //对access_type初始化
        $access_type = $access_type != 0 ? : $this->config['access_type'];
        // 获取用户需要验证的所有有效规则列表
        $authList = $this->getAuthList($uid, $type, $access_type);
        if (is_string($rules)) {
            $rules = strtolower($rules);
            if (strpos($rules, ',') !== false) {
                $rules = explode(',', $rules);
            } else {
                $rules = [$rules];
            }
        }
        $list = []; //保存验证通过的规则名
        $REQUEST = [];
        if ('url' == $mode) {
            $REQUEST = unserialize(strtolower(serialize($this->request->param())));
        }
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ('url' == $mode && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $rules) && $intersect == $param) {
                    //如果节点相符且url参数满足
                    $list[] = $auth;
                }
            } else {
                if (in_array($auth, $rules)) {
                    $list[] = $auth;
                }
            }
        }
        if ('or' == $relation && !empty($list)) {
            return true;
        }
        $diff = array_diff($rules, $list);
        if ('and' == $relation && empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * @title getAuthList
     * @description
     * @createtime: 2018/7/10 21:20
     * @param int $uid 用户的id true '' ''
     * @param int $type 类型 true '' ''
     * @param int $access_type 这个用户对应的表,是总后台还是各个子后台 false 0 ''
     * @return array
     */
    public function getAuthList($uid, $type, $access_type = 0)
    {
        //对access_type初始化
        $access_type = $access_type != 0 ? : $this->config['access_type'];

        static $_authList = []; //保存用户验证通过的权限列表
        $typejoin = is_array($type)?implode(',', $type):$type;
        if (isset($_authList[$uid . $typejoin . "-" . $access_type])) {
            return $_authList[$uid . $typejoin . "-" . $access_type];
        }
        //读取用户所属用户组
        $groups = $this->getGroups($uid, $access_type);
        $ids = []; //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        //如果不存在任何一个属性, 那就制空然后返回
        if (empty($ids)) {
            $_authList[$uid . $typejoin . "-" . $access_type] = [];
            return [];
        }
        //构造查询条件
        $where = [
            'id' => $ids,
            'type' => is_array($type)?$type:explode(",", $type),
            'status' => 1
        ];
        //查询到所有规则可用的
        $rules = Db::name($this->config['auth_rule'])->where($where)->field('condition,name')->select();
        $authList = []; //循环规则，判断结果。
        $user = $this->getUserInfo($uid, $access_type); //获取用户信息,一维数组, 指定用户表内的
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) {
                //根据condition进行验证
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command); //debug
                $condition = null;
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['name']);
            }
        }
        //保存, 然后返回
        $_authList[$uid . $typejoin . "-" . $access_type] = $authList;
        return array_unique($authList);
    }

    /**
     * @title 获取用户用户组
     * @description 根据用户id获取用户组,返回值为数组
     * @createtime: 2018/7/10 21:22
     * @param int $uid
     * @param int $access_type 这个用户对应的表,是总后台还是各个子后台 false 0 ''
     * @return mixed
     */
    public function getGroups($uid, $access_type = 0)
    {
        //对access_type初始化
        $access_type = $access_type != 0 ? : $this->config['access_type'];

        static $groups = [];
        if (isset($groups[$uid . "-" . $access_type])) {
            return $groups[$uid . "-" . $access_type];
        }
        // 转换表名
        $auth_group_access = $this->config['auth_group_access'];
        $auth_group = $this->config['auth_group'];
        // 执行查询
        $user_groups = Db::view($auth_group_access, 'uid,group_id')
            ->view($auth_group, 'title,rules', "{$auth_group_access}.group_id={$auth_group}.id", 'LEFT')
            ->where($auth_group_access . ".uid", $uid)
            ->where($auth_group . ".status", 1)
            ->where($auth_group_access . ".type", $access_type)
            ->select();
        $groups[$uid . "-" . $access_type] = $user_groups ?: [];
        return $groups[$uid . "-" . $access_type];
    }

    /**
     * @title 根据指定的字段查询到用户的信息
     * @description 根据指定的字段查询到用户的信息
     * @createtime: 2018/6/28 15:45
     * @param string $username 用户的username true '' ''
     * @param int $access_type 这个用户对应的表,是总后台还是各个子后台 false 0 ''
     * @return mixed
     */
    public function checkUserInfo($username, $access_type = 0){
        //对access_type初始化
        $access_type = $access_type != 0 ? : $this->config['access_type'];

        static $uinfo = [];
        $user = Db::name($this->config['auth_user'][$access_type]);

        if (!isset($uinfo[$username . "-" . $access_type])) {
            $uinfo[$username . "-" . $access_type] = $user->where($this->config['login_field'], $username)->find();
        }
        return $uinfo[$username . "-" . $access_type];
    }

    /**
     * @title 获得用户资料,根据自己的情况读取数据库
     * @description 获得用户资料,根据自己的情况读取数据库
     * @createtime: 2018/6/28 15:24
     * @param int $uid 用户id true '' ''
     * @param int $access_type 这个用户对应的表,是总后台还是各个子后台 false 0 ''
     * @return mixed
     */
    public function getUserInfo($uid, $access_type = 0)
    {
        //对access_type初始化
        $access_type = $access_type != 0 ? : $this->config['access_type'];

        static $userinfo = [];
        $user = Db::name($this->config['auth_user'][$access_type]);
        // 获取用户表主键
        $_pk = is_string($user->getPk()) ? $user->getPk() : 'id';
        if (!isset($userinfo[$uid . "-" . $access_type])) {
            $userinfo[$uid . "-" . $access_type] = $user->where($_pk, $uid)->find();
        }
        return $userinfo[$uid . "-" . $access_type];
    }

    /**
     * @title 获取配置参数, 方便其他地方调用
     * @description
     * @createtime: 2018/6/28 22:55
     * @param null $field
     * @return array|null
     */
    public function getConfig($field = null){
        if(is_null($field)){
            return $this->config;
        }else{
            return isset($this->config[$field])?$this->config[$field]:null;
        }
    }

    /**
     * @title 获取权限菜单
     * @description 获取到用户的权限菜单,按照tree格式返回
     * @createtime: 2018/7/10 21:25
     * @param int $uid
     * @param int $access_type
     * @return array
     */
    public function getAuthMenu($uid, $access_type = 0)
    {
        $list = $this->getAuthList($uid, 1, $access_type);
        $menu = Db::name($this->config['auth_menu'])->order('sort', 'desc')->select();
        //将菜单id作为数组key
        $keys = array_column($menu, 'id');
        $menu = array_combine($keys, $menu);
        //返回有权限的菜单
        $menuList = [];
        $pids = [];
        foreach ($menu as $key => $value) {
            $jump = trim(strtolower($value['jump']), DS);
            if (in_array($jump, $list)) {
                if ($value['pid'] != 0) {
                    if (!in_array($value['pid'], $pids)) {
                        $menuList[] = $menu[$value['pid']];
                        $pids[] = $value['pid'];
                    }
                }
                $menuList[] = $value;
            }
        }
        $menuinfo = AuthData::channelLevel($menuList);
        $menuinfo = array_values($menuinfo);
        foreach($menuinfo as $i => $v){
            $menuinfo[$i]['list'] = array_values($menuinfo[$i]['list']);
            foreach($menuinfo[$i]['list'] as $j => $val){
                $menuinfo[$i]['list'][$j]['list'] = array_values($menuinfo[$i]['list'][$j]['list']);
            }
        }
        return $menuinfo;
    }
}
