from flask import Flask, jsonify
from DoubleIA import DoubleIA

app = Flask(__name__)


def prever_sinal(plataforma_id, channel_id, url_connection, estrategia_id, usuario_id, color_mapping, probabilidade):
    double_ia = DoubleIA(plataforma_id, channel_id, url_connection, estrategia_id, usuario_id, color_mapping, probabilidade)

    estrategia_id_ant = estrategia_id
    last_number, last_color, next_color, pattern, estrategia_id = double_ia.prever()
    if last_number is None:
        return {"tipo": "NENHUM"}

    entrar_apos = f"🎯 Após [{last_number}]\n"

    if next_color == 'break':
        return {"tipo": "BREAK"}

    if next_color == 'white' and estrategia_id == estrategia_id_ant:
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
        'channel_id': -1001908999841,
        'url_connection': 'mysql+mysqlconnector://app_usr:MjXx2QjjlQcua88r@82.112.244.136/app_turbocash',
        'estrategia_id': 1,
        'color_mapping': {0: 'white', 1: 'black', 2: 'red'},
        'probabilidade': 0.60
    },
    'speed_green': {
        "plataforma_id": 3
    },
    'teste_alanis': {
        "plataforma_id": 14,
        'channel_id': -1002093089587,
        'url_connection': 'mysql+mysqlconnector://root@localhost/double_joao',
        'estrategia_id': 197,
        'color_mapping': {0: 'white', 1: 'black', 2: 'red'},
        'probabilidade': 0.60
    },
    'jonbet_doublerobo': {
        "plataforma_id": 1,
        'channel_id': -1002392375330,
        'url_connection': 'mysql+mysqlconnector://jonbet_db_usr:A8bHtaT65PNXEjav@31.220.73.132/jonbet_db',
        'estrategia_id': 1,
        'color_mapping': {0: 'white', 1: 'red', 2: 'red', 3: 'red', 4: 'red', 5: 'red', 6: 'red', 7: 'red', 8: 'black', 9: 'black', 10: 'black',
                          11: 'black', 12: 'black', 13: 'black', 14: 'black'},
        'probabilidade': 0.60
    },
    'blaze_doublerobo': {
        "plataforma_id": 1,
        'channel_id': -1002465527746,
        'url_connection': 'mysql+mysqlconnector://blaze_db_usr:K1sMiDLydhr3B4oL@31.220.73.132/blaze_db',
        'estrategia_id': 1,
        'color_mapping': {0: 'white', 1: 'red', 2: 'red', 3: 'red', 4: 'red', 5: 'red', 6: 'red', 7: 'red', 8: 'black', 9: 'black', 10: 'black',
                          11: 'black', 12: 'black', 13: 'black', 14: 'black'},
        'probabilidade': 0.60
    },

}


@app.route('/buscar_sinal/<server_name>/', defaults={'usuario_id': None}, methods=['GET'])
@app.route('/buscar_sinal/<server_name>/<usuario_id>', methods=['GET'])
def lrange(server_name, usuario_id):
    if server_name in dados:
        payload = prever_sinal(
            dados[server_name]['plataforma_id'],
            dados[server_name]['channel_id'],
            dados[server_name]['url_connection'],
            dados[server_name]['estrategia_id'],
            usuario_id,
            dados[server_name]['color_mapping'],
            dados[server_name]['probabilidade']
        )
        # print(json.dumps(payload, ensure_ascii=False))
        return jsonify(payload), 200
    else:
        # print(json.dumps({"error": f"Server name '{server_name}' not supported."}, ensure_ascii=False))
        return jsonify({"error": f"Server name '{server_name}' not supported."}), 400


if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000)
