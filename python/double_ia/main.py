import json
import sys
from time import sleep
import requests
from DoubleIA import DoubleIA


def bet_prever():
    def get_token(url_robo, api_user, api_password) -> str:
        payload_json = {"login": f"{api_user}", "password": f"{api_password}"}
        header = {"Authorization": "Basic 9bd017c8-8614-4626-8607-c7f47493f56f"}
        try:
            response = requests.post(f'{url_robo}/auth', json=payload_json, headers=header)
        except OSError as error:
            # print(f'Conexão indisponível: {error}')
            return ''

        if response.status_code == 200:
            r = response.json()
            return r['data']
        else:
            return ''

    def prever_sinal(plataforma_id, channel_id, url_connection, estrategia_id, usuario_id):
        double_ia = DoubleIA(plataforma_id, channel_id, url_connection, estrategia_id, usuario_id)

        last_number, last_color, next_color, pattern, estrategia_id = double_ia.prever()
        if last_number is None:
            return {"tipo": "NENHUM"}

        entrar_apos = f"🎯 Após [{last_number}]\n"

        if next_color == 'break':
            return {"tipo": "BREAK"}

        if next_color == 'white':
            return {"tipo": "NENHUM"}

        payload_json = {
            "cor": next_color,
            "tipo": 'ENTRADA',
            "channel_id": channel_id,
            "informacao": entrar_apos,
            "estrategia_id": estrategia_id,
            "numero": f'{last_number}'
        }

        return payload_json

    dados = {
        'turbo_cash': {
            "plataforma_id": 1,
            'channel_id': -1002222871476,
            'url_connection': 'mysql+mysqlconnector://app_usr:MjXx2QjjlQcua88r@82.112.244.136/app_turbocash',
            'estrategia_id': 1
        },
        'speed_green': {
            "plataforma_id": 3
        },
        'teste_alanis': {
            "plataforma_id": 14,
            'channel_id': -1002093089587,
            'url_connection': 'mysql+mysqlconnector://root@localhost/double_joao',
            'estrategia_id': 197
        }
    }

    args = sys.argv[1:]
    usuario_id = None
    server_name = args[0]
    if len(args) == 2:
        usuario_id = args[1]
    # server_name = request.args.get('server_name')
    if not server_name:
        return {"result": False, "data": f"Param 'server_name' not found."}, 400

    sys.stdout.reconfigure(encoding='utf-8')

    if server_name in dados:
        payload = prever_sinal(
            dados[server_name]['plataforma_id'],
            dados[server_name]['channel_id'],
            dados[server_name]['url_connection'],
            dados[server_name]['estrategia_id'],
            usuario_id
        )
        print(json.dumps(payload, ensure_ascii=False))
    else:
        print(json.dumps({"error": f"Server name '{server_name}' not supported."}, ensure_ascii=False))


# Executa o aplicativo se este script for executado diretamente
if __name__ == '__main__':
    bet_prever()
