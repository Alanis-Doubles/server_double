<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesMarco202502 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_usuario');
        $this->table('double_usuario')
            ->addColumn('classificacao', 'enum', ['values' => [ 'Todos', 'Ações', 'Commodities', 'Criptomoeda', 'Forex', 'Índice' ], 'null' => false, 'default' => 'Todos'])
            ->save();
    }
}
