<?php
set_time_limit(0); // ignora a diretiva max_execution_time do php.ini para manter o script em execução

use WSSC\WebSocketServer;
use WSSC\Components\ServerConfig;

class TDoubleSinais
{
    private static $ultimo_historico;

    public static function iniciar($param)
    {
        $token = TUtils::openFakeConnection('permission', function () {
            $login = TSession::getValue('login');
            $user = SystemUser::validate($login);
            return ApplicationAuthenticationRestService::getToken($user);
        });

        $data = new stdClass;
        $data->inicio = true;
        $data->token = $token;
        $data->plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $data->tipo = 'cmd';

        $tipo_sinais = $data->plataforma->tipo_sinais;
        if ($tipo_sinais == 'PROPAGA_OUTRO')
            throw new Exception('Plataforma não configurada para buscar sinais');

        $data->plataforma_id = $data->plataforma->id;
        unset($data->plataforma);
        TDoubleUtils::cmd_run('TDoubleSinais', 'executar', $data);

        if ($tipo_sinais == 'GERA') {
            $canais = TUtils::openFakeConnection('double', function() use ($data){
                return DoubleCanal::where('plataforma_id', '=', $data->plataforma_id)
                    ->where('ativo', '=', 'Y')
                    ->load();
            });

            foreach ($canais as $key => $canal) {
                $data->canal_id = $canal->id;
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal', $data);
            }           
        }
    }

