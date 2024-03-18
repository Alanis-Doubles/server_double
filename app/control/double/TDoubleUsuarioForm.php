<?php

use GuzzleHttp\Client;
use Adianti\Widget\Form\TForm;
use Adianti\Base\TStandardForm;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Validator\TMinValueValidator;
use Adianti\Validator\TRequiredValidator;

class TDoubleUsuarioForm  extends TStandardForm
{
    use TUIBuilderTrait;
    use TStandardFormTrait;

    const ACTIVERECORD = 'DoubleUsuario';
    const DATABASE = 'double';

    protected function onBuild($param)
    {
        $this->form->addFields(
            [$this->makeTHidden(['name' => 'id'])],
            [$this->makeTHidden(['name' => 'chat_id'])],
            [$this->makeTHidden(['name' => 'usuarios_canal', 'value' => 'N'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Plataforma'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'plataforma_id', 
                    'label' => $label, 
                    'required' => true, 
                    'database' => 'double', 
                    'model' => 'DoublePlataforma', 
                    'key' => 'id', 
                    'display' => '[{idioma}] {nome}',
                ], 
                function ($object) {
                    $object->setChangeAction( new TAction(array($this, 'onPlataformaChange')) );
                }
            )],
            [$label = $this->makeTLabel(['value' => 'Canal'])],
            [$this->makeTDBCombo(
                [
                    'name' => 'canal_id', 
                    'label' => $label, 
                    'database' => 'double', 
                    'required' => !isset($param['usuarios_canal']) ? false : $param['usuarios_canal'] == 'Y',
                    'model' => 'DoubleCanal', 
                    'key' => 'id', 
                    'display' => '{nome}',
                    'editable' => !isset($param['usuarios_canal']) ? false : $param['usuarios_canal'] == 'Y',
                    'width' => '100%'
                ]
            )],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Nome'])],
            [$this->makeTEntry(['name' => 'nome', 'label' => $label, 'required' => true, 'editable' => false])],
        );

        unset($param['class']);
        unset($param['method']);
        $param['register_state'] = 'false';
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Usuário'])],
            [$this->makeTSeekButton(['name' => 'nome_usuario', 'label' => $label, 'required' => true, 'useOutEvent' => False, 'action' => [$this, 'onBuscarTelegram'], 'action_params' => $param])],
            [$label = $this->makeTLabel(['value' => 'E-mail'])],
            [$this->makeTEntry(['name' => 'email', 'label' => $label, 'required' => true])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Telefone'])],
            [$this->makeTEntry(['name' => 'telefone', 'label' => $label, 'required' => true])],
            [], []
        );

        $status = ['NOVO' => 'Novo', 'DEMO' => 'Demo', 'AGUARDANDO_PAGAMENTO' => 'Ag. Pagto.', 'ATIVO' => 'Ativo', 'INATIVO' => 'Inativo', 'EXPIRADO' => 'Expirado']; 
        
        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Status'])],
            [$this->makeTCombo(['name' => 'status', 'label' => $label, 'items' => $status, 'defaultOption' => false, 'width' => '100%'])],
            [$label = $this->makeTLabel(['value' => 'Vencimento'])],
            [$this->makeTDate(['name' => 'data_expiracao', 'label' => $label, 'width' => '100%', 'mask' => 'dd/mm/yyyy', 'databaseMask' => 'yyyy-mm-dd'])]
        );

        TUtils::setValidation($this->form, 'email', [['validator' => new TEmailValidator]]);

