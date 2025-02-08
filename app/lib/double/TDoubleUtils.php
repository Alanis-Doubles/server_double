<?php

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class TDoubleUtils
{
    public static function cmd_run($class, $method, $data)
    {
        $token = TUtils::openFakeConnection('permission', function() {
            $login = TSession::getValue('login');
            $user =  SystemUser::validate($login);
            return ApplicationAuthenticationRestService::getToken($user);
        });

        if (!property_exists($data, 'token'))
            $data->token = $token;

        $server_root = DoubleConfiguracao::getConfiguracao('server_root');
        if (!$server_root) 
            {
                $server_root = $_SERVER['DOCUMENT_ROOT'];
                DoubleConfiguracao::setConfiguracao('server_root', $server_root);
            }

        $param = http_build_query(['data' => $data]);
        
        $command = 'php ' . $server_root . '/cmd.php "class=' . $class . '&method=' . $method . '&' . $param . '"';
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $command, "r"));
        } else {
            $tentativa = 1;
            while ($tentativa <= 5)
            {
                try {
                    exec($command . " > /dev/null &");
                    break;
                } catch (\Throwable $e) {
                    $tentativa += 1;
                   //  DoubleErros::registrar(1, 'TDoubleUtils', 'cmd_run', "Tentativa: $tentativa", $e->getMessage());
                } catch (Exception $e){
                    $tentativa += 1;
                   //  DoubleErros::registrar(1, 'TDoubleUtils', 'cmd_run', "Tentativa: $tentativa", $e->getMessage());
                }
            }
        }
    }

    public static function enviar_flux($urlflux, $nome, $email, $telefone) 
    {   
        if ($urlflux)
        {
            $client = new Client(['http_errors' => false]);
            $headers = [
                'Accept' => 'application/json, text/plain, */*',
                'Content-Type' => 'application/json'
            ];
            $body = '{
                "nome": "'. $nome . '",
                "telefone": "'. $telefone . '",
                "email": "'. $email . '"
            }';
            $request = new Request('POST', $urlflux, $headers, $body);
            $response = $client->sendAsync($request)->wait();

            $json = $response->getBody()->getContents();
            $content = json_decode($json);
            if ($response->getStatusCode() == 200) {
                return $content->id;
            } else {
               //  DoubleErros::registrar(1, 'TDoubleUtils', 'enviar_flux', $json, $body);
            } 
        }
    }   

    public static function verificar_expiracao($token)
    {
        $timestamp = \time();
        $tks = \explode('.', $token);
        list($headb64, $bodyb64, $cryptob64) = $tks;

        $payloadRaw = JWT::urlsafeB64Decode($bodyb64);
        $dadosToken = JWT::jsonDecode($payloadRaw);

        return (isset($dadosToken->exp) && $timestamp >= $dadosToken->exp);
    }
}