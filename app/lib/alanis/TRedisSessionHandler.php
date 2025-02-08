<?php

use Predis\Client as Redis;

class TRedisSessionHandler implements SessionHandlerInterface
{
    private $redis;
    private $prefix = 'sess_';  // Prefixo para as chaves de sessão no Redis
    private $lifetime;

    public function __construct($redisHost = '127.0.0.1', $redisPort = 6379, $redisPassword = null, $lifetime = 3600)
    {
        // // Cria a instância do Redis
        // $this->redis = new Redis();
        // $this->redis->connect($redisHost, $redisPort);

        // // Se houver senha, realiza a autenticação
        // if ($redisPassword) {
        //     $this->redis->auth($redisPassword);
        // }

        $this->redis = new Redis([
            'scheme' => 'tcp',
            'host'   => $redisHost, // IP do Redis
            'port'   => $redisPort  // Porta padrão do Redis
        ]);

        // Define o tempo de vida padrão para a sessão
        $this->lifetime = $lifetime;
    }

    public function open($savePath, $sessionName): bool
    {
        // Sempre retorna true, pois a conexão já foi estabelecida no construtor
        return true;
    }

    public function close(): bool
    {
        // Fechar a conexão com o Redis
        // $this->redis->disconnect();
        return true;
    }

    public function read($sessionId): string|false
    {
        // Lê os dados da sessão no Redis, prefixando com o nome da chave
        $data = $this->redis->get($this->prefix . $sessionId);
        return $data ? $data : '';
    }

    public function write($sessionId, $data): bool
    {
        // Escreve os dados da sessão no Redis
        // $this->redis->setex($this->prefix . $sessionId, $this->lifetime, $data);
        $ttl = $this->redis->pttl($this->prefix . $sessionId);
        if ($ttl > 0) {
            $ttl = round($ttl / 1000, 0);
            $this->redis->set($this->prefix . $sessionId, $data, 'EX', $ttl);
        } elseif ($ttl === -2) {
            $this->redis->set($this->prefix . $sessionId, $data, 'EX', $this->lifetime);
        }
        //$this->redis->set($this->prefix . $sessionId, $data, 'EX', $this->lifetime);
        return True;
    }

    public function destroy($sessionId): bool
    {
        // Apaga os dados da sessão do Redis
        return $this->redis->del($this->prefix . $sessionId);
    }

    public function gc($maxlifetime): int|false
    {
        // Limpeza das sessões expiradas
        // O Redis irá automaticamente gerenciar a expiração, mas podemos usar essa função para remover sessões específicas
        // baseadas no tempo de vida máximo
        return true;
    }
}
