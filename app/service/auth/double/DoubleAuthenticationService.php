<?php
class DoubleAuthenticationService
{
	public static function authenticate($user, $password)
	{
        $usuario = SystemUser::validate($user);

        if (!$usuario->password)
            throw new Exception('Não é possível realizar o login, gere uma nova senha no Robô do Telegram');

        // DoubleErros::registrar(1, 'DoubleAuthenticationService', 'authenticate', $usuario->password, "{$user} - {$usuario->login} - {$password}");
        if (!$usuario->login) {
            $usuario->login = $user;
            $usuario->save();
        }
        // DoubleErros::registrar(1, 'DoubleAuthenticationService', 'authenticate', $usuario->password, "{$user} - {$usuario->login} - {$password}");

        if ($usuario and SystemUser::authenticate($usuario->login, $password)) {
            return true;
        }

        throw new Exception(_t('User not found or wrong password'));
	}
}
