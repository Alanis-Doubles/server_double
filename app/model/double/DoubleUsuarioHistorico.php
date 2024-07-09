<?php

use Adianti\Database\TRecord;

class DoubleUsuarioHistorico extends DoubleRecord
{
    const TABLENAME  = 'double_usuario_historico';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }
}
