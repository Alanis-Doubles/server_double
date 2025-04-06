<?php

use Adianti\Base\TStandardForm;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TFormSeparator;

class TDoubleMensagemAgendadaForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;
    use Adianti\Base\AdiantiFileSaveTrait;

    const ACTIVERECORD = 'DoubleRecuperacaoMensagem';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id'])],
            [$this->makeTHidden(['name' => 'mensagem_direta', 'value' => 'A'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Título'])],
            [$this->makeTEntry(['name' => 'titulo', 'label' => $label, 'required' => true, 'width' => '100%'])],
        );

        $status = ['TODOS' => 'Todos', 'NOVO' => 'Novo', 'ATIVO' => 'Ativo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => ' Aguardando Pagamento']; 

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Para'])],
            [$this->makeTCombo(['name' => 'status', 'label' => $label, 'items' => $status, 'defaultOption' => false, 'required' => true])],           
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Mensagem'])],
            [$this->makeTText(['name' => 'mensagem', 'label' => $label, 'required' => true, 'width' => '100%'])],
            
        );

        $mensagem_apoio = new TAlert(
            'primary', 
            '<b>Dicas para montar a mensagem</b>' . '<br>' . 
            '<code>{usuario} - Quando quiser referenciar o nome do usuário na mensagem.</code>' . '<br>' .  
            '<code>\\n - Quando quiser fazer uma quebra de linha no texto.</code>', 
            false
        );

        $this->form->addFields([], [$mensagem_apoio]);

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Botão - Texto'])],
            [$this->makeTEntry(['name' => 'botao_1_mensagem', 'label' => $label])],
            [$label = $this->makeTLabel(['value' => 'Botão - Url'])],
            [$this->makeTEntry(['name' => 'botao_1_url', 'label' => $label])],           
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Imagens'])],
            [$this->makeTMultiFile(['name' => 'imagens', 'label' => $label, 'enableFileHandling' => true, 'enableImageGallery' => true])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Videos'])],
            [$this->makeTMultiFile(['name' => 'videos', 'label' => $label, 'extensions' => ['mp4'], 'enableFileHandling' => true])],
        );

        $this->form->addContent([new TFormSeparator('')]);

        $tipo_agendamento = ['M' => _t('Once a month'), 'W' => _t('Once a week'), 'D' => _t('Once a day'), 'F' => _t('Each five minutes')];
        
        $dias = [];
        for ($n = 1; $n <= 31; $n++)
        {
            $day_pad = str_pad($n, 2, '0', STR_PAD_LEFT);
            $dias[$day_pad] = $day_pad;
        }
        
        $horas = [];
        for ($n = 0; $n <= 23; $n++)
        {
            $hour_pad = str_pad($n, 2, '0', STR_PAD_LEFT);
            $horas[$hour_pad] = $hour_pad;
        }
        
        $minutos = [];
        for ($n = 0; $n <= 55; $n += 5)
        {
            $min_pad = str_pad($n, 2, '0', STR_PAD_LEFT);
            $minutos[$min_pad] = $min_pad;
        }

        $dias_semana = ['0' => 'Domingo', '1' => 'Segunda', '2' => 'Terça', '3' => 'Quarta', '4' => 'Quinta', '5' => 'Sexta', '6' => 'Sábado'];
        
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Tipo'])],
            [$this->makeTRadioGroup(['name' => 'tipo_agendamento', 'label' => $label, 'items' => $tipo_agendamento, 'useButton' => true, 'layout' => 'horizontal', 'value' => 'D'],
              function ($object){
                $object->setChangeAction(new TAction([$this, 'onChangeType']));
              })]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Dia do mês'])],
            [$this->makeTCombo(['name' => 'dia_mes', 'label' => $label, 'items' => $dias, 'width' => '100%', 'enableSearch' => true, 'enabled' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Dia da semana'])],
            [$this->makeTCombo(['name' => 'dia_semana', 'label' => $label, 'items' => $dias_semana, 'width' => '100%', 'enableSearch' => true, 'enabled' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Hora'])],
            [$this->makeTCombo(['name' => 'hora', 'label' => $label, 'items' => $horas, 'width' => '100%', 'enableSearch' => true, 'enabled' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Minuto'])],
            [$this->makeTCombo(['name' => 'minuto', 'label' => $label, 'items' => $minutos, 'width' => '100%', 'enableSearch' => true, 'enabled' => false])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Ativo'])],
            [$this->makeTRadioGroup(['name' => 'ativo', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'useButton' => true, 'layout' => 'horizontal', 'value' => 'Y'])]
        );
    }

    public static function onChangeType($param)
    {
        switch ($param['tipo_agendamento'])
        {
            case 'D':
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_mes');
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_semana');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'hora');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'minuto');
                break;
            case 'W':
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_mes');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'dia_semana');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'hora');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'minuto');
                break;
            case 'M':
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_semana');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'dia_mes');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'hora');
                TCombo::enableField('form_TDoubleMensagemAgendadaForm', 'minuto');
                break;
            case 'F':
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_mes');
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'dia_semana');
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'hora');
                TCombo::disableField('form_TDoubleMensagemAgendadaForm', 'minuto');
                break;
        }
    }

    protected function getTitle()
    {
        return 'Mensagem agendada';
    }

    public function onSave()
    {
        $object = parent::onSave();
        $data   = $this->form->getData();

        TUtils::openConnection('double', function() use ($object, $data){
            $this->saveFiles($object, $data, 'imagens', 'app/images/recuperacao', 'DoubleRecuperacaoImagem', 'imagem', 'recuperacao_mensagem_id');
            $this->saveFiles($object, $data, 'videos', 'app/images/recuperacao', 'DoubleRecuperacaoVideo', 'video', 'recuperacao_mensagem_id');
        });

        self::onChangeType(array('tipo_agendamento' => $object->tipo_agendamento));

        Self::onClose([]);

        return $object;
    }

    public function onEdit($param)
    {
        $object = parent::onEdit($param);
        $object->imagens = TUtils::openFakeConnection('double', function() use ($object){
            return DoubleRecuperacaoImagem::where('recuperacao_mensagem_id', '=', $object->id)->getIndexedArray('id', 'imagem');
        });
        $object->videos = TUtils::openFakeConnection('double', function() use ($object){
            return DoubleRecuperacaoVideo::where('recuperacao_mensagem_id', '=', $object->id)->getIndexedArray('id', 'imagem');
        });
        $this->form->setData($object);

        self::onChangeType(array('tipo_agendamento' => $object->tipo_agendamento));
        
        return $object;
    }

    

    public function onInsert($param)
    {
        self::onChangeType(array('tipo_agendamento' => 'D'));
    }
}