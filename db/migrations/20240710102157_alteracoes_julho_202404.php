<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesJulho202404 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_estrategia');
        $this->table('double_estrategia')
            ->addColumn('protecoes', 'integer')
            ->addColumn('protecao_branco', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->save();

        $this->output->writeln('<info>Tabela</info> double_historico');
        $this->table('double_historico')
            ->addColumn('gale', 'integer')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario_historico');
        $this->table('double_usuario_historico')
            ->addColumn('valor_entrada', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_branco', 'double', ['default' => 0, 'null' => false])
            ->addColumn('gale', 'integer')
            ->addColumn('robo_inicio', 'datetime')
            ->addColumn('configuracao', 'text')
            ->save();
    }
}
