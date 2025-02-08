<?php

use GuzzleHttp\Client;

class TMCGames extends TDoublePlataforma
{
    private static $ultimo_sinal;

    public static function validate(string $nome)
    {
        return substr($nome, 0, 7) == 'MCGames';
    }

    public static function nome()
    {
        return 'MCGames';
    }

    public function sinalCorrente() {
    }

    public function aguardarSinal($ultimo_sinal)
    {
    }

    public function ultimoSinal()
    {
        return self::$ultimo_sinal->numero;
    }

    public function getToken(DoubleUsuario $usuario)
    {
        $now = new DateTime();
        if ($usuario->token_expiracao)
            $expiracao = date_create_from_format('Y-m-d H:i:s', $usuario->token_expiracao);
        else 
            $expiracao = date_format($now->modify('-3 hours'), 'Y-m-d H:i:s');
        
        if ($now > $expiracao) {
            $payload = $usuario->token_acesso;
            $payload = (array)json_decode($payload);

            $payload_mc = ['email' => $payload['username'], 'password' => $payload['password']];

            $client = new Client();
            $response = $client->request(
                'POST',
                'https://mcgames.bet.br/api/auth/login',
                [
                    'json' => $payload_mc,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            if ($response->getStatusCode() == 200) {
                $content = json_decode($response->getBody()->getContents());
                
                $usuario->token_plataforma = $content->access_token;
                // $usuario->token_expiracao = date_format($now->modify('+3 hours'), 'Y-m-d H:i:s');
                $usuario->token_expiracao = date_format($now->modify("+{$content->expires_in} seconds"), 'Y-m-d H:i:s');
                
                $usuario->saveInTransaction('double');
                return $content->access_token;
            }
        } else {
            return $usuario->token_plataforma;
        }
    }

    public function saldo(DoubleUsuario $usuario)
    {
        if ($usuario->modo_treinamento == 'Y') {
            // return DoubleConfiguracao::getConfiguracao('homologacao_saldo');
            return $usuario->banca_treinamento;
        } else {
            $token_plataforma = self::getToken($usuario);
            
            $client = new Client(['http_errors' => false]);
            $response = $client->request(
                'GET',
                'https://mcgames.bet.br/api/auth/me',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '. $token_plataforma
                    ]
                ]
            );

            if ($response->getStatusCode() == 200) {    
                $content = json_decode($response->getBody()->getContents());
                return round($content->balance / 100, 2);
            } elseif ($response->getStatusCode() == 401) {
                $usuario->token_expiracao = null;
                $usuario->saveInTransaction();
                return $this->saldo($usuario);
            }
        }
    }

    public function logar(string $usuario, string $senha)
    {
        $payload = ['email' => $usuario, 'password' => $senha];

        $client = new Client();
        $response = $client->request(
            'POST',
            'https://mcgames.bet.br/api/auth/login',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        if ($response->getStatusCode() == 200) {
            $content = json_decode($response->getBody()->getContents());
            return $content->access_token;
        } else {
            throw new Exception("Login inválido, por favor refaça a operação."); 
        }
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
            8  => 'black',
            9  => 'black',
            10 => 'black',
            11 => 'black',
            12 => 'black',
            13 => 'black',
            14 => 'black'
        ];
    }

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor)
    {
        return '';
    }

    public function possuiBancaTreinamento() {
        return false;
    }
    
    public function resetarBancaTreinamento(DoubleUsuario $usuario){}
}
