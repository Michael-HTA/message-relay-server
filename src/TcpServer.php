<?php
namespace App\Src;
use Swoole\Server;

class TcpServer
{
    private $server;
    private array $userConnections = [];

    public function __construct(string $host = "0.0.0.0", int $port = 9502)
    {
        $this->server = new Server($host, $port);

        $this->server->on("Connect", [$this, 'onConnect']);
        $this->server->on("Receive", [$this, 'onReceive']);
        $this->server->on("Close", [$this, 'onClose']);
    }

    public function start(): void
    {
        echo "Starting TCP server...\n";
        $this->server->start();
    }

    public function onConnect(Server $server, int $fd): void
    {
        echo "Client connected: FD=$fd\n";
    }

    public function onReceive(Server $server, int $fd, int $reactor_id, string $data): void
    {
        $decoded = json_decode(trim($data), true);

        if (!$decoded) {
            $server->send($fd, "Invalid JSON\n");
            return;
        }

        $action = $decoded['action'] ?? '';

        switch ($action) {
            case 'register':
                $this->handleRegister($server, $fd, $decoded);
                break;

            case 'message':
                $this->handleMessage($server, $fd, $decoded);
                break;

            default:
                $server->send($fd, "Unknown action\n");
        }
    }

    public function onClose(Server $server, int $fd): void
    {
        echo "Client closed: FD=$fd\n";

        foreach ($this->userConnections as $userId => $userFd) {
            if ($userFd === $fd) {
                unset($this->userConnections[$userId]);
                echo "Removed user $userId with FD $fd\n";
                break;
            }
        }
    }

    private function handleRegister(Server $server, int $fd, array $data): void
    {
        $userId = $data['user_id'] ?? null;
        if ($userId) {
            $this->userConnections[$userId] = $fd;
            $server->send($fd, "Registered as user_id: $userId\n");
            echo "User registered: $userId => FD=$fd\n";
        } else {
            $server->send($fd, "Missing user_id for register\n");
        }
    }

    private function handleMessage(Server $server, int $fd, array $data): void
    {
        $toUserId = $data['to'] ?? null;
        $message = $data['message'] ?? '';

        if ($toUserId && isset($this->userConnections[$toUserId])) {
            $targetFd = $this->userConnections[$toUserId];
            $fromUserId = array_search($fd, $this->userConnections, true) ?: 'unknown';
            $relayMessage = "Message from $fromUserId: $message\n";

            $server->send($targetFd, $relayMessage);
            $server->send($fd, "Message sent to $toUserId\n");
        } else {
            $server->send($fd, "User $toUserId not connected\n");
        }
    }
}
