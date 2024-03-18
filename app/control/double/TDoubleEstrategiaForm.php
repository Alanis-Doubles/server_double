<?php

use Adianti\Database\TFilter;
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

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id'])],
            [$this->makeTHidden(['name' => 'regra'])],
            [$this->makeTHidden(['name' => 'resultado'])],
        );

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter(
                '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
                '=',
                'GERA'
            )
        );
        
        $criteria->add(  new TFilter( 'ativo', '=', 'Y') );

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
                    'criteria' => $criteria
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
                'editable' => $param['method'] != 'onView',
                'required' => true
            ], 
            function ($object) {
                $object->setChangeAction( new TAction(array($this, 'onTipoChange')) );
            })],
            [$label = $this->makeTLabel(['value' => 'Ordem'])],
            [$this->makeTEntry(['name' => 'ordem', 'label' => $label, 'required' => true, 'mask' => '9!', 'editable' => $param['method'] != 'onView'])],
        );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
        $botoes[0]['botao']->enableSection( 'main', ['cor' => 'vermelho', 'value' => ''] );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
        $botoes[1]['botao']->enableSection( 'main', ['cor' => 'preto', 'value' => ''] );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html')];
        $botoes[2]['botao']->enableSection( 'main', [] );

        $numeros[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html')];
        $numeros[0]['botao']->enableSection( 'main', [] );

        for ($i=1; $i < 8; $i++) { 
            $numeros[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
            $numeros[$i]['botao']->enableSection( 'main', ['cor' => 'vermelho', 'value' => $i] );
        }
        
        for ($i=8; $i < 15; $i++) { 
            $numeros[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
            $numeros[$i]['botao']->enableSection( 'main', ['cor' => 'preto', 'value' => $i] );
        }
        
        $regra = new THtmlRenderer('app/resources/double/estrategia/regra.html');
        $regra->enableSection(
            'main',
            [
                'botoes' => $botoes,
                'numeros' => $numeros,
            ]
        );
        
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Regra'])],
            [$regra],
        );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
        $botoes[0]['botao']->enableSection( 'main', ['cor' => 'vermelho', 'value' => ''] );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao.html')];
        $botoes[1]['botao']->enableSection( 'main', ['cor' => 'preto', 'value' => ''] );

        $botoes[] = ['botao' => new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html')];
        $botoes[2]['botao']->enableSection( 'main', [] );

        $retorno = new THtmlRenderer('app/resources/double/estrategia/retorno.html');
        $retorno->enableSection(
            'main',
            [
                'botoes' => $botoes,
            ]
        );
        
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Resultado'])],
            [$retorno],
        );

        TScript::create('
            function atualiza_regra_selecao() {
                const campo = document.getElementsByName("regra")[0];
                if (campo) {
                    const regra = campo.value;
                    const tipo = document.getElementsByName("tipo")[0].value;
                    $.get("engine.php?class=TDoubleEstrategiaForm&method=doConsultarRegra&static=1&regra="+regra+"&tipo="+tipo, function(data) {
                        $("#regra_selecao div").remove();
                        $("#regra_selecao").append(data);
                    });
                }
            }

            function atualiza_resultado_selecao() {
                const regra = document.getElementsByName("resultado")[0].value;
                $.get("engine.php?class=TDoubleEstrategiaForm&method=doConsultarResultado&static=1&regra="+regra, function(data) {
                    $("#retorno_selecao div").remove();
                    $("#retorno_selecao").append(data);
                });
            }

            atualiza_regra_selecao();
            atualiza_resultado_selecao();

            setInterval( atualiza_regra_selecao, 5000);
            setInterval( atualiza_resultado_selecao, 5000);
        ');
    }

    public static function doConsultarResultado($param)
    {
        if (!isset($param['regra']) or (isset($param['regra']) and !$param['regra']))
            echo '<div>Clique nas cores para informar o resultado</div>';
        else {
            $value = $param['regra'];
            if ($value == 'white') {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                $botao->enableSection( 'main', [] );
            } else {
                $botao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                $botao->enableSection( 'main', ['cor' => ['red' => 'vermelho', 'black' => 'preto'][$value], 'value' => ''] );
            }

            echo $botao->getContents();
        }
    }

    public static function doConsultarRegra($param)
    {
        if (!isset($param['regra']) or (isset($param['regra']) and !$param['regra']))
            echo '<div>Clique nas cores ou números para definir uma regra</div>';
        else {
            $value = $param['regra'];
            $tipo = $param['tipo'];
            if ($tipo == 'NUMERO') {
                if ($value == 0) {
                    $selecao = new THtmlRenderer('app/resources/double/estrategia/double_botao_branco.html');
                    $selecao->enableSection( 'main', [] );
                } elseif ($value < 8) {
                    $selecao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $selecao->enableSection( 'main', ['cor' => 'vermelho', 'value' => $value] );
                } else {
                    $selecao = new THtmlRenderer('app/resources/double/estrategia/double_botao.html');
                    $selecao->enableSection( 'main', ['cor' => 'preto', 'value' => $value] );
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

                $selecao = new THtmlRenderer('app/resources/double/estrategia/retorno_lista.html');
                $selecao->enableSection(
                    'main',
                    [
                        'botoes' => $cores,
                    ]
                );
                $selecao;
            }

            echo $selecao->getContents();
        }      
    }

    protected function getTitle()
    {
        return 'Estrategia';
    }

    public static function onTipoChange($param)
    {
        if ($param['tipo'] == 'COR') {
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regras', true);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_cor', true);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_acoes', true);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_numero', false);
        } elseif ($param['tipo'] == 'NUMERO') {
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regras', true);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_cor', false);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_acoes', false);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regra_numero', true);
        } else {
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'regras', false);
            TUtils::showHideField('form_TDoubleEstrategiaForm', 'retorno', false);
        }
    }

    public function onEdit($param)
    {
        $object = parent::onEdit($param);

        self::onTipoChange($object->toArray());
        return $object;
    }
}