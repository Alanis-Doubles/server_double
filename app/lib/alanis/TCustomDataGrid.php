<?php

use Adianti\Widget\Datagrid\TDataGrid;


/**
 * TFaDataGrid
 *
 * @version    1.0
 * @package    alanis
 * @author     Edson Alanis
 * @copyright  Copyright (c) 2022 Alanis.
 */

class TCustomDataGrid extends TDataGrid
{
    public function getActions(){
        return $this->actions;
    }
}