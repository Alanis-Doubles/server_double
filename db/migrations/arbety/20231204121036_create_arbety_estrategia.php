<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreateArbetyEstrategia extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> arbety_estrategia');

        $table = $this->table('arbety_estrategia')
            ->addColumn('nome', 'string', ['null' => false])
            ->addColumn('regra', 'string', ['null' => false])
            ->addColumn('gales', 'integer', ['null' => false])
            ->addColumn('id_usuario', 'integer')
            ->addColumn('create_at', 'datetime')
            // ->addIndex(['create_at'])
            ->addIndex(['nome', 'id_usuario'], ['unique' => true])
            ->addForeignKey('id_usuario', 'arbety_user')
            // ->addTimestamps('create_at', false)
            ->create();
    }
}
