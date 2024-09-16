<?php

use Adianti\Control\TAction;
use Adianti\Database\TFilter;
use Adianti\Base\TStandardList;
use Adianti\Database\TCriteria;
use Adianti\Database\TExpression;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Validator\TMinValueValidator;
use Adianti\Wrapper\BootstrapFormBuilder;

class TExecucoes 
{
    use TUIBuilderTrait;
    use TTransformationTrait;

    public $datagrid;
    public $panel;

    public function __construct()
    {   
        // data-grid
        $dataGrid = new stdClass;
        $dataGrid->name = 'datagridExecucao';
        $dataGrid->pagenavigator = false;
        $dataGrid->columns = [
            ['name' => 'execucao', 'label' => '#', 'width' => '5%', 'align' => 'center'],
            ['name' => 'valor_banca', 'label' => 'Banca', 'width' => '15%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'valor_entrada', 'label' => 'Entrada', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'valor_stop_win', 'label' => 'Stop WIN', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'valor_stop_loss', 'label' => 'Stop LOSS', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'status', 'label' => 'Staus', 'width' => '10%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'status_transformer'])],
            ['name' => 'valor_lucro_prejuizo', 'label' => 'Lucro/Prejuízo', 'width' => '10%', 'align' => 'right', 'data_property' => ['name' => 'style', 'value' => 'font-weight: bold'], 'transformer' => Closure::fromCallable([$this, 'lucroprejuizoTransformer'])],
            ['name' => '0', 'label' => 'Previsto', 'width' => '15%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'previstoTransformer'])],
            ['name' => '0', 'label' => 'Realizado', 'width' => '15%', 'align' => 'right', 'data_property' => ['name' => 'style', 'value' => 'font-weight: bold'], 'transformer' => Closure::fromCallable([$this, 'realizadoTransformer'])],
        ];

        $this->panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagridExecucao');
    }

    public function previstoTransformer($value, $object)
    {
        $calculo = $object->valor_banca + $object->valor_stop_win;
        return 'R$ ' . number_format($calculo, 2, ',', '.');
    }

    public function status_transformer($value, $object)
    {
        $status = [
            'AGUARDANDO'  => ['class' => 'warning', 'label' => 'Aguardando...'], 
            'EXECUTANDO'  => ['class' => 'success', 'label' => 'Executando'],
            'FINALIZADO'  => ['class' => 'danger' , 'label' => 'Finalizado'],
            'PARADO'      => ['class' => 'info'   , 'label' => 'Parado']
        ];
        
        $class = $status[$value]['class'];
        $label = $status[$value]['label'];
        $div = new TElement('span');
        $div->class = "label label-{$class}";
        $div->style = "text-shadow:none; font-size:12px; font-weight:lighter";
        $div->add($label);
        return $div;
    }
    
    public function realizadoTransformer($value, $object)
    {
        if ($object->status == 'AGUARDANDO')
            return 'R$ 0,00';
        else {
            $calculo = $object->valor_banca + $object->valor_lucro_prejuizo;

            if ($object->valor_lucro_prejuizo > 0)
                return "<span style='color:blue'>R\$ " . number_format($calculo, 2, ',', '.') . "</span>";
            elseif ($object->valor_lucro_prejuizo < 0)
                return "<span style='color:red'>R\$ " . number_format($calculo, 2, ',', '.') . "</span>";
            else 
                return 'R$ ' . number_format($calculo, 2, ',', '.');
        }
    }

    public function lucroprejuizoTransformer($value, $object)
    {
        if ($object->status == 'AGUARDANDO')
            return 'R$ 0,00';
        else {
            if ($object->valor_lucro_prejuizo > 0)
                return "<span style='color:blue'>R\$ " . number_format($value, 2, ',', '.') . "</span>";
            elseif ($object->valor_lucro_prejuizo < 0)
                return "<span style='color:red'>R\$ " . number_format($value, 2, ',', '.') . "</span>";
            else 
                return 'R$ ' . number_format($value, 2, ',', '.');
        }
    }
}

