<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreateArbetyBet extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> arbety_bet');
        
        $table = $this->table('arbety_bet')
            ->addColumn('id_bet', 'integer')
            ->addColumn('id_user', 'integer')
            ->addColumn('value', 'double')
            // ->addTimestamps('create_at', false)
            ->addColumn('create_at', 'datetime')
            ->addForeignKey('id_user', 'arbety_user')
            ->addIndex(['id_bet', 'id_user'])
            ->create();
    }
}
