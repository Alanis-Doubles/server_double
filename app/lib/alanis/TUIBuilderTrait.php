<?php

use Adianti\Control\TAction;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TFile;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Form\TColor;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TImage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TSelect;
use Adianti\Widget\Form\TSlider;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Form\TSpinner;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TSortList;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TMultiFile;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Form\TSeekButton;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Form\TMultiSearch;
use Adianti\Widget\Wrapper\TDBSelect;
use Adianti\Widget\Wrapper\TDBSortList;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Wrapper\TDBCheckGroup;
use Adianti\Widget\Wrapper\TDBRadioGroup;
use Adianti\Widget\Wrapper\TDBMultiSearch;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;

/**
 * UI Builder Trait
 *
 * @version    1.0
 * @package    alanis
 * @author     Edson Alanis
 * @copyright  Copyright (c) 2023 Alanis
 */

trait TUIBuilderTrait
{
    protected $fields = [];
    protected $fieldsByName = [];

    public function getWidgets()
    {
        return $this->fields;
    }
    
    public function getWidget($name)
    {
        if (isset($this->fieldsByName[$name]))
        {
            return $this->fieldsByName[$name];
        }
        else
        {
            throw new Exception("Widget {$name} not found");
        } 
    }

    private function validateProperties($classname, $variables, $properties)
    {
        TUtils::validateProperties($classname, $variables, $properties);
    }

