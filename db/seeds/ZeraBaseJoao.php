<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ZeraBaseJoao extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $this->query('SET foreign_key_checks = 0');
        $this->query("SET time_zone = '-03:00'");
        $this->table('double_canal')->truncate();
        $this->table('double_configuracao')->truncate();
        $this->table('double_erros')->truncate();
        $this->table('double_estrategia')->truncate();
        $this->table('double_historico')->truncate();
        $this->table('double_pagamento_historico')->truncate();
        $this->table('double_plataforma')->truncate();
        $this->table('double_sinal')->truncate();
        $this->table('double_usuario')->truncate();
        $this->table('double_usuario_historico')->truncate();
        $this->query('SET foreign_key_checks = 1');

        $this->inserir_dados(
            'double_plataforma',
            [
                [
                    'nome' => 'Reals',
                    'tipo_sinais' => 'PROPAGA_OUTRO',
                    'usuarios_canal' => 'Y',
                    'valor_minimo' => 1,
                    'telegram_token' => '6936510743:AAFvQE4rNZg22ScH_X7EUJ_GmVCynEuaspo',
                    'url_double' => 'https://realsbet.com/casino/game/2377405?provider=CasinoGate',
                    'url_cadastro' => 'https://fwd.cx/vCUusCqAGJpo',
                    'url_tutorial' => 'https://t.me/tutorialdbreals_bot',
                    'url_suporte' => 'https://t.me/joaogrobman',
                ]
            ]
        );

        $this->inserir_dados(
            'double_configuracao',
            [
                ['nome' => 'webdriver_host', 'valor' => ''],
                ['nome' => 'telegram_host', 'valor' => "https://api.telegram.org/bot{token}/"],
                ['nome' => 'server_root', 'valor' => ""],
                ['nome' => 'manutencao', 'valor' => 'N'],
                ['nome' => 'manutencao_chat_ids', 'valor' => '1027086283,6215901409'],
                ['nome' => 'homologacao_saldo', 'valor' => '100'],
            ]
        );

        $this->inserir_dados(
            'double_canal',
            [
                [
                    'plataforma_id' => 1,
                    'nome' => '[TESTE] Double João',
                    'channel_id' => -1002093089587,
                    'protecoes' => 4,
                ]
            ]
        );

        $this->inserir_dados(
            'double_estrategia',
            [
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '8', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '11', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '12', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '14', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'black', 'regra' => '1', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Número', 'resultado' => 'black', 'regra' => '7', 'tipo' => 'NUMERO'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - black', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - red - red', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - black - red - black - red - black', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - red - black - red - black - red', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - red', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black - black', 'tipo' => 'COR'],
                ['canal_id' => 1, 'nome' => 'Soma', 'tipo' => 'SOMA'],
                ['canal_id' => 1, 'nome' => 'Cor', 'resultado' => 'white', 'regra' => 'white', 'tipo' => 'COR'],
            ]
        );

        
    }

    private function inserir_dados($tabela, $data)
    {
        $this->output->writeln("<info>Criando dados</info> {$tabela}");
        $this->table($tabela)
            ->insert($data)
            ->saveData();
    }
}
