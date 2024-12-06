<?php

use Predis\Client;

class TDoubleCanalConsumer extends TDoubleRedis
{
    private $pubsub;

    public function notificar_consumidores($historico)
    {
        $channel_name = strtolower("{$this->serverName()}_canal_historico");
        // echo json_encode($historico) . "\n";

        $historico_canal = TUtils::openConnection('double', function () use ($historico) {
            $object = new DoubleHistorico();

            $object->plataforma_id = $historico['plataforma_id'];
            $object->canal_id = $historico['canal_id'];
            $object->tipo = $historico['tipo'];
            if (isset($historico['cor']))
                $object->cor = $historico['cor'];
            if (isset($historico['estrategia_id']))
                $object->estrategia_id = $historico['estrategia_id'];
            if (isset($historico['informacao']))
                $object->informacao = $historico['informacao'];
            if (isset($historico['entrada_id']))
                $object->entrada_id = $historico['entrada_id'];
            if (isset($historico['gale']))
                $object->gale = $historico['gale'];
            
            if (isset($historico['fator']))
                $object->fator = $historico['fator'];
            if (isset($historico['dice']))
                $object->dice = $historico['dice'];

            $object->save();

            return $object->toArray();
        });

        $redis = new Client();
        echo "histórico: ". json_encode($historico_canal) . "\n";
        $redis->publish($channel_name, json_encode($historico_canal));
        // echo "{$channel_name}: enviado\n";

        return $historico_canal;
    }

