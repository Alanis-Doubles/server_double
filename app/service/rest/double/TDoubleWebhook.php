<?php

class TDoubleWebhook 
{
    public static function processar($param)
    {
        if ( strtoupper($param['origem']) == 'KIRVANO') {
            $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
            if (!$plataforma)
                throw new Exception("Operação não suportada");

            $canal = null;
            if (isset($param['channel_id'])) {
                $canal = DoubleCanal::identificarPorChannel($param['channel_id']);
                if (!$canal)
                    throw new Exception("Operação não suportada");
            }

            if ($plataforma->usuarios_canal == 'Y' and !$canal)
                throw new Exception("Operação não suportada");

            try {
                TUtils::openConnection('double', function() use ($param, $plataforma, $canal){
                    $evento = ['SALE_APPROVED' => 'PAGAMENTO', 'SUBSCRIPTION_CANCELED' => 'CANCELAMENTO', 'SUBSCRIPTION_RENEWED' => 'RENOVACAO', 'SUBSCRIPTION_EXPIRED' => 'EXPIRACAO'];

                    $pagamento = DoublePagamentoHistorico::where('plataforma_pagamento_id', '=', $param['sale_id'])->first();
                    
                    if ($pagamento)
                        return $pagamento;

                    $pagamento = new DoublePagamentoHistorico;
                    $pagamento->plataforma_pagamento = strtoupper($param['origem']);
                    $pagamento->tipo = 'VITALICIO'; // str_replace('PLANO ', '', strtoupper($param['plan']['name']));
                    $pagamento->tipo_entrada = 'AUTOMATICA';
                    $pagamento->tipo_evento = $evento[$param['event']];
                    $pagamento->valor = floatval(str_replace(['R$ ', '.',',',' '], ['','','.',''], isset($param['total_price']) ? $param['total_price'] : '0'));
                    $pagamento->produto = $param['products'][0]['name'];
                    $pagamento->email = $param['customer']['email'];
                    $pagamento->plataforma_id = $plataforma->id;
                    $pagamento->canal_id = !$canal ? null : $canal->id;
                    $pagamento->plataforma_pagamento_id = $param['sale_id'];
                    $pagamento->payload = json_encode($param);

                    $sys_user = SystemUser::where('email', '=', $pagamento->email)
                        ->where('custom_code', 'IS NOT', NULL)
                        ->first();
                    if ($sys_user)
                    {
                        if ($canal)
                            $usuario = DoubleUsuario::where('chat_id', '=', $sys_user->custom_code)
                                ->where('plataforma_id', '=', $plataforma->id)
                                ->where('canal_id', '=', $canal->id)
                                ->first();
                        else
                            $usuario = DoubleUsuario::where('chat_id', '=', $sys_user->custom_code)
                                ->where('plataforma_id', '=', $plataforma->id)
                                ->first();
                        
                        if ($usuario)
                            $pagamento->usuario_id = $usuario->id;
                    }
                    $pagamento->save();

                    return $pagamento;
                });    
            } catch (\Throwable $e) {
                DoubleErros::registrar(1, 'TDoubleWebhook', 'processar', json_encode($param), $e->getMessage());
                throw new Exception("Pagamento não suportado.");
            } 
        }
        else
            throw new Exception("Pagamento não suportado.");

        
        unset($param["call_method"]);
        unset($param["class"]);
        unset($param["method"]);
        unset($param["origem"]);
        unset($param["plataforma"]);
        unset($param["idioma"]);
        return $param;
    }
}