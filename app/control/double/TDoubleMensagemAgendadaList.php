<?php

use Adianti\Widget\Util\TDropDown;

class TDoubleMensagemAgendadaList extends TCustomStandardList
{
    private $idiomas;
    private $status;
    private $tipo_sinais;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->status = ['TODOS' => 'Todos', 'NOVO' => 'Novo', 'ATIVO' => 'Ativo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => ' Aguardando Pagamento']; 
        $this->ativo = ['Y' => 'Sim', 'N' => 'Não'];

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter('mensagem_direta', '=', 'A')
        );

        parent::__construct([
            'title'          => 'Mensagens agendadas',
            'database'       => 'double',
            'activeRecord'   => 'DoubleRecuperacaoMensagem',
            'defaultOrder'   => 'id',
            'formEdit'       => 'TDoubleMensagemAgendadaForm',
            'criteria'       => $criteria,
            'items'          => [
                [
                    'name'   => 'status',
                    'label'  => 'Para',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => $this->status],
                    'column' => ['width' => '15%', 'align' => 'LEFT', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_status'])]
                ],
                [
                    'name'   => 'titulo',
                    'label'  => 'título',
                    'widget' => ['class' => 'TEntry', 'operator' => 'like', 'width' => '100%'],
                    'column' => ['width' => '45%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'    => 'tipo_agendamento',
                    'label'   => 'Frequência',
                    'column'  => ['width' => '30%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_tipo_agendamento'])],
                ],
                [
                    'name'    => 'ativo',
                    'label'   => 'Ativo',
                    'widget'  => ['class' => 'TRadioGroup', 'items' => $this->ativo, 'operator' => '=', 'useButton' => true, 'layout_type' => 'horizontal', 'defaultOption' => false],
                    // 'column'  => ['width' => '10%', 'align' => 'center', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'status_sim_nao_transformer'])],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'transform_ativo'])]
                ],
            ]
        ]);
    }

    public function transform_status($value, $object, $row, $cell)
    {
        return $this->status[$value];
    }

    public function transform_ativo($value, $object, $row, $cell)
    {
        $cores  = ['Y' => '#008d4c', 'N' => '#dd4b39'];
        $cell->href = '#';

        if (empty($value))        
            return;

        $cell->href = '#';
        $dropdown = new TDropDown($this->ativo[$value], '');
        $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:' . $cores[$value];

        $addOpcao = function ($id, $nome, $valor, $cor) use ($dropdown)
        {
            $params = [
                'id' => $id,
                'campo' => 'ativo',
                'valor' => $valor,
                'offset' => $_REQUEST['offset'] ?? 0,
                'limit' => $_REQUEST['limit'] ?? 10,
                'page' => $_REQUEST['page'] ?? 1,
                'first_page' => $_REQUEST['first_page'] ?? 1,
                'register_state' => 'false'
            ];
    
            $dropdown->addAction($nome, new TAction([$this, 'doChangeValue'], $params), 'fas:circle '. $cor);
        };

        foreach ($this->ativo as $key => $value) {
            $addOpcao($object->id, $value, $key, $cores[$key]);
        }
       
        return $dropdown;
    }

    public function doChangeValue($param)
    {
        TUtils::openConnection(
            'double',
            function () use ($param) {
                $plataforma = new DoubleRecuperacaoMensagem($param['id'], false);
                $plataforma->{$param['campo']} = $param['valor'];
                $plataforma->save();
            }
        );

        $this->onReload($param);
    }

    public function transform_tipo_agendamento($value, $object, $row, $cell)
    {
        $freqs = ['M' => _t('Once a month'), 'W' => _t('Once a week'), 'D' => _t('Once a day'), 'F' => _t('Each five minutes')];
            
        $week_day_items = [];
        $week_day_items['1'] = _t('Sunday');
        $week_day_items['2'] = _t('Monday');
        $week_day_items['3'] = _t('Tuesday');
        $week_day_items['4'] = _t('Wednesday');
        $week_day_items['5'] = _t('Thursday');
        $week_day_items['6'] = _t('Friday');
        $week_day_items['7'] = _t('Saturday');
        
        $format = $freqs[$value];
        if ($value == 'D')
        {
            $format .= ' - ' . $object->hora . ':' . $object->minuto;
        }
        else if ($value == 'W')
        {
            $format .= ' - ' . $week_day_items[$object->dia_semana] . ' - ' . $object->hora . ':' . $object->minuto;
        }
        else if ($value == 'M')
        {
            $format .= ' - ' . $object->dia_mes . ' - ' . $object->hora . ':' . $object->minuto;
        }
        
        return $format;
    }
}
