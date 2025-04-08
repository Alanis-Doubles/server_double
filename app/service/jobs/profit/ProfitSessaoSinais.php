<?php

use Predis\Client;

class ProfitSessaoSinais
{
    static public function verificar_sessao_sinais() {

        $horarios_sessao = TUtils::openFakeConnection('double', function() {
            $canal = DoubleCanal::where('nome', '=', 'Playbroker')->first();
            return $canal->horario_sessao;
        });

        if (!$horarios_sessao) {
            return;
        }
        $horarios = explode(',', $horarios_sessao);
        $horario_atual = date('H:i');

        if (in_array($horario_atual, $horarios)) {
            $redis = new Client([
                'scheme' => 'tcp',
                'host'   => DoubleConfiguracao::getConfiguracao('host_usuario'), // IP do seu Redis
                'port'   => 6379, // Porta padrão do Redis
            ]);
    
            $redis->publish("profit_sessao", 'iniciar');
            echo "- Iniciando sessão de sinais";
        } else {
            throw new Exception("Não é horário de sessão de sinais", 1);
        }
    }
}