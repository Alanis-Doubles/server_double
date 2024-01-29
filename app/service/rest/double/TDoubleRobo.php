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

        if ($manutencao and !in_array($param['chat_id'], explode(',', $manutencao_chat_ids)))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_SERVIDOR_MANUTENCAO);
            

        $object = TUtils::openConnection('unit_database', function () use ($param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $param['plataforma']->id);

            if (!$object) {
                $object = new DoubleUsuario();
                $object->chat_id = $param['chat_id'];
                $object->plataforma_id = $param['plataforma']->id;
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
        $object = TUtils::openConnection('unit_database', function () use ($param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $param['plataforma']->id);

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

        $param['plataforma'] = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);;

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
            TUtils::openConnection('unit_database', function() use ($param, $mensagem) {
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
        // if (empty($param['chat_id']))
            throw new Exception("Operação não suportada");

        // TUtils::openConnection('unit_database');
        // try {
        //     $object = DoublePlataforma::where('LOWER(nome)', '=', $param['plataforma'])
        //     ->where('idioma', '=', $param['idioma'])
        //     ->first();
        //     $pagamento = ArbetyWebhook::where('email', '=', $param['email'])
        //         ->where('id_usuario', 'is', null)
        //         ->first();

        //     if (!$pagamento)
        //         throw new Exception("Pagamento não encontrado para o email {$param['email']}");

        //     $pagamento->id_usuario = $object->id;
        //     $pagamento->save();

        //     $object->expiration_date = date('Y-m-d', strtotime('+30 days'));
        //     $object->status = 'ATIVO';
        //     $object->email = $param['email'];
        //     $object->save();
        // } finally {
        //     TTransaction::close();
        // }
        // return $object->toArray(static::ATTRIBUTES);
    }

    public function logar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);

        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('unit_database', function () use ($param, $plataforma) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id);

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
        
        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('unit_database', function () use ($param, $plataforma) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id);
    
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
        $data->usuario = $object;
        $data->plataforma = $plataforma;
        $data->tipo = 'cmd';
        TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);

        return $object->toArray(static::ATTRIBUTES);
    }

    public static function iniciar_apos_loss($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        
        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('unit_database', function() use ($plataforma, $param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id);

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
        $data->usuario = $object;
        $data->plataforma = $plataforma;
        $data->tipo = 'cmd';
        TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);

        return $object->toArray(static::ATTRIBUTES);
    }

    public function parar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        
        if (empty($param['chat_id']))
            throw new Exception($param['plataforma']->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $object = TUtils::openConnection('unit_database', function() use ($plataforma, $param) {
            $object = DoubleUsuario::identificar($param['chat_id'], $plataforma->id);

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

    // public function teste($param)
    // {
    //     $client = new Client(['cookies' => true]);
    //     $jar = new \GuzzleHttp\Cookie\CookieJar;
    //     $response = $client->request(
    //         'POST',
    //         'https://doublevegas.bet/painel/backend/be_login',
    //         [
    //             'headers' => [
    //                 'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    //                 'accept-language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
    //                 'content-type' => 'application/x-www-form-urlencoded',
    //             ],
    //             'form_params' => [
    //                 'useremail' => 'edson.alanis@gmail.com',
    //                 'password' => '#6FkM3LhBhwDt'
    //             ],
    //             'cookies' => $jar
    //         ]
    //     );

    //     if ($response->getStatusCode() == 200) {
    //         $cookie = $jar->getCookieByName('PHPSESSID');
    //         return $cookie->getValue();
    //     } else {
    //         throw new Exception("Login inválido, por favor refaça a operação.");
    //     }
    // }

    // public function teste1($param)
    // {
    //     $client = new Client(['cookies' => true]);
    //     $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
    //         [
    //             'PHPSESSID' => $param['cookie']
    //         ],
    //         'doublevegas.bet'
    //     );
    //     $response = $client->request(
    //         'GET',
    //         'https://doublevegas.bet/api/user/balance/',
    //         [
    //             'headers' => [
    //                 'accept' => 'application/json',
    //                 'accept-language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,ru;q=0.6',
    //                 'content-type' => 'application/json',
    //             ],
    //             'cookies' => $jar
    //         ]
    //     );

    //     if ($response->getStatusCode() == 200) {
    //         $content = json_decode($response->getBody()->getContents());
    //         return $content->data;
    //     } else {
    //         throw new Exception("Login inválido, por favor refaça a operação.");
    //     }
    // }
}
