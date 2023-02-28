<?php
/**
 * Classe administradora de Sessões
 *
 *      Inicia uma seção: $session = Session::iniciar();
 *      Exemplo de SET: $session->attr = 'Exemplo';
 *      Exemplo de GET: echo $sessao->attr;
 *      Encerra seção: $session = NULL;
 *
 * @author    Daniel Bispo <szagot@gmail.com>
 * @copyright Copyright (c) 2015
 */

namespace Sz\Config;

use \Exception;

class Session
{
    const UNIQUE_KEY = 'S3ss10n';

    /** @var Session */
    private static $instance;
    /** @var string Guarda o nome da sessão sem o hash */
    private static $sessionName;

    /**
     * Inicia uma sessão
     *
     * @param string  $id          Define o ID da sessão
     * @param integer $timeMin     Duração da sessão em minutos. Se não informado, mantém a mesma
     * @param string  $sessionPath Path da Sessão no projeto
     *
     * @return Session
     */
    public static function start($id = null, $timeMin = null, $sessionPath = null, $useCookie = false)
    {
        // Verifica se a classe já foi instanciada
        if (! isset(self::$instance)) {
            self::$instance = new self($id, $timeMin, $sessionPath, $useCookie);
        }

        // Retorna a instância da classe
        return self::$instance;
    }

    /**
     * Método Construtor
     * Inicia uma sessão
     *
     * @param string  $id          Id da sessão
     * @param integer $timeMin     Duração da sessão em min
     * @param string  $sessionPath Path da Sessão no projeto
     *
     * @throws Exception Não iniciou a sessão
     */
    private function __construct($id, $timeMin = null, $sessionPath = null, $useCookie = false)
    {
        // Criando pasta da sessão, se não existir
        if (empty($sessionPath)) {
            $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
        }
        if (! file_exists($sessionPath)) {
            mkdir($sessionPath);
        }

        // Setando a pasta da sessão, se existir
        if (file_exists($sessionPath)) {
            ini_set('session.save_path', $sessionPath);
        }

        // Setando duração da sessão para no máximo 1 semana
        ini_set('session.cookie_lifetime', 604800);
        ini_set('session.gc_maxlifetime', 604800);

        // Verifica o cookie único
        if (! isset($_COOKIE[ self::UNIQUE_KEY ])) {
            $value = self::UNIQUE_KEY . DIRECTORY_SEPARATOR . time();
            setcookie(self::UNIQUE_KEY, $value, time() + 604800);
            $_COOKIE[ self::UNIQUE_KEY ] = $value;
        }

        if($useCookie){
            self::$sessionName =
                (($_SERVER[ 'HTTP_HOST' ] != 'localhost') ? $_COOKIE[ self::UNIQUE_KEY ] : 'local') . DIRECTORY_SEPARATOR;
        }

        // Define o nome da sessão
        self::$sessionName .=
            //  IP do usuário
            $_SERVER[ 'REMOTE_ADDR' ] . DIRECTORY_SEPARATOR
            . 'Szga-Ot' . DIRECTORY_SEPARATOR
            // Dados do navegador do usuário
            . $_SERVER[ 'HTTP_USER_AGENT' ] . DIRECTORY_SEPARATOR
            // ID da sessão caso seja definido
            . $id;

        session_name(md5(self::$sessionName));
        session_id(md5(self::$sessionName));

        // Inicia a sessão
        session_start();

        // Verifica se sessão não foi iniciada
        if (! isset($_SESSION)) {
            throw new Exception('Não foi possível iniciar a sessão', 100);
        }

        // Somente seta a duração da sessão se tiver sido informada
        if (! isset($_SESSION[ 'timeMin' ]) || ! empty($timeMin)) {
            $this->setTimeMin($timeMin);
        }

        // Inicia a sessão
        $this->setStartSession();
        $_SESSION[ 'sessionStarted' ] = true;
    }

