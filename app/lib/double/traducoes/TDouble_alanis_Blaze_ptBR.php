<?php

class TDouble_alanis_Blaze_ptBR extends TDoubleTranslate 
{
    public function __construct()
    {
        parent::__construct();
        // $this->list["MSG_SINAIS_ENTRADA_CONFIRMADA"] = "⚠️🚨ENTRADA CONFIRMADA🚨⚠️\n\n📊 ENTRAR NA COR: {cor}\n🚨 FUNCIONA SOMENTE NA PLATAFORMA ABAIXO! ⬇️";
        $this->list["MSG_SINAIS_ENTRADA_CONFIRMADA"] = "⚠️🚨ENTRADA CONFIRMADA🚨⚠️\n\n🔮 TIPO DE ESTRATÉGIA: {estrategia}\n\n📊 ENTRAR NA COR: {cor}\n🎯ENTRAR APÓS: {ultimo_numero} {ultima_cor}\n\n🚨 FUNCIONA SOMENTE NA PLATAFORMA ABAIXO! ⬇️";
        $this->list["MSG_OPERACAO_ENTRADA_CICLO"] = "\n↪️ Recuperação utilizada";
        $this->list["MSG_CICLO_1"] = "Quando a recuperação estiver habilitada, a próxima jogada após o Loss irá utilizar o valor total perdido na jogada anterior. \n\nPor exemplo se você estiver utilizando esta configuração\n\n💲 Sua entrada será de 2,0\n🐓 Você está utilizado 1 gale(s)";
        $this->list["MSG_CICLO_2"] = "Quando ocorrer um **Loss** sua perda será de 6, onde 2 da sua primeira entrada e 4 da Gale 1\nCom a recuperação habilitada na próxima jogada será utilizado como valor de entrada 6 no lugar do 2, para que possamos recuperar o valor perdido.";
        $this->list["MSG_CICLO_3"] = "Nas demais jogadas voltamos a utilizar o valor 2 como entrada.\n\nVocê {ciclo}está usando a recuperação.";
        $this->list["MSG_CICLO_4"] = "Deseja habilitar o uso da recuperação?";
        $this->list["MSG_CICLO_5"] = "Você habilitou o uso da recuperação.";
        $this->list["MSG_CICLO_6"] = "Você desabilitou o uso da recuperação.";
        $this->list["MSG_INICIO_ROBO_6"] = "➡🔸 Olá {usuario} 🔹\n💰A sua banca é de {banca} \n💲 Sua entrada será de {value}\n🐓 Você está utilizado {gales} gale(s)\n✅ Seu Stop Win está programado para {stop_win}\n❌ Seu Stop Loss está programado para {stop_loss}\n↪️ Recuperação: {ciclo}\n🔄 Entrada automática: {entrada_automatica}";
        $this->list["MSG_CONFIGURAR"] = "➡️Seu robô está com a seguinte configuração:\n\n💸 Valor aposta: {value}\n🐓 Gales: {gales}\n✅ Stop WIN: {stop_win}\n❌ Stop LOSS: {stop_loss}\n↪️ Recuperação: {ciclo}\n🔄 Entrada automática: {entrada_automatica}\n\nSe você desejar alterar algum valor, por favor selecione uma das opções.";
        $this->list["MSG_SINAIS_PROJECAO"] = "📈 PROJEÇÃO DE GANHOS:\nVALOR DE ENTRADA: 20\n\n↪️ RECUPERAÇÃO: Habilitado\n\n'VOCÊ ESTARIA COM LUCRO: {valor}";
        $this->list["BOTAO_CICLO"] = "↪️ Recuperação";
        $this->list["BOTAO_JA_ASSINEI"] = "";
        $this->list["BOTAO_QUERO_ASSINAR"] = "";
    }
}