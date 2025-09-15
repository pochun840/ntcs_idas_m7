<?php
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;

run(function () {
    $cli = new Client("127.0.0.1", 9502);
    if (!$cli->upgrade('/')) {
        echo "Upgrade failed\n";
        return;
    }
    echo "Connected to ws://127.0.0.1:9502\n";
    while (true) {
        $frame = $cli->recv(2);
        if ($frame === false) {
            echo "recv error\n";
            break;
        }
        if ($frame === null) {
            echo "server closed\n";
            break;
        }
        echo $frame->data, PHP_EOL;
    }
});