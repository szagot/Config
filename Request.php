<?php
/**
 * Classe para requisição de arquivos públicos.
 * Minimiza arquivos css/html/js antes de adicionar.
 * Demais arquivos são adicionados com suas tags apropriadas conforme raiz definida.
 *
 * Nota: Se a pasta {raiz}/public (e suas pastas internas) não existirem, a classe tenta criar.
 *
 * @author    Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2015, TMW E-commerce Solutions
 */

namespace Config;

class Request
{
    const
        CSS = 'css/',
        JS = 'js/',
        HTML = 'html/',
        IMG = 'img/',
        MIXED = 'mixed/';

    private static
        $instance;

    private
        $raizBase,
        $raiz,
        $minifyPermissions = [ self::CSS, self::HTML, self::JS ],
        $linkPermissions = [ self::CSS, self::IMG, self::JS, self::HTML, self::MIXED ];

    /**
     * Retorna a instancia da classe.
     * O método Singleton aqui se faz necessário para que haja adição de apenas 1 caminho raiz em todo o script.
     *
     * @param string $raiz Define a raiz do projeto
     *
     * @return Request
     */
    public static function iniciar( $raiz = '/' )
    {
        // Verifica se a classe já foi instanciada
        if ( ! isset( self::$instance ) )
            self::$instance = new self( $raiz );

        // Retorna a instância da classe
        return self::$instance;
    }

    /**
     * Método construtor
     *
     * @param string $raiz
     *
     * @return boolean|Request
     */
    private function __construct( $raiz = '/' )
    {

        // Começa com '/'?
        if ( ! preg_match( '/^\//', $raiz ) )
            $raiz = '/' . $raiz;

        // Termina com '/'?
        if ( ! preg_match( '/\/$/', $raiz ) )
            $raiz .= '/';

        $raizProjeto = $_SERVER[ 'DOCUMENT_ROOT' ] . $raiz;

        // A raiz existe?
        if ( ! is_dir( $raizProjeto ) ) {
            self::$instance = null;

            return false;
        }

        $this->raizBase = (string) $raizProjeto . 'public/';
        $this->raiz = (string) $raiz . 'public/';

        // Se não existir a base, tenta criar
        if ( ! is_dir( $this->raizBase ) ) {
            if ( ! @mkdir( $this->raizBase, 0775 ) ) {
                self::$instance = null;

                return false;
            }
        }

        // Se não existir, cria o diretório para arquivos CSS
        if ( ! is_dir( $this->raizBase . self::CSS ) )
            @mkdir( $this->raizBase . self::CSS, 0775 );
        // Se não existir, cria o diretório para arquivos HTML
        if ( ! is_dir( $this->raizBase . self::HTML ) )
            @mkdir( $this->raizBase . self::HTML, 0775 );
        // Se não existir, cria o diretório para arquivos IMG
        if ( ! is_dir( $this->raizBase . self::IMG ) )
            @mkdir( $this->raizBase . self::IMG, 0775 );
        // Se não existir, cria o diretório para arquivos JS
        if ( ! is_dir( $this->raizBase . self::JS ) )
            @mkdir( $this->raizBase . self::JS, 0775 );
        // Se não existir, cria o diretório para arquivos Genéricos
        if ( ! is_dir( $this->raizBase . self::MIXED ) )
            @mkdir( $this->raizBase . self::MIXED, 0775 );
    }

    /**
     * Retorna a raiz do projeto
     *
     * @param string $type Raiz da pasta pública já com o endereço do tipo d
     *
     * @return string
     */
    public function getRaiz( $type = '' )
    {
        return $this->raiz . ( in_array( $type, $this->linkPermissions ) ? $type : '' );
    }

