<?php

use Predis\Client;

class ProfitMensagensService
{
    static public function enviarMensagensAgendadas() {
        TTransaction::open('communication');
        
        $monthday  = date('d');
        $weekday   = (string) (date('N') +1);
        $hour      = date('H');
        $minute    = date('i');
        
        $s1 = DoubleRecuperacaoMensagem::where('tipo_agendamento', '=', 'M')
                ->where('mensagem_direta', '=', 'A')
                ->where('dia_mes',         '=', $monthday)
                ->where('hora',            '=', $hour)
                ->where('minuto',          '=', $minute)
                ->where('ativo',           '=', 'Y')->load();
        
        $s2 = DoubleRecuperacaoMensagem::where('tipo_agendamento', '=', 'W')
                ->where('mensagem_direta', '=', 'A')
                ->where('dia_semana',      '=', $weekday)
                ->where('hora',            '=', $hour)
                ->where('minuto',          '=', $minute)
                ->where('ativo',           '=', 'Y')->load();

        $s3 = DoubleRecuperacaoMensagem::where('tipo_agendamento', '=', 'D')
                ->where('mensagem_direta', '=', 'A')
                ->where('hora',            '=', $hour)
                ->where('minuto',          '=', $minute)
                ->where('ativo',           '=', 'Y')->load();
        
        $s4 = DoubleRecuperacaoMensagem::where('tipo_agendamento', '=', 'F')
                ->where('mensagem_direta', '=', 'A')
                ->where('ativo',           '=', 'Y')->load();
        
        $schedules = array_merge($s1, $s2, $s3, $s4);
        TTransaction::close();
        
        foreach ($schedules as $schedule)
        {
            echo "\n" . date('Y-m-d H:i:s') . " - Iniciando envio de mensagens diretas";
            $list = TUtils::openConnection('double', function() use ($schedule) {
                $query = "SELECT u.id, 
                                u.chat_id,
                                u.status,
                                rm.id recuperacao_mensagem_id,
                                rm.mensagem, 
                                rm.botao_1_mensagem,
                                rm.botao_1_url
                            FROM double_usuario u
                            JOIN double_recuperacao_mensagem rm on (rm.status = u.status or rm.status = 'TODOS')
                        WHERE rm.id = {$schedule->id}";
                $conn = TTransaction::get();
                $list = TDatabase::getData(
                    $conn, 
                    $query, 
                    [
                        ['id','usuario_id'], 
                        ['recuperacao_mensagem_id','recuperacao_mensagem_id'],
                        ['status','status'],
                        ['mensagem', 'mensagem'],
                        ['botao_1_mensagem', 'botao_1_mensagem'],
                        ['botao_1_url', 'botao_1_url']
                    ]
                );

                return $list;
            });

            echo "\n" . date('Y-m-d H:i:s') . " - Total de mensagens a serem enviadas: " . count($list);
            $count = count($list);
            foreach ($list as $key => $value) {
                echo "\n [" . $key+1 . " de " . $count . "] Enviando mensagem para o usuario_id: " . $value['usuario_id'] . " - mensagem_id: " . $value['recuperacao_mensagem_id'];
                $usuario = DoubleUsuario::identificarPorId($value['usuario_id']);
                $payloadTelegram = [
                    'token' => $usuario->canal->telegram_token,
                    'sendMessage' => [
                        'chat_id' => $usuario->chat_id,
                        'text' => str_replace(['{usuario}'], [$usuario->nome], $value['mensagem']
                        ),
                        'reply_markup' => $value['botao_1_mensagem'] ? 
                            [
                                'inline_keyboard' => [
                                    [['text' => $value['botao_1_mensagem'],  'url' => $value['botao_1_url']]], 
                                ]
                            ] : [],
                    ]
                ];

                $server_root = DoubleConfiguracao::getConfiguracao('server_root');
                if (!$server_root) 
                {
                    $server_root = $_SERVER['DOCUMENT_ROOT'];
                    DoubleConfiguracao::setConfiguracao('server_root', $server_root);
                }
                
                $imagens = TUtils::openFakeConnection('double', function() use ($value){
                    return DoubleRecuperacaoImagem::where('recuperacao_mensagem_id', '=', $value['recuperacao_mensagem_id'])->getIndexedArray('id', 'imagem');
                });
                foreach ($imagens as $key => $img) {
                    $imagem = $server_root . '/'. $img;
                    $payloadTelegram['sendPhoto'][] = [
                        'chat_id' => $usuario->chat_id,
                        'photo' => $imagem
                    ];
                    // $telegram->sendPhoto($usuario->chat_id, $imagem);
                }
                
                $videos = TUtils::openFakeConnection('double', function() use ($value){
                    return DoubleRecuperacaoVideo::where('recuperacao_mensagem_id', '=', $value['recuperacao_mensagem_id'])->getIndexedArray('id', 'video');
                });
                foreach ($videos as $key => $vid) {
                    $video = $server_root . '/'. $vid;
                    $payloadTelegram['sendVideo'][] = [
                        'chat_id' => $usuario->chat_id,
                        'video' => $video
                    ];
                    // $telegram->sendVideo($usuario->chat_id, $video);
                }

                $jsonPayload = json_encode($payloadTelegram);
                echo "\n" . $jsonPayload;
                self::enviar_fila_cron_mensagens($jsonPayload);
            }

            echo "\n" . date('Y-m-d H:i:s') . " - Finalizando envio de mensagens diretas";
        }
    }

    static private function enviar_fila_cron_mensagens($payload) {
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => DoubleConfiguracao::getConfiguracao('host_usuario'), // IP do seu Redis
            'port'   => 6379, // Porta padrão do Redis
        ]);
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');
        $queue = "{$server_name}_cron_telegram_queue";

        $redis->lpush($queue, $payload);
    }
}