    public static function finalizar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);

        if ($plataforma->statusSinais == 'EXECUTANDO') {
            $plataforma->statusSinais = 'PARANDO';
        } else {
            $plataforma->statusSinais = 'PARADO';
        }
    }

    public static function executar($param)
    {
        $data = (object) $param['data'];

        $token = $data->token;
        ApplicationAuthenticationService::fromToken($token);

        $data->plataforma = TUtils::openFakeConnection('double', function() use ($data){
            return new DoublePlataforma($data->plataforma_id, false);
        });

        if (!isset($data->tipo) or (isset($data->tipo) and $data->tipo != 'cmd'))
            throw new Exception($data->plataforma->translate->MSG_OPERACAO_METODO_NAO_SUPORTADO);

        if ($data->inicio) 
        {
            if ($data->plataforma->status_sinais == 'EXECUTANDO') {
                self::finalizar(['plataforma' => $data->plataforma->nome, 'idioma' => $data->plataforma->idioma]);
                sleep(10);
            }
            
            $data->plataforma->statusSinais = 'INICIANDO';
            $data->plataforma->statusSinais = 'EXECUTANDO';
            $data->plataforma->inicioSinais = (new DateTime())->format('Y-m-d H:i:s');
            $status = $data->plataforma->statusSinais;
            $data->ultimo_sinal = [];
        }

        $ultimo_sinal = $data->ultimo_sinal;
        $service = null;
        try
        {
            try {
                if (!$service)
                    $service = $data->plataforma->service;
                $ultimo_sinal = $service->aguardarSinal($ultimo_sinal);
                TUtils::openConnection('double', function() use ($data, $service) {
                    $sinal = new DoubleSinal();
                    $sinal->plataforma_id = $data->plataforma->id;
                    $sinal->numero = $service->ultimoSinal();
                    $sinal->cor = $data->plataforma->service->cores()[$sinal->numero];
                    $sinal->save();

                    return $sinal;
                });

                $status = $data->plataforma->statusSinais;
            } catch (\Throwable $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar';
                    $error->erro = $mensagem;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar';
                    $error->erro = $mensagem;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            }
    } finally
        {
            $service = null;
            $status = $data->plataforma->statusSinais;
            if ($status != 'EXECUTANDO')
                $data->plataforma->statusSinais = 'PARADO';
        }

        if ($status == 'EXECUTANDO') {
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });
    
            $data->inicio = false;
            $data->token = $token;
            $data->tipo = 'cmd';
            $data->ultimo_sinal = $ultimo_sinal;

            unset($data->plataforma);

            TDoubleUtils::cmd_run('TDoubleSinais', 'executar', $data);
        }
    }

    public static function registrar($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $historico = TUtils::openConnection('double', function () use ($plataforma, $param) {
            $historico = new DoubleHistorico();
            $historico->plataforma_id = $plataforma->id;
            if (isset($param['cor']))
                $historico->cor = $param['cor'];
            $historico->tipo = $param['tipo'];
            if (isset($param['estrategia_id']))
                $historico->estrategia_id = $param['estrategia_id'];
            if (isset($param['channel_id'])) {
                $canal = DoubleCanal::where('plataforma_id', '=', $plataforma->id)
                    ->where('channel_id', '=', $param['channel_id'])
                    ->first();
                if (!$canal)
                    throw new Exception(
                        str_replace(
                            ['{channel_id}'],
                            [$param['channel_id']], 
                            $plataforma->translate->MSG_OPERACAO_CANAL_NAO_SUPORTADO
                        )
                    );
                $historico->canal_id = $canal->id;
            }
            if (isset($param['informacao']))
                $historico->informacao = $param['informacao'];
            $historico->save();

            return [
                'tipo' => $historico->tipo, 
                'estrategia' => $historico->estrategia,
                'cor' => $historico->cor, 
                'informacao' => $historico->informacao,
                'created_at' => $historico->created_at
            ];
        });

        self::$ultimo_historico = $historico;
    }

    public static function finalizar_canal($param)
    {
        $canal = DoubleCanal::identificar($param['canal_id']);

        if ($canal->statusSinais == 'EXECUTANDO') {
            $canal->statusSinais = 'PARANDO';
        } else {
            $canal->statusSinais = 'PARADO';
        }
    }

    public static function executar_canal($param)
    {
        $data = (object) $param['data'];

        $token = $data->token;
        ApplicationAuthenticationService::fromToken($token);

        $data->canal = TUtils::openFakeConnection('double', function() use ($data){
            return new DoubleCanal($data->canal_id, false);
        });
        $data->plataforma = TUtils::openFakeConnection('double', function() use ($data){
            return new DoublePlataforma($data->plataforma_id, false);
        });

        if (!isset($data->tipo) or (isset($data->tipo) and $data->tipo != 'cmd'))
            throw new Exception($data->plataforma->translate->MSG_OPERACAO_METODO_NAO_SUPORTADO);

        $call_status = function() use ($data) {
            $status = $data->plataforma->statusSinais;
            if ($status != 'EXECUTANDO')
                return $status;

            $status = $data->canal->statusSinais;
            return $status;
        };
    
        $telegram = $data->plataforma->telegram;

        if ($data->inicio) 
        {
            $status = $data->plataforma->statusSinais;
            if ($status != 'EXECUTANDO')
                sleep(5);

            if ($call_status() == 'EXECUTANDO') {
                self::finalizar_canal(['canal_id' => $data->canal->id]);
                sleep(11);
            }
            self::gerarStatus($telegram, $data);
            $data->canal->statusSinais = 'INICIANDO';
            $data->canal->statusSinais = 'EXECUTANDO';
            $data->canal->inicioSinais = (new DateTime())->format('Y-m-d H:i:s');
            // $data->canal->inicioSinais = date('Y-m-d H:i:s', strtotime('+50 minutes'));

            $data->sinal = [];
        }
        
        $service = null;
        try
        {
            $sinal = DoubleSinal::buscarSinal($data->sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
            try {
                if (!$service)
                    $service = $data->plataforma->service;

                $estrategias = TUtils::openFakeConnection('double', function() use ($data) {
                    return DoubleEstrategia::where('canal_id', '=', $data->canal->id)
                        ->where('ativo', '=', 'Y')
                        ->where('usuario_id', 'is', null)
                        ->load();
                });

                // $sinal = DoubleSinal::buscarSinal($sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
                foreach ($estrategias as $key => $estrategia) {
                    if ($estrategia->validar($sinal, $service)) {
                        if ($estrategia->aguardarProximoSinal()) {
                            $message = $telegram->sendMessage(
                                $data->canal->channel_id,
                                $data->plataforma->translate->MSG_SINAIS_OPORTUNIDADE,
                            );
                            $sinal = DoubleSinal::buscarSinal($sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);

                            $telegram->deleteMessage($data->canal->channel_id, $message->result->message_id);

                            if (!$estrategia->validarProximoSinal($sinal))
                                break;
                        }

                        $ultimo_numero = $sinal[0]['numero'];
                        $ultima_cor = $sinal[0]['cor'];
                        $cor_retornada = $estrategia->resultado;

                        $payload = [
                            'plataforma' => strtolower($data->plataforma->nome),
                            'idioma' => $data->plataforma->idioma,
                            'cor' => $cor_retornada,
                            'tipo' => 'ENTRADA',
                            'channel_id' => $data->canal->channel_id,
                            'estrategia_id' => $estrategia->id
                        ];

                        self::registrar($payload);

                        $botao = [];
                        if ($data->plataforma->url_grupo_vip)
                            $botao[] = [["text" => $data->plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $data->plataforma->url_grupo_vip]];
                        if ($data->plataforma->url_cadastro)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $data->plataforma->url_cadastro]];
                        if ($data->plataforma->url_tutorial)
                            $botao[] = [["text" => str_replace(['{plataforma}'], [$data->plataforma->nome], $data->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $data->plataforma->url_tutorial]];
                        if ($data->plataforma->url_suporte)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $data->plataforma->url_suporte]];

                        $telegram->sendMessage(
                            $data->canal->channel_id,
                            str_replace(
                                ['{estrategia}', '{cor}', '{ultimo_numero}', '{ultima_cor}'],
                                [
                                    $estrategia->nome, 
                                    self::getCor($cor_retornada, $data->plataforma->translate), 
                                    $ultimo_numero, 
                                    self::getCor($ultima_cor, $data->plataforma->translate, false), 
                                ],
                                $data->plataforma->translate->MSG_SINAIS_ENTRADA_CONFIRMADA,
                            ),
                            [
                                "resize_keyboard" => true, 
                                "inline_keyboard" => $botao
                            ]
                        );

                        $protecao = 0;
                        $message = null;
                        while (true) {
                            $sinal = DoubleSinal::buscarSinal($sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
                            $win = $estrategia->processarRetorno($sinal);
                            if ($message)
                                $telegram->deleteMessage($data->canal->channel_id, $message->result->message_id);

                            if ($win) {
                                $telegram->sendMessage(
                                    $data->canal->channel_id,
                                    $data->plataforma->translate->MSG_SINAIS_WIN,
                                );

                                $payload = [
                                    'plataforma' => strtolower($data->plataforma->nome),
                                    'idioma' => $data->plataforma->idioma,
                                    'cor' => $cor_retornada,
                                    'tipo' => 'WIN',
                                    'channel_id' => $data->canal->channel_id,
                                    'estrategia_id' => $estrategia->id
                                ];
                                self::registrar($payload);
                                $data->canal = DoubleCanal::identificar($data->canal->id);
                                self::gerarStatus($telegram, $data);
                                break;
                            } else {
                                $gales = ['primeira', 'segunda', 'terceira', 'quarta', 'quinta', 'sexta'];
                                if ($protecao == $data->canal->protecoes) {                                       
                                    $telegram->sendMessage(
                                        $data->canal->channel_id,
                                        $data->plataforma->translate->MSG_SINAIS_LOSS,
                                    );
                                    
                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'LOSS',
                                        'channel_id' => $data->canal->channel_id,
                                        'estrategia_id' => $estrategia->id
                                    ];
                                    self::registrar($payload);
                                    $data->canal = DoubleCanal::identificar($data->canal->id);
                                    self::gerarStatus($telegram, $data);
                                    break;
                                } else {
                                    $message = $telegram->sendMessage(
                                        $data->canal->channel_id,
                                        str_replace(
                                            ['{protecao}'],
                                            [$gales[$protecao]],
                                            $data->plataforma->translate->MSG_SINAIS_GALE,
                                        ),
                                    );

                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'GALE',
                                        'channel_id' => $data->canal->channel_id,
                                        'estrategia_id' => $estrategia->id
                                    ];
                                    self::registrar($payload);
                                }
                            }
                            $protecao += 1;
                        }
                    }
                }
            } catch (\Throwable $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = "Canal: " . $data->canal->nome . '[' . $data->canal->channel_id . ']';
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = "Canal: " . $data->canal->nome . '[' . $data->canal->channel_id . ']';
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            }
        } finally
        {
            $service = null;
            if ($call_status() != 'EXECUTANDO')
                $data->canal->statusSinais = 'PARADO';
        }

        if ($call_status() == 'EXECUTANDO')
        {
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });
   
            $data->inicio = false;
            $data->token = $token;
            $data->tipo = 'cmd';
            $data->sinal = $sinal;

            unset($data->plataforma);
            unset($data->canal);
                        
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal', $data);
        }
    }

    public static function executar_canal_propagar_sinal($param)
    {
        $data = (object) $param['data'];
        $token = $data->token;
        ApplicationAuthenticationService::fromToken($token);

        $data->canal = TUtils::openFakeConnection('double', function() use ($data){
            return new DoubleCanal($data->canal_id, false);
        });
        $data->plataforma = TUtils::openFakeConnection('double', function() use ($data){
            return new DoublePlataforma($data->plataforma_id, false);
        });

        if (!isset($data->tipo) or (isset($data->tipo) and $data->tipo != 'cmd'))
            throw new Exception($data->plataforma->translate->MSG_OPERACAO_METODO_NAO_SUPORTADO);

        $call_status = function() use ($data) {
            $status = $data->canal->statusSinais;
            return $status;
        };

        $telegram = $data->plataforma->telegram;
    
        if ($data->inicio)
        {
            if ($call_status() == 'EXECUTANDO') {
                self::finalizar_canal(['canal_id' => $data->canal->id]);
                sleep(5);
            }
    
            self::gerarStatus($telegram, $data);
            $data->canal->statusSinais = 'INICIANDO';
            $data->historico = [];
            $data->message_id = null;
            $data->canal->statusSinais = 'EXECUTANDO';
            $data->canal->inicioSinais = (new DateTime())->format('Y-m-d H:i:s');
        }
        
        try
        {
            $message = isset($data->message_id) ? $data->message_id : null;
            $historico = isset($data->historico) ? $data->historico : [];
            try {
                $historico = DoubleHistorico::buscarHistorico($historico, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
                if ($historico == []) {
                    return;
                }

                if ($message){
                    $telegram->deleteMessage($data->canal->channel_id, $message);
                    $message = null;
                }

                if ($historico['tipo'] == 'POSSIVEL') {
                    $message = ($telegram->sendMessage(
                        $data->canal->channel_id,
                        $data->plataforma->translate->MSG_SINAIS_OPORTUNIDADE,
                    ))->result->message_id;
                } elseif ($historico['tipo'] == 'ENTRADA') {
                    $cor_retornada = $historico['cor'];

                    $botao = [];
                        if ($data->plataforma->url_grupo_vip)
                            $botao[] = [["text" => $data->plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $data->plataforma->url_grupo_vip]];
                        if ($data->plataforma->url_cadastro)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $data->plataforma->url_cadastro]];
                        if ($data->plataforma->url_tutorial)
                            $botao[] = [["text" => str_replace(['{plataforma}'], [$data->plataforma->nome], $data->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $data->plataforma->url_tutorial]];
                        if ($data->plataforma->url_suporte)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $data->plataforma->url_suporte]];

                    $telegram->sendMessage(
                        $data->canal->channel_id,
                        str_replace(
                            ['{estrategia}', '{cor}', '{informacao}'],
                            [self::buscarNomeEstrategia($historico), self::getCor($cor_retornada, $data->plataforma->translate), $historico['informacao']],
                            $data->plataforma->translate->MSG_SINAIS_ENTRADA_CONFIRMADA,
                        ),
                        [
                            "resize_keyboard" => true, 
                            "inline_keyboard" => $botao
                        ]
                    );
                } elseif ($historico['tipo'] == 'WIN') {
                    $telegram->sendMessage(
                        $data->canal->channel_id,
                        $data->plataforma->translate->MSG_SINAIS_WIN,
                    );
                    $data->canal = DoubleCanal::identificar($data->canal->id);
                    self::gerarStatus($telegram, $data);
                } elseif ($historico['tipo'] == 'LOSS') {
                    $telegram->sendMessage(
                        $data->canal->channel_id,
                        $data->plataforma->translate->MSG_SINAIS_LOSS,
                    );
                    $data->canal = DoubleCanal::identificar($data->canal->id);
                    self::gerarStatus($telegram, $data);
                }
                } catch (\Throwable $e) // in case of exception
                {
                    $mensagem = $e->getMessage();
                    TUtils::openConnection('double', function() use ($mensagem, $data) {
                        $error = new DoubleErros();
                        $error->classe = 'TDoubleSinais';
                        $error->metodo = 'executar_canal_propagar_sinal';
                        $error->erro = $mensagem;
                        $error->detalhe = "Canal: " . $data->canal->nome . '[' . $data->canal->channel_id . ']';
                        $error->plataforma_id = $data->plataforma->id;
                        $error->save();
                    });
                } catch (Exception $e) // in case of exception
                {
                    $mensagem = $e->getMessage();
                    TUtils::openConnection('double', function() use ($mensagem, $data) {
                        $error = new DoubleErros();
                        $error->classe = 'TDoubleSinais';
                        $error->metodo = 'executar_canal_propagar_sinal';
                        $error->erro = $mensagem;
                        $error->detalhe = "Canal: " . $data->canal->nome . '[' . $data->canal->channel_id . ']';
                        $error->plataforma_id = $data->plataforma->id;
                        $error->save();
                    });
                }
        } finally
        {
            if ($call_status() != 'EXECUTANDO')
                $data->canal->statusSinais = 'PARADO';
        }

        if ($call_status() == 'EXECUTANDO')
        {
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });
    
            $data->token = $token;
            $data->tipo = 'cmd';
            $data->message_id = $message;
            $data->historico = $historico;
            $data->inicio = false;

            unset($data->plataforma);
            unset($data->canal);
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal_propagar_sinal', $data);
        }
    }

    public static function executar_usuario($param)
    {
        $data = (object) $param['data'];
        $token = $data->token;
        ApplicationAuthenticationService::fromToken($token);

        $data->plataforma = TUtils::openFakeConnection('double', function() use ($data){
            return new DoublePlataforma($data->plataforma_id, false);
        });
        $data->usuario = TUtils::openFakeConnection('double', function() use ($data){
            return new DoubleUsuario($data->usuario_id, false);
        });

        if (!isset($data->tipo) or (isset($data->tipo) and $data->tipo != 'cmd'))
            throw new Exception($data->plataforma->translate->MSG_OPERACAO_METODO_NAO_SUPORTADO);

        $botao = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $data->plataforma->translate->BOTAO_CONFIGURAR]],
                    [["text" => $data->plataforma->translate->BOTAO_PARAR_ROBO]], 
                ] 
            ];

        $botao_inicio = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $data->plataforma->translate->BOTAO_CONFIGURAR]],
                    [["text" => $data->plataforma->translate->BOTAO_INICIAR], ["text" => $data->plataforma->translate->BOTAO_INICIAR_LOSS]], 
                ] 
            ];

        $service = null;
        $telegram = $data->plataforma->telegram;
        try
        {
            if ($data->inicio)
            {
                $data->usuario->roboStatus = 'EXECUTANDO';
                $data->usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
                $data->historico = [];
            }
            
            $call_status = function() use ($data) {
                return $data->usuario->roboStatus;
            };

            $historico = isset($data->historico) ? $data->historico : [];
            try {
                $historico = DoubleHistorico::buscarHistorico($historico, $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                if ($historico == []) {
                    return;
                }
                
                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                if ($historico['tipo'] == 'LOSS' and $data->usuario->robo_iniciar_apos_loss == 'Y') {
                    $data->usuario->robo_iniciar_apos_loss = 'N';
                    $data->usuario->robo_processando_jogada = 'N';
                    $data->usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
                    $data->usuario->saveInTransaction();
                    $telegram->sendMessage(
                        $data->usuario->chat_id,
                        $data->plataforma->translate->MSG_OPERACAO_IDENTIFICADO_LOSS,
                        $botao
                    );
                } elseif ($historico['tipo'] == 'ENTRADA' and $data->usuario->robo_iniciar_apos_loss == 'N') {
                    if ($data->usuario->status != 'ATIVO' and $data->usuario->status != 'DEMO') {
                        $data->usuario->robo_iniciar = 'N';
                        $data->usuario->robo_iniciar_apos_loss = 'N';
                        $data->usuario->robo_processando_jogada = 'N';
                        $data->usuario->robo_status = 'PARANDO';
                        $data->usuario->saveInTransaction();
                        return;
                    }

                    $data->usuario->robo_processando_jogada = 'Y';
                    $data->usuario->saveInTransaction();
                    try {
                        $protecao = 0;
                        $valor = $data->usuario->valorJogada;
                        $cor = $historico['cor'];

                        $telegram->sendMessage(
                            $data->usuario->chat_id,
                            str_replace(
                                ['{cor}'],
                                [self::getCor($cor, $data->plataforma->translate)],
                                $data->plataforma->translate->MSG_CONFIRMADO_AGUARDANDO
                            ),
                            $botao
                        );
                        
                        while ($call_status() == 'EXECUTANDO') {
                            $sinal = DoubleSinal::buscarSinal([], $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                            if (!$service)
                                $service = $data->plataforma->service;

                            $retornoJogada = $service->jogar($data->usuario, $cor, $valor);
                            if ($retornoJogada == '') {
                                if ($protecao == 0) {
                                    $telegram->sendMessage(
                                        $data->usuario->chat_id,
                                        str_replace(
                                            ['{cor}'],
                                            [self::getCor($cor, $data->plataforma->translate)],
                                            $data->plataforma->translate->MSG_OPERACAO_ENTRADA_CONFIRMADA,
                                        ),
                                        $botao
                                    );

                                    $message = str_replace(
                                        ['{cor}', '{valor}'],
                                        [self::getCor($cor, $data->plataforma->translate), number_format($valor, 2, ',', '.')],
                                        $data->plataforma->translate->MSG_OPERACAO_ENTRADA_REALIZADA
                                    );  
                                    
                                    if ($valor > $data->usuario->valor)
                                        $message .= $data->plataforma->translate->MSG_OPERACAO_ENTRADA_CICLO;
                                    
                                    if ($data->usuario->status == 'DEMO') {
                                        $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                        $data->usuario->demo_jogadas -= 1;
                                        $data->usuario->saveInTransaction();
                                        $message .= str_replace(
                                            ['{demo_jogadas}'],
                                            [$data->usuario->demo_jogadas],
                                            $data->plataforma->translate->MSG_INICIO_ROBO_7
                                        );
                                    }
                                }
                                else
                                    $message = str_replace(
                                        ['{protecao}', '{valor}'],
                                        [$protecao, number_format($valor, 2, ',', '.')],
                                        $data->plataforma->translate->MSG_OPERACAO_MARTINGALE
                                    );

                                $telegram->sendMessage($data->usuario->chat_id, $message, $botao);

                                $sinal = DoubleSinal::buscarSinal($sinal, $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                                $numero_retornado = $sinal[0]['numero'];
                                $cor_retornada = $sinal[0]['cor'];

                                if (isset($historico['estrategia'])) {
                                    $estrategia = TUtils::openFakeConnection('double', function() use($historico){
                                        return new DoubleEstrategia($historico['estrategia'], false);
                                    });
                                    $win = $estrategia->processarRetorno($sinal);
                                } else 
                                    $win = $cor == $cor_retornada;

                                if ($win) {
                                    $telegram->sendMessage($data->usuario->chat_id, $data->plataforma->translate->MSG_BET_3, $botao);
                                    if ($cor_retornada == 'white')
                                        $valor *= 14;
                                    $lucro = self::criarUsuarioHistorico($data->usuario, $valor);
                                    self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                } else {
                                    $lucro = self::criarUsuarioHistorico($data->usuario, -1 * $valor);
                                }

                                $ocorreu_stop_loss = -$data->usuario->stop_loss >= $lucro;
                                $ocorreu_stop_win = $data->usuario->stop_win <= $lucro;
                                if ($ocorreu_stop_loss or $ocorreu_stop_win) {
                                    if ($ocorreu_stop_loss){
                                        $message = $data->plataforma->translate->MSG_BET_4;
                                        self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                    }
                                    else if ($ocorreu_stop_win)
                                        $message = $data->plataforma->translate->MSG_BET_5;
                                    
                                    $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                    $data->usuario->robo_iniciar = 'N';
                                    $data->usuario->robo_status = 'PARANDO';
                                    $data->usuario->saveInTransaction();
                                    $telegram->sendMessage($data->usuario->chat_id, $message, $botao_inicio);
                                    break;
                                }

                                if ($win) {
                                    break;
                                } else if ($protecao == $data->usuario->protecao){
                                    $telegram->sendMessage($data->usuario->chat_id, $data->plataforma->translate->MSG_BET_6, $botao);
                                    self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                    break;
                                } else {
                                    sleep(1);
                                }
                            } elseif ($retornoJogada == 'saldo_insuficiente') {
                                self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);

                                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                $data->usuario->robo_iniciar = 'N';
                                $data->usuario->robo_status = 'PARANDO';
                                $data->usuario->saveInTransaction();
                                $telegram->sendMessage(
                                    $data->usuario->chat_id, 
                                    $data->plataforma->translate->MSG_BET_7,
                                    $botao_inicio
                                );
                                break;
                            } else {
                                TUtils::openConnection('double', function() use ($retornoJogada, $data) {
                                    $error = new DoubleErros();
                                    $error->classe = 'TDoubleSinais';
                                    $error->metodo = 'executar_usuario';
                                    $error->erro = $retornoJogada;
                                    $error->detalhe = "Usuário: " . $data->usuario->nome_usuario . '[' . $data->usuario->chat_id . ']';
                                    $error->plataforma_id = $data->plataforma->id;
                                    $error->save();
                                });
                            }

                            if ($data->usuario->ultimo_saldo + $lucro <= $data->plataforma->valor_minimo) {
                                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                $data->usuario->robo_iniciar = 'N';
                                $data->usuario->robo_status = 'PARANDO';
                                $data->usuario->saveInTransaction();
                                $telegram->sendMessage(
                                    $data->usuario->chat_id, 
                                    $data->plataforma->translate->MSG_BET_7,
                                    $botao_inicio
                                );
                                self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                break;
                            }
                            $protecao += 1;
                            $valor *= 2;
                        }
                    } finally {
                        $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                        if ($data->usuario->status == 'DEMO' and $data->usuario->demo_jogadas == 0) {
                            self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);

                            $data->usuario->status = 'AGUARDANDO_PAGAMENTO';
                            $data->usuario->roboStatus = 'PARANDO';
                            $data->usuario->robo_iniciar = 'N';

                            $botaoAgPgamento = [
                                "resize_keyboard" => true, 
                                "keyboard" => [
                                    [["text" => $data->plataforma->translate->BOTAO_JA_ASSINEI]], 
                                    [["text" => $data->plataforma->translate->BOTAO_QUERO_ASSINAR]], 
                                ]
                            ];
                            
                            if ($data->plataforma->translate->MSG_STATUS_AG_PGTO == '')
                            {
                                $botaoAgPgamento = ["remove_keyboard" => true];
                            }

                            $message = $data->plataforma->translate->MSG_BET_9;
                            $telegram->sendMessage(
                                $data->usuario->chat_id, 
                                $message,
                                $botaoAgPgamento
                            );

                            if ($data->plataforma->translate->MSG_STATUS_AG_PGTO == '')
                            {
                                $message = str_replace(
                                    ['{usuario}'],
                                    [$data->usuario->nome],
                                    $data->plataforma->translate->MSG_AG_PAGAMENTO_SUPORTE,
                                );
                                $telegram->sendMessage(
                                    $data->usuario->chat_id, 
                                    $message,
                                    [
                                        "resize_keyboard" => true, 
                                        "inline_keyboard" => [
                                            [["text" => $data->plataforma->translate->MSG_SUPORTE,  "url" => $data->plataforma->url_suporte]], 
                                        ]
                                    ]
                                );
                            }

                        }
                        $data->usuario->robo_processando_jogada = 'N';
                        $data->usuario->saveInTransaction();
                    }
                }
            } catch (\Throwable $e) 
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_usuario';
                    $error->erro = $mensagem;
                    $error->detalhe = "Usuário: " . $data->usuario->nome_usuario . '[' . $data->usuario->chat_id . ']';
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });

            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_usuario';
                    $error->erro = $mensagem;
                    $error->detalhe = "Usuário: " . $data->usuario->nome_usuario . '[' . $data->usuario->chat_id . ']';
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            }
        } finally
        {
            $service = null;
            $status = $data->usuario->roboStatus;
            if ($status != 'EXECUTANDO')
                $data->usuario->roboStatus = 'PARADO';
        }

        if ($status == 'EXECUTANDO')
        {
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });
    
            $data->token = $token;
            $data->tipo = 'cmd';
            $data->inicio = false;
            $data->historico = $historico;

            unset($data->usuario);
            unset($data->plataforma);

            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario', $data);
        }
    }

    public static function criarUsuarioHistorico($usuario, $valor)
    {
        TUtils::openConnection('double', function() use ($usuario, $valor) {
            $bet = new DoubleUsuarioHistorico;
            $bet->sequencia = $usuario->robo_sequencia;
            $bet->usuario_id = $usuario->id;
            $bet->valor = $valor;
            $bet->save();
        });

        return $usuario->lucro;
    }

    public static function gerarUsuarioStatus($usuario, $lucro, $cor, $telegram)
    {
        // $banca = number_format($usuario->ultimo_saldo + $lucro, 2, ',', '.');
        sleep(2);
        $saldo = $usuario->plataforma->service->saldo($usuario);
        $banca = number_format($saldo, 2, ',', '.');
        // $lucro = number_format($lucro, 2, ',', '.');
        $lucro = number_format($saldo - $usuario->ultimo_saldo, 2, ',', '.');
        $cor_result = self::getCor($cor, $usuario->plataforma->translate);

        $telegram->sendMessage(
            $usuario->chat_id, 
            str_replace(
                ['{cor}', '{lucro}', '{banca}'],
                [$cor_result, $lucro, $banca],
                $usuario->plataforma->translate->MSG_BET_10
            )
        );
    }

    public static function gerarStatus($telegram, $data)
    {
        $dados = TUtils::openFakeConnection('double', function() use ($data){
            $conn = TTransaction::get(); 
            return $conn->query("SELECT tipo, count(1) total FROM double_historico 
                                  WHERE plataforma_id = {$data->plataforma->id}
                                    AND canal_id = {$data->canal->id}
                                    AND tipo IN ('WIN','LOSS')
                                    AND DATE(created_at) = curdate() GROUP BY tipo");
        });

        $win = 0;
        $loss = 0;
        while ($row = $dados-> fetchObject())
        {
            if ($row->tipo == 'WIN')
                $win = $row->total;
            else
                $loss = $row->total;
        }

        $percentual = 0;
        $total = $win + $loss;
        if ($total > 0)
            $percentual = round(($win / $total) * 100, 1);
        $telegram->sendMessage(
            $data->canal->channel_id,
            str_replace(
                ['{win}', '{loss}', '{percentual}'],
                [$win, $loss, $percentual],
                $data->plataforma->translate->MSG_SINAIS_PARCIAL_DIA,
            ),
        );

        $protecoes = $data->canal->protecoes;
        $total_loss = pow(2, $protecoes + 1) - 1;
        $acertos = $win - ($loss * $total_loss);
        $valor = ($acertos * 20);
        if ($data->canal->exibir_projecao == 'Y' and $valor > 0)
        {
            $valor = number_format($valor, 2, ',', '.');
            $banca = number_format($total_loss * 20 * 1.333, 2, ',', '.');
            $telegram->sendMessage(
                $data->canal->channel_id,
                str_replace(
                    ['{banca}', '{valor}'],
                    [$banca, $valor],
                    $data->plataforma->translate->MSG_SINAIS_PROJECAO,
                ),
            );
        }
    }

    public static function getCor($cor, TDoubletranslate $translate, $completo = true) {
        switch ($cor) {
            case 'red':
                $cor_result = $completo ? $translate->COLOR_RED : $translate->RED;
                break;
            case 'black':
                $cor_result =  $completo ? $translate->COLOR_BLACK : $translate->BLACK;
                break;
            case 'lo':
                $cor_result = 'LO';
                break;
            case 'hi':
                $cor_result = 'HI';
                break;

            default:
                $cor_result =  $completo ? $translate->COLOR_WHITE : $translate->WHITE;
                break;
        }

        return $cor_result;
    }

    public static function buscarNomeEstrategia($historico)
    {
        if (!isset($historico['estrategia']))
            return '' ;
        
        return TUtils::openFakeConnection('double', function() use ($historico){
            $obj = new DoubleEstrategia(historico['estrategia'], false);
            if ($obj)
                return $obj->nome;
            else
                return '';
        });  
    }
}
