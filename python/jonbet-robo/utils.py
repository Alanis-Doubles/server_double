from enum import Enum, auto

from telethon.tl.custom import Conversation


class AutoStrEnum(str, Enum):
    """
    StrEnum where enum.auto() returns the field name.
    See https://docs.python.org/3.9/library/enum.html#using-automatic-values
    """
    @staticmethod
    def _generate_next_value_(name: str, start: int, count: int, last_values: list) -> str:
        return name.upper()


class UserStatus(AutoStrEnum):
    NOVO = auto()
    DEMO = auto()
    AGUARDANDO_PAGAMENTO = auto()
    ATIVO = auto()
    INATIVO = auto()
    EXPIRADO = auto()


class YesNoStatus(AutoStrEnum):
    Y = auto()
    N = auto()


class AssinaturaTipo(AutoStrEnum):
    MENSAL = auto()
    TRIMESTRAL = auto()
    SEMESTRAL = auto()
    ANUAL = auto()


class AssinaturaPlataforma(AutoStrEnum):
    LASTLINK = auto()


class AssinaturaEvento(AutoStrEnum):
    INICIO_ACESSO = auto()
    FIM_ACESSO = auto()
    PAGAMENTO_RECORRENTE = auto()


class TipoHistorico(AutoStrEnum):
    ENTRADA = auto()
    WIN = auto()
    LOSS = auto()
    GALE = auto()
    POSSIVEL = auto()

async def ask(conv: Conversation, question: str, mask: bool = False) -> str:
    await conv.send_message(question)
    response = conv.get_response()
    response = await response
    return response.text


def signals_to_str(v_signals: list) -> str:
    return ' - '.join(str(e) for e in v_signals)


def signals_color_to_str(v_signals: list) -> str:
    return ' - '.join(e.color for e in v_signals)


def print_signals(v_signals: list):
    print(signals_to_str(v_signals))
