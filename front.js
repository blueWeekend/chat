$(function(){
    $('.confirm_name').click(function () {
        var nickname=$('.nickname').val();
        nickname=nickname.replace(/^\s+|\s+$/gm,'');
        if(!nickname){
            alert('请输入昵称');
            return false;
        }
        if(!isNaN(nickname)){
            alert('昵称不能为纯数字');
            return false;
        }
        $('.stage').hide();
        link(nickname);
    })
    $('ul').on('click','li',function(obj){
        var idName=$(this).attr('class');
        var isExist=$('#'+idName).length;
        var to_uesr=idName;
        $('ul').hide();
        if(isExist){
            $('#'+idName).show();
        }else{
            var div=$('.copy').eq(0).clone();
            div.attr('id',idName);
            div.find('.to_user').text(to_uesr);
            $('body').append(div);
            div.show();
        }
    })
})
function send(obj) {
    var idName=$(obj).parent().attr('id');
    var to_fd=$('.'+idName).data('fd');
    var msg=$(obj).siblings('.answer').val();
    var obj={'user':to_fd,'text':msg,'status':200};
    $(obj).prev().val('');
    websocket.send(JSON.stringify(obj));
}
function link(user) {
    var online=$('ul');
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
            var data=data.data;
        }else if(data.status==300){
            //获取在线用户
            var online=data.data;
            for(var i in online){
                var temp_li=$('ul li').eq(0).clone();
                temp_li.data('fd',i);
                temp_li.attr('class',online[i]);
                temp_li.children('.recipient').text(online[i]);
                temp_li.children('.last-msg').text('test');
                $('ul').append(temp_li);
            }
        }else if(data.status==400){
            //监听用户下线
            $('.'+data.data).remove();
        }
    };
    $('.login').css('display','none');
}
function back(obj) {
    $(obj).parents('.copy').hide();
    $('ul').show();
}
function send_msg(){
    var obj={'user':touser,'text':text,'status':200};
    websocket.send(JSON.stringify(obj));
}
function login() {
    var text=$('.user').val();
    link(text);
}