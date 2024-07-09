<?php

use Adianti\Database\TRecord;

class DoubleCanal extends DoubleRecord
{
    const TABLENAME  = 'double_canal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';
    const DELETEDAT  = 'deleted_at';

    use RecordTrait;

    private $obj_plataforma;
    private $obj_telegram;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function get_statusSinais()
    {
        $canal =  TUtils::openFakeConnection('double', function() {
            return new self($this->id, false);
        });

        return $canal->status_sinais;
    }

    public static function identificar($canal_id)
    {
        return TUtils::openFakeConnection('double', function() use($canal_id) {
            return new DoubleCanal($canal_id, false);
        });
    }

    public static function identificarPorChannel($channel_id)
    {
        return TUtils::openFakeConnection('double', function() use($channel_id) {
            return DoubleCanal::where('channel_id', '=', $channel_id)->first();
        });
    }

    public function set_statusSinais($value)
    {
        TUtils::openConnection('double', function() use ($value) {
            $canal = new self($this->id, false);
            $canal->status_sinais = $value;
            $canal->save();
        });
    }

    public function get_inicioSinais()
    {
        $canal = TUtils::openFakeConnection('double', function() {
            return  new self($this->id, false);
        });
        
        return $canal->inicio_sinais;
    }

    public function set_inicioSinais($value)
    {
        TUtils::openConnection('double', function () use ($value) {
            $canal = new self($this->id, false);
            $canal->inicio_sinais = $value;
            $canal->save();
        });
    }

    public function get_plataforma()
    {
        if (!$this->obj_plataforma) {
            $this->obj_plataforma =  TUtils::openConnection('double', function () {
                return new DoublePlataforma($this->plataforma_id, false);
            });
        }
        
        return $this->obj_plataforma;
    }

    public function get_telegram()
    {
        if ($this->telegram_token)
            if (!$this->obj_telegram)
                $this->obj_telegram = new TelegramRest($this->telegram_token);
            
        return $this->obj_telegram;
    }
}
