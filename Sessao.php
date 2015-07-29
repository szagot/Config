<?php
/**
 * Classe administradora de Sessões
 *
 * Inicia uma seção: $sessao = Sessao::iniciar();
 * Exemplo de SET: $sessao->attr = 'Exemplo';
 * Exemplo de GET: echo $sessao->attr;
 * Encerra seção: $sessao = NULL;
 *
 * @author Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2015, TMW E-commerce Solutions
 */
namespace Config;

use \Exception;

class Sessao
{
    private static 
        $instance,                  # Guarda a instância da classe
        $nomeSessao,                # Guarda o nome da sessão sem o hash
        $sessaoIniciada = false;    # Verfica se a sessão foi iniciada

    /**
     * Gera a Instância da Sessão
     * @param string $id Define o ID da sessão
     * @param integer $tempoHoras Duração da sessão em horas
     * @return Sessao
     */
    private static function getInstance( $id, $tempoHoras )
    {
        // Verifica se a classe já foi instanciada
        if( ! isset( self::$instance ) )
            self::$instance = new self( $id, $tempoHoras );
        
        // Retorna a instância da classe            
        return self::$instance;
    }

    /**
     * Inicia uma sessão - Atalho para getInstance
     * @param string $id Define o ID da sessão
     * @param integer $tempoHoras Duração da sessão em horas
     * @return Sessao
     */
    public static function iniciar( $id = NULL, $tempoHoras = 12 )
    {
        return self::getInstance( $id, $tempoHoras );
    }

    /**
     * Método Construtor
     * Inicia uma sessão
     * @param string $id Id da sessão
     * @param integer $tempoHoras Duração da sessão em horas
     */
    private function __construct( $id, $tempoHoras )
    {
        // Criando pasta da sessão, se não existir
        $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
        if( ! file_exists( $sessionPath ) )
            mkdir( $sessionPath );
        
        // Setando a pasta da sessão, se existir
        if( file_exists( $sessionPath ) )
            ini_set('session.save_path', $sessionPath);
        
        // Setando duração da sessão
        ini_set('session.gc_maxlifetime', $tempoHoras * 3600);

        // Define o nome da sessão
        self::$nomeSessao = 'L0j45'
                          . DIRECTORY_SEPARATOR
                          //  IP do usuário
                          . $_SERVER['REMOTE_ADDR'] 
                          . DIRECTORY_SEPARATOR
                          . 'TMWxD' 
                          . DIRECTORY_SEPARATOR
                            // Dados do navegador do usuário
                          . $_SERVER['HTTP_USER_AGENT'] 
                          . DIRECTORY_SEPARATOR
                          // ID da sessão caso seja definido
                          . $id;
                          
        session_name( md5( self::$nomeSessao ) );
        session_id( md5( self::$nomeSessao ) );
        
        // Inicia a sessão
        session_start();
        
        // Verifica se sessão não foi iniciada
        if( ! isset( $_SESSION ) )
            throw new Exception( 'Não foi possível iniciar a sessão', 100 );
        
        self::$sessaoIniciada = true;
    }
    
    /**
     * Método Set
     * Cria uma nova chave na sessão. 
     * $sessao->attr é o mesmo que $_SESSION['attr']
     * @return boolean Retorna verdadeiro em caso de sucesso
     */
    public function __set( $chave, $valor )
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return false;
        
        // Seta o parâmetro serializado dentro da sessão
        $_SESSION[ $chave ] = serialize( $valor );
        
        return true;
    }
    
    /**
     * Método Get
     * Pega o conteúdo da chave de uma sessão
     * $sessao->attr é o mesmo que $_SESSION['attr']
     * @return mixed Conteúdo da chave
     */
    public function __get( $chave )
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return NULL;
         
        // Retorna o valor desserializado do parâmetro caso ele exista
        if( $this->chaveExiste( $chave ) )
            return @unserialize( $_SESSION[ $chave ] );
        
        return NULL;
    }
    
    /**
     * Método Destrutor
     * Fecha a sessão
     */
    public function __destruct()
    {
        session_write_close();
        self::$sessaoIniciada = false;
        self::$instance = NULL;
    }
    
    /**
     * Verifica a existência de uma chave
     * @param string $chave  Chave da sessão
     * @return boolean
     */
    public function chaveExiste( $chave )
    {
        return isset( $_SESSION[ $chave ] );
    }
    
    /**
     * Elimina uma chave da sessão
     * @param string $chave Chave da sessão a ser eliminada
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function eliminaChave( $chave )
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return false;
        
        // A chave existe?
        if( ! $this->chaveExiste( $chave ) )
            throw new Exception( "A chave da sessão $chave é inexistente.", 101 );
        
        // Elimina a chave se ela existir
        unset( $_SESSION[ $chave ] );
        return true;
    }
    
    /**
     * Elimina todas as chaves da sessão
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function eliminaTodasChaves()
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return false;
            
        // Elimina uma a uma das chaves da sesão 
        foreach( $_SESSION as $chave => $valor )
            unset( $_SESSION[ $chave ] );
        
        return true;
    }
    
    /**
     * Destrói a sessão
     * @return string Dados codificados da sessão
     */
    public function destruir()
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return false;
        
        // Salva os dados da sessão codificados
        $dadosSessao = session_encode();
        
        // Destrói a sessão após eliminar as chaves
        $this->eliminaTodasChaves();
        session_destroy();  
        
        // Desinstancia a classe
        self::$instance = 
        self::$nomeSessao = NULL;
        
        // Retorna os dados da sessão
        return $dadosSessao;
    }
    
    /**
     * Restaura a sessão
     * @param string $dadosSessao Dados da sessão codificados
     * @return boolean Verdadeiro em caso de sucesso
     */
    public function restaurar( $dadosSessao )
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return false;
            
        // Elimina quaisquer chaves ainda existentes na sessão
        $this->eliminaTodasChaves();
        
        // Retorna verdadeiro em caso de sucesso
        return session_decode( $dadosSessao );
    }

    /**
     * Retorna o ID da sessão
     * @return string
     */
    public function getId()
    {
        return session_id();
    }
    
    /**
     * Retorna o Nome da Sessão
     * @return string
     */
    public function getNome()
    {
        return self::$nomeSessao;
    }
    
    /**
     * Retorna Todos os Dados da Sessão
     * @return array Todos os dados desserializados
     */
    public function getDados()
    {
        // Verifica se a sessão foi iniciada
        if( ! self::$sessaoIniciada )
            return array();
        
        // Lê todas as chaves da sessão
        $retorno = array();
        foreach( $_SESSION as $chave => $valor )
            $retorno[ $chave ] = unserialize( $valor );
        
        // Retorna os dados desserializados    
        return $retorno;
    }
}