<?php

use GuzzleHttp\Client;
use WSSC\WebSocketClient;
use GuzzleHttp\Cookie\CookieJar;
use WSSC\Components\ClientConfig;

class TReals implements IDoublePlataforma
{
    private static $ultimo_sinal;

    public static function nome()
    {
        return 'Reals';
    }

    public function teste()
    {
        // $this->logar('edson.alanis@gmail.com', 'Da2403vi@');
        $request = new HTTP_Request2();
        $request->setUrl('https://onabet.com/api/client/clients:login-with-form');
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setCookieJar();
        $request->setConfig(array(
            'follow_redirects' => TRUE
        ));
        $request->setHeader(array(
            'Content-Type' => 'application/json'
        ));
        $request->setBody('{
        \n    "id": "5801:87eb250c-d646-4b5a-9409-3aa1225d2949",
        \n    "values": {
        \n        "CAPTCHA_INPUT": "",
        \n        "MULTICHANNEL": "edson.alanis@gmail.com",
        \n        "PASSWORD": "Da2403vi"
        \n    }
        \n}');
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                echo $response->getBody();
            } else {
                echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
                    $response->getReasonPhrase();
            }
        } catch (HTTP_Request2_Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function aguardarSinal($ultimo_sinal)
    {
        $config = new ClientConfig();
        $url = 'wss://games.casinogate.io/ws/DoubleDouble/';
        $client = new WebSocketClient($url, $config);
        // $client->send('eyJtc2dUeXBlIjoid2FsbGV0IiwidG9rZW4iOiJuYXNoLVZQNlN3VHptS0E1WFJCSEJrUlpYQ0tzYnlPVWNEMnlvIiwic2Vzc2lvbklEIjoiY2YwNzVhNjYtZDBkYy00MGM4LWFmOTQtNjAzZTU5Y2Q1ZGM5In0=');
        while ($client->isConnected()) {
            try {
                $message = $client->receive();

                if (str_starts_with($message, '42')) {
                    $message = str_replace('42', '', $message);
                    $content = json_decode($message);
                    if ($content[1]->status == 'Closed') {
                        $sinal = new stdClass;
                        $sinal->id = $content[1]->slug;
                        $sinal->cor = $content[1]->betColor;
                        $sinal->numero = $content[1]->betNumber;
                        if ($this->ultimo_sinal != $sinal) {
                            $this->ultimo_sinal = $sinal;
                            return $this->ultimo_sinal;
                        } else {
                            // usleep(1000);
                        }
                    } else {
                        usleep(1000);
                    }
                }
            } catch (BadOpcodeException $e) {
                $erro = $e->getMessage();
                echo $erro;
            } catch (\Throwable $e) {
                $erro = $e->getMessage();
                echo $erro;
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
        if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') {
            return 100;
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
                        'Authorization' => 'Bearer ' . $token_plataforma
                    ],
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
        $payload = [
            "id" => "2765:410251f2-74f5-4346-b8ea-bc05467ab3e9",
            "values" => [
                'CAPTCHA_INPUT' => '',
                'MULTICHANNEL' => $usuario,
                'PASSWORD' => $senha
            ]
        ];

        $client = new Client(['http_errors' => false]);
        $jar = new CookieJar;
        $response = $client->request(
            'POST',
            'https://m.realsbet.com/api/client/clients:login-with-form',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'cookies' => $jar
            ]
        );

        $content = json_decode($response->getBody()->getContents());
        if ($response->getStatusCode() == 200) {
            return $content->access_token;
        } else {
            echo json_encode($content);
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
        return '';
    }
}
