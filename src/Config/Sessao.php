<?php
/**
 * Classe administradora de Sessões
 *
 *      Inicia uma seção: $sessao = Sessao::iniciar();
 *      Exemplo de SET: $sessao->attr = 'Exemplo';
 *      Exemplo de GET: echo $sessao->attr;
 *      Encerra seção: $sessao = NULL;
 *
 * @author    Daniel Bispo <szagot@gmail.com>
 * @copyright Copyright (c) 2015
 */

namespace Sz\Config;

use \Exception;

class Sessao
{
    private static
        $instance,                  # Guarda a instância da classe
        $nomeSessao;                # Guarda o nome da sessão sem o hash

    private
        $sessaoIniciada = false;    # Verifica se a sessão foi iniciada


    /**
     * Inicia uma sessão
     *
     * @param string  $id       Define o ID da sessão
     * @param integer $tempoMin Duração da sessão em minutos (12h por padrao)
     *
     * @return Sessao
     */
    public static function iniciar($id = null, $tempoMin = 720)
    {
        // Verifica se a classe já foi instanciada
        if (! isset(self::$instance)) {
            self::$instance = new self($id, $tempoMin);
        }

        // Retorna a instância da classe
        return self::$instance;
    }

    /**
     * Método Construtor
     * Inicia uma sessão
     *
     * @param string  $id       Id da sessão
     * @param integer $tempoMin Duração da sessão em min
     *
     * @throws Exception Não iniciou a sessão
     */
    private function __construct($id, $tempoMin)
    {
        // Criando pasta da sessão, se não existir
        $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
        if (! file_exists($sessionPath)) {
            mkdir($sessionPath);
        }

        // Setando a pasta da sessão, se existir
        if (file_exists($sessionPath)) {
            ini_set('session.save_path', $sessionPath);
        }

        // Setando duração da sessão em segundos
        ini_set('session.cookie_lifetime', $tempoMin * 60);
        ini_set('session.gc_maxlifetime', $tempoMin * 60);

        // Define o nome da sessão
        self::$nomeSessao = 'Sz4g0t' . DIRECTORY_SEPARATOR
            //  IP do usuário
            . $_SERVER[ 'REMOTE_ADDR' ] . DIRECTORY_SEPARATOR
            . 'TMWxD' . DIRECTORY_SEPARATOR
            // Dados do navegador do usuário
            . $_SERVER[ 'HTTP_USER_AGENT' ] . DIRECTORY_SEPARATOR
            // ID da sessão caso seja definido
            . $id;

        session_name(md5(self::$nomeSessao));
        session_id(md5(self::$nomeSessao));

        // Inicia a sessão
        session_start();

        // Verifica se sessão não foi iniciada
        if (! isset($_SESSION)) {
            throw new Exception('Não foi possível iniciar a sessão', 100);
        }

        $this->setTempoMin($tempoMin);
        $this->setInicioSessao();
        $this->sessaoIniciada = true;
    }

