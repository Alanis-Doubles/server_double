<?php

use Phinx\Seed\AbstractSeed;

class ZerarBase extends AbstractSeed
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
        // $this->table('arbety_params')->truncate();
        // $this->table('arbety_estrategia')->truncate();
        // $this->query('SET foreign_key_checks = 1');

        // $data = [
        //     ['nome' => 'buscar_sinais_status', 'valor' => 'PARADO'],
        //     ['nome' => 'buscar_sinais_inicio', 'valor' => ''],
        //     ['nome' => 'webdriver_host', 'valor' => 'http://24.152.38.215:4444'],
        //     ['nome' => 'telegram_token', 'valor' => '6936510743:AAFvQE4rNZg22ScH_X7EUJ_GmVCynEuaspo'],
        //     ['nome' => 'telegram_chat_id', 'valor' => '-1002093089587'],
        //     ['nome' => 'telegram_host', 'valor' => "https://api.telegram.org/bot{token}/"],
        //     ['nome' => 'server_root', 'valor' => ""],
        //     ['nome' => 'arbety_gales', 'valor' => 4],
        //     ['nome' => 'arbety_exibe_projecao', 'valor' => 'N'],
        //     ['nome' => 'arbety_ambiente', 'valor' => 'TESTE'],
        // ];

        // $this->output->writeln('<info>Criando dados</info> arbety_params');
        // $this->table('arbety_params')
        //     ->insert($data)
        //     ->saveData();

        // $data = [
        //     ['nome' => 'Número', 'resultado' => 'red'  , 'regra' => '8', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Número', 'resultado' => 'red'  , 'regra' => '11', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Número', 'resultado' => 'red'  , 'regra' => '12', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Número', 'resultado' => 'red'  , 'regra' => '14', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Número', 'resultado' => 'black', 'regra' => '1', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Número', 'resultado' => 'black', 'regra' => '7', 'tipo' => 'NUMERO'],
        //     ['nome' => 'Cor'   , 'resultado' => 'black', 'regra' => 'red - red - black - black', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'black', 'regra' => 'red - red - black - red - red', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'black', 'regra' => 'red - black - red - black - red - black', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'red'  , 'regra' => 'black - red - black - red - black - red', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'red'  , 'regra' => 'black - black', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'black', 'regra' => 'red - red', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'black', 'regra' => 'red - red - red', 'tipo' => 'COR'],
        //     ['nome' => 'Cor'   , 'resultado' => 'red'  , 'regra' => 'black - black - black', 'tipo' => 'COR'],
        //     ['nome' => 'Soma'  , 'tipo' => 'SOMA'],
        //     ['nome' => 'Cor'   , 'resultado' => 'white', 'regra' => 'white', 'tipo' => 'COR'],
        // ];

        // $this->output->writeln('<info>Criando dados</info> arbety_estrategia');
        // $this->table('arbety_estrategia')
        //     ->insert($data)
        //     ->saveData();

        $this->query('SET foreign_key_checks = 0');
        $this->table('double_plataforma')->truncate();
        $this->table('double_configuracao')->truncate();
        $this->table('double_canal')->truncate();
        $this->table('double_estrategia')->truncate();
        $this->table('double_sinal')->truncate();
        $this->table('double_historico')->truncate();
        $this->query('SET foreign_key_checks = 1');

        $this->inserir_dados(
            'double_plataforma',
            [
                [
                    'nome' => 'brazabet',
                    'gerar_sinais' => 'Y',
                    'protecoes' => 4,
                    'telegram_token' => '6936510743:AAFvQE4rNZg22ScH_X7EUJ_GmVCynEuaspo',
                    'url_double' => 'https://brazabet.net/games/double',
                    'url_suporte' => 'https://t.me/joaogrobman'
                ]
            ]
        );

        $this->inserir_dados(
            'double_configuracao',
            [
                ['nome' => 'webdriver_host', 'valor' => 'http://24.152.38.215:4454'],
                ['nome' => 'telegram_host', 'valor' => "https://api.telegram.org/bot{token}/"],
                ['nome' => 'server_root', 'valor' => ""]
            ]
        );

        $this->inserir_dados(
            'double_canal',
            [
                [
                    'nome' => '[VIP] Double pro | 98% Green',
                    'channel_id' => -1002093089587
                ]
            ]
        );


        $this->inserir_dados(
            'double_estrategia',
            [
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '8', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '11', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '12', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'red', 'regra' => '14', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'black', 'regra' => '1', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Número', 'resultado' => 'black', 'regra' => '7', 'tipo' => 'NUMERO'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - black', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - red - red', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - black - red - black - red - black', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - red - black - red - black - red', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - red', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black - black', 'tipo' => 'COR'],
                ['plataforma_id' => 1, 'nome' => 'Soma', 'tipo' => 'SOMA'],
                ['plataforma_id' => 1, 'nome' => 'Cor', 'resultado' => 'white', 'regra' => 'white', 'tipo' => 'COR'],
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
