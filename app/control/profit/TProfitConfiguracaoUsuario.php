<?php

use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardForm;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Validator\TMaxValueValidator;

class TProfitConfiguracaoUsuario extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleUsuario';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $usuario = TUtils::openFakeConnection("double", function () use ($param) {
            return new DoubleUsuario($param['key']);
        });

        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id', 'value' => $usuario->id])],
            [$this->makeTHidden(['name' => 'plataforma_id', 'value' => $usuario->plataforma_id])],
        );

        $expiracao = [
            // '5' => '5 segundos',
            // '10' => '10 segundos',
            // '15' => '15 segundos',
            // '30' => '30 segundos',
            // '45' => '45 segundos',
            // '60' => '1 minuto',
            // '120' => '2 minutos',
            // '180' => '3 minutos',
            // '300' => '5 minutos'
            '60'   => '1 minuto',
            '300'  => '5 minutos',
            '900'  => '15 minutos',
            '1800' => '30 minutos',
        ];

        $classificacao = [
            'Todos' => 'Todos',
            // 'Ações' => 'Ações',
            // 'Commodities' => 'Commodities',
            'Criptomoeda' => 'Criptomoeda',
            'Forex' => 'Forex',
            'OTC' => 'OTC',
            // 'Índice' => 'Índice'
        ];

        $modo = ['Y' => '📚 Treinamento', 'N' => '🏆 Real'];
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '🎮 Modo'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'modo_treinamento',
                        'label' => $label,
                        'items' => $modo,
                        'width' => '100%',
                        'defaultOption' => false
                    ]
                )
            ],
            [$label = $this->makeTLabel(['value' => '📅 Data exp.'])],
            [
                $this->makeTDate(
                    [
                        'name' => 'data_expiracao',
                        'label' => $label,
                        'mask' => 'dd/mm/yyyy',
                        'databaseMask' => 'yyyy-mm-dd',
                        'editable' => false
                    ]
                )
            ],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '💸 Valor operação'])],
            [
                $this->makeTNumeric(
                    [
                        'name' => 'valor',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'required' => true
                    ]
                )
            ],
            [$label = $this->makeTLabel(['value' => '🔒 Proteções'])],
            [
                $this->makeTEntry(
                    [
                        'name' => 'protecao',
                        'label' => $label,
                        'mask' => '9!',
                        'required' => true
                    ],
                )
            ],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '⏰ Tempo exp.'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'expiration',
                        'label' => $label,
                        'items' => $expiracao,
                        'width' => '100%',
                        'defaultOption' => false
                    ]
                )
            ],
            [$label = $this->makeTLabel(['value' => '🔎 Classific.'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'classificacao',
                        'label' => $label,
                        'items' => $classificacao,
                        'width' => '100%',
                        'defaultOption' => false
                    ]
                )
            ],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '♻ Fator multip.'])],
            [
                $this->makeTNumeric(
                    [
                        'name' => 'fator_multiplicador',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'required' => true
                    ]
                )
            ],
            [$label = $this->makeTLabel(['value' => '✅ Stop WIN'])],
            [
                $this->makeTNumeric(
                    [
                        'name' => 'stop_win',
                        'label' => $label,
                        'decimals' => 2,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'required' => true
                    ]
                )
            ],
        );

        $ciclo = ['N' => 'Desabilitado', 'Y' => 'Habilitado'];
        $ciclo_valor = $usuario->ciclo == 'N' ? 'N' : 'Y';
        $entrada_automatica_valor = $usuario->entrada_automatica == 'N' ? 'N' : 'Y';

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '❌ Stop LOSS'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'tipo_stop_loss',
                        'items' => ['VALOR' => 'Valor', 'QUANTIDADE' => 'Quantidade'],
                        'width' => '60%',
                        'defaultOption' => false
                    ], 
                    function ($object) {
                        $object->setChangeAction(new TAction([$this, 'onChangeTipoStopLoss']));;
                    }
                ),
                $this->makeTNumeric(
                    [
                        'name' => 'stop_loss',
                        'label' => $label,
                        'decimals' => 0,
                        'decimalsSeparator' => ',',
                        'width' => '40%',
                        'thousandSeparator' => '.',
                        'required' => true
                    ], 
                    function ($object) {
                        $object->setId('stop_loss_id');
                    }
                )
            ],
            [$label = $this->makeTLabel(['value' => '↪️ Ciclo'])],
            [
                $this->makeTRadioGroup(
                    [
                        'name' => 'ciclo_valor',
                        'value' => $ciclo_valor,
                        'label' => $label,
                        'items' => $ciclo,
                        'width' => '100%',
                        'layout' => 'horizontal',
                        'useButton' => true,
                        'required' => true
                    ]
                )
            ],
        );

        $this->form->addContent([new TFormSeparator('')]);

        $apos = ['Y' => 'Stop WIN', 'A' => 'Stop WIN e Stop LOSS', 'B' => 'Stop LOSS'];
        $apos_valor = $usuario->entrada_automatica == 'Y' ? 'Y' : ($usuario->entrada_automatica == 'A' ? 'A' : 'B');

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Entrada Auto.'])],
            [
                $this->makeTRadioGroup(
                    [
                        'name' => 'entrada_automatica_valor',
                        'value' => $entrada_automatica_valor,
                        'label' => $label,
                        'items' => $ciclo,
                        'width' => '100%',
                        'layout' => 'horizontal',
                        'useButton' => true,
                        'required' => true
                    ]
                )
            ],
            [$label = $this->makeTLabel(['value' => 'Ocorre após'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'apos_valor',
                        'value' => $apos_valor,
                        'items' => $apos,
                        'width' => '100%',
                        'defaultOption' => false
                    ]
                )
            ]
        );

        $ciclo_stop_loss = ['N' => 'Desabilitado', 'A' => 'Habilitado'];
        $ciclo_stop_loss_valor = $usuario->entrada_automatica == 'Y' ? 'N' : (in_array($usuario->entrada_automatica, ['A', 'B']) ? 'A' : 'N');
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Ciclo Stop LOSS'])],
            [
                $this->makeTRadioGroup(
                    [
                        'name' => 'ciclo_stop_loss_valor',
                        'label' => $label,
                        'defaultOption' => false,
                        'width' => '100%',
                        'items' => $ciclo_stop_loss,
                        'value' => $ciclo_stop_loss_valor,
                        'layout' => 'horizontal',
                        'useButton' => true
                    ]
                )
            ],

            [$label = $this->makeTLabel(['value' => 'Tipo de espera'])],
            [
                $this->makeTRadioGroup(
                    [
                        'name' => 'entrada_automatica_tipo',
                        'label' => $label,
                        'defaultOption' => false,
                        'width' => '100%',
                        'items' => ['WIN' => 'Win', 'LOSS' => 'Loss'],
                        'layout' => 'horizontal',
                        'useButton' => true
                    ],
                    function ($object) {
                        $object->title = '';
                    }
                )
            ]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Qtde. de espera'])],
            [
                $this->makeTNumeric(
                    [
                        'name' => 'entrada_automatica_total_loss',
                        'label' => $label,
                        'decimals' => 0,
                        'decimalsSeparator' => ',',
                        'thousandSeparator' => '.',
                        'width' => '100%',
                        'value' => 0
                    ],
                )
            ],
            [],
            []
        );
    }

    public static function onChangeTipoStopLoss($param)
    {
        $field = 'stop_loss_id';
        $value = $param['_field_value'];

        if ($value == 'VALOR') {
            TScript::create("tentry_numeric_mask( '{$field}', 2, ',', '.', false, false); ");
        } else {
            TScript::create("tentry_numeric_mask( '{$field}', 0, '', '.', false, true); ");
        }

        $data = new stdClass;
        $data->stop_loss = 0;

        TForm::sendData('form_ProfitConfiguracaoUsuario', $data, false, false, 2000);
    }

    protected function getTitle()
    {
        return 'Configuração';
    }
}
