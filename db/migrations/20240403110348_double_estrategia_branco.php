<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleEstrategiaBranco extends AbstractMigration
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
            ->addColumn('incrementa_valor_entrada', 'enum', ['values' => ['NUNCA', 'A_CADA_GALE'], 'default' => 'A_CADA_GALE', 'null' => false])
            ->addColumn('resetar_valor_entrada', 'enum', ['values' => ['NUNCA', 'SEMPRE', 'A_CADA_HORA'], 'default' => 'NUNCA', 'null' => false])
            ->changeColumn('tipo', 'enum', ['values' => ['COR', 'SOMA', 'NUMERO', 'BRANCO'], 'default' => 'COR', 'null' => false])
            ->save();
    }
}