    private function gerar_entrada($canal, $sinal) {
        // $dir_double_python_ia = DoubleConfiguracao::getConfiguracao('dir_double_python_ia');
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');

        // if (substr(php_uname(), 0, 7) == "Windows") {
        //     $command = "$dir_double_python_ia/venv/Scripts/python $dir_double_python_ia/main.py $server_name";
        // } else {
        //     $command = "$dir_double_python_ia/venv/bin/python $dir_double_python_ia/main.py $server_name";
        // }
        // if (substr(php_uname(), 0, 7) == "Windows") 
        //     $command = str_replace(['/'], ['\\'], $command);
        // $command = escapeshellcmd($command);  

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://127.0.0.1:5000/buscar_sinal/$server_name",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $json = null;
        if ($http_status == 200) {
            // $output = trim(shell_exec($command));
            // echo "python: {$output}\n";
            // DoubleErros::registrar(1, 'canal', 'run', 'python', $output);

            echo "python: {$response}\n";
            $historico = json_decode($response);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($historico->tipo == 'ENTRADA')
                {
                    if (!$historico->cor)
                        return null;

                    $json = [
                        'plataforma_id' => $canal->plataforma->id,
                        'canal_id' => $canal->id,
                        'cor' => $historico->cor,
                        'informacao' => $historico->informacao,
                        'estrategia_id' => $historico->estrategia_id,
                        'tipo' => 'ENTRADA',
                        'numero' => $historico->numero,
                        'fator' => $sinal->fator,
                        'dice' => $sinal->dice
                        // 'id' => $historico_canal->id,
                    ];
                } else 
                {
                    $json = [
                        'tipo' => $historico->tipo
                    ];
                }
            } else 
            {
                echo json_last_error_msg();
                DoubleErros::registrar($canal->plataforma->id, 'TDoubleHistoricoConsumer', 'callback', json_last_error_msg(), $response);
            }
        }
        return $json;
    }

    private function processar_sinais($canal, $cor_esperada, $entrada_id, $estrategia_id) {
        $plataforma = $canal->plataforma;
        $protecao = 0;
        foreach ($this->pubsub as $message) {
            $message = (object) $message;

            if ($message->kind === 'message') {
                if ($canal->statusSinais == 'EXECUTANDO')  { 
                    echo "received message: {$message->channel} - {$message->payload}\n";
                    $object = json_decode($message->payload);
                    $object->entrada_id = $entrada_id;
                    $object->estrategia_id = $estrategia_id;
                    $win = $object->cor == $cor_esperada;

                    if (!$win and $canal->protecao_branco == 'Y')
                        $win = $object->cor == 'white';

                    if ($win) {
                        $object->entrada_id = $entrada_id;
                        $object->tipo = 'WIN';
                        $object->protecao = $protecao;

                        $output = [
                            'plataforma_id' => $canal->plataforma->id,
                            'canal_id' => $canal->id,
                            'cor' => $object->cor,
                            'tipo' => $object->tipo,
                            'gale' => $object->protecao,
                            'entrada_id' => $object->entrada_id,
                            'estrategia_id' => $object->estrategia_id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];

                        if ($canal->enviarSinais())
                            TRedisUtils::sendMessage(
                                $canal->channel_id,
                                $canal->telegram_token,
                                $object->cor == 'white' ? $plataforma->translate->MSG_SINAIS_WIN_BRANCO : $plataforma->translate->MSG_SINAIS_WIN,
                                []
                            );

                        $this->notificar_consumidores($output);
                        break;
                    } elseif ($canal->protecoes == $protecao) {
                        $object->entrada_id = $entrada_id;
                        $object->tipo = 'LOSS';
                        $object->protecao = $protecao;
                        
                        $output = [
                            'plataforma_id' => $canal->plataforma->id,
                            'canal_id' => $canal->id,
                            'cor' => $object->cor,
                            'tipo' => $object->tipo,
                            'gale' => $object->protecao,
                            'entrada_id' => $object->entrada_id,
                            'estrategia_id' => $object->estrategia_id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];

                        if ($canal->enviarSinais())
                            TRedisUtils::sendMessage(
                                $canal->channel_id,
                                $canal->telegram_token,
                                str_replace(
                                    ["{cor_retornada}"],
                                    [TRedisUtils::getCor($object->cor, $plataforma->translate)],
                                    $plataforma->translate->MSG_SINAIS_LOSS,
                                ),
                                [],
                            );

                        $this->notificar_consumidores($output);
                        break;
                    } else {
                        $object->entrada_id = $entrada_id;
                        $object->tipo = 'GALE';
                        $object->protecao = $protecao;
                        
                        $output = [
                            'plataforma_id' => $canal->plataforma->id,
                            'canal_id' => $canal->id,
                            'cor' => $object->cor,
                            'tipo' => $object->tipo,
                            'gale' => $object->protecao,
                            'entrada_id' => $object->entrada_id,
                            'estrategia_id' => $object->estrategia_id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];

                        $gales = ['primeira', 'segunda', 'terceira', 'quarta', 'quinta', 'sexta'];

                        if ($canal->enviarSinais())
                            TRedisUtils::sendMessage(
                                $canal->channel_id,
                                $canal->telegram_token,
                                str_replace(
                                    ['{protecao}', '{n_protecao}'],
                                    [$gales[$protecao], $protecao + 1],
                                    $plataforma->translate->MSG_SINAIS_GALE,
                                ),
                                [],
                                true
                            );

                        $this->notificar_consumidores($output);
                        $protecao += 1;
                    } 
                } 
            }
        }
    }

    private function gerar_sinais($canal, $sinal){
        $plataforma = $canal->plataforma;

        $output = $this->gerar_entrada($canal, $sinal);
        if ($output) {
            if ($output['tipo'] !== 'ENTRADA')
                return;
            
            $cor = $output['cor'];
            // DoubleErros::registrar(1, 'canal', 'run', 'cor', $cor);
            $canal = DoubleCanal::identificar($canal->id);

            $botao = [];
            if ($plataforma->url_grupo_vip)
                $botao[] = [["text" => $plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $plataforma->url_grupo_vip]];
            if ($plataforma->url_cadastro)
                $botao[] = [["text" => $plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $plataforma->url_cadastro]];
            if ($plataforma->url_tutorial)
                $botao[] = [["text" => str_replace(['{plataforma}'], [$plataforma->nome], $plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $plataforma->url_tutorial]];
            if ($plataforma->url_double)
                $botao[] = [["text" => $plataforma->translate->MSG_SINAIS_ACESSAR,  "url" => $plataforma->url_double]];
            if ($plataforma->url_suporte)
                $botao[] = [["text" => $plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $plataforma->url_suporte]];
            if ($plataforma->url_robo)
                $botao[] = [["text" => $plataforma->translate->MSG_ROBO_AUTOMATICO,  "url" => $plataforma->url_robo]];

            if ($canal->enviarSinais())
                TRedisUtils::sendMessage(
                    $canal->channel_id,
                    $canal->telegram_token,
                    str_replace(
                        ['{estrategia}', '{cor}', '{ultimo_numero}', '{ultima_cor}', '{informacao}', '{protecoes}'],
                        [
                            TRedisUtils::buscarNomeEstrategia(isset($output['estrategia_id']) ? $output['estrategia_id'] : ''), 
                            TRedisUtils::getCor($cor, $plataforma->translate), 
                            $output['numero'], 
                            TRedisUtils::getCor($cor, $plataforma->translate, false), 
                            isset($output['informacao']) ? $output['informacao'] : '',
                            $canal->protecoes
                        ],
                        $plataforma->translate->MSG_SINAIS_ENTRADA_CONFIRMADA,
                    ), 
                    [
                        "resize_keyboard" => true, 
                        "inline_keyboard" => $botao
                    ]
                );

            $historico = $this->notificar_consumidores($output);
            $entrada_id = $historico['id'];
            $estrategia_id = $historico['estrategia_id'];
            $this->processar_sinais($canal, $cor, $entrada_id, $estrategia_id);
        }
    }

    public function run($param)
    {
        $canal = DoubleCanal::identificar($param['canal_id']);
        $channel_name = strtolower("{$this->serverName()}_{$canal->plataforma->nome}_{$canal->plataforma->idioma}_sinais");
        $channel_historico = strtolower("{$this->serverName()}_{$canal->channel_id}_usuario_historico");

        if (!in_array($canal->plataforma->tipo_sinais , ['GERA', 'PROPAGA_VALIDA_SINAL']))
            return;

        echo "{$canal->statusSinais}\n";
        $canal->statusSinais = 'INICIANDO';
        sleep(5);
        $canal->statusSinais = 'EXECUTANDO';
        echo "{$canal->statusSinais}\n";

        $redis = new Client([
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        $this->pubsub = $redis->pubSubLoop();

        if ($canal->plataforma->tipo_sinais == 'GERA')
            $currentChannel = $channel_name;
        else 
            $currentChannel = $channel_historico;

        $this->pubsub->subscribe($currentChannel);
        
        foreach ($this->pubsub as $message) {
            $message = (object) $message;

            if ($message->kind === 'message') {
                if ($canal->statusSinais == 'EXECUTANDO')  {
                    echo "received message: {$message->channel} - {$message->payload}\n";
                    if ($canal->plataforma->tipo_sinais == 'GERA')
                        $this->gerar_sinais($canal, json_decode($message->payload));
                } else
                    $this->pubsub->unsubscribe(($channel_name));
            }
        }        

        $canal->statusSinais == 'PARADO';
    }
}