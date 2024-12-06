import re

import requests
from telethon import events, TelegramClient
from telethon.tl.types import PeerChannel

from Blaze import Blaze
from translate.DoubleTranslate import DoubleTranslate
from utils import TipoHistorico


class BlazeDoubleRoboPrd(Blaze):
    @property
    def admins(self) -> list:
        return []

    @property
    def channel_id(self):
        # Alanis Blaze
        return -1002465527746

    @property
    def token(self) -> str:
        # @BlazeDoubleRoboBot
        return '7225793384:AAFAsr2Y8zEIljVkJALHZMcBM-0haJFle1w'

    @property
    def url_register_double(self):
        return 'blaze-7.com/r/GbnMEk'

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
        return 'https://blaze.doublerobo.com.br/'

    @property
    def valor_minimo(self):
        return 0.1

    @property
    def api_user(self) -> str:
        return 'api'

    @property
    def api_password(self) -> str:
        return '&yUTC4yu'


# class BlazeAlanisTst(BlazeAlanisPrd):
    # @property
    # def channel_id(self):
    #     # DOUBLE DO GB BLAZE
    #     return -1002129523713
    #
    # @property
    # def token(self) -> str:
    #     # @double_automatico_gb_tst_bot
    #     return '6882263658:AAGGJPaQGf7jVVgoxI-YuTMGS1sHHtcg-aI'
    #
    # @property
    # def url_robo(self):
    #     return 'http://server_double.test'
    #
    # @property
    # def api_user(self) -> str:
    #     return 'api_joao'
    #
    # @property
    # def api_password(self) -> str:
    #     return 'p1RNnP9yCXQpDgMann@'