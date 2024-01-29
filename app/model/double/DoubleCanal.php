<?php

use Adianti\Database\TRecord;

class DoubleCanal extends DoubleRecord
{
    const TABLENAME  = 'double_canal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    private $obj_plataforma;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('unit_database');
    }

    public function get_statusSinais()
    {
        $canal =  TUtils::openFakeConnection('unit_database', function() {
            return new self($this->id, false);
        });

        return $canal->status_sinais;
    }

    public static function identificar($canal_id)
    {
        return TUtils::openFakeConnection('unit_database', function() use($canal_id) {
            return new DoubleCanal($canal_id, false);
        });
    }

    public function set_statusSinais($value)
    {
        TUtils::openConnection('unit_database', function() use ($value) {
            $canal = new self($this->id, false);
            $canal->status_sinais = $value;
            $canal->save();
        });
    }

    public function get_inicioSinais()
    {
        $canal = TUtils::openFakeConnection('unit_database', function() {
            return  new self($this->id, false);
        });
        
        return $canal->inicio_sinais;
    }

    public function set_inicioSinais($value)
    {
        TUtils::openConnection('unit_database', function () use ($value) {
            $canal = new self($this->id, false);
            $canal->inicio_sinais = $value;
            $canal->save();
        });
    }

    public function get_plataforma()
    {
        if (!$this->obj_plataforma) {
            $this->obj_plataforma =  TUtils::openConnection('unit_database', function () {
                return new DoublePlataforma($this->plataforma_id, false);
            });
        }
        
        return $this->obj_plataforma;
    }
}
