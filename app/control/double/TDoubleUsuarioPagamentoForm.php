<?php

use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Validator\TMinValueValidator;

class TDoubleUsuarioPagamentoForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoublePagamentoHistorico';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'plataforma_id'])],
            [$this->makeTHidden(['name' => 'canal_id'])],
            [$this->makeTHidden(['name' => 'usuario_id'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Nome'])],
            [$this->makeTEntry(['name' => 'nome_completo', 'label' => $label, 'editable' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Usuário'])],
            [$this->makeTEntry(['name' => 'nome_usuario', 'label' => $label, 'editable' => false])],
            [$label = $this->makeTLabel(['value' => 'E-mail'])],
            [$this->makeTEntry(['name' => 'email', 'label' => $label, 'required' => true])],
        );

        $this->form->addContent([new TFormSeparator('')]);
        
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Produto'])],
            [$this->makeTEntry(['name' => 'produto', 'label' => $label, 'required' => true])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Plataforma'])],
            [$this->makeTCombo([
                'name' => 'plataforma_pagamento',
                'label' => $label,
                'width' => '100%',
                'items' => ['PIX' => 'Pix', 'LASTLINK' => 'LastLink'],
                'defaultOption' => false,
                'required' => true
            ])],
            [$label = $this->makeTLabel(['value' => 'Entrada'])],
            [$this->makeTCombo([
                'name' => 'tipo_entrada',
                'label' => $label,
                'width' => '100%',
                'items' => ['MANUAL' => 'Manual', 'AUTOMATICA' => 'Automática'],
                'defaultOption' => false,
                'required' => true
            ])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Tipo'])],
            [$this->makeTCombo([
                'name' => 'tipo',
                'label' => $label,
                'width' => '100%',
                'items' => ['MENSAL' => 'Mensal', 'TRIMESTRAL' => 'Trimestral', 'SEMESTRAL' => 'Semestral', 'ANUAL' => 'Anual'],
                'defaultOption' => false,
                'required' => true
            ])],
            [$label = $this->makeTLabel(['value' => 'Evento'])],
            [$this->makeTCombo([
                'name' => 'tipo_evento',
                'label' => $label,
                'width' => '100%',
                'items' => ['PAGAMENTO' => 'Pagamento', 'CANCELAMENTO' => 'Cancelamento'],
                'defaultOption' => false,
                'required' => true
            ])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Valor'])],
            [$this->makeTNumeric(['name' => 'valor', 'label' => $label, 'decimals' => 2, 'decimalsSeparator' => ',', 'thousandSeparator' => '.', 'required' => true])],
            [],[]
        );

        TUtils::setValidation($this->form, 'email', [['validator' => new TEmailValidator]]);
        TUtils::setValidation($this->form, 'valor', [['validator' => new TMinValueValidator, 'params' => '1']]);
    }

    protected function getTitle()
    {
        return 'Registrar Pagamento';
    }

    public function onInsert($param)
    {
        $usuario = TUtils::openFakeConnection('double', function () use ($param) {
            return new DoubleUsuario($param['id'], false);
        });

        $data = new stdClass;
        $data->usuario_id = $usuario->id;
        $data->plataforma_pagamento = 'PIX';
        $data->tipo = 'MENSAL';
        $data->tipo_entrada = 'MANUAL';
        $data->tipo_evento = 'PAGAMENTO';
        $data->plataforma_pagamento = 'PIX';
        $data->valor = 0;
        $data->nome_completo = $usuario->nome_completo;
        $data->nome_usuario = $usuario->nome_usuario;
        $data->email = $usuario->email;
        $data->plataforma_id = $usuario->plataforma->id;
        if ($usuario->plataforma->usuarios_canal == "Y") {
            $data->canal_id = $usuario->canal->id;
            $data->produto = 'Acesso ao canal: ' . $usuario->canal->nome;
        } else
        $data->produto = 'Acesso a plataforma: ' . $usuario->plataforma->nome;
        $this->form->setData($data);
    }
}
