<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;

interface IDoublePlataforma 
{
    public static function nome();

    public function aguardarSinal($ultimo_sinal);

    public function ultimoSinal();

    public function getToken(DoubleUsuario $usuario);

    public function saldo(DoubleUsuario $usuario);

    public function logar(string $usuario, string $senha);

    public function cores();

    public function jogar(DoubleUsuario $usuario, string $cor, float $valor);

    public function sinalCorrente();
}
