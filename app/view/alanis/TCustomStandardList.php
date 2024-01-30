<?php

use Adianti\Control\TAction;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardList;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Container\TPanelGroup;

class TCustomStandardList extends TStandardList
{
    private $properties;
    private $filter_label;

    use TUIBuilderTrait;

    public function __construct($param)
    {
        parent::__construct();
        
        if (is_array($param))
            $this->properties = (object) $param;
        else {
            $this->properties = $param;
        }

        $wFilters = [];
        $database = (string) isset($this->properties->{'database'}) ? (string) $this->properties->{'database'} : 'double';
        parent::setDatabase($database);          
        parent::setActiveRecord((string) $this->properties->{'activeRecord'}); 

        $noDefaultOrder = isset($this->properties->{'noDefaultOrder'}) ? $this->properties->{'noDefaultOrder'} : false;
        if (!$noDefaultOrder)
            $this->setDefaultOrder((string) isset($this->properties->{'defaultOrder'}) ? $this->properties->{'defaultOrder'} : 'id', 'asc');

        parent::setLimit(TSession::getValue(get_class($this) . '_limit') ?? 10);

        parent::setAfterSearchCallback( [$this, 'onAfterSearch' ] );

        $this->form = new BootstrapFormBuilder('form_search_' . get_class($this));
        $this->form->setFormTitle((string) isset($this->properties->{'titleFilters'}) ? $this->properties->{'titleFilters'} : 'Pesquisar');
        foreach ($this->properties->{'items'} as $key => $item) {
            if (is_array($item))
                $item = (object) $item;

            if (isset($item->{'widget'}))
            {
                TUtils::validateProperties("Item($key)", ['label', 'name'], $item);
                $label = $this->makeTLabel(['value' => $item->{'label'}]);

                $props = $item->{'widget'};
                $layout = isset($props['layout']) ? $props['layout'] : null;
                unset($props['layout']);
                $widget = [ [$label] ];
                if (!isset($props['class'])) {
                    foreach ($props as $propWdgt) {
                        if (!isset($propWdgt['name']))
                            $propWdgt['name'] = $item->{'name'};
                        $propWdgt['label'] = $label;

                        TUtils::validateProperties("Items($key)->Widget", ['operator'], $propWdgt);
                        parent::addFilterField($propWdgt['name'], $propWdgt['operator'], "search_{$propWdgt['name']}", isset($propWdgt['filterTransformer']) ? $propWdgt['filterTransformer'] : null); 
                        $propWdgt['name'] = "search_{$propWdgt['name']}";
                        $widget[] = [$this->createWidget($propWdgt)];
                    }
                } else
                {
                    if (!isset($props['name']))
                        $props['name'] = $item->{'name'};
                    $props['label'] = $label;

                    TUtils::validateProperties("Items($key)->Widget", ['operator'], $item->{'widget'});
                    parent::addFilterField($props['name'], $props['operator'], "search_{$props['name']}", isset($props['filterTransformer']) ? $props['filterTransformer'] : null); 
                    $props['name'] = "search_{$props['name']}";
                    $widget[] =  [$this->createWidget($props)];

                    if (isset($item->{'filter'}) and $item->{'filter'}) {
                        // $props['width'] = $item->{'filter'}['widget'];
                        $wTemp = clone $widget[1][0];
                        $wTemp->setSize($item->{'filter'}['width']);
                        $wTemp->style = 'height: ' . $item->{'filter'}['height'];
                        $wFilters[] = clone $wTemp;
                    }
                }
                
                $row = call_user_func_array([$this->form, 'addFields'], $widget);
                if (isset($layout))
                    $row->layout = $layout;
            }
        }

        $this->form->setFields($this->getWidgets());

        $this->form->setData( TSession::getValue(get_class($this) . '_filter_data') );

        $labelBtnSearch = property_exists($this->properties, 'labelBtnSearch') ? $this->properties->{'labelBtnSearch'} : 'Buscar';
        $icoBtnSearch   = property_exists($this->properties, 'icoBtnSearch')   ? $this->properties->{'icoBtnSearch'}   : 'fa:search';
        $btn = $this->form->addAction($labelBtnSearch, new TAction([$this, 'onSearch'], $param), $icoBtnSearch);
        $btn->class = 'btn btn-sm btn-primary';

        $dataGrid = new stdClass;
        $dataGrid->name = 'datagrid';
        $dataGrid->pagenavigator = property_exists($this->properties, 'pagenavigator') ? $this->properties->{'pagenavigator'} : true;
        $dataGrid->columns =[];
        
        foreach ($this->properties->{'items'} as $key => $item) {
            if (is_array($item))
                $item = (object) $item;

            if (isset($item->{'column'}))
            {
                $column = $item->{'column'};
                if (!isset($column['name']))
                    $column['name'] = $item->{'name'};
                if (!isset($column['label']))
                    $column['label'] = $item->{'label'};
                
                $dataGrid->columns[] = $column;
            }
        }

        $edtAction = [];
        $delAction = [];
        $visAction = [];
        if (isset($this->properties->{'formEdit'})) 
        {
            $edtAction = [$this->properties->{'formEdit'}, 'onEdit'];
            $delAction = [$this, 'onDelete'];   
            $visAction = [$this->properties->{'formEdit'}, 'onView'];
        }

        $field  = isset($this->properties->{'defaultOrder'}) ? $this->properties->{'defaultOrder'} : 'id';
        $fields = isset($this->properties->{'fields'}) ? $this->properties->{'fields'} : null;
        if ($fields)
            $field = null;

        $dataGrid->actions = [
            'actEditar'     => ['label' => 'Editar' , 'image' => 'far:edit blue', 'field' => $field, 'fields' => $fields, 'action' => $edtAction, 'action_params' => ['register_state' => 'false', 'fromClass' => get_class($this)]],
            'actExcluir'    => ['label' => 'Excluir', 'image' => 'far:trash-alt red', 'field' => $field, 'fields' => $fields, 'action' => $delAction],
            'actVisualizar' => ['label' => 'Visualizar', 'image' => 'fa:search', 'field' => $field, 'fields' => $fields, 'action' => $visAction, 'action_params' =>  ['register_state' => 'false']],
        ];    
                
        if (isset($this->properties->{'actions'}))
            foreach ($this->properties->{'actions'} as $key => $action) {
                $actionParams = [];
                if (isset($action['action_params']))
                    $actionParams = $action['action_params'];
                $actionParams = array_merge($actionParams, ['register_state' => 'false']);
                $action['action_params'] = $actionParams;
                $dataGrid->actions[$key] = $action;
            }

        $panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagrid');
        if ($dataGrid->pagenavigator)
            $this->pageNavigation = $this->getWidget('datagrid_pnv');


        if (count($wFilters) > 0)
        {
            $btnf = TButton::create('find', [$this, 'onSearch'], '', 'fa:search');
            $btnf->style= 'height: 37px; margin-right:4px;';
            
            $form_search = new TForm('form_search_name');
            $form_search->style = 'float:left;display:flex';
            $pnl = new TElement('div');
            // $pnl->style =  'width: 300px';
            foreach ($wFilters as $key => $value) {
                // $value->style = 'width: 100%; height: 100%'; 
                $pnl->add($value);  
                $form_search->setFields([$value]); 
            }
            $form_search->add($pnl);
            $form_search->add($btnf, true);
            // $form_search->setFields([$btnf]); 
            $panel->addHeaderWidget($form_search);
        }

        $hasInsert = isset($this->properties->{'hasInsert'}) ? $this->properties->{'hasInsert'} : true;
        if ($hasInsert) {
            unset($param['class']);
            unset($param['method']);
            $param['register_state'] = 'false';
            $param['fromClass'] = get_class($this);
            $panel->addHeaderActionLink('', new TAction([$this->properties->{'formEdit'}, 'onInsert'], $param), 'fa:plus');
        }

        $this->filter_label = $panel->addHeaderActionLink('Filtros', new TAction([$this, 'onShowCurtainFilters'], $param), 'fa:filter');
            
        $dropdown = new TDropDown(_t('Export'), 'fa:list');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( _t('Save as CSV'), new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table fa-fw blue' );
        $dropdown->addAction( _t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf fa-fw red' );
        $dropdown->addAction( _t('Save as XML'), new TAction([$this, 'onExportXML'], ['register_state' => 'false', 'static'=>'1']), 'fa:code fa-fw green' );
        $panel->addHeaderWidget( $dropdown );

        $dropdown = new TDropDown( TSession::getValue(get_class($this) . '_limit') ?? '10', '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( 10,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '10']) );
        $dropdown->addAction( 20,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '20']) );
        $dropdown->addAction( 50,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '50']) );
        $dropdown->addAction( 100,  new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '100']) );
        $dropdown->addAction( 1000, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '1000']) );
        $panel->addHeaderWidget( $dropdown );

        if (TSession::getValue(get_class($this).'_filter_counter') > 0)
        {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel('Filtros ('. TSession::getValue(get_class($this) . '_filter_counter').')');
        }
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        // if (get_class($this) != 'TCustomStandardList')
            $container->add(new TXMLBreadCrumb('menu.xml', get_class($this)));
        $container->add($panel);
        
        parent::add($container);
    }

    public function onAfterSearch($datagrid, $options)
    {
        if (TSession::getValue(get_class($this).'_filter_counter') > 0)
        {
            $this->filter_label->class = 'btn btn-primary';
            $this->filter_label->setLabel('Filtros ('. TSession::getValue(get_class($this).'_filter_counter').')');
        }
        else
        {
            $this->filter_label->class = 'btn btn-default';
            $this->filter_label->setLabel('Filtros');
        }
        
        // if (!empty(TSession::getValue(get_class($this).'_filter_data')))
        // {
        //     $obj = new stdClass;
        //     $obj->name = TSession::getValue(get_class($this).'_filter_data')->name;
        //     TForm::sendData('form_search_name', $obj);
        // }
    }

    public static function onShowCurtainFilters($param = null)
    {
        $class = get_called_class();

        try
        {
            $page = new TPage;
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('override', 'true');
            $page->setPageName($class);
            
            $btn_close = new TButton('closeCurtain');
            $btn_close->onClick = "Template.closeRightPanel();";
            $btn_close->setLabel("Fechar");
            $btn_close->setImage('fas:times');
            
            $embed = new $class($param);
            $embed->form->addHeaderWidget($btn_close);
            
            // embed form inside curtain
            $page->add($embed->form);
            $page->setIsWrapped(true);
            $page->show();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onChangeLimit($param)
    {
        TSession::setValue(get_class($this) . '_limit', $param['limit'] );
        AdiantiCoreApplication::loadPage(get_class($this), 'onReload');
    }
}