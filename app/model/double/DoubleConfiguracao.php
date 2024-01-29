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
        $this->loadAttributes('unit_database');
    }

    public static function getConfiguracao($nome)
    {
        return TUtils::openFakeConnection('unit_database', function() use ($nome){
            $config = self::where('nome', '=', $nome)->first();
            return $config->valor;
        });
    }

    public static function setConfiguracao($nome, $valor)
    {
        TUtils::openConnection('unit_database', function() use ($nome, $valor){
            $config = self::where('nome', '=', $nome)->first();
            $config->valor = $valor;
            $config->save();
        });
    }
}
