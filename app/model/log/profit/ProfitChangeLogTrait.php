<?php

trait ProfitChangeLogTrait
{
    public function onAfterDelete( $object )
    {
        $usuario_logado = TSession::getValue('login');
        if ($usuario_logado == 'api')
          return;
        
        $deletedat = self::getDeletedAtColumn();
        if ($deletedat)
        {
            $lastState = (array) $object;

            $info = TTransaction::getDatabaseInfo();
            $date_mask = (in_array($info['type'], ['sqlsrv', 'dblib', 'mssql'])) ? 'Ymd H:i:s' : 'Y-m-d H:i:s';
            $object->{$deletedat} = date($date_mask);

            SystemChangeLogService::register($this, $lastState, (array) $object);
        }
        else
        {
            SystemChangeLogService::register($this, $object, [], 'delete');
        }
    }
    
    public function onBeforeStore($object)
    {
        $usuario_logado = TSession::getValue('login');
        if ($usuario_logado == 'api')
          return;
        
        $pk = $this->getPrimaryKey();
        $this->lastState = array();
        
        if (!empty($object->$pk))
        {
            $object = parent::load($object->$pk, TRUE);
            
            if ($object instanceof TRecord)
            {
                $this->lastState = $object->toArray();
            }
        }
    }
    
    public function onAfterStore($object)
    {
        $usuario_logado = TSession::getValue('login');
        if ($usuario_logado == 'api')
          return;

        SystemChangeLogService::register($this, $this->lastState, (array) $object);
    }
}
