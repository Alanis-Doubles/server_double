<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesMarco202503 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_usuario');
        $this->table('double_usuario')
            ->changeColumn('classificacao', 'enum', ['values' => [ 'Todos', 'Ações', 'Commodities', 'Criptomoeda', 'Forex', 'Índice', 'OTC' ], 'null' => false, 'default' => 'Todos'])
            ->save();
    }
}
