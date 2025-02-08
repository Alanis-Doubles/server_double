<?php

use GuzzleHttp\Client;
use WebSocket\Client as WebSocketClient;

class TAvalon extends TDoublePlataforma
{
    private static $ultimo_sinal;

    public static function validate(string $nome)
    {
        return substr($nome, 0, 6) == 'Avalon';
    }

    public static function nome()
    {
        return 'Avalon';
    }

    public function sinalCorrente() {}

    public function aguardarSinal($ultimo_sinal) {}

    public function ultimoSinal()
    {
        return self::$ultimo_sinal->numero;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        $expiracao = date_create_from_format('Y-m-d H:i:s', $usuario->token_expiracao);
        $now = new DateTime();
        if ($now > $expiracao) {
            $payload = $usuario->token_acesso;
            $payload = (array)json_decode($payload);

            // $payload = ['userId' => $payload['email']];

            $client = new Client();
            $response = $client->request(
                'POST',
                'http://180.149.34.86:3001/api/usuarios/auth',
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
    
    public function resetarBancaTreinamento(DoubleUsuario $usuario)
    {
        $payload = [
            "apiKey" => self::getToken($usuario)
        ];
        $client = new Client(['http_errors' => false]);

        $response = $client->request(
            'POST',
            'http://180.149.34.86:3001/api/usuarios/balance/reset_demo',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            $content = json_decode($response->getBody()->getContents());
            return $content->balance;
        } else {
            throw new Exception("Login inválido, por favor refaça a operação."); 
        }
    }

    public function saldo(DoubleUsuario $usuario)
    {
        $payload = [
            "apiKey" => self::getToken($usuario),
            "type" => $usuario->modo_treinamento == 'Y' ? "demo" : "real"
        ];

        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'POST',
            'http://180.149.34.86:3001/api/usuarios/balance',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {    
            $content = json_decode($response->getBody()->getContents());
            return $content->balance ;
        } elseif ($response->getStatusCode() == 500) {
            $usuario->token_expiracao = null;
            $usuario->saveInTransaction();
            return $this->saldo($usuario);
        }
    }

    public function logar(string $usuario, string $senha)
    {
        $payload = ['username' => $usuario, 'password' => $senha];

        $client = new Client();
        $response = $client->request(
            'POST',
            'http://180.149.34.86:3001/api/usuarios/auth',
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

    public function jogarAPI(DoubleUsuario $usuario, $params)
    {
        $payload = $params;
        $payload["userId"] = $usuario->id;

        $client = new Client();
        $response = $client->request(
            'POST',
            'http://180.149.34.86:3001/api/usuarios/buyorsell',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            json_decode($response->getBody()->getContents());
        } else {
            throw new Exception("Login inválido, por favor refaça a operação."); 
        }
    }

    public function possuiBancaTreinamento() {
        return true;
    }

    public function iniciar($usuario) {
        $usuario->robo_status = 'EXECUTANDO';
        if (!isset($param['nao_reseta_inicio']))
            $usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
        $usuario->saveInTransaction();

            
        if (substr(php_uname(), 0, 7) == "Windows") 
        {
            $redis_param = [
                'usuario_id' => $usuario->id
            ];
            // php cmd.php "class=TAvalonUsuarioConsumer&method=run&usuario_id=7"
            TUtils::cmd_run('TAvalonUsuarioConsumer', 'run', $redis_param);

            return;
        }

        //echo "Configurando arquivo supervisor\n";
        DoubleErros::registrar(2, 'TAvalon', 'iniciar', 'Configurando arquivo supervisor', 'Configurando arquivo supervisor');
        $usuario_id = $usuario->id;

        $path_supervisor = "/opt/docker/etc/supervisor.d/";
        $server_root = "/var/www/html"; 
        $log_supervisor = "/var/log/supervisor"; 
        $server_name = "profit"; 
        
        $filename = "{$path_supervisor}{$server_name}_usuario_{$usuario_id}.conf";
        if (file_exists($filename))
            return "{$path_supervisor}{$server_name}_usuario_{$usuario_id}.conf";

        $usuarioConfig = "[program:{$server_name}_usuario_{$usuario_id}_consumer]\n";
        $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TAvalonUsuarioConsumer&method=run&usuario_id={$usuario_id}&server_name={$server_name}'\n";
        $usuarioConfig .= "autostart=true\n";
        $usuarioConfig .= "autorestart=true\n";
        $usuarioConfig .= "stdout_logfile={$log_supervisor}/{$server_name}_usuario_{$usuario_id}_consumer.out.log\n";
        $usuarioConfig .= "numprocs=1\n";
        $usuarioConfig .= "\n";
        // $usuarioConfig .= "[program:{$server_name}_usuario_{$usuario_id}_sinais_consumer]\n";
        // $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TAvalonUsuarioConsumer&method=run&usuario_id={$usuario_id}'\n";
        // $usuarioConfig .= "autostart=true\n";
        // $usuarioConfig .= "autorestart=true\n";
        // $usuarioConfig .= "stdout_logfile={$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log\n";
        // $usuarioConfig .= "numprocs=1\n";
        // $usuarioConfig .= "\n";

        $criado = file_put_contents($filename, $usuarioConfig);
        //echo "Arquivo supervisor criado: {$criado}\n";
        DoubleErros::registrar(2, 'TAvalon', 'iniciar', 'Arquivo supervisor criado', 'Arquivo supervisor criado -' . $criado);
        return '';
    }

    public function finalizar($usuario)
    {
        if (substr(php_uname(), 0, 7) == "Windows") 
        {
            return;
        }

        //echo "Finalizando arquivo supervisor\n";
        $usuario_id = $usuario->id;

        $path_supervisor = "/opt/docker/etc/supervisor.d/";
        $server_root = "/var/www/html"; 
        $log_supervisor = "/var/log/supervisor"; 
        $server_name = "profit"; 
        
        $filename = "{$path_supervisor}{$server_name}_usuario_{$usuario_id}.conf";
        if (file_exists($filename))
            unlink($filename);

        // $filename = "{$log_supervisor}/{$server_name}_usuario_{$usuario_id}_consumer.out.log";
        // if (file_exists($filename))
        //     unlink($filename);

        sleep(2); // Espera 2 segundos para finalizar o arquivo

        // $filename = "{$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log";
        // if (file_exists($filename))
        //     unlink($filename);    
        //echo "Arquivo superfisor finalizado\n";
    }
}
