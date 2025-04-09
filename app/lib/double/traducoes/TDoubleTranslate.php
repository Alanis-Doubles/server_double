<?php

class TDoubleTranslate
{
    public $list;
    protected $plataforma_id;

    public function __construct($plataforma_id)
    {
        $this->plataforma_id = $plataforma_id;

        $this->list = TUtils::openFakeConnection('double', function () use ($plataforma_id){
            $list = DoubleTraducao::getIndexedArray('chave', 'valor');
            $plataforma = DoubleTraducaoPlataforma::where('plataforma_id', '=', $plataforma_id)
                ->getIndexedArray('chave', 'valor');
            foreach ($plataforma as $key => $value) {
                $list[$key] = $value;
            }

            ksort($list);
            return $list;
        });
    }

    public function __get($property) {
        if (isset($this->list[$property]))
            return $this->list[$property];

        return 'nao listado >' . $property;
    }

    public function translate($text){
        if (!isset($this->list[$text]))
        {
            $this->list[$text] = TUtils::openConnection('double', function() use ($text){
                $translate = TUtils::google_translator($text);

                $data = new DoubleTraducaoPlataforma;
                $data->plataforma_id = $this->plataforma_id;
                $data->chave = $text;
                $data->valor = $translate;
                $data->save();

                return $translate;
            });
        }
        
        return $this->list[$text];
    }

    public function list() {
        return $this->list;
    }
}