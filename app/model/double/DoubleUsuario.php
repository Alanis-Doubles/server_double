<?php

use Adianti\Database\TCriteria;
use Adianti\Database\TDatabase;
use Adianti\Database\TRecord;
use Adianti\Database\TTransaction;

class DoubleUsuario extends DoubleRecord
{
    const TABLENAME  = 'double_usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    private $user;
    private $obj_plataforma;
    private $obj_canal;
    private $obj_ultimo_pagamento;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function get_logado()
    {
        return $this->token_plataforma ? 'Y' : 'N';
    }

    public function load($id)
    {
        $object = parent::load($id);
        $this->obj_ultimo_pagamento = DoublePagamentoHistorico::where('usuario_id', '=', $this->id)
            ->where('tipo_evento', '=', 'PAGAMENTO')
            ->last();

        return $object;
    }

    public function store()
    {
        unset($this->usuarios_canal);
        unset($this->usuario_canal);
        $this->valor = $this->valor == null ? 0 : $this->valor;
        $this->protecao = $this->protecao == null ? 0 : $this->protecao;
        $this->stop_win = $this->stop_win == null ? 0 : $this->stop_win;
        $this->stop_loss = $this->stop_loss == null ? 0 : $this->stop_loss;
        $this->demo_jogadas = $this->demo_jogadas == null ? 0 : $this->demo_jogadas;
        $this->ciclo = $this->ciclo == null ? 'N' : $this->ciclo;
        $plataforma = new DoublePlataforma($this->plataforma_id, false);
        $system_group = $plataforma->system_group;
        $system_role = $plataforma->system_role;
        TUtils::openConnection('permission', function () use ($plataforma, $system_group, $system_role){
            $user = $this->buscarSystemUser();
            if (!$this->user->checkInGroup($system_group)) {
                $user->addSystemUserGroup($system_group);
                $user->save();
            }

            if (!$this->user->checkInUnit($user->unit)) {
                $user->addSystemUserUnit($user->unit);
                $user->save();
            }

            if (!$this->user->checkInRole($system_role)) {
                $user->addSystemUserRole($system_role);
                $user->save();
            }
        });

        $pk = $this->getPrimaryKey();
        $lastState = array();
        if (isset($this->$pk) and self::exists($this->$pk))
        {
            $lastState = parent::load($this->$pk, TRUE)->toArray();
            if ($lastState['status'] != $this->status)
                $this->data_envio_recuperacao = null;
        }
        
        parent::store();
    }

    public static function identificar($chat_id, $plataforma_id, $canal_id)
    {
        return TUtils::openFakeConnection('double', function() use($chat_id, $plataforma_id, $canal_id) {
            return DoubleUsuario::where('chat_id', '=', $chat_id)
                ->where('plataforma_id', '=', $plataforma_id)
                ->where('canal_id', '=', $canal_id)
                ->first();
        });
    }

    private function buscarSystemUser()
    {
        $this->user = SystemUser::where('custom_code', '=', $this->chat_id)->first();
        if (!$this->user)
        {
            $this->user = new SystemUser();
            $this->user->custom_code = $this->chat_id;
            $this->user->system_unit_id = TSession::getValue('userunitid');
            $this->user->save();
        }

        return $this->user;
    }

    public function get_nome()
    {
        if (!$this->user) {
            $this->user = TUtils::openFakeConnection('permission', function () {
                return $this->buscarSystemUser();
            });
        }

        return $this->user->name;
    }

    public function get_telefone()
    {
        if (!$this->user) {
            $this->user = TUtils::openFakeConnection('permission', function () {
                return $this->buscarSystemUser();
            });
        }

        return $this->user->phone;
    }

    public function get_nome_completo()
    {
        if (!$this->user) {
            $this->user = TUtils::openFakeConnection('permission', function () {
                return $this->buscarSystemUser();
            });
        }

        return '['.$this->chat_id.'] '. $this->user->name;
    }

