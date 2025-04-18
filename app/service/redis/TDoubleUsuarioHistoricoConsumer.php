<?php

use Predis\Client;
use GuzzleHttp\Client as GuzzleClient;

class TDoubleUsuarioHistoricoConsumer extends TDoubleRedis
{
    public function run($param)
    {
        $channel_name = strtolower("{$this->serverName()}_usuario_historico");

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => $this->hostUsuario(), // IP do seu Redis
            'port'   => 6379, // Porta padrão do Redis
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        // $pubsub = $redis->pubSubLoop();

        // $pubsub->subscribe($channel_name);

        $callback = function ($message) {
            // echo "received message: {$message->channel} - {$message->payload}\n";
            // $object = json_decode($message->payload);
            $object = $message;

            $usuario = DoubleUsuario::identificarPorId($object->usuario_id);

            if (in_array($object->tipo, ['WIN', 'LOSS', 'GALE'])) 
            {
                TUtils::openConnection('double', function() use ($object, $usuario) {
                    $bet = new DoubleUsuarioHistorico;
                    $bet->sequencia = $object->sequencia;
                    $bet->usuario_id = $object->usuario_id;
                    $bet->valor = $object->valor;
                    $bet->entrada_id = $object->entrada_id;
                    $bet->valor_entrada = $object->valor_entrada;
                    $bet->valor_branco = $object->valor_branco;
                    $bet->gale = $object->gale;
                    $bet->tipo = $object->tipo;
                    $bet->robo_inicio = $object->robo_inicio;
                    $bet->configuracao = $object->configuracao;
                    $bet->modo_treinamento = $usuario->modo_treinamento;
                    
                    if (isset($object->fator))
                        $bet->fator = $object->fator;
                    if (isset($object->dice))
                        $bet->ticker = $object->dice;
                    if (isset($object->ticker))
                        $bet->ticker = $object->ticker;
                    if (isset($object->ticker_description))
                        $bet->ticker_description = $object->ticker_description;
                    if (isset($object->ticker_classifier))
                        $bet->ticker_classifier = $object->ticker_classifier;
                    if (isset($object->banca))
                        $bet->banca = $object->banca;
                    $bet->save();
                });
            }

            if (in_array($object->tipo, ['WIN', 'LOSS'])) 
            {
                $cor_result = TRedisUtils::getCor($object->cor, $usuario->plataforma->translate);
                if ($usuario->status_objetivo == 'EXECUTANDO') {
                    $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                        return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                            ->where('sequencia', '=', $usuario->robo_sequencia)
                            ->sumBy('valor', 'total');
                    }) ?? 0;
                } else
                    $lucro = $object->lucro;

                $banca = number_format($object->banca, 2, ',', '.');

                $botao = [];
                if ($usuario->plataforma->url_sala_sinais)
                    $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_SALA_SINAIS,  "url" => $usuario->plataforma->url_sala_sinais]];
                if ($usuario->plataforma->url_comunidade)
                    $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_COMUNIDADE,  "url" => $usuario->plataforma->url_comunidade]];
                if ($usuario->plataforma->url_tutorial)
                    $botao[] = [["text" => str_replace(['{plataforma}'], [$usuario->plataforma->nome], $usuario->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $usuario->plataforma->url_tutorial]];
                if ($usuario->plataforma->url_suporte)
                    $botao[] = [["text" => $usuario->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $usuario->plataforma->url_suporte]];
                
                $dados_resumo = TUtils::openConnection('double', function() use ($usuario) {
                    return TDashboardUsuarioService::getStatusUsuario($usuario);
                });
                
                $dados_resumo = json_decode($dados_resumo);
                $assertividade = number_format($dados_resumo->total_win / ($dados_resumo->total_win + $dados_resumo->total_loss) * 100, 2, ',', '.');
                $ultima_entrada = number_format($object->valor_entrada, 2, ',', '.');
                $msg_resumo = "\n\n🏆 Win {$dados_resumo->total_win}   ❌ Loss {$dados_resumo->total_loss}\n\n📈 Assertividade: {$assertividade}%\n\n⬆ Última Entrada $ {$ultima_entrada}";
                
                if ($usuario->status_objetivo == 'EXECUTANDO') {
                    $msg_resumo .= "\n\n" . $usuario->usuario_objetivo->progresso;
                }
                echo "$msg_resumo\n";

                $lucro_atual = TUtils::openFakeConnection('double', function() use($usuario, $object) {
                    return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                        ->where('entrada_id', '=', $object->entrada_id)
                        ->sumBy('valor', 'total');
                }) ?? 0;

                // echo $usuario->plataforma->translate->MSG_BET_10 . $msg_resumo . "\n";
                $mensagem = str_replace(
                    ['{cor}', '{lucro}', '{banca}', '{lucro_atual}'],
                    [$cor_result, number_format($lucro, 2, ",", "."), $banca, number_format($lucro_atual, 2, ",", ".")],
                    $usuario->plataforma->translate->MSG_BET_10 . $msg_resumo
                );
                echo "$mensagem\n";

                TRedisUtils::sendMessage(
                    $usuario->chat_id, 
                    $usuario->canal->telegram_token, 
                    $mensagem,
                    [
                        "resize_keyboard" => true, 
                        "inline_keyboard" => $botao
                    ]
                );

                if ($usuario->webhook)
                {
                    if ($object->tipo === 'WIN')
                        $mensagem = "✅ Win\n\n$mensagem";
                    else
                        $mensagem = "❌ Loss\n\n$mensagem";

                    $client = new GuzzleClient();
                    $client->post(
                        $usuario->webhook,
                        [
                            'json' => json_encode(
                                [
                                    'plataforma' => $usuario->canal->plataforma->nome,
                                    'mensagem' => $mensagem
                                ]
                            )
                        ]
                    );
                }

                // $dados_resumo = TUtils::openConnection('double', function() use ($usuario) {
                //     return TDashboardUsuarioService::getStatusUsuario($usuario);
                // });
                
                // $dados_resumo = json_decode($dados_resumo);
                // $msg_resumo = "Resumo\n🏆 Win {$dados_resumo->total_win}   ❌ Loss {$dados_resumo->total_loss}\n⬆ Maior Entrada $ {$dados_resumo->maior_entrada}";
                // echo "$msg_resumo\n";

                // TRedisUtils::sendMessage(
                //     $usuario->chat_id, 
                //     $usuario->canal->telegram_token, 
                //     $msg_resumo
                // );
            }
        };

        // foreach ($pubsub as $message) {
        //     $message = (object) $message;
        //     if ($message->kind === 'message') {
        //         $callback($message);
        //     }
        // } 
        echo "iniciando\n";
        while (true) {
            try {
                $message = $redis->brpop($channel_name, 0); 
                // echo "$message\n";
                if ($message) {
                    $payload = json_decode($message[1]);
                    echo "{$message[1]}\n";
            
                    $callback($payload);
                }
            } catch (\Throwable $th) {
                echo "$th\n";
                $redis->disconnect();
                $redis = new Client([
                    'scheme' => 'tcp',
                    'host'   => $this->hostUsuario(), // IP do seu Redis
                    'port'   => 6379, // Porta padrão do Redis
                    'persistent' => true,
                    'read_write_timeout' => -1
                ]);
            }
        }
    }
}