<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleEstrategiaOrdem extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);
        
        $this->output->writeln('<info>Tabela</info> double_estrategia');

        $this->table('double_estrategia')
            ->addColumn('ordem', 'integer')
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario');

        if ($this->isMigratingUp()) {
            $this->table('double_usuario')
                ->changeColumn('entrada_automatica', 'enum', ['values' => ['Y', 'N', 'A'], 'default' => 'N', 'null' => false])
                ->addColumn('entrada_automatica_total_loss', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('entrada_automatica_qtd_loss', 'integer', ['default' => 0, 'null' => false])
                ->save();
        } else {
            $this->table('double_usuario')
                ->addColumn('entrada_automatica_total_loss', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('entrada_automatica_qtd_loss', 'integer', ['default' => 0, 'null' => false])
                ->save();
        }
    }
}
