<?php

use Adianti\Database\TRecord;

class DoubleRecord extends TRecord
{
    public function store()
    { 
        if (!$this->id) {
            if (in_array('create_at', $this->getAttributes()))
                $this->create_at = (new DateTime())->format('Y-m-d H:i:s');
        } else {
            if (in_array('updated_at', $this->getAttributes()))
                $this->updated_at = (new DateTime())->format('Y-m-d H:i:s');
        }
        
        parent::store();
    }
}