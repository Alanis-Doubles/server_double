<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Util\TDropDown;
use Adianti\Wrapper\BootstrapFormBuilder;
use Symfony\Component\Console\Application;

class TNewRanking 
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
        $dataGrid->columns = [
            ['name' => 'usuario_id', 'hide' => true, 'label' => 'Nome', 'width' => '10%', 'align' => 'left'],
            ['name' => 'estrategia_id', 'hide' => true, 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'canal_id', 'hide' => true, 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'nome', 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'regra', 'label' => 'Regra', 'width' => '30%', 'align' => 'left', 'transformer' => Closure::fromCallable(['TDoubleDashboard', 'transform_regra'])],
            ['name' => 'resultado', 'label' => 'Resultado', 'width' => '15%', 'align' => 'center', 'transformer' => Closure::fromCallable(['TDoubleDashboard', 'transform_resultado'])],
            ['name' => 'protecoes', 'label' => 'Tot. Gale', 'width' => '5%', 'align' => 'center'],
            ['name' => 'protecao_branco', 'label' => 'Branco', 'width' => '5%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'status_sim_nao_transformer'])],
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
                'actCopiar'  => ['label' => 'Copiar estratégia', 'image' => 'fa:copy', 'fields' => ['usuario_id', '*'], 'action' => ['TDoubleDashboardUsuario', 'doCopiarEstrategia'], 'action_params' =>  ['register_state' => 'false']],
            ];
        }

        $this->panel = $this->makeTDataGrid($dataGrid);

        $this->datagrid = $this->getWidget($dataGrid->name);
    }
}

class TDoubleDashboardUsuario extends TPage
{
    use TUIBuilderTrait;

    private $form;
    private $filterRanking;
    private $datagrid;
    private $botoes;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_TDoubleDashboardUsuario');
        $this->form->setFormTitle('Configurações');
        if ($this->isMobile())
            $this->form->addExpandButton('', 'fa:cogs');

