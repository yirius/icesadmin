<?php
/**
 * User: Yirius
 * Date: 2018/7/1
 * Time: 15:04
 */

namespace icesadmin\extend\model;


use think\facade\Response;
use think\facade\Validate;
use think\model\concern\SoftDelete;

class Model extends \think\Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    /**
     * 私有方法, 获取到所有提交过来的参数
     * @description
     * @createtime: 2018/3/22 16:07
     * @param $defaultOrder
     * @param null $defaultSearchField
     * @return array
     */
    protected static function _checkTableParam($defaultOrder, $defaultSearchField = null){
        $config = [];
        /**
         * 首先判断一下order参数, 限定为desc和asc, 防止sql注入
         */
        $config['order'] = input("param.order", "desc");
        if(!in_array($config['order'], ['desc', 'asc'])){
            $config['order'] = "desc";
        }
        /**
         * 判断page和limit
         */
        $config['limit'] = intval(input("param.limit", 10));
        $config['page'] = intval(input("param.page", 1));
        /**
         * 判断sort是否存在, 并且是否是表内字段
         */
        $config['sort'] = input("param.sort", $defaultOrder);
        if(!in_array($config['sort'], self::getTableFields())){
            $config['sort'] = null;
        }
        //返回数据
        return $config;
    }

    /**
     * @title 基础方法,直接生成一个列表格式
     * @description model的基础方法之一,可以直接生成一个不含msg的table需要内容,如果传递了with条件,会自动把当前表别名为a
     * @createtime: 2018/7/11 00:01
     * @param string $field 需要查询的字段 false '*' ''
     * @param array $where 查询的条件 false [] ''
     * @param array $with 关联的表,如果传递了这个参数,当前表会alias为a false [] ''
     * @param null $eachFuns 需要循环运行的方法 false null ''
     * @param array $defaultConfig 其他的一些预定义参数,defaultOrder/orderPrefix/group/cacheName/cacheSeconds false [] ''
     * @param int $trashed 是否显示放进回收站的内容,1是全部显示,2是只显示删除内容 false '0' 0|1|2
     * @return array
     */
    public static function icesList($field = "*", $where = [], $with = [], $eachFuns = null, $defaultConfig = [], $trashed = 0){
        /**
         * 处理一下获取到的基础配置
         */
        $defaultConfig = array_merge([
            "defaultOrder" => "id",
            "orderPrefix" => "a",
            "group" => null,
            "cacheName" => null,
            "cacheSeconds" => 0
        ], $defaultConfig);
        /**
         * 基类方法获取到配置
         */
        $config = self::_checkTableParam($defaultConfig['defaultOrder']);

        /**
         * 判断一下sort
         */
        if(!empty($with)){
            $config['sort'] = $defaultConfig['orderPrefix'] . "." . $config['sort'];
        }
        /**
         * 获取到列表
         */
        $_this = $trashed == 0?
            self::field($field)
            :(
                $trashed == 2?
                self::onlyTrashed()->field($field)
                :self::withTrashed()->field($field)
            );
        $select = $_this
            ->alias(empty($with)?null:'a')
            ->join($with)
            ->where($where)
            ->order($config['sort']." ".$config['order'])
            ->group($defaultConfig['group'])
            ->page($config['page'], $config['limit'])
            ->cache(
                empty($defaultConfig['cacheName'])?false:$defaultConfig['cacheName'],
                empty($defaultConfig['cacheSeconds'])?null:$defaultConfig['cacheSeconds']
            )
            ->select();

        /**
         * 去掉其他配置, 获取总数
         */
        $_this = $trashed == 0?
            self::where($where)
            :(
            $trashed == 2?
                self::onlyTrashed()->where($where)
                :self::withTrashed()->where($where)
            );
        $count = $_this
            ->alias(empty($with)?null:'a')
            ->join($with)
            ->order($config['sort']." ".$config['order'])
            ->group($defaultConfig['group'])
            ->cache(
                empty($defaultConfig['cacheName'])?false:$defaultConfig['cacheName'],
                empty($defaultConfig['cacheSeconds'])?null:$defaultConfig['cacheSeconds']
            )
            ->count();

        /**
         * 如果有循环就写出来
         */
        if(!empty($eachFuns)){
            $select->each($eachFuns);
        }

        return [
            "count" => $count,
            "data" => $select->toArray(),
            'code' => 1
        ];
    }

    /**
     * @title 删除的自定义操作
     * @description 在用户验证了密码之后,进行快捷删除的方法
     * @createtime: 2018/7/1 15:45
     * @param array $ids 需要删除的id数组 true [] ''
     * @param array $notDelete 不能删除的id数组 false [] ''
     * @param string $pk 主键对应 false id [] ''
     * @param bool $force_delete 是否是真实删除,默认是彻底删除 false true true|false
     * @return array 用户无法删除的或者删除失败的信息
     */
    public static function icesDelete(array $ids, $notDelete = [], $pk = "id", $force_delete = true){
        //记录下来所有删除失败的数据
        $errorDelete = [];
        foreach($ids as $i => $v){
            $del_id = is_array($v)?$v[$pk]:$v;
            if(!in_array($del_id, $notDelete)){
                if($force_delete){
                    $flag = self::where($pk, $del_id)->delete();
                }else{
                    $flag = self::destroy($del_id, $force_delete);
                }
                if(!$flag)
                    $errorDelete[] = $del_id;
            }
        }
        //如果删除失败数据为空,那就显示删除成功
        return $errorDelete;
    }

    /**
     * @title 自动保存和修改的接口
     * @description 通过传入或者不传入where条件,来对数据进行快捷的新增和更新
     * @createtime: 2018/7/2 18:34
     * @param array $add 需要添加或者修改的内容的数组,通常是post内容 true [] ''
     * @param array $where 更新的条件 false [] ''
     * @param Validate $valiate Validate类的实例化 false null ''
     * @return bool|number 如果是更新就是返回成功与失败,如果是新增就是返回自增完了的主键
     */
    public static function icesSave(array $add, $where = [], Validate $valiate = null){
        //如果验证器存在的话,就验证一下数据
        if(!is_null($valiate)){
            if(!$valiate->check($add)){
                Response::create([
                    'msg' => $valiate->getError(),
                    'code' => 0
                ], 'json')->send();
                exit;
            }
        }
        $_this = new static;
        //自动保存
        if(!empty($where)){
            //如果存在信息,就是更新操作
            $result = $_this->save($add, $where);
            if($result){
                return true;
            }else{
                if($error = $_this->getError()){
                    Response::create([
                        'msg' => $error,
                        'code' => 0
                    ], 'json')->send();
                    exit;
                }else{
                    Response::create([
                        'msg' => "您尚未进行任何修改,提交无效",
                        'code' => 0
                    ], 'json')->send();
                    exit;
                }
            }
        }else{
            //否则就是新增操作
            $info = self::create($add);
            return $info->getLastInsID();
        }
    }
}
