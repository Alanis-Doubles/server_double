<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Form\TArrowStep;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;
use AdminLte\Widget\Container\TLTESmallBox;

class TProfitRanking
{
    use TUIBuilderTrait;

    public $datagrid;
    public $panel;

    public function __construct()
    {
        $dataGrid = new stdClass;
        $dataGrid->name = 'dataraking';
        $dataGrid->pagenavigator = false;
        $dataGrid->title = '<i class="fas fa-trophy green"></i>  Ranking das Estratégias ';
        $dataGrid->columns = [
            ['name' => 'nome', 'label' => 'Nome', 'width' => '20%', 'align' => 'left'],
            ['name' => 'regra', 'label' => 'Regra', 'width' => '35%', 'align' => 'left', 'transformer' => Closure::fromCallable(['TProfitDashboard', 'transform_regra'])],
            ['name' => 'resultado', 'label' => 'Resultado', 'width' => '10%', 'align' => 'center', 'transformer' => Closure::fromCallable(['TProfitDashboard', 'transform_resultado'])],
            ['name' => 'win', 'label' => 'Win', 'width' => '5%', 'align' => 'center'],
            ['name' => 'loss', 'label' => 'Loss', 'width' => '5%', 'align' => 'center'],
            ['name' => 'percentual', 'label' => '%', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_0', 'label' => 'G0', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_1', 'label' => 'G1', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_2', 'label' => 'G2', 'width' => '5%', 'align' => 'center'],
            ['name' => 'gale_3', 'label' => 'G3', 'width' => '5%', 'align' => 'center'],
        ];

        $this->panel = $this->makeTDataGrid($dataGrid);

        $this->datagrid = $this->getWidget('dataraking');
    }
}

class TProfitDashboard extends TPage
{
    use TUIBuilderTrait;

    private $form;
    private $filterRanking;
    private $datagrid;
    private $botoes;

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_TProfitDashboard');
        $this->form->setFormTitle('Filtros');
        $this->form->addExpandButton('');

        $this->form->addFields(
            [$this->makeTHidden(['name' => 'usuarios_canal', 'value' => 'N'])],
            [$this->makeTHidden(['name' => 'data_ranking', 'value' => date('Y-m-d')])],
        );

        // $criteria = new TCriteria;
        // $criteria->add(
        //     new TFilter(
        //         '(SELECT p.tipo_sinais FROM double_plataforma p WHERE p.id = double_canal.plataforma_id)',
        //         'IN',
        //         ['NAO_GERA', 'GERA', 'PROPAGA_VALIDA_SINAL']
        //     )
        // );

        // $criteria->add(new TFilter('ativo', '=', 'Y'));

        // $this->form->addFields(
        //     [$label = $this->makeTLabel(['value' => 'Canal'])],
        //     [$this->makeTDBCombo(
        //         [
        //             'name' => 'canal_id',
        //             'label' => $label,
        //             'database' => 'double',
        //             'required' => True,
        //             'model' => 'DoubleCanal',
        //             'key' => 'id',
        //             'display' => '[{plataforma->idioma}] {plataforma->nome} - {nome}',
        //             'defaultOption' => false,
        //             'width' => '100%',
        //             'criteria' => $criteria
        //         ]
        //     )],
        // );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Início'])],
            [$this->makeTDate(['name' => 'data_inicio', 'label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function ($object) {
                $object->setValue(date('01/m/Y'));
            })],
            [$label = $this->makeTLabel(['value' => 'Fim'])],
            [$this->makeTDate(['name' => 'data_fim', 'label' => $label, 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'], function ($object) {
                $object->setValue(date('t/m/Y'));
            })],
        );

        $btn = $this->form->addAction('Atualizar', new TAction([$this, 'onSearch'], $param), 'fa:sync');
        $btn->class = 'btn btn-sm btn-primary';

