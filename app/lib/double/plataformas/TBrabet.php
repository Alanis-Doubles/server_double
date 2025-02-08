<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class TBrabet extends TDoublePlataforma
{
    private static $ultimo_sinal;

    public static function nome()
    {
        return 'Brabet';
    }

    public function possuiBancaTreinamento() {
        return false;
    }
    public function resetarBancaTreinamento(DoubleUsuario $usuario){}

    public function aguardarSinal($ultimo_sinal)
    {
        self::$ultimo_sinal = $ultimo_sinal;

        $token = DoubleConfiguracao::getConfiguracao('token_brabet');
        if (!$token)
          $gerarToken = true;
        else {
            if (TDoubleUtils::verificar_expiracao($token)) {
                $gerarToken = true;
            }
            $gerarToken = false;
        }

        if ($gerarToken) {
            $token = $this->logar('edson.alanis@gmail.com', 'Da2403vi');
            DoubleConfiguracao::setConfiguracao('token_brabet', $token);
        }
        $payload = ['token' => $token, 'type' => 3, 'language' => 'pt-pt'];

        $client = new Client(['http_errors' => false]);
        while (true)
        {
            $response = $client->request(
                'POST',
                'https://api.bbh5sdffi01.com/goldGame/double_history',
                [
                    'json' => $payload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            $json = $response->getBody()->getContents();
            if ($response->getStatusCode() == 200) {
                $content = json_decode($json);
                $sinal = new stdClass;
                $sinal->id = implode('',$content->data);
                $sinal->numero = $content->data[0];
                $sinal->cor = $this->cores()[$sinal->numero];
                if (self::$ultimo_sinal != (array) $sinal)
                {
                    self::$ultimo_sinal = $sinal;
                    return self::$ultimo_sinal;              
                } else {
                    sleep(1);
                }
            } else {
               //  DoubleErros::registrar(1, 'TBlaze', 'aguardarSinal', 'Tentando reinniciar', $json);
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
                'POST',
                'https://api.bbh5sdffi01.com/login/login',
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
        // if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') {
        //     return DoubleConfiguracao::getConfiguracao('homologacao_saldo');
        // } else {
        //     $token_plataforma = self::getToken($usuario);
        //     $client = new Client();
        //     $response = $client->request(
        //         'GET',
        //         'https://blaze.com/api/wallets',
        //         [
        //             'headers' => [
        //                 'Content-Type' => 'application/json',
        //                 'Accept' => 'application/json',
        //                 'Authorization' => 'Bearer '. $token_plataforma
        //             ]
        //         ]
        //     );

        //     if ($response->getStatusCode() == 200) {
        //         $content = json_decode($response->getBody()->getContents());
        //         return round($content[0]->balance, 2);
        //     } 
        // }
    }

    public function logar(string $usuario, string $senha)
    {
        $payload = ['account' => $usuario, 'password' => $senha, 'login_type' => 2];

        $client = new Client();
        $response = $client->request(
            'POST',
            'https://api.bbh5sdffi01.com/login/login',
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
            return $content->data->token;
        } else {
            throw new Exception("Login inválido, por favor refaça a operação."); 
        }
    }

    public function cores()
    {
        return [
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
            14 => 'black',
            15 => 'white'
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        // // aguardar status watting para jogar
        // $client = new Client(['http_errors' => false]);
        // while (true)
        // {
        //     $response = $client->request(
        //         'GET',
        //         'https://blaze.com/api/roulette_games/current',
        //         [
        //             'headers' => [
        //                 'Content-Type' => 'application/json',
        //                 'Accept' => 'application/json'
        //             ]
        //         ]
        //     );

        //     if ($response->getStatusCode() == 200) {
        //         $content = json_decode($response->getBody()->getContents());
        //         if ($content->status == 'waiting') {
        //             break;
        //         } else {
        //             sleep(1);
        //         }
        //     }

        // }

        // if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') 
        //     return '';


        // $token_plataforma = self::getToken($usuario);

        // $response = $client->request(
        //     'GET',
        //     'https://blaze.com/api/wallets',
        //     [
        //         'headers' => [
        //             'Content-Type' => 'application/json',
        //             'Accept' => 'application/json',
        //             'Authorization' => 'Bearer '. $token_plataforma
        //         ]
        //     ]
        // );

        // $content = json_decode($response->getBody()->getContents());
        // if ($response->getStatusCode() != 200) {
        //     return $content->error->message;
        // } 

        // $payload = [
        //     'amount' => $valor,
        //     'color' => ['red' => 1, 'black' => 2, 'white' => 0][$cor],
        //     'free_bet' => false,
        //     'wallet_id' => $content[0]->id
        // ];

        // $response = $client->request(
        //     'POST',
        //     'https://blaze.com/api/roulette_bets',
        //     [
        //         'json' => $payload,
        //         'headers' => [
        //             'Content-Type' => 'application/json',
        //             'Accept' => 'application/json',
        //             'Authorization' => 'Bearer '. $token_plataforma
        //         ]
        //     ]
        // );

        // if ($response->getStatusCode() != 200) {
        //     $content = json_decode($response->getBody()->getContents());
        //     if ($content->error->code == '1010') 
        //         return 'saldo_insuficiente';
        //     elseif ($content->error->code == '1005') 
        //         return 'saldo_insuficiente';
        //     else 
        //         return $content->error->message;
        // } else {
        //     return '';
        // }
    }

    public function sinalCorrente(){
        return '';
    }
}
