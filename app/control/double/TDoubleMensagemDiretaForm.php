<?php

use Adianti\Base\TStandardForm;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TFormSeparator;

class TDoubleMensagemDiretaForm  extends TStandardForm
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
            [$this->makeTHidden(['name' => 'mensagem_direta', 'value' => 'Y'])],
        );

        $status = ['NOVO' => 'Novo', 'ATIVO' => 'Ativo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => ' Aguardando Pagamento']; 

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Status'])],
            [$this->makeTCombo(['name' => 'status', 'label' => $label, 'items' => $status, 'defaultOption' => false, 'required' => true])],           
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Mensagem'])],
            [$this->makeTText(['name' => 'mensagem', 'label' => $label, 'required' => true])],
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
            [$label = $this->makeTLabel(['value' => 'Botão - Mensagem'])],
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
    }

    protected function getTitle()
    {
        return 'Mensagem direta';
    }

    public function onSave()
    {
        $object = parent::onSave();
        $data   = $this->form->getData();

        TUtils::openConnection('double', function() use ($object, $data){
            $this->saveFiles($object, $data, 'imagens', 'app/images/recuperacao', 'DoubleRecuperacaoImagem', 'imagem', 'recuperacao_mensagem_id');
            $this->saveFiles($object, $data, 'videos', 'app/images/recuperacao', 'DoubleRecuperacaoVideo', 'video', 'recuperacao_mensagem_id');
        });

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
        
        return $object;
    }
}