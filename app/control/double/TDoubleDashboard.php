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

    private $formFilter;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->formFilter = new BootstrapFormBuilder('form_filter');
        $this->formFilter->setFormTitle('Filtros');
        $this->formFilter->addExpandButton('');

        $this->formFilter->addFields(
            [$this->makeTHidden(['name' => 'usuarios_canal', 'value' => 'N'])],
        );

        $this->formFilter->addFields(
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
        $this->formFilter->addFields(
            [$label = $this->makeTLabel(['value' => 'Início'])],
            [$this->makeTDate(['name' => 'data_inicio','label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function($object) {
                $object->setValue(date('01/m/Y'));
            })],
            [$label = $this->makeTLabel(['value' => 'Fim'])],
            [$this->makeTDate(['name' => 'data_fim','label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function($object) {
                $object->setValue(date('t/m/Y'));
            })],
        );

        $btn = $this->formFilter->addAction('Atualizar', new TAction([$this, 'onReload'], $param), 'fa:sync');
        $btn->class = 'btn btn-sm btn-primary';

        $dados = $this->consultarDados($param);

        $html1 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html1->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('Total de Usuários', 'users', 'green', $dados['totalUsuarios'] ? $dados['totalUsuarios'] : 0),
                'indicator2' => TUtils::renderInfoBox('Novos de Usuários', 'user-plus', 'orange', $dados['novosUsuarios'] ? $dados['novosUsuarios'] : 0),
                'indicator3' => TUtils::renderInfoBox('Total Planos Assinado', 'dollar-sign', 'green', $dados['totalPlanosAssinados'] ? $dados['totalPlanosAssinados'] : 0),
            ]
        );

        $html2 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html2->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('Usuários Ativos', 'trophy', 'red', $dados['usuariosAtivos'] ? $dados['usuariosAtivos'] : 0),
                'indicator2' => TUtils::renderInfoBox('Total Testes Iniciados', 'gamepad', 'aqua', $dados['totalTestesIniciados'] ? $dados['totalTestesIniciados'] : 0),
                'indicator3' => TUtils::renderInfoBox('Valor Total Assinaturas', 'dollar-sign', 'orange',' R$ ' . number_format($dados['valorTotalAssinaturas'] ? $dados['valorTotalAssinaturas'] : 0, 2, ',', '.')),
            ]
        );

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TUtils::createXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->formFilter);
        $container->add($html1);
        $container->add($html2);

        parent::add($container);

        $data = new stdClass;
        $data->plataforma_id = TSession::getValue('dasboard_platafora_id');
        $data->canal_id = TSession::getValue('dasboard_canal_id');
        $data->data_inicio = TSession::getValue('dasboard_data_inicio');
        $data->data_fim = TSession::getValue('dasboard_data_fim');

        if (!$data->data_inicio)
            $data->data_inicio = date('01/m/Y');
        else
            $data->data_inicio = TDate::convertToMask($data->data_inicio, 'yyyy-mm-dd', 'dd/mm/yyyy');

        if (!$data->data_fim)
            $data->data_fim = date('t/m/Y');
        else
            $data->data_fim = TDate::convertToMask($data->data_fim, 'yyyy-mm-dd', 'dd/mm/yyyy');
        
        TForm::sendData('form_filter', $data, False, False);
    }

    public function onReload($param)
    {
    //     AdiantiCoreApplication::gotoPage(self::class, null, $param);
    }

    public function consultarDados($param) 
    {
        if (isset($param['data_inicio']))
            $param['data_inicio'] = TDate::convertToMask($param['data_inicio'], 'dd/mm/yyyy', 'yyyy-mm-dd');
        if (isset($param['data_fim']))
            $param['data_fim'] = TDate::convertToMask($param['data_fim'], 'dd/mm/yyyy', 'yyyy-mm-dd');
    
        if (isset($param['plataforma_id']) and $param['plataforma_id'])
            TSession::setValue('dasboard_platafora_id', $param['plataforma_id']);
        if (isset($param['canal_id']) and $param['canal_id'])
            TSession::setValue('dasboard_canal_id', $param['canal_id']);
        if (isset($param['data_inicio']))
             TSession::setValue('dasboard_data_inicio', $param['data_inicio']);
        if (isset($param['data_fim']))
             TSession::setValue('dasboard_data_fim', $param['data_fim']);

        return TUtils::openFakeConnection('double', function() use ($param){
            $usuariosTotal       = DoubleUsuario::where(1, '=', 1);
            $usuariosAtivos      = DoubleUsuario::where('robo_status', '=', 'EXECUTANDO');
            $usuariosNovos       = DoubleUsuario::where(1, '=', 1);
            $totalTestes         = DoubleUsuario::where('demo_jogadas', '<', 5);
            $totalPagamentos     = DoublePagamentoHistorico::where('tipo_evento', '=', 'PAGAMENTO');
            $totalCancelamentos  = DoublePagamentoHistorico::where('tipo_evento', '=', 'CANCELAMENTO');
            $totalAssinaturas    = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'CANCELAMENTO']);

            if (isset($param['plataforma_id']) and $param['plataforma_id'])
            {
                $usuariosTotal       = $usuariosTotal->where('plataforma_id', '=', $param['plataforma_id']);
                $usuariosAtivos      = $usuariosAtivos->where('plataforma_id', '=', $param['plataforma_id']);
                $usuariosNovos       = $usuariosNovos->where('plataforma_id', '=', $param['plataforma_id']);
                $totalTestes         = $totalTestes->where('plataforma_id', '=', $param['plataforma_id']);
                $totalPagamentos     = $totalPagamentos->where('plataforma_id', '=', $param['plataforma_id']);
                $totalCancelamentos  = $totalCancelamentos->where('plataforma_id', '=', $param['plataforma_id']);
                $totalAssinaturas    = $totalAssinaturas->where('plataforma_id', '=', $param['plataforma_id']);
            }

            if (isset($param['canal_id']) and $param['canal_id'])
            {
                $usuariosTotal       = $usuariosTotal->where('canal_id', '=', $param['canal_id']);
                $usuariosAtivos      = $usuariosAtivos->where('canal_id', '=', $param['canal_id']);
                $usuariosNovos       = $usuariosNovos->where('canal_id', '=', $param['canal_id']);
                $totalTestes         = $totalTestes->where('canal_id', '=', $param['canal_id']);
                $totalPagamentos     = $totalPagamentos->where('canal_id', '=', $param['canal_id']);
                $totalCancelamentos  = $totalCancelamentos->where('canal_id', '=', $param['canal_id']);
                $totalAssinaturas    = $totalAssinaturas->where('canal_id', '=', $param['canal_id']);
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

            $usuariosAtivos      = $adicionarFiltroData($usuariosAtivos, 'created_at', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);
            $usuariosNovos       = $adicionarFiltroData($usuariosNovos, 'created_at', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);
            $totalTestes         = $adicionarFiltroData($totalTestes, 'demo_inicio', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);
            $totalPagamentos     = $adicionarFiltroData($totalPagamentos, 'created_at', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);
            $totalCancelamentos  = $adicionarFiltroData($totalCancelamentos, 'created_at', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);
            $totalAssinaturas    = $adicionarFiltroData($totalAssinaturas, 'created_at', isset($param['data_inicio']) ? $param['data_inicio'] : null, isset($param['data_fim']) ? $param['data_fim'] : null);

            $dados['totalUsuarios']         = $usuariosTotal->count();
            $dados['usuariosAtivos']        = $usuariosAtivos->count();
            $dados['novosUsuarios']         = $usuariosNovos->count();
            $dados['totalTestesIniciados']  = $totalTestes->count();
            $dados['totalPlanosAssinados']  = $totalPagamentos->count() - $totalCancelamentos->count();
            $dados['valorTotalAssinaturas'] = $totalAssinaturas->sumBy('valor');

            return $dados;
        });
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
                    TCombo::enableField('form_filter', 'canal_id');
                    $criteria = TCriteria::create( ['plataforma_id' => $param['plataforma_id'] ] );
                    TDBCombo::reloadFromModel('form_filter', 'canal_id', 'double', 'DoubleCanal', 'plataforma_id', '{nome}', 'id', $criteria, TRUE);
                } else
                {
                    TCombo::clearField('form_DoubleUsuarioForm', 'plataforma_id');
                    TDBCombo::disableField('form_filter', 'canal_id');
                }
            }
            else
            {
                TCombo::clearField('form_DoubleUsuarioForm', 'plataforma_id');
                TDBCombo::disableField('form_filter', 'canal_id');
            }

            $data = new stdClass;
            $data->plataforma_id = $param['plataforma_id'];
            $data->canal_id = $param['canal_id'];
            $data->data_inicio = $param['data_inicio'];
            $data->data_fim = $param['data_fim'];
            $data->usuarios_canal = $param['usuarios_canal'];
            TForm::sendData('form_filter', $data, False, False);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
