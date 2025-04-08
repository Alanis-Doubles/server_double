<?php

use Adianti\Database\TDatabase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TDoubleRobo
{
    const ATTRIBUTES = [
        'chat_id', 'nome', 'nome_usuario', 'email', 'telefone', 'status', 'valor', 'protecao', 'stop_win', 'stop_loss', 'ultimo_saldo',
        'data_expiracao', 'ciclo', 'robo_iniciar', 'robo_iniciar_apos_loss', 'demo_jogadas', 'logado', 'robo_processando_jogada',
        'entrada_automatica', 'entrada_automatica_total_loss', 'tipo_stop_loss', 'entrada_automatica_tipo', 'metas',
        'usuario_meta', 'valor_max_ciclo', 'protecao_branco', 'modo_treinamento', 'banca_treinamento', 'status_objetivo', 'robo_status',
        'fator_multiplicador', 'fator_multiplicador_branco', 'valor_branco', 'expiration', 'classificacao', 'demo_jogadas_restantes'
    ];

    public function carregar($param)
    {
        $manutencao = DoubleConfiguracao::getConfiguracao('manutencao');
        $manutencao_chat_ids = DoubleConfiguracao::getConfiguracao('manutencao_chat_ids');

        if ($manutencao == 'Y' and !in_array($param['chat_id'], explode(',', $manutencao_chat_ids)))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_SERVIDOR_MANUTENCAO);
            
        $object = TUtils::openConnection('double', function () use ($param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $param['plataforma']->id, $param['canal']->id);
            if (!$object) {
                $object = new DoubleUsuario();
                $object->chat_id = $param['chat_id'];
                $object->plataforma_id = $param['plataforma']->id;
                $object->canal_id = $param['canal']->id;
                $object->nome = $param['nome'];
                $object->nome_usuario = $param['nome_usuario'];
                $object->save();

                $object = new DoubleUsuario($object->id, false);
            }
            $now = date('Y-m-d');
            if ($object->status == 'ATIVO' and $now > $object->data_expiracao) {
                $object->status = 'EXPIRADO';
                $object->robo_iniciar = 'N';
                $object->robo_iniciar_apos_loss = 'N';
                $object->robo_processando_jogada = 'N';
                $object->save();
            }
            if ($object->logado == 'Y') {
                if (!$object->ultimo_saldo)
                    $object->ultimo_saldo = 0;
                
                if (isset($param['buscar_saldo']) and $param['buscar_saldo'] == 'Y') {
                    $object->ultimo_saldo = $param['plataforma']->service->saldo($object);
                }
               
                $object->save();
            }

            if (!$object->flux_id and $object->telefone) {
                $object->flux_id = TDoubleUtils::enviar_flux(
                    $param['plataforma']->url_flux,
                    $object->nome,
                    $object->email,
                    $object->telefone
                );
                $object->save();
            }

            return $object;
        });

        $arrData = $object->toArray(static::ATTRIBUTES);

        if ($object->metas == 'Y' and $object->usuario_meta) {
            $object->usuario_meta->atualizar($object);
            $arrData['valor'] = $object->usuario_meta->valor_real_entrada;
        }

        return $arrData;
    }

    public function atualizar($param)
    {
        $object = TUtils::openConnection('double', function () use ($param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $param['plataforma']->id, $param['canal']->id);
            $service = $object->plataforma->service;

            if (!$object) {
                $object = new DoubleUsuario();
                $object->chat_id = $param['chat_id'];
                $object->plataforma_id = $param['plataforma']->id;
                $object->save();

                $object = new DoubleUsuario($object->id, false);
            }

            if (isset($param['data']['status']) and $param['data']['status'] == 'DEMO') {
                $param['data']['data_expiracao'] = date('Y-m-d', strtotime('+5 days'));
                $param['data']['demo_jogadas'] = 50;
                $param['data']['demo_inicio'] = date('Y-m-d h:i:s');
            }

            $usuario_meta = [];
            if (isset($param['data']['usuario_meta']))
                $usuario_meta = $param['data']['usuario_meta'];

            unset($param['data']['idioma']);
            unset($param['data']['channel_id']);
            unset($param['data']['usuario_meta']);

            $object->fromArray((array) $param['data']);
            $object->store();

            if (isset($param['modo_treinamento']) and $param['modo_treinamento'] == 'N') {
                $object->ultimo_saldo = $param['plataforma']->service->saldo($object);
                $object->store();
            }

            if (isset($param['modo_treinamento']) and $param['modo_treinamento'] == 'Y' and $service->possuiBancaTreinamento()) {
                $object->banca_treinamento = $param['plataforma']->service->saldo($object);
                $object->store();
            }

            if (isset($param['email']))
                $object->email = $param['email'];
    
            if ($usuario_meta)
            {
                $meta = $object->usuario_meta;
                if (!$meta)
                {
                    $meta = new DoubleUsuarioMeta;
                    $meta->usuario_id = $object->id;
                }
                $meta->fromArray((array) $usuario_meta);
                $meta->store();
            }

            if (isset($param['telefone'])) {
                $object->telefone = $param['telefone'];
                $object->flux_id = TDoubleUtils::enviar_flux(
                    $param['plataforma']->url_flux,
                    $object->nome,
                    $object->email,
                    $object->telefone
                );
            }

            return $object;
        });
        return $object->toArray(static::ATTRIBUTES);
    }

    public function resetar_saldo_treinamento($param) 
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);

        if (empty($param['chat_id']))
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function () use ($param, $plataforma, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);
            $object->banca_treinamento = $plataforma->service->resetarBancaTreinamento($object);

            $object->save();

            return $object;
        });
        return $object->toArray(static::ATTRIBUTES);
    }

    public function handle($param)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        unset($param['class']);
        unset($param['method']);
        $param['data'] = $param;

        $param['plataforma'] = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $param['canal'] = DoubleCanal::identificarPorChannel($param['channel_id']);

        if (!$param['canal'])
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        try {
            switch ($method) {
                case 'GET':
                    if (!empty($param['chat_id'])) {
                        return $this->carregar($param);
                    } else {
                        throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);
                    }
                    break;
                case 'POST':
                    throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);
                    break;
                case 'PUT':
                    if (empty($param['chat_id']))
                        throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);
                    return $this->atualizar($param);
                    break;
                case 'DELETE':
                    throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);
                    break;
            }
        } catch (\Throwable $e) {
            $mensagem = $e->getMessage();
            $trace = json_encode($e->getTrace());
            TUtils::openConnection('double', function() use ($param, $mensagem, $trace, $method) {
                $error = new DoubleErros();
                $error->classe = 'TDoubleRobo';
                $error->metodo = 'handle - ' . $method;
                $error->erro = $mensagem;
                $error->detalhe = $trace;
                $error->plataforma_id = $param['plataforma']->id;
                $error->save();
            });
            throw $e;
        }
    }

    public function validar_pagamento($param)
    {
        if (empty($param['chat_id']))
            throw new Exception("Operação não suportada");

        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $busca = TUtils::openConnection('double', function() use ($param, $plataforma){
            $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
            if ($plataforma->usuarios_canal == 'N') {
                $usuario = DoubleUsuario::where('chat_id', '=', $param['chat_id'])
                    ->where('plataforma_id', '=', $plataforma->id)
                    ->first();
                $usuario->email = $param['email'];
                $usuario->save();

                return [
                    'usuario' => $usuario,
                    'pagamento' => DoublePagamentoHistorico::where('email', '=', $param['email'])
                                    ->where('plataforma_id', '=', $plataforma->id)
                                    ->where('usuario_id', 'is', null)
                                    ->first()
                ];
            } else {
                $usuario = DoubleUsuario::where('chat_id', '=', $param['chat_id'])
                    ->where('plataforma_id', '=', $plataforma->id)
                    ->where('canal_id', '=', $canal->id)
                    ->first();
                $usuario->email = $param['email'];
                $usuario->save();

                return [
                    'usuario' => $usuario, 
                    'pagamento' => DoublePagamentoHistorico::where('email', '=', $param['email'])
                                    ->where('plataforma_id', '=', $plataforma->id)
                                    ->where('canal_id', '=', $canal->id)
                                    ->where('usuario_id', 'is', null)
                                    ->first()
                ];
            }
        });
        
        if (!$busca['pagamento'])
            throw new Exception("Pagamento não encontrado para o email {$param['email']}");

        return TUtils::openConnection('double', function() use ($busca){
            $busca['pagamento']->usuario_id = $busca['usuario']->id;
            $busca['pagamento']->save();
            new DoubleUsuario($busca['$usuario']->id, False);
        });
    }

    public function logar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);

        if (empty($param['chat_id']))
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function () use ($param, $plataforma, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);

            $token = $plataforma->service->logar($param['email'], $param['password']);

            $object->robo_iniciar = 'N';
            $object->robo_iniciar_apos_loss = 'N';
            $object->robo_processando_jogada = 'N';
            $object->token_acesso = json_encode(['username' => $param['email'], 'password' => $param['password']]);
            // $object->token_acesso = TCrypto::encrypt(json_encode(['username' => $param['email'], 'password' => $param['password']]), $object->chat_id);
            $object->token_plataforma = $token;
            $object->token_expiracao = date('Y-m-d H:i:s', strtotime('+3 hours'));

            $object->ultimo_saldo = $plataforma->service->saldo($object);

            $object->save();

            return $object;
        });
        return $object->toArray(static::ATTRIBUTES);
    }

    public static function iniciar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (empty($param['chat_id']))
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function () use ($param, $plataforma, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);
    
            $object->robo_status = 'INICIANDO';
            $object->robo_iniciar = 'Y';
            $object->robo_iniciar_apos_loss = 'N';
            $object->robo_processando_jogada = 'N';
            $object->robo_sequencia += 1;
            $object->entrada_automatica_qtd_loss = 0;
            $object->quantidade_loss = 0;
            $object->ultimo_saldo = $plataforma->service->saldo($object);
            $object->save();

            if ($object->metas == 'Y' and $object->usuario_meta)
            {
                $object->usuario_meta->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                $object->usuario_meta->proxima_execucao = null;
                $object->usuario_meta->atualizar($object);
                // $object->usuario_meta->ultimo_saldo = $plataforma->service->saldo($object);
                // $object->usuario_meta->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                // $object->usuario_meta->proxima_execucao = null;
                // $object->usuario_meta->save();
            }

            return $object;
        });

        // $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        // if ($use_redis == 'Y') {
        //     $object->robo_status = 'EXECUTANDO';
        //     if (!isset($param['nao_reseta_inicio']))
        //         $object->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
        //     $object->saveInTransaction();

        //     $redis_param = [
        //         'usuario_id' => $object->id
        //     ];

        //     if (substr(php_uname(), 0, 7) == "Windows") 
        //     {
        //         // php cmd.php "class=TDoubleUsuarioConsumer&method=run&usuario_id=7"
        //         TUtils::cmd_run('TDoubleUsuarioConsumer', 'run', $redis_param);
        //         if (substr($object->plataforma->nome, 0, 5) == "Bacbo") {
        //             $bacbo_plataforma = strtolower(substr($object->plataforma->nome, 5));
        //             $caminho = "C:/Users/edson/Downloads/bacbo/sala_bacbo-main";
        //             $arquivo = "{$bacbo_plataforma}/{$bacbo_plataforma}_bacbo_usuario.py";
        //             $command = "{$caminho}/venv/Scripts/python {$caminho}/{$arquivo} {$object->id}";

        //             // pclose(popen("start /B " . $command, "r"));
        //         }
        //     } else 
        //     {
        //         // self::removerArquivoSupervisor($object);
        //         // $object = self::configurarArquivoSuperviobjectsor($object);
        //         // TUtils::cmd_run('TDoubleRobo', 'supervisor', ['id' => $object->id, 'comando' => 'start']);
        //     }
        // } else {
        //     $data = new stdClass;
        //     $data->usuario_id = $object->id;
        //     $data->plataforma_id = $plataforma->id;
        //     $data->tipo = 'cmd';
        //     $data->inicio = true;
        //     TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);
        // }

        $object->plataforma->service->finalizar($object);
        $object->plataforma->service->iniciar($object);

        ////  DoubleErros::registrar('1', 'TDoubleRobo', 'iniciar 1', $object->toArray(static::ATTRIBUTES));

        return $object->toArray(static::ATTRIBUTES);
    }

    public static function iniciar_apos_loss($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (empty($param['chat_id']))
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);

            $object->robo_status = 'INICIANDO';
            $object->robo_iniciar = 'Y';
            $object->robo_iniciar_apos_loss = 'Y';
            $object->robo_processando_jogada = 'N';
            $object->robo_sequencia += 1;
            $object->entrada_automatica_qtd_loss = 0;
            $object->quantidade_loss = 0;
            $object->ultimo_saldo = $plataforma->service->saldo($object);
            $object->save();

            if ($object->metas == 'Y' and $object->usuario_meta)
            {
                $object->usuario_meta->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                $object->usuario_meta->proxima_execucao = null;
                $object->usuario_meta->atualizar($object);
                // $object->usuario_meta->ultimo_saldo = $plataforma->service->saldo($object);
                // $object->usuario_meta->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                // $object->usuario_meta->proxima_execucao = null;
                // $object->usuario_meta->save();
            }

            return $object;
        });
        
        // $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        // if ($use_redis == 'Y') {
        //     $object->robo_status = 'EXECUTANDO';
        //     if (!isset($param['nao_reseta_inicio']))
        //         $object->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
        //     $object->saveInTransaction();

        //     $redis_param = [
        //         'usuario_id' => $object->id
        //     ];
            
        //     if (substr(php_uname(), 0, 7) == "Windows") 
        //     {
        //         // php cmd.php "class=TDoubleUsuarioConsumer&method=run&usuario_id=7"
        //         TUtils::cmd_run('TDoubleUsuarioConsumer', 'run', $redis_param);
        //     } else 
        //     {
        //         self::removerArquivoSupervisor($object);
        //         $programa = self::configurarArquivoSupervisor($object);
        //         // TUtils::cmd_run('TDoubleRobo', 'supervisor', ['id' => $object->id, 'comando' => 'start']);
        //     }
        // } else {
        //     $data = new stdClass;
        //     $data->usuario_id = $object->id;
        //     $data->plataforma_id = $plataforma->id;
        //     $data->tipo = 'cmd';
        //     $data->inicio = true;
        //     if (isset($param['nao_reseta_inicio']))
        //         $data->nao_reseta_inicio = 'Y';
        //     TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);
        // }

        $object->plataforma->service->finalizar($object);
        $object->plataforma->service->iniciar($object);

        return $object->toArray(static::ATTRIBUTES);
    }

    public function parar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);

            $object->robo_status = 'PARADO';
            $object->robo_iniciar = 'N';
            $object->robo_iniciar_apos_loss = 'N';
            $object->robo_processando_jogada = 'N';
            $object->ultimo_saldo = $plataforma->service->saldo($object) ?? 0;
            $object->save();

            if ($object->metas == 'Y' and $object->usuario_meta)
            {
                $object->usuario_meta->ultimo_saldo = 0;
                $object->usuario_meta->inicio_execucao = null;
                $object->usuario_meta->proxima_execucao = null;
                $object->usuario_meta->save();
            }

            if ($object->status_objetivo == 'EXECUTANDO')
                $object->usuario_objetivo->parar();

            return $object;
        });

        $object->plataforma->service->finalizar($object);

        // $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        // if ($use_redis == 'Y') {
        //     if (substr(php_uname(), 0, 7) != "Windows") {
        //         self::removerArquivoSupervisor($object);
        //     }
        // }
        return $object->toArray(static::ATTRIBUTES);
    }

    // public static function removerArquivoSupervisor($usuario)
    // {
        // $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        // $usuario_id = $usuario->id;

        // $filename = "/etc/supervisor/conf.d/{$server_name}_usuario_{$usuario_id}.conf";
        // if (file_exists($filename))
        //     unlink($filename);

        // $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        // $server_root = DoubleConfiguracao::getConfiguracao('server_root');
        
        // $filename = "{$server_root}/logs/{$server_name}_usuario_{$usuario_id}_consumer.out.log";
        // if (file_exists($filename))
        //     unlink($filename);

        // $filename = "{$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log";
        // if (file_exists($filename))
        //     unlink($filename);    

        // if (substr($usuario->plataforma->nome, 0, 5) == "Bacbo") {
        //     if ($usuario->modo_treinamento == 'N' and $usuario->servidor_conectado)
        //     {            
        //         $client = new Client(['http_errors' => false]);
        //         $response = $client->request(
        //             'GET',
        //             "http://{$usuario->servidor_conectado}:5001/usuario/{$usuario_id}/parar"
        //         );
        //         $usuario->servidor_conectado = null;
        //         $usuario->saveInTransaction();
        //     }
        // }
