<?php

// chdir(dirname(__DIR__, 3));
// require_once 'init.php';

use Predis\Client as RedisClient;
use WebSocket\Client as WebSocketClient;

new TSession;
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

class RedisWebSocket
{
    public function run($param){
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $serverName = DoubleConfiguracao::getConfiguracao('server_name');

        $channel_sinais = strtolower("{$serverName}_{$plataforma->nome}_{$plataforma->idioma}_sinais");
        $channel_mensagem = strtolower("{$serverName}_mensagem_{$plataforma->nome}_{$plataforma->idioma}");
        $channel_usuario_historico = strtolower("{$serverName}_usuario_historico_notify");

        $redis = new RedisClient(['read_write_timeout' => 0]);
        $pubsub = $redis->pubSubLoop();

        $pubsub->subscribe($channel_sinais);
        $pubsub->subscribe($channel_mensagem);
        $pubsub->subscribe($channel_usuario_historico);
        
        foreach ($pubsub as $message) {
            $message = (object) $message;
            // echo "{$message->channel} - {$message->payload}\n";
            if ($message->kind === 'message') {
                $payload = ['payload' => $message->payload];
                if ($message->channel === $channel_sinais) {
                    $payload['channel'] = 'atualiza_sinais';
                } elseif ($message->channel === $channel_mensagem) {
                    $payload['channel'] = 'mensagem_usuario';
                } elseif ($message->channel === $channel_usuario_historico) {
                    $payload['channel'] = 'historico_usuario';
                } 
                
                $payload = json_encode($payload);
                $this->sendMessageToNotificationServer($payload);
            }
        }      
    }
    
    private function sendMessageToNotificationServer($message) {
        try {
            $servidor_ws = DoubleConfiguracao::getConfiguracao('servidor_ws');
            echo "Servidor WS: {$servidor_ws}\n";

            $client = new WebSocketClient($servidor_ws);
            $client->send($message);
            $client->close();

            echo "Enviado ao WS {$message}\n";
        } catch (\Throwable $th) {
            $erro = $th->getMessage();
            echo "Erro ao enviar a notificação ao WS\nMensage: $message\nErro: $erro\n";
        }
        
    }
}