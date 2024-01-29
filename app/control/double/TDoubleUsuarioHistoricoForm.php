<?php

class TDoubleUsuarioHistoricoForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TTransformationTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoublePagamentoHistorico';
    const DATABASE = 'double';

    private $datagrid;

    protected function onBuild($param)
    {
        $this->form->addFields(
            // [$label = $this->makeTLabel(['value' => 'Id'])],
            [$this->makeTHidden(['name' => 'id', 'editable' => false])],
            // [],
            // [],
        );
        $dataGrid = new stdClass;
        $dataGrid->name = 'datagrid';
        $dataGrid->pagenavigator = false;
        $dataGrid->columns = [
            ['name' => 'created_at', 'label' => 'Data', 'width' => '20%', 'align' => 'left', 'transformer' => Closure::fromCallable([$this, 'dateTimeTransformer'])],
            ['name' => 'plataforma_pagamento', 'label' => 'Plataforma', 'width' => '15%', 'align' => 'left'],
            ['name' => 'tipo', 'label' => 'Tipo', 'width' => '20%', 'align' => 'left'],
            ['name' => 'tipo_entrada', 'label' => 'Entrada', 'width' => '15%', 'align' => 'left'],
            ['name' => 'tipo_evento', 'label' => 'Evento', 'width' => '15%', 'align' => 'left'],
            ['name' => 'valor', 'label' => 'Valor', 'width' => '15%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'doubleTransformer'])],
        ];

        $panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagrid');

        $columns = $this->datagrid->getColumns();
        $columns[5]->enableTotal('sum', null, 2, '.', ',');

        $this->form->addContent([$panel]);
    }

    public function onEdit($param)
    {
        // $data = parent::onEdit($param);

        $historico = TUtils::openFakeConnection('double', function () use ($param) {
            return DoublePagamentoHistorico::where('usuario_id', '=', $param['id'])
                ->orderBy('created_at', 'desc')
                ->load();
        });

        $this->datagrid->clear();
        if ($historico) {
            // iterate the collection of active records
            foreach ($historico as $object) {
                // add the object inside the datagrid
                $this->datagrid->addItem($object);
            }
        }

        return $historico;
    }

    protected function getTitle()
    {
        return 'Histórico de pagamentos';
    }
}
