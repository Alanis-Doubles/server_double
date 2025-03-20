<?php

use Predis\Client;

class TRedisUtils
{
    public static function sendMessage($chat_id, $token, $message, $reply_markup = [], $excluir = false) {
         $redis = new Client([
            'scheme' => 'tcp',
            'host'   => DoubleConfiguracao::getConfiguracao('host_usuario'), // IP do seu Redis
            'port'   => 6379, // Porta padrão do Redis
        ]);
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $queue = "{$server_name}_telegram_queue";

        $payload = [
            'chat_id' => $chat_id,
            'telegram_token' => $token,
            'generatedId' => rand(1000, 9999),
            'message' => $message,
            'tipo' => 'sendMessage',
            'excluir' => $excluir
        ];

        if ($reply_markup)
            $payload['reply_markup'] = $reply_markup;

        $redis->lpush($queue, json_encode($payload));
    }

    public static function deleteMessage($chat_id, $token, $message_id){
         $redis = new Client([
            'scheme' => 'tcp',
            'host'   => DoubleConfiguracao::getConfiguracao('host_usuario'), // IP do seu Redis
            'port'   => 6379, // Porta padrão do Redis
        ]);
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $queue = "{$server_name}_telegram_queue";

        $payload = [
            'chat_id' => $chat_id,
            'telegram_token' => $token,
            'message_id' => $message_id,
            'tipo' => 'deleteMessage'
        ];

        $redis->lpush($queue, json_encode($payload));

        // echo "Message enqueued to queue: $queue [deleteMessage] \n";
    }

    public static function getCor($cor, TDoubletranslate $translate, $completo = true) {
        switch ($cor) {
            case 'white':
                $cor_result = $completo ? $translate->COLOR_WHITE : $translate->WHITE;
                break;
            case 'red':
                $cor_result = $completo ? $translate->COLOR_RED : $translate->RED;
                break;
            case 'black':
                $cor_result =  $completo ? $translate->COLOR_BLACK : $translate->BLACK;
                break;
            case 'lo':
                $cor_result = 'LO';
                break;
            case 'hi':
                $cor_result = 'HI';
                break;

            default:
                $cor_result =  $completo ? $translate->COLOR_WHITE : $translate->WHITE;
                break;
        }

        return $cor_result;
    }

    public static function buscarNomeEstrategia($estrategia_id)
    {
        if (!$estrategia_id)
            return 'Inteligência artificial' ;
        
        return TUtils::openFakeConnection('double', function() use ($estrategia_id){
            $obj = new DoubleEstrategia($estrategia_id, false);
            if ($obj)
                return $obj->nome;
            else
                return 'Inteligência artificial';
        });  
    }
}