        $html1 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html1->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('totalUsuarios', 'Total de Usuários', 'users', 'green', 0),
                'indicator2' => TUtils::renderInfoBox('novosUsuarios', 'Novos de Usuários', 'user-plus', 'orange', 0),
                'indicator3' => TUtils::renderInfoBox('totalPlanosAssinados', 'Total Planos Assinado', 'dollar-sign', 'green', 0),
            ]
        );

        $html2 = new THtmlRenderer('app/resources/double/dashboard.html');
        $html2->enableSection(
            'main',
            [
                'indicator1' => TUtils::renderInfoBox('usuariosAtivos', 'Usuários Jogando', 'trophy', 'red', 0),
                'indicator2' => TUtils::renderInfoBox('totalTestesIniciados', 'Total Testes Iniciados', 'gamepad', 'aqua', 0),
                'indicator3' => TUtils::renderInfoBox('valorTotalAssinaturas', 'Valor Total Assinaturas', 'dollar-sign', 'orange', ' R$ 0,00'),
            ]
        );

        $session = TSession::getValue('form_TProfitDashboard_filter_data');

        // $panel = self::createRanking();
        $ranking = new TProfitRanking();
        $panel = $ranking->panel;
        $this->datagrid = $ranking->datagrid;

        $this->filterRanking = new TForm('form_filer_ranking');
        $this->filterRanking->style = 'float:left;display:flex';
        $filterDataRanking = $this->makeTDate(['name' => 'data_ranking', 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd', 'change_action' => [$this, 'onChangeRanking']], function ($object) use ($session) {
            $object->setValue(date('d/m/Y'));
        });
        $this->filterRanking->add($filterDataRanking, true);

        $panel->addHeaderWidget($this->filterRanking);

        // $columns = $this->datagrid->getColumns();
        // $columns[3]->enableTotal('sum', null, 0, '.', ',');
        // $columns[4]->enableTotal('sum', null, 0, '.', ',');

        $barResult = new THtmlRenderer('app/resources/google_column_chart.html');
        $panelResultado = new TPanelGroup('Resultado dos últimos 7 dias');
        $panelResultado->add($barResult);
        $data = array();
        $data[] = ['Day', 'Value 1', 'Value 2', 'Value 3'];
        $data[] = ['Day 1',   100,       120,       140];
        $data[] = ['Day 2',   120,       140,       160];
        $data[] = ['Day 3',   140,       160,       180];

        # PS: If you use values from database ($row['total'), 
        # cast to float. Ex: (float) $row['total']

        // replace the main section variables
        $barResult->enableSection('main', array(
            'data'   => json_encode($data),
            'width'  => '100%',
            'height'  => '300px',
            'title'  => '',
            'ytitle' => 'Accesses',
            'xtitle' => 'Day',
            'uniqid' => uniqid()
        ));

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TUtils::createXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($html1);
        $container->add($html2);
        // $container->add(new TElement('br'));
        // $container->add($panelResultado);
        $container->add(new TElement('br'));


        $labelAtivo = new TLabel('Gráfico do Ativo: <b>Aguardando ativo...</b>');
        $labelAtivo->setId('labelAtivo');
        // $labelAtivo->setUseHtml(true);

        $step = new TArrowStep('step');
        $step->addItem('Operação', 99, '#3498db');
        $step->addItem('Entrada' , 0 , '#27ae60');
        $step->setCurrentKey(99);
        $step->setHeight(40);

        $apex = new TElement('div');
        $apex->id = 'candlestick_chart';
        $apex->style = 'width: 100%; height: 300px;';

        $panelGrafio = new TPanelGroup();
        $panelGrafio->add($labelAtivo);
        $panelGrafio->add($step);
        $panelGrafio->add(new TElement('br'));
        $panelGrafio->add($apex);
        $container->add($panelGrafio);

        $container->add(new TElement('br'));
        $container->add($panel);

        parent::add($container);

        $this->form->setData($session);
        $this->filterRanking->setData($session);

        // TScript::create($this->getJavaScript());
        TScript::create($this->getProfitJavaScript());
    }

    private function getJavaScript()
    {
        $host = DoubleConfiguracao::getConfiguracao('servidor_ws');
        
        return <<<JAVASCRIPT
            function atualiza_contadores() {
                $.get("engine.php?class=TProfitDashboard&method=doConsultar&static=1", function(data) {
                    const options = { 
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                        style: "currency",
                        currency: "BRL"
                    };

                    const dados = JSON.parse(data);

                    document.querySelector("#totalUsuarios").textContent = dados["totalUsuarios"];
                    document.querySelector("#novosUsuarios").textContent = dados["novosUsuarios"];
                    document.querySelector("#totalPlanosAssinados").textContent = dados["totalPlanosAssinados"];
                    document.querySelector("#usuariosAtivos").textContent = dados["usuariosAtivos"];
                    document.querySelector("#totalTestesIniciados").textContent = dados["totalTestesIniciados"];
                    document.querySelector("#valorTotalAssinaturas").textContent = Number(dados["valorTotalAssinaturas"]).toLocaleString("pt-BR", options);;
                });
            }

            function atualiza_ranking() {
                
                $.get("engine.php?class=TProfitDashboard&method=doConsultarRanking&static=1", function(data) {
                    
                    $("#dataraking tbody").remove();
                    $("#dataraking").append(data);
                });
            }

            atualiza_contadores();
            atualiza_ranking();

            setInterval( atualiza_contadores, 5000);
            setInterval( atualiza_ranking, 7000 );

            let data = [];
            let ativoAtual = "";
            let entradaAtivo = null;

            let options = {
                series: [{
                    data: data
                }],
                chart: {
                    type: 'candlestick',
                    height: 350,
                    animations: {
                        enabled: false
                    }
                },
                xaxis: {
                    type: 'string'
                },
                yaxis: {
                    tooltip: {
                        enabled: true
                    }
                },
                annotations: {
                    yaxis: []
                }
            };

            let chart = new ApexCharts(document.querySelector('#candlestick_chart') ?? '', options);
            chart.render();

            let socket = new WebSocket(`{$host}`);

            socket.onopen = function(event) {
                console.log('Conexão WebSocket estabelecida.');
            };

            socket.onmessage = function (event) {
                let data = JSON.parse(event.data);

                if (data.type === "candle_update") {
                    atualizarNomeAtivo(data.moeda);
                    atualizarGrafico(data);
                    tarrowstep_set_current('step', data.gale);
                } else if (data.type === "win") {
                    console.log("win:", event.data);
                    document.getElementById('step_' + data.gale).innerHTML = "✅ " + document.getElementById('step_' + data.gale).innerHTML;
                } else if (data.type === "loss") {
                    console.log("loss:", event.data);
                    document.getElementById('step_' + data.gale).innerHTML = "❌ " + document.getElementById('step_' + data.gale).innerHTML;
                } else if (data.type === "indisponivel") {
                    console.log("indisponivel:", event.data);
                    document.getElementById('step_' + data.gale).innerHTML = "🚫 " + document.getElementById('step_' + data.gale).innerHTML;
                } else if (data.type === "entrada") {
                    console.log("entrada:", event.data);
                    document.getElementById('step_99').innerHTML = "Operção";
                    document.getElementById('step_0').innerHTML = "Entrada";
                    document.getElementById('step_1').innerHTML = "Gale 1";
                    document.getElementById('step_2').innerHTML = "Gale 2";
                    document.getElementById('step_3').innerHTML = "Gale 3";
                }
            };

            function atualizarNomeAtivo(nome) {
                document.getElementById('labelAtivo').innerHTML = "Gráfico do Ativo: <b>" + nome + "</b>";
                const tema = document.documentElement.getAttribute("data-bs-theme");
                atualizarTemaGrafico(tema);
            }

            function atualizarGrafico(message) {
                document.getElementById('step_99').innerHTML = "Operção - " + message.acao;
                data = message.historico.map(vela => ({
                    x: vela.datahora, 
                    y: [vela.open, vela.high, vela.low, vela.close]
                }));

                entradaUsuario = null; 
                chart.updateOptions({ annotations: { yaxis: [] } }); 

                chart.updateSeries([{ data }]);

                ultimoClose = data[data.length - 1].y[3];
                ultinoOpen = data[data.length - 1].y[0];

                _updateOptions = [];
                
                if (message.entrada) {
                    _updateOptions.push({
                        y: ultimoClose,
                        borderColor: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000',
                        label: {
                            text: ultimoClose,
                            style: { color: '#fff', background: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000' }
                        }
                    });
                }
                
                chart.updateOptions({
                    annotations: {
                        yaxis: _updateOptions
                    }
                });
            }
JAVASCRIPT;
    }

    private function getProfitJavaScript()
    {
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        // $usuario = TUtils::openFakeConnection("double", function () use ($canal) {
        //     return DoubleUsuario::where('canal_id', '=', $canal->id)
        //         ->where('chat_id', '=', TSession::getValue('usercustomcode'))
        //         ->first();
        // });

        $host = DoubleConfiguracao::getConfiguracao('servidor_ws');

        return <<<JAVASCRIPT
            console.log('Iniciando script...');
            atualizando = false;
            ordem_realizada = 0;

           options = {
                series: [{
                    data: []
                }],
                chart: {
                    type: 'candlestick',
                    height: 300,
                    animations: {
                        enabled: false
                    },
                    zoom: { enabled: false },  
                },
                xaxis: {
                    type: 'string'
                },
                yaxis: {
                    tooltip: {
                        enabled: true
                    }
                },
                annotations: {
                    yaxis: []
                }
            };

            chart = new ApexCharts(document.querySelector('#candlestick_chart') ?? '', options);
            chart.render();

            socket = new WebSocket(`{$host}`);

            socket.onopen = function(event) {
                console.log('Conexão WebSocket estabelecida.');
            };

            function atualiza_contadores() {
                $.get("engine.php?class=TProfitDashboard&method=doConsultar&static=1", function(data) {
                    const options = { 
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                        style: "currency",
                        currency: "BRL"
                    };

                    const dados = JSON.parse(data);

                    document.querySelector("#totalUsuarios").textContent = dados["totalUsuarios"];
                    document.querySelector("#novosUsuarios").textContent = dados["novosUsuarios"];
                    document.querySelector("#totalPlanosAssinados").textContent = dados["totalPlanosAssinados"];
                    document.querySelector("#usuariosAtivos").textContent = dados["usuariosAtivos"];
                    document.querySelector("#totalTestesIniciados").textContent = dados["totalTestesIniciados"];
                    document.querySelector("#valorTotalAssinaturas").textContent = Number(dados["valorTotalAssinaturas"]).toLocaleString("pt-BR", options);;
                });
            }

            socket.onmessage = function (event) {
                let data = JSON.parse(event.data);
                console.log("type:", data.type);

                if (data.type === "candle_update") {
                    atualizarNomeAtivo(data.moeda, data.horaEntrada);

                    atualizarGrafico(data);
                    if (atualizando) {
                        tarrowstep_set_current('step', data.gale);
                    }
                } else if (data.type === 'ordem_realizada') {
                    console.log("ordem_realizada:", event.data);
                    ordem_realizada = data.entrada_usuario;
                    tarrowstep_set_current('step', data.gale);
                } else if (data.type === "win") {
                    atualizando = false;
                    console.log("win:", event.data);
                    
                    __adianti_show_toast('success', 'Win', 'top right', 'far:check-circle');
                    addStep("WIN ", "#27ae60");
                } else if (data.type === "loss") {
                    atualizando = false;
                    console.log("loss:", event.data);
                    
                    __adianti_show_toast('error', 'Loss', 'top right', 'far:times-circle');
                    addStep("LOSS ", "#e74c3c");
                } else if (data.type === "gale") {
                    console.log("gale:", event.data);
                    addStep("Gale " + data.gale, getGradientColor(data.gale, {$canal->protecoes}))
                } else if (data.type === "indisponivel") {
                    console.log("indisponivel:", event.data);
                    document.getElementById('step_' + data.gale).innerHTML = "🚫 " + document.getElementById('step_' + data.gale).innerHTML;
                } else if (data.type === "entrada") {
                    ordem_realizada = 0;
                    console.log("entrada:", event.data);
                    atualizarNomeAtivo(data.moeda, data.hora);
                    tarrowstep_set_current('step', 99);
                    clearSteps();
                    document.getElementById('step_0').innerHTML = "Entrada" ;
                    atualizando = true;
                }
            };

            function addStep(stepName, stepColor) {
                let stepsContainer = document.querySelector(".arrow_steps");
                if (!stepsContainer) return;

                let lastStep = stepsContainer.querySelector(".step:last-child");
                let lastKey = lastStep ? parseInt(lastStep.getAttribute("data-key")) : -1;
                let newKey = lastKey + 1;

                let newStep = document.createElement("div");
                newStep.classList.add("step");
                newStep.setAttribute("data-key", newKey);

                let newInput = document.createElement("input");
                newInput.type = "hidden";
                newInput.id = "tarrowstep_1855478791_" + newKey;
                newInput.value = newKey;

                let newSpan = document.createElement("span");
                newSpan.id = "step_" + newKey;
                newSpan.textContent = stepName;

                newStep.appendChild(newInput);
                newStep.appendChild(newSpan);

                stepsContainer.appendChild(newStep);

                addStepStyle(newKey, stepColor);
                
                tarrowstep_set_current('step', newKey);
            }

            function addStepStyle(stepKey, stepColor) {
                let styleSheet = document.querySelector("#dynamicStyles");
                
                if (!styleSheet) {
                    styleSheet = document.createElement("style");
                    styleSheet.id = "dynamicStyles";
                    document.head.appendChild(styleSheet);
                }

                styleSheet.innerHTML += 
                    '.arrow_steps_step .step.current[data-key="' + stepKey + '"], ' +
                    '.arrow_steps_step .step.preview-current[data-key="' + stepKey + '"] { ' +
                    '    background-color: ' + stepColor + '; ' +
                    '} ' +
                    '.arrow_steps_step .step.current[data-key="' + stepKey + '"]:after, ' +
                    '.arrow_steps_step .step.preview-current[data-key="' + stepKey + '"]:after { ' +
                    '    border-left-color: ' + stepColor + '; ' +
                    '} ' ;
            }

            function getGradientColor(step, totalSteps = 10) {
                let startColor = [241, 196, 15]; 
                let endColor = [230, 126, 34];    
                
                
                let ratio = Math.min(step / totalSteps, 1); 

                
                let r = Math.round(startColor[0] + ratio * (endColor[0] - startColor[0]));
                let g = Math.round(startColor[1] + ratio * (endColor[1] - startColor[1]));
                let b = Math.round(startColor[2] + ratio * (endColor[2] - startColor[2]));

                const cor = "#" + r.toString(16).padStart(2, "0") 
                                + g.toString(16).padStart(2, "0") 
                                + b.toString(16).padStart(2, "0");

                return cor;
            }

            function clearSteps() {
                let stepsContainer = document.querySelector(".arrow_steps");
                if (!stepsContainer) return;

                let steps = stepsContainer.querySelectorAll(".step");

                steps.forEach((step, index) => {
                    if (index > 1) {
                        step.remove();
                    }
                });
            }

            function atualizarNomeAtivo(nome, hora) {
                document.getElementById('labelAtivo').innerHTML = "Gráfico do Ativo: <b>" + nome + "</b> - Entrada às " + hora;
            }

            function atualizarGrafico(message) {
                document.getElementById('step_99').innerHTML = message.acao;
                data = message.historico.map(vela => ({
                    x: vela.datahora, 
                    y: [vela.open, vela.high, vela.low, vela.close]
                }));

                entradaUsuario = null; 
                chart.updateOptions({ annotations: { yaxis: [] } }); 

                chart.updateSeries([{ data }]);

                ultimoClose = data[data.length - 1].y[3];
                ultinoOpen = data[data.length - 1].y[0];

                let _updateOptions = [];
                let _points = [];
                let galeMax = message.gale; 
                let labels = ["Entrada", "Gale 1", "Gale 2", "Gale 3"];
                let cores = ["#27ae60", "#f1c40f", "#e67e22", "#e74c3c"];
                
                if (message.entrada) {
                    _updateOptions.push({
                        y: ultimoClose,
                        borderColor: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000',
                        label: {
                            text: ultimoClose,
                            position: 'top', 
                            offsetX: 150,
                            style: { color: '#fff', background: ultinoOpen < ultimoClose ? '#00B746' : '#FF0000' }
                        }
                    });

                    if (ordem_realizada > 0) {
                        _updateOptions.push({
                            y: ordem_realizada,
                            borderColor: '#3498db',
                            label: {
                                text: 'Entrada realizada',
                                position: 'top', 
                                offsetX: 50,
                                style: { color: '#fff', background: '#3498db' }
                            }
                        });
                    }

                    let maxValue = Math.max(...data.map(d => d.y[1])); 
                    for (let i = 0; i < message.historico.length; i++) {
                        let vela = message.historico[i];
                        if (vela.gale != 99) {
                            const corGale = getGradientColor(vela.gale, {$canal->protecoes});
                            _points.push({
                                x: vela.datahora, 
                                y: maxValue, 
                                yAxisIndex: 0, 
                                marker: { size: 0 }, 
                                label: {
                                    text: labels[vela.gale],
                                    position: "top", 
                                    offsetY: -10, 
                                    style: {
                                        color: '#fff',
                                        background: (vela.gale === 0 ? '#28a745' : corGale),  
                                        fontSize: '12px',
                                        fontWeight: 'bold'
                                    }
                                }
                            });
                        }
                    }
                }
                
                chart.updateOptions({
                    annotations: {
                        yaxis: _updateOptions,
                        points: _points
                    }
                });
            }

            function atualizarTemaGrafico(tema) {
                const novasCores = tema === "dark" 
                    ? { text: "#fff", linha: "#777" } 
                    : { text: "#333", linha: "#ccc" }; 

                chart.updateOptions({
                    xaxis: {
                        labels: { style: { colors: novasCores.text } },
                        axisBorder: { color: novasCores.linha },
                        axisTicks: { color: novasCores.linha }
                    },
                    yaxis: {
                        labels: { style: { colors: novasCores.text } },
                        axisBorder: { color: novasCores.linha },
                        axisTicks: { color: novasCores.linha }
                    }
                });
            }

            originalToggleGlobalTheme = Template.toggleGlobalTheme;

            Template.toggleGlobalTheme = function (el) {
                originalToggleGlobalTheme.call(this, el);

                const tema = document.documentElement.getAttribute("data-bs-theme");

                atualizarTemaGrafico(tema);
            };

            atualiza_contadores();

    JAVASCRIPT;
    }

    public function onReload($param)
    {
        $a = '1';
    }

    public function onAtivarInativarEstrategia($param)
    {
        TUtils::openConnection('double', function () use ($param) {
            $estrategia = new DoubleEstrategia($param['estrategia_id'], false);
            if ($estrategia) {
                $estrategia->ativo = $estrategia->ativo == 'Y' ? 'N' : 'Y';
                $estrategia->save();
            }
        });
    }

    public static function onChangeRanking($param)
    {
        $session = TSession::getValue('form_TProfitDashboard_filter_data');
        if (!$session) {
            $session = new stdClass;
            $session->canal_id = '';
            $session->usuarios_canal = '';
            $session->data_inicio = date('Y-m-0');
            $session->data_fim = date('Y-m-t');
        }

        $session->data_ranking = TDate::convertToMask($param['data_ranking'], 'dd/mm/yyyy', 'yyyy-mm-dd');
        TSession::setValue('form_TProfitDashboard_filter_data', $session);
    }

    public static function transform_resultado($value, $object, $row, $cell)
    {
        if ($value <> '') {
            return self::addOption($value, $object->plataforma_id);
        }
    }

    public static function transform_regra($value, $object, $row, $cell)
    {
        if ($value <> '') {
            $opcoes = explode(' - ', $value);

            $div = new TElement('div');
            $div->class = 'class="flex flex-row space-x-1"';

            foreach ($opcoes as $key => $opcao) {
                $div->add(self::addOption($opcao, $object->plataforma_id));
            }

            return $div;
        }
    }

    public static function addOption($option, $plataforma_id)
    {
        $bet_name = TUtils::openFakeConnection('double', function () use ($plataforma_id) {
            $obj = new DoublePlataforma($plataforma_id, false);
            if ($obj)
                return $obj->nome;
            else
                return '--';
        });

        $path = 'app/images/regras/';
        $path_bet = "app/images/regras/{$bet_name}/";

        if (substr($bet_name, 0, 5) == "Bacbo") {
            $imageMap = [
                'red'   => ['image' => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'), 'title' => 'Banker'],
                'black' => ['image' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'), 'title' => 'Player'],
                'white' => ['image' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'), 'title' => 'Empate'],
                'break' => ['image' => (file_exists($path_bet . 'break.png') ? $path_bet . 'break.png' : $path . 'break.png'), 'title' => 'Ignorar entrada'],
            ];
        } else {
            $imageMap = [
                'red'   => ['image' => (file_exists($path_bet . 'red.png') ? $path_bet . 'red.png' : $path . 'red.png'), 'title' => ''],
                'black' => ['image' => (file_exists($path_bet . 'black.png') ? $path_bet . 'black.png' : $path . 'black.png'), 'title' => ''],
                'white' => ['image' => (file_exists($path_bet . 'white.png') ? $path_bet . 'white.png' : $path . 'white.png'), 'title' => ''],
                'other' => ['image' => (file_exists($path_bet . 'other.png') ? $path_bet . 'other.png' : $path . 'other.png'), 'title' => 'Qualquer cor'],
                'break' => ['image' => (file_exists($path_bet . 'break.png') ? $path_bet . 'break.png' : $path . 'break.png'), 'title' => 'Ignorar entrada'],
                '1'     => ['image' => (file_exists($path_bet . '1.png') ? $path_bet . '1.png' : $path . '1.png'), 'title' => ''],
                '2'     => ['image' => (file_exists($path_bet . '2.png') ? $path_bet . '2.png' : $path . '2.png'), 'title' => ''],
                '3'     => ['image' => (file_exists($path_bet . '3.png') ? $path_bet . '3.png' : $path . '3.png'), 'title' => ''],
                '4'     => ['image' => (file_exists($path_bet . '4.png') ? $path_bet . '4.png' : $path . '4.png'), 'title' => ''],
                '5'     => ['image' => (file_exists($path_bet . '5.png') ? $path_bet . '5.png' : $path . '5.png'), 'title' => ''],
                '6'     => ['image' => (file_exists($path_bet . '6.png') ? $path_bet . '6.png' : $path . '6.png'), 'title' => ''],
                '7'     => ['image' => (file_exists($path_bet . '7.png') ? $path_bet . '7.png' : $path . '7.png'), 'title' => ''],
                '8'     => ['image' => (file_exists($path_bet . '8.png') ? $path_bet . '8.png' : $path . '8.png'), 'title' => ''],
                '9'     => ['image' => (file_exists($path_bet . '9.png') ? $path_bet . '9.png' : $path . '9.png'), 'title' => ''],
                '10'    => ['image' => (file_exists($path_bet . '10.png') ? $path_bet . '10.png' : $path . '10.png'), 'title' => ''],
                '11'    => ['image' => (file_exists($path_bet . '11.png') ? $path_bet . '11.png' : $path . '11.png'), 'title' => ''],
                '12'    => ['image' => (file_exists($path_bet . '12.png') ? $path_bet . '12.png' : $path . '12.png'), 'title' => ''],
                '13'    => ['image' => (file_exists($path_bet . '13.png') ? $path_bet . '13.png' : $path . '13.png'), 'title' => ''],
                '14'    => ['image' => (file_exists($path_bet . '14.png') ? $path_bet . '14.png' : $path . '14.png'), 'title' => ''],
                'ia'    => ['image' => (file_exists($path_bet . 'ia.png') ? $path_bet . '14.png' : $path . 'ia.png'), 'title' => ''],
            ];
        }

        if (isset($imageMap[$option])) {
            $imgTag = new TElement('img');
            $imgTag->src = $imageMap[$option]['image'];
            $imgTag->title = $imageMap[$option]['title'];
            $imgTag->style = 'width: 30px; height: 30px; margin: 2px;';

            return $imgTag;
        }
    }

    public function onSearch($param)
    {
        $object = $this->form->getData();
        TSession::setValue('form_TProfitDashboard_filter_data', $object);

        $filter = $this->filterRanking->getData();
    }

    public static function doConsultarRanking($paraam)
    {
        $raking = new TProfitRanking;
        $datagrid = $raking->datagrid;

        $session = TSession::getValue('form_TProfitDashboard_filter_data');
        if ($session and $session->canal_id) {
            try {
                $lista = TUtils::openFakeConnection('double',  function () use ($session) {
                    $filtro1 = 'dh.canal_id = ' . $session->{'canal_id'};
                    $filtro2 = 'c.id = ' . $session->{'canal_id'};

                    if (!isset($session->data_ranking))
                        $session->data_ranking = date('Y-m-d');
                    if ((isset($session->data_ranking) and !$session->data_ranking))
                        $session->data_ranking = date('Y-m-d');
                    $data = $session->data_ranking;

                    $query = "SELECT tipo,
                                    plataforma_id,
                                    canal_id,
                                    nome,
                                    regra,
                                    resultado,
                                    ativo,
                                    win,
                                    loss,
                                    percentual,
                                    gale_0,
                                    gale_1,
                                    gale_2,
                                    gale_3,
                                    gale_4
                            FROM ( SELECT e.tipo,
                                        c.plataforma_id,
                                        e.canal_id,
                                        e.nome,
                                        e.regra,
                                        e.resultado,
                                        e.ativo,
                                        sum(win) win,
                                        sum(loss) loss,
                                        ROUND((sum(win)/(sum(win)+sum(loss)))*100, 2) percentual,
                                        sum(gale_0) gale_0,
                                        sum(gale_1) gale_1,
                                        sum(gale_2) gale_2,
                                        sum(gale_3) gale_3,
                                        sum(gale_4) gale_4
                                    FROM double_estrategia e
                                    JOIN double_canal c ON c.id = e.canal_id
                                    LEFT JOIN ( SELECT estrategia_id,
                                                        win,
                                                        loss,
                                                        if(gale = 0, 1, 0) gale_0,
                                                        if(gale = 1, 1, 0) gale_1,
                                                        if(gale = 2, 1, 0) gale_2,
                                                        if(gale = 3, 1, 0) gale_3,
                                                        if(gale = 4, 1, 0) gale_4
                                                    FROM ( SELECT if(dh.tipo = 'WIN', 1, 0) win,
                                                                        if(dh.tipo = 'LOSS', 1, 0) loss,
                                                                    dh.estrategia_id,
                                                                    (SELECT COUNT(1)
                                                                        FROM double_historico h
                                                                        JOIN double_canal c ON c.id = h.canal_id
                                                                        WHERE h.canal_id = dh.canal_id
                                                                        AND h.tipo = 'GALE'
                                                                        AND h.entrada_id = dh.entrada_id) gale
                                                                        --  AND h.estrategia_id = dh.estrategia_id
                                                                        --  AND h.id between dh.id - c.protecoes - 1 AND dh.id) gale
                                                                FROM double_historico dh
                                                                WHERE $filtro1
                                                                AND dh.tipo IN ('WIN', 'LOSS')
                                                                AND dh.entrada_id IS NOT null
                                                                AND DATE(CONVERT_TZ(dh.created_at, '+00:00', '-03:00')) = '$data'
                                                        ) a
                                                ) b ON b.estrategia_id = e.id 
                                    WHERE $filtro2
                                    and e.usuario_id is NULL
                                    and e.deleted_at is NULL
                                    and e.resultado <> 'break'
                                    and e.ativo = 'Y'
                                    GROUP BY e.tipo, c.plataforma_id, e.canal_id, e.nome, e.regra, e.resultado, e.ativo, e.ordem 
                                    ORDER BY 10 DESC, 8 DESC, 9 ASC, 11 DESC, 12 DESC, 14 DESC, 15 DESC, e.ordem ASC
                                ) c
                            ";

                    $conn = TTransaction::get();
                    $list = TDatabase::getData(
                        $conn,
                        $query
                    );

                    return $list;
                });

                foreach ($lista as $key => $value) {
                    $datagrid->addItem((object) $value);
                }
                echo $datagrid->getBody();
            } catch (\Throwable $e) {
                //  DoubleErros::registrar(1, 'TProfitDashboard', doConsultarRanking, e->getMessage());
            }
        } else {
            echo "";
        }
    }

    public static function doConsultar($param)
    {
        
        $canal = TUtils::openFakeConnection("double", function () {
            return DoubleCanal::where('nome', '=', 'Playbroker')
                ->first();
        });

        $object = TSession::getValue('form_TProfitDashboard_filter_data');
        if (!$object) {
            $object = new stdClass;
            $canal->id = '';
            $object->usuarios_canal = '';
            $object->data_inicio = date('Y-m-0');
            $object->data_fim = date('Y-m-t');
            $object->data_ranking = date('Y-m-d');
        }

        if ($object->data_inicio)
            $object->data_inicio = TDate::convertToMask($object->data_inicio, 'dd/mm/yyyy', 'yyyy-mm-dd');
        if ($object->data_fim)
            $object->data_fim = TDate::convertToMask($object->data_fim, 'dd/mm/yyyy', 'yyyy-mm-dd');

        ////  DoubleErros::registrar(3, 'dashboard', 'dash', json_encode($object));
        $dados = TUtils::openFakeConnection('double', function () use ($object, $canal) {
            $usuariosTotal       = DoubleUsuario::where(1, '=', 1);
            // $usuariosAtivos      = DoubleUsuario::where('robo_status', '=', 'EXECUTANDO');
            $usuariosNovos       = DoubleUsuario::where(1, '=', 1);
            $totalTestes         = DoubleUsuario::where('demo_jogadas', '<', 5);
            $totalPagamentos     = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'RENOVACAO']);
            $totalCancelamentos  = DoublePagamentoHistorico::where('tipo_evento', '=', 'CANCELAMENTO');
            $totalAssinaturas    = DoublePagamentoHistorico::where('tipo_evento', 'in', ['PAGAMENTO', 'RENOVACAO', 'CANCELAMENTO']);

            $usuariosAtivos = [];

            if ($canal->id) {
                $usuariosTotal       = $usuariosTotal->where('canal_id', '=', $canal->id);
                // $usuariosAtivos      = $usuariosAtivos->where('canal_id', '=', $canal->id);
                $usuariosNovos       = $usuariosNovos->where('canal_id', '=', $canal->id);
                $totalTestes         = $totalTestes->where('canal_id', '=', $canal->id);
                $totalPagamentos     = $totalPagamentos->where('canal_id', '=', $canal->id);
                $totalCancelamentos  = $totalCancelamentos->where('canal_id', '=', $canal->id);
                $totalAssinaturas    = $totalAssinaturas->where('canal_id', '=', $canal->id);

                $sqlAtivos = "SELECT COUNT(DISTINCT dh.usuario_id) total
                                FROM double_usuario_historico dh
                                JOIN double_usuario du on du.id = dh.usuario_id
                               WHERE dh.created_at >= NOW() - INTERVAL 1 HOUR
                                 AND du.canal_id = {$canal->id}
                                 and du.robo_status = 'EXECUTANDO'";

                $conn = TTransaction::get();
                $usuariosAtivos = TDatabase::getData($conn, $sqlAtivos);
            }

            $adicionarFiltroData = function (TRepository $objeto, $campo, $data_inicio, $data_fim) {
                if ($data_inicio and $data_fim) {
                    return $objeto->where("DATE({$campo})", 'between', [$data_inicio, $data_fim]);
                } elseif ($data_inicio and !$data_fim) {
                    return $objeto->where("DATE({$campo})", '>=', $data_inicio);
                } elseif (!$data_inicio and $data_fim) {
                    return $objeto->where("DATE({$campo})", '<=', $data_fim);
                } else {
                    return $objeto->where("DATE({$campo})", 'between', [date('Y-m-01'), date('Y-m-t')]);
                }
            };

            $usuariosNovos       = $adicionarFiltroData($usuariosNovos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalTestes         = $adicionarFiltroData($totalTestes, 'demo_inicio', $object->data_inicio, $object->data_fim);
            $totalPagamentos     = $adicionarFiltroData($totalPagamentos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalCancelamentos  = $adicionarFiltroData($totalCancelamentos, 'created_at', $object->data_inicio, $object->data_fim);
            $totalAssinaturas    = $adicionarFiltroData($totalAssinaturas, 'created_at', $object->data_inicio, $object->data_fim);

            $dados['totalUsuarios']         = $usuariosTotal->count() ?? 0;
            $dados['novosUsuarios']         = $usuariosNovos->count() ?? 0;
            $dados['usuariosAtivos']        = count($usuariosAtivos) > 0 ? $usuariosAtivos[0]['total'] : 0;
            // $dados['usuariosAtivos']        = $usuariosAtivos->count() ?? 0;
            $dados['novosUsuarios']         = $usuariosNovos->count() ?? 0;
            $dados['totalTestesIniciados']  = $totalTestes->count() ?? 0;
            $dados['totalPlanosAssinados']  = ($totalPagamentos->count() ?? 0) - ($totalCancelamentos->count() ?? 0);
            $dados['valorTotalAssinaturas'] = $totalAssinaturas->sumBy('valor') ?? 0;

            return json_encode($dados);
        });

        echo $dados;
    }
}
