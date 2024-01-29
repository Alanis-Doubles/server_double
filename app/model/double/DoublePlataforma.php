<?php

use Adianti\Database\TRecord;
use Adianti\Registry\TSession;

class DoublePlataforma extends DoubleRecord
{
    const TABLENAME  = 'double_plataforma';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    private $obj_translate;
    private $obj_system_group;
    private $obj_system_role;
    private $obj_telegram;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('unit_database');
    }

    public function get_statusSinais()
    {
        $plataforma =  TUtils::openFakeConnection('unit_database', function() {
            return new self($this->id, false);
        });

        return $plataforma->status_sinais;
    }

    public function set_statusSinais($value)
    {
        TUtils::openConnection('unit_database', function() use ($value) {
            $plataforma = new self($this->id, false);
            $plataforma->status_sinais = $value;
            $plataforma->save();
        });
    }

    public function get_inicioSinais()
    {
        $plataforma = TUtils::openFakeConnection('unit_database', function() {
            return  new self($this->id, false);
        });
        
        return $plataforma->inicio_sinais;
    }

    public function set_inicioSinais($value)
    {
        TUtils::openConnection('unit_database', function () use ($value) {
            $plataforma = new self($this->id, false);
            $plataforma->inicio_sinais = $value;
            $plataforma->save();
        });
    }

    public function get_service()
    {
        if (TBrazabet::nome() == $this->nome)
            return new TBrazabet;
        else if (TBlaze::nome() == $this->nome)
            return new TBlaze;
        else if (TArbety::nome() == $this->nome)
            return new TArbety;
        else if (TWeplay::nome() == $this->nome)
            return new TWeplay;
        else
            throw new Exception("Plataforma '{$this->nome}' não suportada.");
    }

    public function get_telegram()
    {
        if (!$this->obj_telegram)
            $this->obj_telegram = new TelegramRest($this->telegram_token);
        
        return $this->obj_telegram;
    }

    public function get_translate()
    {
        if (!$this->obj_translate)
        {
            $classe = 'T'. ucfirst(TSession::getValue('unit_database')) . '_' . $this->nome . '_' . $this->idioma;
            $this->obj_translate = new $classe;
        }

        return $this->obj_translate;
    }

    public function get_system_group()
    {
        if (!$this->obj_system_group) {
            $this->obj_system_group =  TUtils::openConnection('permission', function () {
                return SystemGroup::where('name', '=', 'Double-Jogadores')->first();
            });
        }

        if (!$this->obj_system_group) 
            throw new Exception("Grupo '". 'Double-Jogadores' . "' não encontrado.");
        
        return $this->obj_system_group;
    }

    public function get_system_role()
    {
        if (!$this->obj_system_role) {
            $this->obj_system_role = TUtils::openConnection('permission', function() {
                return SystemRole::where('name', '=', 'Jogador')->first();
            });
        }

            if (!$this->obj_system_role) 
            throw new Exception("Papel 'Jogador' não encontrado.");
        
        return $this->obj_system_role;
    }


    public static function indentificar($nome, $idioma)
    {
        $plataforma = TUtils::openConnection('unit_database', function() use ($nome, $idioma) {
            return DoublePlataforma::where('LOWER(nome)', '=', $nome)
                ->where('idioma', '=', $idioma)
                ->where('ativo', '=', 'Y')
                ->first();
        });
       
        if (!$plataforma)
            throw new Exception("Plataforma não suportada.");

        return $plataforma;
    }
}
