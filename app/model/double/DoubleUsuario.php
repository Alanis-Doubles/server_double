<?php

use Adianti\Database\TCriteria;
use Adianti\Database\TDatabase;
use Adianti\Database\TRecord;
use Adianti\Database\TTransaction;

class DoubleUsuario extends DoubleRecord
{
    const TABLENAME  = 'double_usuario';
    const PRIMARYKEY = 'id';
        const IDPOLICY   = 'serial';

    use RecordTrait;

    private $user;
    private $obj_plataforma;
    private $obj_canal;
    private $obj_ultimo_pagamento;
    private $obj_usuario_meta;

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

    public function valorJogada($estrategia_id)
    {
        $valor = $this->valor;
        if ($this->metas == 'Y' and $this->usuario_meta)
            $valor = $this->usuario_meta->valor_real_entrada;


        if ($this->ciclo != 'N') {
            $result = TUtils::openFakeConnection('double', function () {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                    ->where('sequencia', '=', $this->robo_sequencia)
                    ->select(['valor', 'created_at'])
                    ->last();
            });

            if ($result)
                if ($result->valor < 0) {
                    if ($this->protecao_branco == 'N')
                        $fator_multiplicador = $this->fator_multiplicador;
                    else 
                        $fator_multiplicador = 2.5 * 0.83333;

                    if ($estrategia_id) {
                        $estrategia = TUtils::openFakeConnection('double', function() use ($estrategia_id){
                            return new DoubleEstrategia($estrategia_id, false);
                        });

                        if ($estrategia and $estrategia->resetar_valor_entrada == 'A_CADA_HORA') {
                            $date = date_create_from_format('Y-m-d H:i:s', $result->created_at);
                            $now = new DateTime();

                            if ($now->format('H') > $date->format('H'))
                                $valor_novo = $valor;
                            else
                                $valor_novo = round($result->valor * -$fator_multiplicador, 2);
                        }
                        elseif ($estrategia and $estrategia->resetar_valor_entrada == 'SEMPRE')
                            $valor_novo = $valor;
                        else
                            $valor_novo = round($result->valor * -$fator_multiplicador, 2);
                    }    
                    else
                        $valor_novo = round($result->valor * -$fator_multiplicador, 2);

                    return $valor_novo;
                }
                else
                    return $valor;
            else
                return $valor;
        } else
            return $valor;
    }

    public function get_lucro()
    {
        $result = TUtils::openFakeConnection('double', function() {
            return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                ->where('sequencia', '=', $this->robo_sequencia)
                ->sumBy('valor', 'total');
        });
        
        if ($result != null)
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

    public function get_canal_id_ref()
    {
        $canal = $this->get_canal();
        if ($canal->ativo == 'N') {
            $canal = TUtils::openConnection('double', function () {
                $result = DoubleCanal::where('plataforma_id', '=', $this->plataforma_id)
                    ->where('ativo', '=', 'Y')
                    ->first();
                return $result;
            });
        }

        return $canal->id;
    }

    public function get_ultimo_pagamento()
    {
        return $this->obj_ultimo_pagamento;
    }

    public function get_usuario_meta()
    {
        if (!$this->obj_usuario_meta) {
            $this->obj_usuario_meta =  TUtils::openConnection('double', function () {
                return DoubleUsuarioMeta::where('usuario_id', '=', $this->id)
                    ->first();
            });
        }
        
        return $this->obj_usuario_meta;
    }
}
