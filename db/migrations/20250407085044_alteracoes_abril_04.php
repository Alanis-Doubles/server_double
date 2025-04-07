<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesAbril04 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->
        writeln('<info>Tabela</info> double_canal');
        $this->table('double_canal')
            ->addColumn('horario_sessao', 'string')
            ->save();
    }
}
