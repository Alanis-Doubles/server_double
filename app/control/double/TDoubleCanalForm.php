<?php

use Adianti\Base\TStandardForm;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TFormSeparator;

class TDoubleCanalForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleCanal';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id', 'editable' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Nome'])],
            [$this->makeTEntry(['name' => 'nome', 'label' => $label])]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Id Canal'])],
            [$this->makeTEntry(['name' => 'channel_id', 'label' => $label])],
            [$label = $this->makeTLabel(['value' => 'Plataforma'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'plataforma_id', 
                    'label' => $label, 
                    'required' => true, 
                    'database' => 'double', 
                    'model' => 'DoublePlataforma', 
                    'key' => 'id', 
                    'display' => '[{idioma}] {nome}',
                ]
            )],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Proteções'])],
            [$this->makeTEntry(['name' => 'protecoes', 'label' => $label, 'mask' => '9!'])],
            [$label = $this->makeTLabel(['value' => 'Ativo'])],
            [$this->makeTCombo(['name' => 'ativo', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Exibe projeção'])],
            [$this->makeTCombo(['name' => 'exibir_projecao', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%'])],
            [$label = $this->makeTLabel(['value' => 'Proteção no branco'])],
            [$this->makeTCombo(['name' => 'protecao_branco', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%'])],
        );

        $inicio = new DateTime('00:00');
        $fim = new DateTime('23:59'); // precisa ser maior que 23:30 para incluir

        $intervalo = new DateInterval('PT30M'); // intervalo de 30 minutos
        $periodo = new DatePeriod($inicio, $intervalo, $fim);

        $horarios = [];

        foreach ($periodo as $hora) {
            $horarios[$hora->format('H:i')] = $hora->format('H:i');
        }

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Horário Sessão'])],
            [$this->makeTMultiSearch(['name' => 'horario_sessao', 'label' => $label, 'items' => $horarios, 'separator' => ',', 'minlen' => 2, 'height' => 40, 'width' => '100%'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Telegram Token - Robô'])],
            [$this->makeTEntry(['name' => 'telegram_token', 'label' => $label, 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );
        
        $this->form->addContent([new TElement('br')]);
        $this->form->addContent([new TFormSeparator('Webhooks Suportados')]);

        $this->form->addContent(
            [$this->makeTButton(['name' => 'kirvano', 'value' => 'Clique aqui para copiar o link do Webhook da Kirvano', 'icon' => 'far:copy', 'action' => [$this, 'urlKirvano']])]
        );

        $this->form->setFields($this->getWidgets());
    }

    protected function getTitle()
    {
        return 'Canal';
    }

    public static function urlKirvano($param){
        $plataforma = TUtils::openFakeConnection('double', function() use ($param){
            return new DoublePlataforma($param['plataforma_id'], false);
        });

        $kirvano = 'https://' . $_SERVER['HTTP_HOST'] . '/api/webhook/kirvano?plataforma='. $plataforma->nome . '&idioma=' . $plataforma->idioma . '&channel_id=' . $param['channel_id'];

        TScript::create("__adianti_copy_to_clipboard('".$kirvano."');");
    }
}