<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreateArbetyOdd extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> arbety_odd');
        
        $table = $this->table('arbety_odd')
            ->addColumn('color', 'string')
            ->addColumn('number', 'integer')
            ->addColumn('result', 'enum', ['values' => ['WIN', 'LOSS']])
            ->addColumn('gale', 'integer')
            ->addColumn('id_estrategia', 'integer')
            ->addColumn('id_usuario', 'integer')
            // ->addTimestamps('create_at', false)
            ->addColumn('create_at', 'datetime')
            ->addIndex(['create_at'])
            ->addForeignKey('id_estrategia', 'arbety_estrategia')
            ->addForeignKey('id_usuario', 'arbety_user')
            ->create();
    }
}
