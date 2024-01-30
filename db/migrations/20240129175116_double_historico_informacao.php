<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleHistoricoInformacao extends AbstractMigration
{
   
    public function change(): void
    {
        $this->output->writeln('<info>Tabela</info> double_historico');

        $this->table('double_historico')
            ->addColumn('informacao', 'string')
            ->save();
    }
}
