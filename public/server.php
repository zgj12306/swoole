<?php
//服务器代码
//创建websocket 服务器

$table = new Swoole\Table(1024);
$table->column('fd', Swoole\Table::TYPE_INT);
$table->column('name', Swoole\Table::TYPE_STRING, 64);
$table->create();

$ws = new Swoole\Websocket\Server("0.0.0.0",8000);

$ws->table = $table;
// open
$ws->on('open',function($ws,$request){
    echo "新用户 $request->fd 加入。\n";

});
//message
$ws->on('message',function($ws,$request){
    $msg = $GLOBALS['fd'][$request->fd]['name'].":".$request->data."\n";
    if(strstr($request->data,"#name#")){//用户设置昵称
        $ws->table->set($request->fd, ['fd' => $request->fd, 'name' => str_replace("#name#",'',$request->data)]);

    }else{//进行用户信息发送
        //发送每一个客户端
        $name = $ws->table->get($request->fd)['name'];
        foreach($ws->table as $i){
            $ws->push($i['fd'], $name . $msg);
        }
    }
});
//close
$ws->on('close',function($ws,$request){
    echo "客户端-{$request} 断开连接\n";
    unset($GLOBALS['fd'][$request]);//清楚连接仓库
});
$ws->start();