<?php

use Adianti\Database\TTransaction;

class TDoubleCron
{
    public function recuperacao() 
    {
        $list = TUtils::openConnection('double', function(){
            $query = "SELECT *
            FROM (SELECT u.id, 
                         u.chat_id,
                         HOUR(
                             TIMEDIFF(
                                 if(u.data_envio_recuperacao IS null, 
                                 if(u.updated_at IS NULL, u.created_at, u.updated_at), 
                                     u.data_envio_recuperacao
                                 ), 
                                 NOW()
                             )
                         )  * 60 +
                         MINUTE(
                             TIMEDIFF(
                                 if(u.data_envio_recuperacao IS null, 
                                 if(u.updated_at IS NULL, u.created_at, u.updated_at), 
                                     u.data_envio_recuperacao
                                 ), 
                                 NOW()
                             )
                         ) horas,
                         if(u.data_envio_recuperacao IS null, 
                         if(u.updated_at IS NULL, u.created_at, u.updated_at), 
                             u.data_envio_recuperacao
                         ) data, 
                         u.data_envio_recuperacao,
                         u.created_at,
                         u.updated_at,
                         u.status,
                         rm.id recuperacao_mensagem_id,
                         rm.horas * if(rm.tipo_tempo = 'HORA', 60, 1) horas_recuperacao,
                         rm.mensagem, 
                         rm.botao_1_mensagem,
                         rm.botao_1_url,
                         ROW_NUMBER() OVER (PARTITION BY u.id ORDER BY u.id, rm.ordem ASC) AS sequencia
                    FROM double_usuario u
                    JOIN double_recuperacao_mensagem rm on rm.status = u.status AND rm.deleted_at IS null
                    WHERE NOT EXISTS(SELECT 1 FROM double_recuperacao_usuario ru
                                        WHERE ru.usuario_id = u.id
                                        AND ru.recuperacao_mensagem_id = rm.id
                                        AND ru.created_at >= if(u.data_envio_recuperacao IS null, 
                                                                            if(u.updated_at IS NULL, u.created_at, u.updated_at), 
                                                                                u.data_envio_recuperacao
                                                                            )
                                        )
                ) c
           WHERE horas >= horas_recuperacao
             AND sequencia = 1";
            $conn = TTransaction::get();
            $list = TDatabase::getData(
                $conn, 
                $query, 
                [
                    ['id','usuario_id'], 
                    ['recuperacao_mensagem_id','recuperacao_mensagem_id'],
                    ['horas','horas'],
                    ['data','data'],
                    ['status','status'],
                    ['mensagem', 'mensagem'],
                    ['botao_1_mensagem', 'botao_1_mensagem'],
                    ['botao_1_url', 'botao_1_url']
                ]
            );

            return $list;
        });

        $total = 0;
        foreach ($list as $key => $value) {
            TUtils::openConnection('double', function() use ($value){
                $usuario = new DoubleUsuario($value['usuario_id'], false);
                $recuperacao = new DoubleRecuperacaoUsuario();
                $recuperacao->usuario_id = $value['usuario_id'];
                $recuperacao->recuperacao_mensagem_id = $value['recuperacao_mensagem_id'];
                $recuperacao->save();

                $telegram = $usuario->plataforma->telegram;
                $msg = str_replace(
                    ['{usuario}'], 
                    [$usuario->nome], 
                    $value['mensagem']
                );

                $botao = [];
                if ($value['botao_1_mensagem'])
                {
                    $botao = [
                        "resize_keyboard" => true, 
                        "inline_keyboard" => [
                            [["text" => $value['botao_1_mensagem'],  "url" => $value['botao_1_url']]], 
                        ]
                    ];
                }

                $telegram->sendMessage($usuario->chat_id, $msg, $botao);

                if (!$usuario->data_envio_recuperacao) {
                    $usuario->data_envio_recuperacao = date('Y-m-d H:i:s');
                    $usuario->save();
                }

                // $baseUrl = DoubleConfiguracao::getConfiguracao('base_url');
                $server_root = DoubleConfiguracao::getConfiguracao('server_root');
                if (!$server_root) 
                    {
                        $server_root = $_SERVER['DOCUMENT_ROOT'];
                        DoubleConfiguracao::setConfiguracao('server_root', $server_root);
                    }
                
                $imagens = DoubleRecuperacaoImagem::where('recuperacao_mensagem_id', '=', $value['recuperacao_mensagem_id'])->getIndexedArray('id', 'imagem');
                foreach ($imagens as $key => $img) {
                    $imagem = $server_root . '/'. $img;
                    $telegram->sendPhoto($usuario->chat_id, $imagem);
                }
                
                $videos = DoubleRecuperacaoVideo::where('recuperacao_mensagem_id', '=', $value['recuperacao_mensagem_id'])->getIndexedArray('id', 'video');
                foreach ($videos as $key => $vid) {
                    $video = $server_root . '/'. $vid;
                    $telegram->sendVideo($usuario->chat_id, $video);
                }
            });

            $total += 1;
        }


        echo date('Y-m-d H:i:s') . " - Total de mensagens enviadas: " . $total;
    }
}