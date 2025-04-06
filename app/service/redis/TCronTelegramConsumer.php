<?php

use Predis\Client;

class TCronTelegramConsumer extends TDoubleRedis
{
    private function sendMessage(TelegramRest $telegram, $payload) {
        // Envia uma mensagem via Telegram
        $telegram->sendMessage($payload['chat_id'], $payload['text'], $payload['reply_markup']);
    }

    private function sendPhoto(TelegramRest $telegram, $payload) {
        foreach ($payload as $key => $value) {
           // Envia uma foto via Telegram
           $telegram->sendPhoto($value['chat_id'], $value['photo']);
        }
    }

    private function sendVideo(TelegramRest $telegram, $payload) {
        foreach ($payload as $key => $value) {
            // Envia uma video via Telegram
            $telegram->sendVideo($value['chat_id'], $value['photo']);
         }
    }

    private function processMessage($payload) {
        // Processa a mensagem recebida
        // Aqui você pode adicionar a lógica para processar a mensagem
        // Por exemplo, enviar uma notificação via Telegram
        echo "Processando mensagem: " . json_encode($payload) . "\n";

        $telegram = new TelegramRest($payload['token']);
        if (isset($payload['sendMessage']))
            $this->sendMessage($telegram, $payload['sendMessage']);

        if (isset($payload['sendPhoto']))
            $this->sendPhoto($telegram, $payload['sendPhoto']);

        if (isset($payload['sendVideo']))
            $this->sendVideo($telegram, $payload['sendVideo']);
    }

    public function run($param) {
        $redis = new Client([
           'scheme' => 'tcp',
           'host'   => $this->hostUsuario(), // IP do seu Redis
           'port'   => 6379, // Porta padrão do Redis
           'persistent' => true,
           'read_write_timeout' => -1
       ]);
       $queue = "{$this->serverName()}_cron_telegram_queue";
       // echo "$queue\n";
               
       echo "iniciando\n";
       while (true) {
           try {
               $message = $redis->brpop($queue, 0); 
               // echo "$message\n";
               if ($message) {
                   $payload = json_decode($message[1], true);
           
                  $this->processMessage($payload);
               }
           } catch (\Throwable $th) {
               echo "$th\n";
               $redis->disconnect();
               $redis = new Client([
                   'scheme' => 'tcp',
                   'host'   => $this->hostUsuario(), // IP do seu Redis
                   'port'   => 6379, // Porta padrão do Redis
                   'persistent' => true,
                   'read_write_timeout' => -1
               ]);
           }
       }
   }
}