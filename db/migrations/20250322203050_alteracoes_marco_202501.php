<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;


final class AlteracoesMarco202501 extends AbstractMigration
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

        $this->output->writeln('<info>Tabela</info> double_sinal');
        $this->table('double_sinal')
            ->addColumn('ticker_description', 'string')
            ->addColumn('ticker_classifier', 'string')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_historico');
        $this->table('double_historico')
            ->addColumn('ticker_description', 'string')
            ->addColumn('ticker_classifier', 'string')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario_historico');
        $this->table('double_usuario_historico')
            ->addColumn('ticker_description', 'string')
            ->addColumn('ticker_classifier', 'string')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_sessao_historico');
        $this->table('double_sessao_historico')
            ->addColumn('ticker_description', 'string')
            ->addColumn('ticker_classifier', 'string')
            ->save();
    }
}
