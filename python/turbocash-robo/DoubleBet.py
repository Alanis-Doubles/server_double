import logging
import asyncio
from datetime import datetime, date, timedelta
from typing import Any
import requests
from telethon import events, TelegramClient, functions
from telethon.tl.custom import Message, Button
from telethon.tl.types import ReplyKeyboardHide, PeerUser, PeerChannel
from telethon.utils import get_display_name
from translate.DoubleTranslate import DoubleTranslate
from utils import YesNoStatus, UserStatus, ask, signals_to_str


class DoubleBet:

    def __init__(self, translate: DoubleTranslate):
        self.last_signals = []
        self.users = {}

        self.translate = translate

        api_id = 28007219
        api_hash = 'cb0aa0146d046f84bd6a984d12344525'
        name = self.token.split(':')[0]
        self.__bot = TelegramClient(name, api_id, api_hash)

        @self.bot.on(events.NewMessage(pattern='/start'))
        async def start(event: Message):
            # contato = event.contact
            await self.do_start(event)

        @self.bot.on(events.NewMessage())
        async def dados_compartilhados(event: Message):
            contato = event.contact
            if contato:
                usuario = await self.get_user(event.chat_id)
                print(usuario)
                usuario = await self.update_user(
                    event.chat_id,
                    {"telefone": event.contact.phone_number})
                print(usuario)
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_OBRIGADO_COMPARTILHAR_DADOS,
                    buttons=self.create_buttons(usuario, 'inicio')
                )
                await logar_bet(event)
                print(contato.stringify())

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_JA_ASSINEI))
        async def ja_assinei(event: Message):
            if not self.translate.BOTAO_JA_ASSINEI:
                return

            chat_id = event.chat.id
            async with self.bot.conversation(chat_id) as conv:
                email = await ask(conv, self.translate.MSG_EMAIL_COMPRA)
                token = self.get_token()
                if token:
                    payload = {"email": email}
                    header = {"Authorization": f"Bearer {token}"}
                    response = requests.post(
                        f'{self.url_api}/{chat_id}/validar_pagamento',
                        headers=header,
                        json=payload
                    )

                    r = response.json()
                    if response.status_code == 200:
                        user = r['data']
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_CONTA_ATIVADA.format(
                                dia_expiracao=datetime.strptime(user['data_expiracao'], '%Y-%m-%d').strftime('%d/%m/%Y')
                            ),
                            buttons=self.create_buttons(user, '')
                        )
                        await self.do_start(event, user)
                    else:
                        await self.bot.send_message(event.chat_id, r['data'])
                else:
                    await self.bot.send_message(event.chat_id, self.translate.MSG_ROBO_INDISPONIVEL)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_QUERO_ASSINAR))
        async def quero_assinar(event: Message):
            if not self.translate.BOTAO_QUERO_ASSINAR:
                return

            chat_id = event.chat.id
            async with self.bot.conversation(chat_id) as conv:
                email = await ask(conv, self.translate.MSG_ASSINATURA_EMAIL)
                user = await self.update_user(chat_id, {"email": email})
                if user:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_ASSINATURA_TIPO,
                        buttons=self.create_buttons(user, 'assinar')
                    )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_TESTE_5_RODADAS))
        async def quero_testar(event: Message):
            if not self.translate.BOTAO_TESTE_5_RODADAS:
                return

            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"status": UserStatus.DEMO})
            user['status'] = UserStatus.DEMO
            if user:
                await self.do_start(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_LOGAR))
        async def logar_bet(event):
            if not self.translate.BOTAO_LOGAR:
                return

            chat_id = event.chat.id
            if self.translate.BOTAO_TELEFONE:
                usuario = await self.get_user(chat_id)
                if usuario and not usuario['telefone']:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_CLIQUE_BOTAO_ABAIXO,
                        buttons=self.create_buttons(usuario, 'telefone'))
                    return

            async with self.bot.conversation(chat_id) as conv:
                email = await ask(conv, self.translate.MSG_LOGAR_EMAIL.format(plataforma=self.bet_name))
                password = await ask(conv, self.translate.MSG_LOGAR_SENHA)

                await self.bot.send_message(chat_id, self.translate.MSG_LOGAR_AGUARDE)
                token = self.get_token()
                if token:
                    payload = {"email": email, "password": password}
                    header = {"Authorization": f"Bearer {token}"}
                    response = requests.post(
                        f'{self.url_api}/{chat_id}/logar',
                        headers=header,
                        json=payload)

                    r = response.json()
                    if response.status_code == 200:
                        user = r['data']
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_LOGAR_LOGIN_SUCESSO,
                            buttons=self.create_buttons(user, 'inicio'))
                        if self.translate.MSG_LOGAR_BANCA and self.url_deposito:
                            print(f"chat_id: {event.chat_id}, type: {type(event.chat_id)}")
                            await self.bot.send_message(
                                event.chat_id,
                                self.translate.MSG_LOGAR_BANCA.format(banca=user['ultimo_saldo']),
                                buttons=[Button.url(self.translate.BOTAO_DEPOSITAR, url=self.url_deposito)]
                            )
                        await self.bot.send_file(
                            event.chat_id,
                            './assets/passos_iniciais.jpeg',
                            caption=self.translate.MSG_LOGAR_PASSOS_CONFIGURACAO
                        )

                        await self.logado_sucesso(event.chat_id)

                        if user['status'] == UserStatus.DEMO:
                            if user['demo_jogadas'] > 0:
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_LOGAR_INICIO_DEMO)
                            else:
                                user = await self.update_user(chat_id, {"status": UserStatus.AGUARDANDO_PAGAMENTO})
                                user['status'] = UserStatus.AGUARDANDO_PAGAMENTO
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_LOGAR_FIM_DEMO
                                )
                                await self.do_start(event, user)
                    else:
                        await self.bot.send_message(event.chat_id, self.translate.MSG_LOGAR_LOGIN_ERRO)
                else:
                    await self.bot.send_message(event.chat_id, self.translate.MSG_ROBO_INDISPONIVEL)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_DESLOGAR))
        async def deslogar_bet(event):
            if not self.translate.BOTAO_DESLOGAR:
                return

            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"token_plataforma": None})
            if user:
                await self.bot.send_message(event.chat.id, self.translate.MSG_DESLOGAR)
                await self.do_start(event)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_CADASTRO))
        async def registar_double(event):
            if not self.translate.BOTAO_CADASTRO:
                return

            await self.__bot.send_message(
                event.chat_id,
                self.translate.MSG_CADASTRO.format(usuario=get_display_name(event.sender)),
                buttons=[Button.url(self.translate.BOTAO_CADASTRO, url=self.url_register_double)]
            )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_CONFIGURAR))
        async def configurar(event):
            if not self.translate.BOTAO_CONFIGURAR:
                return

            user = await self.get_user(event.chat_id)
            if user:
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_VOLTAR))
        async def voltar(event: Message):
            if not self.translate.BOTAO_VOLTAR:
                return

            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_SELECIONE_OPCAO,
                    buttons=self.create_buttons(user, 'inicio')
                )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_STOP_LOSS))
        async def set_stop_loss(event):
            if not self.translate.BOTAO_STOP_LOSS:
                return

            # chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_STOP_LOSS.format(
                        tipo=user['tipo_stop_loss'].capitalize(),
                        valor=user['stop_loss']
                    ),
                    buttons=self.create_buttons(user, 'stop_loss')
                )
                # async with self.bot.conversation(chat_id) as conv:
                #     __stop_loss = await ask(conv, self.translate.MSG_STOP_LOSS_2)
                #     try:
                #         __value = float(__stop_loss.replace(',', '.'))
                #         user = await self.update_user(chat_id, {"stop_loss": __value})
                #         if user:
                #             await self.exibir_configuracoes(event, user)
                #     except ValueError:
                #         await self.bot.send_message(
                #             event.chat_id,
                #             self.translate.MSG_ERRO_NUMERO,
                #             buttons=self.create_buttons(user, 'configurar')
                #         )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_STOP_LOSS_VALOR))
        async def set_valor_stop_loss(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    __stop_loss = await ask(conv, self.translate.MSG_STOP_LOSS_2)
                    try:
                        __value = float(__stop_loss.replace(',', '.'))
                        user = await self.update_user(chat_id, {"stop_loss": __value})
                        if user:
                            await self.exibir_configuracoes(event, user, 'stop_loss')
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'stop_loss')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_STOP_LOSS_TIPO))
        async def set_tipo_stop_loss(event):
            # chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_SELECIONE_OPCAO,
                            buttons=self.create_buttons(user, 'stop_loss_selecao')
                        )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'tipo_stop_loss_quantidade'))
        async def stop_loss_tipo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"tipo_stop_loss": 'QUANTIDADE'})
            if user:
                await self.exibir_configuracoes(event, user, 'stop_loss')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'tipo_stop_loss_valor'))
        async def tipo_stop_loss_valor(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"tipo_stop_loss": 'VALOR'})
            if user:
                await self.exibir_configuracoes(event, user, 'stop_loss')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_STOP_WIN))
        async def set_stop_win(event):
            if not self.translate.BOTAO_STOP_WIN:
                return

            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                # await self.bot.send_message(
                #     event.chat_id,
                #     self.translate.MSG_STOP_WIN_1.format(stop_win=f"{user['stop_win']:.2f}"),
                #     buttons=self.create_buttons(user, '')
                # )
                async with self.bot.conversation(chat_id) as conv:
                    __stop_win = await ask(conv, self.translate.MSG_STOP_WIN_2)
                    try:
                        __value = float(__stop_win.replace(',', '.'))
                        user = await self.update_user(chat_id, {"stop_win": __value})
                        if user:
                            await self.exibir_configuracoes(event, user)
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'configurar')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_VALOR_APOSTA))
        async def set_value(event):
            if not self.translate.BOTAO_VALOR_APOSTA:
                return

            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    __bet_value = await ask(conv, self.translate.MSG_VALUE_2)
                    try:
                        __value = float(__bet_value.replace(',', '.'))
                        valor_minimo = self.valor_minimo_com_protecao if user['protecao_branco'] == 'Y' else self.valor_minimo
                        if __value >= valor_minimo:
                            if self.valor_entrada_multiplo > 0:
                                if not __value.is_integer() or __value % self.valor_entrada_multiplo != 0:
                                    await self.bot.send_message(
                                        event.chat.id,
                                        'Por favor repita a operação.\nVocê deve informar um valor múltiplo de {value} e não pode possuir '
                                        'centavos'.format(
                                            value=f"{self.valor_entrada_multiplo:.2f}"))
                                    return

                            user = await self.update_user(chat_id, {"valor": __value})
                            if user:
                                await self.exibir_configuracoes(event, user)
                        else:
                            await self.bot.send_message(
                                event.chat.id,
                                'Por favor repita a operação.\nVocê deve informar um valor superior ou igual a {value}'.format(
                                     value=f"{valor_minimo:.2f}"
                                )
                            )
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'configurar')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_GALES))
        async def set_gale(event):
            if not self.translate.BOTAO_GALES:
                return

            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    __gales = await ask(conv, self.translate.MSG_GALES_2)
                    try:
                        __value = int(__gales)
                        user = await self.update_user(chat_id, {"protecao": __value})
                        if user:
                            await self.exibir_configuracoes(event, user)
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'configurar')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_CICLO))
        async def set_ciclo(event):
            if not self.translate.BOTAO_CICLO:
                return

            user = await self.get_user(event.chat_id)
            if user:
                ciclo = 'não ' if user['ciclo'] == YesNoStatus.N else ''
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_CICLO_1,
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_file(
                    event.chat_id,
                    './assets/ciclo-1.png',
                    caption=self.translate.MSG_CICLO_2
                )
                await self.bot.send_file(
                    event.chat_id,
                    './assets/ciclo-2.png',
                    caption=self.translate.MSG_CICLO_3.format(ciclo=ciclo)
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_CICLO_4,
                    buttons=self.create_buttons(user, 'ciclo')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_ciclo'))
        async def habilitar_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"ciclo": 'Y'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_CICLO_5,
                    buttons=self.create_buttons(user, 'configurar')
                )
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'desabilitar_ciclo'))
        async def desabilitar_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"ciclo": 'N'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_CICLO_6,
                    buttons=self.create_buttons(user, 'configurar')
                )
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_HABILITAR_PROTECAO_BRANCO))
        async def set_ciclo(event):
            if not self.translate.BOTAO_HABILITAR_PROTECAO_BRANCO:
                return

            user = await self.get_user(event.chat_id)
            if user:
                protecao_branco = 'não ' if user['protecao_branco'] == YesNoStatus.N else ''
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_HABILITAR_PROTECAO_BRANCO_1.format(protecao_branco=protecao_branco),
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_file(
                    event.chat_id,
                    './assets/protecao-branco.png',
                )
                # await self.bot.send_file(
                #     event.chat_id,
                #     './assets/protecao-branco-2.png',
                # )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_HABILITAR_PROTECAO_BRANCO_2,
                    buttons=self.create_buttons(user, 'protecao_branco')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_protecao_branco'))
        async def habilitar_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"protecao_branco": 'Y'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_HABILITAR_PROTECAO_BRANCO_3,
                    buttons=self.create_buttons(user, 'configurar')
                )
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'desabilitar_protecao_branco'))
        async def desabilitar_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"protecao_branco": 'N'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_HABILITAR_PROTECAO_BRANCO_4,
                    buttons=self.create_buttons(user, 'configurar')
                )
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA))
        async def set_entrada_automatica(event):
            if not self.translate.BOTAO_ENTRADA_AUTOMATICA:
                return

            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_SELECIONE_OPCAO,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR))
        async def set_habilitar_entrada_automatica(event):
            if not self.translate.BOTAO_ENTRADA_AUTOMATICA:
                return

            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_1,
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_2,
                    buttons=self.create_buttons(user, 'ativar_entrada_automatica')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_entrada_automatica'))
        async def habilitar_entrada_automatica(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"entrada_automatica": 'Y'})
            if user:
                await self.bot.send_message(
                        event.chat.id,
                        self.translate.MSG_ENTRADA_AUTOMATICA_3,
                        buttons=self.create_buttons(user, 'entrada_automatica')
                    )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'desabilitar_entrada_automatica'))
        async def desabilitar_entrada_automatica(event: events.CallbackQuery.Event) -> None:
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_SELECIONE_OPCAO,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_DESABILITAR))
        async def set_desabilitar_entrada_automatica(event):
            chat_id = event.chat.id
            user = await self.update_user(
                chat_id, {
                    "entrada_automatica": 'N',
                    'entrada_automatica_total_loss': 1,
                    "metas": 'N',
                    'valor_max_ciclo': 0
                }
            )
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_4,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR_STOP_LOSS))
        async def set_habilitar_entrada_automatica_stop_loss(event):
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_5,
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_2,
                    buttons=self.create_buttons(user, 'entrada_automatica_stop_loss')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_entrada_automatica_stop_loss'))
        async def habilitar_entrada_automatica_stop_loss(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user['ciclo'] == 'A':
                user = await self.update_user(chat_id, {"entrada_automatica": 'A', 'ciclo': 'Y'})
            else:
                user = await self.update_user(chat_id, {"entrada_automatica": 'A'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_6,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_somente_stop_win'))
        async def habilitar_somente_stop_win(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user['ciclo'] == 'A':
                user = await self.update_user(chat_id, {"entrada_automatica": 'Y', 'ciclo': 'Y'})
            else:
                user = await self.update_user(chat_id, {"entrada_automatica": 'Y'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_6,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_stop_win_stop_loss'))
        async def habilitar_somente_stop_win(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user['ciclo'] == 'A':
                user = await self.update_user(chat_id, {"entrada_automatica": 'A', 'ciclo': 'Y'})
            else:
                user = await self.update_user(chat_id, {"entrada_automatica": 'A'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_6,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_somente_stop_loss'))
        async def habilitar_somente_stop_win(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user['ciclo'] == 'A':
                user = await self.update_user(chat_id, {"entrada_automatica": 'B', 'ciclo': 'Y'})
            else:
                user = await self.update_user(chat_id, {"entrada_automatica": 'B'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_6,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_DESABILITAR_STOP_LOSS))
        async def set_desabilitar_entrada_automatica_stop_loss(event):
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user['ciclo'] == 'A':
                user = await self.update_user(
                    chat_id,
                    {"entrada_automatica": 'Y', "ciclo": "Y"}
                )
            else:
                user = await self.update_user(chat_id, {"entrada_automatica": 'Y'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_7,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR_CICLO))
        async def set_habilitar_entrada_automatica_ciclo(event):
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_12,
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_2,
                    buttons=self.create_buttons(user, 'entrada_automatica_ciclo')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'habilitar_entrada_automatica_ciclo'))
        async def habilitar_entrada_automatica_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"ciclo": 'A'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_13,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_TIPO))
        async def set_alterar_tipo_entrada_automatica(event):
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_15,
                    buttons=self.create_buttons(user, 'entrada_automatica_tipo')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'entrada_automatica_tipo_loss'))
        async def habilitar_entrada_automatica_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"entrada_automatica_tipo": 'LOSS'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_16.format(tipo='LOSS'),
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'entrada_automatica_tipo_win'))
        async def habilitar_entrada_automatica_ciclo(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"entrada_automatica_tipo": 'WIN'})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_16.format(tipo='LOSS'),
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_DESABILITAR_CICLO))
        async def set_desabilitar_entrada_automatica_ciclo(event):
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"ciclo": 'Y', 'valor_max_ciclo': 0})
            if user:
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_14,
                    buttons=self.create_buttons(user, 'entrada_automatica')
                )
                await self.exibir_configuracoes(event, user, 'entrada_automatica')

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO))
        async def set_alterar_valor_max_ciclo(event):
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_17.format(valor_max_ciclo=f"{user['valor_max_ciclo']:.2f}"),
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_18,
                    buttons=self.create_buttons(user, 'entrada_automatica_valor_max_ciclo')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'alterar_valor_max_ciclo'))
        async def aumentar_espera_entrada_automatica(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    __espera = await ask(conv, self.translate.MSG_ENTRADA_AUTOMATICA_19)
                    try:
                        __value = float(__espera.replace(',', '.'))
                        if __value >= 0:
                            user = await self.update_user(chat_id, {"valor_max_ciclo": __value})
                            if user:
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_ENTRADA_AUTOMATICA_20.format(valor_max_ciclo=__value),
                                    buttons=self.create_buttons(user, 'entrada_automatica')
                                )
                                await self.exibir_configuracoes(event, user, 'entrada_automatica')
                            else:
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_ROBO_INDISPONIVEL,
                                    buttons=self.create_buttons(user, 'entrada_automatica')
                                )
                        else:
                            raise ValueError()
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'entrada_automatica')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_ALTERAR_QTDE_ESPERA.format(tipo='LOSS')))
        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ENTRADA_AUTOMATICA_ALTERAR_QTDE_ESPERA.format(tipo='WIN')))
        async def set_aumentar_espera_entrada_automatica(event):
            user = await self.get_user(event.chat_id)
            if user:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_8,
                    buttons=self.create_buttons(user, '')
                )
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_ENTRADA_AUTOMATICA_9,
                    buttons=self.create_buttons(user, 'entrada_automatica_quantidade_espera')
                )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'aumentar_espera_entrada_automatica'))
        async def aumentar_espera_entrada_automatica(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    __espera = await ask(conv, self.translate.MSG_ENTRADA_AUTOMATICA_10)
                    try:
                        __value = int(__espera)
                        if __value >= 0:
                            user = await self.update_user(chat_id, {"entrada_automatica_total_loss": __value})
                            if user:
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_ENTRADA_AUTOMATICA_11.format(quantidade=__value),
                                    buttons=self.create_buttons(user, 'entrada_automatica')
                                )
                                await self.exibir_configuracoes(event, user, 'entrada_automatica')
                            else:
                                await self.bot.send_message(
                                    event.chat_id,
                                    self.translate.MSG_ROBO_INDISPONIVEL,
                                    buttons=self.create_buttons(user, 'entrada_automatica')
                                )
                        else:
                            raise ValueError()
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'entrada_automatica')
                        )

        @self.bot.on(events.CallbackQuery(data=lambda data: data == b'manter_espera_entrada_automatica'))
        async def manter_espera_entrada_automatica(event: events.CallbackQuery.Event) -> None:
            chat_id = event.chat.id
            user = await self.get_user(chat_id)
            if user:
                await self.exibir_configuracoes(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_INICIAR))
        async def iniciar_robo(event):
            if not self.translate.BOTAO_INICIAR:
                return

            chat_id = event.chat_id
            user = await self.get_user(chat_id, 'Y')
            if user:
                await self.executar_robo(event, user, False)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_INICIAR_LOSS))
        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_INICIAR_WIN))
        async def iniciar_robo_apos_loss(event):
            if not self.translate.BOTAO_INICIAR_LOSS:
                return

            chat_id = event.chat_id
            user = await self.get_user(chat_id, 'Y')
            if user:
                await self.executar_robo(event, user, True)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_PARAR_ROBO))
        async def parar_robo(event):
            if not self.translate.BOTAO_PARAR_ROBO:
                return

            chat_id = event.chat_id
            user = await self.robo_start_stop(chat_id, False)
            if user:
                await self.bot.send_message(
                    chat_id,
                    self.translate.MSG_PARAR_ROBO,
                    buttons=self.create_buttons(user, 'inicio'))

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_GERAR_ACESSO_APP))
        async def gerar_acesso_app(event):
            if not self.translate.BOTAO_GERAR_ACESSO_APP:
                return

            chat_id = event.chat.id
            async with self.bot.conversation(chat_id) as conv:
                email = await ask(conv, self.translate.MSG_GERAR_ACESSO_APP_1)

                token = self.get_token()
                if token:
                    header = {"Authorization": f"Bearer {token}"}
                    payload = {'email': email}
                    response = requests.post(f'{self.url_api}/{chat_id}/gerar_acesso', headers=header, json=payload)

                    r = response.json()
                    if response.status_code == 200:
                        await self.bot.send_message(chat_id, self.translate.MSG_GERAR_ACESSO_APP_2)
                    else:
                        await self.bot.send_message(chat_id, r['data'])
                else:
                    await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_REGISTAR_USUARIO))
        async def registrar_usuario(event):
            if not self.translate.BOTAO_REGISTAR_USUARIO:
                return

            chat_id = event.chat.id
            async with self.bot.conversation(chat_id) as conv:
                user_id_name = await ask(conv, 'Informe user_id ou user_name')
                expiration_days = await ask(conv, 'Informe o número de dias para expiração do bot')
                if user_id_name.isnumeric():
                    user_telegram = PeerUser(int(user_id_name))
                else:
                    try:
                        user_telegram = await self.bot.get_input_entity(user_id_name)
                    except Exception as e:
                        logging.exception(e)
                        await self.bot.send_message(
                            chat_id,
                            f"Usuário {user_id_name} não encontrado no Telegram",
                            buttons=self.create_buttons(chat_id, 'inicio')
                        )
                        return
                user = await self.get_user(user_telegram.user_id)
                data = datetime.strptime(user['data_expiracao'], '%Y-%m-%d') if user['data_expiracao'] is not None else date.today()
                data = data + timedelta(days=int(expiration_days))
                user = await self.update_user(chat_id, {'status': 'ATIVO', 'data_expiracao': data.strftime('%Y-%m-%d')})

                await self.bot.send_message(
                    chat_id,
                    self.translate.MSG_CONTA_ATIVADA.format(
                                dia_expiracao=datetime.strptime(user['data_expiracao'], '%Y-%m-%d').strftime('%d/%m/%Y')
                            ),
                    buttons=self.create_buttons(user, 'inicio')
                )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META))
        async def metas(event):
            chat_id = event.chat.id
            user = await self.get_user(chat_id, 'Y')
            if user:
                if user['metas'] == 'N':
                    await self.bot.send_message(
                        chat_id,
                        self.translate.MSG_META1,
                        buttons=self.create_buttons(user, 'meta')
                    )
                else:
                    await self.exibir_configuracoes_meta(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META_ATIVAR))
        async def metas_ativar(event):
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"metas": 'Y'})
            if user:
                await self.bot.send_message(
                    chat_id,
                    self.translate.MSG_META2,
                    buttons=self.create_buttons(user, 'meta')
                )
                await self.exibir_configuracoes_meta(event, user)

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META_DESATIVAR))
        async def metas_desativar(event):
            chat_id = event.chat.id
            user = await self.update_user(chat_id, {"metas": 'N'})
            if user:
                await self.bot.send_message(
                    chat_id,
                    self.translate.MSG_META3,
                    buttons=self.create_buttons(user, 'meta')
                )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META_VALOR_ENTRADA))
        async def set_meta_entrada(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    try:
                        valor_entrada = await ask(conv, self.translate.MSG_META6)
                        valor_entrada = float(valor_entrada.replace(',', '.'))

                        tipo_entrada = await ask(conv, self.translate.MSG_META7)
                        tipo_entrada = int(tipo_entrada)
                        if tipo_entrada == 1:
                            v_tipo_entrada = 'FIXO'
                            valor_real_entrada = valor_entrada
                        elif tipo_entrada == 2:
                            v_tipo_entrada = 'PERCENTUAL'
                            valor_real_entrada = user['ultimo_saldo'] * (valor_entrada / 100)
                        else:
                            raise ValueError()

                        valor_minimo = self.valor_minimo_com_protecao if user['protecao_branco'] == 'Y' else self.valor_minimo
                        if valor_real_entrada >= valor_minimo:
                            user = await self.update_user(
                                chat_id,
                                {"usuario_meta": {
                                    "valor_entrada": valor_entrada,
                                    "tipo_entrada": v_tipo_entrada,
                                    'valor_real_entrada': valor_real_entrada
                                }}
                            )
                            if user:
                                await self.exibir_configuracoes_meta(event, user)
                        else:
                            await self.bot.send_message(
                                event.chat.id,
                                self.translate.MSG_META8.format(
                                    entrada=f"{valor_real_entrada:.2f}",
                                    minimo=f"{valor_minimo:.2f}"
                                )
                            )
                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'meta')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META_VALOR_OBJETIVO))
        async def set_meta_objetivo(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    try:
                        valor_objetivo = await ask(conv, self.translate.MSG_META9)
                        valor_objetivo = float(valor_objetivo.replace(',', '.'))

                        tipo_objetivo = await ask(conv, self.translate.MSG_META10)
                        tipo_objetivo = int(tipo_objetivo)
                        if tipo_objetivo == 1:
                            v_tipo_objetivo = 'FIXO'
                            valor_real_objetivo = valor_objetivo
                        elif tipo_objetivo == 2:
                            v_tipo_objetivo = 'PERCENTUAL'
                            valor_real_objetivo = user['ultimo_saldo'] * (valor_objetivo / 100)
                        else:
                            raise ValueError()

                        user = await self.update_user(
                            chat_id,
                            {"usuario_meta": {
                                "valor_objetivo": valor_objetivo,
                                "tipo_objetivo": v_tipo_objetivo,
                                'valor_real_objetivo': valor_real_objetivo
                            }}
                        )
                        if user:
                            await self.exibir_configuracoes_meta(event, user)

                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'meta')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_META_VALOR_PERIODICIDADE))
        async def set_meta_periodicidade(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                async with self.bot.conversation(chat_id) as conv:
                    try:
                        valor_periodicidade = await ask(conv, self.translate.MSG_META11)
                        valor_periodicidade = int(valor_periodicidade.replace(',', '.'))

                        tipo_periodicidade = await ask(conv, self.translate.MSG_META12)
                        tipo_periodicidade = int(tipo_periodicidade)
                        if tipo_periodicidade == 1:
                            v_tipo_periodicidade = 'HORAS'
                        elif tipo_periodicidade == 2:
                            v_tipo_periodicidade = 'MINUTOS'
                        else:
                            raise ValueError()

                        user = await self.update_user(
                            chat_id,
                            {"usuario_meta": {
                                "valor_periodicidade": valor_periodicidade,
                                "tipo_periodicidade": v_tipo_periodicidade,
                            }}
                        )
                        if user:
                            await self.exibir_configuracoes_meta(event, user)

                    except ValueError:
                        await self.bot.send_message(
                            event.chat_id,
                            self.translate.MSG_ERRO_NUMERO,
                            buttons=self.create_buttons(user, 'meta')
                        )

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_MODO_REAL_INATIVO))
        async def set_modo_real(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                user = await self.update_user(chat_id,{"modo_treinamento": "N"})
                await self.bot.send_message(chat_id, self.translate.MSG_MODO_REAL, buttons=self.create_buttons(user, 'inicio'))

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_MODO_TREINAMENTO_INATIVO))
        async def set_modo_treinamento(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user:
                user = await self.update_user(chat_id, {"modo_treinamento": "Y"})
                await self.bot.send_message(chat_id, self.translate.MSG_MODO_TREINAMENTO, buttons=self.create_buttons(user, 'inicio'))

        @self.bot.on(events.NewMessage(pattern=self.translate.BOTAO_ALTERAR_BANCA_TREINAMENTO))
        async def set_alterara_banca_treinamento(event):
            chat_id = event.chat.id
            user = await self.get_user(event.chat_id)
            if user and user['modo_treinamento'] == 'Y':
                async with self.bot.conversation(chat_id) as conv:
                    __banca_treinamento = await ask(conv, self.translate.MSG_BANCA_TREINAMENTO_1)
                    try:
                        __value = float(__banca_treinamento.replace(',', '.'))
                        user = await self.update_user(chat_id, {"banca_treinamento": __value})
                        if user:
                            await self.bot.send_message(
                                event.chat_id,
                                self.translate.MSG_SELECIONE_OPCAO,
                                buttons=self.create_buttons(user, 'inicio')
                            )
                    except ValueError:
                        await self.bot.send_message(event.chat_id, self.translate.MSG_ERRO_NUMERO, buttons=self.create_buttons(user, 'inicio'))
            else:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_BANCA_TREINAMENTO_2.format(banca=user['ultimo_saldo']),
                    buttons=[Button.url(self.translate.BOTAO_DEPOSITAR, url=self.url_deposito)]
                )

    async def logado_sucesso(self, chat_id):
        return

    async def exibir_configuracoes_meta(self, event: events.CallbackQuery.Event, user, key='meta'):
        if user['usuario_meta']:
            entrada = f"{user['usuario_meta']['valor_entrada']:.2f}"
            if user['usuario_meta']['tipo_entrada'] == 'PERCENTUAL':
                entrada += f"% da banca - R$ {user['usuario_meta']['valor_real_entrada']:.2f}"
            else:
                entrada = 'R$ ' + entrada
            objetivo = f"{user['usuario_meta']['valor_objetivo']:.2f}"
            if user['usuario_meta']['tipo_objetivo'] == 'PERCENTUAL':
                objetivo += f"% da banca - R$ {user['usuario_meta']['valor_real_objetivo']:.2f}"
            else:
                objetivo = 'R$ ' + objetivo
            periodicidade = f"{user['usuario_meta']['valor_periodicidade']:.0f}"
            if user['usuario_meta']['tipo_periodicidade'] == 'HORAS':
                periodicidade += 'hr'
            else:
                periodicidade += 'min'

            if key == 'meta':
                await self.bot.send_message(
                    event.chat.id,
                    self.translate.MSG_META5.format(
                        banca=f"{float(user['ultimo_saldo']):.2f}",
                        entrada=entrada,
                        objetivo=objetivo,
                        periodicidade=periodicidade
                    ).replace('.', ','),
                    buttons=self.create_buttons(user, key)
                )
            else:
                return self.translate.MSG_META5.format(
                    banca=f"{float(user['ultimo_saldo']):.2f}",
                    entrada=entrada,
                    objetivo=objetivo,
                    periodicidade=periodicidade
                ).replace('.', ',')
        else:
            if key == 'meta':
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_META4,
                    buttons=self.create_buttons(user, key)
                )

    async def exibir_configuracoes(self, event: events.CallbackQuery.Event, user, key='configurar'):
        msg = 'Não habilitado' if user['entrada_automatica'] == 'N' else 'Habilitado'

        if user['entrada_automatica'] == 'Y':
            msg += '\n     - Ocorrerá após o Stop WIN'
        if user['entrada_automatica'] == 'A':
            msg += '\n     - Ocorrerá após o Stop WIN e Stop LOSS'
        if user['entrada_automatica'] == 'B':
            msg += '\n     - Ocorrerá após o Stop LOSS'

        if (user['entrada_automatica'] == 'A' or user['entrada_automatica'] == 'B') and user['ciclo'] == 'A':
            msg += '\n     - {ciclo} habilitado para o Stop LOSS'.format(ciclo=self.translate.MSG_CICLO_7)
            if user['valor_max_ciclo'] > 0:
                msg += '\n     - {ciclo}: {valor_max_ciclo}'.format(
                    ciclo=self.translate.BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO,
                    valor_max_ciclo=f"{user['valor_max_ciclo']:.2f}"
                )

        if user['entrada_automatica'] != 'N':
            msg += '\n     - Será esperado a ocorrência de {quantidade} {tipo}'.format(
                quantidade=user['entrada_automatica_total_loss'],
                tipo=user['entrada_automatica_tipo']
            )

        if user['metas'] == 'Y':
            msg += '\n\n🎯 Metas: Ativado \n' + await self.exibir_configuracoes_meta(event, user, 'configuracao')

        chat_id = user['chat_id']
        if event.chat:
            chat_id = event.chat.id
        await self.bot.send_message(
            chat_id,
            self.translate.MSG_CONFIGURAR.format(
                value=f"{user['valor']:.2f}",
                gales=user['protecao'],
                stop_win=f"{user['stop_win']:.2f}",
                stop_loss=f"{user['stop_loss']:.2f} [{user['tipo_stop_loss'].capitalize()}]",
                ciclo='Habilitado' if user['ciclo'] != 'N' else 'Não habilitado',
                protecao_branco='Habilitado' if user['protecao_branco'] == 'Y' else 'Não habilitado',
                entrada_automatica=msg
            ).replace('.', ','),
            buttons=self.create_buttons(user, key)
        )

    async def executar_robo(self, event, user: Any, start_after_loss):
        chat_id = event.chat_id

        if user['logado'] == 'N':
            await self.bot.send_message(
                chat_id,
                self.translate.MSG_INICIO_ROBO_1,
                buttons=self.create_buttons(user, 'inicio')
            )
            return

        if user['status'] not in [UserStatus.ATIVO, UserStatus.DEMO]:
            await self.do_start(event, user)
            return

        valor_minimo = self.valor_minimo_com_protecao if user['protecao_branco'] == 'Y' else self.valor_minimo
        if float(user['valor']) < valor_minimo:
            await self.bot.send_message(chat_id, self.translate.MSG_INICIO_ROBO_2.format(valor=f'{valor_minimo:.2f}'))
            return

        if float(user['ultimo_saldo']) < valor_minimo:
            await self.bot.send_message(
                chat_id,
                self.translate.MSG_INICIO_ROBO_3.format(valor=f'{valor_minimo:.2f}'),
                buttons=[Button.url('💰 Depositar', url=self.url_deposito)])
            return

        if user['status'] == UserStatus.ATIVO:
            await self.bot.send_message(
                chat_id,
                self.translate.MSG_INICIO_ROBO_4.format(
                    dia_expiracao=datetime.strptime(user['data_expiracao'], '%Y-%m-%d').strftime('%d/%m/%Y')
                )
            )
        else:
            await self.bot.send_message(chat_id, self.translate.MSG_INICIO_ROBO_5)

        msg = self.translate.MSG_INICIO_ROBO_6.format(
            usuario=user['nome'],
            banca=user['ultimo_saldo'],
            value=f"{user['valor']:.2f}",
            gales=user['protecao'],
            stop_win=f"{user['stop_win']:.2f}",
            stop_loss=f"{user['stop_loss']:.2f} [{user['tipo_stop_loss'].capitalize()}]",
            ciclo='Habilitado' if user['ciclo'] != 'N' else 'Não habilitado',
            protecao_branco='Habilitado' if user['protecao_branco'] == 'Y' else 'Não habilitado',
            entrada_automatica='Não habilitado' if user['entrada_automatica'] == 'N' else 'Habilitado'
        ).replace('.', ',')

        if user['entrada_automatica'] == 'Y':
            msg += '\n     - Ocorrerá após o Stop WIN'
        if user['entrada_automatica'] == 'A':
            msg += '\n     - Ocorrerá após o Stop WIN e Stop LOSS'
        if user['entrada_automatica'] == 'B':
            msg += '\n     - Ocorrerá após o Stop LOSS'

        if (user['entrada_automatica'] == 'A' or user['entrada_automatica'] == 'B') and user['ciclo'] == 'A':
            msg += '\n     - {ciclo} habilitado para o Stop LOSS'.format(ciclo=self.translate.MSG_CICLO_7)
            if user['valor_max_ciclo'] > 0:
                msg += '\n     - {ciclo}: {valor_max_ciclo}'.format(
                    ciclo=self.translate.BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO,
                    valor_max_ciclo=f"{user['valor_max_ciclo']:.2f}"
                )

        if user['entrada_automatica'] != 'N':
            msg += '\n     - Será esperado a ocorrência de {quantidade} {tipo}'.format(
                quantidade=user['entrada_automatica_total_loss'],
                tipo=user['entrada_automatica_tipo']
            )

        if user['status'] == UserStatus.DEMO:
            if user['demo_jogadas'] > 0:
                msg += self.translate.MSG_INICIO_ROBO_7.format(demo_jogadas=user['demo_jogadas'])
            else:
                msg = self.translate.MSG_INICIO_ROBO_8
                await self.bot.send_message(event.chat.id, msg, buttons=self.create_buttons(user, 'pagamento'))
                user = await self.update_user(chat_id, {"status": UserStatus.AGUARDANDO_PAGAMENTO})
                user['status'] = UserStatus.AGUARDANDO_PAGAMENTO
                await self.do_start(event, user)
                return

        if user['metas'] == 'Y':
            msg += '\n\n🎯 Metas: Ativado \n' + await self.exibir_configuracoes_meta(event, user, 'configuracao')

        user = await self.robo_start_stop(chat_id, True, start_after_loss)
        await self.bot.send_message(chat_id, msg, buttons=self.create_buttons(user, 'inicio'))
        if start_after_loss:
            await self.bot.send_message(
                chat_id,
                self.translate.MSG_INICIO_ROBO_9.format(
                    quantidade=user['entrada_automatica_total_loss'],
                    tipo=user['entrada_automatica_tipo']
                )
            )

    async def robo_start_stop(self, chat_id, start: bool, after_loss: bool = False):
        token = self.get_token()
        if token:
            header = {"Authorization": f"Bearer {token}"}
            if start:
                if after_loss:
                    url = f'{self.url_api}/{chat_id}/iniciar_apos_loss'
                else:
                    url = f'{self.url_api}/{chat_id}/iniciar'
            else:
                url = f'{self.url_api}/{chat_id}/parar'

            response = requests.get(url, headers=header)

            if response.status_code == 200:
                r = response.json()
                user = r['data']
                return user
            else:
                await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)
        else:
            await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)

    async def update_user(self, chat_id, payload):
        token = self.get_token()
        if token:
            header = {"Authorization": f"Bearer {token}"}
            response = requests.put(f'{self.url_api}/{chat_id}', headers=header, json=payload)

            r = response.json()
            if response.status_code == 200:
                user = r['data']
                return user
            else:
                print(r)
                await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)
        else:
            await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)

    async def do_start(self, event, user=None):
        if user is None:
            user = await self.get_user(event.chat_id)

        if user:
            if user['status'] == UserStatus.NOVO:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_STATUS_NOVO.format(usuario=user['nome']),
                    buttons=self.create_buttons(user, 'novo')
                )
            elif user['status'] == UserStatus.ATIVO or user['status'] == UserStatus.DEMO:
                await self.bot.send_message(
                    event.chat_id,
                    self.translate.MSG_STATUS_DEMO.format(usuario=user['nome'], plataforma=self.bet_name),
                    buttons=self.create_buttons(user, 'inicio')
                )
            elif user['status'] == UserStatus.AGUARDANDO_PAGAMENTO:
                if self.translate.MSG_STATUS_AG_PGTO and self.translate.BOTAO_JA_ASSINEI and self.translate.BOTAO_QUERO_ASSINAR:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_STATUS_AG_PGTO.format(usuario=user['nome'], plataforma=self.bet_name),
                        buttons=self.create_buttons(user, 'pagamento')
                    )
                else:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_AG_PAGAMENTO_SUPORTE.format(usuario=user['nome']),
                        buttons=[Button.url(self.translate.MSG_SUPORTE, url=self.url_suporte)]
                    )
            elif user['status'] == UserStatus.EXPIRADO:
                if self.translate.MSG_STATUS_EXPIRADO and self.translate.BOTAO_JA_ASSINEI and self.translate.BOTAO_QUERO_ASSINAR:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_STATUS_EXPIRADO.format(
                            usuario=user['nome'],
                            dia_expiracao=datetime.strptime(user['data_expiracao'], '%Y-%m-%d').strftime('%d/%m/%Y')
                        ),
                        buttons=self.create_buttons(user, 'pagamento')
                    )
                else:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_STATUS_EXPIRADO.format(
                            usuario=user['nome'],
                            dia_expiracao=datetime.strptime(user['data_expiracao'], '%Y-%m-%d').strftime('%d/%m/%Y')
                        ),
                        buttons=[Button.url(self.translate.MSG_SUPORTE, url=self.url_suporte)]
                    )
            elif user['status'] == UserStatus.INATIVO:
                if self.translate.MSG_STATUS_INATIVO and self.translate.BOTAO_JA_ASSINEI and self.translate.BOTAO_QUERO_ASSINAR:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_STATUS_INATIVO.format(usuario=user['nome']),
                        buttons=self.create_buttons(user, 'pagamento')
                    )
                else:
                    await self.bot.send_message(
                        event.chat_id,
                        self.translate.MSG_STATUS_INATIVO.format(usuario=user['nome']),
                        buttons=[Button.url(self.translate.MSG_SUPORTE, url=self.url_suporte)]
                    )
            else:
                pass  # await super().do_start_bot(event)

    def get_token(self) -> str:
        payload = {"login": f"{self.api_user}", "password": f"{self.api_password}"}
        header = {"Authorization": "Basic 9bd017c8-8614-4626-8607-c7f47493f56f"}
        try:
            response = requests.post(
                f'{self.url_robo}/auth',
                json=payload,
                headers=header
            )
        except OSError as error:
            print(f'Conexão indisponível: {error}')
            return ''

        if response.status_code == 200:
            r = response.json()
            return r['data']
        else:
            return ''

    async def get_user(self, chat_id, buscar_saldo='N') -> Any:
        user_info = await self.bot.get_input_entity(PeerUser(int(chat_id)))
        user_info = await self.bot(functions.users.GetUsersRequest([user_info]))

        # if not user_info[0].username:
        #    await self.bot.send_message(chat_id, self.translate.MSG_NOME_USUARIO_OBRIGATORIO)

        token = self.get_token()
        if token:
            header = {"Authorization": f"Bearer {token}"}
            payload = {
                "nome": get_display_name(user_info[0]),
                "nome_usuario": user_info[0].username,
                "buscar_saldo": buscar_saldo
            }
            response = requests.get(
                f'{self.url_api}/{chat_id}',
                headers=header,
                json=payload
            )

            if response.status_code == 200:
                r = response.json()
                return r['data']
            else:
                r = response.json()
                print('erro: ' + r['data'] + '\n')
                await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)
                return ''
        else:
            await self.bot.send_message(chat_id, self.translate.MSG_ROBO_INDISPONIVEL)
            return ''

    async def channel_name(self) -> str:
        peer = PeerChannel(self.channel_id)
        channel = await self.bot.get_entity(peer)
        return channel.title

    async def wait_for_new_signals(self, signals: list) -> list:
        result = self.last_signals
        while signals_to_str(signals) == signals_to_str(self.last_signals):
            await asyncio.sleep(2)
            result = self.last_signals
        return result

    @property
    def to_bet_string_replace(self) -> str:
        return '🚨 FUNCIONA SOMENTE NA PLATAFORMA ABAIXO! ⬇️'

    async def print_result(self, user, profit, return_color) -> None:
        message = self.translate.MSG_BET_10.format(
            cor=return_color,
            lucro=f'{profit:.2f}',
            banca=user['ultimo_saldo'] + profit
        ).replace('.', ',')
        # message = (f'➡️ RESULTADO: {return_color}\n💸 Lucro/Prejuízo: R$ {profit:.2f}'
        #            f'\n\nA sua banca é de {user['ultimo_saldo'] + profit}').replace('.', ',')
        await self.bot.send_message(user.chat_id, message)

    @property
    def button_login(self):
        return Button.text(self.translate.BOTAO_LOGAR, resize=True)

    @property
    def button_register(self):
        return Button.text(self.translate.BOTAO_CADASTRO, resize=True)

    @property
    def button_config(self):
        return Button.text(self.translate.BOTAO_CONFIGURAR, resize=True)

    @property
    def admins(self) -> list:
        return []

    def is_admin(self, chat_id: int) -> bool:
        try:
            return chat_id in self.admins
        except Exception as e:
            logging.exception(e)
            return False

    @property
    def button_ciclo(self) -> str:
        return self.translate.BOTAO_CICLO

    def button_iniciar_loss(self, user):
        if user['entrada_automatica_tipo'] == 'LOSS':
            return self.translate.BOTAO_INICIAR_LOSS
        else:
            return self.translate.BOTAO_INICIAR_WIN

    def create_buttons(self, user: Any, key: str):
        is_logged_bet = user['logado'] == 'Y'
        is_start = user['robo_status'] == 'EXECUTANDO' or user['status_objetivo'] == 'EXECUTANDO'
        # is_start = user['robo_iniciar'] == 'Y'
        __buttons = ReplyKeyboardHide()
        if key == 'novo':
            __buttons = [
                [Button.text(self.translate.BOTAO_JA_ASSINEI, resize=True)],
                [Button.text(self.translate.BOTAO_QUERO_ASSINAR, resize=True)],
                [Button.text(self.translate.BOTAO_TESTE_5_RODADAS, resize=True)],
            ]
        elif key in ['inicio', 'resultado']:
            if self.is_admin(int(user['chat_id'])):
                __buttons = [
                    [Button.text(self.translate.BOTAO_REGISTAR_USUARIO, resize=True)],
                    [self.button_config] if is_logged_bet else [self.button_login, self.button_register],
                    [Button.text(self.translate.BOTAO_INICIAR, resize=True),
                     Button.text(self.button_iniciar_loss(user), resize=True)] if is_logged_bet and not is_start else [],
                    [Button.text(self.translate.BOTAO_PARAR_ROBO, resize=True)] if is_logged_bet and is_start else []
                ]
            else:
                if is_logged_bet:
                    saldo_banca = self.translate.BOTAO_BANCA_TREINAMENTO.format(banca=f"{float(user['banca_treinamento']):.2f}").replace('.', ',')
                    if user['modo_treinamento'] == 'N':
                        saldo_banca = self.translate.BOTAO_BANCA_TREINAMENTO.format(banca=f"{float(user['ultimo_saldo']):.2f}").replace('.', ',')
                    __buttons = [
                        [self.button_config],
                        [] if is_start else [Button.text(self.translate.BOTAO_MODO_TREINAMENTO_ATIVO, resize=True),
                                             Button.text(self.translate.BOTAO_MODO_REAL_INATIVO, resize=True)] if user['modo_treinamento'] == 'Y' else
                        [Button.text(self.translate.BOTAO_MODO_TREINAMENTO_INATIVO, resize=True),
                         Button.text(self.translate.BOTAO_MODO_REAL_ATIVO, resize=True)],
                        [] if is_start else [Button.text(self.translate.BOTAO_ALTERAR_BANCA_TREINAMENTO, resize=True),
                                             Button.text(saldo_banca, resize=True)],
                        [Button.text(self.translate.BOTAO_INICIAR, resize=True),
                         Button.text(self.button_iniciar_loss(user), resize=True)] if not is_start else
                        [Button.text(self.translate.BOTAO_PARAR_ROBO, resize=True)],
                        # [Button.text(self.translate.BOTAO_GERAR_ACESSO_APP)] if not is_start else []
                    ]
                else:
                    __buttons = [[self.button_login, self.button_register]]
        elif key in ['assinar']:
            __buttons = [Button.url(self.translate.BOTAO_PLANO_MENSAL, url='https://pay.kirvano.com/65afbafd-3658-4d01-9ee3-38d9c895be22')]
        elif key in ['pagamento']:
            __buttons = [
                [Button.text(self.translate.BOTAO_JA_ASSINEI, resize=True)],
                [Button.text(self.translate.BOTAO_QUERO_ASSINAR, resize=True)],
            ]
        elif key == 'configurar':
            __buttons = [
                [Button.text(self.translate.BOTAO_DESLOGAR, resize=True)],
                [Button.text(self.translate.BOTAO_VALOR_APOSTA, resize=True), Button.text(self.translate.BOTAO_GALES, resize=True)],
                [Button.text(self.translate.BOTAO_STOP_WIN, resize=True), Button.text(self.translate.BOTAO_STOP_LOSS, resize=True)],
                [Button.text(self.translate.BOTAO_ENTRADA_AUTOMATICA, resize=True), Button.text(self.button_ciclo, resize=True)],
                [Button.text(self.translate.BOTAO_HABILITAR_PROTECAO_BRANCO, resize=True)],
                [Button.text(self.translate.BOTAO_VOLTAR, resize=True)],
            ]
        elif key == "telefone":
            __buttons = [
                [Button.request_phone(self.translate.BOTAO_TELEFONE, resize=True)]
            ]
        elif key == 'protecao_branco':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'habilitar_protecao_branco'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_protecao_branco')]
        elif key == 'ciclo':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'habilitar_ciclo'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_ciclo')]
        elif key == 'entrada_automatica':
            __buttons = [
                [
                    Button.text(
                        self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR if user['entrada_automatica'] == 'N'
                        else self.translate.BOTAO_ENTRADA_AUTOMATICA_DESABILITAR,
                        resize=True
                    )
                ],
                [] if user['entrada_automatica'] == 'N' else
                [
                    Button.text(
                        self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR_STOP_LOSS,
                        resize=True
                    )
                ],
                [] if user['entrada_automatica'] == 'N' or (user['entrada_automatica'] == 'Y' or user['ciclo'] == 'N') else
                [
                    Button.text(
                        self.translate.BOTAO_ENTRADA_AUTOMATICA_HABILITAR_CICLO if user['ciclo'] == 'Y'
                        else self.translate.BOTAO_ENTRADA_AUTOMATICA_DESABILITAR_CICLO,
                        resize=True
                    ),
                    Button.clear() if user['ciclo'] == 'N' or user['ciclo'] == 'Y' else
                    Button.text(
                        self.translate.BOTAO_ENTRADA_AUTOMATICA_VALOR_MAX_CICLO,
                        resize=True
                    )
                ],
                [] if user['entrada_automatica'] == 'N' else
                [
                    Button.text(self.translate.BOTAO_ENTRADA_AUTOMATICA_TIPO, resize=True)
                ],
                [] if user['entrada_automatica'] == 'N' else
                [
                    Button.text(self.translate.BOTAO_ENTRADA_AUTOMATICA_ALTERAR_QTDE_ESPERA.format(tipo=user['entrada_automatica_tipo']), resize=True)
                ],
                [Button.text(self.translate.BOTAO_CONFIGURAR, resize=True)] if user['entrada_automatica'] == 'N' else
                [
                    # Button.text(self.translate.BOTAO_META, resize=True),
                    Button.text(self.translate.BOTAO_CONFIGURAR, resize=True)
                ]
            ]
        elif key == 'entrada_automatica_valor_max_ciclo':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'alterar_valor_max_ciclo'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_entrada_automatica')]
        elif key == 'ativar_entrada_automatica':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'habilitar_entrada_automatica'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_entrada_automatica')]
        elif key == 'entrada_automatica_quantidade_espera':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'aumentar_espera_entrada_automatica'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_entrada_automatica')]
        elif key == 'entrada_automatica_stop_loss':
            __buttons = [[Button.inline('Somente Stop WIN', data=b'habilitar_somente_stop_win')],
                         [Button.inline('Stop WIN + Stop LOSS', data=b'habilitar_stop_win_stop_loss')],
                         [Button.inline('Somente Stop LOSS', data=b'habilitar_somente_stop_loss')]]
            # __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'habilitar_entrada_automatica_stop_loss'),
            #             Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_entrada_automatica')]
        elif key == 'entrada_automatica_ciclo':
            __buttons = [Button.inline(self.translate.BOTAO_SIM, data=b'habilitar_entrada_automatica_ciclo'),
                         Button.inline(self.translate.BOTAO_NAO, data=b'desabilitar_entrada_automatica')]
        elif key == 'entrada_automatica_tipo':
            __buttons = [Button.inline(self.translate.BOTAO_ENTRADA_AUTOMATICA_TIPO_LOSS, data=b'entrada_automatica_tipo_loss'),
                         Button.inline(self.translate.BOTAO_ENTRADA_AUTOMATICA_TIPO_WIN, data=b'entrada_automatica_tipo_win')]
        elif key == 'stop_loss':
            __buttons = [
                [Button.text(self.translate.BOTAO_STOP_LOSS_TIPO, resize=True),
                 Button.text(self.translate.BOTAO_STOP_LOSS_VALOR, resize=True)],
                [Button.text(self.translate.BOTAO_CONFIGURAR, resize=True)]
            ]
        elif key == 'stop_loss_selecao':
            __buttons = [Button.inline(self.translate.BOTAO_STOP_LOSS_TIPO_QUANTIDADE, data=b'tipo_stop_loss_quantidade'),
                         Button.inline(self.translate.BOTAO_STOP_LOSS_TIPO_VALOR, data=b'tipo_stop_loss_valor')]
        elif key == 'meta':
            if user['metas'] == 'N':
                __buttons = [
                    [Button.text(self.translate.BOTAO_META_ATIVAR, resize=True),
                     Button.text(self.translate.BOTAO_ENTRADA_AUTOMATICA, resize=True)]
                ]
            else:
                __buttons = [
                    [Button.text(self.translate.BOTAO_META_DESATIVAR, resize=True),
                     Button.text(self.translate.BOTAO_META_VALOR_ENTRADA, resize=True)],
                    [Button.text(self.translate.BOTAO_META_VALOR_OBJETIVO, resize=True),
                     Button.text(self.translate.BOTAO_META_VALOR_PERIODICIDADE, resize=True)],
                    [Button.text(self.translate.BOTAO_ENTRADA_AUTOMATICA, resize=True)]
                ]

        return __buttons

    @property
    def token(self) -> str:
        return ''

    @property
    def channel_id(self):
        return 0

    @property
    def bot(self) -> TelegramClient:
        return self.__bot

    @property
    def bet_name(self) -> str:
        return ''

    @property
    def url_register_double(self):
        return ''

    @property
    def url_double(self):
        return ''

    @property
    def url_suporte(self):
        return ''

    @property
    def url_api(self):
        return f'{self.url_robo}/api{self.bet_id}/{self.channel_id}/robo'

    @property
    def url_deposito(self):
        return ''

    @property
    def url_robo(self):
        return ''

    @property
    def bet_id(self):
        return ''

    @property
    def api_user(self) -> str:
        return ''

    @property
    def api_password(self) -> str:
        return ''

    @property
    def valor_minimo(self):
        return 0

    @property
    def valor_entrada_multiplo(self):
        return 0

    @property
    def valor_minimo_com_protecao(self):
        return 0
