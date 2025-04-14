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
            '60' => '1 ' . _t('minuto'),
            '120' => '2 ' . _t('minuto'),
            '180' => '3 ' . _t('minuto'),
            '300' => '5 ' . _t('minuto'),
        ];

        $classificacao = [
            'Todos'       => _t('Todos'),
            'Criptomoeda' => _t('Criptomoeda'),
            'Forex'       => _t('Forex'),
            'OTC'         => _t('OTC'),
        ];

        $modo = ['Y' => '📚 ' . _t('Treinamento'), 'N' => '🏆 Real'];
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '🎮 ' . _t('Modo')])],
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
            [$label = $this->makeTLabel(['value' => '📅 ' . _t('Data exp.')])],
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
            [$label = $this->makeTLabel(['value' => '💸 ' . _t('Valor operação')])],
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
            [$label = $this->makeTLabel(['value' => '🔒 ' . _t('Proteções')])],
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
            [$label = $this->makeTLabel(['value' => '⏰ ' . _t('Tempo exp.')])],
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
            [$label = $this->makeTLabel(['value' => '🔎 ' . _t('Classific.')])],
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
            [$label = $this->makeTLabel(['value' => '♻ ' . _t('Fator multip.')])],
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

        $ciclo = ['N' => _t('Desabilitado'), 'Y' => _t('Habilitado')];
        $ciclo_valor = $usuario->ciclo == 'N' ? 'N' : 'Y';
        $entrada_automatica_valor = $usuario->entrada_automatica == 'N' ? 'N' : 'Y';

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => '❌ Stop LOSS'])],
            [
                $this->makeTCombo(
                    [
                        'name' => 'tipo_stop_loss',
                        'items' => ['VALOR' => 'Valor', 'QUANTIDADE' => _t('Quantidade')],
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
            [$label = $this->makeTLabel(['value' => '↪️ ' . _t('Ciclo')])],
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

        $apos = ['Y' => 'Stop WIN', 'A' => _t('Stop WIN e Stop LOSS'), 'B' => 'Stop LOSS'];
        $apos_valor = $usuario->entrada_automatica == 'Y' ? 'Y' : ($usuario->entrada_automatica == 'A' ? 'A' : 'B');

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => _t('Entrada Auto.')])],
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
            [$label = $this->makeTLabel(['value' => _t('Ocorre após')])],
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

        $ciclo_stop_loss = ['N' => _t('Desabilitado'), 'A' => _t('Habilitado')];
        $ciclo_stop_loss_valor = $usuario->entrada_automatica == 'Y' ? 'N' : (in_array($usuario->entrada_automatica, ['A', 'B']) ? 'A' : 'N');
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => _t('Ciclo Stop LOSS')])],
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

            [$label = $this->makeTLabel(['value' => _t('Tipo de espera')])],
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
            [$label = $this->makeTLabel(['value' => _t('Qtde. de espera')])],
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
        return _t('Configuração');
    }
}
