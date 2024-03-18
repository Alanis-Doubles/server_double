<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TFilter;
use Adianti\Registry\TSession;
use Adianti\Database\TCriteria;
use Adianti\Database\TRepository;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Template\THtmlRenderer;
use AdminLte\Widget\Container\TLTESmallBox;

class TRanking 
{
    use TUIBuilderTrait;

    public $datagrid;
    public $panel;

    public function __construct()
    {
        $dataGrid = new stdClass;
        $dataGrid->name = 'dataraking';
        $dataGrid->pagenavigator = false;
        $dataGrid->title = '<i class="fas fa-trophy green"></i>  Ranking das Estratégias ';
        $dataGrid->columns = [
            ['name' => 'nome', 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'regra', 'label' => 'Regra', 'width' => '35%', 'align' => 'left', 'transformer' => Closure::fromCallable(['TDoubleDashboard', 'transform_regra'])],
            ['name' => 'resultado', 'label' => 'Resultado', 'width' => '10%', 'align' => 'center', 'transformer' => Closure::fromCallable(['TDoubleDashboard', 'transform_resultado'])],
            ['name' => 'win', 'label' => 'Win', 'width' => '5%', 'align' => 'center'],
            ['name' => 'loss', 'label' => 'Loss', 'width' => '5%', 'align' => 'center'],
            ['name' => 'percentual', 'label' => '%', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_0', 'label' => 'G0', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_1', 'label' => 'G1', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_2', 'label' => 'G2', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_3', 'label' => 'G3', 'width' => '5%', 'align' => 'center'],
        ];

        // $dataGrid->actions = [
        //     'actEditar' => ['label' => 'Editar' , 'image' => 'far:edit blue', 'field' => 'estrategia_id', 'action' => ['TDoubleEstrategiaForm', 'onEdit'], 'action_params' => ['register_state' => 'false', 'fromClass' => 'TDoubleDashboard']],
        //     'actAtivar' => ['label' => 'Ativar/Inativar' , 'image' => 'fa:power-off orange', 'field' => 'estrategia_id', 'action' => ['TDoubleDashboard', 'onAtivarInativarEstrategia'], 'action_params' => ['register_state' => 'false']],
        // ];

        $this->panel = $this->makeTDataGrid($dataGrid);
        // $this->panel->addHeaderActionLink('', new TAction(['TDoubleEstrategiaForm', 'onInsert'], ['register_state' => 'false']), 'fa:plus');

        $this->datagrid = $this->getWidget('dataraking');
    }
}

class TDoubleDashboard extends TPage
{
    use TUIBuilderTrait;

    private $form;
    private $filterRanking;
    private $datagrid;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_TDoubleDashboard');
        $this->form->setFormTitle('Filtros');
        $this->form->addExpandButton('');

