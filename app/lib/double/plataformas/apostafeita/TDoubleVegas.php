<?php

use WebSocket\Client;
use Predis\Client as RedisClient;

class TDoubleVegas implements IDoublePlataforma
{
    public static function validate(string $nome)
    {
        return substr($nome, 0, 11) == 'DoubleVegas';
    }

    public static function nome()
    {
        return 'DoubleVegas';
    }

    public function sinalCorrente() {
        
    }

    public function aguardarSinal($ultimo_sinal)
    {
        
    }

    public function ultimoSinal()
    {
        return ;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        
    }

    public function saldo(DoubleUsuario $usuario)
    {
        
    }

    public function logar(string $usuario, string $senha)
    {
        
    }

    public function cores()
    {
        return [
            0  => 'white',
            1  => 'red',
            2  => 'red',
            3  => 'red',
            4  => 'red',
            5  => 'red',
            6  => 'red',
            7  => 'red',
            8  => 'red',
            9  => 'red',
            10 => 'red',
            11 => 'black',
            12 => 'black',
            13 => 'black',
            14 => 'black',
            15 => 'black',
            16 => 'black',
            17 => 'black',
            18 => 'black',
            19 => 'black',
            20 => 'black',
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        return 'Não implementado';
    }

    public function buscar_sinais($param){
        $plataforma = DoublePlataforma::indentificar($param['plataforma'], $param['idioma']);
        $serverName = DoubleConfiguracao::getConfiguracao('server_name');
        $queue = strtolower("{$serverName}_{$plataforma->nome}_{$plataforma->idioma}_buscar_sinais");

        $redis = new RedisClient();

        try {
            $client = new Client("wss://hypetech-api-yuj39.ondigitalocean.app/socket.io/?EIO=4&transport=websocket");
        
            while (true) {
                try {
                    // Receber mensagem do servidor
                    $message = $client->receive();

                     // Verificar o prefixo da mensagem
                     if (!$message)
                        continue;

                    if (substr($message, 0, 2) == '42') {
                        $message = substr($message, 2); // Remover o '42'

                        // Decodificar a mensagem como JSON
                        $decodedMessage = json_decode($message, true);

                        if (isset($decodedMessage[0]) && $decodedMessage[0] === "registered-transactions" && isset($decodedMessage[1])) {
                            continue;
                        }

                        if (isset($decodedMessage[0]) && $decodedMessage[0] === "start-timeout") {
                            $timeoutValue = $decodedMessage[1];
                            if ($timeoutValue >= 7) {
                                $redis->publish($queue, 'Fazer entrada');
                                echo "Fazer entrada\n";
                            }
                        } elseif (isset($decodedMessage[0]) && $decodedMessage[0] === "start-game" && isset($decodedMessage[1])) {
                            // Extrair os dados do objeto
                            $gameData = $decodedMessage[1];
                            
                            // Acessar os valores
                            $hash = $gameData['hash'];
                            $slice = $gameData['slice'];
                            $decimal = $gameData['decimal'];
                            $value = $gameData['value'];
                            $color = $gameData['color'];

                            $payload = [
                                'id'    => $slice,
                                'roll'  => $value,
                                'color' => $color
                            ];
                            $redis->publish($queue, json_encode($payload));

                            // Tratar os valores (exemplo: exibir no terminal)
                            echo "\nEvento: start-game\n";
                            echo "Hash: $hash\n";
                            echo "Slice: $slice\n";
                            echo "Decimal: $decimal\n";
                            echo "Value: $value\n";
                            echo "Color: $color\n";
                        }
                    } elseif (substr($message, 0, 1) == '0') {
                        $client->send('40'.json_encode(['token' => '212cc28168004389c331844548152aaa']));
                        $client->send('42["get-results-history"]');
                    }
                    // sleep(1);
                } catch (Exception $e) {
                    // Caso haja um erro, exibir e sair do loop
                    echo "Erro ao receber mensagem: " . $e->getMessage() . "\n";
                    break;
                }
            }
        
            // Fechar a conexão quando o loop for interrompido
            $client->close();
        
        } catch (Exception $e) {
            echo "Erro de conexão: " . $e->getMessage();
        }
    }
}