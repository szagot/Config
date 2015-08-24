<?php
/**
 * Classe para Manipulação de URI's
 *
 * @author Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2015, TMW E-commerce Solutions
 */

namespace Config;

class Uri
{
    const
        RETORNO_OBJ = true,
        RETORNO_ARRAY = false;

    private
        $uri,
        $caminho = array(),
        $parametros = array(),
        $body = array(),
        $raiz;

    /**
     * Método Construtor
     *
     * @param string $raiz Raiz da loja
     * @param string $raizLocal Raiz da loja quando executado em localhost
     */
    public function __construct( $raiz = '', $raizLocal = '' )
    {
        // Pega a URI removendo a barra inicial se houver
        $this->uri = preg_replace( '/^\//', '', urldecode( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );

        // Pega o body
        $this->body = json_decode( file_get_contents( 'php://input' ) );

        // Separa os parâmetros (Query String) da URI 
        @list( $caminho, $parametros ) = explode( '?', $this->uri );

        // Remove a Raiz do caminho local quando informada
        if ( ! empty( $raizLocal ) && is_string( $raizLocal ) && $this->eLocal() ) {
            $caminho = preg_replace( '/^' . addcslashes( $raizLocal, '/' ) . '\/?/', '', $caminho );
            $this->raiz = preg_replace( "/(^\/|\/$)/", '', $raizLocal );
        }

        // Remove a Raiz do caminho quando informada
        if ( ! empty( $raiz ) && is_string( $raiz ) ) {
            $caminho = preg_replace( '/^' . addcslashes( $raiz, '/' ) . '\/?/', '', $caminho );
            $this->raiz = ( $this->raiz ? "$this->raiz/" : '' ) . preg_replace( "/(^\/|\/$)/", '', $raiz );
        }

        $this->raiz .= '/';

        // Separa a URI nas suas partes principais
        $caminhoDividido = explode( '/', $caminho );
        if ( count( $caminhoDividido ) > 0 && is_array( $caminhoDividido ) )
            foreach ( $caminhoDividido as $index => $caminho )
                switch ( $index ):
                    case 0:
                        $this->caminho[ 'pagina' ] = $caminho;
                        break;
                    case 1:
                        $this->caminho[ 'opcao' ] = $caminho;
                        break;
                    case 2:
                        $this->caminho[ 'detalhe' ] = $caminho;
                        break;
                    default:
                        $this->caminho[ 'outros' ][] = $caminho;
                endswitch;

        // Pega os parâmetros da Query String (GET)
        if ( isset( $parametros ) && ! empty( $parametros ) )
            foreach ( explode( '&', $parametros ) as $campos ):
                @list( $campo, $valor ) = explode( '=', $campos );
                if ( isset( $campo ) && ! empty( $campo ) )
                    // Se o valor for nulo, recebe true como indicação que o parâmetro existe
                    $this->parametros[ $campo ] = ( $valor === NULL ) ? true : $valor;
            endforeach;

        // Pega os parâmetros da postagem se houver
        $post = filter_input_array( INPUT_POST );
        if( $post )
            $this->parametros= array_merge( $this->parametros, $post );
    }

    /**
     * Verifica se está executando o script localmente
     * @return boolean
     */
    public function eLocal()
    {
        return preg_match( '/localhost|127\.0\.0\.1/i', filter_input( INPUT_SERVER, 'HTTP_HOST' ) );
    }

    /**
     * Adiciona (por padrão) ou remove o WWW da URL
     * @param bool $add = Deve adicionar ou remover o WWW?
     * @return bool O retorno FALSE indica que não foi necessário nenhuma alteração na URL. Evidentemente,
     *              se foi necessária uma alteração, o navegador irá restartar a página.
     */
    public function addWWW( $add = true )
    {

        // Se for local, não faz nada
        if ( $this->eLocal() )
            return false;

        $server = $this->getServer( false );

        // É pra remover o WWW ou pra adicionar?
        if ( $add ) {
            // Possui o WWW?
            if ( ! preg_match( '/^\/{0,2}www\./i', $server ) ) {
                // Tenta redirecionar a URL com WWW
                if ( ! headers_sent() )
                    header( 'Location: ' . preg_replace( '/^(https?:\/\/)/', '$1www.', $this->getServer( true, true ) ) );
                return true;
            }

            return false;
        } else {
            // Não possui o WWW?
            if ( preg_match( '/^\/{0,2}www\./i', $server ) ) {
                // Tenta redirecionar a URL sem WWW
                if ( ! headers_sent() )
                    header( 'Location: ' . preg_replace( '/\/\/www\./i', '//', $this->getServer( true, true ) ) );
                return true;
            }

            return false;
        }
    }

    /**
     * Remove o WWW da URL
     * Obs.: Atalho para $this->addWWW(), porém com parâmetros para remoção do WWW
     */
    public function removeWWW()
    {
        $this->addWWW( false );
    }

    /**
     * Retorna o caminho da URI em um array ou objeto, conforme segue:
     *  URI: http://minhapagina.com/pagina/opcao/detalhe/outros-0/outros-1/?param1=valor
     *      $this->getCaminho()->pagina = Página atual, primeira parte da URI
     *      $this->getCaminho()->opcao = Opções da página, segunda parte da uri
     *      $this->getCaminho()->detalhe = Detalhe da opção, terceira parte da uri
     *      $this->getCaminho()->outros[x] = Da quarta parte em diante é agrupado em outros
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Caminho da URI
     */
    public function getCaminho( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        if ( $obj ) {
            $caminho = new \stdClass;
            foreach ( $this->caminho as $campo => $valor )
                $caminho->$campo = $valor;
        } else
            $caminho = $this->caminho;

        return $caminho;
    }

    /**
     * Retorna os parâmetros (Query String + POST) da URI
     *  URI: http://minhapagina.com/pagina/opcao/detalhe/outros-0/outros-1/?param1=valor
     *      $this->getParametros()->param1 = Pega o valor do param1
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Parâmetros da URI
     */
    public function getParametros( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        if ( $obj ) {
            $parametros = new \stdClass;
            foreach ( $this->parametros as $campo => $valor )
                $parametros->$campo = $valor;
        } else
            $parametros = $this->parametros;

        return $parametros;
    }

    /**
     * Retorna o caminho e mais os parâmetros (Query String + POST) da URI
     * É a soma de $this->getCaminho() e $this->getParametros()
     *  URI: http://minhapagina.com/pagina/opcao/detalhe/outros-0/outros-1/?param1=valor
     *      $this->getUri()->pagina = Página atual, primeira parte da URI
     *      $this->getUri()->opcao = Opções da página, segunda parte da uri
     *      $this->getUri()->detalhe = Detalhe da opção, terceira parte da uri
     *      $this->getUri()->outros[x] = Da quarta parte em diante é agrupado em outros
     *      $this->getUri()->param1 = Pega o valor do param1
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Caminho da URI completo com os parâmetros se houverem
     */
    public function getUri( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        $params = array_merge( $this->caminho, $this->parametros );
        if ( $obj ) {
            $parametros = new \stdClass;
            foreach ( $params as $campo => $valor )
                $parametros->$campo = $valor;
        } else
            $parametros = $params;

        return $parametros;
    }

    /**
     * Retorna o conteúdo do Body em caso de requisição POST via http request
     *
     * @return array Parâmetros da URI
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Pega a raiz da URI, com ou sem servidor
     *
     * @param boolean $comServer = Deve ir com servidor?
     * @param boolean $comProtoloco = Deve ir com protocolo (http|https) ou apenas a indicação de servidor (//)?
     * @return string Raiz
     */
    public function getRaiz( $comServer = false, $comProtoloco = false )
    {
        return
            ( $comServer
                // Com servidor
                ? $this->getServer( $comProtoloco )
                // Apenas raiz
                : preg_replace( '/\/+/', '/', ( '/' . $this->raiz ) )
            );
    }

    /**
     * Pega o servidor da URL
     *
     * @param boolean $comProtoloco = Deve ir com protocolo (http|https) ou apenas a indicação de servidor (//)?
     * @param boolean $comUri = Deve ir com o restante da URI?
     * @return string
     */
    public function getServer( $comProtoloco = false, $comUri = false )
    {

        $protocol = preg_match( '/https/i', filter_input( INPUT_SERVER, 'SERVER_PROTOCOL' ) ) ? 'https://' : 'http://';
        $server = filter_input( INPUT_SERVER, 'HTTP_HOST' ) . '/';

        return
            // Com protocolo?
            ( $comProtoloco ? $protocol : '//' ) .
            // Evita duplicidade nas barras
            preg_replace( '/\/+/', '/', ( $server . ( $comUri ? $this->uri : $this->raiz ) ) );

    }

}