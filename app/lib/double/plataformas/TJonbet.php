<?php

use GuzzleHttp\Client;

class TJonbet implements IDoublePlataforma
{
    private static $ultimo_sinal;

    public static function validate(string $nome)
    {
        return substr($nome, 0, 6) == 'Jonbet';
    }

    public static function nome()
    {
        return 'Jonbet';
    }

    public function sinalCorrente() {
        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'GET',
            'https://jon.bet/api/singleplayer-originals/originals/roulette_games/current/1',
            // 'https://jon.bet/api/roulette_games/current',
            
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        return [
            "status_code" => $response->getStatusCode(),
            "data" => json_decode($response->getBody()->getContents())
        ];
    }

    public function aguardarSinal($ultimo_sinal)
    {
        self::$ultimo_sinal = $ultimo_sinal;
        $client = new Client(['http_errors' => false]);
        while (true)
        {
            $response = $this->sinalCorrente();
            $content = $response['data'];

            if ($response['status_code'] == 200) {
                if ($content->status == 'rolling') {
                    $sinal = new stdClass;
                    $sinal->id = $content->id;
                    $sinal->cor = $content->color;
                    $sinal->numero = $content->roll;
                    if (self::$ultimo_sinal != (array) $sinal)
                    {
                        self::$ultimo_sinal = $sinal;
                        return self::$ultimo_sinal;              
                    }
                }
            } else {
                DoubleErros::registrar(1, 'TJonbet', 'aguardarSinal', 'Tentando reinniciar', json_encode($content));
                $client = new Client(['http_errors' => false]);
                sleep(1);
            }

        }
    }

    public function ultimoSinal()
    {
        return self::$ultimo_sinal->numero;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        $expiracao = date_create_from_format('Y-m-d H:i:s', $usuario->token_expiracao);
        $now = new DateTime();
        if ($now > $expiracao) {
            $payload = TCrypto::decrypt($usuario->token_acesso, $usuario->chat_id);
            $payload = (array)json_decode($payload);

            $client = new Client();
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
            // return DoubleConfiguracao::getConfiguracao('homologacao_saldo');
            return $usuario->banca_treinamento;
        } else {
            $token_plataforma = self::getToken($usuario);
            $client = new Client();
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

        $client = new Client();
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
            1  => 'red',
            2  => 'red',
            3  => 'red',
            4  => 'red',
            5  => 'red',
            6  => 'red',
            7  => 'red',
            8  => 'black',
            9  => 'black',
            10 => 'black',
            11 => 'black',
            12 => 'black',
            13 => 'black',
            14 => 'black'
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        // aguardar status watting para jogar
        $client = new Client(['http_errors' => false]);
        while ($usuario->canal->espera_entrada == 'Y')
        {
            $response = $this->sinalCorrente();

            if ($response['status_code'] == 200) {
                $content = $response['data'];
                if ($content->status == 'waiting') {
                    break;
                } else {
                    sleep(1);
                }
            }

        }

        if ($usuario->modo_treinamento == 'Y') 
            return '';


        $token_plataforma = self::getToken($usuario);

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

        $content = json_decode($response->getBody()->getContents());
        if ($response->getStatusCode() != 200) {
            return $content->error->message;
        } 

        $payload = [
            'amount' => $valor,
            'color' => ['red' => 1, 'black' => 2, 'white' => 0][$cor],
            'currency_type' => $content[0]->currency_type,
            'free_bet' => false,
            'room_id' => 1,
            'wallet_id' => $content[0]->id
        ];

        $response = $client->request(
            'POST',
            'https://jon.bet/api/singleplayer-originals/originals/roulette_bets',
            // 'https://jon.bet/api/roulette_bets',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '. $token_plataforma
                ]
            ]
        );

        if ($response->getStatusCode() != 200) {
            $content = json_decode($response->getBody()->getContents());
            /*if ($content->error->code == '1010') 
                return 'saldo_insuficiente';
            else*/ if ($content->error->code == '1005') 
                return 'saldo_insuficiente';
            else 
                return $content->error->message;
        } else {
            return '';
        }
    }
}
