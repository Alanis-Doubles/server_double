<?php

class TDoubleRedis {

    public function run($param) {}

    public function serverName() {
        return DoubleConfiguracao::getConfiguracao('server_name');
    }

    public function hostUsuario() {
        return DoubleConfiguracao::getConfiguracao('host_usuario');
    }
}