<?php

namespace AdminLte\Widget\Container;

use Adianti\Widget\Base\TElement;

/**
 * Wrapper class to handle LTE Small Box
 *
 * @version    1.0.0
 * @package    widget
 * @subpackage container
 * @author     Edson Rodrigues ALanis
 * @copyright  @copyright  Copyright (c) 2024 Alanis
 */

class TLTESmallBox 
{
    private $id         = null;
    private $main       = null;
    // private $boxImages  = ['collapse' => 'fa:plus', 'expand' => 'fa:minus', 'remove' => 'fa:times'];
    // private $dataWidget = ['collapse' => 'collapse', 'expand' => 'collapse', 'remove' => 'remove'];
    // private $boxType    = self::BOXTYPE_DEFAULT;
    // private $isSolid    = false;
    // private $boxTool    = self::BOXTOOL_NONE;
    private $title;
    private $value;
    private $color;
    private $icon;
    private $action;

    public function __construct($title, $value, $color, $icon, $action = null)
    {
        $this->main   = new TElement('div');
        $this->title  = $title;
        $this->value  = $value;
        $this->color  = $color;
        $this->icon   = $icon;
        $this->action = $action;
        $this->id = 'tltesmallbox_' . mt_rand(1000000000, 1999999999);
    }

    public function setId($value) {
        $this->id = $value;
    }

    public function show() 
    {
        $this->main->{'class'} = 'small-box bg-' . $this->color;
        $this->main->{'id'} = $this->id;
        // $this->main->{'style'} = "margin: 0px 0px 0px 0px";

        $inner = new TElement('div');
        $inner->{'class'} = 'inner';

        $value = new TElement('h3');
        $value->add($this->value);
        $inner->add($value);

        $title = new TElement('p');
        $title->add($this->title);
        $inner->add($title);

        $icon = new TElement('div');
        $icon->{'class'} = 'icon';
        $icon->add('<i class="' . $this->icon . '"/>');

        $footer = new TElement('a');
        if ($this->action)
            $footer->{'onclick'} = "__adianti_load_page('{$this->action->serialize(true)}');";
        // else
            // $footer->{'href'} = '#';
        $footer->{'class'} = 'small-box-footer';
        $footer->add('Mais informações ');
        $footer->add('<i class="fa fa-arrow-circle-right"/>');

        $this->main->add($inner);
        $this->main->add($icon);
        $this->main->add($footer);

        echo $this->main;
    }
}