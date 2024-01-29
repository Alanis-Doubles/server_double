<?php

use GuzzleHttp\Client;

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
            "text" => $message
        ];

        if ($reply_markup)
            $payload['reply_markup'] = $reply_markup;

        $location = str_replace('{token}', $telegram_token, $telegram_host);
        $client = new Client();
        $response = $client->request(
            'POST', 
            $location.'sendMessage',
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

    public function deleteMessage($chat_id, $message_id) {
        $telegram_host = DoubleConfiguracao::getConfiguracao('telegram_host');
        $telegram_token = $this->telegram_token;
            
        $payload = [
            'chat_id' => $chat_id,
            "message_id" => $message_id
        ];

        $location = str_replace('{token}', $telegram_token, $telegram_host);
        $client = new Client();
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