        $this->form->addContent([new TElement('br')]);
        $this->form->addContent([new TFormSeparator('Dados Robô')]);

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Valor'])],
            [$this->makeTNumeric(['name' => 'valor', 'label' => $label, 'decimals' => 2, 'decimalsSeparator' => ',', 'thousandSeparator' => '.'])],
            [$label = $this->makeTLabel(['value' => 'Proteção'])],
            [$this->makeTEntry(['name' => 'protecao', 'label' => $label, 'mask' => '9!'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Stop WIN'])],
            [$this->makeTNumeric(['name' => 'stop_win', 'label' => $label, 'decimals' => 2, 'decimalsSeparator' => ',', 'thousandSeparator' => '.'])],
            [$label = $this->makeTLabel(['value' => 'Stop LOSS'])],
            [$this->makeTNumeric(['name' => 'stop_loss', 'label' => $label, 'decimals' => 2, 'decimalsSeparator' => ',', 'thousandSeparator' => '.'])],
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Jogadas Gratuitas'])],
            [$this->makeTEntry(['name' => 'demo_jogadas', 'label' => $label, 'mask' => '9!'])],
            [$label = $this->makeTLabel(['value' => 'Início Jogadas Gratuitas'])],
            [$this->makeTDateTime(['name' => 'demo_inicio', 'label' => $label, 'width' => '100%', 'mask' => 'dd/mm/yyyy hh:ii:ss', 'databaseMask' => 'yyyy-mm-dd hh:ii:ss'])]
        );

        $this->form->addFields(
            [$label = $this->makeTLabel(['value' => 'Utiliza Recuperação'])],
            [$this->makeTCombo(['name' => 'ciclo', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%'])],
            [$label = $this->makeTLabel(['value' => 'Entrada Automática'])],
            [$this->makeTCombo(['name' => 'entrada_automatica', 'label' => $label, 'items' => ['Y' => 'Sim', 'N' => 'Não'], 'width' => '100%'])],
        );
    }

    protected function getTitle()
    {
        return 'Usuário';
    }

    public function onBuscarTelegram($param) {
        $payload = ['user_name' => $param['nome_usuario']];

        $client = new Client(['http_errors' => false]);
        $response = $client->request(
            'POST',
            'https://alanis.discloud.app/telegram/getUserInfo',
            [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        $content = $response->getBody()->getContents();
        $content = json_decode($content);
        if ($response->getStatusCode() == 200) {
            $data = new stdClass;
            $data->nome = $content->data->full_name;
            $data->nome_usuario = $content->data->user_name;
            $data->chat_id = $content->data->chat_id;
            $data->email = $param['email'];
            $data->plataforma_id = $param['plataforma_id'];
            $data->canal_id = $param['canal_id'];
            $data->usuarios_canal = $param['usuarios_canal'];
            $this->form->setData($data);
        } else {
            $data = new stdClass;
            $data->nome = $param['nome'];
            $data->nome_usuario = $param['nome_usuario'];
            $data->email = $param['email'];
            $data->chat_id = $param['chat_id'];
            $data->plataforma_id = $param['plataforma_id'];
            $data->canal_id = $param['canal_id'];
            $data->usuarios_canal = $param['usuarios_canal'];
            $this->form->setData($data);
            new TMessage('error', $content->data); 
        }
    }

    public static function onPlataformaChange($param)
    {
        try
        {
            if (!empty($param['plataforma_id']))
            {
                $plataforma = TUtils::openFakeConnection('double', function() use ($param){
                    return new DoublePlataforma($param['plataforma_id'], false);
                });
                $param['usuarios_canal'] = $plataforma->usuarios_canal;
                if ($plataforma->usuarios_canal == 'Y')
                    TCombo::enableField('form_TDoubleUsuarioForm', 'canal_id');
                $criteria = TCriteria::create( ['plataforma_id' => $param['plataforma_id'] ] );
                TDBCombo::reloadFromModel('form_TDoubleUsuarioForm', 'canal_id', 'double', 'DoubleCanal', 'plataforma_id', '{nome}', 'id', $criteria, TRUE);
            }
            else
            {
                TCombo::clearField('form_TDoubleUsuarioForm', 'plataforma_id');
            }

            $data = new stdClass;
            $data->nome = $param['nome'];
            $data->nome_usuario = $param['nome_usuario'];
            $data->email = $param['email'];
            $data->chat_id = $param['chat_id'];
            $data->plataforma_id = $param['plataforma_id'];
            $data->canal_id = $param['canal_id'];
            $data->usuarios_canal = $param['usuarios_canal'];
            TForm::sendData('form_TDoubleUsuarioForm', $data, False, False);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