    /**
     * Método Set
     * Cria uma nova chave na sessão.
     * $sessao->attr é o mesmo que $_SESSION['attr']
     *
     * @param string $chave Chave a ser inserida na sessão
     * @param mixed  $valor Valor da Chave
     *
     * @return boolean Retorna verdadeiro em caso de sucesso
     */
    public function __set($chave, $valor)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->sessaoIniciada) {
            return false;
        }

        // Seta o parâmetro serializado dentro da sessão
        $_SESSION[ $chave ] = serialize($valor);

        return true;
    }

    /**
     * Método Get
     * Pega o conteúdo da chave de uma sessão
     * $sessao->attr é o mesmo que $_SESSION['attr']
     *
     * @param string $chave Chave da sessão a ser pega
     *
     * @return mixed Conteúdo da chave
     */
    public function __get($chave)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->sessaoIniciada) {
            return null;
        }

        // Retorna o valor desserializado do parâmetro caso ele exista
        if ($this->chaveExiste($chave)) {
            return @unserialize($_SESSION[ $chave ]);
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
        $this->sessaoIniciada = false;
        self::$instance = null;
    }

    /**
     * Seta a quantidade de tempo de duração da sessão em minutos
     *
     * @param int $tempoMin Tempo em minutos
     */
    public function setTempoMin($tempoMin = 0)
    {
        // Se o tempo não foi definido, coloca algo bem longo
        if ((int)$tempoMin <= 0) {
            $tempoMin = 999999;
        }

        $_SESSION[ 'tempoMin' ] = (int)$tempoMin;
    }

    /**
     * Inicia/Reinicia a contagem de tempo.
     *
     * @param bool $reiniciar É para forçar reinicio da contagem?
     */
    public function setInicioSessao($reiniciar = false)
    {
        // Inicio da sessao já definido ou ordem para reiniciar?
        if (! isset($_SESSION[ 'inicioSessao' ]) || $reiniciar) {
            // Tempo limite definido?
            if (! isset($_SESSION[ 'tempoMin' ])) {
                $this->setTempoMin();
            }

            // Inicializa timing
            $_SESSION[ 'inicioSessao' ] = time();
            $_SESSION[ 'fimSessao' ] = strtotime("+{$_SESSION['tempoMin']} minutes");
        }
    }

    /**
     * Retorna a data/hora do início da sessão
     *
     * @return string aaaa-mm-dd h24:m:s
     */
    public function getInicioSessao()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return '';
        }

        return date('Y-m-d H:i:s', $_SESSION[ 'inicioSessao' ]);
    }

    /**
     * Retorna a data/hora do fim da sessão
     *
     * @return string aaaa-mm-dd h24:m:s
     */
    public function getFimSessao()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return '';
        }

        return date('Y-m-d H:i:s', $_SESSION[ 'fimSessao' ]);
    }

    /**
     * Verifica se a sessão ainda é válida
     *
     * @return bool
     */
    private function verificaSessao()
    {
        if (! $this->sessaoIniciada) {
            return false;
        }

        if (! isset($_SESSION[ 'fimSessao' ]) || time() >= $_SESSION[ 'fimSessao' ]) {
            $this->destruir();

            return false;
        }

        return true;
    }

    /**
     * Verifica a existência de uma chave
     *
     * @param string $chave Chave da sessão
     *
     * @return boolean
     */
    public function chaveExiste($chave)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return false;
        }

        return isset($_SESSION[ $chave ]);
    }

    /**
     * Elimina uma chave da sessão
     *
     * @param string $chave Chave da sessão a ser eliminada
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function eliminaChave($chave)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return false;
        }

        // Se a chave não existir, retorna como verdadeiro (Afinal, já está eliminada :)
        if (! $this->chaveExiste($chave)) {
            return true;
        }

        // Elimina a chave se ela existir
        unset($_SESSION[ $chave ]);

        return true;
    }

    /**
     * Elimina todas as chaves da sessão
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function eliminaTodasChaves()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->sessaoIniciada) {
            return false;
        }

        // Elimina uma a uma das chaves da sesão
        foreach ($_SESSION as $chave => $valor) {
            unset($_SESSION[ $chave ]);
        }

        return true;
    }

    /**
     * Destrói a sessão
     *
     * @param bool $salvarSessao A sessão deve ser salva?
     *
     * @return string|bool Dados codificados da sessão
     */
    public function destruir($salvarSessao = false)
    {
        // Verifica se a sessão foi iniciada
        if (! isset($_SESSION)) {
            return false;
        }

        // Salva os dados da sessão codificados
        $dadosSessao = ($salvarSessao) ? $this->pegaDadosSessao() : true;

        // Destrói a sessão após eliminar as chaves
        $this->eliminaTodasChaves();
        @session_destroy();

        // Desinstancia a classe
        self::$instance =
        self::$nomeSessao = null;

        // Retorna os dados da sessão
        return $dadosSessao;
    }

    /**
     * Restaura a sessão
     *
     * @param string $dadosSessao Dados da sessão codificados
     *
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function restaurar($dadosSessao)
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return false;
        }

        // Elimina quaisquer chaves ainda existentes na sessão
        $this->eliminaTodasChaves();

        // Retorna verdadeiro em caso de sucesso
        return session_decode($dadosSessao);
    }

    /**
     * Pega os dados da sessão
     *
     * @return string
     */
    public function pegaDadosSessao()
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
    public function getNome()
    {
        return self::$nomeSessao;
    }

    /**
     * Retorna Todos os Dados da Sessão
     *
     * @return array Todos os dados desserializados
     */
    public function getDados()
    {
        // Verifica se a sessão foi iniciada
        if (! $this->verificaSessao()) {
            return [];
        }

        // Lê todas as chaves da sessão
        $retorno = [];
        foreach ($_SESSION as $chave => $valor) {
            $retorno[ $chave ] = @unserialize($valor);
        }

        // Retorna os dados desserializados
        return $retorno;
    }
}