<?php

use Adianti\Database\TFilter;
use Adianti\Widget\Wrapper\TDBCombo;

class TDoubleUsuarioList extends TCustomStandardList 
{
    private $idiomas;
    private $status;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->status = ['NOVO' => 'Novo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => 'Ag. Pagto.', 'ATIVO' => 'Ativo', 'INATIVO' => 'Inativo', 'EXPIRADO' => 'Expirado']; 
        $this->robo_status  = ['PARADO' => 'Parado', 'INICIANDO' => 'Iniciando', 'EXECUTANDO' => 'Executando', 'PARANDO' => 'Parando'];
               
        parent::__construct([
            'title'          => 'Usuarios',
            'database'       => 'double',
            'activeRecord'   => 'DoubleUsuario',
            'defaultOrder'   => 'id',
            'formEdit'       => 'TDoubleUsuarioForm',
            'items'          => [
                [
                    'name'   => 'plataforma_id',
                    'label'  => 'Plataforma',
                    'widget' => [
                        'class' => 'TDBCombo', 
                        'database' => 'double', 
                        'model' => 'DoublePlataforma', 
                        'key' => 'id', 
                        'display' => '[{idioma}] {nome}', 
                        'operator' => '=', 
                        'callback' => function ($object) {
                            $object->setChangeAction( new TAction(array($this, 'onPlataformaChange')) );
                        }
                    ],
                ],
                [
                    'name'   => 'canal_id',
                    'label'  => 'Canal',
                    'widget' => [
                        'class' => 'TDBCombo', 
                        'database' => 'double', 
                        'model' => 'DoubleCanal', 
                        'key' => 'id', 
                        'display' => '{nome}', 
                        'operator' => '=', 
                    ],
                ],
                [
                    'name'   => 'nome_completo',
                    'label'  => 'Nome',
                    'widget' => ['class' => 'TEntry', 'operator' => 'like', 'filter_name' => '(SELECT name FROM system_users u WHERE u.custom_code = double_usuario.chat_id)'],
                    'column' => ['width' => '25%', 'align' => 'left', 'order' => false]
                ],
                [
                    'name'   => 'plataforma->nome',
                    'label'  => 'Plataforma/Canal',
                    'column' => ['width' => '20%', 'align' => 'left', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'nomePlataformaTransformer'])]
                ],
                [
                    'name'   => 'nome_usuario',
                    'label'  => 'Usuário',
                    'widget' => ['class' => 'TEntry', 'operator' => 'like', 'filter_name' => '(SELECT login FROM system_users u WHERE u.custom_code = double_usuario.chat_id)'],
                    'column' => ['width' => '5%', 'align' => 'left', 'order' => false]
                ],
                [
                    'name'   => 'email',
                    'label'  => 'E-mail',
                    'widget' => ['class' => 'TEntry', 'operator' => 'like', 'filter_name' => '(SELECT email FROM system_users u WHERE u.custom_code = double_usuario.chat_id)'],
                    'column' => ['width' => '10%', 'align' => 'left', 'order' => false]
                ],
                [
                    'name'   => 'data_expiracao',
                    'label'  => 'Vencimento',
                    'column' => ['width' => '15%', 'align' => 'left', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'dateTransformer'])]
                ],
                [
                    'name'   => 'status',
                    'label'  => 'Status',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => $this->status],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'transform_status'])]
                ],
                [
                    'name'   => 'robo_status',
                    'label'  => 'Status Robô',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => $this->robo_status],
                    'column' => ['width' => '15%', 'align' => 'center', 'order' => false, 'transformer' => Closure::fromCallable([$this, 'transform_robo_status'])]
                ],
            ],
            'actions' => [
                // 'actEditar'     => ['visible' => false],
                'actExcluir'        => ['visible' => false],
                'actVisualizar'     => ['visible' => false],
                'actPagamento'      => ['label' => 'Registrar pagamento', 'image' => 'fas:money-bill-wave green', 'field' => 'id', 'action' => ['TDoubleUsuarioPagamentoForm', 'onInsert'], 'action_params' =>  ['register_state' => 'false', 'fromClass' => get_class($this)]],
                'actHistorico'      => ['label' => 'Histórico de pagamentos', 'image' => 'fas:history red', 'field' => 'id', 'action' => ['TDoubleUsuarioHistoricoForm', 'onView'], 'action_params' =>  ['register_state' => 'false', 'fromClass' => get_class($this)]],
                'actMsgRecuperacao' => ['label' => 'Mensagens de recuperação', 'image' => 'fas:volume-up fa-fw orange', 'field' => 'id', 'action' => ['TDoubleUsuarioRecuperacaoForm', 'onView'], 'action_params' =>  ['register_state' => 'false', 'fromClass' => get_class($this)]],
            ]
        ]);
        
    }

    public function nomePlataformaTransformer($value, $object, $row, $cell) 
    {
        $result = $object->plataforma->render('[{idioma}] {nome}');
        if ($object->plataforma->usuarios_canal == 'Y')
            $result .= ' / ' . $object->canal->nome;

        return $result;
    }
    
    public function transform_status($value, $object, $row, $cell)
    {
        $cores  = ['NOVO' => '#00a7d0', 'DEMO' => '#30bbbb', 'AGUARDANDO_PAGAMENTO' => '#ff7701', 'ATIVO' => '#008d4c', 'INATIVO' => '#d33724', 'EXPIRADO' => '#cA195a'];
        $cell->href = '#';

        if (empty($value))        
            return;

        $cell->href = '#';
        $dropdown = new TDropDown($this->status[$value], '');
        $dropdown->getButton()->style .= ';color:white;border-radius:5px;background:' . $cores[$value];

        $addOpcao = function ($id, $nome, $valor, $cor) use ($dropdown)
        {
            $params = [
                'id' => $id,
                'campo' => 'status',
                'valor' => $valor,
                'offset' => $_REQUEST['offset'] ?? 0,
                'limit' => $_REQUEST['limit'] ?? 10,
                'page' => $_REQUEST['page'] ?? 1,
                'first_page' => $_REQUEST['first_page'] ?? 1,
                'register_state' => 'false'
            ];
    
            $dropdown->addAction($nome, new TAction([$this, 'doChangeValue'], $params), 'fas:circle '. $cor);
        };

        foreach ($this->status as $key => $value) {
            $addOpcao($object->id, $value, $key, $cores[$key]);
        }
       
        return $dropdown;
    }

    public static function onPlataformaChange($param)
    {
        try
        {
            if (!empty($param['search_plataforma_id']))
            {
                $criteria = TCriteria::create( ['plataforma_id' => $param['search_plataforma_id'] ] );
                TDBCombo::reloadFromModel('form_search_TDoubleUsuarioList', 'search_canal_id', 'double', 'DoubleCanal', 'plataforma_id', '{nome}', 'id', $criteria, TRUE);
            }
            else
            {
                TCombo::clearField('form_search_TDoubleUsuarioList', 'search_plataforma_id');
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
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

    public function transform_robo_status($value, $object, $row, $cell)
    {
        $cores = ['PARADO' => '#dd4b39', 'INICIANDO' => '#f39c12', 'EXECUTANDO' => '#00a65a', 'PARANDO' => '#ff851b'];
        $cell->href = '#';

        $button = new TElement('button');
        $button->add((empty($value) ? 'Parado' : $this->robo_status[$value]));
        $button->{'data-toggle'} = 'dropdown';
        $button->{'class'}       = 'btn btn-default btn-sm';
        $button->{'style'}       = ';color:white;border-radius:5px;background:' . (empty($value) ? '#dd4b39' : $cores[$value]);

        return $button;
    }
}