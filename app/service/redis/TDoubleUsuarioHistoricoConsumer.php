<?php

use Predis\Client;

class TDoubleUsuarioHistoricoConsumer extends TDoubleRedis
{
    public function run($param)
    {
        $channel_name = strtolower("{$this->serverName()}_usuario_historico");

        $redis = new Client();
        // $pubsub = $redis->pubSubLoop();

        // $pubsub->subscribe($channel_name);

        $callback = function ($message) {
            // echo "received message: {$message->channel} - {$message->payload}\n";
            // $object = json_decode($message->payload);
            $object = $message;

            $usuario = DoubleUsuario::identificarPorId($object->usuario_id);

            if (in_array($object->tipo, ['WIN', 'LOSS', 'GALE'])) 
            {
                TUtils::openConnection('double', function() use ($object) {
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
                    $bet->save();
                });
            }

            if (in_array($object->tipo, ['WIN', 'LOSS'])) 
            {
                $cor_result = TRedisUtils::getCor($object->cor, $usuario->plataforma->translate);
                $lucro = $object->lucro;
                $banca = $object->banca;
        
                $botao = [];
                if ($usuario->plataforma->url_sala_sinais)
                    $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_SALA_SINAIS,  "url" => $usuario->plataforma->url_sala_sinais]];
                if ($usuario->plataforma->url_comunidade)
                    $botao[] = [["text" => $usuario->plataforma->translate->BOTAO_COMUNIDADE,  "url" => $usuario->plataforma->url_comunidade]];
                if ($usuario->plataforma->url_tutorial)
                    $botao[] = [["text" => str_replace(['{plataforma}'], [$usuario->plataforma->nome], $usuario->plataforma->translate->MSG_SINAIS_TUTORIAL),  "url" => $usuario->plataforma->url_tutorial]];
                if ($usuario->plataforma->url_suporte)
                    $botao[] = [["text" => $usuario->plataforma->translate->MSG_SINAIS_SUPORTE,  "url" => $usuario->plataforma->url_suporte]];
                
                TRedisUtils::sendMessage(
                    $usuario->chat_id, 
                    $usuario->canal->telegram_token, 
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
        };

        // foreach ($pubsub as $message) {
        //     $message = (object) $message;
        //     if ($message->kind === 'message') {
        //         $callback($message);
        //     }
        // } 
         
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
                DoubleErros::registrar(
                    1, 
                    'TDoubleUsuarioHistoricoConsumer', 
                    'run', 
                    $th->getMessage()
                ); 
            }
        }
    }
}