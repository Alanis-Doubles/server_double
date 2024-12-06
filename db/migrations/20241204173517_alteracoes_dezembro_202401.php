<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesDezembro202401 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_sinal');
        $this->table('double_sinal')
            ->addColumn('fator', 'integer')
            ->addColumn('dice', 'integer')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_historico');
        $this->table('double_historico')
            ->addColumn('fator', 'integer')
            ->addColumn('dice', 'integer')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario_historico');
        $this->table('double_usuario_historico')
            ->addColumn('fator', 'integer')
            ->addColumn('dice', 'integer')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario');
        $this->table('double_usuario')
            ->addColumn('fator_multiplicador_branco', 'double', ['default' => 2.0])
            ->addColumn('valor_branco', 'double', ['default' => 0.0])
            ->save();
    }
}
