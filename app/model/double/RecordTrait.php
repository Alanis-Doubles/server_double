<?php

use Adianti\Database\TTransaction;

/**
 * Record Trait
 *
 * @version    1.0
 * @package    double
 * @subpackage app\model
 * @author     Edson Alanis
 * @copyright  Copyright (c) 2023 Fabank
 */

 trait RecordTrait
 {
    private static $atributos;

    public function loadAttributes($database)
    {
        $className = get_class($this);
        if (!isset(self::$atributos[$className]))
            // self::$atributos[$className] = TUtils::openFakeConnection($database, function () use ($database) {
                $conn = TTransaction::get();
                $data = $conn->query("DESCRIBE " . self::TABLENAME);

                $result = [];
                while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                    if (($row['Field'] != 'id') and ($row['Field'] != 'data')) {
                        $result[] = $row['Field'];
                    }
                }

                return $result;
            // });
        
        $result = self::$atributos[$className];
        foreach ($result as $key => $row) {
            parent::addAttribute($row);
        }
    }

    public function toArray( $filter_attributes = null )
    {
        $attributes = $filter_attributes ? $filter_attributes : $this->attributes;
        
        $data = array();
        if (count($attributes) > 0)
        {
            $pk = $this->getPrimaryKey();
            if (!empty($this->data))
            {
                $data[$pk] = $this->$pk;
                foreach ($attributes as $key => $value)
                {
                    $data[$value] = $this->$value;
                    if (is_object($data[$value]))
                        $data[$value] = $data[$value]->toArray();
                }
            }
        }
        else
        {
            $data = $this->data;
        }
        return $data;
    }

    public function saveInTransaction($database = 'double') {
        TUtils::openConnection($database, function() {
            $this->save();
        });
    }
 }