<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class ApplicationAuthenticationRestService
{
    public static function authenticate($param)
    {
        $user = ApplicationAuthenticationService::authenticate($param['login'], $param['password']);
        return self::getToken($user);
    }

    public static function getToken($user)
    {
        $ini = AdiantiApplicationConfig::get();
        $key = APPLICATION_NAME . $ini['general']['seed'];
        
        if (empty($ini['general']['seed']))
        {
            throw new Exception('Application seed not defined');
        }
        
        $token = array(
            "user" => $user->login,
            "userid" => $user->id,
            "username" => $user->name,
            "usermail" => $user->email,
            "expires" => strtotime("+ 3 hours")
        );

        if (!empty($user->unit))
        {
            $token['userunitid'] =  TSession::getValue('userunitid');
            $token['userunitname'] =  TSession::getValue('userunitname');
            $token['unit_database'] =  TSession::getValue('unit_database');//$user->unit->connection_name;
        }
        
        return JWT::encode($token, $key, 'HS256');
    }
}
