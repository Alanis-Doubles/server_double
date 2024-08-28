<?php

use Predis\Client;
use Ramsey\Uuid\Type\Integer;

class TDoubleSinaisPublisher extends TDoubleRedis
{
    private $ultimo_id = '';
    private $fazer_entrada;

    public function buscar_sinais(Int $plataforma_id, IDoublePlataforma $service)
    {
        try {
            $response = $service->sinalCorrente();
            $sinal = $response['data'];
            // echo "{$response['status_code']}\n" ;
            if ($response['status_code'] == 200) {
                if ($sinal->status == 'rolling') {
                    if ($this->ultimo_id !== $sinal->id)
                    {
                        $this->ultimo_id = $sinal->id;
                        $json = [
                            'plataforma_id' => $plataforma_id,
                            'numero' => $sinal->roll,
                            'cor' => $service->cores()[$sinal->roll],
                            'id_referencia' => $sinal->id,
                            'created_at' => (new DateTime())->format('Y-m-d H:i:s')
                        ];
                        return json_encode($json);
                    }
                } elseif ($sinal->status == 'waiting') {
                    return 'fazer_entrada';
                }
            }
        } catch (\Throwable $th) {
            DoubleErros::registrar($plataforma_id, 'TDoubleSinaisPublisher', 'buscar_sinais', $th->getMessage());
        }
        return null;
    }    

    public function notificar_consumidores($plataforma, $sinal)
    {
        echo "send message: {$sinal}\n";

        $object = json_decode($sinal);
        $sinal = TUtils::openConnection('double', function() use ($object) {
            $sinal = new DoubleSinal();
            $sinal->plataforma_id = $object->plataforma_id;
            $sinal->numero = $object->numero;
            $sinal->cor = $object->cor;
            $sinal->id_referencia = $object->id_referencia;
            $sinal->save();

            return json_encode($sinal->toArray());
        });

        $channel_name = strtolower("{$this->serverName()}_{$plataforma->nome}_{$plataforma->idioma}_sinais");

        $redis = new Client();
        $redis->publish($channel_name, $sinal);
        $redis->set($this->fazer_entrada, false);
        echo "{$channel_name} - {$sinal}\n";
    }

    public function notificar_fazer_entrada($value, $plataforma)
    {
        $channel_name = strtolower("{$this->serverName()}_{$plataforma->nome}_{$plataforma->idioma}_notificar_entrada");

        $redis = new Client();
        $redis->set($this->fazer_entrada, $value);
        if ($value) {
            $redis->publish($channel_name, json_encode(['fazer_entrada' => true]));
            echo "Fazer entrada\n";
        }
    }

    public function run($param){
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $service = $plataforma->service;
        $this->fazer_entrada = strtolower("{$this->serverName()}_{$plataforma->nome}_{$plataforma->idioma}_fazer_entrada");

        $plataforma->statusSinais = 'INICIANDO';
        sleep(5);
        $plataforma->statusSinais = 'EXECUTANDO';

        while ($plataforma->statusSinais == 'EXECUTANDO') {
            $sinais = $this->buscar_sinais($plataforma->id, $service);
            if ($sinais) {
                if ($sinais == 'fazer_entrada') {
                    $this->notificar_fazer_entrada(true, $plataforma);
                } else {
                    $this->notificar_consumidores($plataforma, $sinais);
                }
            } else {
                $this->notificar_fazer_entrada(false, $plataforma);
            }
            sleep(2); 
        }
        $this->notificar_consumidores($plataforma, '');

        $plataforma->statusSinais = 'PARADO';
    }
}