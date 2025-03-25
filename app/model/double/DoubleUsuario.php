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
    const DELETEDAT  = 'deleted_at';

    use RecordTrait;

    private $user;
    private $obj_plataforma;
    private $obj_canal;
    private $obj_ultimo_pagamento;
    private $obj_usuario_meta;
    private $obj_usuario_objetivo;

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
        unset($this->plataforma);

        if (isset($this->ciclo_valor)) {
            $ciclo = $this->ciclo_valor;
            $ciclo_stop_loss = $this->ciclo_stop_loss_valor;
            if ($ciclo !== 'N' && $ciclo_stop_loss)
                $ciclo = $ciclo_stop_loss;

            $entrada_automatica = $this->entrada_automatica_valor;
            $tipo_entrada_automatica =  $this->apos_valor;
            if ($entrada_automatica !== 'N' && $tipo_entrada_automatica)
                $entrada_automatica = $tipo_entrada_automatica;

            if ($entrada_automatica == 'N')
                $this->valor_max_ciclo = 0;

            $this->ciclo              = $ciclo;
            $this->entrada_automatica = $entrada_automatica;

            unset($this->ciclo_valor);
            unset($this->ciclo_stop_loss_valor);
            unset($this->entrada_automatica_valor);
            unset($this->apos_valor);
        }

        $this->valor = $this->valor == null ? 0 : $this->valor;
        $this->protecao = $this->protecao == null ? 0 : $this->protecao;
        $this->stop_win = $this->stop_win == null ? 0 : $this->stop_win;
        $this->stop_loss = $this->stop_loss == null ? 0 : $this->stop_loss;
        $this->demo_jogadas = $this->demo_jogadas == null ? 0 : $this->demo_jogadas;
        $this->ciclo = $this->ciclo == null ? 'N' : $this->ciclo;
        // $this->robo_status = $this->roboStatus;
        // $this->robo_inicio = $this->roboInicio;
        $plataforma = new DoublePlataforma($this->plataforma_id, false);
        $system_group = $plataforma->system_group;
        $system_role = $plataforma->system_role;

        TUtils::openConnection('permission', function () use ($plataforma, $system_group, $system_role){
            $user = $this->buscarSystemUser();
            if (!$this->user->checkInGroup($system_group)) {
                $user->addSystemUserGroup($system_group);
                $user->save();
            }

            if (!$this->checkInUnit($user->unit)) {
                $user->addSystemUserUnit($user->unit);
                $user->save();
            }

            if (!$this->checkInRole($system_role)) {
                $user->addSystemUserRole($system_role);
                $user->save();
            }

            $dashboard = SystemProgram::where('controller', '=', 'TProfitDashboardUsuario')->first();
            if ($dashboard)
                $user->frontpage_id = $dashboard->id;
                $user->save();
        });

        $pk = $this->getPrimaryKey();
        $lastState = array();
        if (isset($this->$pk) and self::exists($this->$pk))
        {
            $lastState = parent::load($this->$pk, TRUE)->toArray();
            if ($lastState['status'] != $this->status)
                $this->data_envio_recuperacao = null;
        }

        unset($this->telefone);
        unset($this->nome);
        unset($this->nome_usuario);
        unset($this->nome_email);
        unset($this->email);
        
        parent::store();
    }

    /**
     * Check if the user is within a unit
     */
    public function checkInUnit( SystemUnit $unit )
    {
        $user_units = array();
        foreach( $this->user->getSystemUserUnits() as $user_unit )
        {
            $user_units[] = $user_unit->id;
        }
    
        return in_array($unit->id, $user_units);
    }

    /**
     * Check if the user is within a role
     */
    public function checkInRole( SystemRole $role )
    {
        $user_roles = array();
        foreach( $this->user->getSystemUserRoles() as $user_role )
        {
            $user_roles[] = $user_role->id;
        }
    
        return in_array($role->id, $user_roles);
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

    public static function identificarPorId($usuario_id)
    {
        return TUtils::openFakeConnection('double', function() use($usuario_id) {
            return new DoubleUsuario($usuario_id, false);
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

            // if (!$this->login)
            // {
            //     $old = SystemUserOldPassword::register($this->user->id, $value);
            //     if ($old)
            //         $conn = TTransaction::get();
            //         TDatabase::updateData(
            //             $conn, 
            //             'system_user_old_password',
            //             ['created_at' => date('Y-m-d H:i:s', strtotime('-91 days'))],
            //             TCriteria::create(['id' => $old->id])
            //         );
            //     $this->user->password = SystemUser::passwordHash($value);
            // }

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
        $usuario = TUtils::openFakeConnection('double', function () {
            return new self($this->id, false);
        });
        return $usuario->robo_status;
    }

    public function set_roboStatus($value)
    {
        TUtils::openConnection('double', function () use ($value) {
            $usuario = new self($this->id, false);
            $usuario->robo_status = $value;
            $this->robo_status = $value;
            $usuario->save();
        });
    }

    public function get_roboInicio()
    {
        $usuario =TUtils::openFakeConnection('double', function () {
            return new self($this->id, false);
        });
        return $usuario->robo_inicio;
    }

    public function set_roboInicio($value)
    {
        TUtils::openConnection('double', function () use ($value) {
            $usuario = new self($this->id, false);
            $usuario->robo_inicio = $value;
            $this->robo_inicio = $value;
            $usuario->save();
        });
    }

    public function valorJogada($estrategia_id)
    {
        $valor = $this->valor;
        // if ($this->metas == 'Y' and $this->usuario_meta)
        //     $valor = $this->usuario_meta->valor_real_entrada;

        if ($this->ciclo != 'N') {
            $result = TUtils::openFakeConnection('double', function () {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                    ->where('sequencia', '=', $this->robo_sequencia)
                    ->where('tipo', 'IN', ['WIN', 'LOSS'])
                    ->select(['valor', 'created_at', 'valor_entrada'])
                    ->last();
            });

            if ($result)
                if ($result->valor < 0) {
                    // if ($this->protecao_branco == 'N')
                    $fator_multiplicador = $this->fator_multiplicador;
                    // else 
                    //     $fator_multiplicador = 2.5 * 0.83333;

                    // if ($estrategia_id) {
                    //     $estrategia = TUtils::openFakeConnection('double', function() use ($estrategia_id){
                    //         return new DoubleEstrategia($estrategia_id, false);
                    //     });

                    //     if ($estrategia and $estrategia->resetar_valor_entrada == 'A_CADA_HORA') {
                    //         $date = date_create_from_format('Y-m-d H:i:s', $result->created_at);
                    //         $now = new DateTime();

                    //         if ($now->format('H') > $date->format('H'))
                    //             $valor_novo = $valor;
                    //         else
                    //             $valor_novo = round($result->valor_entrada * $fator_multiplicador, 2);
                    //     }
                    //     elseif ($estrategia and $estrategia->resetar_valor_entrada == 'SEMPRE')
                    //         $valor_novo = $valor;
                    //     else
                    //         $valor_novo = round($result->valor_entrada * $fator_multiplicador, 2);
                    // }    
                    // else
                    $valor_novo = round($result->valor_entrada * $fator_multiplicador, 2);

                    return $valor_novo;
                }
                else
                    return $valor;
            else
                return $valor;
        } else
            return $valor;
    }

    public function valorJogadaBranco()
    {
        $valor = $this->valor_branco;

        if ($this->ciclo != 'N') {
            $result = TUtils::openFakeConnection('double', function () {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $this->id)
                    ->where('sequencia', '=', $this->robo_sequencia)
                    ->where('tipo', 'IN', ['WIN', 'LOSS'])
                    ->select(['valor', 'created_at', 'valor_branco'])
                    ->last();
            });

            if ($result)
                if ($result->valor < 0) {
                    $fator_multiplicador = $this->fator_multiplicador_branco;
                    $valor_novo = round($result->valor_branco * $fator_multiplicador, 2);

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
                // ->where('sequencia', '=', $this->robo_sequencia)
                ->where('created_at', '>=', $this->robo_inicio)
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
    
    public function generate_access($email) {
        $value = TUtils::gerarSenhaAleatoria(12);
        TUtils::openConnection('permission', function() use ($email, $value) {
            if (!$this->user)
                $this->user = $this->buscarSystemUser();
        
            $conn = TTransaction::get();
            TDatabase::clearData(
                $conn, 
                'system_user_old_password',
                TCriteria::create(['system_user_id' => $this->user->id])
            );

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

            // $this->user->login = $email;
            $this->user->email = $email;
            $this->user->save();
        });

        return $value;
    }

    public function tst_generate_access($email, $senha) {
        $user = TUtils::openFakeConnection('permission', function () {
            return  $this->buscarSystemUser();
        });

        $value['email'] = $email;
        $value['senha'] = SystemUser::passwordHash($senha);
        $value['senha_atual'] = $user->password;

        return $value;
    }

    public function get_possui_estrategias() 
    {
        $estrategias = TUtils::openFakeConnection('double', function(){
            return DoubleEstrategia::where('usuario_id', '=', $this->id)
                ->where('ativo', '=', 'Y')
                ->where('deleted_at', 'is', null)
                ->first();
        });

        if ($estrategias)
            return true;
        else
            return false;
    }

    public function get_configuracao_texto() {
        $translate = $this->plataforma->translate;

        $valores_expiracao = ['5' => '5 segundos', '10' => '10 segundos', '15' => '15 segundos',
            '30' => '30 segundos', '45' => '45 segundos', '60' => '1 minuto',
            '120' => '2 minutos', '180' => '3 minutos', '300' => '5 minutos'];

        $texto = $translate->MSG_INICIO_ROBO_6;
        $msg = str_replace(
            [ 
                '{usuario}', 
                '{banca}', 
                '{value}', 
                '{gales}', 
                '{stop_win}', 
                '{stop_loss}', 
                '{ciclo}', 
                '{protecao_branco}', 
                '{entrada_automatica}',
                '{expiracao}',
                '{fator_multiplicador}',
                '{classificacao}'
            ],
            [
                $this->nome,
                number_format($this->plataforma->service->saldo($this), 2, ',', '.'),
                number_format($this->valor, 2, ',', '.'),
                $this->protecao,
                number_format($this->stop_win, 2, ',', '.'),
                number_format($this->stop_loss, 2, ',', '.') . '[' . ucfirst($this->tipo_stop_loss) . ']',
                $this->ciclo == 'N' ? 'Não Habilitado' : 'Habilitado',
                $this->protecao_branco == 'Y' ? 'Habilitado' : 'Não habilitado',
                $this->entrada_automatica == 'N' ? 'Não habilitado' : 'Habilitado',
                $valores_expiracao[$this->expiration],
                number_format($this->fator_multiplicador, 2, ',', '.'),
                $this->classificacao
            ],
            $texto
        );

        if ($this->entrada_automatica == 'Y')
            $msg .= '\n     - Ocorrerá após o Stop WIN';
        if ($this->entrada_automatica == 'A')
            $msg .= '\n     - Ocorrerá após o Stop WIN e Stop LOSS';
        if ($this->entrada_automatica == 'B')
            $msg .= '\n     - Ocorrerá após o Stop LOSS';

        if (($this->entrada_automatica == 'A' or $this->entrada_automatica == 'B') and $this->ciclo == 'A') {
            $msg .= str_replace(
                ['{ciclo}'],
                [$translate->MSG_CICLO_7],
                '\n     - {ciclo} habilitado para o Stop LOSS'
            );

            if ($this->valor_max_ciclo > 0)
                $msg .= str_replace(
                    ['{ciclo}', '{valor_max_ciclo}'],
                    [
                        $translate->BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO,
                        number_format($this->valor_max_ciclo, 2, ',', '.')
                    ],
                    '\n     - {ciclo}: {valor_max_ciclo}'
                );
        }

        if ($this->entrada_automatica != 'N')
            $msg .= str_replace(
                ['{quantidade}', '{tipo}'],
                [$this->entrada_automatica_total_loss, $this->entrada_automatica_tipo],
                '\n     - Será esperado a ocorrência de {quantidade} {tipo}'
            );

        return $msg;
    }

    public function get_usuario_objetivo()
    {
        if (!$this->obj_usuario_objetivo) {
            $this->obj_usuario_objetivo =  TUtils::openConnection('double', function () {
                return DoubleUsuarioObjetivo::where('usuario_id', '=', $this->id)
                    ->first();
            });
        }
        
        return $this->obj_usuario_objetivo;
    }

    public function get_status_objetivo()
    {
        if ($this->usuario_objetivo)
            return $this->usuario_objetivo->status;
        else
            return 'PARADO';
    }
}
