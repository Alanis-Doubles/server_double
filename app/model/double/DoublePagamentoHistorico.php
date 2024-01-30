<?php

use Adianti\Database\TRecord;
use Adianti\Widget\Form\TDate;

class DoublePagamentoHistorico extends DoubleRecord
{
    const TABLENAME  = 'double_pagamento_historico';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function store()
    {
        unset($this->nome);
        unset($this->nome_completo);
        unset($this->nome_usuario);
        unset($this->nome_email);

        if ($this->tipo_evento == 'CANCELAMENTO' and $this->valor > 0) 
            $this->valor *= -1;

        parent::store();

        if (isset($this->usuario_id)) {
            $usuario = new DoubleUsuario($this->usuario_id);
            
            if ($this->tipo_evento == 'CANCELAMENTO') {
                switch ($this->tipo) {                  
                    case 'TRIMESTRAL':
                        $meses = "- 3 month";
                        break;
                    case 'SEMESTRAL':
                        $meses = "- 6 month";
                        break;
                    case 'ANUAL':
                        $meses = "- 1 month";
                        break;

                    default:
                        $meses = "- 1 month";
                        break;    
                }

                if ($usuario->data_expiracao)
                    $data = date('Y-m-d', strtotime($meses, strtotime($usuario->data_expiracao)));
                else
                    $data = date('Y-m-d', strtotime($meses));

                $usuario->data_expiracao = $data;
                if (date('Y-m-d') > $data)
                    $usuario->status = 'INATIVO';
                if ($this->email)
                    $usuario->email = $this->email;
                $usuario->save();

                $plataforma = $usuario->plataforma;

                if ($usuario->logado == 'N')
                    $botao = [
                        "resize_keyboard" => true, 
                        "keyboard" => [
                                [["text" => $plataforma->translate->BOTAO_LOGAR],["text" => $plataforma->translate->BOTAO_CADASTRO]], 
                            ] 
                        ];
                else
                    $botao = [
                        "resize_keyboard" => true, 
                        "keyboard" => [
                                [["text" => $plataforma->translate->BOTAO_CONFIGURAR]],
                                [["text" => $plataforma->translate->BOTAO_INICIAR], ["text" => $plataforma->translate->BOTAO_INICIAR_LOSS]], 
                            ] 
                        ];


                if ($usuario->status == 'INATIVO') {
                    $telegram = $plataforma->telegram;
                    $telegram->sendMessage(
                        $usuario->chat_id,
                        str_replace(
                            ['{usuario}'],
                            [$usuario->nome],
                            $plataforma->translate->MSG_STATUS_INATIVO,
                        ),
                        $botao                
                    );
                }
            } elseif ($this->tipo_evento == 'PAGAMENTO') {
                switch ($this->tipo) {                  
                    case 'TRIMESTRAL':
                        $meses = "+ 3 month";
                        break;
                    case 'SEMESTRAL':
                        $meses = "+ 6 month";
                        break;
                    case 'ANUAL':
                        $meses = "+ 1 month";
                        break;

                    default:
                        $meses = "+ 1 month";
                        break;    
                }

                if ($usuario->data_expiracao)
                    $data = date('Y-m-d', strtotime($meses, strtotime($usuario->data_expiracao)));
                else
                    $data = date('Y-m-d', strtotime($meses));

                $usuario->data_expiracao = $data;
                $usuario->status = 'ATIVO';
                $usuario->save();

                $plataforma = $usuario->plataforma;

                if ($usuario->logado == 'N')
                    $botao = [
                        "resize_keyboard" => true, 
                        "keyboard" => [
                                [["text" => $plataforma->translate->BOTAO_LOGAR],["text" => $plataforma->translate->BOTAO_CADASTRO]], 
                            ] 
                        ];
                else
                    $botao = [
                        "resize_keyboard" => true, 
                        "keyboard" => [
                                [["text" => $plataforma->translate->BOTAO_CONFIGURAR]],
                                [["text" => $plataforma->translate->BOTAO_INICIAR], ["text" => $plataforma->translate->BOTAO_INICIAR_LOSS]], 
                            ] 
                        ];


                $telegram = $plataforma->telegram;
                $telegram->sendMessage(
                    $usuario->chat_id,
                    str_replace(
                        ['{dia_expiracao}'],
                        [TDate::convertToMask($data, 'yyyy-mm-dd', 'dd/mm/yyyy')],
                        $plataforma->translate->MSG_CONTA_ATIVADA,
                    ),
                    $botao                
                );
            }
        }
    }
}
