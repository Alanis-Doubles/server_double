<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesJaneiro202502 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_recuperacao_mensagem');
        $this->table('double_recuperacao_mensagem')
            ->changeColumn('mensagem_direta', 'enum', ['values' => ['Y','N','P'], 'default' => 'N', 'null' => false])
            ->addColumn('periodicidade', 'string')
            ->addColumn('hora', 'time')
            ->save();

    }
}
