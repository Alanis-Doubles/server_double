<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesAbril05 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> profit_manual_signals');
        $this->table('profit_manual_signals')
            ->removeColumn('time')
            ->addColumn('data', 'timestamp')
            ->save();
    }
}
