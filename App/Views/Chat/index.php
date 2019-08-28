<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>聊天</title>
    <link rel="stylesheet" href="/Static/asset/layui/css/layuiv2.css" media="all">
</head>
<body>
<ul class="layui-nav">
    <li class="layui-nav-item">
        <a href="">个人中心<span class="layui-badge-dot"></span></a>
    </li>
    <li class="layui-nav-item" style="float: right;">
        <a href=""><img src="<?=$this->e($user['avatar'])?>" class="layui-nav-img">我</a>
        <dl class="layui-nav-child">
            <dd><a href="javascript:;" onclick="editInfo()">修改信息</a></dd>
            <!--            <dd><a href="javascript:;">安全管理</a></dd>-->
            <dd><a href="/Chat/Chat/loginout?token=<?=$this->e($token)?>">退了</a></dd>
        </dl>
    </li>
</ul>
<script src="/Static/asset/layui/layui.js"></script>
<script src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script>
    function editInfo() {
        layer.open({
            type: 2,
            title: '修改信息',
            shadeClose: true,
            shade: 0.8,
            area: ['40%', '70%'],
            content: '/Chat/Chat/editInfo' //iframe的url
        });
    }
  
    var storage = window.localStorage;
    layui.use('element', function(){
        var element = layui.element;
    });
    function sendMsg(socket, data){
        console.log('连接状态码：'+socket.readyState);
        socket.send(data);
    }
    var ping;
    var socket;

    layui.use('layim', function(layim){
        //基础配置
        layim.config({
            title: '我的通讯',
            isAudio: true,
            isVideo: true,
            init: {
                url: '/Chat/User/userinfo', //接口地址
                data: {
                    'token': storage.getItem('token')
                }
            } //获取主面板列表信息，下文会做进一步介绍

            //获取群员接口（返回的数据格式见下文）
            ,members: {
                url: '/Chat/User/members' //接口地址
                ,type: 'get' //默认get，一般可不填
                ,data: {} //额外参数
            }

            //上传图片接口（返回的数据格式见下文），若不开启图片上传，剔除该项即可
            ,uploadImage: {
                url: '/Chat/Func/uploadImg' //接口地址
                ,type: 'post' //默认post
            }

            //上传文件接口（返回的数据格式见下文），若不开启文件上传，剔除该项即可
            ,uploadFile: {
                url: '/Chat/Func/uploadFile' //接口地址
                ,type: 'post' //默认post
            }
            //扩展工具栏，下文会做进一步介绍（如果无需扩展，剔除该项即可）
            ,tool: [{
                alias: 'code' //工具别名
                ,title: '代码' //工具名称
                ,icon: '&#xe64e;' //工具图标，参考图标文档
            }]

            ,msgbox: '/Chat/User/messageBox?token='+storage.getItem('token') //消息盒子页面地址，若不开启，剔除该项即可
            ,find: '/Chat/User/find' //发现页面地址，若不开启，剔除该项即可
            ,chatLog: '/Chat/User/chatLog' //聊天记录页面地址，若不开启，剔除该项即可
        });

        socket = new WebSocket('<?=$this->e($server)?>?token='+storage.getItem('token'));
        socket.onopen = function(){
            console.log('socket successful connection');
            ping = setInterval(function(){
                sendMsg(socket, '{"type":"ping"}');
                console.log('ping...');
            }, 1000*10);
        };
        socket.onmessage = function(res){
            data = JSON.parse(res.data);
            switch(data.type){
                case 'friend':
                case 'group':
                    layim.getMessage(data);
                    break;
                case 'msgBox':
                    setTimeout(function(){
                        if(data.count > 0){
                            layim.msgbox(data.count);
                        }
                    }, 1000);
                    break;
                case 'layer':
                	if(data.code == 200){
                    	layer.msg(data.msg);
                    }else{
                    	layer.msg(data.msg, function(){});
                    }
                    break;
                case 'friendStatus':
                    layim.setFriendStatus(data.uid, data.status);
                    break;
                case 'token_expire':
                    window.location.reload();
                    break;
                case 'joinNotify':
                    layim.getMessage(data.data);
                    break;
                case 'addList':
                    console.log(data.data);
                    layim.addList(data.data);
                    break;
            }
        };

        socket.onclose = function(){
            console.log('websocket is closed');
            clearInterval(ping);
            layer.open({
               title: '掉线'
               ,content: '可尝试刷新页面'
            }); 
        };

        layim.on('sendMessage', function(res){
            res.token = storage.getItem('token');
            sendMsg(socket, JSON.stringify({
                type: 'chatMessage'
                ,data: res
            }));
        });

      	layim.on('sign', function(value){
            $.ajax({
                url: '/Chat/User/signEdit',
                type: 'post',
                data: {sign: value, token: storage.getItem('token')},
                success: function (result) {
                    //console.log(result);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    console.log(textStatus);
                }
            });
        });
      
        layim.on('tool(code)', function(insert, send, obj){
            layer.prompt({
                title: '插入代码',
                formType: 2,
                shade: 0
            }, function(text, index){
                layer.close(index);
                insert('[pre class=layui-code]' + text + '[/pre]');
            });
            //console.log(this); //获取当前工具的DOM对象
            //console.log(obj); //获得当前会话窗口的DOM对象、基础信息
        });    
      
        layim.on('chatChange', function(obj){
            //console.log(obj.data);
            var type = obj.data.type;
            if(type === 'friend'){
               if(obj.data.status == 'online'){
                    layim.setChatStatus('<span style="color:#FF5722;">在线</span>'); //模拟标注好友在线状态
               }else{
                    layim.setChatStatus('<span style="color:#666;">离线</span>'); //模拟标注好友在线状态
               }
            }
        });
    });
</script>
</body>
</html>