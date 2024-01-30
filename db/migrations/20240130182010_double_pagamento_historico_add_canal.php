<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoublePagamentoHistoricoAddCanal extends AbstractMigration
{
    public function change(): void
    {
        $this->output->writeln('<info>Tabela</info> double_historico');

        $this->table('double_pagamento_historico')
            ->addColumn('plataforma_id', 'integer')
            ->addColumn('canal_id', 'integer')
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addForeignKey('canal_id', 'double_canal')
            ->save();
    }
}
