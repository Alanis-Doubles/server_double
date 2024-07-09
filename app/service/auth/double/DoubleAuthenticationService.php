<?php
class DoubleAuthenticationService
{
	public static function authenticate($user, $password)
	{
        $usuario = SystemUser::validate($user);
        
        if ($usuario and SystemUser::authenticate($usuario->login, $password)) {
            return true;
        }

        throw new Exception(_t('User not found or wrong password'));
	}
}
