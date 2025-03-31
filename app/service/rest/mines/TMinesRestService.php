<?php

use GuzzleHttp\Client;
use Random\Randomizer;

class TMinesRestService
{
    private static $telegram_token = "6891255381:AAH1vJAY0KCH-XmScXrNRP0SGHwhjnuN2RU";
    private static $telegram_chat_id = ["@sinalesmines100", "@sinalesminesplaypix"]; 
    private static $telegram_host = "https://api.telegram.org/bot{token}/";
    private static $tamanho_matriz = 5;
    private static $total_diamantes = 5;

    public static function executar()
    {
        while (true) {
            $botoes = [
                "resize_keyboard" => true, 
                "inline_keyboard" => [
                    [["text" => "📱 CREAR CUENTA EMIRBET", 'url' =>  "https://cleverplayer.net/KwHiPQA"]],
                    [["text" => " 🆘 SOPORTE", 'url' => "https://t.me/suporteoficialmines_bot"]], 
                    [["text" => " 💣 ACCESO A MINAS", 'url' => "https://emirbet.co/es/casino/play/mines-hacksaw-gaming-m?game"]], 
                ] 
            ];


            $mines = [];
            for ($row = 0; $row < self::$tamanho_matriz; $row++) {
                for ($col = 0; $col < self::$tamanho_matriz; $col++) {
                    $mines[$row][$col] = "🟦";
                }
            }

            $adicionados = 0;
            while ($adicionados < self::$total_diamantes) {
                $row = random_int(0, self::$total_diamantes - 1);
                $col = random_int(0, self::$total_diamantes - 1);

                if ($mines[$row][$col] == "🟦") {
                    $mines[$row][$col] = "💎";
                    $adicionados += 1;
                }
            }

            $matriz = "<b>⭐ Entrada Vip Confirmada ⭐\n\n🚨 Nº de intentos: 3                          \n" . 
                    "💣 03 MINAS\n🕑 Valido por 2 minutos</b>\n\n";
            foreach ($mines as $row) {
                $matriz .= implode("", $row) . "\n";
            }
            $matriz .= "\n🚨Recomendamos un depósito equivalente a 22 dólares.";

            self::sendMessage("ENTRADA FINALIZADA🔹\n✅ ✅ ✅ VICTORIA ✅ ✅ ✅");
            sleep(5);
            self::sendMessage($matriz, $botoes);

            sleep(3 * 60); // 3 minutos
            
        }
    }

    public static function sendMessage($message, $reply_markup = [])
    {
        foreach (self::$telegram_chat_id as $chat_id) {
            $payload = [
                "chat_id" => $chat_id,
                "text" => $message,
                "parse_mode" => "HTML"
            ];

            if ($reply_markup)
                $payload["reply_markup"] = $reply_markup;

            $location = str_replace("{token}", self::$telegram_token, self::$telegram_host);
            $client = new Client();
            $response = $client->request(
                "POST",
                $location . "sendMessage",
                [
                    "json" => $payload,
                    "headers" => [
                        "Content-Type" => "application/json",
                        "Accept" => "application/json"
                    ]
                ]
            );

            $contents = json_decode($response->getBody()->getContents());
        // return $contents;3
        }
    }
}