    /**
     * Método Set
     * Cria uma nova chave na sessão.
     * $sessao->attr é o mesmo que $_SESSION['attr']
     *
     * @param string $key   Chave a ser inserida na sessão
     * @param mixed  $value Valor da Chave
     *
     * @return boolean Retorna verdadeiro em caso de sucesso
     */
    public function __set($key, $value)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify() || preg_match('/^(timeMin|startedAt|endedAt|sessionStarted)$/', $key)) {
            return false;
        }

        // Seta o parâmetro serializado dentro da sessão
        $_SESSION[ $key ] = serialize($value);

        return true;
    }

    /**
     * Método Get
     * Pega o conteúdo da chave de uma sessão
     * $sessao->attr é o mesmo que $_SESSION['attr']
     *
     * @param string $key Chave da sessão a ser pega
     *
     * @return mixed Conteúdo da chave
     */
    public function __get($key)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify() || preg_match('/^(timeMin|startedAt|endedAt|sessionStarted)$/', $key)) {
            return null;
        }

        // Retorna o valor desserializado do parâmetro caso ele exista
        if ($this->keyExists($key)) {
            return @unserialize($_SESSION[ $key ]);
        }

        return null;
    }

    /**
     * Método Destrutor
     * Fecha a sessão
     */
    public function __destruct()
    {
        session_write_close();
        self::$instance = null;
    }

    /**
     * Seta a quantidade de tempo de duração da sessão em minutos
     *
     * @param int $timeMin Tempo em minutos
     *
     * @return $this
     */
    public function setTimeMin($timeMin = 0)
    {
        // Se o tempo não foi definido, coloca algo bem longo
        if ((int)$timeMin <= 0) {
            $timeMin = 999999;
        }

        $_SESSION[ 'timeMin' ] = (int)$timeMin;

        return $this;
    }

    /**
     * Inicia/Reinicia a contagem de tempo.
     *
     * @param bool $restart É para forçar reinicio da contagem?
     *
     * @return $this
     */
    public function setStartSession($restart = false)
    {
        // Inicio da sessao já definido ou ordem para reiniciar?
        if (! isset($_SESSION[ 'startedAt' ]) || $restart) {
            // Tempo limite definido?
            if (! isset($_SESSION[ 'timeMin' ])) {
                $this->setTimeMin();
            }

            // Inicializa timing
            $_SESSION[ 'startedAt' ] = time();
            $_SESSION[ 'endedAt' ] = strtotime("+{$_SESSION[ 'timeMin' ]} minutes");
        }

        return $this;
    }

    /**
     * Retorna a data/hora do início da sessão
     *
     * @return string aaaa-mm-dd h24:m:s
     */
    public function getStartedAt()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return '';
        }

        return date('Y-m-d H:i:s', $_SESSION[ 'startedAt' ]);
    }

    /**
     * Retorna a data/hora do fim da sessão
     *
     * @return string aaaa-mm-dd h24:m:s
     */
    public function getEndedAt()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return '';
        }

        return date('Y-m-d H:i:s', $_SESSION[ 'endedAt' ]);
    }

    /**
     * Verifica se a sessão ainda é válida
     *
     * @return bool
     */
    private function verify()
    {
        if (! isset($_SESSION[ 'sessionStarted' ])) {
            return false;
        }

        if (! isset($_SESSION[ 'endedAt' ]) || time() >= $_SESSION[ 'endedAt' ]) {
            $this->destroy();

            return false;
        }

        return true;
    }

    /**
     * Verifica a existência de uma chave
     *
     * @param string $key Chave da sessão
     *
     * @return boolean
     */
    public function keyExists($key)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return false;
        }

        return isset($_SESSION[ $key ]);
    }

    /**
     * Elimina uma chave da sessão
     *
     * @param string $key Chave da sessão a ser eliminada
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function destroyKey($key)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return false;
        }

        // Se a chave não existir, retorna como verdadeiro (Afinal, já está eliminada :)
        if (! $this->keyExists($key)) {
            return true;
        }

        // Elimina a chave se ela existir
        unset($_SESSION[ $key ]);

        return true;
    }

    /**
     * Elimina todas as chaves da sessão
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function destroyAllKeys()
    {
        // Verifica se a sessão foi iniciada
        if (! isset($_SESSION[ 'sessionStarted' ])) {
            return false;
        }

        // Elimina uma a uma das chaves da sesão
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[ $key ]);
        }

        return true;
    }

    /**
     * Destrói a sessão
     *
     * @param bool $saveSession A sessão deve ser salva?
     *
     * @return string|bool Dados codificados da sessão
     */
    public function destroy($saveSession = false)
    {
        // Verifica se a sessão foi iniciada
        if (! isset($_SESSION)) {
            return false;
        }

        // Salva os dados da sessão codificados
        $sessionData = ($saveSession) ? $this->getSessionData() : true;

        // Destrói a sessão após eliminar as chaves
        $this->destroyAllKeys();
        @session_destroy();

        // Desinstancia a classe
        self::$instance =
        self::$sessionName = null;

        // Retorna os dados da sessão
        return $sessionData;
    }

    /**
     * Restaura a sessão
     *
     * @param string $sessionData Dados da sessão codificados
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function restoreSession($sessionData)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return false;
        }

        // Elimina quaisquer chaves ainda existentes na sessão
        $this->destroyAllKeys();

        // Retorna verdadeiro em caso de sucesso
        return session_decode($sessionData);
    }

    /**
     * Pega os dados da sessão
     *
     * @return string
     */
    public function getSessionData()
    {
        return @session_encode();
    }

    /**
     * Retorna o ID da sessão
     *
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Retorna o Nome da Sessão
     *
     * @return string
     */
    public function getSessionName()
    {
        return self::$sessionName;
    }

    /**
     * Retorna Todos os Dados da Sessão
     *
     * @return array Todos os dados desserializados
     */
    public function getAllKeys()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verify()) {
            return [];
        }

        // Lê todas as chaves da sessão
        $return = [];
        foreach ($_SESSION as $key => $value) {
            $return[ $key ] = @unserialize($value);
        }

        // Retorna os dados desserializados
        return $return;
    }
}
