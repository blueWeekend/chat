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
    var to_id=$(obj).data('to_fd');
    var msg=$(obj).siblings('.answer').val();
    var obj={'user':to_id,'text':msg,'status':200};
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

    };
    $('.login').css('display','none');
}
function back(obj) {
    $(obj).parents('.copy').hide();
    $('ul').show();
}
function song(){

    var obj={'user':touser,'text':text,'status':0};
    websocket.send(JSON.stringify(obj));
}
function login() {
    var text=$('.user').val();
    link(text);
}