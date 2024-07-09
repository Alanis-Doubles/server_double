<?php

use Adianti\Database\TDatabase;
use Adianti\Database\TRecord;

use function Safe\json_encode;

class DoubleEstrategia extends DoubleRecord
{
    const TABLENAME  = 'double_estrategia';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';
    const DELETEDAT  = 'deleted_at';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function validar($sinais, IDoublePlataforma $plataforma)
    {
        switch ($this->tipo) {
            case 'COR':
                $resultado = $this->validarCor($sinais);
                break;
            case 'NUMERO':
                $resultado = $this->validarNumero($sinais);
                break;
            case 'SOMA':
                $resultado = $this->validarSoma($sinais, $plataforma);
                break;
            case 'BRANCO':
                $resultado = $this->validarBranco($sinais, $plataforma);
                break;

            default:
                $resultado = false;
                break;
        }

        return $resultado;
    }

    public function validarCor($sinais)
    {
        if ($this->tipo_controle == 'APARTIR_HORA' and $this->valor_controle) {
            $now = new DateTime();
            $date = date_create_from_format('Y-m-d H:i:s', $this->valor_controle);

            if ($date > $now)
                return false;
        } elseif ($this->tipo_controle == 'DIVISAO' ) {
            $arrRegra = explode(' - ', $this->regra);
            $total = count($arrRegra);
            if ($total == 1) {
                if ($this->valor_controle) {
                    $date = date_create_from_format('Y-m-d H:i:s', $this->valor_controle);
                    $now = new DateTime();
                    $diff = $date->diff($now);  

                    $tempo = ($diff->i * 60 + $diff->s) * ($diff->invert ? -1 : 0);
                    $protecoes = $this->canal->protecoes + 1;
                    if ($tempo > (intval($this->valor_incremento) * $protecoes) and $this->regra == $sinais[0]['cor']) {
                        $this->valor_controle = null;
                        $this->saveInTransaction();
                    }
                }

                if ($this->valor_controle) {
                    $now = new DateTime();
                    $date = date_create_from_format('Y-m-d H:i:s', $this->valor_controle);
        
                    if ($date > $now)
                        return false;
                    else
                        return true;
                } else {
                    if ($this->regra != $sinais[0]['cor'])
                        return false;
                    
                    $numero = $sinais[0]['numero'];
                    if($numero % 2 == 0){
                        $now = new DateTime();
                        $date = date_create_from_format('Y-m-d H:i:s', $now->format('Y-m-d H:i:00'));

                        $incremento = ($numero / 2) . ' MINUTES';

                        date_add($date, date_interval_create_from_date_string($incremento));
                        date_add($date, date_interval_create_from_date_string('-20 SECONDS'));

                        if ($date > $now) {
                            $this->valor_controle = $date->format('Y-m-d H:i:s');
                            $this->saveInTransaction();
                            return false;
                        }
                        else
                            return true;
                    }
                    else
                        return false;
                }
            }
        }

        // $arrRegra = explode(' - ', $this->regra);
        // array_pop($arrRegra);
        // $total = count($arrRegra);
        // $strRegra = implode(' - ', $arrRegra);
        // $valor = '';
        // if (count($sinais) >= $total) {
        //     for ($i = 0; $i < $total; $i++) {
        //         $valor = $sinais[$i]['cor'] . ($i > 0 ? ' - ' : '') . $valor;
        //     }
        // }

        // $resposta = $strRegra == $valor;

        $map_sinais = function($data) {
            return $data['cor'];
        };

        $reverse_sinais = array_reverse($sinais);
        $str_sinais = implode(' - ', array_map($map_sinais, $reverse_sinais));
        $arrRegra = explode(' - ', $this->regra);
        array_pop($arrRegra);
        $str_regra = '/' . implode(' - ', $arrRegra) . '$/';
        $str_regra = str_replace(
            ['other'],
            ['(red|black|white)'],
            $str_regra,
        );
        $resposta = preg_match($str_regra, $str_sinais) == 1;

        return $resposta;
    }

    public function validarNumero($sinais)
    {
        return $this->regra == $sinais[0]['numero'];
    }

    public function validarSoma($sinais, IDoublePlataforma $plataforma)
    {
        $resultado = false;
        if (count($sinais) >= 3) {
            $possuiBranco = false;
            for ($i = 0; $i < 3; $i++) {
                $possuiBranco = $sinais[$i]['numero'] == 0;
                if ($possuiBranco)
                    return false;
            }

            $sum_value = ($sinais[2]['numero'] + $sinais[1]['numero']) - $sinais[0]['numero'];
            if ($sum_value > count($plataforma->cores())-1 or $sum_value <= 0)
                return false;

            $this->resultado = $plataforma->cores()[$sum_value];
            $resultado = $this->resultado <> null;
        }
        return $resultado;
    }

