<?php

use Adianti\Widget\Util\TDropDown;

class TDoubleRecuperacaoMenagemList extends TCustomStandardList
{
    private $idiomas;
    private $status;
    private $tipo_sinais;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->status = ['NOVO' => 'Novo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => 'Aguardando pagamento', 'ATIVO' => 'Ativo', 'INATIVO' => 'Inativo', 'EXPIRADO' => 'Expirado']; 

        parent::__construct([
            'title'          => 'Mensagens de recuperação',
            'database'       => 'double',
            'activeRecord'   => 'DoubleRecuperacaoMensagem',
            'defaultOrder'   => 'id',
            'formEdit'       => 'TDoubleRecuperacaoMensagemForm',
            'items'          => [
                [
                    'name'   => 'status',
                    'label'  => 'Status',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => $this->status],
                    'column' => ['width' => '15%', 'align' => 'LEFT', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_status'])]
                ],
                [
                    'name'   => 'ordem',
                    'label'  => 'Ordem',
                    'widget' => ['class' => 'TEntry', 'operator' => '=', 'width' => '100%'],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => true]
                ],
                [
                    'name'   => 'mensagem',
                    'label'  => 'Mensagem',
                    'widget' => ['class' => 'TEntry', 'operator' => 'like', 'width' => '100%'],
                    'column' => ['width' => '65%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'    => 'horas',
                    'label'   => 'Tempo',
                    'column'  => ['width' => '10%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_tempo'])]
                ],
            ],
        ]);
    }

    public function transform_status($value, $object, $row, $cell)
    {
        return $this->status[$value];
    }


    public function transform_tempo($value, $object, $row, $cell)
    {
        return $value . ($object->tipo_tempo == 'HORA' ? 'h' : 'm');
    }
}
