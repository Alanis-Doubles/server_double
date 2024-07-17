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

        if (!isset($data->ultimo_sinal))
            $data->ultimo_sinal = [];
        $ultimo_sinal = $data->ultimo_sinal;
        $service = null;
        $sair = false;
        try
        {
            try {
                if (!$service)
                    $service = $data->plataforma->service;

                $ultimo_sinal = $service->aguardarSinal($ultimo_sinal);
                $sinal = TUtils::openConnection('double', function() use ($data, $service, $ultimo_sinal) {
                    $sinal = new DoubleSinal();
                    $sinal->plataforma_id = $data->plataforma->id;
                    $sinal->numero = $service->ultimoSinal();
                    $sinal->cor = $data->plataforma->service->cores()[$sinal->numero];
                    $sinal->id_referencia = $ultimo_sinal->id;
                    $sinal->save();

                    return $sinal;
                });

                $sair = TUtils::openConnection('double', function () use($sinal){
                    $existe = DoubleSinal::where('id_referencia', '=', $sinal->id_referencia)
                        ->where('plataforma_id', '=', $sinal->plataforma_id)
                        ->where('id', '<', $sinal->id)
                        ->first();

                    if ($existe) {
                        $sinal->delete();
                        return true;
                    } else
                        return false;
                });

                $status = $data->plataforma->statusSinais;
            } catch (\Throwable $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                if (!str_contains($mensagem, "Broken frame") and !str_contains($mensagem, "id_referencia"))
                {
                    TUtils::openConnection('double', function() use ($mensagem, $data) {
                        $error = new DoubleErros();
                        $error->classe = 'TDoubleSinais';
                        $error->metodo = 'executar';
                        $error->erro = $mensagem;
                        $error->plataforma_id = $data->plataforma->id;
                        $error->save();
                    });
                }
            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                if (!str_contains($mensagem, "Broken frame") and !str_contains($mensagem, "id_referencia"))
                {
                    TUtils::openConnection('double', function() use ($mensagem, $data) {
                        $error = new DoubleErros();
                        $error->classe = 'TDoubleSinais';
                        $error->metodo = 'executar';
                        $error->erro = $mensagem;
                        $error->plataforma_id = $data->plataforma->id;
                        $error->save();
                    });
                }
            }
        } finally
        {
            $service = null;
            $status = $data->plataforma->statusSinais;
            if ($status != 'EXECUTANDO')
                $data->plataforma->statusSinais = 'PARADO';
        }

        if ($status == 'EXECUTANDO' and !$sair) 
        {
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
            if (isset($param['entrada_id']))
                $historico->entrada_id = $param['entrada_id'];
            if (isset($param['usuario_id']))
                $historico->usuario_id = $param['usuario_id'];
            if (isset($param['gale']))
                $historico->gale = $param['gale'];
            $historico->save();

            return [
                'tipo' => $historico->tipo, 
                'estrategia' => $historico->estrategia,
                'cor' => $historico->cor, 
                'informacao' => $historico->informacao,
                'created_at' => $historico->created_at,
                'id' => $historico->id
            ];
        });

        self::$ultimo_historico = $historico;
        return $historico;
    }

    public static function registrar_enviar_sinal_canal($param)
    {
        $canal = null;

        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $ret = TUtils::openConnection('double', function () use ($plataforma, $param) {
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
            if (isset($param['entrada_id']))
                $historico->entrada_id = $param['entrada_id'];
            if (isset($param['gale']))
                $historico->gale = $param['gale'];
            $historico->save();

            return ['historico' => $historico, 'plataforma' => $plataforma, 'canal' => $canal];
        });

        $plataforma = $ret['plataforma'];
        $canal = $ret['canal'];

        $historico = [
            'tipo' => $ret['historico']->tipo, 
            'estrategia' => $ret['historico']->estrategia,
            'cor' => $ret['historico']->cor, 
            'informacao' => $ret['historico']->informacao,
            'created_at' => $ret['historico']->created_at,
            'id' => $ret['historico']->id
        ];

        if ($plataforma->ativo = 'Y' and $plataforma->tipo_sinais == 'PROPAGA_VALIDA_SINAL') {
            $sinal = TUtils::openFakeConnection('double', function () use ($plataforma) {
                $obj = DoubleSinal::select('TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_since_last_signal')
                    ->where('plataforma_id', '=', $plataforma->id)
                    ->last();

                return $obj->seconds_since_last_signal;
            });

            if ($sinal and $sinal > 60) {
                $plataforma->status_sinais = 'PARADO';
                $plataforma->saveInTransaction();

                $token = TUtils::openFakeConnection('permission', function () {
                    $login = TSession::getValue('login');
                    $user = SystemUser::validate($login);
                    return ApplicationAuthenticationRestService::getToken($user);
                });

                $data = new stdClass;
                $data->inicio = true;
                $data->token = $token;
                $data->plataforma_id = $plataforma->id;
                $data->tipo = 'cmd';
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar', $data);
            }
        }

        if ($canal) {
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });

            $data = new stdClass;
            $data->token = $token;
            $data->plataforma_id = $plataforma->id;
            $data->tipo = 'cmd';
            $data->inicio = false;
            $data->canal_id = $canal->id;
                
            if ($plataforma->tipo_sinais == 'PROPAGA_VALIDA_SINAL')
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal_propagar_validar_sinal', $data);
            if ($plataforma->tipo_sinais == 'PROPAGA_OUTRO' or $plataforma->tipo_sinais == 'NAO_GERA')
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal_propagar_sinal', $data);
        }

        $data = new stdClass;
        $data->inicio = 'Y';
        TDoubleUtils::cmd_run('TDoubleSinais', 'validar_double_sinais', $data);

        self::$ultimo_historico = $historico;
        return $historico;
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
    
        $telegram = $data->canal->telegram;
        if (!$telegram)
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
            $data->sinal = [];
        }

        if (!isset($data->sinal))
            $data->sinal = [];
        
        $service = null;
        try
        {
            $sinal = DoubleSinal::buscarSinal($data->sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
            try {
                if ($sinal) {
                    if (!$service)
                        $service = $data->plataforma->service;

                    $estrategias = TUtils::openFakeConnection('double', function() use ($data) {
                        return DoubleEstrategia::where('canal_id', '=', $data->canal->id)
                            ->where('ativo', '=', 'Y')
                            ->where('usuario_id', 'is', null)
                            ->where('deleted_at', 'is', null)
                            ->orderBy('ordem, id')
                            ->load();
                    });

                    foreach ($estrategias as $key => $estrategia) {
                        if ($estrategia->validar($sinal, $service)) {
                            if ($estrategia->resultado == 'break')
                                break;

                            if ($estrategia->aguardarProximoSinal()) {
                                $message = $telegram->sendMessage(
                                    $data->canal->channel_id,
                                    $data->plataforma->translate->MSG_SINAIS_OPORTUNIDADE,
                                );

                                $payload = [
                                    'plataforma' => strtolower($data->plataforma->nome),
                                    'idioma' => $data->plataforma->idioma,
                                    'tipo' => 'POSSIVEL',
                                    'channel_id' => $data->canal->channel_id
                                ];
                                self::registrar($payload);

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
                                'estrategia_id' => $estrategia->id, 
                                'gale' => 0, 
                                'informacao' => "🎯 Após [{$ultimo_numero}]"
                            ];

                            $historico = self::registrar($payload);
                            $entrada_id = self::$ultimo_historico['id'];

                            $botao = [];
                            if ($data->plataforma->url_grupo_vip)
                                $botao[] = [["text" => $data->plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $data->plataforma->url_grupo_vip]];
                            if ($data->plataforma->url_cadastro)
                                $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $data->plataforma->url_cadastro]];
                            if ($data->plataforma->url_tutorial)
                                $botao[] = [["text" => str_replace(['{plataforma}'], [$data->plataforma->nome], $data->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $data->plataforma->url_tutorial]];
                            if ($data->plataforma->url_suporte)
                                $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $data->plataforma->url_suporte]];
                            if ($data->plataforma->url_robo)
                                $botao[] = [["text" => $data->plataforma->translate->MSG_ROBO_AUTOMATICO,  "url" => $data->plataforma->url_robo]];

                            $telegram->sendMessage(
                                $data->canal->channel_id,
                                str_replace(
                                    ['{estrategia}', '{cor}', '{ultimo_numero}', '{ultima_cor}', '{informacao}', '{protecoes}'],
                                    [
                                        $estrategia->nome, 
                                        self::getCor($cor_retornada, $data->plataforma->translate), 
                                        $ultimo_numero, 
                                        self::getCor($ultima_cor, $data->plataforma->translate, false), 
                                        isset($historico['informacao']) ? $historico['informacao'] : '',
                                        $data->canal->protecoes
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
                                $cor_retornada = $sinal[0]['cor'];

                                if (!$win and $data->canal->protecao_branco == 'Y')
                                    $win = $cor_retornada == 'white';

                                if ($message)
                                    $telegram->deleteMessage($data->canal->channel_id, $message->result->message_id);

                                if ($win) {
                                    $telegram->sendMessage(
                                        $data->canal->channel_id,
                                        $cor_retornada == 'white' ? $data->plataforma->translate->MSG_SINAIS_WIN_BRANCO : $data->plataforma->translate->MSG_SINAIS_WIN,
                                    );

                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'WIN',
                                        'channel_id' => $data->canal->channel_id,
                                        'estrategia_id' => $estrategia->id,
                                        'entrada_id' => $entrada_id,
                                        'gale' => $protecao
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
                                            str_replace(
                                                ["{cor_retornada}"],
                                                [self::getCor($cor_retornada, $data->plataforma->translate)],
                                                $data->plataforma->translate->MSG_SINAIS_LOSS,
                                            )
                                        );
                                        
                                        $payload = [
                                            'plataforma' => strtolower($data->plataforma->nome),
                                            'idioma' => $data->plataforma->idioma,
                                            'cor' => $cor_retornada,
                                            'tipo' => 'LOSS',
                                            'channel_id' => $data->canal->channel_id,
                                            'estrategia_id' => $estrategia->id,
                                            'entrada_id' => $entrada_id,
                                            'gale' => $protecao
                                        ];
                                        self::registrar($payload);
                                        $data->canal = DoubleCanal::identificar($data->canal->id);
                                        self::gerarStatus($telegram, $data);
                                        break;
                                    } else {
                                        $message = $telegram->sendMessage(
                                            $data->canal->channel_id,
                                            str_replace(
                                                ['{protecao}', '{n_protecao}'],
                                                [$gales[$protecao], $protecao + 1],
                                                $data->plataforma->translate->MSG_SINAIS_GALE,
                                            ),
                                        );

                                        $payload = [
                                            'plataforma' => strtolower($data->plataforma->nome),
                                            'idioma' => $data->plataforma->idioma,
                                            'cor' => $cor_retornada,
                                            'tipo' => 'GALE',
                                            'channel_id' => $data->canal->channel_id,
                                            'estrategia_id' => $estrategia->id,
                                            'entrada_id' => $entrada_id,
                                            'gale' => $protecao
                                        ];
                                        self::registrar($payload);
                                    }
                                }
                                $protecao += 1;
                            }
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = $trace;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = $trace;
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

        $telegram = $data->canal->telegram;
        if (!$telegram)
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
            $message = DoubleConfiguracao::getConfiguracao('reals_message_id');
            $historico = isset($data->historico) ? $data->historico : [];
            try {
                $continue = true;
                $historico = DoubleHistorico::buscarHistorico($historico, $data->canal->inicioSinais, $data->plataforma->id, $data->canal->id, $call_status);
                if ($historico == []) {
                    $continue = false;
                }

                if ($continue) {
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
                            if ($data->plataforma->url_robo)
                                $botao[] = [["text" => $data->plataforma->translate->MSG_ROBO_AUTOMATICO,  "url" => $data->plataforma->url_robo]];

                        $telegram->sendMessage(
                            $data->canal->channel_id,
                            str_replace(
                                ['{estrategia}', '{cor}', '{informacao}', '{protecoes}'],
                                [
                                    self::buscarNomeEstrategia($historico), 
                                    self::getCor($cor_retornada, 
                                    $data->plataforma->translate), 
                                    isset($historico['informacao']) ? $historico['informacao'] : '',
                                    $data->canal->protecoes
                                ],
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
                    } elseif ($historico['tipo'] == 'GALE') {
                        $message = ($telegram->sendMessage(
                            $data->canal->channel_id,
                            str_replace(
                                ['{informacao}'],
                                isset($historico['informacao']) ? $historico['informacao'] : '',
                                $data->plataforma->translate->MSG_SINAIS_GALE,
                            ),
                        ))->result->message_id;
                    }
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

        DoubleConfiguracao::setConfiguracao('reals_message_id', $message);
    }

    public static function executar_canal_propagar_validar_sinal($param)
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

        $telegram = $data->canal->telegram;
        if (!$telegram)
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
                $continue = true;
                $historico = DoubleHistorico::buscarHistorico($historico, $data->canal->inicioSinais, $data->plataforma->id, $data->canal->id, $call_status);
                if ($historico == []) {
                    $continue = false;
                }

                if ($continue) {
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
                        $data->canal->processando_jogada = 'Y';
                        $data->canal->saveInTransaction();

                        $cor = $historico['cor'];

                        $botao = [];
                        if ($data->plataforma->url_grupo_vip)
                            $botao[] = [["text" => $data->plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $data->plataforma->url_grupo_vip]];
                        if ($data->plataforma->url_cadastro)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $data->plataforma->url_cadastro]];
                        if ($data->plataforma->url_tutorial)
                            $botao[] = [["text" => str_replace(['{plataforma}'], [$data->plataforma->nome], $data->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $data->plataforma->url_tutorial]];
                        if ($data->plataforma->url_suporte)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $data->plataforma->url_suporte]];
                        if ($data->plataforma->url_robo)
                            $botao[] = [["text" => $data->plataforma->translate->MSG_ROBO_AUTOMATICO,  "url" => $data->plataforma->url_robo]];

                        $telegram->sendMessage(
                            $data->canal->channel_id,
                            str_replace(
                                ['{estrategia}', '{cor}', '{informacao}', '{protecoes}'],
                                [
                                    self::buscarNomeEstrategia($historico), 
                                    self::getCor($cor, $data->plataforma->translate), 
                                    isset($historico['informacao']) ? $historico['informacao'] : '',
                                    $data->canal->protecoes
                                ],
                                $data->plataforma->translate->MSG_SINAIS_ENTRADA_CONFIRMADA,
                            ),
                            [
                                "resize_keyboard" => true, 
                                "inline_keyboard" => $botao
                            ]
                        );

                        $entrada_id = $historico['id'];
                        $sinal = DoubleSinal::buscarSinal([], $data->canal->inicioSinais, $data->plataforma->id, $call_status);
                        $protecao = 0;
                        $message = null;
                        while (true) {
                            $sinal = DoubleSinal::buscarSinal($sinal, $data->canal->inicioSinais, $data->plataforma->id, $call_status);
                            $numero_retornado = $sinal[0]['numero'];
                            $cor_retornada = $sinal[0]['cor'];

                            $win = $cor == $cor_retornada;

                            if (!$win and $data->canal->protecao_branco == 'Y')
                                $win = $cor_retornada == 'white';
                            
                            if ($message)
                                $telegram->deleteMessage($data->canal->channel_id, $message->result->message_id);

                            if ($win) {
                                $telegram->sendMessage(
                                    $data->canal->channel_id,
                                    $cor_retornada == 'white' ? $data->plataforma->translate->MSG_SINAIS_WIN_BRANCO : $data->plataforma->translate->MSG_SINAIS_WIN,
                                );

                                $payload = [
                                    'plataforma' => strtolower($data->plataforma->nome),
                                    'idioma' => $data->plataforma->idioma,
                                    'cor' => $cor_retornada,
                                    'tipo' => 'WIN',
                                    'channel_id' => $data->canal->channel_id,
                                    'entrada_id' => $entrada_id,
                                    'estrategia_id' => isset($historico['estrategia']) ? $historico['estrategia'] : null,
                                    'gale' => $protecao
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
                                        str_replace(
                                            ["{cor_retornada}"],
                                            [self::getCor($cor_retornada, $data->plataforma->translate)],
                                            $data->plataforma->translate->MSG_SINAIS_LOSS,
                                        ),
                                    );
                                    
                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'LOSS',
                                        'channel_id' => $data->canal->channel_id,
                                        'entrada_id' => $entrada_id,
                                        'estrategia_id' => isset($historico['estrategia']) ? $historico['estrategia'] : null,
                                        'gale' => $protecao
                                    ];
                                    self::registrar($payload);
                                    $data->canal = DoubleCanal::identificar($data->canal->id);
                                    self::gerarStatus($telegram, $data);
                                    break;
                                } else {
                                    $message = $telegram->sendMessage(
                                        $data->canal->channel_id,
                                        str_replace(
                                            ['{protecao}', '{n_protecao}'],
                                            [$gales[$protecao], $protecao + 1],
                                            $data->plataforma->translate->MSG_SINAIS_GALE,
                                        ),
                                    );

                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'GALE',
                                        'channel_id' => $data->canal->channel_id,
                                        'entrada_id' => $entrada_id,
                                        'estrategia_id' => isset($historico['estrategia']) ? $historico['estrategia'] : null,
                                        'gale' => $protecao
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
                $mensagem = $e->getMessage();
                TUtils::openConnection('double', function() use ($mensagem, $data) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal_propagar_validar_sinal';
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
                    $error->metodo = 'executar_canal_propagar_validar_sinal';
                    $error->erro = $mensagem;
                    $error->detalhe = "Canal: " . $data->canal->nome . '[' . $data->canal->channel_id . ']';
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            }
        } finally
        {
            $data->canal->processando_jogada = 'N';
            $data->canal->saveInTransaction();

            $status = $call_status();
            if ($status != 'EXECUTANDO')
                $data->canal->statusSinais = 'PARADO';
        }
    }

    public static function incrementar_entrada_automatica($usuario, $botao, $telegram) 
    {
        if ($usuario->robo_iniciar_apos_loss == 'N')
            return;

        $usuario->entrada_automatica_qtd_loss += 1;
        $usuario->saveInTransaction();

        if ($usuario->entrada_automatica_qtd_loss == $usuario->entrada_automatica_total_loss) {
            $usuario->entrada_automatica_qtd_loss = 0;
            $usuario->robo_iniciar_apos_loss = 'N';
            $usuario->robo_processando_jogada = 'N';
            //$usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
            $usuario->saveInTransaction();
            $telegram->sendMessage(
                $usuario->chat_id,
                $usuario->plataforma->translate->MSG_OPERACAO_IDENTIFICADO_LOSS,
                $botao
            );
        }
    }

    public static function zerar_entrada_automatica($usuario)
    {
        $usuario->entrada_automatica_qtd_loss = 0;
        $usuario->saveInTransaction();
    }

    public static function executar_usuario_sinais($param)
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

        $data->canal = $data->usuario->canal;

        if (!isset($data->tipo) or (isset($data->tipo) and $data->tipo != 'cmd'))
            throw new Exception($data->plataforma->translate->MSG_OPERACAO_METODO_NAO_SUPORTADO);

        $call_status = function() use ($data) {
            return $data->usuario->roboStatus;
        };
    
        if ($data->usuario->executando_usuario_sinais == 'N') 
        {
            $data->usuario->executando_usuario_sinais = 'Y';
            $data->usuario->saveInTransaction();
            $data->sinal = [];
        }

        if (!isset($data->sinal))
            $data->sinal = [];
        
        $service = null;
        try
        {
            $sinal = DoubleSinal::buscarSinal($data->sinal, $data->usuario->roboInicio, $data->plataforma->id, $call_status);
            try {
                if ($sinal) {
                    if (!$service)
                        $service = $data->plataforma->service;

                    $estrategias = TUtils::openFakeConnection('double', function() use ($data) {
                        return DoubleEstrategia::where('canal_id', '=', $data->canal->id)
                            ->where('ativo', '=', 'Y')
                            ->where('usuario_id', '=', $data->usuario->id)
                            ->where('deleted_at', 'is', null)
                            ->orderBy('ordem, id')
                            ->load();
                    });

                    foreach ($estrategias as $key => $estrategia) {
                        if ($estrategia->validar($sinal, $service, false)) {
                            if ($estrategia->resultado == 'break')
                                break;

                            $ultimo_numero = $sinal[0]['numero'];
                            $ultima_cor = $sinal[0]['cor'];
                            $cor_retornada = $estrategia->resultado;

                            $payload = [
                                'plataforma' => strtolower($data->plataforma->nome),
                                'idioma' => $data->plataforma->idioma,
                                'cor' => $cor_retornada,
                                'tipo' => 'ENTRADA',
                                'channel_id' => $data->canal->channel_id,
                                'estrategia_id' => $estrategia->id,
                                'usuario_id' => $data->usuario->id,
                                'gale' => 0, 
                                'informacao' => "🎯 Após [{$ultimo_numero}]"
                            ];

                            $historico = self::registrar($payload);
                            $entrada_id = self::$ultimo_historico['id'];

                            $protecao = 0;
                            $message = null;
                            while (true) {
                                $sinal = DoubleSinal::buscarSinal($sinal, $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                                $win = $estrategia->processarRetorno($sinal);
                                $cor_retornada = $sinal[0]['cor'];

                                if (!$win and $estrategia->protecao_branco == 'Y')
                                    $win = $cor_retornada == 'white';

                                if ($win) {
                                    $payload = [
                                        'plataforma' => strtolower($data->plataforma->nome),
                                        'idioma' => $data->plataforma->idioma,
                                        'cor' => $cor_retornada,
                                        'tipo' => 'WIN',
                                        'channel_id' => $data->canal->channel_id,
                                        'estrategia_id' => $estrategia->id,
                                        'entrada_id' => $entrada_id,
                                        'usuario_id' => $data->usuario->id,
                                        'gale' => $protecao
                                    ];
                                    self::registrar($payload);
                                    $data->canal = DoubleCanal::identificar($data->canal->id);
                                    break;
                                } else {
                                    $gales = ['primeira', 'segunda', 'terceira', 'quarta', 'quinta', 'sexta'];
                                    if ($protecao == $estrategia->protecoes) {                                       
                                        $payload = [
                                            'plataforma' => strtolower($data->plataforma->nome),
                                            'idioma' => $data->plataforma->idioma,
                                            'cor' => $cor_retornada,
                                            'tipo' => 'LOSS',
                                            'channel_id' => $data->canal->channel_id,
                                            'estrategia_id' => $estrategia->id,
                                            'entrada_id' => $entrada_id,
                                            'usuario_id' => $data->usuario->id,
                                            'gale' => $protecao
                                        ];
                                        self::registrar($payload);
                                        $data->canal = DoubleCanal::identificar($data->canal->id);
                                        break;
                                    } else {
                                        $payload = [
                                            'plataforma' => strtolower($data->plataforma->nome),
                                            'idioma' => $data->plataforma->idioma,
                                            'cor' => $cor_retornada,
                                            'tipo' => 'GALE',
                                            'channel_id' => $data->canal->channel_id,
                                            'estrategia_id' => $estrategia->id,
                                            'entrada_id' => $entrada_id,
                                            'usuario_id' => $data->usuario->id,
                                            'gale' => $protecao
                                        ];
                                        self::registrar($payload);
                                    }
                                }
                                $protecao += 1;
                            }
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = $trace;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_canal';
                    $error->erro = $mensagem;
                    $error->detalhe = $trace;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });
            }
        } finally
        {
            $service = null;          
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

            unset($data->usuario);
            unset($data->plataforma);
            unset($data->canal);
                        
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario_sinais', $data);
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

        $iniciar_apos = $data->plataforma->translate->BOTAO_INICIAR_LOSS;
        if ($data->usuario->entrada_automatica_tipo == 'WIN')
            $iniciar_apos = $data->plataforma->translate->BOTAO_INICIAR_WIN;

        $modo_treinamento = $data->plataforma->translate->BOTAO_MODO_TREINAMENTO_ATIVO;
        $modo_real = $data->plataforma->translate->BOTAO_MODO_REAL_INATIVO;
        if ($data->usuario->modo_treinamento == 'N') {
            $modo_treinamento = $data->plataforma->translate->BOTAO_MODO_TREINAMENTO_INATIVO;
            $modo_real = $data->plataforma->translate->BOTAO_MODO_REAL_ATIVO;
        }
            
        $botao_inicio = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $data->plataforma->translate->BOTAO_CONFIGURAR]],
                    [["text" => $modo_treinamento], ["text" => $modo_real]], 
                    [["text" => $data->plataforma->translate->BOTAO_INICIAR], ["text" => $iniciar_apos]], 
                    [["text" => $data->plataforma->translate->BOTAO_GERAR_ACESSO_APP]]
                ] 
            ];

        $service = null;
        $telegram = $data->usuario->canal->telegram;
        if (!$telegram)
            $telegram = $data->plataforma->telegram;
        try
        {
            if ($data->inicio)
            {
                $data->usuario->roboStatus = 'EXECUTANDO';
                $data->usuario->roboInicio = (new DateTime())->format('Y-m-d H:i:s');
                $data->historico = [];

                $data->usuario->executando_usuario_sinais = 'N';
                $data->usuario->saveInTransaction();

                if ($data->usuario->possui_estrategias and $data->usuario->executando_usuario_sinais == 'N') {
                    $data_novo = clone  $data;
                    $data_novo->token = $token;
                    $data_novo->tipo = 'cmd';
                    $data_novo->inicio = false;
        
                    unset($data_novo->usuario);
                    unset($data_novo->plataforma);
        
                    TDoubleUtils::cmd_run('TDoubleSinais', 'executar_usuario_sinais', $data_novo);
                }        
            }

            $call_status = function() use ($data) {
                return $data->usuario->roboStatus;
            };

            $historico = isset($data->historico) ? $data->historico : [];
            try {
                $historico = DoubleHistorico::buscarHistorico(
                    $historico, 
                    $data->usuario->roboInicio, 
                    $data->plataforma->id, 
                    $data->usuario->canal_id_ref, 
                    $call_status,
                    ($data->usuario->possui_estrategias ? $data->usuario->id : null)
                );

                $continue = true;
                if ($historico == []) {
                    $continue = false;
                }
                
                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);

                $agora = (new DateTime())->format('Y-m-d H:i:s');
                if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta and $data->usuario->usuario_meta->proxima_execucao) 
                {
                    if ($data->usuario->usuario_meta->proxima_execucao > $agora)
                        $continue = false;
                    else {
                        $data->usuario->usuario_meta->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                        $data->usuario->usuario_meta->proxima_execucao = null;
                        $data->usuario->usuario_meta->atualizar($data->usuario);
                        $data->usuario->robo_iniciar_apos_loss = 'Y';
                        self::zerar_entrada_automatica($data->usuario);

                        $entrada = number_format($data->usuario->usuario_meta->valor_entrada, 2, ',', '.');
                        if ($data->usuario->usuario_meta->tipo_entrada == 'PERCENTUAL')
                            $entrada .= "% da banca - R$ " . number_format($data->usuario->usuario_meta->valor_real_entrada, 2, ',', '.');
                        else
                            $entrada = 'R$ ' . ($entrada * 2);
                        $objetivo = number_format($data->usuario->usuario_meta->valor_objetivo, 2, ',', '.');
                        if ($data->usuario->usuario_meta->tipo_objetivo == 'PERCENTUAL')
                            $objetivo .= "% da banca - R$ " . number_format($data->usuario->usuario_meta->valor_real_objetivo, 2, ',', '.');
                        else
                            $objetivo = 'R$ ' . ($objetivo * 2);
                        $periodicidade = number_format($data->usuario->usuario_meta->valor_periodicidade, 0, ',', '.');
                        if ($data->usuario->usuario_meta->tipo_periodicidade == 'HORAS')
                            $periodicidade .= 'hr';
                        else
                            $periodicidade .= 'min';
                        $banca = number_format($data->usuario->usuario_meta->ultimo_saldo, 2, ',', '.');

                        $telegram->sendMessage(
                            $data->usuario->chat_id,
                            str_replace(
                                ['{banca}', '{entrada}', '{objetivo}', '{periodicidade}'],
                                [$banca, $entrada, $objetivo, $periodicidade],
                                $data->plataforma->translate->MSG_META5
                            ),
                            $botao
                        );

                        $telegram->sendMessage(
                            $data->usuario->chat_id,
                            str_replace(
                                ['{quantidade}', '{tipo}'],
                                [$data->usuario->entrada_automatica_total_loss, $data->usuario->entrada_automatica_tipo],
                                $data->plataforma->translate->MSG_INICIO_ROBO_9
                            ),
                            $botao
                        );   
                    }
                }

                if (!$continue)
                    $continue = true;
                elseif ($historico['tipo'] == 'LOSS' and $data->usuario->robo_iniciar_apos_loss == 'Y') {
                    if ($data->usuario->entrada_automatica_tipo == 'LOSS') {
                        self::incrementar_entrada_automatica($data->usuario, $botao, $telegram);
                    } else {
                        self::zerar_entrada_automatica($data->usuario);
                    }
                } elseif ($historico['tipo'] == 'WIN') {
                    if ($data->usuario->entrada_automatica_tipo == 'LOSS') {
                        self::zerar_entrada_automatica($data->usuario);
                    } else {
                        self::incrementar_entrada_automatica($data->usuario, $botao, $telegram);
                    }
                } elseif ($historico['tipo'] == 'ENTRADA' and $data->usuario->robo_iniciar_apos_loss == 'N') {
                    if ($data->usuario->status != 'ATIVO' and $data->usuario->status != 'DEMO') {
                        $data->usuario->robo_iniciar = 'N';
                        $data->usuario->robo_iniciar_apos_loss = 'N';
                        $data->usuario->robo_processando_jogada = 'N';
                        $data->usuario->robo_status = 'PARANDO';
                        $data->usuario->saveInTransaction();

                        if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta)
                        {
                            $data->usuario->usuario_meta->ultimo_saldo = 0;
                            $data->usuario->usuario_meta->inicio_execucao = null;
                            $data->usuario->usuario_meta->proxima_execucao = null;
                            $data->usuario->usuario_meta->saveInTransaction();
                        }
                        return;
                    }

                    $data->usuario->robo_processando_jogada = 'Y';
                    $data->usuario->saveInTransaction();
                    try {
                        $protecao = 0;
                        $estrategia_id = isset($historico['estrategia']) ? $historico['estrategia'] : null;
                        $valor = $data->usuario->valorJogada($estrategia_id);
                        $cor = $historico['cor'];

                        $telegram->sendMessage(
                            $data->usuario->chat_id,
                            str_replace(
                                ['{cor}', '{estrategia}', '{informacao}'],
                                [
                                    self::getCor($cor, $data->plataforma->translate), 
                                    self::buscarNomeEstrategia($historico), 
                                    isset($historico['informacao']) ? $historico['informacao'] : ''
                                ],
                                $data->plataforma->translate->MSG_CONFIRMADO_AGUARDANDO
                            ),
                            $botao
                        );

                        $lucro = 0;
                        $valor_branco = 0;

                        // $sinal = DoubleSinal::buscarSinal([], $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                        $sinal = DoubleSinal::buscarUltimoSinal([], $data->usuario->roboInicio, $data->plataforma->id, $call_status);

                        $meta_atingida = false;
                        // $sinal = [];
                        while ($call_status() == 'EXECUTANDO') {
                            if (!$service)
                                $service = $data->plataforma->service;

                            $max_valor = $data->usuario->valor_max_ciclo;
                            $valor_usuario = $data->usuario->valor;
                            if ($max_valor > 0 and $valor > $max_valor)
                                $valor = $valor_usuario;

                            $retornoJogada = $service->jogar($data->usuario, $cor, $valor);
                            if ($data->usuario->protecao_branco == 'Y') {
                                $valor_branco = round($valor * 0.2, 2);
                                $service->jogar($data->usuario, 'white', $valor_branco);
                            }

                            $_valor_entrada = $valor;
                            $_valor_branco = $valor_branco;
                                    
                            if ($retornoJogada == '') 
                            {
                                if ($protecao == 0) {
                                    $message = str_replace(
                                        ['{cor}', '{valor}', '{branco}', '{estrategia}'],
                                        [
                                            self::getCor($cor, $data->plataforma->translate), 
                                            number_format($valor, 2, ',', '.'),
                                            $valor_branco == 0 ? "" : "🎯 Cor: " . self::getCor('white', $data->plataforma->translate) . " - Valor: R$ " . number_format($valor_branco, 2, ',', '.'). ". ",
                                            self::buscarNomeEstrategia($historico)
                                        ],
                                        $data->plataforma->translate->MSG_OPERACAO_ENTRADA_REALIZADA
                                    );  
                                    
                                    $valor_usuario = $data->usuario->valor;
                                    if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta)
                                        $valor_usuario = $data->usuario->usuario_meta->valor_real_entrada;

                                    if ($valor > $valor_usuario)
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
                                        ['{protecao}', '{valor}', '{cor}', "{branco}", "{estrategia}"],
                                        [
                                            $protecao, 
                                            number_format($valor, 2, ',', '.'),
                                            self::getCor($cor, $data->plataforma->translate), 
                                            $valor_branco == 0 ? "" : "🎯 Cor: " . self::getCor('white', $data->plataforma->translate) . " - Valor: R$ " . number_format($valor_branco, 2, ',', '.'). ". ",
                                            self::buscarNomeEstrategia($historico)
                                        ],
                                        $data->plataforma->translate->MSG_OPERACAO_MARTINGALE,
                                    );

                                $telegram->sendMessage($data->usuario->chat_id, $message, $botao);
                                $sinal = DoubleSinal::buscarUltimoSinal($sinal, $data->usuario->roboInicio, $data->plataforma->id, $call_status);
                                $numero_retornado = $sinal[0]['numero'];
                                $cor_retornada = $sinal[0]['cor'];

                                $win = $cor == $cor_retornada;

                                if (!$win and $data->usuario->protecao_branco == 'Y')
                                    $win = $cor_retornada == 'white';

                                if ($win) {
                                    $telegram->sendMessage(
                                        $data->usuario->chat_id, 
                                        $cor_retornada == 'white' ? $data->plataforma->translate->MSG_SINAIS_WIN_BRANCO : $data->plataforma->translate->MSG_BET_3,
                                        $botao
                                    );
                                    if ($cor_retornada == 'white') {
                                        if ($data->usuario->protecao_branco == 'Y') 
                                            $valor = ($valor_branco * 14) - $valor;
                                        else
                                            $valor = ($valor * 14);
                                    }
                                    else
                                        $valor = $valor - $valor_branco;
                                    
                                    $lucro = self::criarUsuarioHistorico(
                                        $data->usuario, 
                                        $valor, 
                                        $historico['id'],
                                        $_valor_entrada,
                                        $_valor_branco,
                                        $protecao, 
                                        'WIN'
                                    );

                                    $data->usuario->quantidade_loss = 0;
                                    $data->usuario->saveInTransaction();
                                    self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                    $meta_atingida = self::gerarUsuarioMetasStatus($data->usuario, $telegram);
                                } else {
                                    $data->usuario->quantidade_loss += 1;
                                    $data->usuario->saveInTransaction();

                                    $lucro = self::criarUsuarioHistorico(
                                        $data->usuario, 
                                        -1 * ($valor + $valor_branco), 
                                        $historico['id'],
                                        $_valor_entrada,
                                        $_valor_branco,
                                        $protecao,
                                        ($protecao == $data->usuario->protecao ? 'LOSS' : 'GALE')
                                    );
                                }

                                if ($data->usuario->tipo_stop_loss == 'QUANTIDADE')
                                    $ocorreu_stop_loss = $data->usuario->quantidade_loss >= $data->usuario->stop_loss;
                                else
                                    $ocorreu_stop_loss = -$data->usuario->stop_loss >= $lucro;

                                $ocorreu_stop_win = round($data->usuario->stop_win, 1) <= round($lucro, 1);

                                if ($ocorreu_stop_loss or $ocorreu_stop_win) {
                                    $entrada_automatica = false;
                                    if ($ocorreu_stop_loss){
                                        $message = $data->plataforma->translate->MSG_BET_4;
                                        self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                        $meta_atingida = self::gerarUsuarioMetasStatus($data->usuario, $telegram);
                                        if ($data->usuario->entrada_automatica == 'A' or $data->usuario->entrada_automatica == 'B')
                                        {
                                            $entrada_automatica = true;
                                            $message = str_replace(
                                                ['{quantidade}', '{tipo}'],
                                                [$data->usuario->entrada_automatica_total_loss, $data->usuario->entrada_automatica_tipo],
                                                $data->plataforma->translate->MSG_STOP_LOSS_4
                                            );
                                            $botao_inicio = $botao;
                                            $historico = DoubleHistorico::buscarHistorico(
                                                $historico, 
                                                $data->usuario->roboInicio, 
                                                $data->plataforma->id, 
                                                $data->usuario->canal_id_ref, 
                                                $call_status,
                                                ($data->usuario->possui_estrategias ? $data->usuario->id : null)
                                            );
                                        }
                                    }
                                    else if ($ocorreu_stop_win) {
                                        $message = $data->plataforma->translate->MSG_BET_5;
                                        if ($data->usuario->entrada_automatica == 'Y' or $data->usuario->entrada_automatica == 'A')
                                        {
                                            $entrada_automatica = true;
                                            $message = str_replace(
                                                ['{quantidade}', '{tipo}'],
                                                [$data->usuario->entrada_automatica_total_loss, $data->usuario->entrada_automatica_tipo],
                                                $data->plataforma->translate->MSG_STOP_WIN_4
                                            );
                                            $botao_inicio = $botao;
                                            $historico = DoubleHistorico::buscarHistorico(
                                                $historico, 
                                                $data->usuario->roboInicio, 
                                                $data->plataforma->id, 
                                                $data->usuario->canal_id_ref, 
                                                $call_status,
                                                ($data->usuario->possui_estrategias ? $data->usuario->id : null)
                                            );
                                        }
                                    }
                                    
                                    $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                    if ($entrada_automatica)
                                    {
                                        $data->usuario->robo_iniciar_apos_loss = 'Y';
                                        $data->usuario->quantidade_loss = 0;
                                        if ($data->usuario->ciclo != 'A')
                                            $data->usuario->robo_sequencia += 1;
                                        if ($ocorreu_stop_win) 
                                            $data->usuario->robo_sequencia += 1;
                                        $data->usuario->ultimo_saldo = $data->plataforma->service->saldo($data->usuario);
                                    }
                                    else
                                    {
                                        $data->usuario->robo_iniciar = 'N';
                                        $data->usuario->robo_status = 'PARANDO';

                                        if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta)
                                        {
                                            $data->usuario->usuario_meta->ultimo_saldo = 0;
                                            $data->usuario->usuario_meta->inicio_execucao = null;
                                            $data->usuario->usuario_meta->proxima_execucao = null;
                                            $data->usuario->usuario_meta->saveInTransaction();
                                        }
                                    }
                                    $data->usuario->saveInTransaction();
                                    if (!$meta_atingida)
                                        $telegram->sendMessage($data->usuario->chat_id, $message, $botao_inicio);
                                    break;
                                }

                                if ($win) {
                                    break;
                                } else if ($protecao == $data->usuario->protecao){
                                    $telegram->sendMessage($data->usuario->chat_id, $data->plataforma->translate->MSG_BET_6, $botao);
                                    self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                    $meta_atingida = self::gerarUsuarioMetasStatus($data->usuario, $telegram);
                                    break;
                                } else {
                                    sleep(1);
                                }
                            } elseif ($retornoJogada == 'saldo_insuficiente') {
                                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                $data->usuario->robo_iniciar = 'N';
                                $data->usuario->robo_status = 'PARANDO';
                                $data->usuario->saveInTransaction();
                                
                                if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta)
                                {
                                    $data->usuario->usuario_meta->ultimo_saldo = 0;
                                    $data->usuario->usuario_meta->inicio_execucao = null;
                                    $data->usuario->usuario_meta->proxima_execucao = null;
                                    $data->usuario->usuario_meta->saveInTransaction();
                                }

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

                                
                                $telegram->sendMessage(
                                    $data->usuario->chat_id, 
                                    'Entrada abortada, aguardando próximo sinal...\n\nMensagem retornada pela plataforma:\n' . $retornoJogada, 
                                    $botao
                                );
                                
                                break;
                            }

                            if ($data->usuario->ultimo_saldo + $lucro <= $data->plataforma->valor_minimo) {
                                $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                                $data->usuario->robo_iniciar = 'N';
                                $data->usuario->robo_status = 'PARANDO';
                                $data->usuario->saveInTransaction();

                                if ($data->usuario->metas == 'Y' and $data->usuario->usuario_meta)
                                {
                                    $data->usuario->usuario_meta->ultimo_saldo = 0;
                                    $data->usuario->usuario_meta->inicio_execucao = null;
                                    $data->usuario->usuario_meta->proxima_execucao = null;
                                    $data->usuario->usuario_meta->saveInTransaction();
                                }
                                
                                $telegram->sendMessage(
                                    $data->usuario->chat_id, 
                                    $data->plataforma->translate->MSG_BET_7,
                                    $botao_inicio
                                );
                                self::gerarUsuarioStatus($data->usuario, $lucro, $cor_retornada, $telegram);
                                break;
                            }
                            $protecao += 1;

                            if (isset($historico['estrategia']))
                            {
                                $estrategia = TUtils::openFakeConnection('double', function() use ($historico){
                                    return new DoubleEstrategia($historico['estrategia'], false);
                                });

                                if ($estrategia and $estrategia->incrementa_valor_entrada == 'A_CADA_GALE') {
                                    if ($data->usuario->protecao_branco == 'N')
                                        $valor *= round($data->usuario->fator_multiplicador, 2);
                                    else {
                                        $valor = round($valor * 2.5, 2);
                                    }
                                }
                            }
                            else
                                if ($data->usuario->protecao_branco == 'N')
                                    $valor *= round($data->usuario->fator_multiplicador,2);
                                else {
                                    $valor = round($valor * 2.5, 2);
                                }

                            
                        }
                    } finally {
                        $data->usuario = DoubleUsuario::identificar($data->usuario->chat_id, $data->plataforma->id, $data->usuario->canal_id);
                        if ($data->usuario->status == 'DEMO' and $data->usuario->demo_jogadas == 0) {
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
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_usuario';
                    $error->erro = $mensagem . '[' . $data->usuario->chat_id . ']';
                    $error->detalhe = $trace;
                    $error->plataforma_id = $data->plataforma->id;
                    $error->save();
                });

            } catch (Exception $e) // in case of exception
            {
                $service = null;
                $mensagem = $e->getMessage();
                $trace = json_encode($e->getTrace());
                TUtils::openConnection('double', function() use ($mensagem, $data, $trace) {
                    $error = new DoubleErros();
                    $error->classe = 'TDoubleSinais';
                    $error->metodo = 'executar_usuario';
                    $error->erro = $mensagem . '[' . $data->usuario->chat_id . ']';
                    $error->detalhe = $trace;
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

    public static function criarUsuarioHistorico($usuario, $valor, $entrada_id, $valor_entrada, $valor_branco, $gale, $tipo)
    {
        TUtils::openConnection('double', function() use ($usuario, $valor, $entrada_id, $valor_entrada, $valor_branco, $gale, $tipo) {
            $bet = new DoubleUsuarioHistorico;
            $bet->sequencia = $usuario->robo_sequencia;
            $bet->usuario_id = $usuario->id;
            $bet->valor = $valor;
            $bet->entrada_id = $entrada_id;
            $bet->valor_entrada = $valor_entrada;
            $bet->valor_branco = $valor_branco;
            $bet->gale = $gale;
            $bet->tipo = $tipo;
            $bet->robo_inicio = $usuario->robo_inicio;
            $bet->configuracao = $usuario->configuracao_texto;
            $bet->save();
        });

        return $usuario->lucro;
    }

    public static function gerarUsuarioStatus($usuario, $lucro, $cor, $telegram)
    {
        if ($usuario->modo_treinamento == 'Y') {
            $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                    ->where('created_at', '>=', $usuario->robo_inicio)
                    ->sumBy('valor', 'total');
            });

            $banca = number_format($usuario->ultimo_saldo + $lucro, 2, ',', '.');
            $lucro = number_format($lucro, 2, ',', '.');
        } else {
            sleep(2);
            $saldo = $usuario->plataforma->service->saldo($usuario);
            $banca = number_format($saldo, 2, ',', '.');
            $lucro = number_format($lucro, 2, ',', '.');
        }

        $cor_result = self::getCor($cor, $usuario->plataforma->translate);

        $botao = [];
        if ($usuario->plataforma->url_sala_sinais)
            $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_SALA_SINAIS,  "url" => $usuario->plataforma->url_sala_sinais]];
        if ($usuario->plataforma->url_comunidade)
            $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_COMUNIDADE,  "url" => $usuario->plataforma->url_comunidade]];
        
        $telegram->sendMessage(
            $usuario->chat_id, 
            str_replace(
                ['{cor}', '{lucro}', '{banca}'],
                [$cor_result, $lucro, $banca],
                $usuario->plataforma->translate->MSG_BET_10
            ),
            [
                "resize_keyboard" => true, 
                "inline_keyboard" => $botao
            ]
        );
    }

    public static function gerarUsuarioMetasStatus($usuario, $telegram)
    {
        if ($usuario->metas == 'Y' and $usuario->usuario_meta)
        {
            $banca_inicio = $usuario->usuario_meta->ultimo_saldo;
            $valor_real_objetivo = $usuario->usuario_meta->valor_real_objetivo;

            $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                    ->where('created_at', '>=', $usuario->usuario_meta->inicio_execucao)
                    ->sumBy('valor', 'total');
            });

            // if ($usuario->plataforma->ambiente == 'HOMOLOGACAO') {
            if ($usuario->modo_treinamento == 'Y') {
                $lucro_total = TUtils::openFakeConnection('double', function() use($usuario) {
                    return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                        ->where('created_at', '>=', $usuario->robo_inicio)
                        ->sumBy('valor', 'total');
                });
    
                $banca = $usuario->ultimo_saldo + $lucro_total;
             } else {
                 sleep(2);
                 $banca = $usuario->plataforma->service->saldo($usuario);
             }
  
            $percentual = ($lucro / $valor_real_objetivo) * 100;

            $telegram->sendMessage(
                $usuario->chat_id, 
                str_replace(
                    ['{banca_inicio}', '{banca}', '{lucro}', '{objetivo}', '{percentual}'],
                    [
                        number_format($banca_inicio, 2, ',', '.'), 
                        number_format($banca, 2, ',', '.'), 
                        number_format($lucro, 2, ',', '.'), 
                        number_format($valor_real_objetivo, 2, ',', '.'), 
                        number_format($percentual, 2, ',', '.')
                    ],
                    $usuario->plataforma->translate->MSG_META12
                )
            );

            if ($percentual >= 100)
            {
                if ($usuario->usuario_meta->tipo_periodicidade == 'HORAS')
                    $tipo = '+' . $usuario->usuario_meta->valor_periodicidade . ' hour';
                else
                    $tipo = '+' . $usuario->usuario_meta->valor_periodicidade . ' minute';

                $data_entrada = new DateTime();
                $data_entrada->modify($tipo);

                $usuario->usuario_meta->proxima_execucao = $data_entrada->format('Y-m-d H:i:s');
                $usuario->usuario_meta->saveInTransaction();

                $telegram->sendMessage(
                    $usuario->chat_id, 
                    str_replace(
                        ['{data_entrada}'],
                        [$data_entrada->format('d/m/Y H:i:s')],
                        $usuario->plataforma->translate->MSG_META13
                    )
                );
            }

            return $percentual >= 100;            
        }
    }

    public static function gerarStatus($telegram, $data)
    {
        $dados = TUtils::openFakeConnection('double', function() use ($data){
            $conn = TTransaction::get(); 
            return $conn->query("SELECT if(cor = 'white' AND tipo = 'WIN', 'BRANCO', tipo) tipo, 
                                        count(1) total 
                                   FROM double_historico 
                                  WHERE plataforma_id = {$data->plataforma->id}
                                    AND canal_id = {$data->canal->id}
                                    AND tipo IN ('WIN','LOSS')
                                    AND DATE(created_at) = CURDATE() 
                                    AND usuario_id IS NULL
                                  GROUP BY 1");
        });

        $win = 0;
        $loss = 0;
        $branco = 0;
        $acertos = 0;
        while ($row = $dados-> fetchObject())
        {
            if ($row->tipo == 'WIN')
                $win = $row->total;
            elseif ($row->tipo == 'BRANCO')
                $branco = $row->total;
            else
                $loss = $row->total;
        }

        $percentual = 0;
        $total = $win + $loss + $branco;
        if ($total > 0)
            $percentual = round((($win + $branco) / $total) * 100, 1);
        $telegram->sendMessage(
            $data->canal->channel_id,
            str_replace(
                ['{win}', '{loss}', '{percentual}'],
                [$win + $branco, $loss, $percentual],
                $data->plataforma->translate->MSG_SINAIS_PARCIAL_DIA,
            ),
        );

        $acertos = $win - $loss;
        $valor = ($acertos * 20);
        if ($valor < 0)
            $valor = $valor * ($data->canal->protecoes + 1);
        $valor = $valor + (14 * 20 * $branco);
        if ($data->canal->exibir_projecao == 'Y' and $valor > 0)
        {
            $valor = number_format($valor, 2, ',', '.');
            
            $telegram->sendMessage(
                $data->canal->channel_id,
                str_replace(
                    ['{valor}'],
                    [$valor],
                    $data->plataforma->translate->MSG_SINAIS_PROJECAO,
                ),
            );
        }
    }

    public static function getCor($cor, TDoubletranslate $translate, $completo = true) {
        switch ($cor) {
            case 'white':
                $cor_result = $completo ? $translate->COLOR_WHITE : $translate->WHITE;
                break;
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
            return 'Inteligência artificial' ;
        
        return TUtils::openFakeConnection('double', function() use ($historico){
            $obj = new DoubleEstrategia($historico['estrategia'], false);
            if ($obj)
                return $obj->nome;
            else
                return 'Inteligência artificial';
        });  
    }

    public function verificar($param)
    {
        TSession::setValue('unit_database', 'double');
        $login = TSession::setValue('login', 'api');

        $manutencao =  DoubleConfiguracao::getConfiguracao('manutencao');
        if ($manutencao =='Y') {
            print_r('Em manutenção.');
            return;
        }

        $diff = TUtils::openConnection('double', function(){
            $sinal = DoubleSinal::last();
            $date = date_create_from_format('Y-m-d H:i:s', $sinal->created_at);
            $now = new DateTime();
            $diff = $date->diff($now);   
            return $diff;      
        });
        
        $tempo = $diff->i * 60 + $diff->s;
        print_r("Ultimo sinal aconteceu ha {$tempo} segundos.\n");
        if ($tempo >= 40)
        {
            print_r('Parando o servico... \n');
            self::finalizar($param);
            sleep(10);
            print_r('Iniciando o servico... \n');
            self::iniciar($param);
        }
    }

    public function verificar_canal($param)
    {
        TSession::setValue('unit_database', 'double');
        $login = TSession::setValue('login', 'api');

        $manutencao =  DoubleConfiguracao::getConfiguracao('manutencao');
        if ($manutencao =='Y') {
            print_r('Em manutenção.');
            return;
        }

        $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
        if (!$canal) {
            print_r('Canal não identificado.');
            return;
        }

        $token = TUtils::openFakeConnection('permission', function () {
            $login = TSession::getValue('login');
            $user = SystemUser::validate($login);
            return ApplicationAuthenticationRestService::getToken($user);
        });

        $data = new stdClass;
        $data->inicio = true;
        $data->token = $token;
        $data->tipo = 'cmd';
        $data->canal_id = $canal->id;
        $data->plataforma_id = $canal->plataforma->id;

        $date = date_create_from_format('Y-m-d H:i:s', $canal->created_at);
        $now = new DateTime();
        $diff = $date->diff($now);   
        
        $tempo = $diff->i * 60 + $diff->s;
        print_r("Ultima mensagem enviada aconteceu ha {$tempo} segundos.\n");
        if ($tempo >= 40)
        {
            print_r('Parando o canal... \n');
            self::finalizar_canal(['canal_id' => $canal->id]);
            sleep(10);
            print_r('Iniciando o canal... \n');
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal', $data);
        }
    }

    public static function sinal_corrente($param)
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        if ($plataforma->ativo = 'Y' and $plataforma->tipo_sinais == 'PROPAGA_VALIDA_SINAL') {
            $sinal = TUtils::openFakeConnection('double', function () use ($plataforma) {
                $obj = DoubleSinal::select('TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_since_last_signal')
                    ->where('plataforma_id', '=', $plataforma->id)
                    ->last();

                return $obj->seconds_since_last_signal;
            });

            if ($sinal and $sinal > 60) {
                $plataforma->status_sinais = 'PARADO';
                $plataforma->saveInTransaction();

                sleep(10);

                $token = TUtils::openFakeConnection('permission', function () {
                    $login = TSession::getValue('login');
                    $user = SystemUser::validate($login);
                    return ApplicationAuthenticationRestService::getToken($user);
                });

                $data = new stdClass;
                $data->inicio = true;
                $data->token = $token;
                $data->plataforma_id = $plataforma->id;
                $data->tipo = 'cmd';
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar', $data);
            }
        }

        $service = $plataforma->service;
        $sinal = $service->sinalCorrente();

        if ($sinal['status_code'] == 200)
            return $sinal['data'];
        else
            http_response_code($sinal['status_code'] );
    }

    public function validar_double_sinais($params) {
        $parar = DoubleConfiguracao::getConfiguracao('parar_validar_double_sinais');
        if ($parar == 'Y')
            return;

        $executando = DoubleConfiguracao::getConfiguracao('executando_validar_double_sinais');
        if ($params['data']['inicio'] == 'Y' and $executando == 'Y')
            return;

        try
        {
            DoubleConfiguracao::setConfiguracao('executando_validar_double_sinais', 'Y');
            DoubleErros::registrar(1, 'TDoubleCron', 'validar_double_sinais', 'entrou');
            TSession::setValue('unit_database', 'double');
            TSession::setValue('login', 'api');
            
            $token = TUtils::openFakeConnection('permission', function () {
                $login = TSession::getValue('login');
                $user = SystemUser::validate($login);
                return ApplicationAuthenticationRestService::getToken($user);
            });

            $plataformas = TUtils::openFakeConnection('double', function() {
                return DoublePlataforma::where('ativo', '=', 'Y')
                    ->where('tipo_sinais', '<>', 'PROPAGA_OUTRO')
                    ->load();
            });

            // executa busca de sinais
            foreach ($plataformas as $key => $plataforma) {
                $data = new stdClass;
                $data->inicio = $plataforma->status_sinais == 'EXECUTANDO' ?  false : true;
                $data->token = $token;
                $data->plataforma_id = $plataforma->id;
                $data->tipo = 'cmd';
                TDoubleUtils::cmd_run('TDoubleSinais', 'executar', $data);
                DoubleErros::registrar(1, 'TDoubleCron', 'validar_double_sinais', " .. Plataforma {$plataforma->nome}");
            }

            sleep(30);
            
            $data = new stdClass;
            $data->inicio = 'N';
            TDoubleUtils::cmd_run('TDoubleSinais', 'validar_double_sinais', $data);
        } catch (\Throwable $th) {
            DoubleConfiguracao::setConfiguracao('executando_validar_double_sinais', 'N');
            DoubleErros::registrar(1, 'TDoubleCron', 'validar_double_sinais', $th->getMessage());
        } finally {
            DoubleErros::registrar(1, 'TDoubleCron', 'validar_double_sinais', 'saiu');
        }
    }
}
