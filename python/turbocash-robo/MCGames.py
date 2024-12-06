from DoubleBet import DoubleBet


class MCGames(DoubleBet):
    @property
    def bet_name(self) -> str:
        return 'MCGames'

    @property
    def bet_id(self):
        return '/mcgames/ptBR'

    @property
    def url_double(self):
        return 'https://mcgames.bet/play/1433'

    @property
    def url_deposito(self):
        return 'https://mcgames.bet/pt/games/double?modal=cashier&type=fiat_deposit'

    @property
    def valor_minimo(self):
        return 0.2

    @property
    def valor_minimo_com_protecao(self):
        return 1
