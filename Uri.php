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
        $this->uri = preg_replace( '/^\//', '', urldecode( $_SERVER['REQUEST_URI'] ) );

        // Pega o body
        $this->body = json_decode( file_get_contents('php://input') );

        // Separa os parâmetros (Query String) da URI 
        @list( $caminho, $parametros ) = explode( '?', $this->uri );

        // Remove a Raiz do caminho local quando informada
        if(
            ! empty( $raizLocal ) && is_string( $raizLocal ) &&
            preg_match( '/localhost|127\.0\.0\.1/i', $_SERVER['HTTP_HOST'] )
        ){
            $caminho = preg_replace( '/^' . addcslashes( $raizLocal, '/' ) .'\/?/', '', $caminho );
            $this->raiz = preg_replace( "/(^\/|\/$)/", '', $raizLocal );
        }

        // Remove a Raiz do caminho quando informada
        if( ! empty( $raiz ) && is_string( $raiz ) ){
            $caminho = preg_replace( '/^' . addcslashes( $raiz, '/' ) .'\/?/', '', $caminho );
            $this->raiz = ( $this->raiz ? "$this->raiz/" : '' ) . preg_replace( "/(^\/|\/$)/", '', $raiz );
        }

        $this->raiz .= '/';

        // Separa a URI nas suas partes principais
        $caminhoDividido = explode( '/', $caminho );
        if( count( $caminhoDividido ) > 0 && is_array( $caminhoDividido ) )
            foreach( $caminhoDividido as $index => $caminho )
                switch( $index ):
                    case 0:
                        $this->caminho['pagina'] = $caminho;
                        break;
                    case 1:
                        $this->caminho['opcao'] = $caminho;
                        break;
                    case 2:
                        $this->caminho['detalhe'] = $caminho;
                        break;
                    default:
                        $this->caminho['outros'][] = $caminho;
                endswitch;

        // Pega os parâmetros da Query String (GET)
        if( isset( $parametros ) && ! empty( $parametros ) )
            foreach( explode( '&', $parametros ) as $campos ):
                @list( $campo, $valor ) = explode( '=', $campos );
                if( isset( $campo ) && ! empty( $campo ) )
                    // Se o valor for nulo, recebe true como indicação que o parâmetro existe
                    $this->parametros[ $campo ] = ( $valor === NULL ) ? true : $valor;
            endforeach;

        // Pega os parâmetros da postagem se houver
        if( is_array( $_POST ) && count( $_POST ) > 0 )
            foreach( $_POST as $campo => $valor )
                $this->parametros[ $campo ] = $valor;
    }

    /**
     * Retorna o caminho da URI
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Caminho da URI
     */
    public function getCaminho( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        if( $obj ) {
            $caminho = new \stdClass;
            foreach( $this->caminho as $campo => $valor )
                $caminho->$campo = $valor;
        } else
            $caminho = $this->caminho;

        return $caminho;
    }

    /**
     * Retorna os parâmetros (Query String + POST) da URI
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Parâmetros da URI
     */
    public function getParametros( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        if( $obj ) {
            $parametros = new \stdClass;
            foreach( $this->parametros as $campo => $valor )
                $parametros->$campo = $valor;
        } else
            $parametros = $this->parametros;

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
     * Retorna o caminho e mais os parâmetros (Query String + POST) da URI
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     * @return array Caminho da URI completo com os parâmetros se houverem
     */
    public function getUri( $obj = self::RETORNO_OBJ )
    {
        // Retorno em forma de objeto ou array?
        $params = array_merge( $this->caminho, $this->parametros );
        if( $obj ) {
            $parametros = new \stdClass;
            foreach( $params as $campo => $valor )
                $parametros->$campo = $valor;
        } else
            $parametros = $params;

        return $parametros;
    }

    /**
     * Pega a raiz da URI
     *
     * @return string Raiz
     */
    public function getRaiz()
    {
        return $this->raiz;
    }

}