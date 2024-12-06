from DoubleBet import DoubleBet


class Jonbet(DoubleBet):
    @property
    def bet_name(self) -> str:
        return 'Jonbet'

    @property
    def bet_id(self):
        return '/jonbet/ptBR'

    @property
    def url_double(self):
        return 'https://jon.bet/pt/games/double'

    @property
    def url_deposito(self):
        return 'https://jon.bet/pt/games/double?modal=cashier&type=fiat_deposit'

    @property
    def valor_minimo(self):
        return 0.2

    @property
    def valor_minimo_com_protecao(self):
        return 1
