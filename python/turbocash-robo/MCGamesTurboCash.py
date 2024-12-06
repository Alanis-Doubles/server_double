from telethon import TelegramClient, events, Button
from telethon.tl.custom import Message
from MCGames import MCGames
from translate import DoubleTranslate
from utils import TipoHistorico, UserStatus


class MCGamesTurboCashPrd(MCGames):

    def __init__(self, translate: DoubleTranslate):
        super().__init__(translate)

        @self.bot.on(events.NewMessage(pattern='/tutorial'))
        async def tutorial(event: Message):
            await self.bot.send_message(
                event.chat_id,
                'Acesse o tutorial clicando no botão abaixo',
                buttons=[Button.url('Clique aqui!!', url='https://speed-green.com/tutorial')]
            )

    async def do_start(self, event, user=None):
        print("Inicio: ")
        print(user)
        if user is None:
            user = await self.get_user(event.chat_id)

        print("Inicio: ")
        print(user)
        if user:
            if user['status'] != UserStatus.ATIVO:
                chat_id = event.chat.id
                user = await self.update_user(
                    chat_id,
                    {
                        "status": UserStatus.NOVO,
                        "data_expiracao": "2100-01-01"
                    }
                )

            user['status'] = UserStatus.ATIVO
            await super().do_start(event, user)

    async def logado_sucesso(self, chat_id):
        await self.update_user(chat_id, {"status": UserStatus.ATIVO})

    @property
    def bet_id(self):
        return '/bacbomcgames/ptBR'

    @property
    def admins(self) -> list:
        return []

    @property
    def channel_id(self):
        return -1001908999841

    @property
    def token(self) -> str:
        return '7433920023:AAGNE8K33KPVkA5mDET5NyB5dA7heopDc2o'

    @property
    def url_register_double(self):
        return 'https://go.aff.mcgames.bet/r4048r9o'

    @property
    def url_suporte(self):
        return 'https://t.me/gabrieladfaria'

    @property
    def url_register_sala_sinais(self) -> str:
        return 'https://t.me/doubletechjg'

    @property
    def url_register_grupo_whats(self) -> str:
        return ''

    @property
    def url_robo(self):
        return 'https://app.turbocash.blog'

    @property
    def valor_minimo(self):
        return 5

    @property
    def valor_minimo_com_protecao(self):
        return 25

    @property
    def valor_entrada_multiplo(self):
        return 5

    @property
    def api_user(self) -> str:
        return 'api'

    @property
    def api_password(self) -> str:
        return 'p1RNnP9yCXQpDgMann@1'


class MCGamesTurboCashTst(MCGamesTurboCashPrd):
    @property
    def channel_id(self):
        return -1002405334026

    @property
    def token(self) -> str:
        return '7757595077:AAGCV0RZ7w3UtztPsEpozWeWS3JFnqxUDg4'

    @property
    def url_robo(self):
        return 'http://server_double.test'

    @property
    def api_user(self) -> str:
        return 'api_joao'

    @property
    def api_password(self) -> str:
        return 'p1RNnP9yCXQpDgMann@1'
