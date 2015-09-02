<?php
/**
 * Classe para envio de Request via Http
 *
 * Exemplos de Uso:
 *      // GET
 *      $hr = new HttpRequest('https://minhaurl.com.br');
 *      $hr->execute();
 *      var_dump( $hr->getResponse() );
 *
 *      // POST
 *      $hr = new HttpRequest('https://minhaurl.com.br', 'POST');
 *      $hr->setHeader([ 'Content-Type: application/json; charset=utf-8' ]);
 *      $hr->setBodyContent('{"Conteudo": "JSON"}');
 *      $hr->setBasicUser('Usuário'); #BASIC Auth
 *      $hr->setBasicPass('Senha'); #BASIC Auth
 *      $hr->execute();
 *      var_dump( $hr->getResponse() );
 *
 * @author    Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2015, TMW E-commerce Solutions
 */

namespace Config;


class HttpRequest
{
    private
        $url,
        $method,
        $headers,
        $bodyContent,
        $basicUser,
        $basicPass,
        $response,
        $error;

    /**
     * Inicializa a classe setando os atributos principais para a conexão Http
     *
     * @param string     $url    URL da Requisição
     * @param string     $method Método.
     * @param array      $headers
     * @param array|null $params
     * @param string     $bodyContent
     * @param string     $authType
     * @param string     $authUser
     * @param string     $authPass
     */
    public function __construct( $url = null, $method = null, array $headers = null, array $params = null, $bodyContent = null, $authType = null, $authUser = null, $authPass = null )
    {
        $this->setUrl( $url );
        $this->setMethod( $method );
        $this->setHeaders( $headers );
        $this->setBodyContent( $bodyContent );
        $this->setBasicUser( $authUser );
        $this->setBasicPass( $authPass );
    }

    /**
     * Efetua a requisição
     * A resposta pode ser obtida utilizando o método getResponse()
     */
    public function execute()
    {
        // Incia a requisição setando parâmetros básicos
        $conection = curl_init();
        curl_setopt( $conection, CURLOPT_URL, $this->url );      #URL
        curl_setopt( $conection, CURLOPT_TIMEOUT, 30 );          #Timeout de 30seg
        curl_setopt( $conection, CURLOPT_RETURNTRANSFER, true ); #Mostra o resultado real da requisição

        // Método
        curl_setopt( $conection, CURLOPT_CUSTOMREQUEST, $this->method );

        // Tem header?
        if ( count( $this->headers ) > 0 ) {
            curl_setopt( $conection, CURLOPT_HTTPHEADER, $this->headers );
        }

        // Tem senha?
        if ( ! empty( $this->basicUser ) && ! empty( $this->basicPass ) ) {
            curl_setopt( $conection, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $conection, CURLOPT_USERPWD, "{$this->basicUser}:{$this->basicPass}" );
        }

        // Tem Conteúdo de Body?
        if ( count( $this->bodyContent ) > 0 ) {
            curl_setopt( $conection, CURLOPT_POST, true );
            curl_setopt( $conection, CURLOPT_POSTFIELDS, $this->bodyContent );
        }

        // Resultado
        $this->response[ 'body' ] = curl_exec( $conection );

        // Status da resposta
        $this->response[ 'status' ] = curl_getinfo( $conection, CURLINFO_HTTP_CODE );

        curl_close( $conection );

        // Erro?
        if ( $this->response[ 'status' ] < 200 || $this->response[ 'status' ] > 299 )
            $this->error = 'A requisição retornou um erro ou aviso';
    }

    /**
     * Pega o erro da requisição
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $url URL/URI da requisição
     *
     * @return boolean
     */
    public function setUrl( $url )
    {
        $this->url = filter_var( trim( (string) $url ), FILTER_VALIDATE_URL );
        if ( empty( $this->url ) ) {
            $this->error = 'Informe uma URL válida';

            return false;
        }

        $this->error = null;

        return true;
    }

    /**
     * Seta o método da requisição, podendo ser:
     *      GET    - Chamadas
     *      POST   - Postagem/Criação
     *      PUT    - Atualização
     *      PATCH  - Atualização parcial de campos
     *      DELETE - Deleção
     *
     * @param string $method Método da requisição
     */
    public function setMethod( $method = 'GET' )
    {
        $this->method = preg_match( '/^(GET|POST|PUT|PATCH|DELETE)$/', $method ) ? $method : 'GET';
    }

    /**
     * @param array $headers Headers da requisição
     */
    public function setHeaders( array $headers = null )
    {
        $this->headers = $headers;
    }

    /**
     * @param string $bodyContent Conteúdo a ser enviado.Normalmente uma string em JSON ou XML.
     */
    public function setBodyContent( $bodyContent = null )
    {
        $this->bodyContent = (string) $bodyContent;
    }

    /**
     * Seta o Usuário de uma autenticação do tipo BASIC
     *
     * @param string $basicUser
     */
    public function setBasicUser( $basicUser = null )
    {
        $this->basicUser = (string) $basicUser;
    }

    /**
     * Seta a Senha de uma autenticação do tipo BASIC
     *
     * @param string $basicPass
     */
    public function setBasicPass( $basicPass = null )
    {
        $this->basicPass = (string) $basicPass;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * @return string
     */
    public function getBasicUser()
    {
        return $this->basicUser;
    }

    /**
     * @return string
     */
    public function getBasicPass()
    {
        return $this->basicPass;
    }

    /**
     * Pega a resposta da requisição em caso de sucesso.
     *
     * @return array No seguinte formado:
     *               [
     *                  'error' => 'Com possíveis erros da requisição ou null em caso negativo',
     *                  'response' => [
     *                      'body' => 'Corpo da resposta',
     *                      'status' => 200 // HTTP Status da requisição
     *                  ]
     *               ]
     */
    public function getResponse()
    {
        return [
            'error'    => $this->error,
            'response' => $this->response
        ];
    }

}