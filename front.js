$(function(){
    $('.confirm_name').click(function () {
        nickname=$('.nickname').val();
        nickname=nickname.replace(/^\s+|\s+$/gm,'');
        if(!nickname){
            alert('请输入昵称');
            return false;
        }
        if(nickname.length>15){
            alert('昵称不能超过15个字符');
            return false;
        }
        if(!isNaN(nickname)){
            alert('昵称不能为纯数字');
            return false;
        }
        if(is_use(nickname)){
            alert('改昵称已被占用');
            return false;
        }
        $('.stage').hide();
        $('ul,h3').show();
        link(nickname);
    })
    $('ul').on('click','li',function(obj){
        var idName=$(this).attr('class');
        $('ul,h3').hide();
        $('#'+idName).show().children('.answer').focus();
        var chat_div=$('#'+idName).children('.content');
        var chat_div_height=chat_div.get(0).scrollHeight;
        chat_div.scrollTop(chat_div_height);
    })
})
//发送消息
function send(obj) {
    var chat_div=$(obj).siblings('.content');
    var idName=$(obj).parent().attr('id');
    //接受客户端标识
    var to_fd=$('.'+idName).data('fd');
    var msg=$(obj).prev().val();
    //消息内容添加到内容框
    var cur_time=get_time();
    var send_msg=nickname+' '+cur_time+'<br>'+msg;
    var div=$('<div class="msg">'+send_msg+'</div>');
    div.css('text-align','right');
    //显示最近的一条消息
    $('.'+idName).find('.last-msg').text(msg);
    //有新消息滚动到最下方
    var chat_div_height=chat_div.get(0).scrollHeight;
    chat_div.append(div).scrollTop(chat_div_height);
    $(obj).prev().val('').focus();
    var obj={'user':to_fd,'text':msg,'status':200};
    websocket.send(JSON.stringify(obj));
}
//连接swoole
function link(user) {
    var wsServer = 'ws://liule.online:9501';
    websocket = new WebSocket(wsServer);
    websocket.onopen = function (evt) {
        var obj={'user':user,'status':100};
        websocket.send(JSON.stringify(obj));
    };
    websocket.onmessage = function (evt) {
        var data=JSON.parse(evt.data);
        console.log(data);
        if(data.status==200){
            //监听用户消息
            var msg=data.data;
            var div=$('<div class="msg">'+msg+'</div>');
            var user=data.user;
            //显示最近的一条消息
            var last_msg=msg.split('<br>')[1];
            $('.'+user).find('.last-msg').text(last_msg);
            var chat_div=$('#'+user).children('.content');
            chat_div.append(div);
            if(!$('#'+user).is(':hidden')){
                var chat_div_height=chat_div.get(0).scrollHeight;
                chat_div.scrollTop(chat_div_height);
            }
        }else if(data.status==300){
            //获取在线用户
            var online=data.data;
            var online_add=0;
            for(var i in online){
                online_add++;
                var temp_li=$('ul li').eq(0).clone();
                temp_li.data('fd',i);
                temp_li.attr('class',online[i]);
                temp_li.children('.recipient').text(online[i]);
                temp_li.children('.last-msg').text('');
                $('ul').append(temp_li);
                //添加聊天对话框
                var div=$('.copy').eq(0).clone();
                div.find('.content').empty();
                div.find('.answer').val('');
                div.attr('id',online[i]);
                div.css('display','none');
                div.find('.to_user').text(online[i]);
                $('body').append(div);
            }
            $('.online-num').text(parseInt($('.online-num').text())+online_add);
        }else if(data.status==400){
            //监听用户下线
            if(!$('#'+data.data).is(':hidden')){
                //正在与下线用户聊天则显示在线用户列表
                $('ul,h3').show();
            }
            $('.'+data.data+',#'+data.data).remove();
            $('.online-num').text(parseInt($('.online-num').text())-1);
        }
    };
}
function back(obj) {
    $(obj).parents('.copy').hide();
    $('ul,h3').show();
}
function get_time() {
    var date=new Date();
    var month=format_time(date.getMonth()+1);
    var day=format_time(date.getDate());
    var hours=format_time(date.getHours());
    var minutes=format_time(date.getMinutes());
    var seconds=format_time(date.getSeconds());
    return  date.getFullYear()+'-'+month+'-'+day+' '+hours+':'+minutes+':'+seconds;
}
function format_time(str) {
    return str<10?'0'+str:str;
}
//昵称是否已被占用
function is_use(name) {
    var flag;
    $.ajax({
        url:'is_online.php',
        type:'post',
        data:{'name':name},
        async:false,
        success:function (data){
            if(data==1){
                flag=true;
            }else{
                flag=false;
            }
        }
    })
    return flag;
}
