/** layuiAdmin.pro-v1.0.0 LPPL License By http://www.layui.com/admin/ */
 ;layui.define(["admin","form","layer","view","table","jquery"],function(e){var t=layui.admin,i=layui.form,l=layui.layer,n=layui.view,a=layui.table,u=layui.jquery,r={copyright:"© 2018 天津契约精神汽车科技有限公司",layout:{topmenu:"./company/admin/topmenu",menu:"./company/admin/menu",userinfo:"./company/admin/userinfo",message:"./company/admin/message.html"},login:{title:"契约精神",span:"契约精神汽车科技",path:"./company/admin/login",captcha:"./captcha.html",sucMsg:"登录成功~"},logout:"./company/admin/logout",admin:{user:{list:"./icesuser/lists",del:"./icesuser/delete",form:"./icesuser/update",role:"./icesuser/role",pwd:"./icesuser/editpwd"},role:{list:"./icesrole/lists",del:"./icesrole/delete",form:"./icesrole/update",rule:"./icesrole/rule",rule_tree:"./icesrole/rule_tree"}}};r.funcs={del:function(e,i){e=u.extend({},{data:{},url:null,table:null},e),l.prompt({formType:1,title:"敏感操作，请验证登录密码"},function(n,u){l.close(u),l.confirm("确定删除吗？",function(u){t.req({method:"DELETE",url:e.url,data:{password:n,deldata:e.data},done:function(t){"function"==typeof i?i(t):(e.table&&a.reload(e.table),l.msg(t.msg||"已删除"))}})})})},add:function(e,r,d){e=u.extend({},{view:null,title:"新增界面",btn:null,url:null,table:null,id:null,data:{},area:["500px","500px"]},e);var s=e.id||"icesadmin-"+(new Date).getTime();t.popup({title:e.title,area:e.area,id:s,success:function(u,s){n(this.id).render(e.view,e.data).done(function(){r?r(e):layui.event.call(this,layui.setter.MOD_NAME,"render({*})",e),e.btn&&i.on("submit("+e.btn+")",function(i){return t.req({method:"POST",url:e.url,data:i.field,done:function(t){"function"==typeof d?d(t):(e.table&&a.reload(e.table),l.close(s))}}),!1})})}})}},r.events={del:function(e,t,i){var n=a.checkStatus(e),u=n.data;return 0===u.length?l.msg("请选择数据"):void r.funcs.del({data:u,url:t,table:e},i)},add:function(e,t,i){r.funcs.add(e,t,i)}},e("think",r)});
