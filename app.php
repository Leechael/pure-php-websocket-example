<?php

include_once('vendor/autoload.php');

use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Handler implements MessageComponentInterface {

    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function getClients()
    {
        return $this->clients;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to "%d" other connection%s' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $err)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function sendMessage($msg)
    {
        $counts = 0;
        foreach ($this->clients as $client) {
            $client->send($msg);
            $counts++;
        }
        echo "Total {$counts} client got messages.\n";
    }
}

$handler = new Handler;
$loop = new React\EventLoop\StreamSelectLoop;


//
// The Redis subscriber.
//
$client = new Predis\Async\Client('tcp://127.0.0.1:6379', $loop);
$client->connect(function($client) use ($loop, $handler) {
    echo "Connected to Redis, now listening for incoming messages...\n";

    $client->pubSubLoop('pubsub:example', function ($event) use ($handler) {
        echo "Redis pubsub message: \n";
        $handler->sendMessage($event->payload);
    });
});


//
// The React IOServer
//
$socket = new React\Socket\Server($loop);
$socket->listen('8080', '127.0.0.1');
$server = new IoServer(new HttpServer(new WsServer($handler)), $socket, $loop);


//
// Runs per 5 seconds, send memory usage and connected clients count to stdout.
//
$loop->addPeriodicTimer(5, function () use ($handler) {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    $num = count($handler->getClients());
    echo "Current memory usage: {$formatted} | Connected clients: {$num}\n";
});

$loop->run();