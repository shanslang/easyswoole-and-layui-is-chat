<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes" />
    <link rel="stylesheet" type="text/css" href="/Static/asset/login/css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="/Static/asset/login/css/demo.css" />
    <!--必要样式-->
    <link rel="stylesheet" type="text/css" href="/Static/asset/login/css/component.css" />
    <link rel="stylesheet" type="text/css" href="/Static/asset/layui/css/layui.css" />
    <script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript" src="/Static/asset/layui/layui.js"></script>
</head>
<body>
<div class="container demo-1">
    <div class="content">
        <div id="large-header" class="large-header">
            <canvas id="demo-canvas"></canvas>
            <div class="logo_box">
                <h3>登录 chat</h3>
                <form action="#" name="f" method="post">
                    <input type="password" style="position:absolute;top:-999px"/>
                    <div class="input_outer">
                        <span class="u_user"></span>
                        <input name="username" class="text" style="color: #FFFFFF !important" type="text" placeholder="请输入账户" value="test1">
                    </div>
                    <div class="input_outer">
                        <span class="us_uer"></span>
                        <input name="password" class="text" style="color: #FFFFFF !important; position:absolute; z-index:100;"value="123456" type="password" placeholder="请输入密码">
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">验证码</label>
                        <div class="layui-input-block">
                            <input type="text" style="width: 200px;float: left;" name="code" required  lay-verify="required" placeholder="请输入验证码" autocomplete="off" class="layui-input">
                            <img style="width: 150px;height: 40px;margin-left: 10px" src="/Chat/Chat/getCode?key=<?php echo $code_hash;?>" alt="">
                            <input type="hidden" name="key" value="<?php echo $code_hash;?>">
                        </div>
                    </div>
                    <div class="mb2"><a id = "sub" lay-filter="sub" class="act-but submit" href="javascript:;" style="color: #FFFFFF">登录</a></div>
                </form>
                <!-- <p style="text-align: center;">还没有账号？立即<a style="color: #cccccc;" href="javascript:;" onclick="register()"> 注册 </a></p> -->
            </div>
        </div>
    </div>
</div><!-- /container -->
<script src="/Static/asset/login/js/TweenLite.min.js"></script>
<script src="/Static/asset/login/js/EasePack.min.js"></script>
<script src="/Static/asset/login/js/rAF.js"></script>
<script src="/Static/asset/login/js/demo-1.js"></script>
</body>
<script>
    function register() {
        layer.open({
            type: 2,
            title: '注册',
            shadeClose: true,
            shade: 0.8,
            area: ['40%', '70%'],
            content: '/Chat/Chat/register' //iframe的url
        });
    }
    //加载弹出层组件
    layui.use('layer',function(){

        var layer = layui.layer;

        //登录的点击事件
        $("#sub").on("click",function(){
            login();
        })

        $("body").keydown(function(){
            if(event.keyCode == "13"){
                login();
            }
        })

        //登录函数
        function login(){
            var username = $(" input[ name='username' ] ").val();
            var password = $(" input[ name='password' ] ").val();
            var code     = $(" input[ name='code' ] ").val();
            var key      = $(" input[ name='key' ] ").val();
            $.ajax({
                url:"/Customer/CustomerService/login",
                data:{"username":username,"psw":password, code:code, key:key},
                type:"post",
                dataType:"json",
                success:function(res){
                    if(res.code == 200){
                        layer.msg(res.msg);
                        var storage=window.localStorage;
                        storage.setItem("token",res.data.token);
                        setTimeout(function(){
                            window.location = "/Customer/CustomerService/index?token="+res.data.token;
                       },1500);
                    }else{
                        console.log(res);
                        layer.msg(res.msg,function(){});
                    }
                }
            })
        }
    })
</script>
</html>