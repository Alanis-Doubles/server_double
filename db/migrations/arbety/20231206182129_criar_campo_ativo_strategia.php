<?php
declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CriarCampoAtivoStrategia extends AbstractMigration
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
            ->changeColumn('regra', 'string', ['null' => true])
            ->changeColumn('gales', 'integer', ['null' => true])
            ->addColumn('ativo', 'enum', ['values' => ['Y','N'], 'default' => 'Y', 'null' => false])
            ->addColumn('tipo', 'enum', ['values' => ['COR', 'SOMA', 'NUMERO'], 'default' => 'COR', 'null' => false])
            ->removeIndexByName('nome')
            ->save();
    }
}
