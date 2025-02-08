<?php

use GuzzleHttp\Client;
use WSSC\WebSocketClient;
use WSSC\Components\ClientConfig;

class TArbety extends TDoublePlataforma
{
    private $ultimo_sinal;

    private $ooop;
    private $connector;
    private $app;

    // public function __construct()
    // {
    //     $this->loop = Loop::get();
    //     $this->connector = new Connector($this->loop);

    //     $this->app = function (WebSocket $conn) {
    //         $conn->on('message', function (MessageInterface $msg) use ($conn) {
    //             echo "Received: {$msg}\n";
    //             $conn->close();
    //         });

    //         $conn->on('close', function ($code = null, $reason = null) {
    //             echo "Connection closed ({$code} - {$reason})\n";

    //             //in 3 seconds the app will reconnect
    //             $this->loop->addTimer(3, function () {
    //                 $this->connectToServer($this->connector, $this->loop, $this->app);
    //             });
    //         });

    //         $conn->on('error', function ($error) {
    //             echo "Error\n";
    //         });

    //         $conn->send('3');
    //     };

    //     $this->connectToServer($this->connector, $this->loop, $this->app);

    //     // $this->loop->run();
    // }

    // private function connectToServer($connector, $loop, $app)
    // {
    //     $header = [
    //         'Pragma' => 'no-cache',
    //         'Origin' => 'https://www.arbety.com',
    //         'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
    //         'Sec-WebSocket-Key' => 'sGeq1spHvYoPKLWg3SlvuQ==',
    //         'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    //         'Upgrade' => 'websocket',
    //         'Cache-Control' => 'no-cache',
    //         'Connection' => 'Upgrade',
    //         'Sec-WebSocket-Version' => '13',
    //         'Sec-WebSocket-Extensions' => 'permessage-deflate; client_max_window_bits'
    //       ];
    //     $connector('wss://arbety.eway.dev:3010/socket.io/?EIO=4&transport=websocket&sid=MqzsZmEEaUA1TspJG6xM', [], $header)
    //         ->then($app, function (\Exception $e) use ($loop) {
    //             echo "Could not connect: {$e->getMessage()}\n";
    //             $loop->stop();
    //         });
    // }

    public static function nome()
    {
        return 'Arbety';
    }