    // public function validarBranco($sinais) 
    // {
    //     $minute_list = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];
        
    //     $current_time = new DateTime();
    //     $current_minute = (int)$current_time->format('i');
    //     $current_second = (int)$current_time->format('s');

    //     // Verificar se a hora atual está a 30 segundos de um dos minutos listados
    //     $is_near = false;
    //     foreach ($minute_list as $minute) {
    //         if (abs($current_minute - $minute) <= 1) {
    //             if (($current_minute == $minute && $current_second <= 30) || 
    //                 ($current_minute == ($minute - 1) % 60 && $current_second >= 30)) {
    //                 $is_near = true;
    //                 break;
    //             }
    //         }
    //     }

    //     if ($is_near) {
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }
    
    // public function validaBranco($sinais) 
    // {
    //     $result = TUtils::openFakeConnection('double', function () {
    //         $source = TTransaction::get();

    //         $canal = DoubleCanal::identificar($this->canal_id);
    //         $plataforma = $canal->plataforma;
            
    //         $sql = "SELECT DATE_ADD(created_at, INTERVAL 13 MINUTE) AS new_time
    //         FROM double_sinal
    //         WHERE cor = 'white'
    //           AND plataforma_id = {$plataforma->id}
    //           AND created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-%d %H:00:00'), INTERVAL 1 HOUR)
    //           AND created_at < DATE_FORMAT(NOW(), '%Y-%m-%d %H:00:00')
    //         ORDER BY created_at DESC
    //         LIMIT 1";

    //         return TDatabase::getData($source, $sql);
    //     });

    //     if (count($result) > 0) {
    //         // Pegar o resultado da consulta
    //         $row = $result[0];
    //         $new_time = new DateTime($row['new_time']);
    //         $start_minute = (int)$new_time->format('i');
        
    //         // Construir a lista de minutos
    //         $minute_list = [];
    //         for ($i = 0; $i < 6; $i++) {
    //             $minute_list[] = ($start_minute + $i * 10) % 60;
    //         }
        
    //         // Ordenar a lista de minutos
    //         sort($minute_list);
    //         // DoubleErros::registrar(1, 'DoubleEstrategia', 'validrBranco', $row['new_time'], json_encode($minute_list));
        
    //         // Exibir a lista de minutos
    //         // echo "Lista de minutos: " . implode(", ", $minute_list) . "\n";

    //         // Hora e minuto atual
    //         $current_time = new DateTime();
    //         $current_minute = (int)$current_time->format('i');
    //         $current_second = (int)$current_time->format('s');

    //         // Verificar se a hora atual está a 30 segundos de um dos minutos listados
    //         $is_near = false;
    //         foreach ($minute_list as $minute) {
    //             if (abs($current_minute - $minute) <= 1) {
    //                 if (($current_minute == $minute && $current_second <= 30) || 
    //                     ($current_minute == ($minute - 1) % 60 && $current_second >= 30)) {
    //                     $is_near = true;
    //                     break;
    //                 }
    //             }
    //         }

    //         if ($is_near) {
    //             return true;
    //         } else {
    //             return false;
    //         }
    //     } else {
    //         return false;
    //     }

    // }

    public function validarBranco($sinais) 
    {
        // busca o último branco nos últimos 6 minutos
        $result = TUtils::openFakeConnection('double', function () {
            $source = TTransaction::get();

            $canal = DoubleCanal::identificar($this->canal_id);
            $plataforma = $canal->plataforma;
            
            $sql = "SELECT DATE_FORMAT(DATE_ADD(DATE_SUB(created_at, INTERVAL 16 SECOND), INTERVAL 7 MINUTE), '%i') AS minute,
                DATE_FORMAT(DATE_SUB(created_at, INTERVAL 16 SECOND), '%i') AS origem
            FROM double_sinal
            WHERE cor = 'white'
              AND plataforma_id = {$plataforma->id}
              AND created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-%d %H:00:00'), INTERVAL 5 MINUTE)
            ORDER BY created_at DESC
            LIMIT 1";

            return TDatabase::getData($source, $sql);
        });

        $origem = $result[0]['origem'];
        $incremento = $this->valor_incremento;
        if ($origem >= 0 and $incremento >  $origem)
            $origem += 60;

        // DoubleErros::registrar(1,'doubleestrategia','validarbranco',$origem,$incremento);
        

        if (!$this->valor_controle) { 
            if ($result and $origem > $incremento) {
                $this->valor_controle = $result[0]['origem'] . '|' . $result[0]['minute'];
                $this->valor_incremento = null;
                $this->saveInTransaction();
                // DoubleErros::registrar(1,'doubleestrategia','validarbranco',json_encode($result),$this->valor_controle);
            }

            return false;
        } else {
            $controle = explode('|', $this->valor_controle);
            // $origem = $controle[0];
            $minute = $controle[1];

            DoubleErros::registrar(1,'doubleestrategia','validarbranco',$result[0]['minute'],$minute-1);
            if ($result[0]['minute'] == $minute-1) {
                $this->valor_controle = null;
                $this->valor_incremento = $minute;
                $this->saveInTransaction();

                return false;
            }
            
            // Hora e minuto atual
            $current_time = new DateTime();
            $current_minute = (int)$current_time->format('i');
            $current_second = (int)$current_time->format('s');

            $is_near = false;
            if (abs($current_minute - $minute) <= 1) {
                if (($current_minute == $minute && $current_second <= 30) || 
                    ($current_minute == ($minute - 1) % 60 && $current_second >= 30)) {
                    $is_near = true;
                }
            }

            if ($is_near) {
                $this->valor_controle = null;
                $this->valor_incremento = $minute;
                $this->saveInTransaction();
                // DoubleErros::registrar(1,'doubleestrategia','validarbranco','aqui',$this->valor_controle);
            }
            
            return $is_near;
        }
    }

    // public function validaBranco($sinais)
    // {
    //     $now = new DateTime();

    //     $ultimo_win_branco = TUtils::openFakeConnection('double', function () {
    //         $canal = DoubleCanal::identificar($this->canal_id);
    //         $plataforma = $canal->plataforma;
    //         return DoubleHistorico::where('plataforma_id', '=', $plataforma->id)
    //             ->where('cor', '=', 'white')
    //             ->where('tipo', '=', 'WIN')
    //             ->select(['created_at'])
    //             ->last();
    //     });

    //     if ($ultimo_win_branco) {
    //         $date = date_create_from_format('Y-m-d H:i:s', $ultimo_win_branco->created_at);
    //         $diff = $date->diff($now);  
    //         $tempo = $diff->i * 60 + $diff->s;
    //         if ($tempo < 59)
    //             return false;
    //     }

    //     $proxima_hora = strval($now->format('H') + 1);
    //     if ($proxima_hora < 10)
    //         $proxima_hora = '0' . $proxima_hora;
    //     $date = date_create_from_format('Y-m-d H:i:s', $now->format('Y-m-d'). ' ' . $proxima_hora .':00:00');
    //     $diff = $date->diff($now);  

    //     $tempo = $diff->i * 60 + $diff->s;
    //     if ($tempo <= 20)
    //         $hora_busca = strval($now->format('H'));
    //     else
    //         $hora_busca = strval($now->format('H') - 1);

    //     if ($hora_busca < 10)
	//         $hora_busca = '0' . $hora_busca;
    //     $dataIni = $now->format('Y-m-d'). ' ' . $hora_busca .':00:00';
    //     $dataFim = $now->format('Y-m-d'). ' ' . $hora_busca .':59:59';

    //     $ultimo_branco = TUtils::openFakeConnection('double', function() use ($dataIni, $dataFim){
    //         $canal = DoubleCanal::identificar($this->canal_id);
    //         $plataforma = $canal->plataforma;
    //         $sinal = DoubleSinal::where('plataforma_id', '=', $plataforma->id)
    //             ->where('cor', '=', 'white')
    //             ->where('created_at', '>=', $dataIni)
    //             ->where('created_at', '<', $dataFim)
    //             ->maxBy('created_at', 'ultimo_branco');

    //         return $sinal;
    //     });

    //     $encontrou = false;
    //     if ($ultimo_branco) {
    //         $date = date_create_from_format('Y-m-d H:i:s', $ultimo_branco);

    //         // Primeora Busca
    //         $calculo = $date->format('i') + 3;
    //         if ($calculo < 10)
	//             $calculo = '0' . $calculo;

    //         for ($i=0; $i < 6; $i++) {
    //             $min_compare = intval($i . strval($calculo)[1]);
    //             if ($min_compare < 10)
    //                 $min_compare = '0' . $min_compare;

    //             $data_compare = date_create_from_format(
    //                 'Y-m-d H:i:s', 
    //                 $now->format('Y-m-d H') . ':' . $min_compare . ':00' 
    //             );
                
    //             // date_add($data_compare, date_interval_create_from_date_string('-20 SECONDS'));

    //             $diff = $data_compare->diff($now);  
    //             $tempo = ($diff->i * 60 + $diff->s) * ($diff->invert == 1 ? -1 : 1);
    //             if ($tempo > -21 and $tempo < 59) {
    //                 $this->valor_controle = $data_compare->format('Y-m-d H:i:s') .'-'. $now->format('Y-m-d H:i:s') ;
    //                 $this->valor_incremento = $tempo;
    //                 $this->saveInTransaction();
    //                 $encontrou = true;
    //                 break;
    //             }
    //         }

    //         // Segunda Busca
    //         $calculo = $calculo + 4;
    //         if ($calculo < 10)
	//             $calculo = '0' . $calculo;

    //         for ($i=0; $i < 6; $i++) {
    //             $min_compare = intval($i . strval($calculo)[1]);
    //             if ($min_compare < 10)
    //                 $min_compare = '0' . $min_compare;
    //             $data_compare = date_create_from_format(
    //                 'Y-m-d H:i:s', 
    //                 $now->format('Y-m-d H') . ':' . $min_compare . ':00' 
    //             );
                
    //             $diff = $data_compare->diff($now);  
    //             $tempo = ($diff->i * 60 + $diff->s) * ($diff->invert == 1 ? -1 : 1);
    //             if ($tempo > -31 and $tempo < 59) {
    //                 $encontrou = true;
    //                 break;
    //             }
    //         }

    //         // // Terceira Busca
    //         // $calculo = $calculo + 4;
    //         // if ($calculo < 10)
	//         //     $calculo = '0' . $calculo;

    //         // for ($i=0; $i < 6; $i++) {
    //         //     $min_compare = intval($i . strval($calculo)[1]);
    //         //     if ($min_compare < 10)
    //         //         $min_compare = '0' . $min_compare;
    //         //     $data_compare = date_create_from_format(
    //         //         'Y-m-d H:i:s', 
    //         //         $now->format('Y-m-d H') . ':' . $min_compare . ':00' 
    //         //     );
                
    //         //     $diff = $data_compare->diff($now);  
    //         //     $tempo = ($diff->i * 60 + $diff->s) * ($diff->invert == 1 ? -1 : 1);
    //         //     if ($tempo > -31 and $tempo < 59) {
    //         //         $encontrou = true;
    //         //         break;
    //         //     }
    //         // }
    //     }

    //     return $encontrou;
    // }

    public function aguardarProximoSinal()
    {
        if ($this->tipo == 'COR')
            if ($this->tipo_controle == 'DIVISAO') {
                return false;
            } else
                return true;
        else
            return false;
    }

    public function validarProximoSinal($sinais)
    {
        if ($this->tipo == 'COR') {
            $arrRegra = explode(' - ', $this->regra);
            $strRegra = array_pop($arrRegra);
            $valor =  $sinais[0]['cor'];
            $resposta = $strRegra == $valor;

            $map_sinais = function($data) {
                return $data['cor'];
            };
    
            $reverse_sinais = array_reverse($sinais);
            $str_sinais = implode(' - ', array_map($map_sinais, $reverse_sinais));
            $str_regra = '/' . $this->regra . '$/';
            $str_regra = str_replace(
                ['other'],
                ['(red|black|white)'],
                $str_regra,
            );
            $resposta = preg_match($str_regra, $str_sinais) == 1;

            if ($resposta and $this->tipo_controle == 'APARTIR_HORA') {
                $now = new DateTime();
                date_add($now, date_interval_create_from_date_string($this->valor_incremento));
                $this->valor_controle = $now->format('Y-m-d H:i:s');
                $this->saveInTransaction();
            }

            return $resposta;
        } else
            return true;
    }

    public function processarRetorno($sinais)
    {
        $resultado = $sinais[0]['cor'] == $this->resultado;

        if ($resultado and $this->tipo_controle == 'APARTIR_HORA' and $this->valor_controle) {
            $this->valor_controle == null;
            $this->saveInTransaction();
        }

        if ($resultado and $this->tipo_controle == 'DIVISAO' and $this->valor_controle) {
            $this->valor_controle = null;
            $this->saveInTransaction();
        }

        return $resultado;
    }

    public function get_canal()
    {
        if (!$this->obj_canal) {
            $this->obj_canal =  TUtils::openConnection('double', function () {
                $result = new DoubleCanal($this->canal_id, false);
                if (!$result)
                    $result = new DoubleCanal();
                return $result;
            });
        }
        
        return $this->obj_canal;
    }

    public function get_agrupamento()
    {
        $result = $this->canal->plataforma->render('[{idioma}] {nome}');
        if ($this->canal->plataforma->usuarios_canal == 'Y')
            $result .= ' / ' . $this->canal->nome;

        return $result;
    }
}
