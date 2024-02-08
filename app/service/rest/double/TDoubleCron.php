<?php

use Adianti\Database\TTransaction;

class TDoubleCron
{
    public function recuperacao() 
    {
        $total = TUtils::openConnection('double', function(){
            $query = "SELECT *
                FROM (SELECT u.id, 
                                HOUR(
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
                                rm.horas horas_recuperacao,
					            rm.mensagem, 
					            ROW_NUMBER() OVER (PARTITION BY u.id ORDER BY u.id, rm.ordem ASC) AS sequencia
                        FROM double_usuario u
                        JOIN double_recuperacao_mensagem rm on rm.status = u.status
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
                ]
            );

            $total = 0;
            foreach ($list as $key => $value) {
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
                $telegram->sendMessage($usuario->chat_id, $msg);
                if (!$usuario->data_envio_recuperacao) {
                    $usuario->data_envio_recuperacao = date('Y-m-d H:i:s');
                    $usuario->save();
                }

                $baseUrl = DoubleConfiguracao::getConfiguracao('base_url');
                $imagens =  DoubleRecuperacaoImagem::where('recuperacao_mensagem_id', '=', $value['recuperacao_mensagem_id'])->getIndexedArray('id', 'imagem');
                foreach ($imagens as $key => $value) {
                    $imagem = $baseUrl . $value;
                    $telegram->sendPhoto($usuario->chat_id, $imagem);
                }

                $total += 1;
            }

            return $total;
        });


        echo "Total de mensagens enviadas: " . $total;
    }
}