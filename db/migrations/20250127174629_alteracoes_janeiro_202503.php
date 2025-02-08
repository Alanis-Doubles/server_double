<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesJaneiro202503 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_sessao_historico');
        $this->table('double_sessao_historico')
            ->addColumn('sessao', 'datetime', ['null' => false])
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('canal_id', 'integer')
            ->addColumn('tipo', 'enum', ['values' => ['ENTRADA', 'WIN', 'LOSS', 'GALE', 'POSSIVEL']])
            ->addColumn('estrategia_id', 'integer')
            ->addColumn('cor', 'string')
            ->addColumn('informacao', 'string')
            ->addColumn('fator', 'double')
            ->addColumn('dice', 'integer')
            ->addColumn('ticker', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addForeignKey('estrategia_id', 'double_estrategia')
            ->addForeignKey('canal_id', 'double_canal')
            ->addIndex(['plataforma_id', 'created_at'])
            ->create();
    }
}
