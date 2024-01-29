<?php

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
                // if (substr(php_uname(), 0, 7) == "Windows")
                //     $server_root .=  '/server_double';
                DoubleConfiguracao::setConfiguracao('server_root', $server_root);
            }

        $param = base64_encode(serialize($data));
        
        $command = 'php ' . $server_root . '/cmd.php "class=' . $class . '&method=' . $method . '&data=' . $param . '"';
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $command, "r"));
        } else {
            exec($command . " > /dev/null &");
        }
    }
}