    public function makeTLabel($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        if (!isset($properties->{'name'}))
            $properties->{'name'} = 'label_' . uniqid();

        $this->validateProperties('TLabel', ['value'], $properties);

        $widget = new TLabel((string) $properties->{'value'});
        if (isset($properties->{'color'}))
            $widget->setFontColor((string) $properties->{'color'});
        if (isset($properties->{'size'}))
            $widget->setFontSize((string) $properties->{'size'});
        if (isset($properties->{'style'}))
            $widget->setFontStyle((string) $properties->{'style'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->setFontColor((string) 'red');

        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        return $widget;
    }

    public function makeTButton($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TButton', ['name', 'icon', 'value'], $properties);

        $widget = new TButton((string) $properties->{'name'});
        $widget->setImage((string) $properties->{'icon'});
        $widget->setLabel((string) $properties->{'value'});
        if (isset($properties->{'function'})) {
            $widget->addFunction($properties->{'function'});
        }
        if (isset($properties->{'action'})) {
            if (is_callable((array) $properties->{'action'}, true))
            {
                if (!isset($properties->{'action_params'}))
                    $properties->{'action_params'} = [];
                $widget->setAction(new TAction((array) $properties->{'action'}, (array) $properties->{'action_params'}), (string) $properties->{'value'});
            }
        
            if (is_callable($callback, true))
                call_user_func($callback, $widget);
        }
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTEntry($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TEntry', ['name'], $properties);

        $widget = new TEntry((string) $properties->{'name'});
        // $widget->forceUpperCase();
        if (isset($properties->{'label'}))
            $widget->setLabel('<b>' . $properties->{'label'}->getValue() . '</b>');

        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'mask'}))
        {
            $replaceOnPost = false;
            if (isset($properties->{'replaceOnPost'})) 
                $replaceOnPost = (boolean) $properties->{'replaceOnPost'};
            $widget->setMask((string) $properties->{'mask'}, $replaceOnPost);
        } 
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'maxlen'})) 
            $widget->setMaxLength((int) $properties->{'maxlen'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});

            if (isset($properties->{'numericMask'}))
            {
                $params = explode(';', $properties->{'numericMask'});
                $widget->setNumericMask($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);
            }
       
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string)$properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTNumeric($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TNumeric', ['name', 'decimals', 'decimalsSeparator', 'thousandSeparator'], $properties);

        $widget = new TNumeric((string)  $properties->{'name'}, 
                               (integer) $properties->{'decimals'},
                               (string)  $properties->{'decimalsSeparator'},
                               (string)  $properties->{'thousandSeparator'},
                               isset($properties->{'decimals'}) ? (boolean) $properties->{'decimals'} : true);

        if (isset($properties->{'label'}))
            $widget->setLabel('<b>' . $properties->{'label'}->getValue() . '</b>');
        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'maxlen'})) 
            $widget->setMaxLength((int) $properties->{'maxlen'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
       
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string)$properties->{'name'}] = $widget;
        
        return $widget;
    }
    
    public function makeTSpinner($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TSpinner', ['name', 'min', 'max', 'step'], $properties);

        $widget = new TSpinner((string) $properties->{'name'});
        $widget->setRange((int) $properties->{'min'}, (int) $properties->{'max'}, (int) $properties->{'step'});
        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
            
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string)$properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTSlider($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TSlider', ['name', 'min', 'max', 'step'], $properties);

        $widget = new TSlider((string) $properties->{'name'});
        $widget->setRange((int) $properties->{'min'}, (int) $properties->{'max'}, (int) $properties->{'step'});
        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});

        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string)$properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTPassword($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TPassword', ['name'], $properties);

        $widget = new TPassword((string) $properties->{'name'});
        if (isset($properties->{'value'})) 
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'})) 
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTDate($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDate', ['name'], $properties);

        $widget = new TDate((string) $properties->{'name'});
        $widget->setLabel('<b>' . $properties->{'label'}->getValue() . '</b>');
        $widget->setSize('100%');
        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'editable'}))
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'mask'}))
        {
            $replaceOnPost = false;
            if (isset($properties->{'replaceOnPost'})) 
                $replaceOnPost = (boolean) $properties->{'replaceOnPost'};
            $widget->setMask((string) $properties->{'mask'}, $replaceOnPost);
        }
        if (isset($properties->{'databaseMask'})) 
            $widget->setDatabaseMask((string) $properties->{'databaseMask'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTDateTime($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDateTime', ['name'], $properties);

        $widget = new TDateTime((string) $properties->{'name'});
        if (isset($properties->{'value'}))
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'editable'}))
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'mask'}))
        {
            $replaceOnPost = false;
            if (isset($properties->{'replaceOnPost'})) 
                $replaceOnPost = (boolean) $properties->{'replaceOnPost'};
            $widget->setMask((string) $properties->{'mask'}, $replaceOnPost);
        }
        if (isset($properties->{'databaseMask'})) 
            $widget->setDatabaseMask((string) $properties->{'databaseMask'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTMultiFile($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TMultiFile', ['name'], $properties);

        $widget = new TMultiFile((string) $properties->{'name'});
        $widget->setAllowedExtensions( ['gif', 'png', 'jpg', 'jpeg'] );

        if (isset($this->{'extensions'}))
            $widget->setAllowedExtensions($this->{'extensions'});
        if (isset($properties->{'enableFileHandling'})) 
            $widget->enableFileHandling();
        if (isset($properties->{'enableImageGallery'})) 
            $widget->enableImageGallery(); 
        if (isset($properties->{'enablePopover'})) 
            $widget->enablePopover($properties->{'enablePopover'}); 
        if (isset($properties->{'editable'}))
            $widget->setEditable((string) $properties->{'editable'});
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTFile($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TFile', ['name'], $properties);

        $widget = new TFile((string) $properties->{'name'});
        if (isset($properties->{'label'}))
            $widget->setLabel('<b>' . $properties->{'label'}->getValue() . '</b>');
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'editable'}))
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'extensions'})) 
            $widget->setAllowedExtensions((array) $properties->{'extensions'});
        if (isset($properties->{'enableFileHandling'})) 
            $widget->enableFileHandling();
        if (isset($properties->{'enableImageGallery'})) 
            $widget->enableImageGallery(); 
        if (isset($properties->{'enablePopover'})) 
            $widget->enablePopover($properties->{'enablePopover'}); 
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTColor($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TColor', ['name'], $properties);

        $widget = new TColor((string) $properties->{'name'});
        if (isset($properties->{'value'})) 
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'width'})) 
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTImage($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TImage', ['image'], $properties);

        if (file_exists((string) $properties->{'image'}))
            $widget = new TImage((string) $properties->{'image'});
        else
            $widget = new TLabel((string) 'Image not found: ' . $properties->{'image'});
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        return $widget;
    }

    public function makeTText($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TText', ['name'], $properties);

        $widget = new TText((string) $properties->{'name'});
        if (isset($properties->{'value'})) 
            $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
        if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTCheckGroup($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TCheckGroup', ['name', 'items'], $properties);

        $widget = new TCheckGroup((string) $properties->{'name'});

        $items = $properties->{'items'};
	    $widget->addItems($items);

        $layout = 'vertical';
	    if (isset($properties->{'layout'}))
            $layout = $properties->{'layout'};
        $widget->setLayout($layout);
        
	    if (isset($properties->{'value'}))
	        $widget->setValue(explode(',', (string) $properties->{'value'}));
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBCheckGroup($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBCheckGroup', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBCheckGroup((string) $properties->{'name'},
                                    (string) $properties->{'database'},
                                    (string) $properties->{'model'},
                                    (string) $properties->{'key'},
                                    (string) $properties->{'display'},
                                    (string) $ordercolumn,
                                    $criteria);
        
        $layout = 'vertical';
	    if (isset($properties->{'layout'}))
            $layout = $properties->{'layout'};
        $widget->setLayout($layout);
        
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTRadioGroup($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TRadioGroup', ['name', 'items'], $properties);

        $widget = new TRadioGroup((string) $properties->{'name'});
        
        $items = $properties->{'items'};
	    $widget->addItems($items);

        $layout = 'vertical';
	    if (isset($properties->{'layout'}))
            $layout = $properties->{'layout'};
        $widget->setLayout($layout);
        
        if (isset($properties->{'value'})) 
	        $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'useButton'}) && $properties->{'useButton'} === true) 
            $widget->setUseButton();
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBRadioGroup($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBRadioGroup', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBRadioGroup((string) $properties->{'name'},
                                    (string) $properties->{'database'},
                                    (string) $properties->{'model'},
                                    (string) $properties->{'key'},
                                    (string) $properties->{'display'},
                                    (string) $ordercolumn,
                                    $criteria);
        
        $layout = 'vertical';
	    if (isset($properties->{'layout'}))
            $layout = $properties->{'layout'};
        $widget->setLayout($layout);
        
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTCombo($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TCombo', ['name', 'items'], $properties);

        $widget = new TCombo((string) $properties->{'name'});
        if (isset($properties->{'label'}))
            $widget->setLabel('<b>' . $properties->{'label'}->getValue() . '</b>');

	    $items = $properties->{'items'};
	    $widget->addItems($items);

	    if (isset($properties->{'value'}))
	        $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'width'})) 
	        $widget->setSize($properties->{'width'});
        if (isset($properties->{'defaultOption'}))
            $widget->setDefaultOption($properties->{'defaultOption'});
        if (isset($properties->{'enableSearch'}))
            $widget->enableSearch();
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBCombo($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBCombo', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBCombo((string) $properties->{'name'},
                               (string) $properties->{'database'},
                               (string) $properties->{'model'},
                               (string) $properties->{'key'},
                               (string) $properties->{'display'},
                               (string) $ordercolumn,
                               $criteria);
	    
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
        if (isset($properties->{'width'})) 
	        $widget->setSize($properties->{'width'});
        if (isset($properties->{'defaultOption'}))
            $widget->setDefaultOption($properties->{'defaultOption'});
        if (isset($properties->{'enableSearch'}))
            $widget->enableSearch();
	    if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);
        if (isset($properties->{'editable'})) 
            $widget->setEditable((string) $properties->{'editable'});

        
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTSelect($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TSelect', ['name', 'items'], $properties);

        $widget = new TSelect((string) $properties->{'name'});
	    $items = $properties->{'items'};
	    $widget->addItems($items);

	    if (isset($properties->{'value'}))
	        $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        if (isset($properties->{'defaultOption'}))
            $widget->setDefaultOption($properties->{'defaultOption'});
        if (isset($properties->{'disableMultiple'}))
            $widget->setDefaultOption($properties->{'disableMultiple'});
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBSelect($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBSelect', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBSelect((string) $properties->{'name'},
                               (string) $properties->{'database'},
                               (string) $properties->{'model'},
                               (string) $properties->{'key'},
                               (string) $properties->{'display'},
                               (string) $ordercolumn,
                               $criteria);

	    if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        if (isset($properties->{'defaultOption'}))
            $widget->setDefaultOption($properties->{'defaultOption'});
        if (isset($properties->{'disableMultiple'}))
            $widget->disableMultiple();

        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTSortList($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TSortList', ['name', 'items'], $properties);

        $widget = new TSortList((string) $properties->{'name'});
        $items = $properties->{'items'};
	    $widget->addItems($items);

	    if (isset($properties->{'value'}))
	        $widget->setValue((string) $properties->{'value'});
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        
        $widget->setProperty('style', 'box-sizing: border-box !important', FALSE);

        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBSortList($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBSortList', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBSortList((string) $properties->{'name'},
                               (string) $properties->{'database'},
                               (string) $properties->{'model'},
                               (string) $properties->{'key'},
                               (string) $properties->{'display'},
                               (string) $ordercolumn,
                               $criteria );

	    if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        
        $widget->setProperty('style', 'box-sizing: border-box !important', FALSE);

        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTMultiSearch($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TMultiSearch', ['name', 'items'], $properties);

        $widget = new TMultiSearch((string) $properties->{'name'});
	    $items = $properties->{'items'};
	    $widget->addItems($items);

        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        if (isset($properties->{'minlen'})) 
            $widget->setMinLength( (int) $properties->{'minlen'} );
        if (isset($properties->{'maxsize'})) 
	        $widget->setMaxSize( (int) $properties->{'maxsize'} );
	    
        $widget->setProperty('style', 'box-sizing: border-box !important', FALSE);
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTDBMultiSearch($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDBMultiSearch', ['name', 'database', 'model', 'key', 'display'], $properties);

        $ordercolumn = NULL;
        $criteria = NULL;
	    if (isset($properties->{'ordercolumn'}))
            $ordercolumn = (string) $properties->{'ordercolumn'};
	    if (isset($properties->{'criteria'}))
            $criteria = $properties->{'criteria'};
        $widget = new TDBMultiSearch((string) $properties->{'name'},
                               (string) $properties->{'database'},
                               (string) $properties->{'model'},
                               (string) $properties->{'key'},
                               (string) $properties->{'display'},
                               (string) $ordercolumn,
                               $criteria );
        
        // $widget->setProperty('onKeyPress', "return tentry_upper(this)", true);
        // $widget->setProperty('onBlur', "return tentry_upper(this)", true);
        // $widget->setProperty('forceupper', "1", true);
        // $widget->setProperty('style', "text-transform: uppercase;", false);
                                
        if (isset($properties->{'tip'})) 
            $widget->setTip((string) $properties->{'tip'});
	    if (isset($properties->{'width'})) 
        {   
            $height = NULL;
            if (isset($properties->{'height'}))
                $height = $properties->{'height'};
            $widget->setSize($properties->{'width'}, $height);
        }
        if (isset($properties->{'minlen'})) 
            $widget->setMinLength( (int) $properties->{'minlen'} );
        if (isset($properties->{'maxsize'})) 
	        $widget->setMaxSize( (int) $properties->{'maxsize'} );
	    
        $widget->setProperty('style', 'box-sizing: border-box !important', FALSE);
	    
        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
	    $this->fieldsByName[(string) $properties->{'name'}] = $widget;
	    
        return $widget;
    }

    public function makeTSeekButton($properties, $callback = null)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TSeekButton', ['name', ], $properties);

        $widget = new TSeekButton((string) $properties->{'name'});
        if (isset($properties->{'width'}))
            $widget->setSize($properties->{'width'});
        if (isset($properties->{'required'}) AND $properties->{'required'}) 
            if (isset($properties->{'label'}))
                $widget->addValidation((string) '<b>' . $properties->{'label'}->getValue() . '</b>', new TRequiredValidator);
            else
                $widget->addValidation((string) $properties->{'name'}, new TRequiredValidator);

        $useOutEvent = !isset($properties->{'useOutEvent'}) ? true : $properties->{'useOutEvent'};
        $widget->setUseOutEvent($useOutEvent);
        
        $hasAuxiliar = isset($properties->{'hasAuxiliar'}) and $properties->{'hasAuxiliar'} == 'Y';
        if ($hasAuxiliar) {
            $display_field = (string) $properties->{'name'} . '_' . (string) $properties->{'display'};

            $receiver = $this->makeTEntry(['name' => $display_field, 'editable' => false]);
            $receiver->style .= ';margin-left:3px';
            if (isset($properties->{'width_receiver'}))
                $receiver->setSize($properties->{'width_receiver'});
            
            if (isset($properties->{'formSeekClass'}))
                $widget->setAction( new TAction([$properties->{'formSeekClass'}, 'onReload']) );
            $widget->setAuxiliar($receiver);
        } elseif (isset($properties->{'action'})) {
            $actionParams = [];
            if (isset($properties->{'action_params'}))
                $actionParams = $properties->{'action_params'};
            
                $widget->setAction( new TAction($properties->{'action'}, $actionParams) );
        }
        
        // $widget->setSize(40);

        if (is_callable($callback, true))
            call_user_func($callback, $widget);
        
        $this->fields[] = $widget;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        return $widget;
    }

    public function makeTHidden($properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('THidden', ['name'], $properties);

        $widget = new THidden($properties->{'name'});

        if (isset($properties->{'value'}))
            $widget->setValue($properties->{'value'});

        return $widget;
    }

    public function makeTDataGrid($properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('TDataGrid', ['name', 'columns'], $properties);

        if (!isset($properties->{'title'}))
            $properties->{'title'} = '';

        $panel = new TPanelGroup($properties->{'title'});

        $widget = new BootstrapDatagridWrapper(new TCustomDataGrid);
        // $widget->datatable = 'true';
        // $widget->style = 'width: 100%';
        $widget->style = 'min-width: 900px';
        $widget->setId((string) $properties->{'name'});
        $widget->generateHiddenFields();

        if (isset($properties->{'datatable'}))
            $widget->datatable = (string) $properties->{'datatable'};
        if (isset($properties->{'style'}))
            $widget->style = $properties->{'style'};
        if (isset($properties->{'height'}))
            $widget->setHeight((string) $properties->{'height'});
        if (isset($properties->{'disableDefaultClick'}))
            $widget->disableDefaultClick();
        
        if ($properties->{'columns'})
        {
            $search = [];
            foreach ($properties->{'columns'} as $key => $Column)
            {
                if (is_array($Column))
                    $Column = (object)$Column;

                $this->validateProperties("TDataGridColumn($key)", ['name', 'label', 'align'], $Column); 

                $dgcolumn = new TDataGridColumn((string) $Column->{'name'},
                                                (string) $Column->{'label'},
                                                (string) $Column->{'align'},
                                                isset($Column->{'width'}) ? (string) $Column->{'width'} : NULL );
                if (isset($Column->{'transformer'}))
                    $dgcolumn->setTransformer($Column->{'transformer'});
                
                if (isset($Column->{'hide'}) AND $Column->{'hide'})
                    $dgcolumn->setVisibility(false);

                if (isset($Column->{'enableSearch'}) and $Column->{'enableSearch'})
                    $search[] = (string) $Column->{'name'};

                if (isset($Column->{'order'}) and $Column->{'order'})
                {
                    $order = new TAction(array($this, 'onReload'));
                    $order->setParameter('order', (string) $Column->{'name'});
                    $dgcolumn->setAction($order);
                }
                
                $widget->addColumn($dgcolumn);
                $this->fieldsByName[(string)$Column->{'name'}] = $dgcolumn;
            }

            if ($search)
            {
                $input_search = new TEntry('input_search');
                $input_search->placeholder = _t('Search');
                $input_search->setSize('100%');
                $widget->enableSearch($input_search, implode(',', $search));
                $panel->addHeaderWidget($input_search);
            }
        }
        
        if (isset($properties->{'actions'}))
        {
            foreach ($properties->{'actions'} as $key => $Action)
            {
                if (is_array($Action))
                    $Action = (object)$Action;

                if (isset($Action->{'visible'}) AND !$Action->{'visible'})
                    continue;
                    
                $this->validateProperties("TDataGridAction($key)", ['label', 'image', 'action'], $Action); 

                if (is_callable((array) $Action->{'action'}, true))
                {
                    if (!isset($Action->{'action_params'}))
                        $Action->{'action_params'} = [];

                    $dgaction = new TDataGridAction((array) $Action->{'action'}, (array) $Action->{'action_params'});
                    $dgaction->setLabel((string) $Action->{'label'});
                    $dgaction->setImage((string) $Action->{'image'});
                    
                    if (isset($Action->{'display_condition'}))
                        $dgaction->setDisplayCondition($Action->{'display_condition'});

                    if (isset($Action->{'field'}))
                        $dgaction->setField((string) $Action->{'field'});

                    if (isset($Action->{'fields'}))
                        $dgaction->setFields((array) $Action->{'fields'});
                
                    $widget->addAction($dgaction);
                }
            }
        }
        
        if (isset($properties->{'pagenavigator'}))
            if ($properties->{'pagenavigator'})
            {
                $loader = isset($properties->{'loader'}) ? (string) $properties->{'loader'} : 'onReload';
                $pageNavigation = new TPageNavigation;
                $pageNavigation->enableCounters();
                $pageNavigation->setAction(new TAction(array($this, $loader)));
                $pageNavigation->setWidth($widget->getWidth());

                $this->fieldsByName[(string) $properties->{'name'} . '_pnv'] = $pageNavigation;
            }

        if (isset($properties->{'groupColumn'}))
            $widget->setGroupColumn($properties->{'groupColumn'}['name'], $properties->{'groupColumn'}['mask']);
        
        $widget->createModel();
        
        $panel->add($widget);
        if (isset($pageNavigation))
        {
            $panel->addFooter($pageNavigation);
            $widget->setPageNavigation($pageNavigation);
        }

        $this->fieldsByName[(string) $properties->{'name'} . '_pnl'] = $panel;
        $this->fieldsByName[(string) $properties->{'name'}] = $widget;
        
        $panel->getBody()->style = "overflow-x:auto;";
        $widget = $panel;
        
        return $widget;
    }

    public function makeDetail($properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('Detail', ['dataGrid', 'formDetail', 'delDetailFunction'], $properties);

        $actions = [
            'actEditar'=> ['label'=> 'Editar', 'image'=> 'far:edit blue', 'fields'=> ['uniqid', '*'], 'action'=> [$properties->formDetail, 'onEdit']],
            'actExcluir'=> ['label'=> 'Excluir', 'image'=> 'far:trash-alt red', 'field'=>'uniqid', 'action'=> [$this, 'onDeleteDetail'], 'action_params'=> ['static'=> 1, 'delDetailFunction' => $properties->delDetailFunction]],
        ];
        $properties->dataGrid['actions'] = $actions;

        $widget = $this->makeTDataGrid($properties->dataGrid);
        $widget->addHeaderActionLink('Novo',  new TAction([$properties->formDetail, 'onEdit']), 'fa:plus green');

        return $widget;
    }

    public function makeTFieldList($properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('FieldList', ['name', 'items'], $properties);

        $widget = new TFieldList;
        $widget->name = (string) $properties->{'name'};
        $widget->generateAria();
        $widget->width = isset($properties->{'width'}) ? $properties->{'width'} : '100%';

        foreach ($properties->{'items'} as $key => $item)
        {
            if (is_array($item))
                    $obj_item = (object) $item;

            $this->validateProperties("TFieldList($key)", ['name', 'label', 'widget', 'field_item'], $obj_item); 
            
            $obj_item->{'widget'}['name'] = (string) $obj_item->{'name'};
            $obj_item->{'widget'}['width'] = '100%';
            $item_widget = $this->createWidget($obj_item->{'widget'});
            $this->fields[] = $item_widget;
            $this->fieldsByName[(string) $item_widget->{'name'}] = $item_widget;

            $widget->addField('<b>'.$obj_item->{'label'}.'</b>', $item_widget, $obj_item->{'field_item'}); 
        }

        if (isset($properties->{'enableSorting'}) )
            $widget->enableSorting();

        if (isset($properties->{'button_actions'}))
        {
            foreach ($properties->{'button_actions'} as $key => $Action)
            {
                if (is_array($Action))
                    $Action = (object)$Action;

                if (isset($Action->{'visible'}) AND !$Action->{'visible'})
                    continue;
                    
                $this->validateProperties("TFieldList - Button Actions($key)", ['title', 'image', 'action'], $Action); 

                if (is_callable((array) $Action->{'action'}, true))
                {
                    if (!isset($Action->{'action_params'}))
                        $Action->{'action_params'} = [];

                    $widget->addButtonAction(
                        new TAction((array) $Action->{'action'}, $Action->{'action_params'}), 
                        (string) $Action->{'image'}, 
                        (string) $Action->{'title'}
                    );
                }
            }
        }

        $widget->addHeader();
        $widget->addDetail( new stdClass );
        $widget->addCloneAction();

        return $widget;
    }

    public function createWidget($properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $this->validateProperties('createWidget', ['class'], $properties);

        $class      = $properties->{'class'};
        $callback   = isset($properties->{'callback'}) ? $properties->{'callback'} : null;

        $widget = NULL;
        switch ($class)
        {
            case 'T'.'Label':
                $widget = $this->makeTLabel($properties, $callback);
                break;
            case 'T'.'Button':
                $widget = $this->makeTButton($properties, $callback);
                break;
            case 'T'.'Entry':
                $widget = $this->makeTEntry($properties, $callback);
                break;
            case 'T'.'Numeric':
                $widget = $this->makeTNumeric($properties, $callback);
                break;
            case 'T'.'Password':
                $widget = $this->makeTPassword($properties, $callback);
                break;
            case 'T'.'Date':
                $widget = $this->makeTDate($properties, $callback);
                break;
            case 'T'.'DateTime':
                $widget = $this->makeTDateTime($properties, $callback);
                break;
            case 'T'.'MultiFile':
                $widget = $this->makeTMultiFile($properties, $callback);
                break;
            case 'T'.'File':
                $widget = $this->makeTFile($properties, $callback);
                break;
            case 'T'.'Color':
                $widget = $this->makeTColor($properties, $callback);
                break;
            case 'T'.'SeekButton':
                $widget = $this->makeTSeekButton($properties, $callback);
                break;
            case 'T'.'Image':
                $widget = $this->makeTImage($properties, $callback);
                break;
            case 'T'.'Text':
                $widget = $this->makeTText($properties, $callback);
                break;
            case 'T'.'CheckGroup':
                $widget = $this->makeTCheckGroup($properties, $callback);
                break;
            case 'T'.'DBCheckGroup':
                $widget = $this->makeTDBCheckGroup($properties, $callback);
                break;
            case 'T'.'RadioGroup':
                $widget = $this->makeTRadioGroup($properties, $callback);
                break;
            case 'T'.'DBRadioGroup':
                $widget = $this->makeTDBRadioGroup($properties, $callback);
                break;
            case 'T'.'Combo':
                $widget = $this->makeTCombo($properties, $callback);
                break;
            case 'T'.'DBCombo':
                $widget = $this->makeTDBCombo($properties, $callback);
                break;
            case 'T'.'DataGrid':
                $widget = $this->makeTDataGrid($properties);
                break;
            case 'T'.'Spinner':
                $widget = $this->makeTSpinner($properties, $callback);
                break;
            case 'T'.'Slider':
                $widget = $this->makeTSlider($properties, $callback);
                break;
            case 'T'.'Select':
                $widget = $this->makeTSelect($properties, $callback);
                break;
            case 'T'.'DBSelect':
                $widget = $this->makeTDBSelect($properties, $callback);
                break;
            case 'T'.'SortList':
                $widget = $this->makeTSortList($properties, $callback);
                break;
            case 'T'.'DBSortList':
                $widget = $this->makeTDBSortList($properties, $callback);
                break;
            case 'T'.'MultiSearch':
                $widget = $this->makeTMultiSearch($properties, $callback);
                break;
            case 'T'.'DBMultiSearch':
                $widget = $this->makeTDBMultiSearch($properties, $callback);
                break;
            case 'T'.'Hidder':
                $widget = $this->makeTHidden($properties);
                break;
            case 'Detail':
                $widget = $this->makeDetail($properties);
                break;
            case 'T'.'FieldList':
                $widget = $this->makeTFieldList($properties);
                break;
            case 'T'.'Hidden':
                $widget = $this->makeTHidden($properties);
                break;
        }

        return $widget;
    }
}