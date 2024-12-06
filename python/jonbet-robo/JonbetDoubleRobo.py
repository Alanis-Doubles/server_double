from Jonbet import Jonbet

class JonbetDoubleRoboPrd(Jonbet):

    @property
    def admins(self) -> list:
        return []

    @property
    def channel_id(self):
        # Jonbet - Double Robô
        return -1002392375330

    @property
    def token(self) -> str:
        # @AlanisJonbetBot
        return '7179214558:AAFl6_DJ1d3PczpvcI2OWl6YGlfk5kKsZvQ'

    @property
    def url_register_double(self):
        return 'https://jon.ceo/r/OXYJM'

    @property
    def url_suporte(self):
        return 'https://t.me/edson_alanis'

    @property
    def url_register_sala_sinais(self) -> str:
        return 'https://t.me/+Z6uF0049ZxU1MWZh'

    @property
    def url_register_grupo_whats(self) -> str:
        return ''

    @property
    def url_robo(self):
        return 'https://jonbet.doublerobo.com.br'

    @property
    def valor_minimo(self):
        return 0.1

    @property
    def api_user(self) -> str:
        return 'api'

    @property
    def api_password(self) -> str:
        return '&yUTC4yu@'


# class JonbetAlanisTst(JonbetAlanisPrd):
#     async def do_start(self, event, user=None):
#         if user is None:
#             user = await self.get_user(event.chat_id)
#
#         if user:
#             if user['status'] != UserStatus.ATIVO:
#                 chat_id = event.chat.id
#                 user = await self.update_user(
#                     chat_id,
#                     {
#                         "status": UserStatus.ATIVO,
#                         "data_expiracao": "2100-01-01"  #  datetime.strptime("2100-01-01", '%Y-%m-%d').strftime('%d/%m/%Y')
#                     }
#                 )
#
#             await super().do_start(event, user)
#
#     @property
#     def api_user(self) -> str:
#         return 'api_joao'
#
#     @property
#     def api_password(self) -> str:
#         return 'p1RNnP9yCXQpDgMann@'
#
#     @property
#     def url_robo(self):
#         return 'http://turbocash.local'
#
#     @property
#     def token(self) -> str:
#         # @arbety_auto_bot
#         return '6936510743:AAFvQE4rNZg22ScH_X7EUJ_GmVCynEuaspo'
#
#     @property
#     def channel_id(self):
#         return -1002093089587
#
#     # @property
#     # def session_name(self) -> str:
#     #     return 'sala_alanis_weplay_tst'
