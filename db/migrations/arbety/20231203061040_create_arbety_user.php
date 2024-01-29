<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreateArbetyUser extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> arbety_user');
        
        $table = $this->table('arbety_user')
            ->addColumn('chat_id', 'biginteger')
            ->addColumn('status', 'enum', ['values' => ['NOVO','DEMO','AGUARDANDO_PAGAMENTO','ATIVO','INATIVO','EXPIRADO'], 'default' => 'NOVO', 'null' => false])
            ->addColumn('value', 'double', ['default' => 0, 'null' => false])
            ->addColumn('gales', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('stop_win', 'double', ['default' => 0, 'null' => false])
            ->addColumn('stop_loss', 'double', ['default' => 0, 'null' => false])
            ->addColumn('last_balance', 'double', ['default' => 0, 'null' => false])
            ->addColumn('is_betting', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('last_bet', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('expiration_date', 'date')
            ->addColumn('create_at', 'datetime')
            ->addColumn('start_after_loss', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('processing_bet', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('access_token', 'string')
            ->addColumn('token', 'string')
            ->addColumn('expiration_token', 'datetime')
            ->addColumn('ciclo', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('demo_jogadas', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('email', 'string', ['null' => true])
            ->addIndex(['chat_id'], ['unique' => true])
            ->addIndex(['chat_id'])
            ->addIndex(['chat_id', 'is_betting'])
            ->addIndex(['chat_id', 'status'])
            ->create();
    }
}
