<?php

use Adianti\Database\TRecord;

class DoubleErros extends DoubleRecord
{
    const TABLENAME  = 'double_erros';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('unit_database');
    }

    public static function registrar($plataforma_id, $classe, $metodo, $erro, $detalhe = null) 
    {
        TUtils::openConnection('double', function() use ($classe, $metodo, $erro, $detalhe, $plataforma_id) {
            $error = new DoubleErros();
            $error->classe = $classe;
            $error->metodo = $metodo;
            $error->erro = $erro;
            $error->detalhe = $detalhe;
            $error->plataforma_id = $plataforma_id;
            $error->save();
        });
    }
}
