<?php
//服务器代码
//创建websocket 服务器
$ws = new Swoole\Websocket\Server("0.0.0.0",8000);
$table = new Swoole\Table(1024);
$table->column('fd', Swoole\Table::TYPE_INT);
$table->column('reactor_id', Swoole\Table::TYPE_INT);
$table->column('data', Swoole\Table::TYPE_STRING, 64);
$table->create();

$serv = new Swoole\Server('127.0.0.1', 9501);
$serv->set(['dispatch_mode' => 1]);
$serv->table = $table;
// open
$ws->on('open',function($ws,$request){
    echo "新用户 $request->fd 加入。\n";
    $GLOBALS['fd'][$request->fd]['id'] = $request->fd;//设置用户id
    $GLOBALS['fd'][$request->fd]['name'] = '匿名用户';//设置用户名

});
//message
$ws->on('message',function($ws,$request){
    $msg = $GLOBALS['fd'][$request->fd]['name'].":".$request->data."\n";
    if(strstr($request->data,"#name#")){//用户设置昵称
        $GLOBALS['fd'][$request->fd]['name'] = str_replace("#name#",'',$request->data);

    }else{//进行用户信息发送
        //发送每一个客户端
        foreach($GLOBALS['fd'] as $i){
            $ws->push($i['id'],$msg);
        }
    }
});
//close
$ws->on('close',function($ws,$request){
    echo "客户端-{$request} 断开连接\n";
    unset($GLOBALS['fd'][$request]);//清楚连接仓库
});
$ws->start();