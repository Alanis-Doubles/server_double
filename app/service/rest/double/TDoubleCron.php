<?php

use GuzzleHttp\Client as GuzzleClient;
use Adianti\Registry\TSession;
use Adianti\Database\TDatabase;
use Adianti\Database\TTransaction;

class TDoubleCron
{
    public function recuperacao() 
    {
        while (true) 
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
                        JOIN double_recuperacao_mensagem rm on rm.status = u.status AND rm.deleted_at IS null AND mensagem_direta = 'N'
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

            echo "\n" . json_encode($list);

            $teste = TUtils::openConnection('double', function(){
                $query = "SELECT NOW() data";
                $conn = TTransaction::get();
                $list = TDatabase::getData(
                    $conn, 
                    $query
                );

                return $list;
            });

            echo "\n" . json_encode($teste);

            $total = 0;
            foreach ($list as $key => $value) {
                TUtils::openConnection('double', function() use ($value){
                    $usuario = new DoubleUsuario($value['usuario_id'], false);
                    $recuperacao = new DoubleRecuperacaoUsuario();
                    $recuperacao->usuario_id = $value['usuario_id'];
                    $recuperacao->recuperacao_mensagem_id = $value['recuperacao_mensagem_id'];
                    $recuperacao->save();

                    $telegram = $usuario->canal->telegram;
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


            echo "\n" . date('Y-m-d H:i:s') . " - Total de mensagens enviadas: " . $total;

            sleep(30);
        }
    }

    public function enviar_mensagem_direta() 
    {
        $list = TUtils::openConnection('double', function(){
            $query = "SELECT u.id, 
                             u.chat_id,
                             u.status,
                             rm.id recuperacao_mensagem_id,
                             rm.mensagem, 
                             rm.botao_1_mensagem,
                             rm.botao_1_url
                        FROM double_usuario u
                        JOIN double_recuperacao_mensagem rm on rm.status = u.status AND rm.deleted_at IS null
                       WHERE mensagem_direta = 'Y'
                         AND mensagem_direta_enviada = 'N' ";
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

        $ids = [];
        $total = 0;
        foreach ($list as $key => $value) {
            $id = TUtils::openFakeConnection('double', function() use ($value){
                $usuario = new DoubleUsuario($value['usuario_id'], false);

                $telegram = $usuario->canal->telegram;
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

                return $value['recuperacao_mensagem_id'];
            });

            if (!in_array($id, $ids)) {
                $ids[] = $id;
            }
            $total += 1;
        }

        TUtils::openConnection('double', function() use ($ids){
            $conn = TTransaction::get();

            $criteria = new TCriteria;
            $criteria->add(
                new TFilter('id', 'IN', $ids)
            );

            TDatabase::updateData(
                $conn, 
                'double_recuperacao_mensagem', 
                ['mensagem_direta_enviada' => 'Y'],
                $criteria
            );
        });


        // echo date('Y-m-d H:i:s') . " - Total de mensagens enviadas: " . $total;
    }

    public function atualizar_objetivos()
    {
        while (true) 
        {       
            // echo "Script executado em: " . date('Y-m-d H:i:s') . "\n";

            TSession::setValue('unit_database', 'double');
            TSession::setValue('login', 'api');

            // atualiza as execuções
            $lista = TUtils::openFakeConnection('double', function () {
                return DoubleUsuarioObjetivoExecucao::where('status', '=', 'EXECUTANDO')->load();
            });

            ////  DoubleErros::registrar(1, 'TDoubleCron', 'atualizar_objetivos 1', count($lista));
            // echo "Total de execuções ativas: " . count($lista) . "\n";
            foreach ($lista as $execucao) {
                $execucao->atualizar_progresso(true);
            }

            // inicia nova execução
            $lista = TUtils::openFakeConnection('double', function () {
                $sql = "SELECT usuario_objetivo_id,
                            MAX(proxima_execucao) as proxima_execucao
                        FROM double_usuario_objetivo_execucao ue
                        WHERE status = 'FINALIZADO'
                        AND NOT EXISTS(SELECT 1 FROM double_usuario_objetivo_execucao tmp
                                        WHERE tmp.usuario_objetivo_id = ue.usuario_objetivo_id
                                            AND tmp.status = 'EXECUTANDO')
                        GROUP BY usuario_objetivo_id";
                $conn = TTransaction::get();
                return TDatabase::getData(
                    $conn, 
                    $sql
                );

            });

            $data_atual = new DateTime();
            foreach ($lista as $item) {
                $data_proxima_execucao = new DateTime($item['proxima_execucao']);
                if ($data_atual >= $data_proxima_execucao) {
                    TUtils::openFakeConnection('double', function () use($item) {
                        $execucao = DoubleUsuarioObjetivoExecucao::where('status', '=', 'AGUARDANDO')
                            ->where('usuario_objetivo_id', '=', $item['usuario_objetivo_id'])
                            ->first();

                        if (!$execucao)
                            return null;

                        $execucao->status          = 'EXECUTANDO';
                        $execucao->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                        $execucao->save();

                        $objetivo = $execucao->usuario_objetivo;
                        $usuario  = $execucao->usuario;

                        $usuario->valor               = $execucao->valor_entrada;
                        $usuario->protecao            = $objetivo->protecoes;
                        $usuario->stop_win            = $execucao->valor_stop_win;
                        $usuario->stop_loss           = $usuario->protecao + 1;
                        $usuario->tipo_stop_loss      = 'QUANTIDADE';
                        // $usuario->stop_loss           = $execucao->valor_stop_loss;
                        // $usuario->tipo_stop_loss      = 'VALOR';
                        $usuario->modo_treinamento    = $objetivo->modo_treinamento;
                        $usuario->protecao_branco     = $objetivo->protecao_branco;
                        $usuario->ciclo               = 'A';
                        $usuario->entrada_automatica  = 'B';
                        $usuario->valor_max_ciclo     = 0;
                        $usuario->save();

                        $translate = $usuario->plataforma->translate;

                        $botao_inicio = [
                            "resize_keyboard" => true, 
                            "keyboard" => [
                                    [["text" => $translate->BOTAO_CONFIGURAR]],
                                    [["text" => $translate->BOTAO_PARAR_ROBO]], 
                                ] 
                            ];

                        $robo = new TDoubleRobo();
                        $robo->iniciar_apos_loss([
                            'plataforma' => $usuario->plataforma->nome,
                            'idioma' => $usuario->plataforma->idioma,
                            'channel_id' => $usuario->canal->channel_id,
                            'chat_id' => $usuario->chat_id,
                            'nao_reseta_inicio' => 'Y'
                        ]);

                        $telegram = $usuario->canal->telegram;
                        $telegram->sendMessage($usuario->chat_id, 'Execução do objetivo iniciado');
                        $telegram->sendMessage($usuario->chat_id, $usuario->configuracao_texto, $botao_inicio);
                        $telegram->sendMessage(
                            $usuario->chat_id, 
                            str_replace(
                                ['{quantidade}', '{tipo}'],
                                [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
                                $translate->MSG_INICIO_ROBO_9
                            )
                        );
                    });
                }
            }

            sleep(60); // Aguarda 60 segundos antes de executar novamente
        }

        // echo 'ok\n';
    }

    public function verificar_sinais()
    {
        while (true) 
        {       
            // echo "Script executado em: " . date('Y-m-d H:i:s') . "\n";

            TSession::setValue('unit_database', 'double');
            TSession::setValue('login', 'api');

            // atualiza as execuções
            $lista = TUtils::openFakeConnection('double', function () {
                $sql = "SELECT a.created_at,
                               NOW(),
                               TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS minutes_diff
                          FROM double_sinal a
                         ORDER BY a.created_at DESC
                         LIMIT 1";
                $conn = TTransaction::get();
                return TDatabase::getData(
                    $conn, 
                    $sql
                );
            });

            if (is_array($lista) && !empty($lista)) {
                $diff = $lista[0]['minutes_diff'];
                if ($diff > 1) {
                    $client = new GuzzleClient(['http_errors' => false]);
                    $client->request(
                        'GET',
                        "http://localhost:5000/reiniciar/mcgames_bacbo"
                    );
                }
            }

            sleep(60); // Aguarda 60 segundos antes de executar novamente
        }
    }
}