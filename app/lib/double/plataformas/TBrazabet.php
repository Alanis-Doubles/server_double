<?php

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class TBrazabet extends TWebDriverPlataforma
{
    public function __construct()
    {
        parent::__construct();
        
        $this->webDriver->get('https://brazabet.net/');
        $this->webDriver->findElement(WebDriverBy::linkText("Entrar"))->click();
        $this->webDriver->findElement(WebDriverBy::id('email'))->sendKeys('edson.alanis@gmail.com');        
        $this->webDriver->findElement(WebDriverBy::id('password'))->sendKeys('Da2403vi@');   
        sleep(1);     
        $this->webDriver->findElement(WebDriverBy::cssSelector('.overflow-hidden'))->click();
        $this->webDriver->switchTo()->frame(0);
        $this->webDriver->findElement(WebDriverBy::cssSelector('.recaptcha-checkbox-border'))->click();     
        $this->webDriver->switchTo()->defaultContent();
        $this->webDriver->findElement(WebDriverBy::id('remember_me'))->click();        
        $this->webDriver->findElement(WebDriverBy::cssSelector('.inline-flex'))->click();    
        $this->webDriver->get('https://brazabet.net/games/double');

        
    }

    public static function nome()
    {
        return 'brazabet';
    }
}
