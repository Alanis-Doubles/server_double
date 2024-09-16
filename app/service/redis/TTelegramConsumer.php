<?php

use Predis\Client;

class TTelegramConsumer extends TDoubleRedis
{
    private $queue;

    public function notificar_consumidores($payload)
    {
        if ($payload['chat_id'] < 0)
            return;

        $canal = TUtils::openFakeConnection('double', function() use ($payload){
            return DoubleCanal::where('telegram_token', '=', $payload['telegram_token'])->first();
        });

        if (!$canal)
            return;

        $channel_name = strtolower("{$this->serverName()}_mensagem_{$canal->plataforma->nome}_{$canal->plataforma->idioma}");

        $redis = new Client();
        // echo "mensagem: ". json_encode(payload) . "\n";
        $mensagem = $payload['message'];
        $mensagem = nl2br($mensagem);// str_replace('\n', '<br>', $mensagem);
        $redis->publish($channel_name, json_encode(['message' => $mensagem, 'chat_id' => $payload['chat_id']]));
        echo "{$channel_name}: enviado\n";
    }
    
    public function sendMessageToTelegram($payload) {
        $this->notificar_consumidores($payload);

        echo "chat_id: {$payload['chat_id']}\n{$payload['message']}\n";
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $payload['telegram_token'];
            
        $telegram_payload = [
            "chat_id" => $payload['chat_id'],
            "text" => str_replace('\n', "\n", $payload['message'])
        ];

        if (isset($payload['reply_markup']))
            $telegram_payload['reply_markup'] = $payload['reply_markup'];

        $location = str_replace('{token}', $telegram_token, $telegram_host);

        $ch = curl_init();
    
        $defaults = array(
            CURLOPT_URL => $location . 'sendMessage', 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($telegram_payload),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
              ),
        );
        
        curl_setopt_array($ch, $defaults);
        $output = curl_exec ($ch);
        
        curl_close ($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $contents = null;
        if ($http_status == 200)
            $contents = json_decode($output);
        else {
            $error_message = curl_error($ch);
            $error_number = curl_errno($ch);
            $json_erro = json_encode($telegram_payload);
            // DoubleErros::registrar(
            //     1, 
            //     'TTelegramConsumer', 
            //     'sendMessageToTelegram', 
            //     "cURL error ({$error_number}): {$error_message}",
            //     json_encode($telegram_payload)
            // );
            echo "cURL error ({$error_number}): {$error_message}\nJSON: $json_erro";
        }

        $redis = new Client();
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $var = "{$server_name}_telegram_delete";

        if ($redis->exists($var)) {
            $deletar = $redis->get($var);
            $redis->del($var);
            $redis->lpush($this->queue, $deletar);
        }


        if (isset($payload['excluir']) and $payload['excluir']) {
            $payload_delete = [
                'telegram_token' => $payload['telegram_token'],
                'chat_id' => $payload['chat_id'],
                'message_id' => $contents->result->message_id,
                'tipo' => 'deleteMessage'
            ];

            $redis->set($var, json_encode($payload_delete));

            // $redis->lpush($this->queue, json_encode($payload_delete));
        }

        // if ($http_status == 200) {
        //     // echo "message_id: {$contents->result->message_id}\n";
        //     return $contents->result->message_id;
        // }
        // else
        //     return null;
    }

    public function deleteMessageToTelegram($payload) {
        // echo "chat_id: {$payload['chat_id']} - message_id: {$payload['message_id']}\n";
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $payload['telegram_token'];
            
        $telegram_payload = [
            'chat_id' => $payload['chat_id'],
            "message_id" => $payload['message_id']
        ];

        $location = str_replace('{token}', $telegram_token, $telegram_host);

        $ch = curl_init();
    
        $defaults = array(
            CURLOPT_URL => $location . 'deleteMessage', 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($telegram_payload),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
              ),
        );
        
        curl_setopt_array($ch, $defaults);
        $output = curl_exec ($ch);
        
        curl_close ($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($http_status !== 200) {
            $error_message = curl_error($ch);
            $error_number = curl_errno($ch);
            DoubleErros::registrar(
                1, 
                'TTelegramConsumer', 
                'deleteMessageToTelegram', 
                "cURL error ({$error_number}): {$error_message}",
                json_encode($telegram_payload)
            );
        }

        return $http_status == 200;
    }

    public function run($param) {
        $redis = new Client();
        $this->queue = "{$this->serverName()}_telegram_queue";
        // echo "$queue\n";
                
        while (true) {
            try {
                $message = $redis->brpop($this->queue, 0); 
                // echo "$message\n";
                if ($message) {
                    $payload = json_decode($message[1], true);
            
                    if ($payload['tipo'] == 'sendMessage') {
                        $this->sendMessageToTelegram($payload);
                    } elseif ($payload['tipo'] == 'deleteMessage') {
                        $this->deleteMessageToTelegram($payload);
                    }
                }
            } catch (\Throwable $th) {
                DoubleErros::registrar(
                    1, 
                    'TTelegramConsumer', 
                    'run', 
                    $th->getMessage()
                ); 
            }
        }
    }
}