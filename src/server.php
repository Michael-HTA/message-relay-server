<?php
include_once __DIR__ . "/TcpServer.php";
use App\Src\TcpServer;


$server = new TcpServer();
$server->start();