        $this->form->addFields(
            [$this->makeTHidden(['name' => 'ultimo_id', 'value' => '0'])],
        );

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                'IN',
                ['NAO_GERA', 'GERA', 'PROPAGA_VALIDA_SINAL']
            )
        );

        $criteria->add(
            new TFilter(
                '(SELECT u.chat_id FROM double_usuario u WHERE u.canal_id = double_canal.id and u.chat_id = ' . TSession::getValue('usercustomcode') . ')',
                '=',
                TSession::getValue('usercustomcode')
            )
        );
        
        $criteria->add(  new TFilter( 'ativo', '=', 'Y') );

        $this->form->addFields([
            $label = $this->makeTLabel(['value' => 'Canal']),
            $this->makeTDBCombo(
                [
                    'name' => 'canal_id', 
                    'label' => $label, 
                    'database' => 'double', 
                    'required' => True,
                    'model' => 'DoubleCanal', 
                    'key' => 'id', 
                    'display' => '[{plataforma->idioma}] {plataforma->nome} - {nome}',
                    'defaultOption' => false,
                    'width' => '100%',
                    'criteria' => $criteria
                ],
                function ($object){
                    $object->setChangeFunction('doChangeCanal()');
                }
            )
        ]);

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => '💲Entrada']),
                $this->makeTNumeric(
                    [
                        'name' => 'valor_entrada',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ],
            [
                $label = $this->makeTLabel(['value' => '🐓 Gales']),
                $this->makeTNumeric(
                    [
                        'name' => 'gale',
                        'label' => $label,
                        'decimals' => 0,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ]
        )->layout = ['col-sm-6', 'col-sm-6'];
            

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => '✅ Stop WIN']),
                $this->makeTNumeric(
                    [
                        'name' => 'stop_win',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ],
            [
                $label = $this->makeTLabel(['value' => '❌ Stop LOSS']),
                $this->makeTNumeric(
                    [
                        'name' => 'stop_loss',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ],
        )->layout = ['col-sm-6', 'col-sm-6'];

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Tipo Stop LOSS']),
                $this->makeTCombo(
                    [
                        'name' => 'tipo_stop_loss', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['VALOR' => 'Valor', 'QUANTIDADE' => 'Quantidade']
                    ]
                )
            ],
        );

        $this->form->addContent([new TFormSeparator('')]);

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Modo']),
                $this->makeTCombo(
                    [
                        'name' => 'modo_treinamento', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['Y' => '📚 Treinamento', 'N' => '🏆 Real']
                    ]
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => '💰 Banca treinamento']),
                $this->makeTNumeric(
                    [
                        'name' => 'banca_treinamento',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => '↪️ Ciclo']),
                $this->makeTCombo(
                    [
                        'name' => 'usa_ciclo', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['Y' => 'Sim', 'N' => 'Não']
                    ],
                    function ($object){
                        $object->setChangeFunction('doConfigChange()');
                    }
                )
            ],
            [
                $label = $this->makeTLabel(['value' => '⚪ Proteção']),
                $this->makeTCombo(
                    [
                        'name' => 'protecao_branco', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['Y' => 'Sim', 'N' => 'Não']
                    ], function ($object) {
                        $object->title = 'Proteção no Branco';
                    }
                )
            ]
        )->layout = ['col-sm-6', 'col-sm-6'];
        
        $this->form->addContent([new TFormSeparator('')]);

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => '🔄 Entrada Automática']),
                $this->makeTCombo(
                    [
                        'name' => 'entrada_automatica', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['Y' => 'Habilitado', 'N' => 'Desabilitado']
                    ],
                    function ($object){
                        $object->setChangeFunction('doConfigChange()');
                    }
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Tipo de Entrada Automática']),
                $this->makeTCombo(
                    [
                        'name' => 'tipo_entrada_automatica', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['Y' => 'Somente Stop WIN', 'A' => 'Stop WIN + Stop LOSS', 'B' => 'Somente Stop LOSS']
                    ],
                    function ($object){
                        $object->setChangeFunction('doConfigChange()');
                    }
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Ciclo Stop LOSS']),
                $this->makeTCombo(
                    [
                        'name' => 'ciclo_stop_loss', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['A' => 'Habilitado', 'Y' => 'Desabilitado']
                    ]
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Valor máximo ciclo']),
                $this->makeTNumeric(
                    [
                        'name' => 'valor_max_ciclo',
                        'label' => $label,
                        'decimals' => 0,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Tipo de espera']),
                $this->makeTCombo(
                    [
                        'name' => 'tipo_espera', 
                        'label' => $label, 
                        'defaultOption' => false, 
                        'width' => '100%',
                        'items' => ['WIN' => 'Win', 'LOSS' => 'Loss']
                    ], function ($object) {
                        $object->title = '';
                    }
                )
            ]
        );

        $this->form->addFields(
            [
                $label = $this->makeTLabel(['value' => 'Quantidade de espera']),
                $this->makeTNumeric(
                    [
                        'name' => 'quantidade_espera',
                        'label' => $label,
                        'decimals' => 0,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ]
                )
            ]
        );

        $btn = $this->form->addAction('💾 Salvar', new TAction([$this, 'onSave'], $param), '');
        $btn->class = 'btn btn-sm btn-primary';

        $dropdown = new TDropDown('🚀 Iniciar Robô');
        $dropdown->id = 'btn_iniciar';
        $dropdown->style = 'display: none';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-sm btn-success waves-effect dropdown-toggle');
        $dropdown->addAction( '🚀 Iniciar Robô', $this->serilizeAction([$this, 'onIniciarRobo'], ['apos_loss' => 0]) );
        $dropdown->addAction( '🚀 Iniciar Após STOP WIN ou STOP LOSS', $this->serilizeAction([$this, 'onIniciarRobo'], ['apos_loss' => 1]) );
        $this->form->addFooterWidget( $dropdown );

        $btn = $this->form->addAction('⏹️ Parar Robô', new TAction([$this, 'onPararRobo']), '');
        $btn->id = 'btn_parar';
        $btn->style = 'display: none';
        $btn->class = 'btn btn-sm btn-danger';

        $sinais = new TElement('div');
        $sinais->id = 'campo_sinais';
        $sinais->style = 'border: 1px solid #ccc; padding: 5px; padding-left: 5px; min-height: 50px; width: 100%; background: #fff;';
        
        $container = new TElement('div');
        $container->add('<b>Últimos Sinais</b>');
        $container->style = 'margin: 14px; margin-top: 0px; min-height: 50px; width: 100%;';
        $container->add($sinais);

        $container_status = new TElement('div');
        $container_status->id = 'robo-status';
        $container_status->style = 'margin-bottom: 10px; margin-top: 0px';

        $container_modo = new TElement('div');
        $container_modo->id = 'modo';
        $container_modo->style = 'margin-bottom: 10px; margin-top: 0px';

        $meuRanking = new TNewRanking('<i class="fas fa-trophy green"></i>  Ranking das Minhas Estratégias');
        $topRanking = new TNewRanking('<i class="fas fa-trophy green"></i>  Ranking das 10 Melhores Estratégias', True);
        // $this->datagrid = $meuRanking->datagrid;

        $body = new THtmlRenderer('app/resources/double/dashboard-usuario.html');
        $body->enableSection(
            'main',
            [
                'filtro'      => $this->form,
                'indicator1'  => TUtils::renderInfoBox('total-win', 'WIN', 'trophy', 'green', 0),
                'indicator2'  => TUtils::renderInfoBox('total-loss', 'LOSS', 'times', 'red', 0),
                'indicator3'  => TUtils::renderInfoBox('total-lucro', 'Lucro/Perda', 'dollar-sign', 'green', 'R$ 0,00'),
                'indicator4'  => TUtils::renderInfoBox('total-saldo', 'Saldo Atual', 'money-bill-alt', 'green', 'R$ 0,00'),
                'indicator5'  => TUtils::renderInfoBox('maior-entrada', 'Maior Entrada', 'arrow-alt-circle-up', 'green', 'R$ 0,00'),
                'status_robo' => $container_status,
                'modo'        => $container_modo,
                'sinais'      => $container,
                'meuRanking'  => $meuRanking->panel,
                'topRanking'  => $topRanking->panel,
            ]
        );

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TUtils::createXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($body);

        parent::add($container);

        TScript::create($this->getJavaScript());
        TScript::create('atualiza_configuracao()', TRUE, 1000);
    }

    public function doCopiarEstrategia($param) {
        try {
            TUtils::openConnection('double', function() use($param){
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
                    throw new Exception("Você já possui esta estratégia na sua lista");
                    
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
    
            new TMessage('info', 'Estratégia copiada com sucesso.');
        } catch (\Throwable $th) {
            new TMessage('error', $th->getMessage());
        }
    }

    public function serilizeAction($action, $param) {
        $action = new TAction($action, $param);
        $url = $action->serialize(FALSE, TRUE);
        $wait_message = AdiantiCoreTranslator::translate('Loading');
        $action = "Adianti.waitMessage = '$wait_message';";
        $action.= "__adianti_post_data('form_TDoubleDashboardUsuario', '{$url}');";
        $action.= "return false;";

        return $action;
    }

    public function onIniciarRobo($param){
        $arrData = (array) $this->form->getData();

        $chat_id = TSession::getValue('usercustomcode');
        $usuario = TUtils::openFakeConnection('double', function () use($param, $chat_id){
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        if (!$usuario)
            return;

        $translate = $usuario->plataforma->translate;

        $valor_minimo = $usuario->protecao_branco ? $usuario->valor_minimo_protecao : $usuario->valor_minimo;
        if ($usuario->valor < $valor_minimo) {
            new TMessage(
                'error', 
                str_replace(
                    ['valor_minimo'],
                    [$valor_minimo],
                    $translate->MSG_INICIO_ROBO_2
                )
            );
            return;
        }

        if ($usuario->ultimo_saldo < $valor_minimo) {
            new TMessage(
                'error', 
                str_replace(
                    ['valor_minimo'],
                    [$valor_minimo],
                    $translate->MSG_INICIO_ROBO_3
                )
            );
            return;
        }

        $telegram = $usuario->canal->telegram;

        if ($usuario->status == 'ATIVO'){
            $telegram->sendMessage($usuario->chat_id, 'Robô iniciado no Dashboard');

            $telegram->sendMessage(
                $usuario->chat_id, 
                str_replace(
                    ['{dia_expiracao}'],
                    [date('d/m/Y', strtotime($usuario->data_expiracao))],
                    $translate->MSG_INICIO_ROBO_4
                )
            );
        }
        else {
            new TMessage(
                'error', 
                $translate->MSG_INICIO_ROBO_5
            );
            return;
        }

        $robo = new TDoubleRobo();
        if ($param['apos_loss'] == 0 ) 
            $robo->iniciar([
                'plataforma' => $usuario->plataforma->nome,
                'idioma' => $usuario->plataforma->idioma,
                'channel_id' => $usuario->canal->channel_id,
                'chat_id' => $usuario->chat_id
            ]);
        else
            $robo->iniciar_apos_loss([
                'plataforma' => $usuario->plataforma->nome,
                'idioma' => $usuario->plataforma->idioma,
                'channel_id' => $usuario->canal->channel_id,
                'chat_id' => $usuario->chat_id
            ]);

        // $texto = $translate->MSG_INICIO_ROBO_6;
        // $msg = str_replace(
        //     ['{usuario}', '{banca}', '{value}', '{gales}', '{stop_win}', '{stop_loss}', '{ciclo}', '{protecao_branco}', '{entrada_automatica}'],
        //     [
        //         $usuario->nome,
        //         number_format($usuario->ultimo_saldo, 2, ',', '.'),
        //         number_format($usuario->valor, 2, ',', '.'),
        //         $usuario->protecao,
        //         number_format($usuario->stop_win, 2, ',', '.'),
        //         number_format($usuario->stop_loss, 2, ',', '.') . '[' . ucfirst($usuario->tipo_stop_loss) . ']',
        //         $usuario->ciclo == 'Y' ? 'Habilitado' : 'Não habilitado',
        //         $usuario->protecao_branco == 'Y' ? 'Habilitado' : 'Não habilitado',
        //         $usuario->entrada_automatica == 'N' ? 'Não habilitado' : 'Habilitado'
        //     ],
        //     $texto
        // );

        // if ($usuario->entrada_automatica == 'Y')
        //     $msg .= '\n     - Ocorrerá após o Stop WIN';
        // if ($usuario->entrada_automatica == 'A')
        //     $msg .= '\n     - Ocorrerá após o Stop WIN e Stop LOSS';
        // if ($usuario->entrada_automatica == 'B')
        //     $msg .= '\n     - Ocorrerá após o Stop LOSS';

        // if (($usuario->entrada_automatica == 'A' or $usuario->entrada_automatica == 'B') and $usuario->ciclo == 'A') {
        //     $msg .= str_replace(
        //         ['{ciclo}'],
        //         [$translate->MSG_CICLO_7],
        //         '\n     - {ciclo} habilitado para o Stop LOSS'
        //     );

        //     if ($usuario->valor_max_ciclo > 0)
        //         $msg .= str_replace(
        //             ['{ciclo}', '{valor_max_ciclo}'],
        //             [
        //                 $translate->BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO,
        //                 number_format(valor_max_ciclo, 2, ',', '.')
        //             ],
        //             '\n     - {ciclo}: {valor_max_ciclo}'
        //         );
        // }

        // if ($usuario->entrada_automatica != 'N')
        //     $msg .= str_replace(
        //         ['{quantidade}', '{tipo}'],
        //         [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
        //         '\n     - Será esperado a ocorrência de {quantidade} {tipo}'
        //     );

        $botao_inicio = [
            "resize_keyboard" => true, 
            "keyboard" => [
                    [["text" => $translate->BOTAO_CONFIGURAR]],
                    [["text" => $translate->BOTAO_PARAR_ROBO]], 
                ] 
            ];

        $telegram->sendMessage($usuario->chat_id, $usuario->configuracao_texto, $botao_inicio);
        if ($param['apos_loss'] == 1 ) 
            $telegram->sendMessage(
                $usuario->chat_id, 
                str_replace(
                    ['{quantidade}', '{tipo}'],
                    [$usuario->entrada_automatica_total_loss, $usuario->entrada_automatica_tipo],
                    $translate->MSG_INICIO_ROBO_9
                )
            );
    
        TForm::sendData('form_TDoubleDashboardUsuario', $arrData, FALSE, TRUE, 1000);
    
        new TMessage('info', 'Robô iniciado com sucesso.');
    }

    public function onPararRobo($param){
        $arrData = (array) $this->form->getData();

        $chat_id = TSession::getValue('usercustomcode');
        $usuario = TUtils::openFakeConnection('double', function () use($param, $chat_id){
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        $robo = new TDoubleRobo();
        $robo->parar([
            'plataforma' => $usuario->plataforma->nome,
            'idioma' => $usuario->plataforma->idioma,
            'channel_id' => $usuario->canal->channel_id,
            'chat_id' => $usuario->chat_id
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
        $telegram->sendMessage($usuario->chat_id, 'Robô parado no Dashboard');
        $telegram->sendMessage($usuario->chat_id, $usuario->plataforma->translate->MSG_PARAR_ROBO, $botao_inicio);

        TForm::sendData('form_TDoubleDashboardUsuario', $arrData, FALSE, TRUE, 1000);

        new TMessage('info', 'Robô parado com sucesso.');
    }

    public function onSave($param) {
        try
        {
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
            $usuario->protecao_branco               = $object->protecao_branco;
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

            $usuario->ciclo              = $ciclo;
            $usuario->entrada_automatica = $entrada_automatica;
                        
            $usuario->save();

            TTransaction::close();
            
            new TMessage('info', 'Configuração salva com sucesso.');
        } catch (\Throwable $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        } finally {
            // TSession::setValue('form_TDoubleDashboard_data', $object);
            $this->form->setData( $object );
        }
    }

    private function getJavaScript()
    {
        return <<<JAVASCRIPT
            var options = {
                chart: {
                    type: 'line',
                    height: 300,
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 1000
                        }
                    }
                },
                series: [{
                    name: 'Acumulado',
                    data: []
                }, {
                    name: 'Valor',
                    data: []
                }],
                xaxis: {
                    type: 'datetime',
                    labels: {
                        format: 'HH:mm:ss',
                        datetimeUTC: false
                    },
                    timezone: 'America/Sao_Paulo',
                    categories: []
                },
                yaxis: [{
                    title: {
                        text: 'Acumulado'
                    }
                }, {
                    opposite: true,
                    title: {
                        text: 'Lucro/Prejuízo'
                    }
                }],
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    x: {
                        format: 'dd/MM/yyyy HH:mm:ss'
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#apexLineChart"), options);

            var interval_sinais        = null;       
            var interval_grafico       = null;      
            var interval_status        = null;       
            var interval_topRanking    = null;       
            var interval_meuRanking    = null;       

            var fetchingData_sinais      = false;
            var fetchingData_grafico     = false;
            var fetchingData_status      = false;
            var fetchingData_topRanking  = false;
            var fetchingData_meuRanking  = false;


            function atualiza_grafico() {
                if (fetchingData_grafico) {
                    return;
                }
                fetchingData_grafico = true;

                canal_id = document.getElementsByName('canal_id')[0].value;
                ultimo_id = document.getElementsByName('ultimo_id')[0].value;
                clearInterval(interval_grafico);

                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=getHistoricoJs&canal_id=' + canal_id + '&ultimo_id=' + ultimo_id, function(data) {
                    fetchingData_grafico = false;
                    var data = JSON.parse(data);
                    if (data.length > 0) {
                        var newData = [{
                            name: 'Valor Acumulado',
                            data: chart.w.config.series[0].data
                        }, {
                            name: 'Lucro/Prejuízo',
                            data: chart.w.config.series[1].data
                        }];

                        data.forEach(item => {
                            if (newData[0].data.length >= 20)
                                newData[0].data.shift();
                            newData[0].data.push({
                                x: item.data,
                                y: parseFloat(item.acumulado)
                            });

                            if (newData[1].data.length >= 20)
                                newData[1].data.shift();
                            newData[1].data.push({
                                x: item.data,
                                y: parseFloat(item.valor)
                            });
                        });

                        var allYValues = newData.flatMap(series => series.data.map(point => point.y));
                        var minY = Math.min(...allYValues) - 5;
                        var maxY = Math.max(...allYValues) + 5;

                        chart.updateOptions({
                            yaxis: [{
                                title: {
                                    text: 'Valor Acumulado'
                                },
                                min: minY,
                                max: maxY
                            }, {
                                title: {
                                    text: 'Lucro/Prejuízo'
                                },
                                opposite: true,
                                min: minY,
                                max: maxY
                            }]
                        });

                        document.getElementsByName('ultimo_id')[0].value = data[data.length-1].id;
                        chart.updateSeries(newData);
                    };

                    if (!document.hidden && '/double-dashboard-usuario' == location.pathname)
                        interval_grafico = setInterval(atualiza_grafico, 5000);
                }, false);
            };
                

            function atualiza_sinais() {
                if (fetchingData_sinais) {
                    return;
                }
                fetchingData_sinais = true;
                
                canal_id = document.getElementsByName('canal_id')[0].value;
                clearInterval(interval_sinais);

                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=sinaisJs&canal_id=' + canal_id, function(data) {
                    fetchingData_sinais = false;
                    var campoSinais = document.getElementById('campo_sinais');
                    campoSinais.innerHTML = data;

                    if (!document.hidden && '/double-dashboard-usuario' == location.pathname)
                        interval_sinais = setInterval(atualiza_sinais, 5000);
                }, false);
            }

            function atualiza_status() {
                if (fetchingData_status) {
                    return;
                }
                fetchingData_status = true;

                canal_id = document.getElementsByName('canal_id')[0].value;
                clearInterval(interval_status);

                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=statusJs&canal_id=' + canal_id, function(data) {
                    fetchingData_status = false;
                    var data = JSON.parse(data);

                    document.querySelector("#total-win").textContent = data.total_win;
                    document.querySelector("#total-loss").textContent = data.total_loss;
                    document.querySelector("#total-lucro").textContent = 'R$ ' + data.lucro_prejuizo;
                    document.querySelector("#total-saldo").textContent = 'R$ ' + data.saldo;
                    document.querySelector("#maior-entrada").textContent = 'R$ ' + data.maior_entrada;

                    if (data.robo_status == 'EXECUTANDO') {
                        document.querySelector("#robo-status").innerHTML = '<b>🟢 Seu robô está em execução</b>';
                        document.querySelector('#btn_iniciar').style.display = 'none';
                        document.querySelector('#btn_parar').style.display = 'inline';
                    }
                    else {
                        document.querySelector("#robo-status").innerHTML ='<b>🔴 Seu robô está parado</b>';
                        document.querySelector('#btn_iniciar').style.display = 'inline';
                        document.querySelector('#btn_parar').style.display = 'none';
                    }

                     if (data.modo_treinamento == 'Y')
                        document.querySelector("#modo").innerHTML = '<b>📚 Você está no modo treinamento</b>';
                    else
                        document.querySelector("#modo").innerHTML ='<b>🏆 Você está no modo real</b>';

                    if (!document.hidden && '/double-dashboard-usuario' == location.pathname)
                        interval_status = setInterval(atualiza_status, 5000);
                }, false);
            }

            function atualiza_top_ranking() {
                if (fetchingData_topRanking) {
                    return;
                }
                fetchingData_topRanking = true;

                canal_id = document.getElementsByName('canal_id')[0].value;
                clearInterval(interval_topRanking);
                
                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=topRankingJS&canal_id=' + canal_id, function(data) {
                    fetchingData_topRanking = false;
                    $("#topdataranking tbody").remove();
                    $("#topdataranking").append(data);

                    if (!document.hidden && '/double-dashboard-usuario' == location.pathname)
                        interval_topRanking = setInterval(atualiza_top_ranking, 5000);
                }, false);
            }

            function atualiza_meu_ranking() {
                if (fetchingData_meuRanking) {
                    return;
                }
                fetchingData_meuRanking = true;

                canal_id = document.getElementsByName('canal_id')[0].value;
                clearInterval(interval_meuRanking);
                
                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=meuRankingJS&canal_id=' + canal_id, function(data) {
                    fetchingData_meuRanking = false;
                    $("#meudataranking tbody").remove();
                    $("#meudataranking").append(data);

                    if (!document.hidden && '/double-dashboard-usuario' == location.pathname)
                        interval_meuRanking = setInterval(atualiza_meu_ranking, 5000);
                }, false);
            }

            function atualiza_configuracao() {
                canal_id = document.getElementsByName('canal_id')[0].value;
                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=usuarioJs&canal_id=' + canal_id, function(data) {
                    console.log(data);
                    var data = JSON.parse(data);

                    document.getElementsByName('valor_entrada')[0].value = data.valor;
                    document.getElementsByName('gale')[0].value = data.protecao;
                    document.getElementsByName('stop_win')[0].value = data.stop_win;
                    document.getElementsByName('stop_loss')[0].value = data.stop_loss;
                    document.getElementsByName('tipo_stop_loss')[0].value = data.tipo_stop_loss;
                    document.getElementsByName('modo_treinamento')[0].value = data.modo_treinamento;
                    document.getElementsByName('banca_treinamento')[0].value = data.banca_treinamento;
                    document.getElementsByName('protecao_branco')[0].value = data.protecao_branco;
                    document.getElementsByName('tipo_espera')[0].value = data.entrada_automatica_tipo;
                    document.getElementsByName('quantidade_espera')[0].value = data.entrada_automatica_total_loss;
                    document.getElementsByName('valor_max_ciclo')[0].value = data.valor_max_ciclo;
                    
                    var ciclo = data.ciclo;
                    var usa_ciclo = ciclo;
                    if (ciclo !== 'N')
                        usa_ciclo = 'Y';
                    document.getElementsByName('usa_ciclo')[0].value = usa_ciclo;
                    document.getElementsByName('ciclo_stop_loss')[0].value = ciclo;
                    
                    var entrada_automatica = data.entrada_automatica;
                    var usa_entrada_automatica = entrada_automatica;
                    if (entrada_automatica !== 'N')
                        usa_entrada_automatica = 'Y';
                    document.getElementsByName('entrada_automatica')[0].value = usa_entrada_automatica;
                    document.getElementsByName('tipo_entrada_automatica')[0].value = entrada_automatica;

                    doConfigChange();
                }, false);
            }

            function doConfigChange() 
            {
                var ciclo = document.getElementsByName('usa_ciclo')[0].value;
                var ciclo_stop_loss = document.getElementsByName('ciclo_stop_loss')[0].value;
                if (ciclo !== 'N' && ciclo_stop_loss)
                {
                    ciclo = ciclo_stop_loss;
                }

                var entrada_automatica = document.getElementsByName('entrada_automatica')[0].value;
                var tipo_entrada_automatica =  document.getElementsByName('tipo_entrada_automatica')[0].value;
                if (entrada_automatica !== 'N' && tipo_entrada_automatica) 
                {
                    entrada_automatica = tipo_entrada_automatica;
                }
                
                if ((['A', 'B'].indexOf(entrada_automatica) > -1) && (['A', 'Y'].indexOf(ciclo) >-1)) {
                    tcombo_enable_field( 'form_TDoubleDashboardUsuario', 'ciclo_stop_loss' );
                } else {
                    tcombo_disable_field( 'form_TDoubleDashboardUsuario', 'ciclo_stop_loss' );
                }

                if ((['A', 'B'].indexOf(entrada_automatica) > -1) && (ciclo == 'A')) {
                    tcombo_enable_field( 'form_TDoubleDashboardUsuario', 'valor_max_ciclo' );
                } else {
                    tcombo_disable_field( 'form_TDoubleDashboardUsuario', 'valor_max_ciclo' );
                }

                if (entrada_automatica !== 'N') {
                    tcombo_enable_field( 'form_TDoubleDashboardUsuario', 'tipo_entrada_automatica' );
                    tcombo_enable_field( 'form_TDoubleDashboardUsuario', 'tipo_espera' );
                    tcombo_enable_field( 'form_TDoubleDashboardUsuario', 'quantidade_espera' );
                } else {
                    tcombo_disable_field( 'form_TDoubleDashboardUsuario', 'tipo_entrada_automatica' );
                    tcombo_disable_field( 'form_TDoubleDashboardUsuario', 'tipo_espera' );
                    tcombo_disable_field( 'form_TDoubleDashboardUsuario', 'quantidade_espera' );
                }
            }

            function startUpdating() {
                if (!interval_sinais) {
                    atualiza_sinais();
                    atualiza_grafico();
                    atualiza_status();
                    atualiza_top_ranking();
                    atualiza_meu_ranking();
                    
                    interval_sinais     = setInterval(atualiza_sinais, 5000);
                    interval_grafico    = setInterval(atualiza_grafico, 5000);
                    interval_status     = setInterval(atualiza_status, 5000);
                    interval_topRanking = setInterval(atualiza_top_ranking, 5000);
                    interval_meuRanking = setInterval(atualiza_meu_ranking, 5000);
                }
            }

            function stopUpdating() {
                if (interval_sinais) {
                    clearInterval(interval_sinais);
                    clearInterval(interval_grafico);
                    clearInterval(interval_status);
                    clearInterval(interval_topRanking);

                    interval_sinais        = null;
                    interval_grafico       = null;
                    interval_status        = null;
                    interval_status = null;
                }
            }

            function doChangeCanal() {
                atualiza_configuracao();
                fetchingData_sinais = true;
                fetchingData_grafico = true;
                fetchingData_status = true;

                stopUpdating();

                document.getElementsByName('ultimo_id')[0].value = 0;
                chart.updateSeries([{
                            name: 'Valor Acumulado',
                            data: []
                        }, {
                            name: 'Lucro/Prejuízo',
                            data: []
                        }]);

                document.querySelector('#btn_iniciar').style.display = 'none';
                document.querySelector('#btn_parar').style.display = 'none';

                startUpdating();

                fetchingData_sinais = false;
                fetchingData_grafico = false;
                fetchingData_status = false;
            }

            setInterval(function() {
                if ('/double-dashboard-usuario' !== location.pathname) {
                    if (interval_sinais) 
                        stopUpdating();
                }
            }, 1000);

            window.addEventListener('beforeunload', function() {
                stopUpdating();
            });

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopUpdating();
                } else {
                    startUpdating();
                }
            });

            startUpdating();
            chart.render();
