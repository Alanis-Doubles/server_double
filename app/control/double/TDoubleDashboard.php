<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TFilter;
use Adianti\Database\TCriteria;
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
            [$label = $this->makeTLabel(['value' => 'Plataforma'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'plataforma_id',
                    'label' => $label,
                    'database' => 'unit_database',
                    'model' => 'DoublePlataforma',
                    'key' => 'id',
                    'display' => '[{idioma}] {nome}',
                ],
                // function ($object) {
                //     $object->setChangeAction(new TAction(array($this, 'onReload')));
                // }
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
                'indicator1' => TUtils::renderInfoBox('Usuários Ativos', 'trophy', 'red', $dados['usuariosAtivos'] ? $dados['totalUsuarios'] : 0),
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
    }

    public function onReload($param)
    {
    //     AdiantiCoreApplication::gotoPage(self::class, null, $param);
    }

    public function consultarDados($param) {
        return TUtils::openFakeConnection('unit_database', function() use ($param){
            $filterPlataforma = null;
            // $filterData = null;

            if (isset($param['plataforma_id']) and $param['plataforma_id'])
                $filterPlataforma = TCriteria::create(['plataforma_id' => 1]);

            if (isset($param['data_inicio']) and isset($param['data_inicio'])) {
                $filterData = new TFilter('created_at', 'between', $param['data_inicio'], $param['data_fim']);
             }elseif (isset($param['data_inicio']) and !isset($param['data_inicio'])) {
                $filterData = new TFilter('created_at', '>=', $param['data_inicio']);
            } elseif (!isset($param['data_inicio']) and isset($param['data_inicio'])) {
                $filterData = new TFilter('created_at', '<=', $param['data_fim']);
            } else {
                $filterData = new TFilter('created_at', 'between', date('Y-m-01'), date('Y-m-t'));
            }

            $criteria = new TCriteria;
            if ($filterPlataforma) {
                $criteria->add($filterPlataforma);
            }
            $dados['totalUsuarios'] = DoubleUsuario::count($criteria);
            $dados['usuariosAtivos'] = DoubleUsuario::setCriteria(clone $criteria)
                ->where('robo_status', '=', 'EXECUTANDO')
                ->count();

            $criteria->add($filterData);
            $dados['novosUsuarios'] = DoubleUsuario::setCriteria(clone $criteria)
                ->count();

            $pagamentos = DoublePagamentoHistorico::setCriteria(clone $criteria)
                ->where('tipo_evento', '=', 'PAGAMENTO')
                ->count();
            $cancelamentos = DoublePagamentoHistorico::setCriteria(clone $criteria)
                ->where('tipo_evento', '=', 'CANCELAMENTO')
                ->count();
            $dados['totalPlanosAssinados'] = $pagamentos - $cancelamentos;

            $dados['valorTotalAssinaturas'] = DoublePagamentoHistorico::setCriteria(clone $criteria)
                ->where('tipo_evento', 'in', ['PAGAMENTO', 'CANCELAMENTO'])
                ->sumBy('valor');


            if (isset($param['data_inicio']) and isset($param['data_inicio'])) {
                $filterData = new TFilter('demo_inicio', 'between', $param['data_inicio'], $param['data_fim']);
             }elseif (isset($param['data_inicio']) and !isset($param['data_inicio'])) {
                $filterData = new TFilter('demo_inicio', '>=', $param['data_inicio']);
            } elseif (!isset($param['data_inicio']) and isset($param['data_inicio'])) {
                $filterData = new TFilter('demo_inicio', '<=', $param['data_fim']);
            } else {
                $filterData = new TFilter('demo_inicio', 'between', date('Y-m-01'), date('Y-m-t'));
            }

            $criteria = new TCriteria;
            if ($filterPlataforma) {
                $criteria->add($filterPlataforma);
            }
            $criteria->add($filterData);
            $dados['totalTestesIniciados'] = DoubleUsuario::setCriteria($criteria)
                ->where('robo_status', '=', 'EXECUTANDO')
                ->count($criteria);

            return $dados;
        });
    }
}
