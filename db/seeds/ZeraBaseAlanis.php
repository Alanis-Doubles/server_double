<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ZeraBaseAlanis extends AbstractSeed
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
        // $this->query('SET foreign_key_checks = 0');
        // $this->query("SET time_zone = '-03:00'");
        // $this->table('double_canal')->truncate();
        // $this->table('double_configuracao')->truncate();
        // $this->table('double_erros')->truncate();
        // $this->table('double_estrategia')->truncate();
        // $this->table('double_historico')->truncate();
        // $this->table('double_pagamento_historico')->truncate();
        // $this->table('double_plataforma')->truncate();
        // $this->table('double_sinal')->truncate();
        // $this->table('double_usuario')->truncate();
        // $this->table('double_usuario_historico')->truncate();
        // $this->query('SET foreign_key_checks = 1');

        // $this->inserir_dados(
        //     'double_plataforma',
        //     [
        //         [
        //             'nome' => 'Blaze',
        //             'tipo_sinais' => 'GERA',
        //             'usuarios_canal' => 'Y',
        //             'valor_minimo' => 1,
        //             'telegram_token' => '7033752932:AAE19xEsP0gB_M7Um22vHNNT1IohmQ09pQg',
        //             'url_double' => 'https://blaze.com/pt/games/double',
        //             'url_cadastro' => 'blaze-7.com/r/GbnMEk',
        //             'url_tutorial' => '',
        //             'url_suporte' => 'https://t.me/edson_alanis',
        //         ]
        //     ]
        // );

        // $this->inserir_dados(
        //     'double_configuracao',
        //     [
        //         ['nome' => 'webdriver_host', 'valor' => ''],
        //         ['nome' => 'telegram_host', 'valor' => "https://api.telegram.org/bot{token}/"],
        //         ['nome' => 'server_root', 'valor' => ""],
        //         ['nome' => 'manutencao', 'valor' => 'N'],
        //         ['nome' => 'manutencao_chat_ids', 'valor' => '1027086283'],
        //         ['nome' => 'homologacao_saldo', 'valor' => '200'],
        //         ['nome' => 'translate_class', 'valor' => 'TDouble_alanis'],
        //         ['nome' => 'base_url', 'valor' => 'https://alanis.doublerobo.com.br/']
        //     ]
        // );

        // $this->inserir_dados(
        //     'double_canal',
        //     [
        //         [
        //             'plataforma_id' => 1,
        //             'nome' => 'Alanis - Balze',
        //             'channel_id' => -1002009434291,
        //             'protecoes' => 2,
        //         ]
        //     ]
        // );

        $this->inserir_dados(
            'double_estrategia',
            [
                ['ordem' => 1,  'canal_id' => 16, 'nome' => '3 puxa vermelho', 'resultado' => 'red', 'regra' => '3', 'tipo' => 'NUMERO'],
                ['ordem' => 2,  'canal_id' => 16, 'nome' => '8 puxa vermelho', 'resultado' => 'red', 'regra' => '8', 'tipo' => 'NUMERO'],
                ['ordem' => 3,  'canal_id' => 16, 'nome' => '11 puxa vermelho', 'resultado' => 'red', 'regra' => '11', 'tipo' => 'NUMERO'],
                ['ordem' => 4,  'canal_id' => 16, 'nome' => '12 puxa vermelho', 'resultado' => 'red', 'regra' => '12', 'tipo' => 'NUMERO'],
                ['ordem' => 5,  'canal_id' => 16, 'nome' => '14 puxa vermelho', 'resultado' => 'red', 'regra' => '14', 'tipo' => 'NUMERO'],
                ['ordem' => 6,  'canal_id' => 16, 'nome' => '1 puxa preto', 'resultado' => 'black', 'regra' => '1', 'tipo' => 'NUMERO'],
                ['ordem' => 7,  'canal_id' => 16, 'nome' => '7 puxa preto', 'resultado' => 'black', 'regra' => '7', 'tipo' => 'NUMERO'],
                ['ordem' => 8,  'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - black', 'tipo' => 'COR'],
                ['ordem' => 9,  'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - red - red', 'tipo' => 'COR'],
                ['ordem' => 10, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - black - red - black - red - black', 'tipo' => 'COR'],
                ['ordem' => 11, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - red - black - red - black - red', 'tipo' => 'COR'],
                ['ordem' => 12, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'red - black - black - red', 'tipo' => 'COR'],
                ['ordem' => 13, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'black - red - red - black', 'tipo' => 'COR'],
                ['ordem' => 14, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'red - red - red', 'tipo' => 'COR'],
                ['ordem' => 15, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'black - black - black', 'tipo' => 'COR'],
                ['ordem' => 16, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black', 'tipo' => 'COR'],
                ['ordem' => 17, 'canal_id' => 16, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red', 'tipo' => 'COR'],
                ['ordem' => 18, 'canal_id' => 16, 'nome' => 'Soma', 'tipo' => 'SOMA'],
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
