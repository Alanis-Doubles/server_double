<?php

// Função para codificar o host em Base64 (similar ao proxy em JavaScript)
function getEncodedHost() {
    $host = $_SERVER['HTTP_HOST']; // Equivalente a location.host
    return base64_encode($host);
}

// Classe de criptografia (XOR) equivalente à classe Cryptor do JavaScript
class Cryptor {
    
    // Função de criptografia (Encrypt)
    public function encrypt($key, $message) {
        $keyBytes = $this->stringToAsciiArray($key);
        $messageBytes = $this->stringToAsciiArray($message);
        $encrypted = array_map(function($char, $keyChar) {
            return $char ^ $keyChar;
        }, $messageBytes, $keyBytes);
        
        return $this->toHexString($encrypted);
    }

    // Função de descriptografia (Decrypt)
    public function decrypt($key, $encryptedMessage) {
        $keyBytes = $this->stringToAsciiArray($key);
        $encryptedBytes = $this->hexToBytes($encryptedMessage);
        $decrypted = array_map(function($char, $keyChar) {
            return $char ^ $keyChar;
        }, $encryptedBytes, $keyBytes);
        
        return $this->asciiArrayToString($decrypted);
    }

    // Converte uma string para um array de valores ASCII
    private function stringToAsciiArray($str) {
        return array_map('ord', str_split($str));
    }

    // Converte um array de valores ASCII de volta para string
    private function asciiArrayToString($asciiArray) {
        return implode('', array_map('chr', $asciiArray));
    }

    // Converte o array de inteiros para string hexadecimal
    private function toHexString($asciiArray) {
        return implode('', array_map(function($char) {
            return str_pad(dechex($char), 2, '0', STR_PAD_LEFT);
        }, $asciiArray));
    }

    // Converte uma string hexadecimal de volta para um array de bytes
    private function hexToBytes($hexString) {
        $bytes = [];
        for ($i = 0; $i < strlen($hexString); $i += 2) {
            $bytes[] = hexdec(substr($hexString, $i, 2));
        }
        return $bytes;
    }
}

// Exemplo de uso
$cryptor = new Cryptor();
$key = "MG0YvWE3WYf8SFFCASW4";
// $key = "7c25747366737274253d2570666e736e6960252b25736e6a6275253d32293635343e3e3e3e3e3e3e3e3e3e33362b2563756670253d5c363e332b3630353f32323f3e32343e37315a7a";
$mensagem = "b6JnfGF7fXMjOjB79zVoLCJnfGF7fXMjOjAjMSIrIkomIilhIiYqMTmjLCJiYXNwekcjOjAjQmVUU7VJU63DT77heCBQeGF7YWZx9k2gIGRoIENg95Nwekche13r8W3oIjpj9GvgbWV6TkFuZSIyICJFZHNxejBBeGFt8XMjLCJzYW2oIilhIkunY1otfXMuZGa2YkvoIjpjY1Fn8W3xVVJMIilhIkg7fHBnOjcxYkV791Vw963ie17x85Ni8W32962iYWvrYkFi86as91NwezVnLWRxfWJrZSJa";
// $message = "7c25747366737274253d2570666e736e6960252b25736e6a6275253d32293635343e3e3e3e3e3e3e3e3e3e33362b2563756670253d5c363e332b3630353f32323f3e32343e37315a7a";

// Criptografar a mensagem
$mensagemCriptografada = $cryptor->encrypt($key, $mensagem);
echo "Mensagem criptografada: $mensagemCriptografada\n";

// Descriptografar a mensagem
$mensagemDescriptografada = $cryptor->decrypt($key, $mensagemCriptografada);
echo "Mensagem descriptografada: $mensagemDescriptografada\n";

echo "base: " . base64_decode($mensagemDescriptografada);
// // Exemplo de Base64 codificado do host
// $encodedHost = getEncodedHost();
// echo "Host codificado em Base64: $encodedHost\n";

?>
