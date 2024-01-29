<?php

use Adianti\Control\TAction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Dialog\TQuestion;

class TDoubleCanalList extends TCustomStandardList 
{
    private $idiomas;
    private $status;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->status  = ['PARADO' => 'Parado', 'INICIANDO' => 'Iniciando', 'EXECUTANDO' => 'Executando', 'PARANDO' => 'Parando'];
        
        // $filterCount = TSession::getValue(get_class($this).'_filter_counter');
        // if (!$filterCount) {
        //     $plataforma = TUtils::openFakeConnection('double', function () {
        //         return DoublePlataforma::first();
        //     });

        //     if ($plataforma) {
        //         TSession::setValue(get_class($this).'_filter_counter', 1);
        //         $filter = new TFilter('plataforma_id', '=', $plataforma->id);
        //         TSession::setValue("DoubleCanal_filter_0", $filter);
        //     }
        // }

        parent::__construct([
            'title'          => 'Canais',
            'database'       => 'double',
            'activeRecord'   => 'DoubleCanal',
            'defaultOrder'   => 'id',
            'formEdit'       => 'TDoubleCanalForm',
            'items'          => [
                [
                    'name'   => 'plataforma_id',
                    'label'  => 'Plataforma',
                    'widget' => ['class' => 'TDBCombo', 'database' => 'double', 'model' => 'DoublePlataforma', 'key' => 'id', 'display' => '[{idioma}] {nome}', 'operator' => '='],
                    // 'filter' => ['width' => '250', 'height' => '100%']
                ],
                [
                    'name'   => 'nome',
                    'label'  => 'Nome',
                    'widget' => ['class' => 'TEntry', 'operator' => '='],
                    'column' => ['width' => '50%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'   => 'channel_id',
                    'label'  => 'Id Canal',
                    'widget' => ['class' => 'TEntry', 'operator' => '=', 'mask' => '9!'],
                    'column' => ['width' => '20%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'   => 'protecoes',
                    'label'  => 'Proteções',
                    'widget' => ['class' => 'TEntry', 'operator' => '=', 'mask' => '9!'],
                    'column' => ['width' => '10%', 'align' => 'left', 'order' => true]
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
                'actExecutar'  => ['label' => 'Executar/Parar serviço', 'image' => 'fas:play-circle red', 'fields' => ['id', '*'], 'action' => [$this, 'doExecutarServico'], 'action_params' =>  ['register_state' => 'false']],
            ]
        ]);
        
    }
    
    public function transform_status($value, $object, $row, $cell)
    {
        $cores = ['PARADO' => '#dd4b39', 'INICIANDO' => '#f39c12', 'EXECUTANDO' => '#00a65a', 'PARANDO' => '#ff851b'];
        $cell->href = '#';

        $button = new TElement('button');
        $button->add((empty($value) ? 'Parado' : $this->status[$value]));
        $button->{'class'}       = 'btn btn-default btn-sm';
        $button->{'style'}       = ';color:white;border-radius:5px;background:' . (empty($value) ? '#dd4b39' : $cores[$value]);
        return $button;
    }

    public function doChangeValue($param)
    {
        TUtils::openConnection(
            'double',
            function () use ($param) {
                $plataforma = new DoubleUsuario($param['id'], false);
                $plataforma->{$param['campo']} = $param['valor'];
                $plataforma->save();
            }
        );

        $this->onReload($param);
    }

    public function transform_ativo($value, $object, $row, $cell)
    {
        return $this->transform_sim_nao($value, $object, 'ativo', $cell);
    }

    public function transform_sim_nao($value, $object, $field, $cell)
    {
        $cell->href = '#';
        $dropdown = new TDropDown(((empty($value) || $value == 'N') ? 'Não' : 'Sim'), '');
        $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:' . ((empty($value) || $value == 'N') ? '#dd4b39' : '#00a65a');

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

        $addOpcao($object->id, 'Sim', 'Y', '#00a65a');
        $addOpcao($object->id, 'Não', 'N', '#dd4b39');

        return $dropdown;
    }

    public function doExecutarServico($param) {
        unset($param['class']);
        unset($param['method']);

        $plataforma = TUtils::openFakeConnection('double', function() use ($param){
            return new DoublePlataforma($param['plataforma_id'], False);
        });

        if ($plataforma->tipo_sinais == 'NAO_GERA') {
            new TMessage('error', 'Plataforma não configurada para geração de sinais.');
            return;
        }
        $param['tipo_sinais'] = $plataforma->tipo_sinais;

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

        TDoubleSinais::finalizar_canal(['canal_id' => $param['id']]);

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

        $token = TUtils::openFakeConnection('permission', function () {
            $login = TSession::getValue('login');
            $user = SystemUser::validate($login);
            return ApplicationAuthenticationRestService::getToken($user);
        });

        $data = new stdClass;
        $data->token = $token;
        $data->plataforma = TUtils::openFakeConnection('double', function() use ($param){
            return new DoublePlataforma($param['plataforma_id'], false);
        });
        $data->tipo = 'cmd';
        $data->canal =  TUtils::openFakeConnection('double', function() use ($param){
            return new DoubleCanal($param['id'], false);
        });

        if ($param['tipo_sinais'] == 'GERA')
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal', $data);
        else    
            TDoubleUtils::cmd_run('TDoubleSinais', 'executar_canal_propagar_sinal', $data);

        new TMessage('info', 'Serviço iniciado.', new TAction([$this, 'onReload'], $param));
    }
}