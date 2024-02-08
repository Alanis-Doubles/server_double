<?php

class TDoubleUsuarioRecuperacaoForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TTransformationTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleRecuperacaoUsuario';
    const DATABASE = 'double';

    private $datagrid;

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id', 'editable' => false])],
        );
        $dataGrid = new stdClass;
        $dataGrid->name = 'datagrid';
        $dataGrid->pagenavigator = false;
        $dataGrid->style = 'min-width: 600px';
        $dataGrid->columns = [
            ['name' => 'created_at', 'label' => 'Data', 'width' => '20%', 'align' => 'left', 'transformer' => Closure::fromCallable([$this, 'dateTimeTransformer'])],
            ['name' => 'recuperacao_mensagem->mensagem', 'label' => 'Plataforma', 'width' => '80%', 'align' => 'left'],
        ];

        $panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagrid');

        // $columns = $this->datagrid->getColumns();
        // $columns[5]->enableTotal('sum', null, 2, '.', ',');

        $this->form->addContent([$panel]);
    }

    public function onEdit($param)
    {
        // $data = parent::onEdit($param);

        $historico = TUtils::openFakeConnection('double', function () use ($param) {
            return DoubleRecuperacaoUsuario::where('usuario_id', '=', $param['id'])
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
        return 'Mensagens de recuperação';
    }
}
