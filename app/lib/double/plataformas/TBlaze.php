<?php

use GuzzleHttp\Client;
use Predis\Client as RedisClient;
use WebSocket\Client as WebSocketClient;

class TBlaze implements IDoublePlataforma
{
    private static $ultimo_sinal;

    public static function validate(string $nome)
    {
        return substr($nome, 0, 5) == 'Blaze';
    }

    public static function nome()
    {
        return 'Blaze';
    }

    public function sinalCorrente() {
        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'GET',
            // 'https://blaze.com/api/roulette_games/current',
            'https://blaze.com/api/singleplayer-originals/originals/roulette_games/current/1',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36'
                ]
            ]
        );

        if ($response->getStatusCode() == 200)
            return [
                "status_code" => 200,
                "data" => json_decode($response->getBody()->getContents())
            ];
        else
           return [
                "status_code" => $response->getStatusCode(),
                "data" => []
            ];
    }

    public function aguardarSinal($ultimo_sinal)
    {
        self::$ultimo_sinal = $ultimo_sinal;
        $client = new Client(['http_errors' => false]);
        while (true)
        {
            $response = $client->request(
                'GET',
                // 'https://blaze.com/api/roulette_games/current',
                'https://blaze.com/api/singleplayer-originals/originals/roulette_games/current/1',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36'
                    ]
                ]
            );

            $json = $response->getBody()->getContents();
            // DoubleErros::registrar(1, 'TBlaze', 'aguardarSinal', $response->getStatusCode(), $json);

            if ($response->getStatusCode() == 200) {
                $content = json_decode($json);
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
                } else {
                    // sleep(1);
                }
            } else {
                DoubleErros::registrar(1, 'TBlaze', 'aguardarSinal', 'Tentando reinniciar', $json);
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
                'https://blaze.com/api/auth/password',
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
        if ($usuario->modo_treinamento == 'Y') {
            // return DoubleConfiguracao::getConfiguracao('homologacao_saldo');
            return $usuario->banca_treinamento;
        } else {
            $token_plataforma = self::getToken($usuario);
            $client = new Client();
            $response = $client->request(
                'GET',
                'https://blaze.com/api/wallets',
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
            'https://blaze.com/api/auth/password',
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
            $response = $client->request(
                'GET',
                // 'https://blaze.com/api/roulette_games/current',
                'https://blaze.com/api/singleplayer-originals/originals/roulette_games/current/1',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36'
                    ]
                ]
            );

            if ($response->getStatusCode() == 200) {
                $content = json_decode($response->getBody()->getContents());
                if ($content->status == 'waiting') {
                    break;
                } else {
                    sleep(1);
                }
            }

        }

        // if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') 
        if ($usuario->modo_treinamento == 'Y') 
            return '';

        $token_plataforma = self::getToken($usuario);

        $response = $client->request(
            'GET',
            'https://blaze.com/api/users/me',
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
        $user_name = $content->username;

        $response = $client->request(
            'GET',
            'https://blaze.com/api/wallets',
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
            'wallet_id' => $content[0]->id,
            'user_name' => $user_name
        ];

        $json_payload = json_encode($payload);
        echo "url: https://blaze.com/api/singleplayer-originals/originals/roulette_bets\npayload: {$json_payload}\n";
        $response = $client->request(
            'POST',
            // 'https://blaze.com/api/roulette_bets',
            'https://blaze.com/api/singleplayer-originals/originals/roulette_bets',
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
            else*/if ($content->error->code == '1005') 
                return 'saldo_insuficiente';
            else 
                return $content->error->message;
        } else {
            return '';
        }
    }

    public function buscar_sinais($param){
        // $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        // $serverName = DoubleConfiguracao::getConfiguracao('server_name');
        // $queue = strtolower("{$serverName}_{$plataforma->nome}_{$plataforma->idioma}_buscar_sinais");

        // $redis = new RedisClient();

        try {
            $client = new WebSocketClient("wss://api-gaming.blaze1.space/replication/?EIO=3&transport=websocket");
        
            $count_waiting = 0;
            $is_roll = false;
            while (true) {
                try {
                    // Receber mensagem do servidor
                    $message = $client->receive();

                    // echo "$message\n\n";

                     // Verificar o prefixo da mensagem
                     if (!$message)
                        continue;

                    if (substr($message, 0, 2) == '42') {
                        $message = substr($message, 2); // Remover o '42'

                        // Decodificar a mensagem como JSON
                        $decodedMessage = json_decode($message);

                        if (isset($decodedMessage[1]) && $decodedMessage[1]->id === "double.tick") {
                            if ($decodedMessage[1]->payload->status === 'waiting') {
                                if ($count_waiting >= 10) {
                                    // sleep(1);
                                    continue;
                                }

                                // $redis->publish($queue, 'Fazer entrada');

                                echo "Fazer entrada\n";
                                $count_waiting += 1;
                                $is_roll = false;
                                sleep(1);
                            } elseif ($decodedMessage[1]->payload->status === 'rolling') {
                                // Extrair os dados do objeto  
                                if ($is_roll) {
                                    // sleep(1);
                                    continue;
                                }    
                            
                                // Acessar os valores
                                $id =$decodedMessage[1]->payload->id;
                                $roll = $decodedMessage[1]->payload->roll;
                                $color = $decodedMessage[1]->payload->color;

                                $payload = [
                                    'id'    => $id,
                                    'roll'  => $roll,
                                    'color' => $color
                                ];
                                // $redis->publish($queue, json_encode($payload));

                                // Tratar os valores (exemplo: exibir no terminal)
                                echo "\nId: $id\n";
                                echo "Roll: $roll\n";
                                echo "Color: $color\n";
                                
                                $count_waiting = 0;
                                $is_roll = true;
                            }
                        }
                    } elseif (substr($message, 0, 1) == '0') {
                        $client->send('421["cmd",{"id":"subscribe","payload":{"room":"double_v2"}}]');
                    }
                    // sleep(1);
                } catch (Exception $e) {
                    // Caso haja um erro, exibir e sair do loop
                    echo "Erro ao receber mensagem: " . $e->getMessage() . "\n";
                    break;
                }
            }
        
            // Fechar a conexão quando o loop for interrompido
            $client->close();
        
        } catch (Exception $e) {
            echo "Erro de conexão: " . $e->getMessage();
        }
    }
}
