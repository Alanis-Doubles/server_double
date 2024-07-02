<?php

use Adianti\Database\TFilter;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardForm;
use Adianti\Database\TCriteria;
use Adianti\Database\TExpression;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Template\THtmlRenderer;

class TDoubleEstrategiaForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleEstrategia';
    const DATABASE = 'double';
    const RESULTADOS = [
        '-' => ['red' => 'Vermelho', 'black' => 'Preto', 'white' => 'Branco', 'break' => 'Ignorar entrada'],
        'Jonbet' => ['red' => 'Verde', 'black' => 'Preto', 'white' => 'Branco', 'break' => 'Ignorar entrada']
    ];

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id'])],
            [$this->makeTHidden(['name' => 'regra'])],
        );

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                'IN',
                ['GERA', 'PROPAGA_VALIDA_SINAL']
            )
        );
        
        $criteria->add(  new TFilter( 'ativo', '=', 'Y') );

        $object = TUtils::openFakeConnection('double', function() use ($param){
            if (!isset($param['id']) or (isset($param['id']) and empty($param['id'])))
                return ;
            
            $object = new DoubleEstrategia($param['id'], false);
            if ($object)
                return $object;
        });

        $canal = null;

        if ($object) {
            $param['tipo'] = $object->tipo;
            $canal = $object->canal;
        }

        if (!$canal and isset($param['canal_id'])) {
            $canal = TUtils::openFakeConnection('double', function () use ($param){
                return new DoubleCanal($param['canal_id'], false);
            });
        }

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Canal'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'canal_id', 
                    'label' => $label, 
                    'database' => 'double', 
                    'required' => True,
                    'model' => 'DoubleCanal', 
                    'key' => 'id', 
                    'display' => '[{plataforma->idioma}] {plataforma->nome} - {nome}',
                    'editable' => $param['method'] != 'onView',
                    'defaultOption' => false,
                    'width' => '100%',
                    'criteria' => $criteria,
                    'editable' => false
                ]
            )],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Nome'])],
            [$this->makeTEntry(['name' => 'nome', 'label' => $label, 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Tipo'])],
            [$this->makeTCombo([
                'name' => 'tipo',
                'label' => $label,
                'width' => '100%',
                'items' => ['COR' => 'Cor', 'NUMERO' => 'Número', 'SOMA' => 'Soma'],
                'defaultOption' => false,
                'editable' => False,
                'required' => true
            ])],
            [$label = $this->makeTLabel(['value' => 'Ordem'])],
            [$this->makeTEntry(['name' => 'ordem', 'label' => $label, 'required' => true, 'mask' => '9!', 'editable' => $param['method'] != 'onView'])],
        );

        if ($param['tipo'] != 'SOMA') {
            // Campos de regra
            $this->ruleField = new TElement('div');
            $this->ruleField->id = 'rule_field';
            $this->ruleField->style = 'border: 1px solid #ccc; padding: 10px; min-height: 62px;';
            if ($param['method'] == 'onView') {
                $this->ruleField->style .= ' background: #eee;';
            }

            if ($param['method'] != 'onView') {        
                $div    = new TElement('div');
                $button = new TElement('button');
                $icon   = new TElement('i');

                $div->{'id'} = $this->id;
                $icon->{'class'} = 'fa fa-times';
                $div->{'class'} = 'regras';
                
                $button->{'type'} = 'button';
                $button->onclick = "clearOptions()";

                $button->add($icon);
                $div->add($this->innerIcon);
                $div->add($this->tag);
                $div->add($button);
                $this->ruleField->add($div);
            }

            $ruleContainer = new TElement('div');
            $ruleContainer->add($this->ruleField);

            $this->form->addFields([new TLabel('Regra')], [$ruleContainer]);

            // Divs de opções com imagens
            $optionsContainer = new TElement('div');

            $path = 'app/images/regras/';
            $path_bet = "app/images/regras/{$canal->plataforma->nome}/";

            $options = [
                'red'   => ['image' => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'), 'title' => ''],
                'black' => ['image' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'), 'title' => ''],
                'white' => ['image' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'), 'title' => ''],
                'other' => ['image' => (file_exists($path_bet . 'other.png') ? $path_bet . 'other.png' : $path . 'other.png'), 'title' => 'Qualquer cor'],
            ];

            if ($param['tipo'] == 'NUMERO') {
                $options = [
                    '1'  => ['image' => (file_exists($path_bet . '1.png') ? $path_bet . '1.png' : $path . '1.png'), 'title' => ''],
                    '2'  => ['image' => (file_exists($path_bet . '2.png') ? $path_bet . '2.png' : $path . '2.png'), 'title' => ''],
                    '3'  => ['image' => (file_exists($path_bet . '3.png') ? $path_bet . '3.png' : $path . '3.png'), 'title' => ''],
                    '4'  => ['image' => (file_exists($path_bet . '4.png') ? $path_bet . '4.png' : $path . '4.png'), 'title' => ''],
                    '5'  => ['image' => (file_exists($path_bet . '5.png') ? $path_bet . '5.png' : $path . '5.png'), 'title' => ''],
                    '6'  => ['image' => (file_exists($path_bet . '6.png') ? $path_bet . '6.png' : $path . '6.png'), 'title' => ''],
                    '7'  => ['image' => (file_exists($path_bet . '7.png') ? $path_bet . '7.png' : $path . '7.png'), 'title' => ''],
                    '8'  => ['image' => (file_exists($path_bet . '8.png') ? $path_bet . '8.png' : $path . '8.png'), 'title' => ''],
                    '9'  => ['image' => (file_exists($path_bet . '9.png') ? $path_bet . '9.png' : $path . '9.png'), 'title' => ''],
                    '10' => ['image' => (file_exists($path_bet . '10.png') ? $path_bet . '10.png' : $path . '10.png'), 'title' => ''],
                    '11' => ['image' => (file_exists($path_bet . '11.png') ? $path_bet . '11.png' : $path . '11.png'), 'title' => ''],
                    '12' => ['image' => (file_exists($path_bet . '12.png') ? $path_bet . '12.png' : $path . '12.png'), 'title' => ''],
                    '13' => ['image' => (file_exists($path_bet . '13.png') ? $path_bet . '13.png' : $path . '13.png'), 'title' => ''],
                    '14' => ['image' => (file_exists($path_bet . '14.png') ? $path_bet . '14.png' : $path . '14.png'), 'title' => '']
                ];
            }

            if ($param['method'] != 'onView') {
                foreach ($options as $key => $option) {
                    $div = new TElement('div');
                    $div->style = 'display: inline-block; margin: 2px; cursor: pointer;';
                    $div->onclick = "addOption('$key')";
                    $div->title = $option['title'];
                    
                    $img = new TElement('img');
                    $img->src = $option['image'];
                    $img->style = 'width: 35px; height: 35px;';

                    $div->add($img);
                    $optionsContainer->add($div);
                }

                $this->form->addFields([], [$optionsContainer]);
            }
        }

        if ($param['tipo'] != 'SOMA') 
            $this->form->addFields(
                [$label = $this->makeTLabel(['value' => 'Resultado'])],
                [$this->makeTCombo([
                    'name' => 'resultado',
                    'label' => $label,
                    'width' => '100%',
                    'items' => isset(self::RESULTADOS[$canal->plataforma->nome]) ? self::RESULTADOS[$canal->plataforma->nome] : self::RESULTADOS['-'],
                    'defaultOption' => false,
                    'editable' => $param['method'] != 'onView',
                    'required' => true
                ])],
            );

        // carregar as regras
        if ($object and $object->regra) {
            $regras = explode(' - ', $object->regra);
            foreach ($regras as $key => $value) {
                $obj = self::onAddOption(['option' => $value], $canal->plataforma->nome);
                $this->ruleField->add($obj);
            }
        }

        // Adiciona o script de JavaScript para a ação de clique
        TScript::create($this->getJavaScript());

        // Verifica se recebeu o tipo por parâmetro
        if (isset($param['tipo'])) {
            $data = new stdClass;
            $data->tipo = $param['tipo'];
            $data->canal_id = $canal->id;

            TForm::sendData('form_TDoubleEstrategiaForm', $data);
        }
    }

    protected function getTitle()
    {
        return 'Estrategia';
    }

    public function onSave($param = null)
    {
        if ($param['tipo'] != 'SOMA' and $param['regra'] == '')
            new TMessage('error', 'É obrigatório informar uma regra');

        parent::onSave();
    }

    public static function onAddOption($param, $bet_name)
    {
        $path = 'app/images/regras/';
        $path_bet = "app/images/regras/{$bet_name}/";

        $imageMap = [
            'red'   => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'),
            'black' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'),
            'white' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'),
            'other' => (file_exists($path_bet . 'other.png') ? $path_bet . 'other.png' : $path . 'other.png'),
            'break' => (file_exists($path_bet . 'break.png') ? $path_bet . 'break.png' : $path . 'break.png'),
            '1'     => (file_exists($path_bet . '1.png') ? $path_bet . '1.png' : $path . '1.png'),
            '2'     => (file_exists($path_bet . '2.png') ? $path_bet . '2.png' : $path . '2.png'),
            '3'     => (file_exists($path_bet . '3.png') ? $path_bet . '3.png' : $path . '3.png'),
            '4'     => (file_exists($path_bet . '4.png') ? $path_bet . '4.png' : $path . '4.png'),
            '5'     => (file_exists($path_bet . '5.png') ? $path_bet . '5.png' : $path . '5.png'),
            '6'     => (file_exists($path_bet . '6.png') ? $path_bet . '6.png' : $path . '6.png'),
            '7'     => (file_exists($path_bet . '7.png') ? $path_bet . '7.png' : $path . '7.png'),
            '8'     => (file_exists($path_bet . '8.png') ? $path_bet . '8.png' : $path . '8.png'),
            '9'     => (file_exists($path_bet . '9.png') ? $path_bet . '9.png' : $path . '9.png'),
            '10'    => (file_exists($path_bet . '10.png') ? $path_bet . '10.png' : $path . '10.png'),
            '11'    => (file_exists($path_bet . '11.png') ? $path_bet . '11.png' : $path . '11.png'),
            '12'    => (file_exists($path_bet . '12.png') ? $path_bet . '12.png' : $path . '12.png'),
            '13'    => (file_exists($path_bet . '13.png') ? $path_bet . '13.png' : $path . '13.png'),
            '14'    => (file_exists($path_bet . '14.png') ? $path_bet . '14.png' : $path . '14.png'),
        ];

        if (isset($imageMap[$param['option']])) {
            $imgTag = new TElement('img');
            $imgTag->src = $imageMap[$param['option']];
            $imgTag->style = 'width: 35px; height: 35px; margin: 2px;';

            return $imgTag;
        }
    }

    public static function onAddOptionJs($param)
    {
        $bet_name = TUtils::openFakeConnection('double', function () use ($param){
            $obj = new DoubleCanal($param['canal_id'], false);
            if ($obj)
                return $obj->plataforma->nome;
            else
                return '--';
        });

        $option = self::onAddOption($param, $bet_name);
        if ($option) {
            echo $option->getContents();
        }
    }

    private function getJavaScript()
    {
        return <<<JAVASCRIPT
            function addOption(option) {
                regra = document.getElementsByName('regra')[0].value;
                canal_id = document.getElementsByName('canal_id')[0].value;
                if (regra == '')
                    regras = [];
                else
                    regras = regra.split(' - ');

                try
                {
                    if (regras.length == 15) {
                        const e = new Error("Você pode selecionar no máximo 15 opções. ");
                        throw e;
                    }

                    regras.push(option);
                    regra = regras.join(' - ');
                    document.getElementsByName('regra')[0].value = regra;

                    if (!isNaN(option)) {
                        clearOptions();
                        document.getElementsByName('regra')[0].value = option;
                    }

                    __adianti_ajax_exec('class=TDoubleEstrategiaForm&method=onAddOptionJs&option=' + option + '&canal_id=' + canal_id, function(data) {
                        document.getElementById('rule_field').insertAdjacentHTML('beforeend', data);
                    }, false);
                } catch (e) {
                    if (e instanceof Error) {
                        __adianti_error('error', e.message);
                    }
                }
            }

            function clearOptions(){
                regra = document.getElementsByName('regra')[0].value;
                regras = regra.split(' - ');
                
                regras.forEach(option => {
                    let rules = document.getElementById("rule_field");
                    for (const child of rules.children) {
                        if (child.tagName == 'IMG') {
                            child.remove();
                        }
                    }
                });

                document.getElementsByName('regra')[0].value = '';
            }

            
JAVASCRIPT;
    }
}