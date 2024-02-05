<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoublePagamentoHistoricoAlterPlataformaPagamento extends AbstractMigration
{
    public function change(): void
    {
        $this->output->writeln('<info>Tabela</info> double_historico');

        $this->table('double_pagamento_historico')
            ->changeColumn('plataforma_pagamento', 'enum', ['values' => ['PIX', 'LASTLINK', 'KIRVANO'], 'default' => 'PIX', 'null' => false])
            ->changeColumn('tipo_evento', 'enum', ['values' => ['PAGAMENTO','CANCELAMENTO','EXPIRACAO','RENOVACAO'], 'default' => 'PAGAMENTO', 'null' => false])
            ->save();
    }
}
