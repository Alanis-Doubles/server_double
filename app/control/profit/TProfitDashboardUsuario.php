<?php

use Predis\Client;
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Container\TPanel;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Wrapper\BootstrapFormBuilder;
use Symfony\Component\Console\Application;

class TProfitNewRanking
{
    use TUIBuilderTrait;
    use TTransformationTrait;

    public $datagrid;
    public $panel;

    public function __construct($title, $top10 = false)
    {
        $dataGrid = new stdClass;
        $dataGrid->name = $top10 ? 'topdataranking' : 'meudataranking';
        $dataGrid->pagenavigator = false;
        $dataGrid->title = $title;
        $dataGrid->disableDefaultClick = true;
        $dataGrid->columns = [
            ['name' => 'usuario_id', 'hide' => true, 'label' => 'Nome', 'width' => '10%', 'align' => 'left'],
            ['name' => 'estrategia_id', 'hide' => true, 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'canal_id', 'hide' => true, 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'nome', 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'regra', 'label' => 'Regra', 'width' => '30%', 'align' => 'left', 'transformer' => Closure::fromCallable(['TProfitDashboard', 'transform_regra'])],
            ['name' => 'resultado', 'label' => 'Resultado', 'width' => '15%', 'align' => 'center', 'transformer' => Closure::fromCallable(['TProfitDashboard', 'transform_resultado'])],
            ['name' => 'protecoes', 'label' => 'Tot. Gale', 'width' => '5%', 'align' => 'center'],
            ['name' => 'protecao_branco', 'label' => 'Empate', 'width' => '5%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'status_sim_nao_transformer'])],
            ['name' => 'win', 'label' => 'Win', 'width' => '5%', 'align' => 'center'],
            ['name' => 'loss', 'label' => 'Loss', 'width' => '5%', 'align' => 'center'],
            ['name' => 'percentual', 'label' => '%', 'width' => '5%', 'align' => 'center'],
            ['name' => 'max_gales', 'label' => 'Max. Gale', 'width' => '5%', 'align' => 'center'],
        ];

        if ($top10) {
            $dataGrid->columns = array_merge(
                [
                    ['name' => 'nome_usuario', 'label' => 'Usuário', 'width' => '20%', 'align' => 'left'],
                ],
                $dataGrid->columns,
            );

            $dataGrid->actions = [
                'actCopiar'  => ['label' => 'Copiar estratégia', 'image' => 'fa:copy', 'fields' => ['usuario_id', '*'], 'action' => ['TProfitDashboardUsuario', 'doCopiarEstrategia'], 'action_params' =>  ['register_state' => 'false']],
            ];
        }

        $this->panel = $this->makeTDataGrid($dataGrid);

        $this->datagrid = $this->getWidget($dataGrid->name);
    }
}

class TProfitDashboardUsuario extends TPage
{
    use TUIBuilderTrait;

    private $form;
    private $filterRanking;
    private $datagrid;
    private $botoes;

