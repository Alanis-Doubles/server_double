<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesAbril02 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_usuario_historico');
        $this->table('double_usuario_historico')
            ->addColumn('modo_treinamento', 'enum', ['values' => ['Y', 'N']])
            ->save();
    }
}
