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
        echo json_encode(array_reverse($list));
    }

    public static function getStatusUsuario($usuario)
    {
        // $sql = "SELECT usuario_id,
        //                robo_status,
        //                modo_treinamento,
        //                valor_entrada, 
        //                stop_win, 
        //                stop_loss,
        //                SUM(CASE WHEN tipo = 'win' THEN 1 ELSE 0 END) AS total_win,
        //                SUM(CASE WHEN tipo = 'loss' THEN 1 ELSE 0 END) AS total_loss,
        //                SUM(valor) lucro_prejuizo
        //           FROM (SELECT u.id usuario_id, 
        //                        u.robo_status, 
        //                        u.modo_treinamento,
        //                        u.valor valor_entrada,
        //                        u.stop_win,
        //                        u.stop_loss,
        //                        CASE ROW_NUMBER() OVER (PARTITION BY uh.entrada_id ORDER BY uh.entrada_id, uh.id) -1
        //                             WHEN u.protecao then if(uh.valor > 0, 'WIN', 'LOSS')
        //                             WHEN 0 THEN IF(uh.valor > 0, 'WIN', 'ENTRADA')
        //                             ELSE IF(uh.valor > 0, 'WIN', 'GALE')
        //                         END tipo,
        //                         IFNULL(uh.valor, 0) valor
        //                   FROM double_usuario u
        //                   LEFT JOIN double_usuario_historico uh ON uh.usuario_id = u.id
        //                                                        AND uh.created_at >= u.robo_inicio
        //                  WHERE u.id = {$usuario->id}
        //                 ) tmp
        //            GROUP BY usuario_id, robo_status, modo_treinamento, valor_entrada, stop_win, stop_loss";

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
            // 'valor_entrada'    => number_format($result[0]['valor_entrada'], 2, ',', '.'),
            // 'stop_win'         => number_format($result[0]['stop_win'], 2, ',', '.'),
            // 'stop_loss'        => number_format($result[0]['stop_loss'], 2, ',', '.'),
        ];
        echo json_encode($convert);
    }
}
