<?php

class TDoubleTranslate
{
    protected $list;

    public function __construct()
    {
        $this->list["COLOR_RED"] = "🔴 VERMELHO 🔴";
        $this->list["COLOR_BLACK"] = "⚫ PRETO ⚫";
        $this->list["COLOR_WHITE"] = "⚪️ BRANCO ⚪️";
        $this->list["MSG_OPERACAO_NAO_SUPORTADA"] = "Operação não suportada.";
        $this->list["MSG_OPERACAO_SERVIDOR_MANUTENCAO"] = "Servidor em manutenção.";
        $this->list["MSG_OPERACAO_METODO_NAO_SUPORTADO"] = "Método não suportado.";
        $this->list["MSG_PLATAFORMA_NAO_SUPORTADA"] = "Plataforma não suportada";
        $this->list["MSG_OPERACAO_CANAL_NAO_SUPORTADO"] = "Canal '{channel_id}' não suportado.";
        $this->list["MSG_OPERACAO_IDENTIFICADO_LOSS"] = "Identificado um LOSS, robô irá iniciar as jogadas agora.";
        $this->list["MSG_OPERACAO_ENTRADA_CONFIRMADA"] = "⚠️🚨ENTRADA CONFIRMADA🚨⚠️\n📊 ENTRAR NA COR: {cor}";
        $this->list["MSG_OPERACAO_ENTRADA_REALIZADA"] = "🔰 Entrada realizada 🔰\n💸 Valor: R$ {valor}. \n🎯 Cor: {cor}\n";
        $this->list["MSG_OPERACAO_ENTRADA_CICLO"] = "\n↪️ Cliclo utilizado";
        $this->list["MSG_OPERACAO_MARTINGALE"] = "🐓 Martingale {protecao} realizado 🐓\n💸 Valor: R$ {valor}";
        $this->list["MSG_ROBO_INDISPONIVEL"] = "Robô indisponível, tente mais tarde.";
        $this->list["MSG_NOME_USUARIO_OBRIGATORIO"] = "É obrigatório ter nome de usuário configurado no Telegram.\nPor favor realize a configuração em:\n1) Acesse o Menu do Telegram\n2) Clique no menu \"Configurações\"\n3) E preencha o campo \"Nome do usuário\"";
        $this->list["MSG_STATUS_NOVO"] = "Olá, {usuario}. Seja bem-vindo ao bot automático que opera no jogo double sem precisar de você.";
        $this->list["MSG_STATUS_DEMO"] = "Olá, {usuario}. para começar a utilizar o bot é necessário ter cadastro na {plataforma}, clicando no botão abaixo você  poderá realizar seu cadastro.";
        $this->list["MSG_SUPORTE"] = "Suporte";
        $this->list["MSG_AG_PAGAMENTO_SUPORTE"] = "";
        $this->list["MSG_STATUS_AG_PGTO"] = "Olá, {usuario}. para começar a utilizar o bot é necessário ter cadastro na {plataforma}, clicando no botão abaixo você  poderá realizar seu cadastro.";
        $this->list["MSG_STATUS_EXPIRADO"] = "Olá, {usuario}. Seu plano expirou no dia {dia_expiracao}, caso tenha interesse em assinar, selecione uma das opções abaixo.";
        $this->list["MSG_STATUS_INATIVO"] = "Olá, {usuario}. Seu plano está inativo, caso tenha interesse em assinar, selecione uma das opções abaixo.";
        $this->list["MSG_EMAIL_COMPRA"] = "Qual seu e-mail de compra?";
        $this->list["MSG_CONTA_ATIVADA"] = "Conta ativada com data de expiração para {dia_expiracao}.";
        $this->list["MSG_ASSINATURA_EMAIL"] = "Informe o email que será utilizado na compra.\nEste email será utilizado para validar seu pagamento.";
        $this->list["MSG_ASSINATURA_TIPO"] = "Selecione o tipo de assinatura desejado";
        $this->list["MSG_REG_NOVO_USUARIO"] = "Informe user_id ou user_name";
        $this->list["MSG_REG_NOVO_DIAS"] = "Informe o número de dias para expiração do bot";
        $this->list["MSG_REG_NOVO_NAO_ENCONTRADO"] = "Usuário {user_id_name} não encontrado no Telegram";
        $this->list["MSG_LOGAR_EMAIL"] = "Informe seu email da {plataforma}";
        $this->list["MSG_LOGAR_SENHA"] = "Informe sua senha";
        $this->list["MSG_LOGAR_AGUARDE"] = "Aguarde, fazendo login...";
        $this->list["MSG_LOGAR_LOGIN_SUCESSO"] = "Login realizado com sucesso.";
        $this->list["MSG_LOGAR_BANCA"] = "💰A sua banca é de {banca}.\n\nCaso você precise inserir saldo em sua conta clique no botão abaixo.";
        $this->list["MSG_LOGAR_PASSOS_CONFIGURACAO"] = "Siga os passos para configuração do seu bot.";
        $this->list["MSG_LOGAR_INICIO_DEMO"] = "Parabéns, você entrou na versão DEMO e terá direito a 5 entradas gratuitas, aproveite!!";
        $this->list["MSG_LOGAR_FIM_DEMO"] = "Você já utilizou suas 5 entradas gratuitas. Deseja realizar a assinatura e continuar utilizando o bot?";
        $this->list["MSG_LOGAR_LOGIN_ERRO"] = "Login inválido, por favor refaça a operação.";
        $this->list["MSG_DESLOGAR"] = "Logout realizado com sucesso.";
        $this->list["MSG_CADASTRO"] = "Olá {user}, faça seu cadastro clicando no botão abaixo.";
        $this->list["MSG_ERRO_NUMERO"] = "Por favor repita a operação e informe um número válido.";
        $this->list["MSG_SELECIONE_OPCAO"] = "Selecione uma das opções";
        $this->list["MSG_STOP_LOSS_1"] = "Seu Stop LOSS atual é {stop_loss}.";
        $this->list["MSG_STOP_LOSS_2"] = "Informe o valor para Stop LOSS";
        $this->list["MSG_STOP_LOSS_3"] = "Seu Stop LOSS foi configurado para {stop_loss}.";
        $this->list["MSG_STOP_WIN_1"] = "Seu Stop WIN atual é {stop_win}.";
        $this->list["MSG_STOP_WIN_2"] = "Informe o valor para Stop WIN";
        $this->list["MSG_STOP_WIN_3"] = "Seu Stop WIN foi configurado para {stop_win}.";
        $this->list["MSG_VALUE_1"] = "Seu Valor de aposta atual é {value}.";
        $this->list["MSG_VALUE_2"] = "Informe o Valor de aposta";
        $this->list["MSG_VALUE_3"] = "Seu Valor de aposta foi configurado para {value}.";
        $this->list["MSG_GALES_1"] = "O número de Gales atual é {gales}.";
        $this->list["MSG_GALES_2"] = "Informe o número de Gales";
        $this->list["MSG_GALES_3"] = "O número de Gales foi configurado para {gales}.";
        $this->list["MSG_CICLO_1"] = "Quando o ciclo estiver habilitado, a próxima jogada após o Loss irá utilizar o valor total perdido na jogada anterior. \n\nPor exemplo se você estiver utilizando esta configuração\n\n💲 Sua entrada será de 2,0\n🐓 Você está utilizado 1 gale(s)";
        $this->list["MSG_CICLO_2"] = "Quando ocorrer um **Loss** sua perda será de 6, onde 2 da sua primeira entrada e 4 da Gale 1\nCom o ciclo habilitado na próxima jogada será utilizado como valor de entrada 6 no lugar do 2, para que possamos recuperar o valor perdido.";
        $this->list["MSG_CICLO_3"] = "Nas demais jogadas voltamos a utilizar o valor 2 como entrada.\n\nVocê {ciclo}está usando o ciclo.";
        $this->list["MSG_CICLO_4"] = "Deseja habilitar o uso do ciclo?";
        $this->list["MSG_CICLO_5"] = "Você habilitou o uso do ciclo.";
        $this->list["MSG_CICLO_6"] = "Você desabilitou o uso do ciclo.";
        $this->list["MSG_INICIO_ROBO_1"] = "Você deve fazer o login primeiro.";
        $this->list["MSG_INICIO_ROBO_2"] = "Você deve informar um valor de aposta superior a {valor}.";
        $this->list["MSG_INICIO_ROBO_3"] = "Sua banca deve ser superior a  {valor}.\n\nCaso você precise inserir saldo em sua conta clique no botão abaixo.";
        $this->list["MSG_INICIO_ROBO_4"] = "➡️ Ligando o robô 🤖\n Sua licença expira no dia {dia_expiracao}";
        $this->list["MSG_INICIO_ROBO_5"] = "➡️ Ligando o robô 🤖.";
        $this->list["MSG_INICIO_ROBO_6"] = "➡🔸 Olá {usuario} 🔹\n💰A sua banca é de {banca} \n💲 Sua entrada será de {value}\n🐓 Você está utilizado {gales} gale(s)\n✅ Seu Stop Win está programado para {stop_win}\n❌ Seu Stop Loss está programado para {stop_loss}\n↪️ Ciclo: {ciclo}";
        $this->list["MSG_INICIO_ROBO_7"] = "\n🎮 Você tem {demo_jogadas} jogada(s) gratuita(s).";
        $this->list["MSG_INICIO_ROBO_8"] = "Não foi possível iniciar o seu bot, suas jogadas gratuitas terminaram.";
        $this->list["MSG_INICIO_ROBO_9"] = "O robô iniciará após o primeiro loss.";
        $this->list["MSG_PARAR_ROBO"] = "➡️ Robô fechado com sucesso ✅";
        $this->list["MSG_BET_1"] = "🔰 Entrada realizada 🔰\n💸 Valor: R$ {value} . \n🎯 Cor: {cor}";
        $this->list["MSG_BET_2"] = "🐓 Martingale {protecao} realizado 🐓\n💸 Valor: R$ {value}";
        $this->list["MSG_BET_3"] = "✅✅✅";
        $this->list["MSG_BET_4"] = "Bot parou, Stop LOSS atingido.";
        $this->list["MSG_BET_5"] = "Bot parou, Stop WIN atingido.";
        $this->list["MSG_BET_6"] = "Não bateu! 😥";
        $this->list["MSG_BET_7"] = "Você não tem mais banca para jogar, seu bot foi parado.";
        $this->list["MSG_BET_8"] = "Seu bot foi parado, você não tem mais banca para jogar.";
        $this->list["MSG_BET_9"] = "Suas jogadas gratuitas terminaram, seu bot foi parado";
        $this->list["MSG_BET_10"] = "➡️ RESULTADO: {cor}\n💸 Lucro/Prejuízo: R$ {lucro}\n\nA sua banca é de {banca}";
        $this->list["MSG_CONFIRMADO_AGUARDANDO"] = "⚠️🚨ENTRADA CONFIRMADA🚨⚠️\n\n📊 ENTRAR NA COR: {cor}\n\n⏰ Aguardando para realizar a jogada";
        $this->list["MSG_CONFIGURAR"] = "➡️Seu robô está com a seguinte configuração:\n\n💸 Valor aposta: {value}\n🐓 Gales: {gales}\n✅ Stop WIN: {stop_win}\n❌ Stop LOSS: {stop_loss}\n↪️ Ciclo: {ciclo}\n\nSe você desejar alterar algum valor, por favor selecione uma das opções.";
        $this->list["MSG_SINAIS_OPORTUNIDADE"] = "🥁 POSSÍVEL OPORTUNIDADE 🔔";
        $this->list["MSG_SINAIS_ENTRADA_CONFIRMADA"] = "⚠️🚨ENTRADA CONFIRMADA🚨⚠️\n\n📊 ENTRAR NA COR: {cor}\n⚪️ Fazer proteção no BRANCO\n\n🚨 FUNCIONA SOMENTE NA PLATAFORMA ABAIXO! ⬇️";
        $this->list["MSG_SINAIS_WIN"] = "✅✅✅";
        $this->list["MSG_SINAIS_LOSS"] = "Não bateu! 😥\n\n⏺️ Opcional: Faça mais um gale ☑️";
        $this->list["MSG_SINAIS_GALE"] = "🤞🏻 Façam a {protecao} proteção";
        $this->list["MSG_SINAIS_CADASTRO"] = "📱 Faça seu cadastro";
        $this->list["MSG_SINAIS_TUTORIAL"] = "👨‍🏫 Tutorial Double {plataforma}";
        $this->list["MSG_SINAIS_SUPORTE"] = "🆘 Suporte";
        $this->list["MSG_SINAIS_PARCIAL_DIA"] = "🎰 PARCIAL DO DIA 🎰\n\n✅ Win: {win} | ❌ Loss: {loss}\n🤖 Inteligência do Robô: {percentual}%";
        $this->list["MSG_SINAIS_PROJECAO"] = "📈 PROJEÇÃO DE GANHOS:\nVALOR DE ENTRADA: 20\n\nBANCA RECOMENDADA: {banca}\n\n'VOCÊ ESTARIA COM LUCRO: {valor}";
        $this->list["BOTAO_JA_ASSINEI"] = "✔️ Já assinei o Speed Green";
        $this->list["BOTAO_QUERO_ASSINAR"] = "📝 Quero assinar";
        $this->list["BOTAO_TESTE_5_RODADAS"] = "🕹️ Teste 5 rodadas automáticas";
        $this->list["BOTAO_REGISTAR_USUARIO"] = "➕ Registrar novo usuário";
        $this->list["BOTAO_CONFIGURAR"] = "⚙️ Configurar";
        $this->list["BOTAO_LOGAR"] = "🔒 Já possuo cadastro";
        $this->list["BOTAO_CADASTRO"] = "📲 Fazer cadastro";
        $this->list["BOTAO_INICIAR"] = "🚀 Iniciar Robô";
        $this->list["BOTAO_INICIAR_LOSS"] = "🚀 Iniciar após LOSS";
        $this->list["BOTAO_PARAR_ROBO"] = "⏹ Parar Robô";
        $this->list["BOTAO_PLANO_MENSAL"] = "Plano mensal";
        $this->list["BOTAO_STOP_LOSS"] = "❌ Stop LOSS";
        $this->list["BOTAO_STOP_WIN"] = "✅ Stop WIN";
        $this->list["BOTAO_VALOR_APOSTA"] = "💸 Valor aposta";
        $this->list["BOTAO_GALES"] = "🐓 Gales";
        $this->list["BOTAO_HISTORICO"] = "📈 Histórico";
        $this->list["BOTAO_CICLO"] = "↪️ Ciclo";
        $this->list["BOTAO_VOLTAR"] = "⬅️ Voltar";
        $this->list["BOTAO_DESLOGAR"] = "🔓 Logout";
        $this->list["BOTAO_SIM"] = "✔️ Sim";
        $this->list["BOTAO_NAO"] = "❌️ Não";
        $this->list["BOTAO_DEPOSITAR"] = "💰 Depositar";
    }

    public function __get($property) {
        // $json = file_get_contents('files/translate/' . $this->file_name); 
  
        // // Decode the JSON file 
        // $list = json_decode($json,true); 
    
        if (isset($this->list[$property]))
            return $this->list[$property];

        return 'nao listado >' . $property;
    }
}