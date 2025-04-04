<?php
class DoubleAuthenticationService
{
	public static function authenticate($user, $password)
	{
        $usuario = SystemUser::validate($user);

        if (!$usuario->password)
            throw new Exception('Não é possível realizar o login, gere uma nova senha no Robô do Telegram');

        ////  DoubleErros::registrar(1, 'DoubleAuthenticationService', 'authenticate', $usuario->password, "{$user} - {$usuario->login} - {$password}");
        if (!$usuario->login) {
            $usuario->login = $user;
            $usuario->save();
        }

        $double_usuario = TUtils::openFakeConnection('double', function()  use ($usuario){
            return DoubleUsuario::where('chat_id', '=', $usuario->custom_code)->first();
        });

        if ($double_usuario and !in_array($double_usuario->status, ['ATIVO', 'DEMO'])) 
        // if ($double_usuario and $double_usuario->status !== 'ATIVO')
            throw new Exception('O acesso é permitido apenas para usuários ativos.');

        ////  DoubleErros::registrar(1, 'DoubleAuthenticationService', 'authenticate', $usuario->password, "{$user} - {$usuario->login} - {$password}");

        if ($usuario and SystemUser::authenticate($usuario->login, $password)) {
            return true;
        }

        throw new Exception(_t('User not found or wrong password'));
	}
}
