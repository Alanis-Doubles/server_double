<?php

use Adianti\Control\TAction;

trait TStandardFormTrait
{
    private $properties;

    public function __construct($param = null)
    {
        parent::__construct();

        if (is_array($param))
            $this->properties = (object) $param;

        parent::setTargetContainer('adianti_right_panel');
        parent::setDatabase(constant(get_class($this) . '::DATABASE'));
        parent::setActiveRecord(constant(get_class($this) . '::ACTIVERECORD'));

        if (isset($this->properties->{'fromClass'}))
            $this->setAfterSaveAction(new TAction([$this->properties->{'fromClass'}, 'onReload']));

        // creates the form
        $this->form = new BootstrapFormBuilder('form_' . get_class($this));
        $this->form->setFormTitle($this->getTitle());
        $this->form->enableClientValidation();

        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');
        if ($param['method'] != 'onView') {
            $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave'), array_merge($param, ['static' => '1'])), 'far:save');
            $btn->class = 'btn btn-sm btn-primary';
            $this->form->addActionLink(_t('Close'), new TAction(array($this, 'onClose')), 'fa:times red');
        }

        $this->onBuild($param);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);

        parent::add($container);
    }

    protected function getTitle()
    {
        return '';
    }

    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }

    protected function onBuild($param)
    {
    }

    public function onView($param)
    {
        $data = $this->onEdit($param);
        // TUtils::disableForm();
        return $data;
    }

    public function onInsert($param)
    {
    }
}
