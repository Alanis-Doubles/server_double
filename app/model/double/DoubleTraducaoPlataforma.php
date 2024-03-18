<?php

use Adianti\Database\TRecord;

class DoubleTraducaoPlataforma extends DoubleRecord
{
    const TABLENAME  = 'double_traducao_plataforma';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }
}