        $this->form->addFields(
            [$this->makeTHidden(['name' => 'usuarios_canal', 'value' => 'N'])],
            [$this->makeTHidden(['name' => 'data_ranking', 'value' => date('Y-m-d')])],
        );

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                '=',
                'GERA'
            )
        );
        
        $criteria->add(  new TFilter( 'ativo', '=', 'Y') );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Canal'])],
            [$this->makeTDBCombo(
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
                ]
            )],
        );
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Início'])],
            [$this->makeTDate(['name' => 'data_inicio','label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function($object) {
                $object->setValue(date('01/m/Y'));
            })],
            [$label = $this->makeTLabel(['value' => 'Fim'])],
            [$this->makeTDate(['name' => 'data_fim','label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function($object) {
                $object->setValue(date('t/m/Y'));
            })],
        );

        $btn = $this->form->addAction('Atualizar', new TAction([$this, 'onSearch'], $param), 'fa:sync');
        $btn->class = 'btn btn-sm btn-primary';

        $html1 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html1->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('totalUsuarios', 'Total de Usuários', 'users', 'green', 0),
                'indicator2' => TUtils::renderInfoBox('novosUsuarios', 'Novos de Usuários', 'user-plus', 'orange', 0),
                'indicator3' => TUtils::renderInfoBox('totalPlanosAssinados', 'Total Planos Assinado', 'dollar-sign', 'green', 0),
            ]
        );

        $html2 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html2->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('usuariosAtivos', 'Usuários Jogando', 'trophy', 'red', 0),
                'indicator2' => TUtils::renderInfoBox('totalTestesIniciados', 'Total Testes Iniciados', 'gamepad', 'aqua', 0),
                'indicator3' => TUtils::renderInfoBox('valorTotalAssinaturas', 'Valor Total Assinaturas', 'dollar-sign', 'orange',' R$ 0,00'),
            ]
        );

        $session = TSession::getValue('form_TDoubleDashboard_filter_data');

        // $panel = self::createRanking();
        $ranking = new TRanking();
        $panel = $ranking->panel;
        $this->datagrid = $ranking->datagrid;

        $this->filterRanking = new TForm('form_filer_ranking');
        $this->filterRanking->style = 'float:left;display:flex';
        $filterDataRanking = $this->makeTDate(['name' => 'data_ranking', 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd', 'change_action' => [$this, 'onChangeRanking']], function($object) use ($session){
            $object->setValue(date('d/m/Y'));
        });
        $this->filterRanking->add($filterDataRanking, true);

        $panel->addHeaderWidget($this->filterRanking);

        // $columns = $this->datagrid->getColumns();
        // $columns[3]->enableTotal('sum', null, 0, '.', ',');
        // $columns[4]->enableTotal('sum', null, 0, '.', ',');

        $barResult = new THtmlRenderer('app/resources/google_column_chart.html');
        $panelResultado = new TPanelGroup('Resultado dos últimos 7 dias');
        $panelResultado->add($barResult);
        $data = array();
        $data[] = [ 'Day', 'Value 1', 'Value 2', 'Value 3' ];
        $data[] = [ 'Day 1',   100,       120,       140 ];
        $data[] = [ 'Day 2',   120,       140,       160 ];
        $data[] = [ 'Day 3',   140,       160,       180 ];
        
        # PS: If you use values from database ($row['total'), 
        # cast to float. Ex: (float) $row['total']
        
        // replace the main section variables
        $barResult->enableSection('main', array('data'   => json_encode($data),
                                           'width'  => '100%',
                                           'height'  => '300px',
                                           'title'  => '',
                                           'ytitle' => 'Accesses', 
                                           'xtitle' => 'Day',
                                           'uniqid' => uniqid()));

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TUtils::createXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($html1);
        $container->add($html2);
        // $container->add(new TElement('br'));
        // $container->add($panelResultado);
        $container->add(new TElement('br'));
        $container->add($panel);

        parent::add($container);

        $this->form->setData( $session );
        $this->filterRanking->setData( $session );

        TScript::create('
            function atualiza_contadores() {
                $.get("engine.php?class=TDoubleDashboard&method=doConsultar&static=1", function(data) {
                    const options = { 
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                        style: "currency",
                        currency: "BRL"
                    };

                    const dados = JSON.parse(data);

                    document.querySelector("#totalUsuarios").textContent = dados["totalUsuarios"];
                    document.querySelector("#novosUsuarios").textContent = dados["novosUsuarios"];
                    document.querySelector("#totalPlanosAssinados").textContent = dados["totalPlanosAssinados"];
                    document.querySelector("#usuariosAtivos").textContent = dados["usuariosAtivos"];
                    document.querySelector("#totalTestesIniciados").textContent = dados["totalTestesIniciados"];
                    document.querySelector("#valorTotalAssinaturas").textContent = Number(dados["valorTotalAssinaturas"]).toLocaleString("pt-BR", options);;
                });
            }

            function atualiza_ranking() {
                
                $.get("engine.php?class=TDoubleDashboard&method=doConsultarRanking&static=1", function(data) {
                    
                    $("#dataraking tbody").remove();
                    $("#dataraking").append(data);
                });
            }

            atualiza_contadores();
            atualiza_ranking();

            setInterval( atualiza_contadores, 5000);
            setInterval( atualiza_ranking, 7000 );
        ');
    }

    public function onReload($param) {}

    public function onAtivarInativarEstrategia($param) 
    {
        TUtils::openConnection('double', function() use ($param){
            $estrategia = new DoubleEstrategia($param['estrategia_id'], false);
            if ($estrategia)
            {
                $estrategia->ativo = $estrategia->ativo == 'Y' ? 'N' : 'Y';
                $estrategia->save();
            }
        });
    }

    public static function onChangeRanking($param) 
    {
        $session = TSession::getValue('form_TDoubleDashboard_filter_data');
        if (!$session)
        {
            $session = new stdClass;
            $session->canal_id = '';
            $session->usuarios_canal = '';
            $session->data_inicio = date('Y-m-0');
            $session->data_fim = date('Y-m-t');
        }   
        
        $session->data_ranking = TDate::convertToMask($param['data_ranking'], 'dd/mm/yyyy', 'yyyy-mm-dd');
        TSession::setValue('form_TDoubleDashboard_filter_data', $session);
    }

    // public static function transform_resultado($value, $object, $row, $cell)
    // {
    //     $list["RED"] = "🔴";
    //     $list["BLACK"] = "⚫";
    //     $list["WHITE"] = "⚪️";

    //     if ($object->ativo == 'N')
    //         $row->style = 'text-decoration: line-through;';

    //     return str_replace(
    //         ['red', 'black', 'white', ' - '],
    //         [$list["RED"], $list["BLACK"], $list["WHITE"], ' '],
    //         $value
    //     );
    // }

    public static function transform_resultado($value, $object, $row, $cell)
    {
        if ($object->ativo == 'N')
            $row->style = 'text-decoration: line-through;';

        if ($value <> '') {
            if ($value == 'white') {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                $botao->enableSection( 'main', [] );
            } else {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                $botao->enableSection( 'main', ['cor' => ['red' => 'vermelho', 'black' => 'preto'][$value], 'value' => ''] );
            }

            return $botao;
        }
    }

    public static function transform_regra($value, $object, $row, $cell)
    {
        if ($value <> '') {
            if ($object->tipo == 'NUMERO') {
                if ($value == 0) {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                    $botao->enableSection( 'main', [] );
                } elseif ($value < 8) {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $botao->enableSection( 'main', ['cor' => 'vermelho', 'value' => $value] );
                } else {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $botao->enableSection( 'main', ['cor' => 'preto', 'value' => $value] );
                }
            } else {
                $cores = explode(' - ', $value);
                foreach ($cores as $key => $cor) {
                    if ($cor == 'white') {
                        $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                        $botao->enableSection( 'main', [] );
                    } else {
                        $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                        $botao->enableSection( 'main', ['cor' => ['red' => 'vermelho', 'black' => 'preto'][$cor], 'value' => ''] );
                    }
                    $cores[$key] = ['botao' => $botao];
                }

                $lista = new THtmlRenderer('app/resources/double/estrategia/retorno_lista.html');
                $lista->enableSection(
                    'main',
                    [
                        'botoes' => $cores,
                    ]
                );
                return $lista;
            }

            return $botao;
        }
    }

    public function onSearch($param)
    {
        $object = $this->form->getData();
        TSession::setValue('form_TDoubleDashboard_filter_data', $object);

        $filter = $this->filterRanking->getData();
    }

    public static function doConsultarRanking($paraam)
    {
        $raking = new TRanking;
        $datagrid = $raking->datagrid;

        $session = TSession::getValue('form_TDoubleDashboard_filter_data');
        if ($session and $session->canal_id)
        {
            try {
                $lista = TUtils::openFakeConnection('double',  function() use ($session){
                    $filtro1 = 'dh.canal_id = ' . $session->{'canal_id'};
                    $filtro2 = 'c.id = ' . $session->{'canal_id'};

                    if (!isset($session->data_ranking))
                        $session->data_ranking = date('Y-m-d');
                    if ((isset($session->data_ranking) and !$session->data_ranking))
                        $session->data_ranking = date('Y-m-d');
                    $data = $session->data_ranking;

                    $query = "SELECT tipo,
                                    plataforma_id,
                                    canal_id,
                                    nome,
                                    regra,
                                    resultado,
                                    ativo,
                                    win,
                                    loss,
                                    percentual,
                                    gale_0,
                                    gale_1,
                                    gale_2,
                                    gale_3,
                                    gale_4
                            FROM ( SELECT e.tipo,
                                        c.plataforma_id,
                                        e.canal_id,
                                        e.nome,
                                        e.regra,
                                        e.resultado,
                                        e.ativo,
                                        sum(win) win,
                                        sum(loss) loss,
                                        ROUND((sum(win)/(sum(win)+sum(loss)))*100, 2) percentual,
                                        sum(gale_0) gale_0,
                                        sum(gale_1) gale_1,
                                        sum(gale_2) gale_2,
                                        sum(gale_3) gale_3,
                                        sum(gale_4) gale_4
                                    FROM double_estrategia e
                                    JOIN double_canal c ON c.id = e.canal_id
                                    LEFT JOIN ( SELECT estrategia_id,
                                                        win,
                                                        loss,
                                                        if(gale = 0, 1, 0) gale_0,
                                                        if(gale = 1, 1, 0) gale_1,
                                                        if(gale = 2, 1, 0) gale_2,
                                                        if(gale = 3, 1, 0) gale_3,
                                                        if(gale = 4, 1, 0) gale_4
                                                    FROM ( SELECT if(dh.tipo = 'WIN', 1, 0) win,
                                                                        if(dh.tipo = 'LOSS', 1, 0) loss,
                                                                    dh.estrategia_id,
                                                                    (SELECT COUNT(1)
                                                                        FROM double_historico h
                                                                        JOIN double_canal c ON c.id = h.canal_id
                                                                        WHERE h.canal_id = dh.canal_id
                                                                        AND h.tipo = 'GALE'
                                                                        AND h.entrada_id = dh.entrada_id) gale
                                                                        --  AND h.estrategia_id = dh.estrategia_id
                                                                        --  AND h.id between dh.id - c.protecoes - 1 AND dh.id) gale
                                                                FROM double_historico dh
                                                                WHERE $filtro1
                                                                AND dh.tipo IN ('WIN', 'LOSS')
                                                                AND dh.entrada_id IS NOT null
                                                                AND DATE(dh.created_at) = '$data'
                                                        ) a
                                                ) b ON b.estrategia_id = e.id 
                                    WHERE $filtro2
                                    and e.usuario_id is NULL
                                    GROUP BY e.tipo, c.plataforma_id, e.canal_id, e.nome, e.regra, e.resultado, e.ativo, e.ordem 
                                    ORDER BY 10 DESC, 8 DESC, 9 ASC, 11 DESC, 12 DESC, 14 DESC, 15 DESC, e.ordem ASC
                                ) c
                            ";

                    $conn = TTransaction::get();
                    $list = TDatabase::getData(
                        $conn, 
                        $query
                    );

                    return $list;
                });

                foreach ($lista as $key => $value) {
                    $datagrid->addItem( (object) $value);
                }
                echo $datagrid->getBody();
            } catch (\Throwable $e) {
                DoubleErros::registrar(1, 'TDoubleDashboard', doConsultarRanking, e->getMessage());
            }
        } 
        else 
        {
            echo "";
        }

        
    }

    public static function doConsultar($param) 
    {
        $object = TSession::getValue('form_TDoubleDashboard_filter_data');
        if (!$object)
        {
            $object = new stdClass;
            $object->canal_id = '';
            $object->usuarios_canal = '';
            $object->data_inicio = date('Y-m-0');
            $object->data_fim = date('Y-m-t');
            $object->data_ranking = date('Y-m-d');
        }    

        if ($object->data_inicio)
            $object->data_inicio = TDate::convertToMask($object->data_inicio, 'dd/mm/yyyy', 'yyyy-mm-dd');
        if ($object->data_fim)
            $object->data_fim = TDate::convertToMask($object->data_fim, 'dd/mm/yyyy', 'yyyy-mm-dd');

        $dados = TUtils::openFakeConnection('double', function() use ($object){
            $usuariosTotal       = DoubleUsuario::where(1, '=', 1);
            $usuariosAtivos      = DoubleUsuario::where('robo_status', '=', 'EXECUTANDO');
            $usuariosNovos       = DoubleUsuario::where(1, '=', 1);
            $totalTestes         = DoubleUsuario::where('demo_jogadas', '<', 5);
            $totalPagamentos     = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'RENOVACAO']);
            $totalCancelamentos  = DoublePagamentoHistorico::where('tipo_evento', '=', 'CANCELAMENTO');
            $totalAssinaturas    = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'RENOVACAO', 'CANCELAMENTO']);

            if (isset($param['canal_id']) and $param['canal_id'])
            {
                $usuariosTotal       = $usuariosTotal->where('canal_id', '=', $object->canal_id);
                $usuariosAtivos      = $usuariosAtivos->where('canal_id', '=', $object->canal_id);
                $usuariosNovos       = $usuariosNovos->where('canal_id', '=', $object->canal_id);
                $totalTestes         = $totalTestes->where('canal_id', '=', $object->canal_id);
                $totalPagamentos     = $totalPagamentos->where('canal_id', '=', $object->canal_id);
                $totalCancelamentos  = $totalCancelamentos->where('canal_id', '=', $object->canal_id);
                $totalAssinaturas    = $totalAssinaturas->where('canal_id', '=', $object->canal_id);
            }

            $adicionarFiltroData = function(TRepository $objeto, $campo, $data_inicio, $data_fim)
            {
                if ($data_inicio and $data_fim) {
                    return $objeto->where("DATE({$campo})", 'between', [$data_inicio, $data_fim]);
                 }elseif ($data_inicio and !$data_fim) {
                    return $objeto->where("DATE({$campo})", '>=', $data_inicio);
                } elseif (!$data_inicio and $data_fim) {
                    return $objeto->where("DATE({$campo})", '<=', $data_fim);
                } else {
                    return $objeto->where("DATE({$campo})", 'between', [date('Y-m-01'), date('Y-m-t')]);
                }
            };

            $usuariosNovos       = $adicionarFiltroData($usuariosNovos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalTestes         = $adicionarFiltroData($totalTestes, 'demo_inicio', $object->data_inicio, $object->data_fim);
            $totalPagamentos     = $adicionarFiltroData($totalPagamentos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalCancelamentos  = $adicionarFiltroData($totalCancelamentos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalAssinaturas    = $adicionarFiltroData($totalAssinaturas, 'created_at', $object->data_inicio, $object->data_fim);

            $dados['totalUsuarios']         = $usuariosTotal->count() ?? 0;
            $dados['usuariosAtivos']        = $usuariosAtivos->count() ?? 0;
            $dados['novosUsuarios']         = $usuariosNovos->count() ?? 0;
            $dados['totalTestesIniciados']  = $totalTestes->count() ?? 0;
            $dados['totalPlanosAssinados']  = ($totalPagamentos->count() ?? 0) - ($totalCancelamentos->count() ?? 0);
            $dados['valorTotalAssinaturas'] = $totalAssinaturas->sumBy('valor') ?? 0;

            return json_encode($dados);
        });

        echo $dados;
    }
}