//    }

    public static function configurarArquivoSupervisor($usuario)
    {
        // $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        // $usuario_id = $usuario->id;

        // $filename = "/etc/supervisor/conf.d/{$server_name}_usuario_{$usuario_id}.conf";
        // if (file_exists($filename))
        //     return "{$server_name}_usuario_{$usuario_id}_";

        // $server_root = DoubleConfiguracao::getConfiguracao('server_root');

        // $usuarioConfig = "[program:{$server_name}_usuario_{$usuario_id}_consumer]\n";
        // $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TDoubleUsuarioConsumer&method=run&usuario_id={$usuario_id}&server_name={$server_name}'\n";
        // $usuarioConfig .= "autostart=true\n";
        // $usuarioConfig .= "autorestart=true\n";
        // $usuarioConfig .= "stdout_logfile={$server_root}/logs/{$server_name}_usuario_{$usuario_id}_consumer.out.log\n";
        // $usuarioConfig .= "numprocs=1\n";
        // $usuarioConfig .= "\n";
        // $usuarioConfig .= "[program:{$server_name}_usuario_{$usuario_id}_sinais_consumer]\n";
        // $usuarioConfig .= "command=php {$server_root}/cmd.php 'class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id={$usuario_id}'\n";
        // $usuarioConfig .= "autostart=true\n";
        // $usuarioConfig .= "autorestart=true\n";
        // $usuarioConfig .= "stdout_logfile={$server_root}/logs/{$server_name}_usuario_{$usuario_id}_sinais_consumer.out.log\n";
        // $usuarioConfig .= "numprocs=1\n";
        // $usuarioConfig .= "\n";

        // if (substr($usuario->plataforma->nome, 0, 5) == "Bacbo") {
        //     if ($usuario->modo_treinamento == 'N')
        //     {
        //         $client = new Client(['http_errors' => false]);
        //         $response = $client->request(
        //             'GET',
        //             "http://180.149.34.85:5001/usuario/{$usuario_id}/iniciar"
        //         );
            
        //         if ($response->getStatusCode() == 200) {    
        //             $content = json_decode($response->getBody()->getContents());
                
        //             $usuario->servidor_conectado = $content->server;
        //             $usuario->saveInTransaction();
        //         } else {
        //             $content = json_decode($response->getBody()->getContents());
        //            //  DoubleErros::registrar('1', 'TDoubleRobo', 'iniciar', $response->getStatusCode(), "servidor: {$content->server}");
        //         }
        //     }
        // }

        // $criado = file_put_contents($filename, $usuarioConfig);
        // return '';
        return $usuario->plataforma->service->iniciar($usuario);
    }

    public function gerar_acesso($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (!$canal)
            throw new Exception("Canal não suportado.");

        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        if (!isset($param['email']))
            throw new Exception('E-mail não informado');

        $double_usuario = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            return DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);
        });

        if (!$double_usuario)
            throw new Exception('Usuário não encontrado');

        $usuarios = TUtils::openConnection('permission', function () use($double_usuario, $param){
            return SystemUser::where('custom_code', '<>', $double_usuario->chat_id)
                ->where('email', '=', $param['email'])
                ->load();
        });

        if (count($usuarios) > 0)
            throw new Exception('Este email já está sendo utilizado por outro usuário. Informe um novo email.');

        $senha = $double_usuario->generate_access($param['email']);

        (new TEmailValidator())->validate("E-mail {$double_usuario->email}", $double_usuario->email);

        $html = new THtmlRenderer('app/resources/double/double_usuario_senha.html');
        $html->enableTranslation();

        $title = $ini['general']['title']??'System';
        
        $subject = 'Senha de acesso';
        $content = str_replace('^1', $title, 'Utilize a senha abaixo para acessar seu Dashboard. Se estiver encontrando algum problema entre em contato com o suporte técnico.');
        
        $html->enableSection(
            'main',
            [
                'url'   => DoubleConfiguracao::getConfiguracao('base_url'),
                'login' => $double_usuario->email,
                'password' => $senha,
                'login_time' => date("Y-m-d H:i:s"),
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
                'subject' => $subject,
                'content' => $content,
            ]
        );

        MailService::send($double_usuario->email, $subject, $html->getContents(), 'html');

        return 'ok';
    }

    public function tst_gerar_acesso($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (!$canal)
            throw new Exception("Canal não suportado.");

        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        if (!isset($param['email']))
            throw new Exception('E-mail não informado');

        $double_usuario = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            return DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->id);
        });

        if (!$double_usuario)
            throw new Exception('Usuário não encontrado');

        $usuarios = TUtils::openConnection('permission', function () use($double_usuario, $param){
            return SystemUser::where('custom_code', '<>', $double_usuario->chat_id)
                ->where('email', '=', $param['email'])
                ->load();
        });

        if (count($usuarios) > 0)
            throw new Exception('Este email já está sendo utilizado por outro usuário. Informe um novo email.');

        return $double_usuario->tst_generate_access($param['email'], $param['senha']);
    }

    public function tst_edson_alanis($param) 
    {
        $list = TUtils::openFakeConnection('double', function (){
            $usuarios = DoubleUsuario::all();

            $lista = [];
            foreach ($usuarios as $usuario) {
                if (!$usuario->token_acesso)
                    continue;

                $payload = TCrypto::decrypt($usuario->token_acesso, $usuario->chat_id);
                $object = json_decode($payload);
                if ($object->username == 'edson.alanis@gmail.com')
                    $lista[] = $usuario->id;
            }

            return $lista;
        });

        return $list;
    }
}

