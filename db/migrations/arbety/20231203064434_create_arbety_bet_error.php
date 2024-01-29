<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreateArbetyBetError extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> arbety_bet_error');

        $table = $this->table('arbety_bet_error')
            ->addColumn('id_bet', 'integer', ['null' => false])
            ->addColumn('id_user', 'integer', ['null' => false])
            ->addColumn('value', 'double')
            ->addColumn('content', 'string', ['null' => false])
            // ->addTimestamps('create_at', false)
            ->addColumn('create_at', 'datetime')
            ->addForeignKey('id_user', 'arbety_user')
            ->addIndex(['id_bet', 'id_user'])
            ->create();
    }
}
