<?php

use Adianti\Control\TAction;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardList;
use Adianti\Database\TDatabase;
use Adianti\Widget\Form\TCombo;
use Adianti\Database\TTransaction;
use Adianti\Widget\Util\TDropDown;

class TDoubleHistoricoUsuario extends TStandardList
{
    use TUIBuilderTrait;
    use TTransformationTrait;

    public function __construct($param)
    {
        parent::__construct();

        parent::setDatabase('double');          
        parent::setActiveRecord('DoubleUsuarioHistorico'); 
        parent::addFilterField('robo_inicio', '=', 'robo_inicio');
        parent::setLimit(TSession::getValue(get_class($this) . '_limit') ?? 10);
        parent::setDefaultOrder('id', 'desc');
        parent::setAfterSearchCallback( [$this, 'onAfterSearch' ] );

        $usuario_id = TUtils::openFakeConnection('double', function() {
            $obj = DoubleUsuario::where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->where('canal_id', '=', 18)
                ->first();
            return $obj->id;
        });

        $criteria = new TCriteria;
        $criteria->add(
            new TFilter('usuario_id', '=', $usuario_id)
        );
        $criteria->add(
            new TFilter('entrada_id', 'is not', null)
        );
        $criteria->add(
            new TFilter('fator', 'is not', null)
        );
        parent::setCriteria($criteria);

        $dataGrid = new stdClass;
        $dataGrid->name = 'datagrid';
        $dataGrid->pagenavigator = true;
        $dataGrid->disableDefaultClick = true;
        $dataGrid->groupColumn = [
            'name' => 'robo_inicio',
            'mask' => null,
            'transformer' => Closure::fromCallable([$this, 'roboInicioTransformer'])
        ];
        $dataGrid->columns = [
            ['name' => 'id', 'hide' => true, 'label' => 'Id', 'width' => '10%', 'align' => 'left'],
            ['name' => 'created_at', 'label' => 'Criado em', 'width' => '15%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'datetimeCompleteTransformer'])],
            ['name' => 'tipo', 'label' => 'Tipo', 'width' => '5%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'tipoTransformer'])],
            ['name' => 'gale', 'label' => 'Gale', 'width' => '5%', 'align' => 'center'],
            ['name' => 'valor_entrada', 'label' => 'Valor entrada', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'valor_branco', 'label' => 'Valor empate', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'lucro_prejuizo', 'label' => 'Lucro/Prejuízo', 'width' => '30%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'lucroPrejuizoTransformer'])],
            ['name' => 'saldo_atual', 'label' => 'Saldo atual', 'width' => '15%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'saldoAtualTransformer'])],
        ];

        $panel = $this->makeTDataGrid($dataGrid);
        $this->datagrid = $this->getWidget('datagrid');
        $this->pageNavigation = $this->getWidget('datagrid_pnv');

        $datas = TUtils::openFakeConnection('double', function() use ($criteria) {
            $source = TTransaction::get();
            // transformation function
            $format_data  = function ($value, $row) {
                $date = new DateTime($value);
                return $date->format('d/m/Y H:i:s');
            };
            
            // mapping rules between source query and the target table
            $mapping = [];
            $mapping[] = [ 'robo_inicio', 'id' ];
            $mapping[] = [ 'robo_inicio', 'robo_inicio', $format_data ];
            
            // define the query
            $query = "SELECT DISTINCT robo_inicio
                        FROM double_usuario_historico
                       WHERE %WHERE%
                       ORDER BY robo_inicio DESC LIMIT 15";
            
            $query = $this->addCriteraSQL($criteria, $query);

            $list = [];
            TDatabase::getData($source, $query, $mapping, null, function($values) use (&$list) {
                $key = $values['id'];
                $val = $values['robo_inicio'];
                $list[ $key ] = $val;
            });

            return $list;
        });

        $dropdown = new TDropDown( TSession::getValue($this->activeRecord.'_robo_inicio') == '' ? '- Todos -' : TSession::getValue($this->activeRecord.'_robo_inicio'), '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction('- Todos -', new TAction([$this, 'onSearch'], ['register_state' => 'false', 'static'=>'1', 'robo_inicio' => '']));
        foreach ($datas as $key => $value) {
            $dropdown->addAction($value, new TAction([$this, 'onSearch'], ['register_state' => 'false', 'static'=>'1', 'robo_inicio' => $key]));
        }
        $panel->addHeaderWidget( $dropdown );        

        $dropdown = new TDropDown( TSession::getValue(get_class($this) . '_limit') ?? '10', '');
        $dropdown->style = 'height:37px';
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( 10,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '10']) );
        $dropdown->addAction( 20,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '20']) );
        $dropdown->addAction( 50,   new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '50']) );
        $dropdown->addAction( 100,  new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '100']) );
        $dropdown->addAction( 1000, new TAction([$this, 'onChangeLimit'], ['register_state' => 'false', 'static'=>'1', 'limit' => '1000']) );
        $panel->addHeaderWidget( $dropdown );        

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', get_class($this)));
        $container->add($panel);
        
        parent::add($container);
    }

    public function tipoTransformer($value, $object, $row)
    {
        switch ($value) {
            case 'WIN':
                $class = 'success';
                break;
            case 'LOSS':
                $class = 'danger';
                break;
            default:
                $class = 'warning';
                break;
        }

        $label = $value;
        $div = new TElement('span');
        $div->class = "label label-{$class}";
        $div->style = "text-shadow:none; font-size:12px; font-weight:lighter";
        $div->add($label);
        return $div;
    }

    public function lucroPrejuizoTransformer($value, $object)
    {
        if ($value) {
            $valor_entrada = number_format($object->valor_entrada, 2, ',', '.');
            $valor_branco = number_format($object->valor_branco, 2, ',', '.');
            $lucro_prejuizo = 'R$ ' . number_format($object->lucro_prejuizo, 2, ',', '.');

            if ($object->tipo === 'WIN') 
            {
                if ($object->fator == 2) {
                    $total_entrada = number_format($object->valor_entrada * $object->fator, 2, ',', '.') . " ({$valor_entrada} * {$object->fator})";
                    return "<span style='color:green'>{$total_entrada}</span><span style='color:red'> - {$valor_entrada} - {$valor_branco}</span> = <span style='" . ($object->lucro_prejuzo > 0 ? 'color:green' : 'color:red') . "'>{$lucro_prejuizo}</span>";
                } else {
                    $total_entrada = number_format($object->valor_entrada * 0.9, 2, ',', '.') . " ({$valor_entrada} * 90%)";
                    $total_branco = number_format($object->valor_branco * $object->fator, 2, ',', '.') . " ({$valor_branco} * {$object->fator})";
                    return "<span style='color:red'>{$total_entrada}</span><span style='color:green'> + {$total_branco}</span> = <span style='" . ($object->lucro_prejuzo > 0 ? 'color:green' : 'color:red') . "'>{$lucro_prejuizo}</span>";
                }
            } else {
                return "<span style='color:red'>- {$valor_entrada}</span><span style='color:red'> - {$valor_branco}</span> = <span style='color:red'>{$lucro_prejuizo}</span>";
            }
        } else {
            return '';
        }
    }

    public function saldoAtualTransformer($value, $object)
    {
        if ($value) {
            $saldo_atual = $object->saldo_atual + $object->banca_inicial;
            $formatado = 'R$ ' . number_format($saldo_atual, 2, ',', '.');
            if ($saldo_atual > 0) {
                return "{$formatado}";
            } else {
                return "<span style='color:red'>{$formatado}</span>";
            }
        } else {
            return '';
        }
    }

    public function roboInicioTransformer($value)
    {
        if ($value) {
            $date = new DateTime($value);
            return '<b>Início:</b> <i>'. $date->format('d/m/Y H:i:s') . '</i>';
        } else {
            return '';
        }
    }

    public static function onShowCurtainFilters($param = null)
    {
        $class = get_called_class();

        try
        {
            $page = new TPage;
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('override', 'true');
            $page->setPageName($class);
            
            $btn_close = new TButton('closeCurtain');
            $btn_close->onClick = "Template.closeRightPanel();";
            $btn_close->setLabel("Fechar");
            $btn_close->setImage('fas:times');
            
            $embed = new $class($param);
            $embed->form->addHeaderWidget($btn_close);
            
            // embed form inside curtain
            $page->add($embed->form);
            $page->setIsWrapped(true);
            $page->show();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onChangeLimit($param)
    {
        TSession::setValue(get_class($this) . '_limit', $param['limit'] );
        AdiantiCoreApplication::loadPage(get_class($this), 'onReload');
    }

    public function onSearch( $param = null )
    {
        // get the search form data
        $data = (object) $param; //$this->form->getData();
        
        $count_filters = 0;
        
        if ($this->formFilters)
        {
            foreach ($this->formFilters as $filterKey => $formFilter)
            {
                $operator       = isset($this->operators[$filterKey]) ? $this->operators[$filterKey] : 'like';
                $filterField    = isset($this->filterFields[$filterKey]) ? $this->filterFields[$filterKey] : $formFilter;
                $filterFunction = isset($this->filterTransformers[$filterKey]) ? $this->filterTransformers[$filterKey] : null;
                
                // check if the user has filled the form
                if (!empty($data->{$formFilter}) OR (isset($data->{$formFilter}) AND $data->{$formFilter} == '0'))
                {
                    // $this->filterTransformers
                    if ($filterFunction)
                    {
                        $fieldData = $filterFunction($data->{$formFilter});
                    }
                    else
                    {
                        $fieldData = $data->{$formFilter};
                    }
                    
                    // creates a filter using what the user has typed
                    if (stristr($operator, 'like'))
                    {
                        $filter = new TFilter($filterField, $operator, "%{$fieldData}%");
                    }
                    else
                    {
                        $filter = new TFilter($filterField, $operator, $fieldData);
                    }
                    
                    // stores the filter in the session
                    TSession::setValue($this->activeRecord.'_filter', $filter); // BC compatibility
                    TSession::setValue($this->activeRecord.'_filter_'.$formFilter, $filter);
                    TSession::setValue($this->activeRecord.'_filter_'.$filterKey, $filter);
                    TSession::setValue($this->activeRecord.'_'.$formFilter, $data->{$formFilter});
                    
                    $count_filters ++;
                }
                else
                {
                    TSession::setValue($this->activeRecord.'_filter', NULL); // BC compatibility
                    TSession::setValue($this->activeRecord.'_filter_'.$formFilter, NULL);
                    TSession::setValue($this->activeRecord.'_filter_'.$filterKey, NULL);
                    TSession::setValue($this->activeRecord.'_'.$formFilter, '');
                }
            }
        }
        
        TSession::setValue($this->activeRecord.'_filter_data', $data);
        TSession::setValue(get_class($this).'_filter_data', $data);
        TSession::setValue(get_class($this).'_filter_counter', $count_filters);
        
        // // fill the form with data again
        // $this->form->setData($data);
        
        if (is_callable($this->afterSearchCallback))
        {
            call_user_func($this->afterSearchCallback, $this->datagrid, $data);
        }
        
        // $param['offset'] = 0;
        // $param['first_page'] = 1;
        // if (isset($param['static']) && ($param['static'] == '1') )
        // {
        //     $class = get_class($this);
        //     $param = $this->beforeStaticLoadPage($param);
        //     AdiantiCoreApplication::loadPage($class, 'onReload', $param );
        // }
        // else
        // {
        //     $this->onReload( $param );
        // }
        AdiantiCoreApplication::loadPage(get_class($this), 'onReload');
    }

    public function onAfterSearch($datagrid, $options)
    {
       if (!empty(TSession::getValue(get_class($this).'_filter_data')))
        {
            $obj = new stdClass;
            $obj->robo_inicio = TSession::getValue(get_class($this).'_filter_data')->robo_inicio;
            TForm::sendData('form_search_name', $obj);
        }
    }

    public function onReload($param = NULL)
    {
        if (!isset($this->datagrid))
        {
            return;
        }
        
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            $param_criteria = $param;
            
            // open a transaction with database
            TTransaction::open($this->database);
            
            // instancia um repositório
            $repository = new TRepository($this->activeRecord);
            $limit = isset($this->limit) ? ( $this->limit > 0 ? $this->limit : NULL) : 10;
            
            // creates a criteria
            $criteria = isset($this->criteria) ? clone $this->criteria : new TCriteria;
            if ($this->order)
            {
                $criteria->setProperty('order',     $this->order);
                $criteria->setProperty('direction', $this->direction);
            }
            

            if (is_array($this->orderCommands) && !empty($param['order']) && !empty($this->orderCommands[$param['order']]))
            {
                $param_criteria['order'] = $this->orderCommands[$param['order']];
            }
            
            $criteria->setProperties($param_criteria); // order, offset
            $criteria->setProperty('limit', $limit);
            
            // $subcriteria = new TCriteria;
            if ($this->formFilters)
            {
                foreach ($this->formFilters as $filterKey => $filterField)
                {
                    $logic_operator = isset($this->logic_operators[$filterKey]) ? $this->logic_operators[$filterKey] : TExpression::AND_OPERATOR;
                    
                    if (TSession::getValue($this->activeRecord.'_filter_'.$filterKey))
                    {
                        // add the filter stored in the session to the criteria
                        $criteria->add(TSession::getValue($this->activeRecord.'_filter_'.$filterKey), $logic_operator);
                        // $subcriteria->add(TSession::getValue($this->activeRecord.'_filter_'.$filterKey), $logic_operator);
                    }
                }
                
                // if (!$subcriteria->isEmpty())
                // {
                //     $criteria->add($subcriteria);
                // }
            }
            
            // load the objects according to criteria
            $sql = $this->getSQL($criteria);
            $objects = $this->execSQL($sql);
            // $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count = $repository->count($criteria);
            
            if (isset($this->pageNavigation))
            {
                $this->pageNavigation->setCount($count); // count of records
                $this->pageNavigation->setProperties($param); // order, page
                $this->pageNavigation->setLimit($limit); // limit
            }
            
            if (is_callable($this->afterLoadCallback))
            {
                $information = ['count' => $count];
                call_user_func($this->afterLoadCallback, $this->datagrid, $information);
            }
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
            
            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function getSQL( $criteria )
    {
        $sql  = "
            WITH cte AS (
                SELECT 
                    id,
                    robo_inicio,
                    created_at,
                    tipo,
                    gale,
                    fator,
                    dice,
                    CAST(REPLACE(REGEXP_SUBSTR(REPLACE(REPLACE(configuracao, '.', ''), ',', '.'), 'Banca: ([0-9,]+)', 1), 'Banca: ', '') AS DOUBLE) AS banca_inicial,
                    valor_entrada,
                    valor_branco,
                    valor lucro_prejuizo,
                    SUM(valor) OVER (PARTITION BY robo_inicio ORDER BY id) AS saldo_atual
                FROM 
                    double_usuario_historico
                WHERE %WHERE%
            )
            SELECT *
            FROM cte
        ";
        
        $sql = $this->addCriteraSQL($criteria, $sql);
        return $sql;
        // // concatenate the criteria (WHERE)
        // if ($criteria)
        // {
        //     $expression = $criteria->dump(false);
        //     if ($expression)
        //     {
        //         $sql = str_replace(
        //             '%WHERE%', 
        //             $expression,
        //             $sql
        //         );
        //     }
            
        //     // get the criteria properties
        //     $order     = $criteria->getProperty('order');
        //     $group     = $criteria->getProperty('group');
        //     $limit     = (int) $criteria->getProperty('limit');
        //     $offset    = (int) $criteria->getProperty('offset');
        //     $direction = in_array($criteria->getProperty('direction'), array('asc', 'desc')) ? $criteria->getProperty('direction') : '';
            
        //     if ($group)
        //     {
        //         $sql .= ' GROUP BY ' . (is_array($group) ? implode(',', $group) : $group);
        //     }
        //     if ($order)
        //     {
        //         $sql .= ' ORDER BY ' . $order . ' ' . $direction;
        //     }
        //     if ($limit)
        //     {
        //         $sql .= ' LIMIT ' . $limit;
        //     }
        //     if ($offset)
        //     {
        //         $sql .= ' OFFSET ' . $offset;
        //     }
        // }
        // // return the SQL statement
        // return $sql;
    }

    public function addCriteraSQL($criteria, $sql)
    {
        // concatenate the criteria (WHERE)
        if ($criteria)
        {
            $expression = $criteria->dump(false);
            if ($expression)
            {
                $sql = str_replace(
                    '%WHERE%', 
                    $expression,
                    $sql
                );
            }
            
            // get the criteria properties
            $order     = $criteria->getProperty('order');
            $group     = $criteria->getProperty('group');
            $limit     = (int) $criteria->getProperty('limit');
            $offset    = (int) $criteria->getProperty('offset');
            $direction = in_array($criteria->getProperty('direction'), array('asc', 'desc')) ? $criteria->getProperty('direction') : '';
            
            if ($group)
            {
                $sql .= ' GROUP BY ' . (is_array($group) ? implode(',', $group) : $group);
            }
            if ($order)
            {
                $sql .= ' ORDER BY ' . $order . ' ' . $direction;
            }
            if ($limit)
            {
                $sql .= ' LIMIT ' . $limit;
            }
            if ($offset)
            {
                $sql .= ' OFFSET ' . $offset;
            }
        }
        // return the SQL statement
        return $sql;
    }

    public function execSQL( $sql, $callObjectLoad = true )
    {
        if ($conn = TTransaction::get())
        {
            // register the operation in the LOG file
            TTransaction::log($sql);
            $dbinfo = TTransaction::getDatabaseInfo(); // get dbinfo
            // execute the query
            $result= $conn->query($sql);
            $results = array();
            
            $class = $this->activeRecord;
            $callback = array($class, 'load'); // bypass compiler
            
            // Discover if load() is overloaded
            $rm = new ReflectionMethod($class, $callback[1]);
            
            if ($result)
            {
                // iterate the results as objects
                while ($raw = $result-> fetchObject())
                {
                    $object = new $this->activeRecord;
                    if (method_exists($object, 'onAfterLoadCollection'))
                    {
                        $object->onAfterLoadCollection($raw);
                    }
                    $object->fromArray( (array) $raw);
                    
                    if ($callObjectLoad)
                    {
                        // reload the object because its load() method may be overloaded
                        if ($rm->getDeclaringClass()-> getName () !== 'Adianti\Database\TRecord')
                        {
                            $object->reload();
                        }
                    }
                    
                    if ( ($cache = $object->getCacheControl()) /*&& empty($this->columns)*/)
                    {
                        $pk = $object->getPrimaryKey();
                        $record_key = $class . '['. $object->$pk . ']';
                        if ($cache::setValue( $record_key, $object->toArray() ))
                        {
                            TTransaction::log($record_key . ' stored in cache');
                        }
                    }
                    
                    /*if (!empty($this->colTransformers))
                    {
                        foreach ($this->colTransformers as $transf_alias => $transf_callback)
                        {
                            if (isset($object->$transf_alias))
                            {
                                $object->$transf_alias = $transf_callback($object->$transf_alias);
                            }
                        }
                    }*/
                    
                    // store the object in the $results array
                    $results[] = $object;
                }
            }
            return $results;
        }
        else
        {
            // if there's no active transaction opened
            throw new Exception(AdiantiCoreTranslator::translate('No active transactions') . ': ' . __METHOD__ .' '. $this->getEntity());
        }
    }
}