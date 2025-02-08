<?php

use GuzzleHttp\Client as GuzzleClient;
use Predis\Client;

class TBacboMCGames extends TMCGames
{
    public static function validate(string $nome)
    {
        return substr($nome, 0, 12) == 'BacboMCGames';
    }

    public static function nome()
    {
        return 'BacboMCGames';
    }

    public function resetarBancaTreinamento(DoubleUsuario $usuario){}

    public function sinalCorrente() {
        $redis = new Client([
            'persistent' => true,
            'read_write_timeout' => -1
        ]);
        $pubsub = $redis->pubSubLoop();
        $channel = 'bacbo_sinal';

        $pubsub->subscribe($channel);

        foreach ($pubsub as $message) {
            $message = (object) $message;

            if ($message->kind === 'message') {
                echo "received message: {$message->channel} - {$message->payload}\n";
                $pubsub->unsubscribe($channel);
                return [
                    "status_code" => 200,
                    "data" => json_decode($message->payload)
                ];
            }
        } 
    }

    public function aguardarSinal($ultimo_sinal)
    {
        
    }


    public function cores()
    {
        return [
            0  => 'white',
            1  => 'black',
            2  => 'red'
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        if ($valor > $usuario->ultimo_saldo)
            return 'saldo_insuficiente';

        if ($usuario->modo_treinamento == 'Y') 
            return '';

        if ($valor > $usuario->ultimo_saldo)
            return 'saldo_insuficiente';

        $payload = [
            'color' => ['white' => 'Tie', 'red' => 'Banker', 'black' => 'Player'][$cor],
            'amount' => $valor
        ];

        if (!$usuario->servidor_conectado)
            return 'nao_conectado';
        
        $server_name = DoubleConfiguracao::getConfiguracao('server_name');

        $chave = "{$usuario->plataforma->nome}_{$usuario->plataforma->idioma}_{$usuario->canal->nome}_{$usuario->id}_jogar";

        if ($cor == "white")
            $chave += "_branco";

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => "localhost", // IP do Redis
            'port'   => 6379,             // Porta padrão do Redis
        ]);

        // $redis->publish($chave, json_encode($payload));

        $redis->set(
           $chave,
           json_encode($payload), 
            'EX', 
            10
        );

        return '';
    }
}