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
                DoubleConfiguracao::setConfiguracao('server_root', $server_root);
            }

        $param = http_build_query(['data' => $data]);
        
        $command = 'php ' . $server_root . '/cmd.php "class=' . $class . '&method=' . $method . '&' . $param . '"';
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $command, "r"));
        } else {
            exec($command . " > /dev/null &");
        }
    }
}