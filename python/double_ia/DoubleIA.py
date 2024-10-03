import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score
from sqlalchemy import create_engine
import warnings
warnings.filterwarnings('ignore')


class DoubleIA:
    def __init__(self, plataforma_id, channel_id, url_connection, estrategia_id=None, usuario_id=None, color_mapping=None):
        self.plataforma_id = plataforma_id
        self.channel_id = channel_id
        self.url_connection = url_connection
        self.estrategia_id = estrategia_id
        self.usuario_id = usuario_id

        # Mapeamento das cores
        # self.color_mapping = {0: 'white', 1: 'red', 2: 'red', 3: 'red', 4: 'red', 5: 'red', 6: 'red', 7: 'red', 8: 'black', 9: 'black', 10: 'black',
        #                       11: 'black', 12: 'black', 13: 'black', 14: 'black'}
        self.color_mapping = color_mapping
        self.color_to_code = {'white': 0, 'red': 1, 'black': 2}

    # Função para buscar os dados do MySQL
    def fetch_data(self):
        engine = create_engine(self.url_connection)
        query = ("SELECT * "
                 "FROM double_sinal "
                 f"WHERE plataforma_id = {self.plataforma_id} "
                 " AND DATE(created_at) >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)"
                 "ORDER BY created_at")

        try:
            rules_df = pd.read_sql_query(query, engine)
        except:
            rules_df = pd.DataFrame()  # Retorna um DataFrame vazio se a consulta falhar
        return rules_df

    def fetch_rules(self):
        engine = create_engine(self.url_connection)
        query = ("SELECT e.* "
                 "  FROM double_estrategia e "
                 "  JOIN double_canal c on c.id = e.canal_id"
                 f" WHERE c.channel_id = {self.channel_id} "
                 "   AND e.tipo IN ('COR','SOMA','NUMERO') "
                 "   AND e.ativo = 'Y' "
                 "   AND e.deleted_at IS NULL ")

        if self.usuario_id:
            query += f"   AND e.usuario_id = {self.usuario_id}"
        else:
            query += "   AND e.usuario_id IS NULL"

        query += " ORDER BY ordem"
        try:
            rules_df = pd.read_sql_query(query, engine)
        except:
            rules_df = pd.DataFrame()  # Retorna um DataFrame vazio se a consulta falhar
        return rules_df

    # Preparar os dados para o modelo
    def prepare_data(self, df):
        df['color_code'] = df['numero'].map(self.color_mapping).map(self.color_to_code)
        for i in range(1, 4):
            df[f'color_code_shift{i}'] = df['color_code'].shift(i)
        df = df.dropna()
        x = df[['color_code_shift1', 'color_code_shift2', 'color_code_shift3']]
        y = df['color_code']
        return x, y

    # Treinar o modelo
    @staticmethod
    def train_model(x, y):
        x_train, x_test, y_train, y_test = train_test_split(x, y, test_size=0.2, shuffle=False)
        model = RandomForestClassifier()
        model.fit(x_train.values, y_train)
        y_pred = model.predict(x_test)
        accuracy = accuracy_score(y_test, y_pred)
        # print(f"Acurácia: {accuracy:.2f}")
        return model

    # Função para prever a próxima cor
    @staticmethod
    def predict_next_color(model, data):
        last_entries = data.iloc[-3:]
        last_colors = last_entries['color_code'].values.tolist()

        if len(last_colors) < 3:
            return None, None, last_colors  # Não é possível prever com menos de 3 sinais

        window_df = pd.DataFrame([last_colors], columns=['color_code_shift1', 'color_code_shift2', 'color_code_shift3'])
        next_color_code = model.predict(window_df)[0]
        # while next_color_code == 0:  # Garantir que a sugestão não seja branco
        #     next_color_code = model.predict(window_df)[0]

        next_color = {0: 'white', 1: 'red', 2: 'black'}[next_color_code]

        return next_color, last_entries.iloc[-1]['numero'], last_colors

    # Função para verificar regras definidas pelo usuário e calcular a probabilidade de sucesso
    def apply_rules(self, rules_df, data):
        color_names = {0: 'white', 1: 'red', 2: 'black'}
        best_rule = None
        best_id = None
        highest_success_rate = 0
        next_color = None

        for _, row in rules_df.iterrows():
            regra, resultado, tipo, id = row['regra'], row['resultado'], row['tipo'], row['id']
            if tipo == 'COR':
                regra_split = regra.split(' - ')
                if len(regra_split) <= len(data):
                    total_matches = 0
                    successful_matches = 0
                    match = True
                    for i in range(len(regra_split)):
                        # for j in range(len(regra_split)):
                        if regra_split[i] == 'other':
                            continue
                        if regra_split[i] != data.iloc[len(data) - len(regra_split) + i]['cor']:
                            match = False
                            break

                    if match:
                        # Calcular a probabilidade de sucesso da regra
                        # success_rate = self.calculate_rule_success_rate(data, regra, resultado, tipo)
                        # if success_rate > highest_success_rate:
                        #     highest_success_rate = success_rate
                        best_rule = regra
                        best_id = id
                        next_color = resultado
                        break

            elif tipo == 'NUMERO':
                last_number = data.iloc[-1]['numero']
                if str(last_number) == regra:
                    # success_rate = self.calculate_rule_success_rate(data, regra, resultado, tipo)
                    # if success_rate > highest_success_rate:
                    #     highest_success_rate = success_rate
                    best_rule = regra
                    best_id = id
                    next_color = resultado
                    break

            elif tipo == 'SOMA':
                last_three_numbers = data.iloc[-3:]['numero'].values.tolist()
                if len(last_three_numbers) == 3:
                    calc_result = last_three_numbers[1] + last_three_numbers[0] - last_three_numbers[2]
                    if 1 <= calc_result <= 7:
                        result = 'red'
                    elif 8 <= calc_result <= 14:
                        result = 'black'
                    else:
                        continue
                    # success_rate = self.calculate_rule_success_rate(data, regra, result, tipo)
                    # if success_rate > highest_success_rate:
                    #     highest_success_rate = success_rate
                    best_rule = regra
                    best_id = id
                    next_color = result
                    break

        return next_color, best_rule, best_id, highest_success_rate

    # Função para calcular a probabilidade de sucesso de uma regra
    def calculate_rule_success_rate(self, data, regra, resultado, tipo):
        color_names = {0: 'white', 1: 'red', 2: 'black'}
        # color_codes = {'white': 0, 'red': 1, 'black': 2}
        total_matches = 0
        successful_matches = 0

        if tipo == 'COR':
            regra_split = regra.split(' - ')
            for i in range(len(data) - len(regra_split)):
                match = True
                for j in range(len(regra_split)):
                    if regra_split[j] == 'other':
                        continue
                    if regra_split[j] != color_names[data.iloc[i + j]['color_code']]:
                        match = False
                        break
                if match:
                    total_matches += 1
                    if color_names[data.iloc[i + len(regra_split)]['color_code']] == resultado:
                        successful_matches += 1

        elif tipo == 'NUMERO':
            for i in range(len(data) - 1):
                if str(data.iloc[i]['numero']) == regra:
                    total_matches += 1
                    if color_names[data.iloc[i + 1]['color_code']] == resultado:
                        successful_matches += 1

        elif tipo == 'SOMA':
            for i in range(2, len(data)-1):
                calc_result = data.iloc[i - 1]['numero'] + data.iloc[i - 2]['numero'] - data.iloc[i]['numero']
                if 1 <= calc_result <= 7:
                    expected_result = 'red'
                elif 8 <= calc_result <= 14:
                    expected_result = 'black'
                else:
                    continue
                if expected_result == resultado:
                    total_matches += 1
                    if color_names[data.iloc[i + 1]['color_code']] == resultado:
                        successful_matches += 1

        if total_matches == 0:
            return 0
        return successful_matches / total_matches

    def prever(self):
        data = self.fetch_data()
        if not data.empty:
            rules_df = self.fetch_rules()

            color_names = {0: 'white', 1: 'red', 2: 'black'}

            x, y = self.prepare_data(data)
            model = self.train_model(x, y)
            next_color, last_number, last_colors = self.predict_next_color(model, data)
            estrategia_id = self.estrategia_id
            last_colors_names = [color_names[color] for color in last_colors]
            pattern = " - ".join(last_colors_names)

            # ia_success_rate = self.calculate_rule_success_rate(data, pattern, next_color, 'COR')
            # print(f'IA Success Rate: {ia_success_rate:.2f}')

            if not rules_df.empty:
                rule_result, best_rule, best_id, rules_success_rate = self.apply_rules(rules_df, data)
                if rule_result:
                    # print(f'Rules Success Rate: {rules_success_rate:.2f}')
                    # if rules_success_rate > ia_success_rate:
                    next_color = rule_result
                    estrategia_id = best_id
                    pattern = best_rule
                else:
                    if self.usuario_id:
                        last_number, next_color, pattern, estrategia_id = (None, None, None, None)

            # if last_number:
            #     color_name = color_names[last_colors[0]]
            return last_number, color_names[last_colors[0]], next_color, pattern, estrategia_id
        else:
            return None, None, None, None, None