JAVASCRIPT;
    }

    public static function onAddOption($param, $bet_name)
    {
        $path = 'app/images/regras/';
        $path_bet = "app/images/regras/{$bet_name}/";

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
        $canal = TUtils::openFakeConnection('double', function () use ($param){
            return new DoubleCanal($param['canal_id'], false);
        });

        $bet_name = $canal->plataforma->nome;

        $sinais = TUtils::openFakeConnection('double', function () use ($canal){
            $sinais = DoubleSinal::where('plataforma_id', '=', $canal->plataforma_id)
                ->orderBy('id', 'desc')
                ->take(16)
                ->load();

            if ($sinais) {
                $arr = [];
                foreach ($sinais as $sinal) {
                    $arr[] = $sinal->numero;
                }
                return array_reverse($arr);
            }
            else
                return [];
        });

        $option = '';
        foreach ($sinais as $sinal) {
            $object = self::onAddOption($sinal, $bet_name);
            if ($object)
                $option .= $object->getContents();
        }
        echo $option;
    }

    public static function getHistoricoJs($param)
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param){
            $chat_id = TSession::getValue('usercustomcode');
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        echo TDashboardUsuarioService::getHistorico($usuario->id, $param['ultimo_id']);
    }

    public static function statusJs($param) 
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param){
            $chat_id = TSession::getValue('usercustomcode');
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        echo TDashboardUsuarioService::getStatusUsuario($usuario);
    }

    public static function usuarioJS($param)
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param){
            $chat_id = TSession::getValue('usercustomcode');
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        echo json_encode($usuario->toArray());
    }   

    public static function topRankingJS($param) {
        $lista = TDashboardUsuarioService::getRanking($param['canal_id']);

        $raking = new TNewRanking('', True);
        $datagrid = $raking->datagrid;

        foreach ($lista as $key => $value) {
            $datagrid->addItem( (object) $value);
        }
        echo $datagrid->getBody();
    }

    public static function meuRankingJS($param) {
        $usuario = TUtils::openFakeConnection('double', function () use ($param){
            $chat_id = TSession::getValue('usercustomcode');
            return DoubleUsuario::where('canal_id', '=', $param['canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });
        $lista = TDashboardUsuarioService::getRanking($param['canal_id'], $usuario->id);

        $raking = new TNewRanking('');
        $datagrid = $raking->datagrid;

        foreach ($lista as $key => $value) {
            $datagrid->addItem( (object) $value);
        }
        echo $datagrid->getBody();
    }
}