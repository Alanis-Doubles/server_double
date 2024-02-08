<?php

use Adianti\Database\TRecord;

class DoubleRecuperacaoMensagem extends DoubleRecord
{
    const TABLENAME  = 'double_recuperacao_mensagem';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';
    CONST DELETEDAT  = 'deleted_at';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }
    
    public function store()
    {
        unset($this->imagens);
        parent::store();
    }
}