class TDoubleUsuarioObjetivo extends TStandardList
{
    use TUIBuilderTrait;
    use TTransformationTrait;

    public function __construct($param)
    {
        parent::__construct();

        parent::setDatabase('double');          
        parent::setActiveRecord('DoubleUsuarioObjetivoExecucao');
        parent::setLimit(0);
        parent::setDefaultOrder('id', 'asc');

        $this->form = new BootstrapFormBuilder('form_search_' . get_class($this));
        $this->form->setFormTitle('Configuração');
        $this->form->addExpandButton('', 'fa:cog', false);
        $this->form->enableClientValidation();

        // botões
        $btn = $this->form->addAction('💾 Salvar', new TAction([$this, 'doSalvar'], $param), '');
        $btn->name = 'btn_salvar';
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

        // filtros
        // parent::addFilterField(
        //     'usuario_objetivo_id IN (SELECT 1 FROM double_usuario_objetivo o WHERE o.id = double_usuario_objetivo_execucao.usuario_objetivo_id AND o.usuario_id =',
        //     '', 
        //     'search_canal_id',
        //     function($data){
        //         $data = TUtils::openConnection('double', function () use($data){
        //             $chat_id = TSession::getValue('usercustomcode');
        //             $usuario = DoubleUsuario::where('canal_id', '=', $data)
        //                 ->where('chat_id', '=', $chat_id)
        //                 ->where('deleted_at', 'is', null)
        //                 ->first();

        //             return $usuario->id;
        //         });

        //         return 'NOESC:'. $data . ')';
        //     }, 
        //     TExpression::OR_OPERATOR
        // );

        parent::addFilterField(
            'usuario_objetivo_id IN (SELECT id FROM double_usuario_objetivo o WHERE o.usuario_id =',
            '', 
            'search_canal_id',
            function($data){
                $data = TUtils::openConnection('double', function () use($data){
                    $chat_id = TSession::getValue('usercustomcode');
                    $usuario = DoubleUsuario::where('canal_id', '=', $data)
                        ->where('chat_id', '=', $chat_id)
                        ->where('deleted_at', 'is', null)
                        ->first();
                        
                    return $usuario->id;
                });

                return 'NOESC:'. $data . ')';
            }, 
            TExpression::OR_OPERATOR
        );

        parent::setCriteria( TCriteria::create([1 => 2]) );

        // campos
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id'])]
        );

