<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>移动版</title>

<link rel="stylesheet" href="/Static/asset/mlayui/css/layui.mobile.css">
<link id="layuicss-skinlayim-mobilecss" rel="stylesheet" href="/Static/asset/mlayui/css/modules/layim/mobile/layim.css?v=2.0" media="all">

</head>
<body>


<script src="/Static/asset/mlayui/layui.js"></script>
<script src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script>
var storage = window.localStorage;
function sendMsg(socket, data){
   console.log('连接状态码：'+socket.readyState);
   socket.send(data);
}
  var ping;
  var socket;
layui.config({
  version: '20171011'
}).use('mobile', function(){
  var mobile = layui.mobile
  ,layim = mobile.layim
  ,layer = mobile.layer;

  //演示自动回复
  var autoReplay = [
    '您好，我现在有事不在，一会再和您联系。', 
    '你没发错吧？face[微笑] ',
    '请勿打扰！face[哈哈] ',
    '你好，我是主人的小助手，有什么事就跟我说吧，等他回来我会转告他的。face[心] face[心] face[心] ',
    'face[威武] face[威武] face[威武] face[威武] ',
    '<（@￣︶￣@）>',
    '你要和我说话？你真的要和我说话？你确定自己想说吗？你一定非说不可吗？那你说吧，这是自动回复。',
    'face[黑线]  你慢慢说，别急……',
    '(*^__^*) face[嘻嘻]'
  ];

  layim.config({
    //上传图片接口
    uploadImage: {
      url: '/Chat/Func/uploadImg' //（返回的数据格式见下文）
      ,type: '' //默认post
    } 
    
    //上传文件接口
    ,uploadFile: {
      url: '/Chat/Func/uploadFile' //（返回的数据格式见下文）
      ,type: '' //默认post
    }
    
    ,init: <?=$data?>
    
    //扩展更多列表
    ,moreList: [{
      alias: 'find'
      ,title: '退出'
      ,iconUnicode: '&#xe623;' //图标字体的unicode，可不填
      ,iconClass: '' //图标字体的class类名
    },{
      alias: 'share'
      ,title: '分享与邀请'
      ,iconUnicode: '&#xe641;' //图标字体的unicode，可不填
      ,iconClass: '' //图标字体的class类名
    }]
    
    ,isNewFriend: false //是否开启“新的朋友”
    ,isgroup: true //是否开启“群聊”
    //,chatTitleColor: '#c00' //顶部Bar颜色
    ,title: "<?=$this->e($user['nickname'])?>" //应用名，默认：我的IM,
    //,tool: [{
      //alias: 'code' //工具别名
      //,title: '聊天记录' //工具名称
      //,iconUnicode: '&#xe60e;' //工具图标，参考图标文档，可不填
      //,iconClass: '' //图标字体的class类名
    //}]
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
            console.log(data);
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
            //console.log('websocket is closed');
            clearInterval(ping);
            layer.open({
               title: '掉线'
               ,content: '可尝试刷新页面'
            }); 
        };
  //创建一个会话
  /*
  layim.chat({
    id: 111111
    ,name: '许闲心'
    ,type: 'kefu' //friend、group等字符，如果是group，则创建的是群聊
    ,avatar: '//tp1.sinaimg.cn/1571889140/180/40030060651/1'
  });
  */
  
  //监听点击“新的朋友”
  layim.on('newFriend', function(){
    layim.panel({
      title: '新的朋友' //标题
      ,tpl: '<div style="padding: 10px;">自定义模版，{{d.data.test}}</div>' //模版
      ,data: { //数据
        test: '么么哒'
      }
    });
  });
 
  //查看聊天信息
  layim.on('detail', function(data){
    var member;
    $.post("/Chat/User/members",{id:data.id},function(msg){
         var ht='<ul class="layim-members-list">';
	     member = msg.data.list;
      console.log(member);
         for(var i = 0; i <member.length; i++){
              ht += '<li data-uid="'+member[i]['id']+'"><a href="javascript:;"><img src="'+member[i]['avatar']+'" width="36"><cite>'+member[i]['username']+' ('+member[i]['id']+')</cite></a></li>';
         }
         ht += '</ul>';
    layim.panel({
      title: data.name + ' 群成员' //标题
      ,tpl: '{{d.data.test}}' //模版
      ,data: {
    		test: ht
      }
    });
       });
  });
  
  //监听点击更多列表
  layim.on('moreList', function(obj){
    switch(obj.alias){
      case 'find':
       // layer.msg('自定义发现动作');
        window.location.href='/Chat/Chat/loginout?token='+storage.getItem('token');
        //模拟标记“发现新动态”为已读
        //layim.showNew('More', false);
        //layim.showNew('find', false);
      break;
      case 'share':
        layim.panel({
          title: '邀请好友' //标题
          ,tpl: '<div style="padding: 10px;">自定义模版，{{d.data.test}}</div>' //模版
          ,data: { //数据
            test: '么么哒'
          }
        });
      break;
    }
  });
  
  //监听发送消息
  layim.on('sendMessage', function(res){
    var To = data.to;
    console.log(res);
	res.token = storage.getItem('token');
            console.log(res);
            sendMsg(socket, JSON.stringify({
                type: 'chatMessage'
                ,data: res
            }));
 
  });
  
  //模拟收到一条好友消息
  //setTimeout(function(){
   // layim.getMessage({
     // username: "贤心"
     // ,avatar: "img/xx.jpg"
      //,id: "100001"
     // ,type: "friend"
      //,cid: Math.random()*100000|0 //模拟消息id，会赋值在li的data-cid上，以便完成一些消息的操作（如撤回），可不填
     // ,content: "嗨，欢迎体验LayIM。演示标记："+ new Date().getTime()
    //});
  //}, 2000);
  
  //监听查看更多记录
  layim.on('chatlog', function(data, ul){
    console.log(data);
    layim.panel({
      title: '与 '+ data.username +' 的聊天记录' //标题
      ,tpl: '<div style="padding: 10px;">这里是模版，{{d.data.test}}</div>' //模版
      ,data: { //数据
        test: 'Hello'
      }
    });
  });

  
  layim.on('tool(code)', function(insert, send, obj){ //事件中的tool为固定字符，而code则为过滤器，对应的是工具别名（alias）

     data = obj.data;
	 layim.panel({
      title: '与 '+ data.username +' 的聊天记录' //标题
      ,tpl: '这里是模版，{{d.data.test}}' //模版
      ,data: { //数据
        test: 'Hello'
      }
    });
     // console.log(this); //获取当前工具的DOM对象
      console.log(obj); //获得当前会话窗口的DOM对象、基础信息
    });  
  
  layim.on('chatChange', function(res){
    var type = res.data.type;
    if(type === 'friend'){
      layim.setChatStatus('在线'); //模拟标注好友在线状态
    }
  });
  
  //模拟"更多"有新动态
  layim.showNew('More', true);
  layim.showNew('find', true);
});
</script>
