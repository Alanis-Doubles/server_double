<?php

trait TTransformationTrait
{
    public function cpfTransformer($value)
    {
        if ($value) {
            return TUtils::formatMask('999.999.999-99', $value);
        } else {
            return '';
        }
    }

    public function dateTransformer($value)
    {
        if ($value) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        } else {
            return '';
        }
    }

    public function datetimeTransformer($value)
    {
        if ($value) {
            $date = new DateTime($value);
            return $date->format('d/m/Y H:i');
        } else {
            return '';
        }
    }

    public function doubleTransformer($value)
    {
        if (is_numeric($value)) {
            return number_format($value, 2, ',', '.');
        }
        return $value;
    }

    public function percentualTransformer($value)
    {
        if (is_numeric($value)) {
            return number_format($value, 2, ',', '.') . ' %';
        } else if (!$value)
        {
            return '0,00 %';
        } else
        return $value;
    }

    public function moedaTransformer($value)
    {
        if (is_numeric($value)) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        }
        return $value;
    }

    public function tiporegistro_transformer($value, $object, $row)
    {
        switch ($value) {
            case 'V':
                return 'À Vista';
            default:
                return 'Parcelado';
        }
    }

    public function entrada_transformer($value, $object, $row)
    {
        switch ($value) {
            case 'N':
                return 'Não';
            default:
                return 'Sim';
        }
    }

    public function status_nao_sim_transformer($value, $object, $row)
    {
        $class = (empty($value) || $value == 'S') ? 'danger' : 'success';
        $label = (empty($value) || $value == 'N') ? _t('No') : _t('Yes');
        $div = new TElement('span');
        $div->class = "label label-{$class}";
        $div->style = "text-shadow:none; font-size:12px; font-weight:lighter";
        $div->add($label);
        return $div;
    }

    public function status_sim_nao_transformer($value, $object, $row)
    {
        $class = (empty($value) || $value == 'N') ? 'danger' : 'success';
        $label = (empty($value) || $value == 'N') ? _t('No') : _t('Yes');
        $div = new TElement('span');
        $div->class = "label label-{$class}";
        $div->style = "text-shadow:none; font-size:12px; font-weight:lighter";
        $div->add($label);
        return $div;
    }
}
