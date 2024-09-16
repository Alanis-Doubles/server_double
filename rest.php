<?php

use Adianti\Core\AdiantiCoreApplication;
header('Content-Type: application/json; charset=utf-8');

// initialization script
require_once 'init.php';

class AdiantiRestServer
{
    public static function run($request)
    {
        $ini      = AdiantiApplicationConfig::get();
        $input    = json_decode(file_get_contents("php://input"), true);
        $request  = array_merge($request, (array) $input);
        $class    = isset($request['class']) ? $request['class']   : '';
        $method   = isset($request['method']) ? $request['method'] : '';
        $headers  = AdiantiCoreApplication::getHeaders();
        $auth        =  isset($request['auth']) ? $request['auth']   : NULL;
        $response = NULL;   
        $call_method = $_SERVER['REQUEST_METHOD'];
        
        $headers['Authorization'] = $headers['Authorization'] ?? ($headers['authorization'] ?? null); // for clientes that send in lowercase (Ex. futter)
        
        try
        {
            if (isset($request['call_method']))
                if ($request['call_method'] != $call_method) {
                    throw new Exception( "Mátodo {$call_method} não suportado." );
                }
    
            if ($auth != 'None'){
                if (empty($headers['Authorization']))
                {
                    throw new Exception( _t('Authorization error') );
                }
                else
                {
                    if (substr($headers['Authorization'], 0, 5) == 'Basic')
                    {
                        if (empty($ini['general']['rest_key']))
                        {
                            throw new Exception( _t('REST key not defined') );
                        }
                        
                        if ($ini['general']['rest_key'] !== substr($headers['Authorization'], 6))
                        {
                            http_response_code(401);
                            return json_encode( array('status' => 'error', 'data' => _t('Authorization error')));
                        }
                    }
                    else if (substr($headers['Authorization'], 0, 6) == 'Bearer')
                    {
                        ApplicationAuthenticationService::fromToken( substr($headers['Authorization'], 7) );
                    }
                    else
                    {
                        http_response_code(403);
                        throw new Exception( _t('Authorization error') );
                    }
                }
            }
                
            unset($request['auth']);
            $response = AdiantiCoreApplication::execute($class, $method, $request, 'rest');
            if (is_array($response))
            {
                array_walk_recursive($response, ['AdiantiStringConversion', 'assureUnicode']);
            }
            return json_encode( array('status' => 'success', 'data' => $response));
        }
        catch (Exception $e)
        {
            if(200 === http_response_code())
            {
                http_response_code(500);
            }

            return json_encode( array('status' => 'error', 'data' => $e->getMessage()));
        }
        catch (Error $e)
        {
            if(200 === http_response_code())
            {
                http_response_code(500);
            }

            return json_encode( array('status' => 'error', 'data' => $e->getMessage()));
        }
    }
}

print AdiantiRestServer::run($_REQUEST);
