<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleCanalEsperaParaJogar extends AbstractMigration
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
        $this->output->writeln('<info>Tabela</info> double_canal');

        $this->table('double_canal')
            ->addColumn('espera_entrada', 'enum', ['values' => ['Y', 'N'], 'default' => 'Y', 'null' => false])
            ->save();
    }
}
