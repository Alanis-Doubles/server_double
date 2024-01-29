<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ZeraBaseGabriel extends AbstractSeed
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
                    'nome' => 'Blaze',
                    'tipo_sinais'  => 'NAO_GERA',
                    'valor_minimo' => 0.10,
                    'telegram_token' => '6429926045:AAE6ouqVhlR7EX-H6R8Ei5aUxKQfVphEepo',
                    'url_double' => 'https://blaze-6.com/pt/games/double',
                ]
            ]
        );

        $this->inserir_dados(
            'double_configuracao',
            [
                ['nome' => 'webdriver_host', 'valor' => ''],
                ['nome' => 'telegram_host', 'valor' => "https://api.telegram.org/bot{token}/"],
                ['nome' => 'server_root', 'valor' => ""],
                ['nome' => 'manutencao', 'valor' => 'Y'],
                ['nome' => 'manutencao_chat_ids', 'valor' => '1027086283,6104272947'],
                ['nome' => 'homologacao_saldo', 'valor' => '100'],
            ]
        );

        $this->inserir_dados(
            'double_canal',
            [
                [
                    'plataforma_id' => 1,
                    'nome' => 'DOUBLE DO GB BLAZE',
                    'channel_id' => -1002129523713,
                    'protecoes' => 4,
                ]
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