    /**
     * Retorna o conteúdo de um arquivo.
     * Se for um arquivo html, css ou js irá fazer, por padrão, a miniaturalização do mesmos antes de adicionar.
     * Se houverem parâmetros, tentará substituir no código. Exemplo:
     *      O array informado [ 'nome' => 'Teste' ] servirá para localizar e substituir o texto
     *      {{nome}} por Teste.
     *
     * @param string $fileName   Nome do Arquivo
     * @param string $type       Tipo do arquivo. Usar as constantes da classe.
     * @param array  $parametros Parâmetros de substituição.
     * @param bool   $minify     Deve miniaturalizar os arquivos de código? (Remove espaços, quebras de linha e
     *                           comentários)
     *
     * @return mixed Retorna o conteúdo do arquivo pronto para ser printado
     */
    public function getFile( $fileName, $type = self::MIXED, $parametros = [ ], $minify = true )
    {
        // Caminho completo do arquivo
        $fullPath = $this->raizBase . $type . $fileName;

        // O arquivo existe?
        if ( ! file_exists( $fullPath ) )
            return null;

        $fileContent = file_get_contents( $fullPath );

        // Deve minimizar o arquivo?
        if ( $minify && in_array( $type, $this->minifyPermissions ) ) {

            // Removendo comentários HTML
            if ( $type == self::HTML )
                $fileContent = preg_replace( '/<!--(.*)-->/Uis', '', $fileContent );

            // Removendo comentários CSS ou JS
            if ( $type == self::CSS || $type == self::JS )
                $fileContent = preg_replace( '/\/\*(.*)\*\//Uis', '', $fileContent );

            // Removendo comentários JS de linha
            if ( $type == self::JS )
                $fileContent = preg_replace( '/(?:(?<!\:|\\\)\/\/[^"\'].*)/', '', $fileContent );

            // Removendo espaços extras
            $fileContent = preg_replace( '/[\s\t]+/', ' ', $fileContent );
            // Removendo quebras de linha
            $fileContent = preg_replace( '/[\n\r]+/', '', $fileContent );
        }

        // Possui parâmetros?
        if ( count( $parametros ) > 0 ) {
            // Separa as chaves e as formata
            $chaves = explode( '|', '{{' . implode( '}}|{{', array_keys( $parametros ) ) . '}}' );
            // Separa os valores
            $valores = array_values( $parametros );

            // Faz a substituição
            $fileContent = str_replace( $chaves, $valores, $fileContent );
        }

        // Removendo quaisquer parametros não substituídos
        $fileContent = preg_replace( '/{{(.*)}}/Uis', '', $fileContent );

        // Retornando conteúdo do arquivo CSS
        if ( $type == self::CSS )
            return '<style type="text/css">' . $fileContent . '</style>';

        // Retornando conteúdo do arquivo JS
        elseif ( $type == self::JS )
            return '<script type="text/javascript">' . $fileContent . '</script>';

        // Retornando conteúdo do arquivo IMG
        elseif ( $type == self::IMG ) {

            // Adicionando uma imagem
            $dadosImagem = getimagesize( $fullPath );
            $mime = $dadosImagem[ 'mime' ];

            return '<img src="data: '
            . $mime
            . ';base64,'
            . base64_encode( $fileContent )
            . '" alt="' . $fileName . '" />';

        } else
            // Retornando conteúdo do arquivo genérico
            return $fileContent;
    }

    /**
     * Adiciona o conteúdo de um arquivo no ponto de chamada.
     * Atalho para echo $this->getFile() (A impressão em tela é imediata).
     * Se for um arquivo html, css ou js irá fazer, por padrão, a miniaturalização do mesmos antes de adicionar.
     * Se houverem parâmetros, tentará substituir no código. Exemplo:
     *      O array informado [ 'nome' => 'Teste' ] servirá para localizar e substituir o texto
     *      {{nome}} por Teste.
     *
     * @param string $fileName   Nome do Arquivo
     * @param string $type       Tipo do arquivo. Usar as constantes da classe.
     * @param array  $parametros Parâmetros de substituição.
     * @param bool   $minify     Deve miniaturalizar os arquivos de código? (Remove espaços, quebras de linha e
     *                           comentários)
     */
    public function showFile( $fileName, $type = self::MIXED, $parametros = [ ], $minify = true )
    {
        echo $this->getFile( $fileName, $type, $parametros, $minify );
    }

    /**
     * Retorna o link de requição do arquivo.
     * Se o arquivo não for CSS, JS ou IMG, ele irá adicionar o conteúdo do arquivo em uma <div>
     *
     * @param string $fileName Nome do arquivo
     * @param string $type     Tipo do arquivo. Usar as constantes da classe.
     * @param string $id       ID da tag (não aplicável para JS e CSS)
     * @param string $class    Classe(s) da tag (não aplicável para JS e CSS)
     * @param string $alt      Altertext da imagem (apenas pata IMG)
     *
     * @return string Retorna a requisição
     */
    public function getLinkFile( $fileName, $type = self::MIXED, $id = '', $class = '', $alt = '' )
    {
        // Caminho completo do arquivo
        $fullPath = $this->raizBase . $type . $fileName;
        $path = $this->raiz . $type . $fileName;

        // O arquivo existe?
        if ( ! file_exists( $fullPath ) || ! in_array( $type, $this->linkPermissions ) )
            return '';

        // É arquivo do tipo genérico?
        if ( $type == self::MIXED || $type == self::HTML )
            return "<div id='{$id}' class='{$class}'>{$this->getFile($fileName, $type)}</div>";

        // É imagem?
        elseif ( $type == self::IMG )
            return "<img id='{$id}' class='{$class}' src='{$path}' alt='" . ( $alt != '' ? $alt : $fileName ) . "' />";

        // É CSS?
        elseif ( $type == self::CSS )
            return "<link href='{$path}' rel='stylesheet' type='text/css' />";

        // É JS?
        elseif ( $type == self::JS )
            return "<script src='{$path}' type='text/javascript'></script>";

        else
            return '';

    }

    /**
     * Exibe o link de requição do arquivo.
     * Atalho para echo $this->getLinkFile() (A impressão em tela é imediata).
     * Se o arquivo não for CSS, JS ou IMG, ele irá adicionar o conteúdo do arquivo em uma <div>
     *
     * @param string $fileName Nome do arquivo
     * @param string $type     Tipo do arquivo. Usar as constantes da classe.
     * @param string $id       ID da tag (não aplicável para JS e CSS)
     * @param string $class    Classe(s) da tag (não aplicável para JS e CSS)
     * @param string $alt      Altertext da imagem (apenas pata IMG)
     */
    public function showLinkFile( $fileName, $type = self::MIXED, $id = '', $class = '', $alt = '' )
    {
        echo $this->getLinkFile( $fileName, $type, $id, $class, $alt );
    }

}