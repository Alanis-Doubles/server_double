<?php

use GuzzleHttp\Client;
use WSSC\WebSocketClient;
use GuzzleHttp\Psr7\Request;
use WSSC\Components\ClientConfig;

class TWeplay implements IDoublePlataforma
{
    private static $ultimo_sinal;

    public static function nome()
    {
        return 'Weplay';
    }

    public function teste() {
        $token = $this->logar('edson.alanis@gmail.com', 'Da2403vi@');

        TTransaction::openFake('double_joao');
        $object = new DoubleUsuario();
        $object->chat_id = 1234;
        $object->robo_iniciar = 'N';
        $object->robo_iniciar_apos_loss = 'N';
        $object->robo_processando_jogada = 'N';
        $object->token_acesso = TCrypto::encrypt(json_encode(['username' => 'edson.alanis@gmail.com', 'password' => 'Da2403vi@']), $object->chat_id);
        $object->token_plataforma = $token;
        $object->token_expiracao = date('Y-m-d H:i:s', strtotime('+3 hours'));
        TTransaction::close();

        echo $this->saldo($object);
    }

    public function aguardarSinal($ultimo_sinal)
    {
        self::$ultimo_sinal = $ultimo_sinal;
        $url = 'wss://api.weplay.games/socket.io/?EIO=4&transport=websocket';
        $config = new ClientConfig();
        $client = new WebSocketClient($url, $config);
        $client->send('40/game/roulette');
        while ($client->isConnected())
        {
            try {
                $message = $client->receive();
                
                if (str_starts_with($message, '42/game/roulette,')){
                    $message = str_replace('42/game/roulette,', '', $message);
                    $content = json_decode($message);
                    if (!$content)
                      continue;
                    if ($content[1]->status == 'Closed') {
                        $sinal = new stdClass;
                        $sinal->id = $content[1]->slug;
                        $sinal->cor = $content[1]->betColor;
                        $sinal->numero = $content[1]->betNumber;
                        if (self::$ultimo_sinal != (array) $sinal)
                        {
                            self::$ultimo_sinal = $sinal;
                            return self::$ultimo_sinal;              
                        } else {
                            // usleep(1000);
                        }
                    } else {
                        // usleep(100);
                    }
                }
                // $client->send('40/game/roulette');
                // $client->send('3');
            } catch (BadOpcodeException $e) {
                $erro = $e->getMessage();
            } catch (\Throwable $e) {
               $erro = $e->getMessage();   
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
                'https://api.weplay.games/api/v1/auth/login',
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
                $usuario->token_plataforma = $content->accessToken;
                $usuario->token_expiracao = date_format($now->modify('+3 hours'), 'Y-m-d H:i:s');
                $usuario->saveInTransaction('double');
                return $content->accessToken;
            }
        } else {
            return $usuario->token_plataforma;
        }
    }

    public function saldo(DoubleUsuario $usuario)
    {
        if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') {
            return DoubleConfiguracao::getConfiguracao('homologacao_saldo');
        } else {
            $token_plataforma = self::getToken($usuario);
            $client = new Client(['http_errors' => false]);
            $response = $client->request(
                'GET',
                'https://api.weplay.games/api/v1/wallets/by-user',
                [
                    'headers' => [
                        // 'Content-Type' => 'application/json',
                        // 'Accept' => 'application/json',
                        'Accept' => 'application/json, text/plain, */*',
                        'Authorization' => 'Bearer '. $token_plataforma
                    ]
                ]
            );

            $content = json_decode($response->getBody()->getContents());
            if ($response->getStatusCode() == 200) {
                return round($content->currentAccount->total, 2);
            } 
        }
    }

    public function logar(string $usuario, string $senha)
    {
        $payload = ['email' => $usuario, 'password' => $senha];

        $client = new Client();
        $response = $client->request(
            'POST',
            'https://api.weplay.games/api/v1/auth/login',
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
            return $content->accessToken;
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
        $id = null;

        // aguardar status watting para jogar
        $url = 'wss://api.weplay.games/socket.io/?EIO=4&transport=websocket';
        $config = new ClientConfig();
        $client = new WebSocketClient($url, $config);
        $client->send('40/game/roulette');
        while ($client->isConnected())
        {
            try {
                $message = $client->receive();
                
                if (str_starts_with($message, '42/game/roulette,')){
                    $message = str_replace('42/game/roulette,', '', $message);
                    $content = json_decode($message);
                    if (!$content)
                      continue;
                    if ($content[1]->status == 'Queue') {
                        $id = $content[1]->id;
                        break;
                    } else {
                        sleep(1);
                    } 
                }
            } catch (BadOpcodeException $e) {
                $erro = $e->getMessage();
            } catch (\Throwable $e) {
               $erro = $e->getMessage();   
            }
        }

        if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') 
            return '';

        $payload = [
            "amount" => 1,
            "roundId" => $id,
            "betColor" => ['red' => "RED", 'black' => "BLACK", 'white' => "WHITE"][$cor],
            "gameId" => 2,
            "coinId" => 1
        ];

        $token_plataforma = self::getToken($usuario);
        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'POST',
            'https://api.weplay.games/api/v1/roulette/enter-bet',
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
            if ($content->message == 'Saldo insuficiente para fazer esta aposta.') 
                return 'saldo_insuficiente';
            else 
                return $content->message;
        } else {
            return '';
        }
    }
}
