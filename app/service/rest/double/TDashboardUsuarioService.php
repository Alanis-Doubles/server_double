<?php

use Adianti\Registry\TSession;

class TDashboardUsuarioService
{
    public static function getHistorico($usuario_id, $ultimo_id)
    {
        $sql = "SELECT uh.id,
                       uh.created_at  data, 
                       FORMAT( uh.valor, 2 ) valor,
                       FORMAT((SELECT SUM(temp.valor) FROM double_usuario_historico temp
                                WHERE temp.usuario_id =  uh.usuario_id 
                                  AND temp.created_at >= u.robo_inicio AND temp.created_at <= uh.created_at), 2 ) acumulado
                  FROM double_usuario_historico uh
                  JOIN double_usuario u ON u.id = uh.usuario_id
                 WHERE uh.usuario_id = {$usuario_id}
                   AND uh.created_at >= u.robo_inicio 
                   AND uh.id > {$ultimo_id}
                 ORDER BY uh.id desc
                 LIMIT 20";

        $results = TUtils::openFakeConnection('double', function () use($sql){
            $conn = TTransaction::get();
            $list = TDatabase::getData(
                $conn, 
                $sql
            );

            return $list;
        });

        $list = [];
        foreach ($results as &$result) {
           $list[] = ['id' => $result['id'], 'data' => date('c', strtotime($result['data'])), 'acumulado' => $result['acumulado'], 'valor' => $result['valor']];
        }

        // Retorna os dados em formato JSON
        return json_encode(array_reverse($list));
    }

    public static function getStatusUsuario($usuario)
    {
        $sql = "SELECT dh.usuario_id, 
                        du.robo_status,
                        du.modo_treinamento,
                        IFNULL(SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END), 0) AS total_win,
                        IFNULL(SUM(CASE WHEN dh.tipo = 'loss' THEN 1 ELSE 0 END), 0) AS total_loss,
                        IFNULL(SUM(dh.valor), 0) AS lucro_prejuizo,
    	                IFNULL(MAX(dh.valor_entrada + dh.valor_branco), 0) AS maior_entrada
                   FROM double_usuario du
                   LEFT JOIN double_usuario_historico dh ON du.id = dh.usuario_id AND dh.created_at >= du.robo_inicio
                  WHERE du.id = {$usuario->id}
                  GROUP BY dh.usuario_id, du.robo_status, du.modo_treinamento";

        $result = TUtils::openFakeConnection('double', function () use($sql){
            $conn = TTransaction::get();
            $list = TDatabase::getData(
                $conn, 
                $sql
            );

            return $list;
        });

        $saldo = $usuario->plataforma->service->saldo($usuario);
        if ($usuario->modo_treinamento == 'Y') 
            $saldo += $result[0]['lucro_prejuizo'];


        $convert = [
            'usuario_id'       => $result[0]['usuario_id'],
            'robo_status'      => $result[0]['robo_status'],
            'modo_treinamento' => $result[0]['modo_treinamento'],
            'total_win'        => $result[0]['total_win'],
            'total_loss'       => $result[0]['total_loss'],
            'lucro_prejuizo'   => number_format($result[0]['lucro_prejuizo'], 2, ',', '.'),
            'saldo'            => number_format($saldo, 2, ',', '.'),
            'maior_entrada'    => number_format($result[0]['maior_entrada'], 2, ',', '.'),
        ];

        $nao_mostra_treinamento = DoubleConfiguracao::getConfiguracao('nao_mostra_treinamento');
        if (in_array($usuario->chat_id, explode(',', $nao_mostra_treinamento)))
            $convert['modo_treinamento'] = 'N';

