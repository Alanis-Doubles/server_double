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

            if (isset($historico['fator']))
                $object->fator = $historico['fator'];
            if (isset($historico['dice']))
                $object->dice = $historico['dice'];
            
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

    private function gerar_entrada($usuario, $sinal) {
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
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
        echo "python: {$response}\n";
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $json = null;

        if ($http_status == 200) {
            // echo "python: {$response}\n";
            $historico = json_decode($response);

            if (json_last_error() === JSON_ERROR_NONE) {
                if ($historico->tipo == 'ENTRADA')
                {
                    if (!$historico->cor)
                        return null;

                    $json = [
                        'plataforma_id' => $usuario->canal->plataforma->id,
                        'canal_id' => $usuario->canal->id,
                        'cor' => $historico->cor,
                        'informacao' => $historico->informacao,
                        'estrategia_id' => $historico->estrategia_id,
                        'tipo' => 'ENTRADA',
                        'numero' => $historico->numero,
                        'usuario_id' => $usuario->id,
                        'fator' => $sinal->fator,
                        'dice' => $sinal->dice
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
        $estrategia = TUtils::openFakeConnection('double', function() use ($estrategia_id){
            return new DoubleEstrategia($estrategia_id, false);
        });
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

                    if (!$win and $estrategia->protecao_branco == 'Y')
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
                            'usuario_id' => $usuario->id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];
                        $this->notificar_consumidores($output);
                        break;
                    } elseif ($estrategia->protecoes == $protecao) {
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
                            'usuario_id' => $usuario->id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];
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
                            'usuario_id' => $usuario->id,
                            'fator' => $object->fator,
                            'dice' => $object->dice
                        ];
                        $this->notificar_consumidores($output);
                        $protecao += 1;
                    } 
                } else {
                    break;
                }
            }
        }
    }

    private function gerar_sinais($usuario, $sinal){
        $canal = $usuario->canal;
        $output = $this->gerar_entrada($usuario, $sinal);
        
        if ($output) {
            echo "Tipo: {$output['tipo']}\n";
            if ($output['tipo'] !== 'ENTRADA')
                return;

            $historico = $this->notificar_consumidores($output);
            $entrada_id = $historico['id'];
            $estrategia_id = $historico['estrategia_id'];
            $cor = $output['cor'];
            DoubleErros::registrar(1, 'canal', 'run', 'cor', $cor);
            $canal = DoubleCanal::identificar($canal->id);

            $this->processar_sinais($usuario, $cor, $entrada_id, $estrategia_id);
        }
    }

    public function run($param)
    {
        $usuario = DoubleUsuario::identificarPorId($param['usuario_id']);

        $channel_name = strtolower("{$this->serverName()}_{$usuario->canal->plataforma->nome}_{$usuario->canal->plataforma->idioma}_sinais");
        
        $redis = new Client([
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        $this->pubsub = $redis->pubSubLoop();
        $this->pubsub->subscribe($channel_name);

        // $manutencao_chat_ids = DoubleConfiguracao::getConfiguracao('manutencao_chat_ids');
        // if (in_array($usuario->chat_id, explode(',', $manutencao_chat_ids)))
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        if (substr(php_uname(), 0, 7) != "Windows")
        {   // Novo fluxo
            while (true) {
                $output = shell_exec("supervisorctl status {$server_name}_usuario_{$usuario->id}_sinais_consumer");
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
                $processo = "class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id={$usuario->id}";

                // Comando para obter todos os PIDs do processo
                $command = 'ps aux | grep -E ".*' . $processo . '$" | awk \'{print $2}\'';

                // Executa o comando e captura os PIDs
                $output = shell_exec($command);
                
                if (empty($output))
                    $pids = [];
                else
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
            // $processo = "class=TDoubleUsuarioSinaisConsumer&method=run&usuario_id={$usuario->id}";
            // $command = 'ps aux | grep -E ".*' . $processo . '$" | awk \'{print $2}\' | xargs kill -9';
            // shell_exec($command);

            try {
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
                    echo "received message: {$message->channel} - {$message->payload}\n";
        
                    if ($message->kind === 'message') {
                        if ($usuario->roboStatus == 'EXECUTANDO')  {
                            echo "received message: {$message->channel} - {$message->payload}\n";
                            $this->gerar_sinais($usuario, json_decode($message->payload));
                        } 
                    }
                }    
            } catch (\Throwable $th) {
                $this->pubsub->unsubscribe(($channel_name));
                
                $trace = $th->getTrace();
                $message = $th->getMessage();
                echo "---\n$message\n---\n$trace\n---\n";
            }
        } else 
        {
            try {
                foreach ($this->pubsub as $message) {
                    $message = (object) $message;
        
                    if ($message->kind === 'message') {
                        if ($usuario->roboStatus == 'EXECUTANDO')  {
                            echo "received message: {$message->channel} - {$message->payload}\n";
                            $this->gerar_sinais($usuario, json_decode($message->payload));
                        } else {
                            $this->pubsub->unsubscribe(($channel_name));
                            break;
                        }
                    }
                    // break;
                }    
            } catch (\Throwable $th) {
                $trace = ''; //json_encode($th->getTrace());
                $redis = new Client([
                    'persistent' => true,
                    'read_write_timeout' => -1
                ]);
                $this->pubsub = $redis->pubSubLoop();
                $this->pubsub->subscribe($channel_name);
            }
        }
    }
} 