    public function set_nome($value)
    {
        TUtils::openConnection('permission', function() use ($value) {
            if (!$this->user)
                $this->user = $this->buscarSystemUser();

            $this->user->name = $value;
            $this->user->save();
        });
    }

    public function set_telefone($value)
    {
        TUtils::openConnection('permission', function() use ($value) {
            if (!$this->user)
                $this->user = $this->buscarSystemUser();

            $this->user->phone = $value;
            $this->user->save();
        });
    }

    public function get_nome_usuario()
    {
        if (!$this->user) {
            $this->user = TUtils::openFakeConnection('permission', function () {
                return $this->buscarSystemUser();
            });
        }

        return $this->user->login;
    }

    public function set_nome_usuario($value)
    {
        TUtils::openConnection('permission', function() use ($value) {
            if (!$this->user)
            $this->user = $this->buscarSystemUser();

            if (!$this->login)
            {
                $old = SystemUserOldPassword::register($this->user->id, $value);
                if ($old)
                    $conn = TTransaction::get();
                    TDatabase::updateData(
                        $conn, 
                        'system_user_old_password',
                        ['created_at' => date('Y-m-d H:i:s', strtotime('-91 days'))],
                        TCriteria::create(['id' => $old->id])
                    );
                $this->user->password = SystemUser::passwordHash($value);
            }

            $this->user->login = $value;
            $this->user->save();
        });
    }

    public function get_email()
    {
        if (!$this->user) {
            $this->user = TUtils::openFakeConnection('permission', function () {
                return $this->buscarSystemUser();
            });
        }

        return $this->user->email;
    }

    public function set_email($value)
    {
        TUtils::openConnection('permission', function() use ($value) {
            if (!$this->user)
                $this->user = $this->buscarSystemUser();

            $this->user->email = $value;
            $this->user->save();
        });
    }

    public function get_roboStatus()
    {
        $plataforma = TUtils::openFakeConnection('double', function () {
            return new self($this->id, false);
        });

        return $plataforma->robo_status;
    }

    public function set_roboStatus($value)
    {
        TUtils::openConnection('double', function () use ($value) {
            $plataforma = new self($this->id, false);
            $plataforma->robo_status = $value;
            $plataforma->save();
        });
    }

    public function get_roboInicio()
    {
        $plataforma =TUtils::openFakeConnection('double', function () {
            return new self($this->id, false);
        });
        
        return $plataforma->robo_inicio;
    }

    public function set_roboInicio($value)
    {
        TUtils::openConnection('double', function () use ($value) {
            $plataforma = new self($this->id, false);
            $plataforma->robo_inicio = $value;
            $plataforma->save();
        });
    }

    public function get_valorJogada()
    {
        if ($this->ciclo != 'N') {
            $result = TUtils::openFakeConnection('double', function () {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                    ->where('sequencia', '=', $this->robo_sequencia)
                    ->select(['valor'])
                    ->last();
            });

            if ($result)
                if ($result->valor < 0)
                    return $result->valor * -2;
                else
                    return $this->valor;
            else
                return $this->valor;
        } else
            return $this->valor;
    }

    public function get_lucro()
    {
        $result = TUtils::openFakeConnection('double', function() {
            return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                ->where('sequencia', '=', $this->robo_sequencia)
                ->sumBy('valor', 'total');
        });
        
        if ($result)
            return $result;
        else
            return 0;
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

    public function get_agrupamento()
    {
        $result = $this->plataforma->render('[{idioma}] {nome}');
        if ($this->plataforma->usuarios_canal == 'Y')
            $result .= ' / ' . $this->canal->nome;

        return $result;
    }

    public function get_canal()
    {
        if (!$this->obj_canal) {
            $this->obj_canal =  TUtils::openConnection('double', function () {
                $result = new DoubleCanal($this->canal_id, false);
                if (!$result)
                    $result = new DoubleCanal();
                return $result;
            });
        }
        
        return $this->obj_canal;
    }

    public function get_ultimo_pagamento()
    {
        return $this->obj_ultimo_pagamento;
    }
}
