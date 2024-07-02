<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesJunho202401 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_canal');
        $this->table('double_canal')
            ->addColumn('telegram_token', 'string')
            ->addColumn('processando_jogada', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario');
        $this->table('double_usuario')
            ->addColumn('modo_treinamento', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
            ->save();
    }
}