    private function generateRandomString($length = 10)
    {
        return 'Opep_' .
            substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    public function aguardarSinal($ultimo_sinal)
    {
        $client = new Client(['http_errors' => false, 'decode_content' => false, 'verify' => false]);

        // $headers = [
        //     'Accept' => 'application/json',
        //     'Content-Type' => 'application/json'
        //   ];
        // $request = new Request('GET', 'https://arbety.eway.dev:3010/socket.io/?EIO=4&transport=polling&t=Opep_dt', $headers);
        // $res = $client->sendAsync($request)->wait();
        // $content = $res->getBody()->getContents();

        
        // $url = 'wss://api.weplay.games/socket.io/?EIO=4&transport=websocket';
        // $opt =  [
        //     // 'context' => $context,
        //     'headers' => [
        //         // 'Pragma' => 'no-cache',
        //         // 'Host' => 'arbety.eway.dev:3010', 
        //         // 'Origin' => 'https://www.arbety.com',
        //         // 'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
        //         // 'Sec-WebSocket-Key' => 'tPIgsmgfcLctBaYVM0CCWg==',
        //         // 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        //         // 'Upgrade' => 'websocket',
        //         // 'Cache-Control' => 'no-cache',
        //         // 'Connection' => 'Upgrade',
        //         // 'Sec-WebSocket-Version' => '13',
        //         // 'Sec-WebSocket-Extensions' => 'permessage-deflate; client_max_window_bits'
        //     ],
        //     // 'return_obj' => false
        // ];

        // $config = new ClientConfig();
        // $client = new WebSocketClient($url, $config);
        // // $client->send('{"user_id" : 123}');
        // while ($client->isConnected())
        // {
        //     try {
        //         $message = $client->receive();
                
        //         if (str_starts_with($message, '42')){
        //             $message = str_replace('42', '', $message);
        //             $content = json_decode($message);
        //             if ($content[1]->status == 'Closed') {
        //                 $sinal = new stdClass;
        //                 $sinal->id = $content[1]->slug;
        //                 $sinal->cor = $content[1]->betColor;
        //                 $sinal->numero = $content[1]->betNumber;
        //                 if ($this->ultimo_sinal != $sinal)
        //                 {
        //                     $this->ultimo_sinal = $sinal;
        //                     return $this->ultimo_sinal;              
        //                 } else {
        //                     // usleep(1000);
        //                 }
        //             } else {
        //                 usleep(1000);
        //             }
        //         }
        //         $client->send('40/game/roulette');
        //     } catch (BadOpcodeException $e) {
        //         $erro = $e->getMessage();
        //     } catch (\Throwable $e) {
        //        $erro = $e->getMessage();   
        //     }
        // }



        // while (true)
        // {
        $t = $this->generateRandomString(3);
        $response = $client->request(
            'GET',
            'https://arbety.eway.dev:3010/socket.io/?EIO=4&transport=polling&t=' . $t,

            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );
        

        $content = $response->getBody()->getContents();
        if ($response->getStatusCode() == 200) {
            $content = json_decode(substr($content, 1));
            $sid = $content->sid;

            $response = $client->request(
                'POST',
                'https://arbety.eway.dev:3010/socket.io/?EIO=4&transport=polling&t=' . $t .'&sid=' . $sid,

                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            $content = $response->getBody()->getContents();
            if ($response->getStatusCode() == 200) {
                // $response = $client->request(
                //     'GET',
                //     'https://arbety.eway.dev:3010/socket.io/?EIO=4&transport=polling&t=Opep_dt&sid='.$sid,

                //     [
                //         'headers' => [
                //             'Accept' => '*/*',
                //             'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
                //             'Connection' => 'keep-alive',
                //             'Origin' => 'https://www.arbety.com',
                //             'Referer' => 'https://www.arbety.com/',
                //             'Sec-Fetch-Dest' => 'empty',
                //             'Sec-Fetch-Mode' => 'cors',
                //             'Sec-Fetch-Site' => 'cross-site',
                //             'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                //             'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                //             'sec-ch-ua-mobile' => '?0',
                //             'sec-ch-ua-platform' => '"Windows"'
                //         ]
                //     ]
                // );

                // $headers = [
                //     'Accept' => '*/*',
                //     'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
                //     'Connection' => 'keep-alive',
                //     'Origin' => 'https://www.arbety.com',
                //     'Referer' => 'https://www.arbety.com/',
                //     'Sec-Fetch-Dest' => 'empty',
                //     'Sec-Fetch-Mode' => 'cors',
                //     'Sec-Fetch-Site' => 'cross-site',
                //     'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                //     'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                //     'sec-ch-ua-mobile' => '?0',
                //     'sec-ch-ua-platform' => '"Windows"'
                // ];
                // $request = new Request('GET', 'https://arbety.eway.dev:3010/socket.io/?EIO=4&transport=polling&t=' . $t .'&sid=' . $sid, $headers);
                // $response = $client->sendAsync($request)->wait();
                // // echo $res->getBody();


                // $content = $response->getBody()->getContents();
                if ($response->getStatusCode() == 200) {
                    // // $context = stream_context_create();
                    // // stream_context_set_option($context, 'ssl', 'verify_peer', false);
                    // // stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
                    // $url = 'wss://arbety.eway.dev:3010/socket.io/?EIO=4&transport=websocket&sid=' . $sid;
                    // $opt =  [
                    //     // 'context' => $context,
                    //     'headers' => [
                    //         // 'Pragma' => 'no-cache',
                    //         // 'Host' => 'arbety.eway.dev:3010', 
                    //         // 'Origin' => 'https://www.arbety.com',
                    //         // 'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
                    //         // 'Sec-WebSocket-Key' => 'tPIgsmgfcLctBaYVM0CCWg==',
                    //         // 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    //         // 'Upgrade' => 'websocket',
                    //         // 'Cache-Control' => 'no-cache',
                    //         // 'Connection' => 'Upgrade',
                    //         // 'Sec-WebSocket-Version' => '13',
                    //         // 'Sec-WebSocket-Extensions' => 'permessage-deflate; client_max_window_bits'
                    //     ],
                    //     // 'return_obj' => false
                    // ];

                    // $client = new WebSocket\Client($url, $opt);
                    // while (true) {
                    //     try {
                    //         $message = $client->receive();
                    //         if (!$client->isConnected())
                    //             break;
                    //         // Act on received message
                    //         // Break while loop to stop listening
                    //     } catch (\WebSocket\ConnectionException $e) {
                    //         // Possibly log errors
                    //         if (!$client->isConnected())
                    //             break;
                    //     }
                    // }
                    // $client->close();

                    $config = new ClientConfig();
                    $url = 'wss://arbety.eway.dev:3010/socket.io/?EIO=4&transport=websocket';//&sid=' . $sid;
                    $client = new WebSocketClient($url, $config);
                    // $client->send('{"user_id" : 123}');
                    while ($client->isConnected())
                    {
                        try {
                            $message = $client->receive();
                            $client->send('2');
                            
                            if (str_starts_with($message, '42')){
                                $message = str_replace('42', '', $message);
                                $content = json_decode($message);
                                if ($content[1]->status == 'Closed') {
                                    $sinal = new stdClass;
                                    $sinal->id = $content[1]->slug;
                                    $sinal->cor = $content[1]->betColor;
                                    $sinal->numero = $content[1]->betNumber;
                                    if ($this->ultimo_sinal != $sinal)
                                    {
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
                        } catch (\Throwable $e) {
                        $erro = $e->getMessage();   
                        }
                    }
                }
            }
        }
        //         if ($content->status == 'rolling') {
        //             $sinal = new stdClass;
        //             $sinal->id = $content->id;
        //             $sinal->cor = $content->color;
        //             $sinal->numero = $content->roll;
        //             if ($this->ultimo_sinal != $sinal)
        //             {
        //                 $this->ultimo_sinal = $sinal;
        //                 return $this->ultimo_sinal;              
        //             }
        //         } else {
        //             sleep(1);
        //         }
        //     }

        // }



    }

    public function ultimoSinal()
    {
        return $this->ultimo_sinal->numero;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        // $expiracao = date_create_from_format('Y-m-d H:i:s', $usuario->token_expiracao);
        // $now = new DateTime();
        // if ($now > $expiracao) {
        //     $payload = TCrypto::decrypt($usuario->token_acesso, $usuario->chat_id);
        //     $payload = (array)json_decode($payload);

        //     $client = new Client();
        //     $response = $client->request(
        //         'PUT',
        //         'https://blaze.com/api/auth/password',
        //         [
        //             'json' => $payload,
        //             'headers' => [
        //                 'Content-Type' => 'application/json',
        //                 'Accept' => 'application/json'
        //             ]
        //         ]
        //     );

        //     if ($response->getStatusCode() == 200) {
        //         $content = json_decode($response->getBody()->getContents());
        //         $usuario->token_plataforma = $content->access_token;
        //         $usuario->token_expiracao = date_format($now->modify('+3 hours'), 'Y-m-d H:i:s');
        //         $usuario->saveInTransaction('double');
        //         return $content->token_plataforma;
        //     }
        // } else {
        //     return $usuario->token_plataforma;
        // }
    }

    public function saldo(DoubleUsuario $usuario)
    {
        // $token_plataforma = self::getToken($usuario);
        // $client = new Client();
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

        // if ($response->getStatusCode() == 200) {
        //     $content = json_decode($response->getBody()->getContents());
        //     return $content[0]->balance;
        // } 
    }

    public function logar(string $usuario, string $senha)
    {
        // $payload = ['username' => $usuario, 'password' => $senha];

        // $client = new Client();
        // $response = $client->request(
        //     'PUT',
        //     'https://blaze.com/api/auth/password',
        //     [
        //         'json' => $payload,
        //         'headers' => [
        //             'Content-Type' => 'application/json',
        //             'Accept' => 'application/json'
        //         ]
        //     ]
        // );

        // if ($response->getStatusCode() == 200) {
        //     $content = json_decode($response->getBody()->getContents());
        //     return $content->access_token;
        // } else {
        //     throw new Exception("Login inválido, por favor refaça a operação."); 
        // }
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

    public function sinalCorrente() {
        return '';
    }

    public function possuiBancaTreinamento() {
        return false;
    }
    
    public function resetarBancaTreinamento(DoubleUsuario $usuario){}
}
