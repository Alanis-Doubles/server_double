<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoublePlataformaPropagaValidaSinal extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_plataforma');

        $this->table('double_plataforma')
            ->changeColumn('tipo_sinais', 'enum', ['values' => ['GERA', 'NAO_GERA', 'PROPAGA_OUTRO', 'PROPAGA_VALIDA_SINAL'], 'default' => 'GERA', 'null' => false])
            ->save();
    }
}
