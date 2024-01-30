<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ZeraBaseJoaoWeplay extends AbstractSeed
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
        $this->execute("DELETE FROM double_estrategia WHERE canal_id in (SELECT id FROM double_canal WHERE nome = '[TESTE] Weplay')");
        $this->execute("DELETE FROM double_historico WHERE plataforma_id in (SELECT id FROM double_plataforma WHERE nome = 'Weplay')");
        $this->execute("DELETE FROM double_sinal WHERE plataforma_id in (SELECT id FROM double_plataforma WHERE nome = 'Weplay')");
        $this->execute("DELETE FROM double_usuario_historico WHERE usuario_id in (SELECT id FROM double_usuario WHERE plataforma_id in (SELECT id FROM double_plataforma WHERE nome = 'Weplay'))");
        $this->execute("DELETE FROM double_usuario WHERE plataforma_id in (SELECT id FROM double_plataforma WHERE nome = 'Weplay')");
        $this->execute("DELETE FROM double_canal WHERE nome = '[TESTE] Weplay'");
        $this->execute("DELETE FROM double_plataforma WHERE nome = 'Weplay'");
        $this->execute("COMMIT");
        $this->query('SET foreign_key_checks = 1');

        $this->inserir_dados(
            'double_plataforma',
            [
                [
                    'nome' => 'Weplay',
                    'tipo_sinais' => 'GERA',
                    'usuarios_canal' => 'N',
                    'valor_minimo' => 1,
                    'telegram_token' => '6888677460:AAH5uORCXMSnooTkI-H5vvd0h8_r3WSBAaI',
                    'url_double' => 'https://www.Weplay.games/games/double/',
                    'url_cadastro' => 'https://fwd.cx/vCUusCqAGJpo',
                    'url_tutorial' => 'https://t.me/tutorialdbreals_bot',
                    'url_suporte' => 'https://t.me/joaogrobman',
                ]
            ]
        );
        $plataforma = $this->fetchRow("SELECT id FROM double_plataforma WHERE nome = 'Weplay'");

        $this->inserir_dados(
            'double_canal',
            [
                [
                    'plataforma_id' => $plataforma['id'],
                    'nome' => '[TESTE] Weplay',
                    'channel_id' => -1002093089587,
                    'protecoes' => 4,
                ]
            ]
        );
        $canal = $this->fetchRow("SELECT id FROM double_canal WHERE nome = '[TESTE] Weplay'");

        $this->inserir_dados(
            'double_estrategia',
            [
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'red', 'regra' => '8', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'red', 'regra' => '11', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'red', 'regra' => '12', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'red', 'regra' => '14', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'black', 'regra' => '1', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Número', 'resultado' => 'black', 'regra' => '7', 'tipo' => 'NUMERO'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - black', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - black - red - red', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - black - red - black - red - black', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - red - black - red - black - red', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'black', 'regra' => 'red - red - red', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'red', 'regra' => 'black - black - black', 'tipo' => 'COR'],
                ['canal_id' => $canal['id'], 'nome' => 'Soma', 'tipo' => 'SOMA'],
                ['canal_id' => $canal['id'], 'nome' => 'Cor', 'resultado' => 'white', 'regra' => 'white', 'tipo' => 'COR'],
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
