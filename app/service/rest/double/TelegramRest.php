<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Ramsey\Uuid\Rfc4122\NilUuid;

class TelegramRest
{
    private $telegram_token;

    public function __construct($telegram_token)
    {
        $this->telegram_token = $telegram_token;
    }

    public function sendMessage($chat_id, $message, $reply_markup = []) {
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $this->telegram_token;
            
        $payload = [
            "chat_id" => $chat_id,
            "text" => str_replace('\n', "\n", $message)
        ];

        if ($reply_markup)
            $payload['reply_markup'] = $reply_markup;

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
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
              ),
        );
        
        curl_setopt_array($ch, $defaults);
        $output = curl_exec ($ch);
        
        curl_close ($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($chat_id < 0) {
            $canal = DoubleCanal::identificarPorChannel($chat_id);
            $canal->updated_at = (new DateTime())->format('Y-m-d H:i:s');
            $canal->saveInTransaction();
        }
        
        $contents = null;
        if ($http_status == 200)
            $contents = json_decode($output);
        else
            DoubleErros::registrar(1, 'DoubleErros', 'sendMessage', $location . 'sendMessage', json_encode($payload) );
        return $contents;
    }

    public function sendPhoto($chat_id, $urlPhoto) {
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $this->telegram_token;
            

        $location = str_replace('{token}', $telegram_token, $telegram_host);

        $client = new Client(['http_errors' => false]);
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $options = [
            'multipart' => [
                ['name' => 'chat_id', 'contents' => $chat_id],
                [
                    'name' => 'photo',
                    'contents' => Utils::tryFopen($urlPhoto, 'r'),
                    'filename' => $urlPhoto,
                    'headers'  => [
                        'Content-Type' => '<Content-type header>'
                    ]
                ]
            ]
        ];

        $response = $client->request(
            'POST', 
            $location.'sendPhoto',
            $options
        );
        
        $contents = json_decode($response->getBody()->getContents());
        return $contents;
    }

    public function sendVideo($chat_id, $urlVideo) {
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $this->telegram_token;
            

        $location = str_replace('{token}', $telegram_token, $telegram_host);

        $client = new Client(['http_errors' => false]);
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $options = [
            'multipart' => [
                ['name' => 'chat_id', 'contents' => $chat_id],
                [
                    'name' => 'video',
                    'contents' => Utils::tryFopen($urlVideo, 'r'),
                    'filename' => $urlVideo,
                    'headers'  => [
                        'Content-Type' => '<Content-type header>'
                    ]
                ]
            ]
        ];

        $response = $client->request(
            'POST', 
            $location.'sendVideo',
            $options
        );
        
        $contents = json_decode($response->getBody()->getContents());
        return $contents;
    }

    public function deleteMessage($chat_id, $message_id) {
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $this->telegram_token;
            
        $payload = [
            'chat_id' => $chat_id,
            "message_id" => $message_id
        ];

        $location = str_replace('{token}', $telegram_token, $telegram_host);
        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'POST', 
            $location.'deleteMessage',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );
        
        $contents = json_decode($response->getBody()->getContents());
        return $contents;
    }

}
