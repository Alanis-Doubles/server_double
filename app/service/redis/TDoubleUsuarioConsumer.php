<?php

use Cake\Database\Expression\WhenThenExpression;
use Predis\Client;

class TDoubleUsuarioConsumer extends TDoubleRedis
{
    private $fazer_entrada;
    private $pubsub;
    private $channel_historico;
    private $usuario_historico;
    private $channel_entrada;

    private function notificar_usuario_historico_consumidores($historico)
    {
        $channel_name = strtolower("{$this->serverName()}_usuario_historico");
        $channel_notify = strtolower("{$this->serverName()}_usuario_historico_notify");

        $redis = new Client();
        $redis->lpush($channel_name, json_encode($historico));
        $redis->publish($channel_notify, json_encode($historico));

        $payload = json_encode($historico);
        // echo "$channel_notify - $payload\n";
    }

    private function aguardar_entrada()
    {
        $redis = new Client();
        while (!$redis->get($this->fazer_entrada)) {
            sleep(2);
        }
    }

    private function incrementar_entrada_automatica(&$usuario, $botao) 
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
            TRedisUtils::sendMessage(
                $usuario->chat_id,
                $usuario->canal->telegram_token,
                $usuario->plataforma->translate->MSG_OPERACAO_IDENTIFICADO_LOSS,
                $botao
            );
        }
    }

    private function zerar_entrada_automatica(&$usuario)
    {
        $usuario->entrada_automatica_qtd_loss = 0;
        $usuario->saveInTransaction();
    }

    private function processar_sinais($usuario, $message) 
    {
        $object = json_decode($message->payload, true); 
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
                    [["text" => $usuario->plataforma->translate->BOTAO_GERAR_ACESSO_APP]]
                ] 
            ];

        if ($object['tipo'] == 'LOSS' and $usuario->robo_iniciar_apos_loss == 'Y' and $usuario->entrada_automatica_total_loss > 0) {
            if ($usuario->entrada_automatica_tipo == 'LOSS') {
                $this->incrementar_entrada_automatica($usuario, $botao);
            } else {
                $this->zerar_entrada_automatica($usuario);
            }
        } elseif ($object['tipo'] == 'WIN' and $usuario->robo_iniciar_apos_loss == 'Y' and $usuario->entrada_automatica_total_loss > 0) {
            if ($usuario->entrada_automatica_tipo == 'LOSS') {
                $this->zerar_entrada_automatica($usuario);
            } else {
                $this->incrementar_entrada_automatica($usuario, $botao);
            }
        } else {
            if ($usuario->robo_iniciar_apos_loss == 'Y' and $usuario->entrada_automatica_total_loss == 0) {
                $usuario->entrada_automatica_qtd_loss = 0;
                $usuario->robo_iniciar_apos_loss = 'N';
                $usuario->robo_processando_jogada = 'N';
                $usuario->saveInTransaction();
            }

            if ($object['tipo'] == 'ENTRADA' and $usuario->robo_iniciar_apos_loss == 'N') {
                // verifica o status DEMO
                if ($usuario->status != 'ATIVO' and $usuario->status != 'DEMO') {
                    $usuario->robo_iniciar = 'N';
                    $usuario->robo_iniciar_apos_loss = 'N';
                    $usuario->robo_processando_jogada = 'N';
                    $usuario->robo_status = 'PARANDO';
                    $usuario->saveInTransaction();

                    return;
                }

                TRedisUtils::sendMessage(
                    $usuario->chat_id,
                    $usuario->canal->telegram_token,
                    str_replace(
                        ['{cor}', '{estrategia}', '{informacao}'],
                        [
                            TRedisUtils::getCor($object['cor'], $usuario->plataforma->translate), 
                            TRedisUtils::buscarNomeEstrategia($object['estrategia_id']), 
                            $object['informacao']
                        ],
                        $usuario->plataforma->translate->MSG_CONFIRMADO_AGUARDANDO
                    ),
                    $botao
                );

                $valor = $usuario->valorJogada($object['estrategia_id']);

                $historico = $object;
                $protecao = 0;
                $valor = 0;
                $valor_branco = 0;
                $lucro = 0;

                $canal = $usuario->canal;

                $channel_sinais = strtolower("{$this->serverName()}_{$canal->plataforma->nome}_{$canal->plataforma->idioma}_sinais");
                $this->pubsub->subscribe($channel_sinais);
                $this->pubsub->unsubscribe($this->channel_historico);
                $this->pubsub->unsubscribe($this->usuario_historico);
                $first = true;

                $callback_jogar = function () use (&$valor, &$valor_branco, &$usuario, &$historico, &$protecao, $botao, $botao_inicio, 
                                                   &$lucro, $channel_sinais) {
                    $service = $usuario->plataforma->service;

                    $max_valor = $usuario->valor_max_ciclo;
                    $valor_usuario = $usuario->valor;
                    if ($max_valor > 0 and $valor > $max_valor)
                        $valor = $valor_usuario;

                    $redis_entrada = new Client();
                    $pubsub_entrada = $redis_entrada->pubSubLoop();
                    $pubsub_entrada->subscribe($this->channel_entrada);
                    foreach ($pubsub_entrada as $message) {
                        $message = (object) $message;
                        echo "received message: {$message->channel} - {$message->payload}\n";
                        if ($message->kind === 'message' ) {
                            $pubsub_entrada->subscribe($this->channel_entrada);
                            break;
                        }
                    }
                    // $this->aguardar_entrada();

                    if ($usuario->ultimo_saldo + $lucro < $valor) {
                        $retornoJogada = 'saldo_insuficiente';
                    } else {
                        sleep(1);
                        echo "fazer jogada\n";
                        $retornoJogada = $service->jogar($usuario, $historico['cor'], $valor);
                    }

                    $retornoJogadaBranco = '';
                    if ($retornoJogada == '' and $usuario->protecao_branco == 'Y') {
                        $valor_branco = round($valor * 0.2, 2);
                        $retornoJogadaBranco = $service->jogar($usuario, 'white', $valor_branco);
                        if ($retornoJogadaBranco <> '')
                            $valor_branco = 0;
                    }

                    if ($retornoJogada == '') {
                        if ($protecao == 0) {
                            $message = str_replace(
                                ['{cor}', '{valor}', '{branco}', '{estrategia}'],
                                [
                                    TRedisUtils::getCor($historico['cor'], $usuario->plataforma->translate), 
                                    number_format($valor, 2, ',', '.'),
                                    $valor_branco == 0 ? "" : "🎯 Cor: " . TRedisUtils::getCor('white', $usuario->plataforma->translate) . " - Valor: R$ " . number_format($valor_branco, 2, ',', '.'). ". ",
                                    TRedisUtils::buscarNomeEstrategia($historico['estrategia_id'])
                                ],
                                $usuario->plataforma->translate->MSG_OPERACAO_ENTRADA_REALIZADA
                            );  

                            if ($valor > $valor_usuario)
                                $message .= $usuario->plataforma->translate->MSG_OPERACAO_ENTRADA_CICLO;
                            
                            if ($usuario->status == 'DEMO') {
                                $usuario = DoubleUsuario::identificarPorId($usuario->id);
                                $usuario->demo_jogadas -= 1;
                                $usuario->saveInTransaction();
                                $message .= str_replace(
                                    ['{demo_jogadas}'],
                                    [$usuario->demo_jogadas],
                                    $usuario->plataforma->translate->MSG_INICIO_ROBO_7
                                );
                            }
                        }
                        else
                            $message = str_replace(
                                ['{protecao}', '{valor}', '{cor}', "{branco}", "{estrategia}"],
                                [
                                    $protecao, 
                                    number_format($valor, 2, ',', '.'),
                                    TRedisUtils::getCor($historico['cor'], $usuario->plataforma->translate), 
                                    $valor_branco == 0 ? "" : "🎯 Cor: " . TRedisUtils::getCor('white', $usuario->plataforma->translate) . " - Valor: R$ " . number_format($valor_branco, 2, ',', '.'). ". ",
                                    TRedisUtils::buscarNomeEstrategia($historico['estrategia_id'])
                                ],
                                $usuario->plataforma->translate->MSG_OPERACAO_MARTINGALE,
                            );

                        echo "Jogou Gale {$protecao} - valor: $valor - cor: {$historico['cor']}\n";

                        TRedisUtils::sendMessage(
                            $usuario->chat_id, 
                            $usuario->canal->telegram_token, 
                            $message, 
                            $botao
                        );
                    } else{
                        $this->pubsub->subscribe($this->channel_historico);
                        $this->pubsub->subscribe($this->usuario_historico);
                        $this->pubsub->unsubscribe($channel_sinais);
                        if ($retornoJogada == 'saldo_insuficiente') {
                            $usuario = DoubleUsuario::identificar($usuario->chat_id, $usuario->plataforma->id, $usuario->canal_id);
                            $usuario->robo_iniciar = 'N';
                            $usuario->robo_status = 'PARANDO';
                            $usuario->saveInTransaction();

                            if ($usuario->status_objetivo == 'EXECUTANDO')
                                $usuario->usuario_objetivo->parar();

                            TRedisUtils::sendMessage(
                                $usuario->chat_id, 
                                $usuario->canal->telegram_token, 
                                $usuario->plataforma->translate->MSG_BET_7,
                                $botao_inicio
                            );
                        } else {
                            TUtils::openConnection('double', function() use ($retornoJogada, $usuario) {
                                $error = new DoubleErros();
                                $error->classe = 'TDoubleSinais';
                                $error->metodo = 'executar_usuario';
                                $error->erro = $retornoJogada;
                                $error->detalhe = "Usuário: " . $usuario->nome_usuario . '[' . $usuario->chat_id . ']';
                                $error->plataforma_id = $usuario->plataforma->id;
                                $error->save();
                            });

                            
                            TRedisUtils::sendMessage(
                                $usuario->chat_id, 
                                $usuario->canal->telegram_token, 
                                'Entrada abortada, aguardando próximo sinal...\n\nMensagem retornada pela plataforma:\n' . $retornoJogada, 
                                $botao
                            );
                        }
                    }

                    return $retornoJogada;
                };

                foreach ($this->pubsub as $message_sinais)
                {
                    if ($usuario->roboStatus !== 'EXECUTANDO') 
                    {
                        $this->pubsub->unsubscribe($channel_sinais);
                        break;
                    }

                    // if ($message_sinais->channel == $this->channel_historico)
                    if (in_array($message_sinais->channel, [$this->channel_historico, $this->usuario_historico]))
                        continue;

                    $message_sinais = (object) $message_sinais;
                    if ($message_sinais->kind !== 'message' and $first) 
                    {
                        $first = false;
                        $valor = $usuario->valorJogada($object['estrategia_id']);
                        $retorno = $callback_jogar();
                        if ($retorno !== '') {
                            $this->pubsub->unsubscribe($channel_sinais);
                            break;
                        }
                        continue;
                    }
                    echo "{$message_sinais->channel} - {$message_sinais->payload}\n";
                    $object = json_decode($message_sinais->payload, true); 
                    $cor_retornada = $object['cor'];

                    $win = $historico['cor'] == $cor_retornada;

                    echo "Cor esperada: {$historico['cor']} - Cor retornada: $cor_retornada\n";

                    if (!$win and $usuario->protecao_branco == 'Y')
                        $win = $cor_retornada == 'white';

                    $banca = 0;

                    $callback_lucro = function ($valor) use ($usuario, &$banca) {
                        $lucro = $usuario->lucro;
                        if ($usuario->modo_treinamento == 'Y') {
                            $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                                return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                                    ->where('created_at', '>=', $usuario->robo_inicio)
                                    ->sumBy('valor', 'total');
                            }) ?? 0;
                
                            $lucro += $valor;
                            // DoubleErros::registrar(1, 'usuario', 'lucro', $lucro);
                            $banca = number_format($usuario->ultimo_saldo + $lucro, 2, ',', '.');
                            $lucro = number_format($lucro, 2, ',', '.');
                        } else {
                            sleep(10);
                            $lucro += $valor;
                            $saldo = $usuario->plataforma->service->saldo($usuario);
                            $banca = number_format($saldo, 2, ',', '.');
                            $lucro = number_format($lucro, 2, ',', '.');
                        }

                        return $lucro;
                    };

                    if ($win) {
                        TRedisUtils::sendMessage(
                            $usuario->chat_id, 
                            $usuario->canal->telegram_token, 
                            $cor_retornada == 'white' ? $usuario->plataforma->translate->MSG_SINAIS_WIN_BRANCO : $usuario->plataforma->translate->MSG_BET_3,
                            $botao
                        );
                        if ($cor_retornada == 'white') {
                            if ($usuario->protecao_branco == 'Y') 
                                $valor_calc = ($valor_branco * 14) - $valor;
                            else
                                $valor_calc = ($valor * 14);
                        }
                        else
                            $valor_calc = $valor - $valor_branco;
                        
                        $this->notificar_usuario_historico_consumidores([
                            'sequencia' => $usuario->robo_sequencia,
                            'usuario_id' => $usuario->id,
                            'valor' => $valor_calc,
                            'entrada_id' => $historico['id'],
                            'valor_entrada' => $valor,
                            'valor_branco' => $valor_branco,
                            'gale' => $protecao,
                            'tipo' => 'WIN',
                            'cor'  => $cor_retornada,
                            'robo_inicio' => $usuario->robo_inicio,
                            'configuracao' => $usuario->configuracao_texto,
                            'lucro' => $callback_lucro($valor_calc),
                            'banca' => $banca
                        ]);

                        $usuario->quantidade_loss = 0;
                        $usuario->saveInTransaction();

                        $this->pubsub->subscribe($this->channel_historico);
                        $this->pubsub->subscribe($this->usuario_historico);
                        $this->pubsub->unsubscribe($channel_sinais);
                    } else {
                        $usuario->quantidade_loss += 1;
                        $usuario->saveInTransaction();

                        $valor_calc = -1 * ($valor + $valor_branco);

                        $this->notificar_usuario_historico_consumidores([
                            'sequencia' => $usuario->robo_sequencia,
                            'usuario_id' => $usuario->id,
                            'valor' => $valor_calc,
                            'entrada_id' => $historico['id'],
                            'valor_entrada' => $valor,
                            'valor_branco' => $valor_branco,
                            'gale' => $protecao,
                            'tipo' => ($protecao == $usuario->protecao ? 'LOSS' : 'GALE'),
                            'cor'  => $cor_retornada,
                            'robo_inicio' => $usuario->robo_inicio,
                            'configuracao' => $usuario->configuracao_texto,
                            'lucro' => $callback_lucro(-1 * ($valor + $valor_branco)),
                            'banca' => $banca
                        ]);
                    }

                    $lucro = $usuario->lucro;

                    if ($usuario->status_objetivo == 'EXECUTANDO') {
                        if ($usuario->usuario_objetivo->atualizar_progresso()) {
                            $usuario->roboStatus = 'PARANDO';
                            $this->pubsub->subscribe($this->channel_historico);
                            $this->pubsub->subscribe($this->usuario_historico);
                            $this->pubsub->unsubscribe($channel_sinais);
                            $protecao = 0;
                            break;
                        }
                    }
                    // echo "quantidade loss: {$usuario->quantidade_loss} == {$usuario->stop_loss}\n";
                    DoubleErros::registrar($usuario->canal->plataforma->id, 'usuario', 'processar', $lucro);
                    if ($usuario->tipo_stop_loss == 'QUANTIDADE')
                        $ocorreu_stop_loss = $usuario->quantidade_loss >= $usuario->stop_loss;
                    else
                        $ocorreu_stop_loss = -round($usuario->stop_loss, 2) >= $lucro;

                    $ocorreu_stop_win = round($usuario->stop_win, 2) <= round($lucro, 2);

                    if ($ocorreu_stop_loss or $ocorreu_stop_win) {
                        $entrada_automatica = false;
                        if ($ocorreu_stop_loss){
                            $message = $usuario->plataforma->translate->MSG_BET_4;
                            
                            if ($usuario->entrada_automatica == 'A' or $usuario->entrada_automatica == 'B')
                            {
                                $entrada_automatica = true;
                                $message = str_replace(
                                    ['{quantidade}', '{tipo}'],
                                    [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
                                    $usuario->plataforma->translate->MSG_STOP_LOSS_4
                                );
                                $botao_inicio = $botao;
                            }
                        }
                        else if ($ocorreu_stop_win) {
                            $message = $usuario->plataforma->translate->MSG_BET_5;
                            if ($usuario->entrada_automatica == 'Y' or $usuario->entrada_automatica == 'A')
                            {
                                $entrada_automatica = true;
                                $message = str_replace(
                                    ['{quantidade}', '{tipo}'],
                                    [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
                                    $usuario->plataforma->translate->MSG_STOP_WIN_4
                                );
                                $botao_inicio = $botao;
                            }
                        }
                        
                        $usuario = DoubleUsuario::identificarPorId($usuario->id);
                        if ($entrada_automatica)
                        {
                            $usuario->robo_iniciar_apos_loss = 'Y';
                            $usuario->quantidade_loss = 0;
                            if ($usuario->ciclo != 'A')
                                $usuario->robo_sequencia += 1;
                            if ($ocorreu_stop_win) 
                                $usuario->robo_sequencia += 1;
                            $usuario->ultimo_saldo = $usuario->plataforma->service->saldo($usuario);
                        }
                        else
                        {
                            $usuario->robo_iniciar = 'N';
                            $usuario->robo_status = 'PARANDO';

                            if ($usuario->metas == 'Y' and $usuario->usuario_meta)
                            {
                                $usuario->usuario_meta->ultimo_saldo = 0;
                                $usuario->usuario_meta->inicio_execucao = null;
                                $usuario->usuario_meta->proxima_execucao = null;
                                $usuario->usuario_meta->saveInTransaction();
                            }
                        }

                        if ($botao_inicio != $botao and  $usuario->status_objetivo == 'EXECUTANDO') 
                            $botao_inicio = $botao;
                        
                        $usuario->saveInTransaction();
                        TRedisUtils::sendMessage(
                            $usuario->chat_id, 
                            $usuario->canal->telegram_token, 
                            $message, 
                            $botao_inicio
                        );

                        $this->pubsub->subscribe($this->channel_historico);
                        $this->pubsub->subscribe($this->usuario_historico);
                        $this->pubsub->unsubscribe($channel_sinais);
                        break;
                    }

                    if ($win) {
                        break;
                    } else if ($protecao == $usuario->protecao){
                        TRedisUtils::sendMessage(
                            $usuario->chat_id, 
                            $usuario->canal->telegram_token, 
                            $usuario->plataforma->translate->MSG_BET_6, 
                            $botao
                        );
                        
                        $this->pubsub->subscribe($this->channel_historico);
                        $this->pubsub->subscribe($this->usuario_historico);
                        $this->pubsub->unsubscribe($channel_sinais);
                        break;
                    }

                    $protecao += 1;

                    if (isset($historico['estrategia_id']))
                    {
                        $estrategia = TUtils::openFakeConnection('double', function() use ($historico){
                            return new DoubleEstrategia($historico['estrategia_id'], false);
                        });

                        if ($estrategia and $estrategia->incrementa_valor_entrada == 'A_CADA_GALE') {
                            if ($usuario->protecao_branco == 'N')
                                $valor *= round($usuario->fator_multiplicador, 2);
                            else {
                                $valor = round($valor * 2.5, 2);
                            }
                        }
                    }
                    else
                    {
                        if ($usuario->protecao_branco == 'N')
                            $valor *= round($usuario->fator_multiplicador,2);
                        else {
                            $valor = round($valor * 2.5, 2);
                        }
                    }

                    $retorno= $callback_jogar();
                    if ($retorno !== '') {
                        $this->pubsub->unsubscribe($channel_sinais);
                        break;
                    }
                }

                $usuario->quantidade_loss = 0;
                $usuario->saveInTransaction();
                // echo "quantidade loss: {$usuario->quantidade_loss}\n";
            }
        }
    }

    public function run($param) 
    {
        $usuario = DoubleUsuario::identificarPorId($param['usuario_id']);
        $canal = $usuario->canal;

        $this->channel_historico = strtolower("{$this->serverName()}_{$canal->channel_id}_usuario_historico");
        $this->usuario_historico = strtolower("{$this->serverName()}_{$usuario->id}_usuario_historico");
        $this->fazer_entrada = strtolower("{$this->serverName()}_{$canal->plataforma->nome}_{$canal->plataforma->idioma}_fazer_entrada");
        $this->channel_entrada = strtolower("{$this->serverName()}_{$canal->plataforma->nome}_{$canal->plataforma->idioma}_notificar_entrada");

        // $manutencao_chat_ids = DoubleConfiguracao::getConfiguracao('manutencao_chat_ids');
        // if (in_array($usuario->chat_id, explode(',', $manutencao_chat_ids)))
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        if (substr(php_uname(), 0, 7) != "Windows")
        {   // Novo fluxo
            $redis = new Client();
            $this->pubsub = $redis->pubSubLoop();
            $this->pubsub->subscribe($this->channel_historico);
            $this->pubsub->subscribe($this->usuario_historico);

            while (true) {
                $output = shell_exec("supervisorctl status {$server_name}_usuario_{$usuario->id}_consumer");
                echo "output: {$output}\n";
                // Usa expressão regular para extrair o PID
                preg_match('/pid (\d+)/', $output, $matches);
                if (isset($matches[1])) 
                    break;
            }
            echo "output: {$output}\n";
            // Usa expressão regular para extrair o PID
            preg_match('/pid (\d+)/', $output, $matches);

            // Verifica se o PID foi encontrado
            if (isset($matches[1])) {
                $pid_supervidor = $matches[1];
                echo "O PID extraído é: {$pid_supervidor} \n";

                // Processo que será procurado
                $processo = "class=TDoubleUsuarioConsumer&method=run&usuario_id={$usuario->id}&server_name={$this->serverName()}";

                // Comando para obter todos os PIDs do processo
                $command = 'ps aux | grep -E ".*' . $processo . '$" | awk \'{print $2}\'';

                // Executa o comando e captura os PIDs
                $output = shell_exec($command);

                // Remove espaços em branco e transforma a saída em um array
                $pids = array_filter(explode("\n", trim($output)));

                // Verifica e mata os PIDs que não são 123
                foreach ($pids as $pid) {
                    // Remove espaços em branco ao redor do PID e verifica se não é vazio
                    $pid = trim($pid);
                    
                    // Verifica se o PID é válido e diferente de 123
                    if ($pid && $pid != $pid_supervidor) {
                        // Executa o comando kill para o PID válido
                        shell_exec('kill -9 ' . escapeshellarg($pid));
                        echo "Processo com PID $pid foi encerrado.\n";
                    } else {
                        echo "Processo com PID $pid não foi encerrado.\n";
                    }
                }
            } else {
                echo "PID não encontrado.\n";
            }
            
            try {
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
                    if ($message->kind === 'message' ) 
                    {
                        if ($usuario->roboStatus == 'EXECUTANDO') 
                        {
                            $usuario = DoubleUsuario::identificarPorId($usuario->id);
        
                            // Verifica se o usuário possui estratégias próprias e se o histórico é do canal
                            // >> se SIM ignora a mensagem
                            if ($usuario->possui_estrategias and $message->channel == $this->channel_historico)
                                continue;
        
                            // Verifica se o usuário não possui estratégias próprias e se o histórico é do usuário
                            // >> se SIM ignora a mensagem
                            if (!$usuario->possui_estrategias and $message->channel == $this->usuario_historico)
                                continue;
        
                            echo "received message: {$message->channel} - {$message->payload}\n";
                            $this->processar_sinais($usuario, $message);
                        }
                    }
                } 
            } catch (\Throwable $th) {
                $this->pubsub->unsubscribe($this->channel_historico);
                $this->pubsub->unsubscribe($this->usuario_historico);
                
                $trace = json_encode($th->getTrace());
                $message = $th->getMessage();
                echo "---\n$message\n---\n$trace\n---\n";
            } 
        } else
        {
            $usuario->roboStatus = 'PARADO';
            sleep(5);
            $usuario->roboStatus = 'EXECUTANDO';

            $processo = "class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id={$usuario->id}";
            if (substr(php_uname(), 0, 7) == "Windows") 
            {
                $command = 'powershell.exe -Command "Get-WmiObject Win32_Process | Where-Object { $_.CommandLine -match \''. $processo . '\' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }"';
            }
            else 
            {
                $command = 'ps aux | grep -E ".*' . $processo . '$" | awk \'{print $2}\' | xargs kill -9';
            }
            shell_exec($command);

            $redis_param = [
                'usuario_id' => $usuario->id
            ];
            // php cmd.php "class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id=7"
            TUtils::cmd_run('TDoubleUsuarioSinaisConsumer', 'run', $redis_param);
         
            $options = [
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'persistent' => true,
                'read_write_timeout' => 0
            ];
            $redis = new Client($options);
            $this->pubsub = $redis->pubSubLoop();
            $this->pubsub->subscribe($this->channel_historico);
            $this->pubsub->subscribe($this->usuario_historico);
            
            while (true) 
            {
                if ($usuario->roboStatus !== 'EXECUTANDO')
                    break;

                try {
                    foreach ($this->pubsub as $message) {
                        $message = (object) $message;
                        // echo "received message: {$message->channel} - {$message->payload}\n";
                        if ($message->kind === 'message' ) 
                        {
                            if ($usuario->roboStatus == 'EXECUTANDO') 
                            {
                                $usuario = DoubleUsuario::identificarPorId($usuario->id);
            
                                // echo "received message: {$message->channel} - {$message->payload}\n";
                                
                                // Verifica se o usuário possui estratégias próprias e se o histórico é do canal
                                // >> se SIM ignora a mensagem
                                if ($usuario->possui_estrategias and $message->channel == $this->channel_historico)
                                    continue;
            
                                // Verifica se o usuário não possui estratégias próprias e se o histórico é do usuário
                                // >> se SIM ignora a mensagem
                                if (!$usuario->possui_estrategias and $message->channel == $this->usuario_historico)
                                    continue;
            
                                echo "received message: {$message->channel} - {$message->payload}\n";
                                $this->processar_sinais($usuario, $message);
                                if ($usuario->roboStatus !== 'EXECUTANDO') 
                                {
                                    $this->pubsub->unsubscribe($this->channel_historico);
                                    $this->pubsub->unsubscribe($this->usuario_historico);
                                }
                            } else 
                            {
                                $this->pubsub->unsubscribe($this->channel_historico);
                                $this->pubsub->unsubscribe($this->usuario_historico);
                                break;
                            }
                        }
                    } 
                } catch (\Throwable $th) {
                    $trace = ''; // json_encode($th->getTrace());
                    DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioConsumer', 'run', $th->getMessage(), $trace);

                    $redis = new Client();
                    $this->pubsub = $redis->pubSubLoop();
                    $this->pubsub->subscribe($this->channel_historico);
                    $this->pubsub->subscribe($this->usuario_historico);
                } 
            }
            
        }
    }
}