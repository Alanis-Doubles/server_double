<?php

use Adianti\Widget\Base\TScript;
use Adianti\Database\TConnection;
use Adianti\Database\TTransaction;
use Google\Cloud\Translate\V2\TranslateClient;

/**
 * TUtils
 *
 * @version    1.0
 * @package    alanis
 * @author     Edson Alanis
 * @copyright  Copyright (c) 2023 Alanis
 */
class TUtils
{
    public static function cpf_cnpj($name = 'cpf_cnpj')
    {
        $script = new TElement('script');
        $script->type = 'text/javascript';
        $javascript = " 
          $('input[name=\"{$name}\"]').keydown(function(){
              var cpf_cnpj, tamanho, mascara, aplicar;
              cpf_cnpj = $(this).val();
              cpf_cnpj = cpf_cnpj.split(\" \").join(\"\");
              cpf_cnpj = cpf_cnpj.split(\".\").join(\"\");
              cpf_cnpj = cpf_cnpj.split(\"-\").join(\"\");
              cpf_cnpj = cpf_cnpj.split(\"/\").join(\"\");
              tamanho = cpf_cnpj.length;
              mascara = $('input[name=\"maskCpfCnpj\"]').val();
              aplicar = false;
          
              if(tamanho < 11){
                  $('input[name=\"{$name}\"]').mask(\"999.999.999-99\");
                  if (mascara != \"999.999.999-99\") {
                    $('input[name=\"maskCpfCnpj\"]').val(\"999.999.999-99\");
                    aplicar = true;
                  }
              } else {
                  $('input[name=\"{$name}\"]').mask(\"99.999.999/9999-99\");
                  if (mascara != \"99.999.999/9999-99\") {
                      $('input[name=\"maskCpfCnpj\"]').val(\"99.999.999/9999-99\");
                      aplicar = true;
                    }
              }
          
              // ajustando foco
              if (aplicar == true) {
                  var elem = this;
                  setTimeout(function(){
                      // mudo a posição do seletor
                      elem.selectionStart = elem.selectionEnd = 10000;
                  }, 0);
                  
                  $(this).val('');
                  $(this).val(cpf_cnpj);
              }
          }); 
      ";
        $script->add($javascript);

        return $script;
    }

    public static function limparCpfCnpj($value)
    {
        $value = str_replace(' ', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace('-', '', $value);
        $value = str_replace('/', '', $value);

        return $value;
    }

    public static function formatMask($mask, $value)
    {
        if ($value) {
            $value_index  = 0;
            $clear_result = '';

            $value = preg_replace('/[^a-z\d]+/i', '', $value);

            for ($mask_index = 0; $mask_index < strlen($mask); $mask_index++) {
                $mask_char = substr($mask, $mask_index,  1);
                $text_char = substr($value, $value_index, 1);

                if (in_array($mask_char, array('-', '_', '.', '/', '\\', ':', '|', '(', ')', '[', ']', '{', '}', ' '))) {
                    $clear_result .= $mask_char;
                } else {
                    $clear_result .= $text_char;
                    $value_index++;
                }
            }
            return $clear_result;
        }
    }

    public static function createXMLBreadCrumb($xml_file, $controller)
    {
        $frontpageController = TSession::getValue('frontpage');
        TXMLBreadCrumb::setHomeController($frontpageController);
        return new TXMLBreadCrumb($xml_file, $controller);
    }

    public static function createBreadCrumb($options, $home = true)
    {
        $frontpageController = TSession::getValue('frontpage');
        TBreadCrumb::setHomeController($frontpageController);
        return TBreadCrumb::create($options, $home = true);
    }

    public static function validateProperties($classname, $variables, $properties)
    {
        if (is_array($properties))
            $properties = (object)$properties;

        $erros = [];
        foreach ($variables as $key => $variable) {
            if (!isset($properties->$variable))
                $erros[] = AdiantiCoreTranslator::translate('The parameter (^1) of ^2 is required', $variable, $classname);
        }

        if ($erros)
            throw new Exception(implode('<br>', $erros));
    }

    public static function __callStatic($method, $parameters)
    {
        $class_name = get_called_class();
        if (substr($method, 0, 2) == 'is') {
            $permission = substr($method, 2);
            $userGroups = TSession::getValue('usergroupids');
            foreach ($userGroups as $group) {
                $obj = SystemGroup::findInTransaction('permission', $group);
                if ($obj->name == $permission) {
                    return true;
                }
            }

            return false;
        }
    }

    public static function setValidation($form, $field, $validations = [])
    {
        $control = $form->getField($field);
        foreach ($validations as $validation) {
            if (isset($validation)) {
                $validator = $validation['validator'];
                $params = isset($validation['params']) ? $validation['params'] : [];
                $control->addValidation($control->getLabel(), $validator, $params);
            }
        }
    }

    public static function disableForm()
    {
        TScript::create("utils_disable_form()", true, 200);
    }

    public static function showHideField($form, $field, $enabled)
    {
        if ($enabled) {
            BootstrapFormBuilder::showField($form, $field);
            TScript::create("utils_show_field('{$field}', 0)");
        } else {
            BootstrapFormBuilder::hideField($form, $field);
            TScript::create("utils_hide_field('{$field}', 0)");
        }
    }

    public static function enableField($form, $field, $enabled, $class = 'tdate')
    {
        if ($enabled) {
            TScript::create(" {$class}_enable_field( '{$form->getName()}', '{$field}' ); ");
        } else {
            TScript::create(" {$class}_disable_field( '{$form->getName()}', '{$field}' ); ");
        }
    }

    public static function enableFormNameField($formName, $field, $enabled, $class = 'tdate')
    {
        if ($enabled) {
            TScript::create(" {$class}_enable_field( '{$formName}', '{$field}' ); ");
        } else {
            TScript::create(" {$class}_disable_field( '{$formName}', '{$field}' ); ");
        }
    }

    public static function generateEANdigit($code)
    {
        $weightflag = true;
        $sum = 0;
        for ($i = strlen($code) - 1; $i >= 0; $i--) {
            $sum += (int)$code[$i] * ($weightflag ? 3 : 1);
            $weightflag = !$weightflag;
        }
        return (10 - ($sum % 10)) % 10;
    }

    public static function openConnection($database, $callback)
    {
        try {
            TTransaction::open($database);
            $result = $callback();
            // TTransaction::close();
            
            return $result;
        } catch (\Throwable $e) {
            TTransaction::rollback();
            throw $e;
        } finally {
            TTransaction::close();
        }
    }

    public static function openFakeConnection($database, $callback)
    {
        try {
            TTransaction::openFake($database);
            $result = $callback();
            // TTransaction::close();

            return $result;
        } catch (\Throwable $e) {
            // TTransaction::rollback();
            throw $e;
        } finally {
            TTransaction::close();
        }
    }

    public static function renderInfoBox($id, $title, $icon, $backgraound, $value) {
        $infoBox = new THtmlRenderer('app/resources/double/info-box.html');
        $infoBox->enableSection(
            'main',
            [
                'id' => $id,
                'title' => $title,
                'icon' => $icon,
                'background' => $backgraound,
                'value' => $value
            ]
        );

        return $infoBox;
    }

    public static function google_translator($text)
    {
        $translate = new TranslateClient([
            'key' => 'AIzaSyCFPwGw72Umgk-8P2vJV4XkGg7jgQyVCuA'
        ]);
        
        $result = $translate->translate($text, ['target' => 'pt']);
        return $result['text'];
    }

}