<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TDoubleRobo
{
    const ATTRIBUTES = [
        'chat_id', 'nome', 'nome_usuario', 'email', 'status', 'valor', 'protecao', 'stop_win', 'stop_loss', 'ultimo_saldo',
        'data_expiracao', 'ciclo', 'robo_iniciar', 'robo_iniciar_apos_loss', 'demo_jogadas', 'logado', 'robo_processando_jogada'
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
                $object->ultimo_saldo = $param['plataforma']->service->saldo($object);
                $object->save();
            }

            return $object;
        });
        return $object->toArray(static::ATTRIBUTES);
    }

    public function atualizar($param)
    {
        $object = TUtils::openConnection('double', function () use ($param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $param['plataforma']->id, $param['canal']->id);

            if (!$object) {
                $object = new DoubleUsuario();
                $object->chat_id = $param['chat_id'];
                $object->plataforma_id = $param['plataforma']->id;
                $object->save();

                $object = new DoubleUsuario($object->id, false);
            }

            if (isset($param['data']['status']) and $param['data']['status'] == 'DEMO') {
                $param['data']['data_expiracao'] = date('Y-m-d', strtotime('+5 days'));
                $param['data']['demo_jogadas'] = 5;
                $param['data']['demo_inicio'] = date('Y-m-d h:i:s');
            }

            unset($param['data']['idioma']);
            unset($param['data']['channel_id']);
            $object->fromArray((array) $param['data']);
            $object->store();

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
            TUtils::openConnection('double', function() use ($param, $mensagem) {
                $error = new DoubleErros();
                $error->classe = 'TDoubleRobo';
                $error->metodo = 'handle';
                $error->erro = $mensagem;
                $error->detalhe = json_encode($param);
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
            $object->token_acesso = TCrypto::encrypt(json_encode(['username' => $param['email'], 'password' => $param['password']]), $object->chat_id);
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
            $object->ultimo_saldo = $plataforma->service->saldo($object);
            $object->save();

            return $object;
        });

        $data = new stdClass;
        $data->usuario_id = $object->id;
        $data->plataforma_id = $plataforma->id;
        $data->tipo = 'cmd';
        $data->inicio = true;
        TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);

        return $object->toArray(static::ATTRIBUTES);
    }

    public static function iniciar_apos_loss($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (empty($param['chat_id']))
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal->idioma);

            $object->robo_status = 'INICIANDO';
            $object->robo_iniciar = 'Y';
            $object->robo_iniciar_apos_loss = 'Y';
            $object->robo_processando_jogada = 'N';
            $object->robo_sequencia += 1;
            $object->ultimo_saldo = $plataforma->service->saldo($object);
            $object->save();

            return $object;
        });
        
        $data = new stdClass;
        $data->usuario_id = $object->id;
        $data->plataforma_id = $plataforma->id;
        $data->tipo = 'cmd';
        $data->inicio = true;
        TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);

        return $object->toArray(static::ATTRIBUTES);
    }

    public function parar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        
        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('double', function() use ($plataforma, $param, $canal) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id, $canal);

            $object->robo_status = 'PARANDO';
            $object->robo_iniciar = 'N';
            $object->robo_iniciar_apos_loss = 'N';
            $object->robo_processando_jogada = 'N';
            $object->ultimo_saldo = $plataforma->service->saldo($object);
            $object->save();

            return $object;
        });
        return $object->toArray(static::ATTRIBUTES);
    }
}
