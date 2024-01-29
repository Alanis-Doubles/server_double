<?php

use Adianti\Base\TStandardForm;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TFormSeparator;

class TDoublePlataformaForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoublePlataforma';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Id'])],
            [$this->makeTEntry(['name' => 'id', 'label' => $label, 'editable' => false])],
            [],
            [],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Nome'])],
            [$this->makeTEntry(['name' => 'nome', 'label' => $label, 'required' => true, 'editable' => $param['method'] != 'onView'])],
            [$label = $this->makeTLabel(['value' => 'Idioma'])],
            [$this->makeTCombo(['name' => 'idioma', 'label' => $label, 'items' => ['ptBR' => 'Português', 'en' => 'Inglês', 'es' => 'Espanhol'], 'width' => '100%', 'required' => true, 'editable' => $param['method'] != 'onView'])],           
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Ativo'])],
            [$this->makeTCombo(['name' => 'ativo', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%', 'required' => true, 'editable' => $param['method'] != 'onView'])],
            [$label = $this->makeTLabel(['value' => 'Ambiente'])],
            [$this->makeTCombo(['name' => 'ambiente', 'label' => $label, 'items' => ['HOMOLOGACAO' => 'Homologação', 'PRODUCAO' => 'Produção'], 'width' => '100%', 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Valor Mínimo'])],
            [$this->makeTNumeric(['name' => 'valor_minimo', 'label' => $label, 'decimals' => 2, 'decimalsSeparator' => ',', 'thousandSeparator' => '.', 'required' => true, 'editable' => $param['method'] != 'onView'])],
            [$label = $this->makeTLabel(['value' => 'Controle de usuários por canal'])],
            [$this->makeTCombo(['name' => 'usuarios_canal', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%', 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );

        $tipo_sinais = ['GERA' => 'Gera Sinais', 'NAO_GERA' => 'Não Gera Sinais', 'PROPAGA_OUTRO' => 'Propaga Outro Canal'];
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Tipo Sinais'])],
            [$this->makeTCombo(['name' => 'tipo_sinais', 'label' => $label, 'items' => $tipo_sinais, 'width' => '100%', 'required' => true, 'editable' => $param['method'] != 'onView'])],
            [], []
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Telegram Token - Robô'])],
            [$this->makeTEntry(['name' => 'telegram_token', 'label' => $label, 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Url Double'])],
            [$this->makeTEntry(['name' => 'url_double', 'label' => $label, 'required' => true, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Url Cadastro'])],
            [$this->makeTEntry(['name' => 'url_cadastro', 'label' => $label, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Url Tutorial'])],
            [$this->makeTEntry(['name' => 'url_tutorial', 'label' => $label, 'editable' => $param['method'] != 'onView'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Url Suporte'])],
            [$this->makeTEntry(['name' => 'url_suporte', 'label' => $label, 'editable' => $param['method'] != 'onView'])],
        );
    }

    protected function getTitle()
    {
        return 'Plataforma';
    }
}