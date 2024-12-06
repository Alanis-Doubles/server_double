<?php

use GuzzleHttp\Client as GuzzleClient;
use Predis\Client;

class TBacboJonbet extends TJonbet
{
    public static function validate(string $nome)
    {
        return substr($nome, 0, 11) == 'BacboJonbet';
    }

    public static function nome()
    {
        return 'BacboJonbet';
    }

    public function sinalCorrente() {
        $redis = new Client([
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        $pubsub = $redis->pubSubLoop();
        $channel = 'bacbo_sinal';

        $pubsub->subscribe($channel);

        foreach ($pubsub as $message) {
            $message = (object) $message;

            if ($message->kind === 'message') {
                echo "received message: {$message->channel} - {$message->payload}\n";
                $pubsub->unsubscribe($channel);
                return [
                    "status_code" => 200,
                    "data" => json_decode($message->payload)
                ];
            }
        } 
    }

    public function aguardarSinal($ultimo_sinal)
    {
        
    }

    public function getToken(DoubleUsuario $usuario)
    {
        $expiracao = date_create_from_format('Y-m-d H:i:s', $usuario->token_expiracao);
        $now = new DateTime();
        if ($now > $expiracao) {
            $payload = $usuario->token_acesso; // TCrypto::decrypt($usuario->token_acesso, $usuario->chat_id);
            $payload = (array)json_decode($payload);

            $client = new GuzzleClient();
            $response = $client->request(
                'PUT',
                'https://jon.bet/api/auth/password',
                [
                    'json' => $payload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            if ($response->getStatusCode() == 200) {
                $content = json_decode($response->getBody()->getContents());
                $usuario->token_plataforma = $content->access_token;
                $usuario->token_expiracao = date_format($now->modify('+3 hours'), 'Y-m-d H:i:s');
                $usuario->saveInTransaction('double');
                return $content->access_token;
            }
        } else {
            return $usuario->token_plataforma;
        }
    }

    public function saldo(DoubleUsuario $usuario)
    {
        if ($usuario->modo_treinamento == 'Y') {
            return $usuario->banca_treinamento;
        } else {
            $token_plataforma = self::getToken($usuario);
            $client = new GuzzleClient();
            $response = $client->request(
                'GET',
                'https://jon.bet/api/wallets',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '. $token_plataforma
                    ]
                ]
            );

            if ($response->getStatusCode() == 200) {
                $content = json_decode($response->getBody()->getContents());
                return round($content[0]->balance, 2);
            } 
        }
    }

    public function logar(string $usuario, string $senha)
    {
        $payload = ['username' => $usuario, 'password' => $senha];

        $client = new GuzzleClient();
        $response = $client->request(
            'PUT',
            'https://jon.bet/api/auth/password',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            $content = json_decode($response->getBody()->getContents());
            return $content->access_token;
        } else {
            throw new Exception("Login inválido, por favor refaça a operação."); 
        }
    }

    public function cores()
    {
        return [
            0  => 'white',
            1  => 'black',
            2  => 'red'
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        if ($usuario->modo_treinamento == 'Y') 
            return '';

        if ($valor > $usuario->ultimo_saldo)
            return 'saldo_insuficiente';

        $payload = [
            'color' => ['white' => 'Tie', 'red' => 'Banker', 'black' => 'Player'][$cor],
            'amount' => $valor
        ];

        $redis = new Client();
        $redis->set(
            "{$usuario->id}_jogar",
            json_encode($payload), 
            'EX', 
            10
        );

        return '';
    }
}