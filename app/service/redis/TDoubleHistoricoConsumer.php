<?php

use Predis\Client;

class TDoubleHistoricoConsumer extends TDoubleRedis
{
    public function run($param)
    {
        $channel_name = strtolower("{$this->serverName()}_canal_historico");
       
        $redis = new Client([
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        $pubsub = $redis->pubSubLoop();

        $pubsub->subscribe($channel_name);
        
        foreach ($pubsub as $message) {
            $message = (object) $message;
            if ($message->kind === 'message') {
                echo "{$message->channel} - {$message->payload}\n";
                $this->processar_historico($message);
            }
        }        
    }

    private function processar_historico($message){
        $historico = json_decode($message->payload, true);
        if (isset($historico['usuario_id'])) {
            $this->propagarUsuario($historico);
        } else {
            $this->gerarStatusCanal($historico);
        }
    }

    private function propagarUsuario($historico)
    {
        $redis = new Client();
        $channel_name = strtolower("{$this->serverName()}_{$historico['usuario_id']}_usuario_historico");
        $hist = json_encode($historico);
        $redis->publish($channel_name, $hist);
        echo "send message: {$channel_name} - {$hist}\n";
    }

    private function gerarStatusCanal($historico)
    {
        $canal_id = $historico['canal_id'];
        $canal = DoubleCanal::identificar($canal_id);

        $redis = new Client();
        $channel_name = strtolower("{$this->serverName()}_{$canal->channel_id}_usuario_historico");
        $hist = json_encode($historico);
        $redis->publish($channel_name, $hist);
        echo "send message: {$channel_name} - {$hist}\n";

        if (!in_array($historico['tipo'] , ['WIN', 'LOSS']))
            return;

        $result = TUtils::openFakeConnection('double', function () {
            $conn = TTransaction::get();

            $sql = "SELECT IFNULL(SUM(CASE WHEN dh.tipo = 'win' THEN CASE WHEN dh.cor != 'white' THEN 1 ELSE 0 END ELSE 0 END), 0) AS total_win,
                           IFNULL(SUM(CASE WHEN dh.tipo = 'win' THEN CASE WHEN dh.cor = 'white' THEN 1 ELSE 0 END ELSE 0 END), 0) AS total_win_white,
                           IFNULL(SUM(CASE WHEN dh.tipo = 'loss' THEN 1 ELSE 0 END), 0) AS total_loss,
                           COUNT(1) total
                      FROM double_historico dh
                     WHERE DATE(dh.created_at) >= DATE_ADD(CURDATE(), INTERVAL 0 DAY)
                       AND dh.usuario_id IS null
                       AND dh.tipo IN ('WIN', 'LOSS')";

            $list = TDatabase::getData(
                $conn, 
                $sql
            );

            return $list;
        });

        if (count($result) > 0) {
            $percentual = 0;
            $win = $result[0]['total_win'] ;
            $branco = $result[0]['total_win_white'] ;
            $loss = $result[0]['total_loss'] ;
            $total = $win + $loss + $branco;

            if ($total > 0)
                $percentual = round((($win + $branco) / $total) * 100, 1);

            if ($canal->enviarSinais())
                TRedisUtils::sendMessage(
                    $canal->channel_id,
                    $canal->telegram_token,
                    str_replace(
                        ['{win}', '{loss}', '{percentual}'],
                        [$win + $branco, $loss, $percentual],
                        $canal->plataforma->translate->MSG_SINAIS_PARCIAL_DIA,
                    ),
                );

            $acertos = $win - $loss;
            $valor = ($acertos * 20);
            if ($valor < 0)
                $valor = $valor * ($canal->protecoes + 1);
            $valor = $valor + (14 * 4 * $branco);
            if ($canal->exibir_projecao == 'Y' and $valor > 0)
            {
                $valor = number_format($valor, 2, ',', '.');
                
                if ($canal->enviarSinais())
                    TRedisUtils::sendMessage(
                        $canal->channel_id,
                        $canal->telegram_token,
                        str_replace(
                            ['{valor}'],
                            [$valor],
                            $canal->plataforma->translate->MSG_SINAIS_PROJECAO,
                        ),
                    );
            }
        }
    }
}