    public function __construct($param = null)
    {
        parent::__construct();

        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        if (!$usuario) {
            new TMessage('error', _t('Usuário não encontrado.'));
            return;
        }

        if ($usuario->robo_status == 'EXECUTANDO') {
            $configuracao = new TPanelGroup('<div id="robo-status"><span class="cor_verde">' . _t('Robô em execução') . '</span></div>');
        } else {
            $configuracao = new TPanelGroup('<div id="robo-status"><span class="cor_vermelho">' . _t('Robô parado') . '</span></div>');
        }

        $action_config = $configuracao->addHeaderActionLink('', new TAction(['TProfitConfiguracaoUsuario', "onEdit"], ['register_state' => 'false', 'key' => $usuario->id, 'fromClass' => 'TProfitDashboardUsuario']), 'fa:cog');
        $action_config->style = 'width: 50px;';
        $action_config->{'title'} = _t('Alterar configurações');
        $action_config->{'data-toggle'} = 'tooltip';
        $action_config->{'data-placement'} = 'top';
        $action_config->{'id'} = 'btn_config';

        $action_iniciar = $configuracao->addHeaderActionLink('', new TAction([$this, "onIniciarRobo"], ['register_state' => 'false']), 'fa:play');
        $action_iniciar->style = 'width: 50px;';
        $action_iniciar->{"class"} = $action_iniciar->{"class"} . ' cor_verde';
        $action_iniciar->{'title'} = _t('Iniciar robô');
        $action_iniciar->{'data-toggle'} = 'tooltip';
        $action_iniciar->{'data-placement'} = 'top';
        $action_iniciar->{'id'} = 'btn_iniciar';

        $action_parar = $configuracao->addHeaderActionLink('', new TAction([$this, "questionaParar"], ['register_state' => 'false']), 'fa:pause');
        $action_parar->style = 'width: 50px;';
        $action_parar->{"class"} = $action_parar->{"class"} . ' cor_vermelho';
        $action_parar->{'title'} = _t('Parar robô');
        $action_parar->{'data-toggle'} = 'tooltip';
        $action_parar->{'data-placement'} = 'top';
        $action_parar->{'id'} = 'btn_parar';

        if ($usuario->robo_status == 'EXECUTANDO') {
            $action_iniciar->style .= 'display: none;';
            $action_config->style .= 'display: none;';
        } else {
            $action_parar->style .= 'display: none;';
        }

        $configuracao->{'style'} = 'width: 100%;height: 95%; margin: 0px; margin-top: 0px; min-height: 50px;';

        $table = new TTable();
        $table->{'style'} = 'width: 100%;';

        $valores = [
            '5' => '5 ' . _t('segundos'),
            '10' => '10 ' . _t('segundos'),
            '15' => '15 ' . _t('segundos'),
            '30' => '30 ' . _t('segundos'),
            '45' => '45 ' . _t('segundos'),
            '60' => '1 ' . _t('minuto'),
            '120' => '2 ' . _t('minuto'),
            '180' => '3 ' . _t('minuto'),
            '300' => '5 ' . _t('minuto')
        ];

        $tempo_expiracao = $valores[$usuario->expiration];

        if ($usuario->tipo_stop_loss == 'VALOR') {
            $stop_loss = number_format($usuario->stop_loss, 2, ',', '.') . ' [Valor]';
        } else {
            $stop_loss = $usuario->stop_loss  . ' [' . _t('Quantidade') . ']';
        }

        if ($usuario->ciclo == 'N') {
            $ciclo = '<span class="cor_vermelho" id="ciclo">' . _t('Desabilitado') .'</span>';
        } else {
            $ciclo = '<span class="cor_verde" id="ciclo">' . _t('Habilitado') .' </span>';
        }

        $table->addRowSet('<b>💸 ' . _t('Valor operação') . ':</b>', '<span id="valor_aposta">' . number_format($usuario->valor, 2, ',', '.') . '</span>');
        $table->addRowSet('<b>🔒 ' . _t('Proteções') . ':</b>', '<span id="gales">' . $usuario->protecao . '</span>');
        $table->addRowSet('<b>⏰ ' . _t('Tempo expiração') . ':</b>', '<span id="tempo_expiracao">' . $tempo_expiracao . '</span>');
        $table->addRowSet('<b>🔎 ' . _t('Classificação') . ':</b>', '<span id="classificacao">' . $usuario->classificacao . '</span>');
        $table->addRowSet('<b>♻ ' . _t('Fator multiplicador') . ':</b>', '<span id="fator_multiplicador">' . number_format($usuario->fator_multiplicador, 2, ',', '.') . '</span>');
        $table->addRowSet('<b>✅ Stop WIN:</b>', '<span id="stop_win">' . number_format($usuario->stop_win, 2, ',', '.') . '</span>');
        $table->addRowSet('<b>❌ Stop LOSS:</b>', '<span id="stop_loss">' . $stop_loss  . '</span>');
        $table->addRowSet('<b>↪️ ' . _t('Ciclo') . ':</b>', $ciclo);


        $configuracao->add($table);

        $table = new TTable();
        $table->{'style'} = 'width: 100%; text-align: left;';
        $table->id = 'ativos';

        $header = $table->addRow();
        $header->addCell('<b>' . _t('Ativo') . '</b>');
        $header->addCell('<b>Win</b>');
        $header->addCell('<b>Loss</b>');

        $divWrapper = new TElement('div');
        $divWrapper->{'style'} = 'height: 330px; overflow-y: auto;';
        $divWrapper->add($table);

        $ativosPanel = new TPanelGroup(_t("Histórico de Ativos"));
        $ativosPanel->{'style'} = 'width: 100%; height: 420px; margin: 0px; margin-bottom: 15px; min-height: 50px;';
        $ativosPanel->add($divWrapper);


        $labelAtivo = new TLabel(_t('Gráfico do Ativo'). ': <b>' . _t('Aguardando ativo...') . '</b>');
        $labelAtivo->setId('labelAtivo');

        $step = new TArrowStep('step');
        $step->addItem(_t('Operação'), 99, '#3498db');
        $step->addItem(_t('Entrada'), 0, '#27ae60');
        $step->setCurrentKey(99);
        $step->setHeight(40);

        $apex = new TElement('div');
        $apex->id = 'candlestick_chart';
        $apex->style = 'width: 800px; height: 90px;';

        $panelGrafio = new TPanelGroup();
        $panelGrafio->{'style'} = 'width: 100%; height: 420px;overflow-x:auto;';
        $panelGrafio->add($labelAtivo);
        $panelGrafio->add($step);
        $panelGrafio->add(new TElement('br'));
        $panelGrafio->add($apex);


        $body = new THtmlRenderer('app/resources/double/profit-dashboard-usuario.html');
        $body->enableSection(
            'main',
            [
                'configuracao' => $configuracao,
                'indicator1'   => TUtils::renderInfoBox('total-win', 'WIN', 'trophy', 'green', 0),
                'indicator2'   => TUtils::renderInfoBox('total-loss', 'LOSS', 'times', 'red', 0),
                'indicator3'   => TUtils::renderInfoBox('total-lucro', _t('Lucro/Perda'), 'dollar-sign', 'green', '$ 0,00'),
                'indicator4'   => TUtils::renderInfoBox('total-saldo', _t('Saldo Atual'), 'money-bill-alt', 'green', '$ 0,00'),
                'indicator5'   => TUtils::renderInfoBox('maior-entrada', _t('Maior Entrada'), 'arrow-alt-circle-up', 'green', '$ 0,00'),
                'indicator6'   => TUtils::renderInfoBox('assertividade', _t('Assertividade'), 'percent', 'green', '0 %'),
                'ativos'       => $ativosPanel,
                'grafico'      => $panelGrafio,

                // 'status_robo' => $container_status,
                // 'modo'        => $container_modo,
                // 'sinais'      => $container,
                // 'meuRanking'  => $meuRanking->panel,
                // 'topRanking'  => $topRanking->panel,
                // 'historico'   => $historico,
            ]
        );

        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(TUtils::createXMLBreadCrumb('menu-top.xml', __CLASS__));
        $container->add($body);

        parent::add($container);
        TScript::create($this->getProfitJavaScript());

        // $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        // if ($use_redis == 'Y') {
        //     TScript::create($this->getJavaScriptRedisWS());
        // }
        // else
        //     TScript::create($this->getJavaScript());

    }

