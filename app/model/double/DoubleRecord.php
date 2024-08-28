<?php

use Adianti\Database\TRecord;

class DoubleRecord extends TRecord
{
    private $originalData = [];

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        // if ($id) {
        //     // Carrega os dados originais do banco de dados
        //     $this->load($id);
        //     $this->originalData = $this->toArray();
        // }
    }

    public function store()
    { 
        if (!$this->id) {
            if (in_array('create_at', $this->getAttributes()))
                $this->create_at = (new DateTime())->format('Y-m-d H:i:s');
        } else {
            if (in_array('updated_at', $this->getAttributes()))
                $this->updated_at = (new DateTime())->format('Y-m-d H:i:s');
        }
        
        // parent::store();
        // Obtém os dados atuais
        $currentData = $this->toArray();
        $primaryKey = $this->getPrimaryKey();
        
        // Verifica se é um INSERT ou UPDATE
        if (empty($this->$primaryKey)) {
            // Realiza o INSERT
            parent::store();
            // Atualiza os dados originais após o insert
            $this->originalData = $this->toArray();
        } else {
            $class = get_class($this);
            // $this->originalData = TUtils::openFakeConnection('double', function() use($class){
            //     $obj = new $class($this->id, false);
            //     return $obj->toArray();
            // });

            $obj = new $class($this->id, false);
            $this->originalData = $obj->toArray();

            // Compara os dados originais com os dados atuais
            $changedFields = array_diff_assoc((array) $currentData, $this->originalData);

            if (!empty($changedFields)) {
                // Gera a consulta UPDATE personalizada
                $setPart = [];
                $params = [];
                foreach ($changedFields as $field => $value) {
                    $setPart[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }

                $setPart = implode(', ', $setPart);
                $params[":{$primaryKey}"] = $this->$primaryKey;

                $sql = "UPDATE " . $this->getEntity() . " SET $setPart WHERE $primaryKey = :$primaryKey";

                TUtils::openConnection('double', function() use ($sql, $params){
                    $conn = TTransaction::get();
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                });
                
            }
        }
    }
}