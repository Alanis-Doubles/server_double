<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleTraducao extends AbstractMigration
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
        
        $this->output->writeln('<info>Tabela</info> double_traducao');

        $this->table('double_traducao')
            ->addColumn('chave', 'string')
            ->addColumn('valor', 'text')
            ->create();

        $this->table('double_traducao_plataforma')
            ->addColumn('plataforma_id', 'integer')
            ->addColumn('chave', 'string')
            ->addColumn('valor', 'text')
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->create();
    }
}