    public function questionaSalvar($param)
    {
        $message = _t('Tem certeza que deseja salvar a configuração?');
        $action = new TAction([$this, 'onSave'], $param);

        new TQuestion($message, $action);
    }

    public function questionaParar($param)
    {
        $message = _t('Tem certeza que deseja parar a execução?');
        $action = new TAction([$this, 'onPararRobo'], $param);

        new TQuestion($message, $action);
    }

    public function doCopiarEstrategia($param)
    {

        try {
            TUtils::openConnection('double', function () use ($param) {
                $chat_id = TSession::getValue('usercustomcode');
                $canal_id = $param['canal_id'];
                $usuario = DoubleUsuario::where('canal_id', '=', $canal_id)
                    ->where('chat_id', '=', $chat_id)
                    ->where('deleted_at', 'is', null)
                    ->first();

                $estrategia =  DoubleEstrategia::where('regra', '=', $param['regra'])
                    ->where('usuario_id', '=', $usuario->id)
                    ->first();

                if ($estrategia)
                    throw new Exception((string)_t("Você já possui esta estratégia na sua lista"));

                $max = DoubleEstrategia::where('usuario_id', '=', $usuario->id)
                    ->where('canal_id', '=', $canal_id)
                    ->maxBy('ordem', 'max_ordem');

                $new = new DoubleEstrategia();
                $new->usuario_id      = $usuario->id;
                $new->canal_id        = $canal_id;
                $new->nome            = $param['nome'];
                $new->regra           = $param['regra'];
                $new->resultado       = $param['resultado'];
                $new->protecoes       = $param['protecoes'];
                $new->protecao_branco = $param['protecao_branco'];
                $new->ordem           = $max + 1;
                $new->save();
            });

            new TMessage('info', _t('Estratégia copiada com sucesso.'));
        } catch (\Throwable $th) {
            new TMessage('error', $th->getMessage());
        }

        TScript::create('atualiza_configuracao()', TRUE, 1000);
    }

    public function serilizeAction($action, $param)
    {
        $action = new TAction($action, $param);
        $url = $action->serialize(FALSE, TRUE);
        $wait_message = AdiantiCoreTranslator::translate('Loading');
        $action = "Adianti.waitMessage = '$wait_message';";
        $action .= "__adianti_post_data('form_TProfitDashboardUsuario', '{$url}');";
        $action .= "return false;";

        return $action;
    }

    public function onIniciarRobo($param)
    {
        try {
            $canal = TUtils::openFakeConnection("double", function () {
                return DoubleCanal::where('nome', '=', 'Playbroker')
                    ->first();
            });
    
            $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
                return DoubleUsuario::where('canal_id', '=', $canal->id)
                    ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                    ->first();
            });
    
