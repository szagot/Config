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
 * @author    Daniel Bispo <szagot@gmail.com>
 * @copyright Copyright (c) 2015
 */

namespace Sz\Config;


use CURLFile;

class HttpRequest
{
    private
        $url,
        $method,
        $headers,
        $bodyContent,
        $file,
        $basicUser,
        $basicPass,
        /** HttpRequestResponse */
        $response,
        $error;

    /**
     * Inicializa a classe setando os atributos principais para a conexão Http
     *
     * @param string $url    URL da Requisição
     * @param string $method Método.
     * @param array  $headers
     * @param string $bodyContent
     * @param string $authType
     * @param string $authUser
     * @param string $authPass
     */
    public function __construct(
        $url = null,
        $method = 'GET',
        array $headers = null,
        $bodyContent = null,
        $authType = null,
        $authUser = null,
        $authPass = null
    ) {
        $this->setUrl($url);
        $this->setMethod($method);
        $this->setHeaders($headers);
        $this->setBodyContent($bodyContent);
        $this->setBasicUser($authUser);
        $this->setBasicPass($authPass);
        $this->response = new HttpRequestResponse();
    }

    /**
     * Efetua a requisição
     * A resposta pode ser obtida utilizando o método getResponse()
     */
    public function execute()
    {
        // Incia a requisição setando parâmetros básicos
        $conection = curl_init();
        curl_setopt($conection, CURLOPT_URL, $this->url);      #URL
        curl_setopt($conection, CURLOPT_TIMEOUT, 30);          #Timeout de 30seg
        curl_setopt($conection, CURLOPT_RETURNTRANSFER, true); #Mostra o resultado real da requisição
        curl_setopt($conection, CURLOPT_MAXREDIRS, 5);
        curl_setopt($conection, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($conection, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // Método
        curl_setopt($conection, CURLOPT_CUSTOMREQUEST, $this->method);

        // Tem header?
        if (count($this->headers ?? []) > 0) {
            curl_setopt($conection, CURLOPT_HTTPHEADER, $this->headers);
        }

        // Tem senha?
        if (!empty($this->basicUser) && !empty($this->basicPass)) {
            curl_setopt($conection, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($conection, CURLOPT_USERPWD, "{$this->basicUser}:{$this->basicPass}");
        }

        // Tem Conteúdo de Body?
        if (!empty($this->bodyContent)) {
            if (!is_string($this->bodyContent) && !$this->getFile()) {
                $this->bodyContent = http_build_query($this->bodyContent);
            }
            curl_setopt($conection, CURLOPT_POST, true);
            curl_setopt($conection, CURLOPT_POSTFIELDS, $this->bodyContent);
        }

        // Resultado
        $this->response->setBody(curl_exec($conection));

        // Status da resposta
        $this->response->setStatus(curl_getinfo($conection, CURLINFO_HTTP_CODE));

        curl_close($conection);

        // Erro?
        if ($this->response->getStatus() < 200 || $this->response->getStatus() > 299) {
            $this->error = 'A requisição retornou um erro ou aviso';
        }

        return $this;
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
     * @return HttpRequest
     */
    public function setUrl($url)
    {
        $this->url = trim((string)$url);
        if (empty($this->url)) {
            $this->error = 'Informe uma URL válida';

            return $this;
        }

        $this->error = null;

        return $this;
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
     *
     * @return HttpRequest
     */
    public function setMethod($method = 'GET')
    {
        $this->method = preg_match('/^(GET|POST|PUT|PATCH|DELETE)$/', $method) ? $method : 'GET';

        return $this;
    }

    /**
     * @param array $headers Headers da requisição
     *
     * @return HttpRequest
     */
    public function setHeaders(array $headers = null)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string|array $bodyContent Conteúdo a ser enviado.
     *                                  Normalmente uma string em JSON ou XML ou parametros em array
     *
     * @return HttpRequest
     */
    public function setBodyContent($bodyContent = null)
    {
        $this->bodyContent = $bodyContent;

        return $this;
    }

    /**
     * Seta o Usuário de uma autenticação do tipo BASIC
     *
     * @param string $basicUser
     *
     * @return HttpRequest
     */
    public function setBasicUser($basicUser = null)
    {
        $this->basicUser = (string)$basicUser;

        return $this;
    }

    /**
     * Seta a Senha de uma autenticação do tipo BASIC
     *
     * @param string $basicPass
     *
     * @return HttpRequest
     */
    public function setBasicPass($basicPass = null)
    {
        $this->basicPass = (string)$basicPass;

        return $this;
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
     * @return string|array
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
     * @return HttpRequestResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return CURLFile|null
     */
    public function getFile(): ?CURLFile
    {
        return $this->file;
    }

    /**
     * Salva o arquivo em formato CURLFile para envio
     *
     * @param string $filePath
     *
     * @return HttpRequest
     */
    public function setFile(string $filePath): HttpRequest
    {
        $contentFile = @file_get_contents($filePath);
        if(empty($contentFile)){
            return $this;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $contentFile);
        finfo_close($finfo);

        $file = new CURLFile($filePath);
        $file->setMimeType($mime);

        $this->file = $file;

        return $this;
    }

}
