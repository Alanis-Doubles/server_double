import json
from flask import Flask, request, jsonify
from DoubleIA import DoubleIA

app = Flask(__name__)


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


@app.route('/buscar_sinal/<server_name>/', defaults={'usuario_id': None}, methods=['GET'])
@app.route('/buscar_sinal/<server_name>/<usuario_id>', methods=['GET'])
def lrange(server_name, usuario_id):
    if server_name in dados:
        payload = prever_sinal(
            dados[server_name]['plataforma_id'],
            dados[server_name]['channel_id'],
            dados[server_name]['url_connection'],
            dados[server_name]['estrategia_id'],
            usuario_id
        )
        # print(json.dumps(payload, ensure_ascii=False))
        return jsonify(payload), 200
    else:
        # print(json.dumps({"error": f"Server name '{server_name}' not supported."}, ensure_ascii=False))
        return jsonify({"error": f"Server name '{server_name}' not supported."}), 400
    # try:
    #     start = int(start)
    #     end = int(end)
    #
    #     values = r.lrange(key, start, end)
    #     decoded_values = [json.loads(value.decode('utf-8')) for value in values]
    #
    #     if decoded_values:
    #         result = {
    #             "result": decoded_values
    #         }
    #         return jsonify(result), 200
    #     else:
    #         return jsonify({'error': 'Key not found'}), 404
    #
    # except redis.exceptions.ResponseError as e:
    #     return jsonify({'error': str(e)}), 400


if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000)
