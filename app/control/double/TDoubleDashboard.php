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

class TDoubleDashboard extends TPage
{
    use TUIBuilderTrait;

    private $form;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_TDoubleDashboard');
        $this->form->setFormTitle('Filtros');
        $this->form->addExpandButton('');

        $this->form->addFields(
            [$this->makeTHidden(['name' => 'usuarios_canal', 'value' => 'N'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Plataforma'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'plataforma_id',
                    'label' => $label,
                    'database' => 'double',
                    'model' => 'DoublePlataforma',
                    'key' => 'id',
                    'display' => '[{idioma}] {nome}',
                ],
                function ($object) {
                    $object->setChangeAction(new TAction(array($this, 'onPlataformaChange')));
                }
            )],
            [$label = $this->makeTLabel(['value' => 'Canal'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'canal_id', 
                    'label' => $label, 
                    'database' => 'double', 
                    'required' => !isset($param['usuarios_canal']) ? false : $param['usuarios_canal'] == 'Y',
                    'model' => 'DoubleCanal', 
                    'key' => 'id', 
                    'display' => '{nome}',
                    'editable' => !isset($param['usuarios_canal']) ? false : $param['usuarios_canal'] == 'Y',
                    'width' => '100%'
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
                'indicator1' => TUtils::renderInfoBox('usuariosAtivos', 'Usuários Ativos', 'trophy', 'red', 0),
                'indicator2' => TUtils::renderInfoBox('totalTestesIniciados', 'Total Testes Iniciados', 'gamepad', 'aqua', 0),
                'indicator3' => TUtils::renderInfoBox('valorTotalAssinaturas', 'Valor Total Assinaturas', 'dollar-sign', 'orange',' R$ 0,00'),
            ]
        );

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TUtils::createXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($html1);
        $container->add($html2);

        parent::add($container);

        $this->form->setData( TSession::getValue('form_TDoubleDashboard_filter_data') );

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

            atualiza_contadores();

            setInterval( atualiza_contadores, 5000);
        ');
    }

    public function onSearch($param)
    {
        $object = $this->form->getData();
        TSession::setValue('form_TDoubleDashboard_filter_data', $object);
    }

    public static function doConsultar($param) 
    {
        $object = TSession::getValue('form_TDoubleDashboard_filter_data');
        if (!$object)
        {
            $object = new stdClass;
            $object->plataforma_id = '';
            $object->canal_id = '';
            $object->usuarios_canal = '';
            $object->data_inicio = date('Y-m-0');
            $object->data_fim = date('Y/m/t');
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
            $totalPagamentos     = DoublePagamentoHistorico::where('tipo_evento', '=', 'PAGAMENTO');
            $totalCancelamentos  = DoublePagamentoHistorico::where('tipo_evento', '=', 'CANCELAMENTO');
            $totalAssinaturas    = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'CANCELAMENTO']);

            if (isset($object->plataforma_id) and $object->plataforma_id)
            {
                $usuariosTotal       = $usuariosTotal->where('plataforma_id', '=', $object->plataforma_id);
                $usuariosAtivos      = $usuariosAtivos->where('plataforma_id', '=', $object->plataforma_id);
                $usuariosNovos       = $usuariosNovos->where('plataforma_id', '=', $object->plataforma_id);
                $totalTestes         = $totalTestes->where('plataforma_id', '=', $object->plataforma_id);
                $totalPagamentos     = $totalPagamentos->where('plataforma_id', '=', $object->plataforma_id);
                $totalCancelamentos  = $totalCancelamentos->where('plataforma_id', '=', $object->plataforma_id);
                $totalAssinaturas    = $totalAssinaturas->where('plataforma_id', '=', $object->plataforma_id);
            }

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

    public static function onPlataformaChange($param)
    {
        try
        {
            if (!empty($param['plataforma_id']))
            {
                $plataforma = TUtils::openFakeConnection('double', function() use ($param){
                    return new DoublePlataforma($param['plataforma_id'], false);
                });
                $param['usuarios_canal'] = $plataforma->usuarios_canal;
                if ($plataforma->usuarios_canal == 'Y') {
                    TCombo::enableField('form_TDoubleDashboard', 'canal_id');
                    $criteria = TCriteria::create( ['plataforma_id' => $param['plataforma_id'] ] );
                    TDBCombo::reloadFromModel('form_TDoubleDashboard', 'canal_id', 'double', 'DoubleCanal', 'plataforma_id', '{nome}', 'id', $criteria, TRUE);
                } else
                {
                    TCombo::clearField('form_DoubleUsuarioForm', 'plataforma_id');
                    TDBCombo::disableField('form_TDoubleDashboard', 'canal_id');
                }
            }
            else
            {
                TCombo::clearField('form_DoubleUsuarioForm', 'plataforma_id');
                TDBCombo::disableField('form_TDoubleDashboard', 'canal_id');
            }

            $data = new stdClass;
            $data->plataforma_id = $param['plataforma_id'];
            $data->canal_id = $param['canal_id'];
            $data->data_inicio = $param['data_inicio'];
            TForm::sendData('form_TDoubleDashboard', $data, False, False);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    // public function show()
    // {
    //     if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
    //     {
    //         if (func_num_args() > 0)
    //         {
    //             $this->onReload( func_get_arg(0) );
    //         }
    //         else
    //         {
    //             $this->onReload();
    //         }
    //     }
    //     parent::show();
    // }
}
