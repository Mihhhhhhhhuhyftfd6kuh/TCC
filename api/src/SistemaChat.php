<?php

namespace Api\websocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class SistemaChat implements MessageComponentInterface
{
    /** @var \SplObjectStorage */
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Nova conexao: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Conexao fechada: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        error_log('WebSocket error: ' . $e->getMessage());
        $conn->close();
    }
}