        return json_encode($convert);
    }

    public static function getRanking($canal_id, $usuario_id = null) {
        // $sql = "SELECT su.name AS nome_usuario,
        //                de.usuario_id,
        //                de.id estrategia_id,
        //                ca.plataforma_id,
        //                de.canal_id,
        //                de.nome,
        //                de.regra,
        //                de.resultado,
        //                de.protecoes,
        //                de.protecao_branco,
        //                SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END) AS total_win,
        //                SUM(CASE WHEN dh.tipo = 'loss' THEN 1 ELSE 0 END) AS total_loss,
        //                (SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END) / COUNT(1)) * 100 AS percentual,
        //                IF(ISNULL(MAX(gale)), 0, MAX(gale)) AS max_gales
        //           FROM double_estrategia de ";
        // if ($usuario_id) 
        //     $sql .= " LEFT ";
        // $sql .= " JOIN double_historico dh ON dh.estrategia_id = de.id 
        //                                   AND DATE(dh.created_at) >= DATE_ADD(CURDATE(), INTERVAL -0 DAY)
        //                                   AND dh.tipo IN ('WIN', 'LOSS')
        //           JOIN double_usuario du ON du.id = de.usuario_id
        //           JOIN system_users su ON su.custom_code = du.chat_id
        //           JOIN double_canal ca ON ca.id = de.canal_id
        //          WHERE de.canal_id = {$canal_id} 
        //            AND de.deleted_at is NULL";
        // if ($usuario_id)   
        //     $sql .= " AND de.usuario_id = {$usuario_id} AND de.ativo = 'Y' ";

        // $sql .= " GROUP BY su.name, de.usuario_id, de.canal_id, de.id, de.nome, de.regra, de.resultado, de.protecoes, de.protecao_branco
        //           ORDER BY percentual DESC, total_win ASC, total_loss DESC, max_gales DESC, de.id ASC";
        $sql = "  SELECT ranked.*,
                		 su.name AS nome_usuario,
                		 ca.plataforma_id
                  FROM ( SELECT *, 
                                ROW_NUMBER() OVER (PARTITION BY regra ORDER BY percentual DESC, total_win ASC, total_loss DESC, max_gales DESC) as rn
                			  from ( SELECT de.usuario_id,
                						    de.id estrategia_id,
                						    de.canal_id,
                						    de.nome,
                						    de.regra,
                						    de.resultado,
                						    de.protecoes,
                						    de.protecao_branco,
                                            du.chat_id,
                						    SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END) AS total_win,
                						    SUM(CASE WHEN dh.tipo = 'loss' THEN 1 ELSE 0 END) AS total_loss,
                						    (SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END) / COUNT(1)) * 100 AS percentual,
                						    IF(ISNULL(MAX(dh.gale)), 0, MAX(dh.gale)) AS max_gales
                						FROM double_estrategia de ";
        if ($usuario_id)
            $sql .= " LEFT ";
        $sql .= " JOIN double_historico dh ON dh.estrategia_id = de.id 
                						                        AND DATE(dh.created_at) >= DATE_ADD(CURDATE(), INTERVAL -0 DAY)
                						                        AND dh.tipo IN ('WIN', 'LOSS')
                					    JOIN double_usuario du ON du.id = de.usuario_id
                                       WHERE de.usuario_id IS NOT NULL
                                         AND de.deleted_at IS NULL
                					     AND de.canal_id = {$canal_id} ";
        if ($usuario_id)
            $sql .= " AND de.usuario_id = {$usuario_id} AND de.ativo = 'Y' ";
        else 
            $sql .= " AND du.robo_status = 'EXECUTANDO' ";
        $sql .= " GROUP BY de.usuario_id, de.id, de.canal_id, de.nome, de.regra, de.resultado, de.protecoes, de.protecao_branco, du.chat_id ";
        if (!$usuario_id)
            $sql .= " HAVING SUM(CASE WHEN dh.tipo = 'win' THEN 1 ELSE 0 END) > 0";
        $sql .= " ) lista
                		 ) ranked	
                  JOIN system_users su ON su.custom_code = ranked.chat_id
                  JOIN double_canal ca ON ca.id = ranked.canal_id
                 WHERE rn = 1
                 ORDER BY percentual DESC, total_win DESC, total_loss ASC, max_gales DESC, estrategia_id ASC";
        if (!$usuario_id)
            $sql .= " LIMIT 10";

        $result = TUtils::openFakeConnection('double', function () use($sql){
            $conn = TTransaction::get();
            $list = TDatabase::getData(
                $conn, 
                $sql
            );

            return $list;
        });

        $convert = [];
        foreach ($result as $key => $value) {
            $convert[] = [
                'usuario_id'       => $value['usuario_id'],
                'nome_usuario'     => $value['nome_usuario'],
                'estrategia_id'    => $value['estrategia_id'],
                'plataforma_id'    => $value['plataforma_id'],
                'canal_id'         => $value['canal_id'],
                'nome'             => $value['nome'],
                'regra'            => $value['regra'],
                'resultado'        => $value['resultado'],
                'win'              => $value['total_win'],
                'loss'             => $value['total_loss'],
                'percentual'       => number_format($value['percentual'], 2, ',', '.'),
                'max_gales'        => $value['max_gales'],
                'protecoes'        => $value['protecoes'],
                'protecao_branco'  => $value['protecao_branco'],
            ];        
        }
        
        return $convert;
    }
}
