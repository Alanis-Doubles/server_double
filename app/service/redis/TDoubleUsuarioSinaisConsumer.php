<?php

use Predis\Client;

class TDoubleUsuarioSinaisConsumer extends TDoubleRedis
{
    private $pubsub;

    public function notificar_consumidores($historico)
    {
        $channel_name = strtolower("{$this->serverName()}_{$historico['usuario_id']}_usuario_historico");
        // echo json_encode($historico) . "\n";

        $historico_canal = TUtils::openConnection('double', function () use ($historico) {
            $object = new DoubleHistorico();

            $object->plataforma_id = $historico['plataforma_id'];
            $object->canal_id = $historico['canal_id'];
            $object->tipo = $historico['tipo'];
            $object->usuario_id = $historico['usuario_id'];
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
            $object->save();

            return $object->toArray();
        });

        $redis = new Client();
        // echo "histórico: ". json_encode($historico_canal) . "\n";
        $payload = json_encode($historico_canal);
        $redis->publish($channel_name, $payload);
        // echo "{$channel_name} - {$payload}\n";

        return $historico_canal;
    }

    private function gerar_entrada($usuario) {
        // $dir_double_python_ia = DoubleConfiguracao::getConfiguracao('dir_double_python_ia');
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');

        // if (substr(php_uname(), 0, 7) == "Windows") {
        //     $command = "$dir_double_python_ia/venv/Scripts/python $dir_double_python_ia/main.py $server_name {$usuario->id}";
        // } else {
        //     $command = "$dir_double_python_ia/venv/bin/python $dir_double_python_ia/main.py $server_name {$usuario->id}";
        // }
        // if (substr(php_uname(), 0, 7) == "Windows") 
        //     $command = str_replace(['/'], ['\\'], $command);
        // $command = escapeshellcmd($command);  
        // $output = trim(shell_exec($command));
        // // echo "python: {$output}\n";
        // // DoubleErros::registrar(1, 'canal', 'run', 'python', $output);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://127.0.0.1:5000/buscar_sinal/$server_name/{$usuario->id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        // echo "python: {$response}\n";
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($http_status == 200) {
            $historico = json_decode($response);

            $json = null;
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($historico->tipo == 'ENTRADA')
                {
                    $json = [
                        'plataforma_id' => $usuario->canal->plataforma->id,
                        'canal_id' => $usuario->canal->id,
                        'cor' => $historico->cor,
                        'informacao' => $historico->informacao,
                        'estrategia_id' => $historico->estrategia_id,
                        'tipo' => 'ENTRADA',
                        'numero' => $historico->numero,
                        'usuario_id' => $usuario->id,
                    ];
                } else 
                {
                    $json = [
                        'tipo' => $historico->tipo
                    ];
                }
            } else 
            {
                // echo json_last_error_msg();
                DoubleErros::registrar($usuario->canal->plataforma->id, 'TDoubleHistoricoConsumer', 'callback', json_last_error_msg(), $response);
            }
            $payload = json_encode($json);
        }
        // echo "$payload \n";
        return $json;
    }

    private function processar_sinais($usuario, $cor_esperada, $entrada_id, $estrategia_id) {
        $canal = $usuario->canal;
        $plataforma = $canal->plataforma;
        $protecao = 0;
        foreach ($this->pubsub as $message) {
            $message = (object) $message;

            if ($message->kind === 'message') {
                if ($usuario->roboStatus == 'EXECUTANDO')  { 
                    // echo "received message: {$message->channel} - {$message->payload}\n";
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
                            'usuario_id' => $usuario->id
                        ];
                        $this->notificar_consumidores($output);

                        // TRedisUtils::sendMessage(
                        //     $canal->channel_id,
                        //     $canal->telegram_token,
                        //     $object->cor == 'white' ? $plataforma->translate->MSG_SINAIS_WIN_BRANCO : $plataforma->translate->MSG_SINAIS_WIN,
                        //     []
                        // );

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
                            'usuario_id' => $usuario->id
                        ];
                        $this->notificar_consumidores($output);

                        // TRedisUtils::sendMessage(
                        //     $canal->channel_id,
                        //     $canal->telegram_token,
                        //     str_replace(
                        //         ["{cor_retornada}"],
                        //         [TRedisUtils::getCor($object->cor, $plataforma->translate)],
                        //         $plataforma->translate->MSG_SINAIS_LOSS,
                        //     ),
                        //     [],
                        // );

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
                            'usuario_id' => $usuario->id
                        ];
                        $this->notificar_consumidores($output);

                        $gales = ['primeira', 'segunda', 'terceira', 'quarta', 'quinta', 'sexta'];

                        // TRedisUtils::sendMessage(
                        //     $canal->channel_id,
                        //     $canal->telegram_token,
                        //     str_replace(
                        //         ['{protecao}', '{n_protecao}'],
                        //         [$gales[$protecao], $protecao + 1],
                        //         $plataforma->translate->MSG_SINAIS_GALE,
                        //     ),
                        //     [],
                        //     true
                        // );

                        $protecao += 1;
                    } 
                } 
            }
        }
    }

    private function gerar_sinais($usuario){
        $canal = $usuario->canal;
        // $plataforma = $canal->plataforma;

        $output = $this->gerar_entrada($usuario);
        // echo "Tipo: {$output['tipo']}\n";
        if ($output) {
            if ($output['tipo'] !== 'ENTRADA')
                return;

            $historico = $this->notificar_consumidores($output);
            $entrada_id = $historico['id'];
            $estrategia_id = $historico['estrategia_id'];
            $cor = $output['cor'];
            DoubleErros::registrar(1, 'canal', 'run', 'cor', $cor);
            $canal = DoubleCanal::identificar($canal->id);

            // $botao = [];
            // if ($plataforma->url_grupo_vip)
            //     $botao[] = [["text" => $plataforma->translate->BOTAO_GRUPO_VIP,  "url" => $plataforma->url_grupo_vip]];
            // if ($plataforma->url_cadastro)
            //     $botao[] = [["text" => $plataforma->translate->MSG_SINAIS_CADASTRO,  "url" => $plataforma->url_cadastro]];
            // if ($plataforma->url_tutorial)
            //     $botao[] = [["text" => str_replace(['{plataforma}'], [$plataforma->nome], $plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $plataforma->url_tutorial]];
            // if ($plataforma->url_suporte)
            //     $botao[] = [["text" => $plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $plataforma->url_suporte]];
            // if ($plataforma->url_robo)
            //     $botao[] = [["text" => $plataforma->translate->MSG_ROBO_AUTOMATICO,  "url" => $plataforma->url_robo]];

            // TRedisUtils::sendMessage(
            //     $canal->channel_id,
            //     $canal->telegram_token,
            //     str_replace(
            //         ['{estrategia}', '{cor}', '{ultimo_numero}', '{ultima_cor}', '{informacao}', '{protecoes}'],
            //         [
            //             TRedisUtils::buscarNomeEstrategia(isset($output['estrategia_id']) ? $output['estrategia_id'] : ''), 
            //             TRedisUtils::getCor($cor, $plataforma->translate), 
            //             $output['numero'], 
            //             TRedisUtils::getCor($cor, $plataforma->translate, false), 
            //             isset($output['informacao']) ? $output['informacao'] : '',
            //             $canal->protecoes
            //         ],
            //         $plataforma->translate->MSG_SINAIS_ENTRADA_CONFIRMADA,
            //     ), 
            //     [
            //         "resize_keyboard" => true, 
            //         "inline_keyboard" => $botao
            //     ]
            // );

            $this->processar_sinais($usuario, $cor, $entrada_id, $estrategia_id);
        }
    }

    public function run($param)
    {
        $usuario = DoubleUsuario::identificarPorId($param['usuario_id']);

        $channel_name = strtolower("{$this->serverName()}_{$usuario->canal->plataforma->nome}_{$usuario->canal->plataforma->idioma}_sinais");

        $redis = new Client();
        $this->pubsub = $redis->pubSubLoop();
        $this->pubsub->subscribe($channel_name);

        while (true)
        {
            DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', 'roboStatus', $usuario->roboStatus);
            if ($usuario->roboStatus !== 'EXECUTANDO') {
                DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', 'sair');
                break;
            }

            try {
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
        
                    if ($message->kind === 'message') {
                        DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', $message->kind, $usuario->roboStatus);
                        if ($usuario->roboStatus == 'EXECUTANDO')  {
                            // echo "received message: {$message->channel} - {$message->payload}\n";
                            $this->gerar_sinais($usuario);
                        } else {
                            DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', 'saindo');
                            $this->pubsub->unsubscribe(($channel_name));
                            break;
                        }
                    }
                    // break;
                }    
            } catch (\Throwable $th) {
                $trace = ''; //json_encode($th->getTrace());
                DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', $th->getMessage(), $trace);

                $redis = new Client();
                $this->pubsub = $redis->pubSubLoop();
                $this->pubsub->subscribe($channel_name);
            }
        }    
        DoubleErros::registrar($usuario->plataforma->id, 'TDoubleUsuarioSinaisConsumer', 'run', 'saiu');
    }
} 