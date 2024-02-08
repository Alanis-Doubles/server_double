<?php

use Adianti\Database\TRecord;

class DoubleRecuperacaoVideo extends DoubleRecord
{
    const TABLENAME  = 'double_recuperacao_video';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    private $obj_recuperacao_mensagem;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function get_recuperacao_mensagem()
    {
        if (!$this->obj_recuperacao_mensagem)
        {
            $this->obj_recuperacao_mensagem = TUtils::openFakeConnection('double', function(){
                return new DoubleRecuperacaoMensagem($this->recuperacao_mensagem_id);
            });
        }

        return $this->obj_recuperacao_mensagem;
    }
}