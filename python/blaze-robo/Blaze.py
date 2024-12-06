from DoubleBet import DoubleBet


class Blaze(DoubleBet):
    @property
    def bet_name(self) -> str:
        return 'Blaze'

    @property
    def bet_id(self):
        return '/blaze/ptBR'

    @property
    def url_double(self):
        return 'https://www.arbety.com/games/double'

    def url_deposito(self):
        return 'https://blaze.com/pt/games/double?modal=cashier&type=fiat_deposit'
