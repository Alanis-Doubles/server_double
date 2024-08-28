<?php

use Adianti\Widget\Util\TDropDown;

class TDoublePlataformaList extends TCustomStandardList
{
    private $idiomas;
    private $status;
    private $tipo_sinais;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->idiomas = ['ptBR' => 'Português', 'en' => 'Inglês', 'es' => 'Espanhol'];
        $this->status  = ['PARADO' => 'Parado', 'INICIANDO' => 'Iniciando', 'EXECUTANDO' => 'Executando', 'PARANDO' => 'Parando'];
        $this->tipo_sinais = ['GERA' => 'Gera Sinais', 'NAO_GERA' => 'Não Gera Sinais', 'PROPAGA_OUTRO' => 'Propaga de Outro Canal', 'PROPAGA_VALIDA_SINAL' => 'Propaga e Valida Sinal'];

        parent::__construct([
            'title'          => 'Plataformas',
            'database'       => 'double',
            'activeRecord'   => 'DoublePlataforma',
            'defaultOrder'   => 'id',
            'formEdit'       => 'TDoublePlataformaForm',
            'items'          => [
                [
                    'name'    => 'id',
                    'label'   => 'Id',
                    'column'  => ['width' => '5%', 'align' => 'center', 'order' => true]
                ],
                [
                    'name'   => 'nome',
                    'label'  => 'Nome',
                    'widget' => ['class' => 'TEntry', 'operator' => '='],
                    'column' => ['width' => '25%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'   => 'idioma',
                    'label'  => 'Idioma',
                    'widget' => ['class' => 'TCombo', 'operator' => '=', 'items' => $this->idiomas, 'width' => '100%'],
                    'column' => ['width' => '10%', 'align' => 'left', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_idioma'])]
                ],
                [
                    'name'   => 'tipo_sinais',
                    'label'  => 'Tipo Sinais',
                    'widget' => ['class' => 'TCombo', 'operator' => '=', 'items' => $this->tipo_sinais, 'width' => '100%'],
                    'column' => ['width' => '25%', 'align' => 'left', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_tipo_sinais'])]
                ],
                [
                    'name'    => 'valor_minimo',
                    'label'   => 'Valor Mínimo',
                    'column'  => ['width' => '15%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'doubleTransformer'])]
                ],
                [
                    'name'   => 'ativo',
                    'label'  => 'Ativo',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => ['Y' => 'Sim', 'N' => 'Não']],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_ativo'])]
                ],
                [
                    'name'   => 'status_sinais',
                    'label'  => 'Status',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => $this->status],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_status'])]
                ],
            ],
            'actions' => [
                'actExecutar'  => ['label' => 'Executar/Parar serviço', 'image' => 'fas:play-circle red', 'fields' => ['id', 'nome', 'idioma', 'status_sinais'], 'action' => [$this, 'doExecutarServico'], 'action_params' =>  ['register_state' => 'false']],
            ]
        ]);
    }

    public function transform_ativo($value, $object, $row, $cell)
    {
        return $this->transform_sim_nao($value, $object, 'ativo', $cell);
    }

    public function transform_tipo_sinais($value, $object, $row, $cell)
    {
        if ($value)
            return $this->tipo_sinais[$value];
    }

    public function transform_idioma($value, $object, $row, $cell)
    {
        if ($value)
            return $this->idiomas[$value];
    }

    public function transform_status($value, $object, $row, $cell)
    {
        $cores = ['PARADO' => '#dd4b39', 'INICIANDO' => '#f39c12', 'EXECUTANDO' => '#00a65a', 'PARANDO' => '#ff851b'];
        $cell->href = '#';

        $button = new TElement('button');
        $button->add((empty($value) ? 'Parado' : $this->status[$value]));
        $button->{'data-toggle'} = 'dropdown';
        $button->{'class'}       = 'btn btn-default btn-sm';
        $button->{'style'}       = ';color:white;border-radius:5px;background:' . (empty($value) ? '#dd4b39' : $cores[$value]);

        return $button;
    }

    public function transform_sim_nao($value, $object, $field, $cell)
    {
        $cell->href = '#';
        $dropdown = new TDropDown(((empty($value) || $value == 'N') ? 'Não' : 'Sim'), '');
        $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:' . ((empty($value) || $value == 'N') ? '#dd4b39' : '#00a65a');

        $params = [
            'id' => $object->id,
            'campo' => $field,
            'valor' => 'Y',
            'offset' => $_REQUEST['offset'] ?? 0,
            'limit' => $_REQUEST['limit'] ?? 10,
            'page' => $_REQUEST['page'] ?? 1,
            'first_page' => $_REQUEST['first_page'] ?? 1,
            'register_state' => 'false'
        ];

        $dropdown->addAction('Sim', new TAction([$this, 'doChangeValue'], $params), 'fas:circle #00a65a');

        $params = [
            'id' => $object->id,
            'campo' => $field,
            'valor' => 'N',
            'offset' => $_REQUEST['offset'] ?? 0,
            'limit' => $_REQUEST['limit'] ?? 10,
            'page' => $_REQUEST['page'] ?? 1,
            'first_page' => $_REQUEST['first_page'] ?? 1,
            'register_state' => 'false'
        ];

        $dropdown->addAction('Não', new TAction([$this, 'doChangeValue'], $params), 'fas:circle #dd4b39');

        return $dropdown;
    }

    public function doChangeValue($param)
    {
        TUtils::openConnection(
            'double',
            function () use ($param) {
                $plataforma = new DoublePlataforma($param['id'], false);
                $plataforma->ativo = $param['valor'];
                $plataforma->save();
            }
        );

        $this->onReload($param);
    }    
    
    public function doExecutarServico($param) {
        unset($param['class']);
        unset($param['method']);

        $plataforma = TUtils::openFakeConnection('double', function() use ($param){
            return new DoublePlataforma($param['id'], False);
        });

        if ($plataforma->tipo_sinais == 'PROPAGA_OUTRO') {
            new TMessage('error', 'Plataforma não configurada para buscar de sinais.');
            return;
        }
        
        if ($param['status_sinais'] == 'EXECUTANDO') {
            $message = 'Deseja parar a execução do servço?';
            $action = new TAction([$this, 'pararServico'], $param);
        } else {
            $message = 'Deseja iniciar a execução do serviço?';
            $action = new TAction([$this, 'iniciarServico'], $param);
        }
        
        new TQuestion($message, $action);
    }

    public function pararServico($param) {
        unset($param['class']);
        unset($param['method']);

        $param['offset'] = $_REQUEST['offset'] ?? 0;
        $param['limit'] = $_REQUEST['limit'] ?? 10;
        $param['page'] = $_REQUEST['page'] ?? 1;
        $param['first_page'] = $_REQUEST['first_page'] ?? 1;
        $param['register_state'] = 'false';

        $plataforma = TUtils::openFakeConnection('double', function() use ($param){
            return new DoublePlataforma($param['id'], false);
        });
        TDoubleSinais::finalizar(['plataforma' => strtolower($plataforma->nome), 'idioma' => $plataforma->idioma]);

        new TMessage('info', 'Serviço parado.', new TAction([$this, 'onReload'], $param));
    }

    public function IniciarServico($param) {
        unset($param['class']);
        unset($param['method']);

        $param['offset'] = $_REQUEST['offset'] ?? 0;
        $param['limit'] = $_REQUEST['limit'] ?? 10;
        $param['page'] = $_REQUEST['page'] ?? 1;
        $param['first_page'] = $_REQUEST['first_page'] ?? 1;
        $param['register_state'] = 'false';

        TDoubleSinais::iniciar(['plataforma' => $param['nome'], 'idioma' => $param['idioma']]);

        new TMessage('info', 'Serviço iniciado.', new TAction([$this, 'onReload'], $param));
    }
}