            if (!$usuario)
                return;
    
            $translate = $usuario->plataforma->translate;
    
            $valor_minimo = $usuario->plataforma->valor_minimo;
            if ($usuario->valor < $valor_minimo) {
                new TMessage(
                    'error',
                    str_replace(
                        ['{valor}'],
                        [$valor_minimo],
                        $translate->MSG_INICIO_ROBO_2
                    )
                );
                return;
            }
    
            $usuario->ultimo_saldo = $usuario->plataforma->service->saldo($usuario);
            if ($usuario->ultimo_saldo < $valor_minimo) {
                new TMessage(
                    'error',
                    str_replace(
                        ['{valor}'],
                        [$valor_minimo],
                        $translate->MSG_INICIO_ROBO_3
                    )
                );
                return;
            }
    
            $telegram = $usuario->canal->telegram;
    
            if (in_array($usuario->status, ['ATIVO', 'DEMO'])) {
                $telegram->sendMessage($usuario->chat_id, _t('Robô iniciado no Dashboard'));
    
                $telegram->sendMessage(
                    $usuario->chat_id,
                    str_replace(
                        ['{dia_expiracao}'],
                        [date('d/m/Y', strtotime($usuario->data_expiracao))],
                        $translate->MSG_INICIO_ROBO_4
                    )
                );
            } else {
                new TMessage(
                    'error',
                    $translate->MSG_INICIO_ROBO_5
                );
                return;
            }
    
            $robo = new TDoubleRobo();
            // if ($param['apos_loss'] == 0)
                $robo->iniciar([
                    'plataforma' => $usuario->plataforma->nome,
                    'idioma' => $usuario->plataforma->idioma,
                    'channel_id' => $usuario->canal->channel_id,
                    'chat_id' => $usuario->chat_id
                ]);
            // else
            //     $robo->iniciar_apos_loss([
            //         'plataforma' => $usuario->plataforma->nome,
            //         'idioma' => $usuario->plataforma->idioma,
            //         'channel_id' => $usuario->canal->channel_id,
            //         'chat_id' => $usuario->chat_id
            //     ]);
    
            $botao_inicio = [
                "resize_keyboard" => true,
                "keyboard" => [
                    [["text" => $translate->BOTAO_CONFIGURAR]],
                    [["text" => $translate->BOTAO_PARAR_ROBO]],
                ]
            ];
    
            $telegram->sendMessage($usuario->chat_id, $usuario->configuracao_texto, $botao_inicio);
            // if ($param['apos_loss'] == 1)
            //     $telegram->sendMessage(
            //         $usuario->chat_id,
            //         str_replace(
            //             ['{quantidade}', '{tipo}'],
            //             [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
            //             $translate->MSG_INICIO_ROBO_9
            //         )
            //     );
    
            // TForm::sendData('form_TProfitDashboardUsuario', $arrData, FALSE, TRUE, 1000);
    
            TScript::create('atualiza_status()', TRUE, 5000); // 5 segundos
    
