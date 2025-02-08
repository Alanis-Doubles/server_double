<?php

class TDoublePlataforma implements IDoublePlataforma
{
    public static function nome()
    {
        return 'Robo';
    }

    public function aguardarSinal($ultimo_sinal)
    {
        return false;
    }

    public function ultimoSinal()
    {
        return null;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        return null;
    }

    public function saldo(DoubleUsuario $usuario)
    {
        return null;
    }

    public function logar(string $usuario, string $senha)
    {
        return false;
    }

    public function cores()
    {
        return null;
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        return null;
    }

    public function jogarAPI(DoubleUsuario $usuario, $params)
    {
        return null;
    }

    public function sinalCorrente()
    {
        return null;
    }

    public function possuiBancaTreinamento()
    {
        return false;
    }

    public function resetarBancaTreinamento(DoubleUsuario $usuario)
    {
        return false;
    }

    public function iniciar($usuario)
    {
        $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        if ($use_redis == 'Y') {
            $usuario->robo_status = 'EXECUTANDO';
            if (!isset($param['nao_reseta_inicio']))
                $usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
            $usuario->saveInTransaction();

            $redis_param = [
                'usuario_id' => $usuario->id
            ];
            
            if (substr(php_uname(), 0, 7) == "Windows") 
            {
                // php cmd.php "class=TDoubleUsuarioConsumer&method=run&usuario_id=7"
                TUtils::cmd_run('TDoubleUsuarioConsumer', 'run', $redis_param);
                if (substr($usuario->plataforma->nome, 0, 5) == "Bacbo") {
                    $bacbo_plataforma = strtolower(substr($usuario->plataforma->nome, 5));
                    $caminho = "C:/Users/edson/Downloads/bacbo/sala_bacbo-main";
                    $arquivo = "{$bacbo_plataforma}/{$bacbo_plataforma}_bacbo_usuario.py";
                    $command = "{$caminho}/venv/Scripts/python {$caminho}/{$arquivo} {$usuario->id}";

                    // pclose(popen("start /B " . $command, "r"));
                }

                return;
            }
        } else {
            $data = new stdClass;
            $data->usuario_id = $usuario->id;
            $data->plataforma_id = $usuario->plataforma->id;
            $data->tipo = 'cmd';
            $data->inicio = true;
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);
            return;
        }

        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $usuario_id = $usuario->id;

        $filename = "/etc/supervisor/conf.d/{$server_name}_usuario_{$usuario_id}.conf";
        if (file_exists($filename))
            return "{$server_name}_usuario_{$usuario_id}_";

        $server_root = DoubleConfiguracao::getConfiguracao('server_root');

        $usuarioConfig = "[program:{$server_name}_usuario_{$usuario_id}_consumer]\n";
        $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TDoubleUsuarioConsumer&method=run&usuario_id={$usuario_id}&server_name={$server_name}'\n";
        $usuarioConfig .= "autostart=true\n";
        $usuarioConfig .= "autorestart=true\n";
        $usuarioConfig .= "stdout_logfile={$server_root}/logs/{$server_name}_usuario_{$usuario_id}_consumer.out.log\n";
        $usuarioConfig .= "numprocs=1\n";
        $usuarioConfig .= "\n";
        $usuarioConfig .= "[program:{$server_name}_usuario_{$usuario_id}_sinais_consumer]\n";
        $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id={$usuario_id}'\n";
        $usuarioConfig .= "autostart=true\n";
        $usuarioConfig .= "autorestart=true\n";
        $usuarioConfig .= "stdout_logfile={$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log\n";
        $usuarioConfig .= "numprocs=1\n";
        $usuarioConfig .= "\n";

        if (substr($usuario->plataforma->nome, 0, 5) == "Bacbo") {
            if ($usuario->modo_treinamento == 'N')
            {
                $client = new Client(['http_errors' => false]);
                $response = $client->request(
                    'GET',
                    "http://180.149.34.85:5001/usuario/{$usuario_id}/iniciar"
                );
            
                if ($response->getStatusCode() == 200) {    
                    $content = json_decode($response->getBody()->getContents());
                
                    $usuario->servidor_conectado = $content->server;
                    $usuario->saveInTransaction();
                } else {
                    $content = json_decode($response->getBody()->getContents());
                   //  DoubleErros::registrar('1', 'TDoubleRobo', 'iniciar', $response->getStatusCode(), "servidor: {$content->server}");
                }
            }
        }

        $criado = file_put_contents($filename, $usuarioConfig);
        return '';
    }

    public function finalizar($usuario)
    {
        $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        if ($use_redis == 'N') {
            return;
        }

        if (substr(php_uname(), 0, 7) == "Windows") 
        {
            return;
        }

        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $usuario_id = $usuario->id;

        $filename = "/etc/supervisor/conf.d/{$server_name}_usuario_{$usuario_id}.conf";
        if (file_exists($filename))
            unlink($filename);

        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $server_root = DoubleConfiguracao::getConfiguracao('server_root');
        
        $filename = "{$server_root}/logs/{$server_name}_usuario_{$usuario_id}_consumer.out.log";
        if (file_exists($filename))
            unlink($filename);

        $filename = "{$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log";
        if (file_exists($filename))
            unlink($filename);    

        if (substr($usuario->plataforma->nome, 0, 5) == "Bacbo") {
            if ($usuario->modo_treinamento == 'N' and $usuario->servidor_conectado)
            {            
                $client = new Client(['http_errors' => false]);
                $response = $client->request(
                    'GET',
                    "http://{$usuario->servidor_conectado}:5001/usuario/{$usuario_id}/parar"
                );
                $usuario->servidor_conectado = null;
                $usuario->saveInTransaction();
            }
        }
    }
}