<?php

use Adianti\Widget\Util\TDropDown;

class TDoubleEstrategiaList extends TCustomStandardList
{
    private $tipo;
    private $status;
    private $tipo_sinais;

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->tipo = ['COR' => 'Cor', 'NUMERO' => 'Número', 'SOMA' => 'Soma'];

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = (SELECT c.plataforma_id FROM double_canal c where c.id = double_estrategia.canal_id))',
                '=',
                'GERA'
            )
        );

        $criteria_canal = new TCriteria;
        $criteria_canal->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                '=',
                'GERA'
            )
        );
        
        $criteria_canal->add(  new TFilter( 'ativo', '=', 'Y') );

        parent::__construct([
            'title'          => 'Estratégias',
            'database'       => 'double',
            'activeRecord'   => 'DoubleEstrategia',
            'defaultOrder'   => 'ordem, id',
            'formEdit'       => 'TDoubleEstrategiaForm',
            'criteria'       => $criteria,
            'dataGrid'       => [
                'groupColumn' => [
                    'name' => 'agrupamento',
                    'mask' => '<b>Plataforma:</b>: <i>{agrupamento}</i>'
                ],
            ],
            'items'          => [
                [
                    'name'    => 'ordem',
                    'label'   => 'Ordem',
                    'widget' => ['class' => 'TEntry', 'operator' => '='],
                    'column'  => ['width' => '5%', 'align' => 'center', 'order' => true]
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
                        'criteria' => $criteria_canal
                    ],
                ],
                [
                    'name'   => 'nome',
                    'label'  => 'Nome',
                    'widget' => ['class' => 'TEntry', 'operator' => '='],
                    'column' => ['width' => '25%', 'align' => 'left', 'order' => true]
                ],
                [
                    'name'   => 'regra',
                    'label'  => 'Regra',
                    'column' => ['width' => '45%', 'align' => 'left', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_regra'])]
                ],
                [
                    'name'    => 'resultado',
                    'label'   => 'Resultado',
                    'column'  => ['width' => '15%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_resultado'])]
                ],
                [
                    'name'   => 'ativo',
                    'label'  => 'Ativo',
                    'widget' => ['class'  => 'TCombo', 'operator' => '=', 'items' => ['Y' => 'Sim', 'N' => 'Não']],
                    'column' => ['width' => '10%', 'align' => 'center', 'order' => true, 'transformer' => Closure::fromCallable([$this, 'transform_ativo'])]
                ],
            ],
            'actions' => [
                'actExcluir'        => ['visible' => false],
                // 'actVisualizar'     => ['visible' => false],
            ]
        ]);
    }

    public function transform_resultado($value, $object, $row, $cell)
    {
        if ($value <> '') {
            if ($value == 'white') {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                $botao->enableSection( 'main', [] );
            } else {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                $botao->enableSection( 'main', ['cor' => ['red' => 'vermelho', 'black' => 'preto'][$value], 'value' => ''] );
            }

            return $botao;
        }
    }

    public function transform_regra($value, $object, $row, $cell)
    {
        if ($value <> '') {
            if ($object->tipo == 'NUMERO') {
                if ($value == 0) {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                    $botao->enableSection( 'main', [] );
                } elseif ($value < 8) {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $botao->enableSection( 'main', ['cor' => 'vermelho', 'value' => $value] );
                } else {
                    $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $botao->enableSection( 'main', ['cor' => 'preto', 'value' => $value] );
                }
            } else {
                $cores = explode(' - ', $value);
                foreach ($cores as $key => $cor) {
                    if ($cor == 'white') {
                        $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                        $botao->enableSection( 'main', [] );
                    } else {
                        $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                        $botao->enableSection( 'main', ['cor' => ['red' => 'vermelho', 'black' => 'preto'][$cor], 'value' => ''] );
                    }
                    $cores[$key] = ['botao' => $botao];
                }

                $lista = new THtmlRenderer('app/resources/double/estrategia/retorno_lista.html');
                $lista->enableSection(
                    'main',
                    [
                        'botoes' => $cores,
                    ]
                );
                return $lista;
            }

            return $botao;
        }
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
                $plataforma = new DoubleEstrategia($param['id'], false);
                $plataforma->{$param['campo']} = $param['valor'];
                $plataforma->save();
            }
        );

        $this->onReload($param);
    }
}
