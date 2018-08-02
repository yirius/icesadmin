/**

 @Name：全局配置
 @Author：贤心
 @Site：http://www.layui.com/admin/
 @License：LPPL（layui付费产品协议）

 */

layui.define(['laytpl', 'layer', 'element', 'util'], function (exports) {
    exports('setter', {
        container: 'LAY_app' //容器ID
        , base: layui.cache.base //记录layuiAdmin文件夹所在路径
        , views: layui.cache.base + "views/"//layui.cache.base + "views/" //视图所在目录
        , entry: '../../source/search/tradition' //默认视图文件名
        , engine: '.html' //视图文件后缀名
        , pageTabs: false //是否开启页面选项卡功能。单页版不推荐开启

        , name: '契约精神'
        , tableName: 'icesAdmin' //本地存储表名
        , MOD_NAME: 'admin' //模块事件名

        , debug: false //是否开启调试模式。如开启，接口异常时会抛出异常 URL 等信息

        , interceptor: true //是否开启未登入拦截

        //自定义请求字段
        , request: {
            tokenName: 'access_token' //自动携带 token 的字段名。可设置 false 不携带。
        }

        //自定义响应字段
        , response: {
            statusName: 'code' //数据状态的字段名称
            , statusCode: {
                ok: 1 //数据状态一切正常的状态码
                , logout: 1001 //登录状态失效的状态码
            }
            , msgName: 'msg' //状态信息的字段名称
            , dataName: 'data' //数据详情的字段名称
            , countName: 'count'
        }

        //独立页面路由，可随意添加（无需写参数）
        , indPage: [
            'user/login' //登入页
            , 'user/reg' //注册页
            , 'user/forget' //找回密码
            , '/template/tips/test' //独立页的一个测试 demo
        ]

        //扩展的第三方模块
        , extend: [
            'echarts', //echarts 核心包
            'echartsTheme' //echarts 主题
        ]

        //主题配置
        , theme: {
            //内置主题配色方案
            color: [
                {"main": "#20222A", "selected": "#62a8ea", "logo": "#62a8ea", "header": "#62a8ea", "alias": "primary"},
                {"main": "#20222A", "selected": "#8d6658", "logo": "#8d6658", "header": "#8d6658", "alias": "brown"},
                {"main": "#20222A", "selected": "#57c7d4", "logo": "#57c7d4", "header": "#57c7d4", "alias": "cyan"},
                {"main": "#20222A", "selected": "#46be8a", "logo": "#46be8a", "header": "#46be8a", "alias": "green"},
                {"main": "#20222A", "selected": "#757575", "logo": "#757575", "header": "#757575", "alias": "grey"},
                {"main": "#20222A", "selected": "#677ae4", "logo": "#677ae4", "header": "#677ae4", "alias": "indigo"},
                {"main": "#20222A", "selected": "#f2a654", "logo": "#f2a654", "header": "#f2a654", "alias": "orange"},
                {"main": "#20222A", "selected": "#f96197", "logo": "#f96197", "header": "#f96197", "alias": "pink"},
                {"main": "#20222A", "selected": "#926dde", "logo": "#926dde", "header": "#926dde", "alias": "purple"},
                {"main": "#20222A", "selected": "#f96868", "logo": "#f96868", "header": "#f96868", "alias": "red"},
                {"main": "#20222A", "selected": "#3aa99e", "logo": "#3aa99e", "header": "#3aa99e", "alias": "teal"},
                {"main": "#20222A", "selected": "#f7da64", "logo": "#f7da64", "header": "#f7da64", "alias": "yellow"}
            ]

            //初始的颜色索引，对应上面的配色方案数组索引
            //如果本地已经有主题色记录，则以本地记录为优先，除非请求本地数据（localStorage）
            , initColorIndex: 0
        }
    });
});
