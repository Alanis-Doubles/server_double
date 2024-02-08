<?php

class TDoubleUsuarioRecuperacaoForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TTransformationTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleRecuperacaoUsuario';
    const DATABASE = 'double';

    private $datagrid;
    private $status;

    protected function onBuild($param)
    {
        $this->status = ['NOVO' => 'Novo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => 'Aguardando pagamento', 'ATIVO' => 'Ativo', 'INATIVO' => 'Inativo', 'EXPIRADO' => 'Expirado']; 
        
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id', 'editable' => false])],
        );
        $dataGrid = new stdClass;
        $dataGrid->name = 'datagrid';
        $dataGrid->pagenavigator = false;
        $dataGrid->style = 'min-width: 600px';
        $dataGrid->columns = [
            ['name' => 'created_at', 'label' => 'Data', 'width' => '20%', 'align' => 'left', 'transformer' => Closure::fromCallable([$this, 'dateTimeTransformer'])],
            ['name' => 'recuperacao_mensagem->status', 'label' => 'Status', 'width' => '15%', 'align' => 'left', 'transformer' => Closure::fromCallable([$this, 'transform_status'])],
            ['name' => 'recuperacao_mensagem->mensagem', 'label' => 'Mensagem', 'width' => '65%', 'align' => 'left'],
        ];

        $panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagrid');

        $this->form->addContent([$panel]);
    }

    public function transform_status($value, $object, $row, $cell)
    {
        return $this->status[$value];
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
