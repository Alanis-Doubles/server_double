import asyncio
from MCGamesTurboCash import MCGamesTurboCashPrd
from translate.DoubleTranslate import DoubleTranslate


async def run_bet():
    translate = DoubleTranslate('double_turbocash-Jonbet-ptBR.json')
    bet = MCGamesTurboCashPrd(translate)

    async def __run():
        try:
            await bet.bot.start(bot_token=bet.token)
            print(f'''** Utilizando a configuração **
ChatID: {bet.channel_id} - {await bet.channel_name()}
Token: {bet.token}
            ''')
            print('Bot em execução...')
            await bet.bot.run_until_disconnected()
        finally:
            bet.bot.disconnect()

    # async def __run_buscar_sinais():
    #     await bet.buscar_sinais()

    tasks = [
        asyncio.create_task(__run())
        # asyncio.create_task(__run_buscar_sinais())
    ]
    await asyncio.gather(*tasks)

if __name__ == '__main__':
    asyncio.run(run_bet())
