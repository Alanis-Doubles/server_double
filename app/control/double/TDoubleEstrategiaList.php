<?php

use Adianti\Widget\Util\TDropDown;

class TDoubleEstrategiaList extends TCustomStandardList
{
    private $tipo;
    private $status;
    private $tipo_sinais;

    const BOTOES = ['white' => 'branco', 'red' => 'vermelho', 'black' => 'preto', 'other' => 'azul', 'break' => 'parar'];

    use TTransformationTrait;

    public function __construct($param)
    {
        $this->tipo = ['COR' => 'Cor', 'NUMERO' => 'Número', 'SOMA' => 'Soma'];

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = (SELECT c.plataforma_id FROM double_canal c where c.id = double_estrategia.canal_id))',
                'IN',
                ['GERA', 'PROPAGA_VALIDA_SINAL']
            )
        );

        $criteria->add( new TFilter('usuario_id', 'is', null) );

        $criteria_canal = new TCriteria;
        $criteria_canal->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                'IN',
                ['GERA', 'PROPAGA_VALIDA_SINAL']
            )
        );
        
        $criteria_canal->add(  new TFilter( 'ativo', '=', 'Y') );

        parent::__construct([
            'title'          => 'Estratégias',
            'database'       => 'double',
            'activeRecord'   => 'DoubleEstrategia',
            'defaultOrder'   => 'canal_id, ativo, ordem, id',
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
        ]);
    }

    public function criarBotaoInserir($param, $panel) {
        unset($param['class']);
        unset($param['method']);
        $param['register_state'] = 'false';
        $param['fromClass'] = get_class($this);
        
        $dropdown = new TDropDown('Inserir', 'fa:pluss');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( 'Regra de COR', new TAction([$this, 'doIncluir'], array_merge($param, ['tipo' => 'COR'])) );
        $dropdown->addAction( 'Regra de NÚMERO', new TAction([$this, 'doIncluir'], array_merge($param, ['tipo' => 'NUMERO'])) );
        $dropdown->addAction( 'Regra de SOMA', new TAction([$this, 'doIncluir'], array_merge($param, ['tipo' => 'SOMA'])) );
        $panel->addHeaderWidget( $dropdown );
    }

    public function doIncluir($param){
        if ($search_canal = TSession::getValue($this->activeRecord.'_filter_search_canal_id')) {
            $string = $search_canal->dump();
            preg_match("/'(\d+)'/", $string, $matches);
            $param['canal_id'] = $matches[1];
            TApplication::loadPage($param['formEdit'], 'onInsert', $param);
        } else {
            new TMessage('error', 'Primeiro realize o filtro para o canal desejado.');
            return;
        }
    }

    public function transform_resultado($value, $object, $row, $cell)
    {
        if ($value <> '') {
            return self::addOption($value, $object->canal->plataforma->nome);
        }
    }

    public function transform_regra($value, $object, $row, $cell)
    {
        if ($value <> '') {
            $opcoes = explode(' - ', $value);
            
            $div = new TElement('div');
            $div->class = 'class="flex flex-row space-x-1"';

            foreach ($opcoes as $key => $opcao) {
                $div->add(self::addOption($opcao, $object->canal->plataforma->nome));
            }   
            
            return $div;
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

    public static function addOption($option, $bet_name)
    {
        $path = 'app/images/regras/';
        $path_bet = "app/images/regras/{$bet_name}/";

        $imageMap = [
            'red'   => ['image' => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'), 'title' => ''],
            'black' => ['image' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'), 'title' => ''],
            'white' => ['image' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'), 'title' => ''],
            'other' => ['image' => (file_exists($path_bet . 'other.png') ? $path_bet . 'other.png' : $path . 'other.png'), 'title' => 'Qualquer cor'],
            'break' => ['image' => (file_exists($path_bet . 'break.png') ? $path_bet . 'break.png' : $path . 'break.png'), 'title' => 'Ignorar entrada'],
            '1'     => ['image' => (file_exists($path_bet . '1.png') ? $path_bet . '1.png' : $path . '1.png'), 'title' => ''],
            '2'     => ['image' => (file_exists($path_bet . '2.png') ? $path_bet . '2.png' : $path . '2.png'), 'title' => ''],
            '3'     => ['image' => (file_exists($path_bet . '3.png') ? $path_bet . '3.png' : $path . '3.png'), 'title' => ''],
            '4'     => ['image' => (file_exists($path_bet . '4.png') ? $path_bet . '4.png' : $path . '4.png'), 'title' => ''],
            '5'     => ['image' => (file_exists($path_bet . '5.png') ? $path_bet . '5.png' : $path . '5.png'), 'title' => ''],
            '6'     => ['image' => (file_exists($path_bet . '6.png') ? $path_bet . '6.png' : $path . '6.png'), 'title' => ''],
            '7'     => ['image' => (file_exists($path_bet . '7.png') ? $path_bet . '7.png' : $path . '7.png'), 'title' => ''],
            '8'     => ['image' => (file_exists($path_bet . '8.png') ? $path_bet . '8.png' : $path . '8.png'), 'title' => ''],
            '9'     => ['image' => (file_exists($path_bet . '9.png') ? $path_bet . '9.png' : $path . '9.png'), 'title' => ''],
            '10'    => ['image' => (file_exists($path_bet . '10.png') ? $path_bet . '10.png' : $path . '10.png'), 'title' => ''],
            '11'    => ['image' => (file_exists($path_bet . '11.png') ? $path_bet . '11.png' : $path . '11.png'), 'title' => ''],
            '12'    => ['image' => (file_exists($path_bet . '12.png') ? $path_bet . '12.png' : $path . '12.png'), 'title' => ''],
            '13'    => ['image' => (file_exists($path_bet . '13.png') ? $path_bet . '13.png' : $path . '13.png'), 'title' => ''],
            '14'    => ['image' => (file_exists($path_bet . '14.png') ? $path_bet . '14.png' : $path . '14.png'), 'title' => ''],
            'ia'    => ['image' => (file_exists($path_bet . 'ia.png') ? $path_bet . '14.png' : $path . 'ia.png'), 'title' => ''],
        ];

        if (isset($imageMap[$option])) {
            $imgTag = new TElement('img');
            $imgTag->src = $imageMap[$option]['image'];
            $imgTag->title = $imageMap[$option]['title'];
            $imgTag->style = 'width: 30px; height: 30px; margin: 2px;';

            return $imgTag;
        }
    }

    public function exibeEdit($object) {
        return $object->tipo != 'IA';
    }

    public function exibeDelete($object) {
        return $object->tipo != 'IA';
    }

    public function exibeView($object) {
        return $object->tipo != 'IA';
    }
}
