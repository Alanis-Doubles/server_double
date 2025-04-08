<?php

use Predis\Client;

class TPlaybrokerUsuarioConsumer extends TDoubleUsuarioConsumer
{
    const redis_canal = 'profit_historico';
    private $pubsub;

    public function realizarEntrada(&$usuario, $object, $botao, $botao_inicio)
    {
        $canal  = 'profit_usuario_' . $usuario->id;
        try {
            //$this->pubsub->unsubscribe(self::redis_canal);
            $service = $usuario->plataforma->service;
            if ($service) {
                $valor = $usuario->valorJogada($object['estrategia_id']);
                $informacao = explode('|', $object['informacao']);
                $data = new DateTime();

                $dataFormatada = $data->format('Y-m-d') . ' ' . $informacao[1];

                $params = [
                    "balanceType" => $usuario->modo_treinamento == 'Y' ? "demo" : "real",
                    "amount" => $valor,
                    "ticker" => $object['ticker'],
                    "orderIn" => $dataFormatada,
                    "expiration" => $usuario->expiration,
                    "direction" => $object['cor'] == 'black' ? "call" : "put",
                ];

                $executou = $service->jogarAPI($usuario, $params);
                if (!$executou) {
                    echo "Erro ao realizar entrada\n";
                    return;
                }

                $this->pubsub->subscribe($canal);
                $this->pubsub->unsubscribe(self::redis_canal);
                echo "Conectando no canal {$canal}\n";
                
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
                    if ($message->kind == 'message') {
                            $usuario = DoubleUsuario::identificarPorId($usuario->id);
                            echo "received message: {$message->channel} - {$message->payload}\n";
                            $payload = json_decode($message->payload); 
                            if ($payload->tipo === 'WIN') {
                                $this->notificar_usuario_historico_consumidores([
                                    'sequencia' => $usuario->robo_sequencia,
                                    'usuario_id' => $usuario->id,
                                    'valor' => $payload->valor,
                                    'entrada_id' => $object['id'],
                                    'valor_entrada' => $payload->valor_entrada,
                                    'valor_branco' => 0,
                                    'gale' => $payload->gale,
                                    'tipo' => 'WIN',
                                    'cor'  => $payload->cor,
                                    'robo_inicio' => $usuario->robo_inicio,
                                    'configuracao' => $usuario->configuracao_texto,
                                    'lucro' => $usuario->lucro + $payload->lucro,
                                    'banca' => $payload->banca,
                                    'fator' => $payload->fator,
                                    'ticker' => $payload->ticker,
                                    'ticker_description' => $payload->ticker_description,
                                    'ticker_classifier' => $payload->ticker_classifier
                                ]);
                
                                $usuario->quantidade_loss = 0;
                                $usuario->ultimo_saldo = $payload->banca;
                                $usuario->saveInTransaction();

                                $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                                    return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                                        ->where('sequencia', '=', $usuario->robo_sequencia)
                                        ->sumBy('valor', 'total');
                                }) ?? 0;

                                echo "Perda/Lucro Atual: {$payload->valor_entrada}\n";
                                echo "Perda/Lucro Acumulado: {$lucro}\n";

                                $this->validarStopWinLoss(
                                    $usuario,
                                    $lucro,
                                    $botao,
                                    $botao_inicio
                                ); 
                                break;
                            } elseif ($payload->tipo === 'LOSS') {
                                // $usuario->quantidade_loss += 1;
                                // $usuario->saveInTransaction();

                                $this->notificar_usuario_historico_consumidores([
                                    'sequencia' => $usuario->robo_sequencia,
                                    'usuario_id' => $usuario->id,
                                    'valor' => -$payload->valor_entrada,
                                    'entrada_id' => $object['id'],
                                    'valor_entrada' => $payload->valor_entrada,
                                    'valor_branco' => 0,
                                    'gale' => $payload->gale,
                                    'tipo' => 'LOSS',
                                    'cor'  => $payload->cor,
                                    'robo_inicio' => $usuario->robo_inicio,
                                    'configuracao' => $usuario->configuracao_texto,
                                    'lucro' => $usuario->lucro + $payload->lucro,
                                    'banca' => $payload->banca,
                                    'fator' => $payload->fator,
                                    'ticker' => $payload->ticker,
                                    'ticker_description' => $payload->ticker_description,
                                    'ticker_classifier' => $payload->ticker_classifier
                                ]);

                                $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                                    return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                                        ->where('sequencia', '=', $usuario->robo_sequencia)
                                        ->sumBy('valor', 'total');
                                }) ?? 0;

                                echo "Perda/Lucro Atual: {-$payload->valor_entrada}\n";
                                echo "Perda/Lucro Acumulado: {$lucro}\n";

                                $this->validarStopWinLoss(
                                    $usuario,
                                    $lucro,
                                    $botao,
                                    $botao_inicio
                                ); 

                                break;
                            } elseif ($payload->tipo === 'GALE') {
                                // $usuario->quantidade_loss += 1;
                                // $usuario->saveInTransaction();

                                $this->notificar_usuario_historico_consumidores([
                                    'sequencia' => $usuario->robo_sequencia,
                                    'usuario_id' => $usuario->id,
                                    'valor' => -$payload->valor_entrada,
                                    'entrada_id' => $object['id'],
                                    'valor_entrada' => $payload->valor_entrada,
                                    'valor_branco' => 0,
                                    'gale' => $payload->gale - 1,
                                    'tipo' => 'GALE',
                                    'cor'  => $payload->cor,
                                    'robo_inicio' => $usuario->robo_inicio,
                                    'configuracao' => $usuario->configuracao_texto,
                                    'lucro' => $usuario->lucrso + $payload->lucro,
                                    'banca' => $payload->banca,
                                    'fator' => $payload->fator,
                                    'ticker' => $payload->ticker,
                                    'ticker_description' => $payload->ticker_description,
                                    'ticker_classifier' => $payload->ticker_classifier
                                ]);
                            } elseif ($payload->tipo === 'saldo_insuficiente') {
                                $usuario->robo_iniciar = 'N';
                                $usuario->robo_status = 'PARANDO';
                                $usuario->saveInTransaction();

                                TRedisUtils::sendMessage(
                                    $usuario->chat_id, 
                                    $usuario->canal->telegram_token, 
                                    $usuario->plataforma->translate->MSG_PARAR_ROBO, 
                                    $botao_inicio
                                );
                                break;
                            } elseif ($payload->tipo === 'STOP') {
                                if ($usuario->robo_status == "EXECUTANDO") {
                                    $usuario->robo_iniciar = 'N';
                                    $usuario->robo_status = 'PARADO';
                                    $usuario->saveInTransaction();

                                    TRedisUtils::sendMessage(
                                        $usuario->chat_id, 
                                        $usuario->canal->telegram_token, 
                                        $usuario->plataforma->translate->MSG_PARAR_ROBO, 
                                        $botao_inicio
                                    );
                                }
                                
                                break;
                            } elseif ($payload->tipo === 'ENTRADA') {
                                echo "Entrou aqui\n";
                                $usuario->quantidade_loss = 0;
                                $usuario->saveInTransaction();
                            } elseif ($payload->tipo === 'ORDEM_REALIZADA') {}
                            elseif ($payload->tipo === 'demo_fnalizao') {
                                $usuario->robo_iniciar = 'N';
                                $usuario->robo_status = 'PARADO';
                                $usuario->saveInTransaction();
                                echo "Usuário {$usuario->id} sem jogadas\n";

                                $usuario->plataforma->service->finalizar($usuario);
                                break;
                            }
                            else { // outros
                                echo "Saiu aqui\n";
                                break;
                            }
                    }
                    echo "aguardando próximo sinal do usuário\n";
                }
            
            }
        } catch (\Throwable $th) {
            $trace = ''; // json_encode($th->getTrace());
            echo $th->getMessage();
        } finally {
            $this->pubsub->subscribe(self::redis_canal);
            $this->pubsub->unsubscribe($canal);
        }
    }

    public function run($param) 
    {
        echo json_encode($param) . "\n";
        $usuario = DoubleUsuario::identificarPorId($param['usuario_id']);
        // $redis_canal = 'profit_historico';

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => $this->hostUsuario(), // IP do seu Redis
            'port'   => 6379, // Porta padrão do Redis
            'read_write_timeout' => -1
        ]);
        $this->pubsub = $redis->pubSubLoop();
        $this->pubsub->subscribe(self::redis_canal);
        // $this->pubsub->subscribe($this->usuario_historico);
        
        while (true) {
            try {
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
                    if ($message->kind === 'message' ) 
                    {
                        // if ($usuario->status == 'DEMO') {
                        //     if ($usuario->demo_jogadas <=0) {
                        //         $usuario->robo_iniciar = 'N';
                        //         $usuario->robo_status = 'PARADO';
                        //         $usuario->saveInTransaction();
                        //         echo "Usuário {$usuario->id} sem jogadas\n";
                        //     }
                        // } else if ($usuario->status != 'ATIVO') {
                        //     $usuario->robo_iniciar = 'S';
                        //     $usuario->robo_status = 'EXECUTANDO';
                        //     $usuario->saveInTransaction();
                        //     echo "Usuário {$usuario->id} sem status\n";
                        // }

                        $usuario = DoubleUsuario::identificarPorId($usuario->id);
                        if ($usuario->roboStatus == 'EXECUTANDO') 
                        {
                            // $usuario = DoubleUsuario::identificarPorId($usuario->id);
        
                            // // Verifica se o usuário possui estratégias próprias e se o histórico é do canal
                            // // >> se SIM ignora a mensagem
                            // if ($usuario->possui_estrategias and $message->channel == $this->channel_historico)
                            //     continue;
        
                            // // Verifica se o usuário não possui estratégias próprias e se o histórico é do usuário
                            // // >> se SIM ignora a mensagem
                            // if (!$usuario->possui_estrategias and $message->channel == $this->usuario_historico)
                            //     continue;
        
                            echo "received message: {$message->channel} - {$message->payload}\n";

                            $object = json_decode($message->payload, true);
                            $classificacao = [
                                'Todos'  => 'Todos',
                                'crypto' => 'Criptomoeda',
                                'forex'  => 'Forex',
                                'otc'    => 'OTC',
                            ];
                            if ($usuario->classificacao != 'Todos' and $usuario->classificacao != $object['ticker_classifier'])
                            {
                                echo "Esperado '{$usuario->classificacao}' mas recebido '{$object['ticker_classifier']}'\n";
                                continue;
                            }
                            
                            $this->processar_sinais($usuario, $message);
                            if ($usuario->roboStatus !== 'EXECUTANDO') 
                            {
                                $usuario->plataforma->service->finalizar($usuario);
                                $this->pubsub->unsubscribe(self::redis_canal);
                                // $this->pubsub->unsubscribe($this->channel_historico);
                                // $this->pubsub->unsubscribe($this->usuario_historico);
                                exit;
                            }
                        } else 
                        {
                            $usuario->plataforma->service->finalizar($usuario);
                            $this->pubsub->unsubscribe(self::redis_canal);
                            // $this->pubsub->unsubscribe($this->channel_historico);
                            // $this->pubsub->unsubscribe($this->usuario_historico);
                            exit;
                        }
                    }
                    echo "aguardando próximo sinal da sala de sinal\n";
                } 
            } catch (\Throwable $th) {
                $trace = ''; // json_encode($th->getTrace());
                echo $th->getMessage();

                $redis = new Client([
                    'scheme' => 'tcp',
                    'host'   => $this->hostUsuario(), // IP do seu Redis
                    'port'   => 6379, // Porta padrão do Redis
                    'read_write_timeout' => -1
                ]);
                $this->pubsub = $redis->pubSubLoop();
                $this->pubsub->subscribe(self::redis_canal);
                // $this->pubsub->subscribe($this->channel_historico);
                // $this->pubsub->subscribe($this->usuario_historico);
            } 
        }
    }

    public function validar_stop_win_loss($param) 
    {
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        // $canal = DoubleCanal::identificar($param['channel_id']);
        
        // if (!$canal)
        //     throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        if (!$plataforma) 
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $usuario = DoubleUsuario::identificarPorId($param['usuario_id']);

        if ($usuario->roboStatus !== 'EXECUTANDO') 
            throw new Exception($plataforma->translate->MSG_OPERACAO_NAO_SUPORTADA);

        $botao = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $usuario->plataforma->translate->BOTAO_CONFIGURAR]],
                    [["text" => $usuario->plataforma->translate->BOTAO_PARAR_ROBO]], 
                ] 
            ];

        $iniciar_apos = $usuario->plataforma->translate->BOTAO_INICIAR_LOSS;
        if ($usuario->entrada_automatica_tipo == 'WIN')
            $iniciar_apos = $usuario->plataforma->translate->BOTAO_INICIAR_WIN;
    
        $modo_treinamento = $usuario->plataforma->translate->BOTAO_MODO_TREINAMENTO_ATIVO;
        $modo_real = $usuario->plataforma->translate->BOTAO_MODO_REAL_INATIVO;
        if ($usuario->modo_treinamento == 'N') {
            $modo_treinamento = $usuario->plataforma->translate->BOTAO_MODO_TREINAMENTO_INATIVO;
            $modo_real = $usuario->plataforma->translate->BOTAO_MODO_REAL_ATIVO;
        }
    
        $botao_inicio = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $usuario->plataforma->translate->BOTAO_CONFIGURAR]],
                    [["text" => $modo_treinamento], ["text" => $modo_real]], 
                    [["text" => $usuario->plataforma->translate->BOTAO_INICIAR], ["text" => $iniciar_apos]], 
                    // [["text" => $usuario->plataforma->translate->BOTAO_GERAR_ACESSO_APP]]
                ] 
            ];
    
        $lucro = 0;
        if (isset($param['lucro'])) {
            $lucro = $param['lucro'];
        } 
        echo "Perda/Lucro Atual: {$lucro}\n";
        
        $lucro += TUtils::openFakeConnection('double', function() use($usuario) {
            return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                ->where('sequencia', '=', $usuario->robo_sequencia)
                ->sumBy('valor', 'total');
        }) ?? 0;

        echo "Perda/Lucro Acumulado: {$lucro}\n";

        return $this->validarStopWinLoss($usuario, $lucro, $botao, $botao_inicio);
    }
}