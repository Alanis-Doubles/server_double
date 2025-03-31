<?php

use Adianti\Control\TAction;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardList;
use Adianti\Database\TDatabase;
use Adianti\Widget\Form\TCombo;
use Adianti\Database\TTransaction;
use Adianti\Widget\Util\TDropDown;

class TProfitHistoricoUsuario extends TStandardList
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

        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
            return DoubleUsuario::where('canal_id', '=', $canal->id)
                ->where('chat_id', '=', TSession::getValue('usercustomcode'))
                ->first();
        });

        if (!$usuario) {
            new TMessage('error', 'Usuário não encontrado.');
            return;
        }

        $usuario_id = $usuario->id;

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
        $criteria->add(
            new TFilter('banca', 'is not', null)
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
            ['name' => 'ticker', 'label' => 'Ativo', 'width' => '20%', 'align' => 'left', 'transformer' => Closure::fromCallable([$this, 'tickerTransformer'])],
            ['name' => 'tipo', 'label' => 'Tipo', 'width' => '5%', 'align' => 'center', 'transformer' => Closure::fromCallable([$this, 'tipoTransformer'])],
            ['name' => 'gale', 'label' => 'Gale', 'width' => '5%', 'align' => 'center'],
            ['name' => 'valor_entrada', 'label' => 'Valor entrada', 'width' => '10%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'moedaTransformer'])],
            ['name' => 'lucro_prejuizo', 'label' => 'Lucro/Prejuízo', 'width' => '20%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'lucroPrejuizoTransformer'])],
            ['name' => 'banca', 'label' => 'Saldo atual', 'width' => '15%', 'align' => 'right', 'transformer' => Closure::fromCallable([$this, 'saldoAtualTransformer'])],
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
        $container->add(new TXMLBreadCrumb('menu-top.xml', get_class($this)));
        $container->add($panel);
        
        parent::add($container);
    }

    public function tickerTransformer($value, $object)
    {
        $ticker = $object->ticker;
        $moedas = [
            "SOLUSD-AV" => "Solana",
            "ETHUSD-AV" => "Ethereum",
            "EURJPY-AV" => "EUR/JPY",
            "TRON-AV" => "Tron",
            "ADAUSD-AV" => "Cardano",
            "USDCHF-AV" => "USD/CHF",
            "GBPJPY-AV" => "GBP/JPY",
            "AMZN" => "Amazon",
            "USDBRL-AV" => "USD/BRL",
            "UKOUSD-AV" => "Crude Oil Brent",
            "AUDCAD-AV" => "AUD/CAD",
            "GOOGLE-AV" => "Google",
            "META-AV" => "Meta",
            "USNDAQ 100 (NDX) Spot Index" => "US 100",
            "PENUSD-AV" => "PEN/USD",
            "USDCOP-AV" => "USD/COP",
            "USDJPY-AV" => "USD/JPY",
            "USDXOF-AV" => "USD/XOF",
            "EURGBP-AV" => "EUR/GBP",
            "SHIBUSDT-AV" => "Shiba Inu",
            "GBPUSD-AV" => "GBP/USD",
            "EURRUB-AV" => "EUR/USD",
            "USDSGD-AV" => "USD/SGD",
            "USDMXN-AV" => "USD/MXN",
            "EURUSD-AV" => "EUR/RUB",
            "AAPL" => "Apple",
            "DOGEUSD-AV" => "Doge",
            "NZDUSD-AV" => "NZD/USD",
            "S&P 500" => "US 500",
            "USDINR-AV" => "USD/INR",
            "BTCUSD-AV" => "Bitcoin",
            "US30-AV" => "US 30",
            "TESLA" => "TESLA",
        ];
        return $moedas[$ticker] ?? $ticker;
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
                $total_entrada = number_format($object->valor_entrada, 2, ',', '.');
                
                return "<span class='cor_verde'>{$total_entrada} * {$object->fator}%</span> = <span class='" . ($object->lucro_prejuizo > 0 ? 'cor_verde' : 'cor_vermelho') . "'>{$lucro_prejuizo}</span>";
            } else {
                return "<span class='cor_vermelho'>{$lucro_prejuizo}</span>";
            }
        } else {
            return '';
        }
    }

    public function saldoAtualTransformer($value, $object)
    {
        if ($value) {
            $saldo_atual = $object->banca;
            $formatado = 'R$ ' . number_format($saldo_atual, 2, ',', '.');
            if ($saldo_atual > 0) {
                return "{$formatado}";
            } else {
                return "<span style='color:red'>{$formatado}</span>";
            }
        } else {
            return 'R$ 0.00';
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
        $data = (object) $param; 
        
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
        
        if (is_callable($this->afterSearchCallback))
        {
            call_user_func($this->afterSearchCallback, $this->datagrid, $data);
        }
        
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
                    }
                }
            }
            
            // load the objects according to criteria
            $sql = $this->getSQL($criteria);
            $objects = $this->execSQL($sql);
            
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
                    ticker,
                    banca,
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
    }

    public function addCriteraSQL($criteria, $sql)
    {
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