            new TMessage('info', _t('Robô iniciado com sucesso.'));
        } catch (\Throwable $th) {
            new TMessage('error', _t('Erro ao iniciar o robô.'));
        }
    }

    public function onPararRobo($param)
    {
        try {
            $canal = TUtils::openFakeConnection("double", function () {
                return DoubleCanal::where('nome', '=', 'Playbroker')
                    ->first();
            });
    
            $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
                return DoubleUsuario::where('canal_id', '=', $canal->id)
                    ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                    ->first();
            });
    
            $robo = new TDoubleRobo();
            $robo->parar([
                'plataforma' => $usuario->plataforma->nome,
                'idioma' => $usuario->plataforma->idioma,
                'channel_id' => $usuario->canal->channel_id,
                'chat_id' => $usuario->chat_id,
            ]);
    
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
                ]
            ];
    
            $telegram = $usuario->canal->telegram;
            $telegram->sendMessage($usuario->chat_id, _t('Robô parado no Dashboard'));
            $telegram->sendMessage($usuario->chat_id, $usuario->plataforma->translate->MSG_PARAR_ROBO, $botao_inicio);
    
            // TForm::sendData('form_TProfitDashboardUsuario', $arrData, FALSE, TRUE, 1000);
    
            TScript::create('atualiza_status()', TRUE, 5000); // 5 segundos
    
            new TMessage('info', _t('Robô parado com sucesso.'));
        } catch (\Throwable $th) {
            new TMessage('error', _t('Erro ao parar o robô.'));
        }
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('double');

            $object = $this->form->getData();

            $chat_id = TSession::getValue('usercustomcode');
            $usuario = DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();

            $usuario->valor                         = $object->valor_entrada;
            $usuario->protecao                      = $object->gale;
            $usuario->stop_win                      = $object->stop_win;
            $usuario->stop_loss                     = $object->stop_loss;
            $usuario->tipo_stop_loss                = $object->tipo_stop_loss;
            $usuario->modo_treinamento              = $object->modo_treinamento;
            $usuario->banca_treinamento             = $object->banca_treinamento;
            // $usuario->protecao_branco               = $object->protecao_branco;
            $usuario->entrada_automatica_tipo       = $object->tipo_espera;
            $usuario->entrada_automatica_total_loss = $object->quantidade_espera;
            $usuario->valor_max_ciclo               = $object->valor_max_ciclo;

            $ciclo = $object->usa_ciclo;
            $ciclo_stop_loss = $object->ciclo_stop_loss;
            if ($ciclo !== 'N' && $ciclo_stop_loss)
                $ciclo = $ciclo_stop_loss;

            $entrada_automatica = $object->entrada_automatica;
            $tipo_entrada_automatica =  $object->tipo_entrada_automatica;
            if ($entrada_automatica !== 'N' && $tipo_entrada_automatica)
                $entrada_automatica = $tipo_entrada_automatica;

            if ($entrada_automatica == 'N')
                $usuario->valor_max_ciclo = 0;

            $usuario->ciclo              = $ciclo;
            $usuario->entrada_automatica = $entrada_automatica;

            $usuario->save();

            TTransaction::close();

            new TMessage('info', _t('Configuração salva com sucesso.'));
        } catch (\Throwable $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        } finally {
            // TSession::setValue('form_TProfitDashboard_data', $object);
            $this->form->setData($object);
        }
    }

    private function getProfitJavaScript()
    {
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        $host = DoubleConfiguracao::getConfiguracao('servidor_ws');

        return <<<JAVASCRIPT
            atualizando = false;
            ordem_realizada = 0;

           options = {
                series: [{
                    data: []
                }],
                chart: {
                    type: 'candlestick',
                    height: 285,
                    animations: {
                        enabled: false
                    },
                    zoom: { enabled: false },  
                },
                xaxis: {
                    type: 'string'
                },
                yaxis: {
                    tooltip: {
                        enabled: true
                    }
                },
                annotations: {
                    yaxis: []
                }
            };

            chart = new ApexCharts(document.querySelector('#candlestick_chart') ?? '', options);
            chart.render();

            socket = new WebSocket(`{$host}?id_usuario={$usuario->id}`);

            socket.onopen = function(event) {
                console.log('Conexão WebSocket estabelecida.');
            };

            socket.onmessage = function (event) {
                let data = JSON.parse(event.data);

                if (data.type === "candle_update") {
                    atualizarNomeAtivo(data.moeda, data.horaEntrada);

                    atualizarGrafico(data);
                    if (atualizando) {
                        tarrowstep_set_current('step', data.gale);
                    }
                } else if (data.type === 'ordem_realizada') {
                    ordem_realizada = data.entrada_usuario;
                    tarrowstep_set_current('step', data.gale);
                } else if (data.type === "win") {
                    atualizando = false;
                    setTimeout(() => {
                        atualiza_status();
                    }, 10000);
                    __adianti_show_toast('success', 'Win', 'top right', 'far:check-circle');
                    addStep("WIN ", "#27ae60");
                } else if (data.type === "loss") {
                    atualizando = false;
                    setTimeout(() => {
                        atualiza_status();
                    }, 10000);
                    __adianti_show_toast('error', 'Loss', 'top right', 'far:times-circle');
                    addStep("LOSS ", "#e74c3c");
                } else if (data.type === "gale") {
                    addStep("Proteção " + data.gale, getGradientColor(data.gale, {$usuario->protecao}))
                } else if (data.type === "indisponivel") {
                    document.getElementById('step_' + data.gale).innerHTML = "🚫 " + document.getElementById('step_' + data.gale).innerHTML;
                } else if (data.type === "entrada") {
                    ordem_realizada = 0;
                    atualizarNomeAtivo(data.moeda, data.hora);
                    tarrowstep_set_current('step', 99);
                    clearSteps();
                    document.getElementById('step_0').innerHTML = "Entrada" ;
                    atualizando = true;
                    atualiza_status();
                }
            };

            function addStep(stepName, stepColor) {
                let stepsContainer = document.querySelector(".arrow_steps");
                if (!stepsContainer) return;

                let lastStep = stepsContainer.querySelector(".step:last-child");
                let lastKey = lastStep ? parseInt(lastStep.getAttribute("data-key")) : -1;
                let newKey = lastKey + 1;

                let newStep = document.createElement("div");
                newStep.classList.add("step");
                newStep.setAttribute("data-key", newKey);

                let newInput = document.createElement("input");
                newInput.type = "hidden";
                newInput.id = "tarrowstep_1855478791_" + newKey;
                newInput.value = newKey;

                let newSpan = document.createElement("span");
                newSpan.id = "step_" + newKey;
                newSpan.textContent = stepName;

                newStep.appendChild(newInput);
                newStep.appendChild(newSpan);

                stepsContainer.appendChild(newStep);

                addStepStyle(newKey, stepColor);
                
                tarrowstep_set_current('step', newKey);
            }

            function addStepStyle(stepKey, stepColor) {
                let styleSheet = document.querySelector("#dynamicStyles");
                
                if (!styleSheet) {
                    styleSheet = document.createElement("style");
                    styleSheet.id = "dynamicStyles";
                    document.head.appendChild(styleSheet);
                }

                styleSheet.innerHTML += 
                    '.arrow_steps_step .step.current[data-key="' + stepKey + '"], ' +
                    '.arrow_steps_step .step.preview-current[data-key="' + stepKey + '"] { ' +
                    '    background-color: ' + stepColor + '; ' +
                    '} ' +
                    '.arrow_steps_step .step.current[data-key="' + stepKey + '"]:after, ' +
                    '.arrow_steps_step .step.preview-current[data-key="' + stepKey + '"]:after { ' +
                    '    border-left-color: ' + stepColor + '; ' +
                    '} ' ;
            }

            function getGradientColor(step, totalSteps = 10) {
                let startColor = [241, 196, 15]; 
                let endColor = [230, 126, 34];    
                
                
                let ratio = Math.min(step / totalSteps, 1); 

                
                let r = Math.round(startColor[0] + ratio * (endColor[0] - startColor[0]));
                let g = Math.round(startColor[1] + ratio * (endColor[1] - startColor[1]));
                let b = Math.round(startColor[2] + ratio * (endColor[2] - startColor[2]));

                const cor = "#" + r.toString(16).padStart(2, "0") 
                                + g.toString(16).padStart(2, "0") 
                                + b.toString(16).padStart(2, "0");

                return cor;
            }

            function clearSteps() {
                let stepsContainer = document.querySelector(".arrow_steps");
                if (!stepsContainer) return;

                let steps = stepsContainer.querySelectorAll(".step");

                steps.forEach((step, index) => {
                    if (index > 1) {
                        step.remove();
                    }
                });
            }

            function atualizarNomeAtivo(nome, hora) {
                document.getElementById('labelAtivo').innerHTML = _t("Gráfico do Ativo") + ": <b>" + nome + "</b> - " + _t("Entrada às") + " + hora;
            }

            function atualizarGrafico(message) {
                document.getElementById('step_99').innerHTML = message.acao;
                data = message.historico.map(vela => ({
                    x: vela.datahora, 
                    y: [vela.open, vela.high, vela.low, vela.close]
                }));

                entradaUsuario = null; 
                chart.updateOptions({ annotations: { yaxis: [] } }); 

                chart.updateSeries([{ data }]);

                ultimoClose = data[data.length - 1].y[3];
                ultinoOpen = data[data.length - 1].y[0];

                let _updateOptions = [];
                let _points = [];
                let galeMax = message.gale; 
                let labels = ["Entrada", "Proteção 1", "Proteção 2", "Proteção 3"];
                let cores = ["#27ae60", "#f1c40f", "#e67e22", "#e74c3c"];
                
                if (message.entrada) {
                    _updateOptions.push({
                        y: ultimoClose,
                        borderColor: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000',
                        label: {
                            text: ultimoClose,
                            position: 'top', 
                            offsetX: 150,
                            style: { color: '#fff', background: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000' }
                        }
                    });

                    if (ordem_realizada < 0) {
                        _updateOptions.push({
                            y: ordem_realizada,
                            borderColor: '#3498db',
                            label: {
                                text: 'Entrada realizada',
                                position: 'top', 
                                offsetX: 50,
                                style: { color: '#fff', background: '#3498db' }
                            }
                        });
                    }

                    let maxValue = Math.max(...data.map(d => d.y[1])); 
                    for (let i = 0; i < message.historico.length; i++) {
                        let vela = message.historico[i];
                        if (vela.gale != 99) {
                            const corGale = getGradientColor(vela.gale, {$usuario->protecao});
                            _points.push({
                                x: vela.datahora, 
                                y: maxValue, 
                                yAxisIndex: 0, 
                                marker: { size: 0 }, 
                                label: {
                                    text: labels[vela.gale],
                                    position: "top", 
                                    offsetY: -10, 
                                    style: {
                                        color: '#fff',
                                        background: (vela.gale === 0 ? '#28a745' : corGale),  
                                        fontSize: '12px',
                                        fontWeight: 'bold'
                                    }
                                }
                            });
                        }
                    }
                }
                
                chart.updateOptions({
                    annotations: {
                        yaxis: _updateOptions,
                        points: _points
                    }
                });
            }

            function atualiza_status() {
                __adianti_ajax_exec('class=TProfitDashboardUsuario&method=statusJs', function(data) {
                    var data = JSON.parse(data);

                    document.querySelector("#total-win").textContent = data.total_win;
                    document.querySelector("#total-loss").textContent = data.total_loss;
                    document.querySelector("#total-lucro").textContent = '$ ' + data.lucro_prejuizo;
                    document.querySelector("#total-saldo").textContent = '$ ' + data.saldo;
                    document.querySelector("#maior-entrada").textContent = '$ ' + data.maior_entrada;
                    document.querySelector("#assertividade").textContent = data.assertividade + ' %';

                    var executando = data.status_objetivo == 'EXECUTANDO' || data.robo_status == 'EXECUTANDO';
                        
                    if (executando) {
                        document.querySelector("#robo-status").innerHTML = '<span class="cor_verde">_t("Robô em execução")</span>';
                        document.querySelector('#btn_config').style.display = 'none';
                        document.querySelector('#btn_iniciar').style.display = 'none';
                        document.querySelector('#btn_parar').style.display = 'inline';
                    }
                    else {
                        document.querySelector("#robo-status").innerHTML = '<span class="cor_vermelho">_t("Robô parado")</span>';
                        document.querySelector('#btn_config').style.display = 'inline';
                        document.querySelector('#btn_iniciar').style.display = 'inline';
                        document.querySelector('#btn_parar').style.display = 'none';
                    }
                }, false);

                atualiza_historico_ativos();

                const tema = document.documentElement.getAttribute("data-bs-theme");
                atualizarTemaGrafico(tema);
            }

            function atualiza_historico_ativos() {
                __adianti_ajax_exec('class=TProfitDashboardUsuario&method=historicoAtivosJs', function(data) {
                    var data = JSON.parse(data);
                    let table = document.querySelector("#ativos tbody");
                    if (!table) return;

                    let rows = table.querySelectorAll("tr");
                    rows.forEach((row, index) => {
                        if (index > 0) row.remove(); 
                    });

                    data.forEach(entry => {
                        let { ticker, moeda, total_win, total_loss } = entry;
                       
                        let newRow = document.createElement("tr");

                        let ativoCell = document.createElement("td");
                        ativoCell.textContent = moeda;

                        let winCell = document.createElement("td");
                        winCell.textContent = total_win;

                        let lossCell = document.createElement("td");
                        lossCell.textContent = total_loss;

                        newRow.appendChild(ativoCell);
                        newRow.appendChild(winCell);
                        newRow.appendChild(lossCell);

                        table.appendChild(newRow);
                    });
                }, false);
            }

            function atualizarTemaGrafico(tema) {
                const novasCores = tema === "dark" 
                    ? { text: "#fff", linha: "#777" } 
                    : { text: "#333", linha: "#ccc" }; 

                chart.updateOptions({
                    xaxis: {
                        labels: { style: { colors: novasCores.text } },
                        axisBorder: { color: novasCores.linha },
                        axisTicks: { color: novasCores.linha }
                    },
                    yaxis: {
                        labels: { style: { colors: novasCores.text } },
                        axisBorder: { color: novasCores.linha },
                        axisTicks: { color: novasCores.linha }
                    }
                });
            }

            originalToggleGlobalTheme = Template.toggleGlobalTheme;

            Template.toggleGlobalTheme = function (el) {
                originalToggleGlobalTheme.call(this, el);

                const tema = document.documentElement.getAttribute("data-bs-theme");

                atualizarTemaGrafico(tema);
            };


            atualiza_status();
    JAVASCRIPT;
    }

    public static function onAddOption($param, $bet_name)
    {
        $path = 'app/images/regras/';
        $path_bet = "app/images/regras/{$bet_name}/";

        //  DoubleErros::registrar('1', 'Dashusu', 'add', $bet_name);
        $imageMap = [
            'red'   => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'),
            'black' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'),
            'white' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'),
            'other' => (file_exists($path_bet . 'other.png') ? $path_bet . 'other.png' : $path . 'other.png'),
            'break' => (file_exists($path_bet . 'break.png') ? $path_bet . 'break.png' : $path . 'break.png'),
            '0'     => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'),
            '1'     => (file_exists($path_bet . '1.png') ? $path_bet . '1.png' : $path . '1.png'),
            '2'     => (file_exists($path_bet . '2.png') ? $path_bet . '2.png' : $path . '2.png'),
            '3'     => (file_exists($path_bet . '3.png') ? $path_bet . '3.png' : $path . '3.png'),
            '4'     => (file_exists($path_bet . '4.png') ? $path_bet . '4.png' : $path . '4.png'),
            '5'     => (file_exists($path_bet . '5.png') ? $path_bet . '5.png' : $path . '5.png'),
            '6'     => (file_exists($path_bet . '6.png') ? $path_bet . '6.png' : $path . '6.png'),
            '7'     => (file_exists($path_bet . '7.png') ? $path_bet . '7.png' : $path . '7.png'),
            '8'     => (file_exists($path_bet . '8.png') ? $path_bet . '8.png' : $path . '8.png'),
            '9'     => (file_exists($path_bet . '9.png') ? $path_bet . '9.png' : $path . '9.png'),
            '10'    => (file_exists($path_bet . '10.png') ? $path_bet . '10.png' : $path . '10.png'),
            '11'    => (file_exists($path_bet . '11.png') ? $path_bet . '11.png' : $path . '11.png'),
            '12'    => (file_exists($path_bet . '12.png') ? $path_bet . '12.png' : $path . '12.png'),
            '13'    => (file_exists($path_bet . '13.png') ? $path_bet . '13.png' : $path . '13.png'),
            '14'    => (file_exists($path_bet . '14.png') ? $path_bet . '14.png' : $path . '14.png'),
        ];


        if (isset($imageMap[$param])) {
            $imgTag = new TElement('img');
            $imgTag->src = $imageMap[$param];
            $imgTag->style = 'width: 35px; height: 35px; margin: 2px;';

            return $imgTag;
        }
    }

    public static function sinaisJs($param)
    {
        $canal = TUtils::openFakeConnection('double', function () use ($param) {
            return new DoubleCanal($param['canal_id'], false);
        });

        $bet_name = $canal->plataforma->nome;
        $take = $param['take'] ?? 16;

        $sinais = TUtils::openFakeConnection('double', function () use ($canal, $take) {
            $sinais = DoubleSinal::where('plataforma_id', '=', $canal->plataforma_id)
                ->orderBy('id', 'desc')
                ->take($take)
                ->load();

            if ($sinais) {
                $arr = [];
                foreach ($sinais as $sinal) {
                    $arr[] = $sinal->numero;
                }
                return array_reverse($arr);
            } else
                return [];
        });

        $option = '';
        foreach ($sinais as $sinal) {
            $object = self::onAddOption($sinal, $bet_name);
            if ($object)
                $option .= $object->getContents();
        }

        // echo $option;
        return $option;
    }

    public static function getHistoricoJs($param)
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param) {
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $param['chat_id'])
                ->first();
        });

        // echo TDashboardUsuarioService::getHistorico($usuario->id, $param['ultimo_id']);
        return TDashboardUsuarioService::getHistorico($usuario->id, $param['ultimo_id']);
    }

    public static function statusJs($param)
    {
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        $retorno = TDashboardUsuarioService::getStatusUsuario($usuario);
        echo $retorno;
    }

    public static function historicoativosJs($param)
    {
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        $retorno = TDashboardUsuarioService::getHistoricoAtivos($usuario);
        echo $retorno;
    }

    public static function usuarioJS($param)
    {
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        $nao_mostra_treinamento = DoubleConfiguracao::getConfiguracao('nao_mostra_treinamento');
        if (in_array($usuario->chat_id, explode(',', $nao_mostra_treinamento)))
            $usuario->banca_treinamento = 0.00;

        echo json_encode($usuario->toArray());
    }

    public static function topRankingJS($param)
    {
        $lista = TDashboardUsuarioService::getRanking($param['canal_id']);

        $raking = new TProfitNewRanking('', True);
        $datagrid = $raking->datagrid;

        foreach ($lista as $key => $value) {
            $datagrid->addItem((object) $value);
        }
        // echo $datagrid->getBody();
        return $datagrid->getBody()->getContents();
    }

    public static function meuRankingJS($param)
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param) {
            // $chat_id = TSession::getValue('usercustomcode');
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $param['chat_id'])
                ->first();
        });
        $lista = TDashboardUsuarioService::getRanking($param['canal_id'], $usuario->id);

        $raking = new TProfitNewRanking('');
        $datagrid = $raking->datagrid;

        foreach ($lista as $key => $value) {
            $datagrid->addItem((object) $value);
        }
        // echo $datagrid->getBody();
        return $datagrid->getBody()->getContents();
    }

    public function onReload($param) {}
}
