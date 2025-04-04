<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesAbril01 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> profit_manual_signals');
        $this->table('profit_manual_signals')
            ->addColumn('ticker', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('ticker_description', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('ticker_classifier', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('decision', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('time', 'string', ['limit' => 50, 'null' => true])
            ->addTimestamps('created_at', 'updated_at')
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->create();
    }
}
