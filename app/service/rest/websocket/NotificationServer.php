<?php

chdir(dirname(__DIR__, 4));
require_once 'init.php';

new TSession;
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Socket\SecureServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $clientParams = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Receber os parâmetros da URL
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);

        $canal_id = $queryParams['canal_id'] ?? null;
        $chat_id = $queryParams['chat_id'] ?? null;

        $canal = DoubleCanal::identificar($canal_id);
        $usuario = DoubleUsuario::identificar($chat_id, $canal->plataforma->id, $canal->id);
        
        $serverName = DoubleConfiguracao::getConfiguracao('server_name');

        if ($canal_id and $chat_id) {
            $canal = DoubleCanal::identificar($canal_id);
            $plataforma = $canal->plataforma;

            // Armazena a nova conexão para enviar notificações
            $this->clients->attach($conn);

            // Armazenar os parâmetros para este cliente
            $this->clientParams[$conn->resourceId] = [
                'plataforma_id' => $plataforma->id,
                'chat_id'  => $chat_id,
                'usuario_id' => $usuario->id
            ];

            echo "Nova conexão aberta: {$conn->resourceId} - canal_id: {$canal_id} - plataforma_id: {$plataforma->id} - chat_id: {$chat_id}\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Não precisa de manipulação aqui se apenas quiser enviar mensagens Redis
        echo "Mensgem recebida de {$from->resourceId} - {$msg}\n";
        foreach ($this->clients as $client) {
            $json_message = json_decode($msg);
            $json_payload = json_decode($json_message->payload);
            $clientParams = $this->clientParams[$client->resourceId];
            if ($json_message->channel === 'atualiza_sinais' && $clientParams['plataforma_id'] && 
                $clientParams['plataforma_id'] === $json_payload->plataforma_id) 
            {
                $client->send($msg);
                echo "Enviado para {$client->resourceId} - {$msg}\n";
            }

            if ($json_message->channel === 'mensagem_usuario' && $clientParams['chat_id'] && 
                $clientParams['chat_id'] == $json_payload->chat_id) 
            {
                $client->send($msg);
                echo "Enviado para {$client->resourceId} - {$msg}\n";
            } 

            if ($json_message->channel === 'historico_usuario' && $clientParams['usuario_id'] && 
                $clientParams['usuario_id'] == $json_payload->usuario_id) 
            {
                $client->send($msg);
                echo "Enviado para {$client->resourceId} - {$msg}\n";
            } 

        }
    }

    public function onClose(ConnectionInterface $conn) {
        $resourceId = $conn->resourceId;

        if (isset($this->clientParams[$resourceId]))
            echo "Conexão fechada: {$resourceId}\n";

        // Remover o cliente da lista de conexões
        $this->clients->detach($conn);
        unset($this->clientParams[$resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$tipo_ws = DoubleConfiguracao::getConfiguracao('tipo_ws');
$porta_ws = DoubleConfiguracao::getConfiguracao('porta_ws');
$local_cert = DoubleConfiguracao::getConfiguracao('local_cert');
$local_pk = DoubleConfiguracao::getConfiguracao('local_pk');
echo "Tipo WS: {$tipo_ws}\n";


if ($tipo_ws === 'ws') {
    $server = \Ratchet\Server\IoServer::factory(
        new \Ratchet\Http\HttpServer(
            new \Ratchet\WebSocket\WsServer(
                new NotificationServer()
            )
        ),
        8080
    );

    $server->run();
} else {
    // Crie o loop do ReactPHP
    $loop = Loop::get(); // Garante que o loop seja o correto

    // Defina sua aplicação WebSocket
    $app = new NotificationServer();

    // Crie o servidor de socket normal
    $socket = new SocketServer('0.0.0.0:' . $porta_ws, [], $loop);

    // Configure o servidor seguro (WSS) com os certificados SSL gerados pelo Let's Encrypt
    $secureWebsockets = new SecureServer($socket, $loop, [
        'local_cert' => $local_cert, // '/var/www/httpd-cert/app.turbocash.blog_2024-07-30-19-45_22.crt',
        'local_pk'   => $local_pk, // '/var/www/httpd-cert/app.turbocash.blog_2024-07-30-19-45_22.key',
        'allow_self_signed' => false,
        'verify_peer' => false
    ]);

    // Configure o servidor HTTP com WebSocket
    $server = new IoServer(
        new HttpServer(
            new WsServer($app)
        ),
        $secureWebsockets,
        $loop
    );

    // Rode o servidor
    $loop->run();
}