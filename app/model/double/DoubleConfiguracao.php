<?php

use Adianti\Database\TRecord;

class DoubleConfiguracao extends DoubleRecord
{
    const TABLENAME  = 'double_configuracao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public static function getConfiguracao($nome)
    {
        return TUtils::openFakeConnection('double', function() use ($nome){
            $config = self::where('nome', '=', $nome)->first();

            if ($config)
                return $config->valor;
            else
                return '';
        });
    }

    public static function setConfiguracao($nome, $valor)
    {
        TUtils::openConnection('double', function() use ($nome, $valor){
            $config = self::where('nome', '=', $nome)->first();
            if (!$config) {
                $config = new DoubleConfiguracao;
                $config->nome = $nome;
            }
            $config->valor = $valor;
            $config->save();
        });
    }
}