        $usuarioCriteria = new TCriteria;
        $usuarioCriteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                'IN',
                ['NAO_GERA', 'GERA', 'PROPAGA_VALIDA_SINAL']
            )
        );

        $usuarioCriteria->add(
            new TFilter(
                '(SELECT u.chat_id FROM double_usuario u WHERE u.canal_id = double_canal.id and u.chat_id = ' . TSession::getValue('usercustomcode') . ')',
                '=',
                TSession::getValue('usercustomcode')
            )
        );
        
        $usuarioCriteria->add(  new TFilter( 'ativo', '=', 'Y') );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Canal'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'search_canal_id', 
                    'label' => $label, 
                    'model' => 'DoubleCanal', 
                    'database' => 'double',
                    'key' => 'id', 
                    'display' => '[{plataforma->idioma}] {plataforma->nome} - {nome}',
                    // 'defaultOption' => false,
                    'width' => '100%',
                    'criteria' => $usuarioCriteria,
                    'required' => true
                ], 
                function ($object){
                    $object->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
                }
            )],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '💲Entrada (%)'])],
            [$this->makeTNumeric(
                [
                    'name' => 'percentual_entrada',
                    'label' => $label,
                    'decimals' => 3,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 0,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['0,001']);
                }
            )],
            [$label = $this->makeTLabel(['value' => '🐓 Gales'])],
            [$this->makeTNumeric(
                [
                    'name' => 'protecoes',
                    'label' => $label,
                    'decimals' => 0,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 0,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['0']);
                }
            )]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '✅ Stop WIN (%)'])],
            [$this->makeTNumeric(
                [
                    'name' => 'percentual_stop_win',
                    'label' => $label,
                    'decimals' => 3,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 0,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['0,001']);
                }
            )],
            [$label = $this->makeTLabel(['value' => '❌ Stop LOSS (%)'])],
            [$this->makeTNumeric(
                [
                    'name' => 'percentual_stop_loss',
                    'label' => $label,
                    'decimals' => 3,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 0,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['0,001']);
                }
            )]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '⚪ Prot. Branco'])],
            [$this->makeTRadioGroup(
                [
                    'name' => 'protecao_branco',
                    'label' => $label,
                    'items' => ['Y' => 'Sim', 'N' => 'Não'],
                    'value' => 'N',
                    'useButton' => true,
                    'layout' => 'horizontal'
                ]
            )],
            [$label = $this->makeTLabel(['value' => 'Modo'])],
            [$this->makeTRadioGroup(
                [
                    'name' => 'modo_treinamento',
                    'label' => $label,
                    'items' => ['Y' => '📚 Treinamento', 'N' => '🏆 Real'],
                    'value' => 'Y',
                    'useButton' => true,
                    'layout' => 'horizontal'
                ]
            )]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Total Execuções'])],
            [$this->makeTNumeric(
                [
                    'name' => 'total_execucoes',
                    'label' => $label,
                    'decimals' => 0,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 0,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['1']);
                }
            )],
            [],[]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Tipo Periodicidade'])],
            [$this->makeTRadioGroup(
                [
                    'name' => 'tipo_periodicidade',
                    'label' => $label,
                    'items' => ['HORAS' => 'Horas', 'MINUTOS' => 'Minutos'],
                    'value' => 'HORAS',
                    'useButton' => true,
                    'layout' => 'horizontal'
                ]
            )],
            [$label = $this->makeTLabel(['value' => 'Periodicidade'])],
            [$this->makeTNumeric(
                [
                    'name' => 'valor_periodicidade',
                    'label' => $label,
                    'decimals' => 0,
                    'decimalsSeparator' => ',',
                    'thousandSeparator' => '.',
                    'width' => '100%',
                    'value' => 12,
                    'required' => true
                ],
                function ($object) use ($label){
                    $object->addValidation((string) '<b>' . $label->getValue() . '</b>', new TMinValueValidator, ['1']);
                }
            )],
        );

        $sinais = new TElement('div');
        $sinais->id = 'campo_sinais';
        $sinais->style = 'border: 1px solid #ccc; padding: 5px; padding-left: 5px; min-height: 50px; width: 100%; background: #fff;';
        
        $container_sinais = new TElement('div');
        $container_sinais->add('<b>Últimos Sinais</b>');
        $container_sinais->style = 'margin-bottom: 14px; margin-top: 0px; min-height: 50px; width: 100%;';
        $container_sinais->add($sinais);

        $session_data = TSession::getValue(get_class($this) . '_filter_data');
        if ($session_data && $session_data->search_canal_id) {
            $this->form->setData( $session_data );
        }

        $execucoes = new TExecucoes;
        $panel = $execucoes->panel;
        $this->datagrid = $execucoes->datagrid;

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', get_class($this)));
        $container->add($this->form);
        $container->add($container_sinais);
        $container->add($panel);
        
        parent::add($container);

        $use_redis = DoubleConfiguracao::getConfiguracao('use_redis');
        if ($use_redis == 'Y') {
            TScript::create($this->getJavaScriptRedisWS());
            // TScript::create($this->getJavaScriptRedis());
        }
        else
            TScript::create($this->getJavaScript());
    }

    public function beforeStaticLoadPage($param)
    {
        unset($param['static']);
        return $param;
    }

    public function onReload($param = NULL)
    {
        $objects = (array) parent::onReload($param);

        $objetivo = null;
        if (count($objects) > 0)
            $objetivo = $objects[0]->usuario_objetivo;

        if ($objetivo) {
            $this->form->setData($objetivo);
        }

        return $objects;
    }

    public function doSalvar($param)
    {
        $object = null;
        try
        {
            $chat_id = TSession::getValue('usercustomcode');
            $object = TUtils::openConnection('double', function() use ($param, $chat_id){
                $object = (array) $this->form->getData();
                $this->form->validate();

                $usuario = DoubleUsuario::where('canal_id', '=', $param['search_canal_id'])
                    ->where('chat_id', '=', $chat_id)
                    ->first();

                $object['usuario_id'] = $usuario->id;
                unset($object['id']);

                $objetivo = DoubleUsuarioObjetivo::where('usuario_id', '=', $usuario->id)->first();
                if (!$objetivo)
                    $objetivo = new DoubleUsuarioObjetivo();

                $objetivo->fromArray($object);
                $objetivo->store();

                return $object;
            });

            new TMessage('info', 'Configuração salva com sucesso.', new TAction([$this, 'onReload']));
        } catch (\Throwable $th) {
            new TMessage('error', $th->getMessage());
        } finally
        {
            if ($object)
                $this->form->setData($object);
        }
    }

    public function serilizeAction($action, $param) 
    {
        $action = new TAction($action, $param);
        $url = $action->serialize(FALSE, TRUE);
        $wait_message = AdiantiCoreTranslator::translate('Loading');
        $action = "Adianti.waitMessage = '$wait_message';";
        $action.= "__adianti_post_data('form_search_TDoubleUsuarioObjetivo', '{$url}');";
        $action.= "return false;";

        return $action;
    }

    public function onIniciarRobo($param)
    {
        $arrData = (array) $this->form->getData();
        $this->form->validate();

        $chat_id = TSession::getValue('usercustomcode');
        $usuario = TUtils::openFakeConnection('double', function () use($param, $chat_id){
            return DoubleUsuario::where('canal_id', '=', $param['search_canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        if (!$usuario)
            return;

        try {
            $execucao = TUtils::openConnection('double', function () use ($usuario){
                $objetivo = DoubleUsuarioObjetivo::where('usuario_id', '=', $usuario->id)->first();
                $execucao = null;
                foreach ($objetivo->execucoes as $key => $tmp) {
                    if ($tmp->status == 'AGUARDANDO')
                    {
                        $execucao = $tmp;
    
                        $translate = $usuario->plataforma->translate;
                        $valor_minimo = $usuario->protecao_branco == 'Y' ? $usuario->plataforma->valor_minimo_protecao : $usuario->plataforma->valor_minimo;
                        if ($execucao->valor_entrada < $valor_minimo) {
                            throw new Exception(
                                str_replace(
                                    ['{valor}'],
                                    [$valor_minimo],
                                    $translate->MSG_INICIO_ROBO_2
                                )
                            );
                        }
    
                        if ($usuario->ultimo_saldo < $valor_minimo) {
                            throw new Exception(
                                str_replace(
                                    ['{valor}'],
                                    [$valor_minimo],
                                    $translate->MSG_INICIO_ROBO_3
                                )
                            );
                            
                        }
    
                        $objetivo = $execucao->usuario_objetivo;
                        $objetivo->status = 'EXECUTANDO';
                        $objetivo->execucoes_em_execucao = true;
                        $objetivo->save();
    
                        $execucao->status          = 'EXECUTANDO';
                        $execucao->inicio_execucao = (new DateTime())->format('Y-m-d H:i:s');
                        $execucao->save();
    
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
    
                        break;
                    }
                }
    
                return $execucao;
            });
        } catch (\Throwable $th) {
            new TMessage('error', $th->getMessage());        
            return;
        }

        if (!$execucao)
        {
            new TMessage('error', 'Você não tem execuções para iniciar, salve primeiro as configurações.');
            return;
        }

        $translate = $usuario->plataforma->translate;

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

        new TMessage('info', 'Robô iniciado com sucesso.', new TAction([$this, 'onReload']));
    }

    public function onPararRobo($param)
    {
        $arrData = (array) $this->form->getData();

        $chat_id = TSession::getValue('usercustomcode');
        $usuario = TUtils::openFakeConnection('double', function () use($param, $chat_id){
            return DoubleUsuario::where('canal_id', '=', $param['search_canal_id'])
                ->where('chat_id', '=', $chat_id)
                ->first();
        });

        // TUtils::openConnection('double', function () use ($usuario){
        //     $objetivo = DoubleUsuarioObjetivo::where('usuario_id', '=', $usuario->id)->first();
        //     $execucao = null;
        //     foreach ($objetivo->execucoes as $key => $tmp) {
        //         if ($tmp->status == 'EXECUTANDO')
        //         {
        //             $execucao = $tmp;
        //             $execucao->status          = 'FINALIZADO';
        //             $execucao->fim_execucao = (new DateTime())->format('Y-m-d H:i:s');
        //             $execucao->save();

        //             $execucao->atualizar_progresso();

        //             break;
        //         }
        //     }

        //     return $execucao;
        // });

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

        // TForm::sendData('form_TDoubleDashboardUsuario', $arrData, FALSE, TRUE, 1000);

        new TMessage('info', 'Robô parado com sucesso.', new TAction([$this, 'onReload']));
    }

    public static function execucoesJS($param) {
        $expression = TSession::getValue('DoubleUsuarioObjetivoExecucao_filter_0');
        
        $lista = TUtils::openFakeConnection('double', function() use ($expression){
            $criteria = TCriteria::create([1 => 2]);
            $criteria->Add(new TFilter('status', '=', 'EXECUTANDO'), TCriteria::OR_OPERATOR);
            if ($expression)
                $criteria->add($expression, TCriteria::AND_OPERATOR);
            
            $repository = new TRepository('DoubleUsuarioObjetivoExecucao');
            return $repository->load($criteria, FALSE);
        });

        foreach ($lista as $execucao) {
            $execucao->atualizar_progresso();
        }

        $lista = TUtils::openFakeConnection('double', function() use ($expression){
            $criteria = TCriteria::create([1 => 2]);
            if ($expression)
                $criteria->add($expression, TCriteria::OR_OPERATOR);

            $repository = new TRepository('DoubleUsuarioObjetivoExecucao');
            $objects = $repository->load($criteria, FALSE);
            if (isset($objects))
                return $objects;
            else
                return [];
        });

        $raking = new TExecucoes;
        $datagrid = $raking->datagrid;

        foreach ($lista as $key => $value) {
            // DoubleErros::registrar(1, 'TDoubleUsuarioObjetivo', 'execucoesJS', TSession::getValue('usercustomcode'), json_encode((array) $value));
            $datagrid->addItem( (object) $value);
        }
        echo $datagrid->getBody();
    }

    private function getJavaScript()
    {
        return <<<JAVASCRIPT

        var interval_status = null;  
        var fetchingData_status = false;

        var interval_execucoes = null;  
        var fetchingData_execucoes = false;

        function atualiza_status() 
        {
                if (fetchingData_status) {
                    return;
                }

                if (!document.getElementsByName('search_canal_id')[0])
                    return;

                fetchingData_status = true;

                canal_id = document.getElementsByName('search_canal_id')[0].value;

                if (canal_id == "")
                {
                    document.querySelector('#btn_iniciar').style.display = 'none';
                    document.querySelector('#btn_parar').style.display = 'none';
                    return;
                }

                clearInterval(interval_status);

                __adianti_ajax_exec('class=TDoubleDashboardUsuario&method=statusJs&canal_id=' + canal_id, function(data) {
                    console.log(data);
                    fetchingData_status = false;
                    
                    var data = JSON.parse(data);

                    if (data.status_objetivo == 'EXECUTANDO') {
                        document.querySelector('#btn_iniciar').style.display = 'none';
                        document.querySelector('#btn_parar').style.display = 'inline';

                        tcombo_disable_field('form_search_TDoubleUsuarioObjetivo', 'search_canal_id');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_entrada');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_win');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_loss');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'protecoes');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'protecao_branco');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'modo_treinamento');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'total_execucoes');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'tipo_periodicidade');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'valor_periodicidade');
                        tbutton_disable_field('form_search_TDoubleUsuarioObjetivo', 'btn_salvar');
                    }
                    else {
                        if (data.execucao_status_parado == 'Y')
                        {
                            document.querySelector('#btn_iniciar').style.display = 'none';
                            document.querySelector('#btn_parar').style.display = 'none';
                        }
                        else 
                        {
                            document.querySelector('#btn_iniciar').style.display = 'inline';
                            document.querySelector('#btn_parar').style.display = 'none';   
                        }

                        tcombo_enable_field('form_search_TDoubleUsuarioObjetivo', 'search_canal_id');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_entrada');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_win');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_loss');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'protecoes');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'protecao_branco');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'modo_treinamento');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'total_execucoes');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'tipo_periodicidade');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'valor_periodicidade');
                        tbutton_enable_field('form_search_TDoubleUsuarioObjetivo', 'btn_salvar');
                    }

                    if (!document.hidden && '/double-usuario-objetivo' == location.pathname)
                        interval_status = setInterval(atualiza_status, 5000);
                }, false);
            }

            function atualiza_execucoes() {
                if (fetchingData_execucoes) {
                    return;
                }

                if (!$("#datagridExecucao"))
                    return;

                fetchingData_execucoes = true;

                clearInterval(interval_execucoes);
                
                __adianti_ajax_exec('class=TDoubleUsuarioObjetivo&method=execucoesJS', function(data) {
                    fetchingData_execucoes = false;
                    $("#datagridExecucao tbody").remove();
                    $("#datagridExecucao").append(data);

                    if (!document.hidden && '/double-usuario-objetivo' == location.pathname)
                    interval_execucoes = setInterval(atualiza_execucoes, 5000);
                }, false);
            }

            function startUpdating() {
                if (!interval_status) {
                    atualiza_status();
                    
                    interval_status = setInterval(atualiza_status, 5000);
                    interval_execucoes = setInterval(atualiza_execucoes, 5000);
                }
            }

            function stopUpdating() {
                if (interval_status) {
                    clearInterval(interval_status);
                    clearInterval(interval_execucoes);

                    interval_status = null;
                    interval_execucoes = null;
                }
            }

            function doChangeCanal() {
                fetchingData_status = true;

                stopUpdating();

                document.querySelector('#btn_iniciar').style.display = 'none';
                document.querySelector('#btn_parar').style.display = 'none';

                startUpdating();

                fetchingData_status = false;
            }

            setInterval(function() {
                if ('/double-usuario-objetivo' !== location.pathname) {
                    if (interval_status) 
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

JAVASCRIPT;
    }

    private function getJavaScriptRedisWS()
    {
        $chat_id = TSession::getValue('usercustomcode');
        $take = $this->isMobile() ? 21 : 25;
        $servidor_ws = DoubleConfiguracao::getConfiguracao('servidor_ws');

        return <<<JAVASCRIPT
            var socket = null;
            
            function atualiza_sinais() {
                if (!document.getElementsByName('search_canal_id'))
                    return;

                canal_id = document.getElementsByName('search_canal_id')[0].value;
                if (canal_id == '')
                  return;

                __adianti_ajax_exec('api/dashboard/usuario/sinaisJs?canal_id=' + canal_id + '&take={$take}', function(mensagem) {
                    var campoSinais = document.getElementById('campo_sinais');
                    campoSinais.innerHTML = mensagem.data;
                }, false);
            }

            function atualiza_status() {
                if (!document.getElementsByName('search_canal_id')[0])
                    return;

                canal_id = document.getElementsByName('search_canal_id')[0].value;

                if (canal_id == "")
                {
                    document.querySelector('#btn_iniciar').style.display = 'none';
                    document.querySelector('#btn_parar').style.display = 'none';
                    return;
                }

                __adianti_ajax_exec('api/dashboard/usuario/statusJs?canal_id=' + canal_id + '&chat_id={$chat_id}', function(mensagem) {
                    var data = JSON.parse(mensagem.data);

                    if (data.status_objetivo == 'EXECUTANDO') {
                        document.querySelector('#btn_iniciar').style.display = 'none';
                        document.querySelector('#btn_parar').style.display = 'inline';

                        tcombo_disable_field('form_search_TDoubleUsuarioObjetivo', 'search_canal_id');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_entrada');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_win');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_loss');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'protecoes');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'protecao_branco');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'modo_treinamento');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'total_execucoes');
                        tradiogroup_disable_field('form_search_TDoubleUsuarioObjetivo', 'tipo_periodicidade');
                        tfield_disable_field('form_search_TDoubleUsuarioObjetivo', 'valor_periodicidade');
                        tbutton_disable_field('form_search_TDoubleUsuarioObjetivo', 'btn_salvar');
                    }
                    else {
                        if (data.execucao_status_parado == 'Y')
                        {
                            document.querySelector('#btn_iniciar').style.display = 'none';
                            document.querySelector('#btn_parar').style.display = 'none';
                        }
                        else 
                        {
                            document.querySelector('#btn_iniciar').style.display = 'inline';
                            document.querySelector('#btn_parar').style.display = 'none';   
                        }

                        tcombo_enable_field('form_search_TDoubleUsuarioObjetivo', 'search_canal_id');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_entrada');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_win');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'percentual_stop_loss');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'protecoes');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'protecao_branco');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'modo_treinamento');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'total_execucoes');
                        tradiogroup_enable_field('form_search_TDoubleUsuarioObjetivo', 'tipo_periodicidade');
                        tfield_enable_field('form_search_TDoubleUsuarioObjetivo', 'valor_periodicidade');
                        tbutton_enable_field('form_search_TDoubleUsuarioObjetivo', 'btn_salvar');
                    }
                }, false);
            }

            function atualiza_execucoes() {
                if (!$("#datagridExecucao"))
                    return;

                __adianti_ajax_exec('class=TDoubleUsuarioObjetivo&method=execucoesJS', function(data) {
                    fetchingData_execucoes = false;
                    $("#datagridExecucao tbody").remove();
                    $("#datagridExecucao").append(data);
                }, false);
            }

            function inicializarEventSource() {
                if (!document.getElementsByName('search_canal_id'))
                    return;

                canal_id = document.getElementsByName('search_canal_id')[0].value;
                if (canal_id == '')
                  return;
                socket = new WebSocket('{$servidor_ws}/ws?canal_id=' + canal_id + '&chat_id={$chat_id}');

                socket.onopen = function(event) {
                    console.log('Conexão WebSocket estabelecida.');
                    atualiza_sinais();
                    atualiza_status();
                    atualiza_execucoes();
                };

                socket.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.channel == 'atualiza_sinais') {
                        atualiza_sinais();
                    } else if (data.channel == 'historico_usuario') {
                        atualiza_status();
                        atualiza_execucoes();
                    } 
                };

                socket.onerror = function(error) {
                    console.error('Erro na conexão WebSocket:', error);
                };

                socket.onclose = function(event) {
                    console.log('Conexão WebSocket fechada.');
                };
            }

            function finalizarEventSource() {
                if (socket) {
                    socket.close(1000, 'Fechamento pelo usuário');
                    socket = null;
                }
            }

            window.addEventListener('beforeunload', function() {
                finalizarEventSource();
            });

            inicializarEventSource();
JAVASCRIPT;
    }
}