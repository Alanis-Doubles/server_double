<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesAbril03 extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_recuperacao_mensagem');
        $this->table('double_recuperacao_mensagem')
            // Adicionado 'A' para 'Agendamento'
            ->changeColumn('mensagem_direta', 'enum', ['values' => ['Y','N','A'], 'default' => 'N', 'null' => false])
            ->addColumn('ativo', 'enum', ['values' => ['Y','N'], 'default' => 'Y', 'null' => false])
            ->addColumn('dia_mes', 'string')
            ->addColumn('dia_semana', 'string')
            ->changeColumn('hora', 'string')
            ->addColumn('minuto', 'string')
            ->removeColumn('periodicidade')
            ->addColumn('tipo_agendamento', 'enum', ['values' => ['M','W','D','F']])
            ->changeColumn('status', 'string')
            ->addColumn('titulo', 'string')
            ->save();
    }
}
