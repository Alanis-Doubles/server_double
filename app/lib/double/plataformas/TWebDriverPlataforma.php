<?php

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class TWebDriverPlataforma extends TDoublePlataforma
{
    protected RemoteWebDriver $webDriver;

    public function __construct()
    {
        $webdriver_host = DoubleConfiguracao::getConfiguracao('webdriver_host');
        $chrome_options = array(
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4324.104 Safari/537.36',
            '--start-maximized',
            'window-size=800,600',

        );
        $ops = new ChromeOptions();
        $ops->addArguments($chrome_options);
        $ops->setExperimentalOption("excludeSwitches", array("enable-automation"));
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $ops);
        $this->webDriver = RemoteWebDriver::create($webdriver_host, $capabilities);
    }

    public function __destruct()
    {
        $this->webDriver->close();
        $this->webDriver->quit();
    }

    public function possuiBancaTreinamento() {
        return false;
    }
    public function resetarBancaTreinamento(DoubleUsuario $usuario){}

    public static function nome(){}
    public function aguardarSinal($ultimo_sinal){}
    public function ultimoSinal(){}
    public function getToken(DoubleUsuario $usuario){}
    public function saldo(DoubleUsuario $usuario){}
    public function logar(string $usuario, string $senha){}
    public function cores(){}
    public function jogar(DoubleUsuario $usuario, string $cor, float $valor){}
    public function sinalCorrente(){}
}