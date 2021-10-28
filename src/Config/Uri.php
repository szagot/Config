<?php

/**
 * Classe para Manipulação de URI's
 *
 * @author    Daniel Bispo <szagot@gmail.com>
 * @copyright Copyright (c) 2015
 */

namespace Sz\Config;

class Uri
{
    // Tipos de Retorno
    const RETORNO_OBJ = true;
    const RETORNO_ARRAY = false;

    // Parâmetros de Servidor
    const INCLUI_SERVER = true;
    const NAO_INCLUI_SERVER = false;
    const SERVER_COM_PROTOCOLO = true; # http|https
    const SERVER_SEM_PROTOCOLO = false; # http|https
    const SERVER_COM_URI = true; # Com caminho completo
    const SERVER_SEM_URI = false; # Sem caminho completo

    // Parametros para arquivos enviados
    const FILE_NAME = 'name';
    const FILE_TYPE = 'type';
    const FILE_TMP_PATH_NAME = 'tmp_name';
    const FILE_ERROR = 'error';
    const FILE_SIZE = 'size';
    const FILE_SIZE_BYTE = 1;
    // Medidas de retorno
    const FILE_SIZE_KB = 1024;
    const FILE_SIZE_MB = 1024 * 1024;
    const FILE_SIZE_GB = 1024 * 1024 * 1024;

    private $uri;
    private $parametros = [];
    private $body = [];
    private $raiz;
    private $files = [];

    /** @var string 1st param da uri */
    public $pagina;
    /** @var string 2nd param da uri */
    public $opcao;
    /** @var string 3th param da uri */
    public $detalhe;
    /** @var string Others param da uri */
    public $outros = [];

    /**
     * Método Construtor
     *
     * @param string $raiz      Raiz do site
     * @param string $raizLocal Raiz do site quando executado em localhost
     */
    public function __construct($raiz = '', $raizLocal = '')
    {
        // Pega a URI removendo a barra inicial se houver
        $this->uri = preg_replace('/^\//', '', urldecode($_SERVER['REQUEST_URI']));

        // Tenta pegar o body
        $this->body = file_get_contents('php://input') ?? '{}';

        // Separa os parâmetros (Query String) da URI, pegando tudo o que não for GET
        list($caminho) = explode('?', $this->uri);

        // Remove a Raiz do caminho local quando informada
        if (!empty($raizLocal) && is_string($raizLocal) && $this->eLocal()) {
            $caminho = preg_replace('/^' . addcslashes($raizLocal, '/') . '\/?/', '', $caminho);
            $this->raiz = preg_replace("/(^\/|\/$)/", '', $raizLocal);
        }

        // Remove a Raiz do caminho quando informada
        if (!empty($raiz) && is_string($raiz)) {
            $caminho = preg_replace('/^' . addcslashes($raiz, '/') . '\/?/', '', $caminho);
            $this->raiz = ($this->raiz ? "$this->raiz/" : '') . preg_replace("/(^\/|\/$)/", '', $raiz);
        }

        $this->raiz .= '/';

        // Separa a URI nas suas partes principais
        $caminhoDividido = explode('/', $caminho);
        if (count($caminhoDividido) > 0 && is_array($caminhoDividido)) {
            foreach ($caminhoDividido as $index => $caminho) {
                switch ($index):
                    case 0:
                        $this->pagina = $caminho;
                        break;
                    case 1:
                        $this->opcao = $caminho;
                        break;
                    case 2:
                        $this->detalhe = $caminho;
                        break;
                    default:
                        $this->outros[] = $caminho;
                endswitch;
            }
        }

        // Pega os parâmetros get e post se houverem
        $get = filter_input_array(INPUT_GET);
        $post = filter_input_array(INPUT_POST);
        if ($get && $post) {
            $this->parametros = array_merge($get, $post);
        } elseif ($get) {
            $this->parametros = $get;
        } elseif ($post) {
            $this->parametros = $post;
        }

        // Pega os arquivos se houverem
        if (isset($_FILES)) {
            $this->files = $_FILES;
        }
    }

    /**
     * Verifica se está executando o script localmente
     *
     * @return boolean
     */
    public function eLocal()
    {
        return preg_match('/localhost|127\.0\.0\.1/i', $_SERVER['HTTP_HOST']);
    }

    /**
     * Retorna a primeira camada do caminho
     * /page/firstParam/secondParam/nthParams
     *
     * @return string
     */
    public function getPage()
    {
        return $this->pagina;
    }

    /**
     * Retorna a segunda camada do caminho
     * /page/firstParam/secondParam/nthParams
     * 
     * @return string
     */
    public function getFirstUrlParam()
    {
        return $this->opcao;
    }

    /**
     * Retorna a terceira camada do caminho
     * /page/firstParam/secondParam/nthParams
     *
     * @return string
     */
    public function getSecondUrlParam()
    {
        return $this->detalhe;
    }

    /**
     * Retorna da quarta posição em diante
     * /page/firstParam/secondParam/nthParams
     * 
     * @param integer $index
     *
     * @return string
     */
    public function getNthUrlParam(int $index)
    {
        return $this->outros[$index] ?? null;
    }

    /**
     * Adiciona (por padrão) ou remove o WWW da URL
     * Este método deve ser chamado ANTES de qualquer saída em tela
     *
     * @param bool $add Deve adicionar ou remover o WWW?
     *
     * @return bool O retorno FALSE indica que não foi necessário nenhuma alteração na URL. Evidentemente, se foi
     *              necessária uma alteração, o servidor irá restartar a requisição adicionando ou removendo o WWW.
     */
    public function addWWW($add = true)
    {

        // Se for local ou IP, não faz nada
        if ($this->eLocal() || preg_match('/^([0-9]+\.)+[0-9]+(:[0-9]+)?$/', $_SERVER['HTTP_HOST'])) {
            return false;
        }

        $server = $this->getServer();

        // É pra remover o WWW ou pra adicionar?
        if ($add) {
            // Possui o WWW?
            if (!preg_match('/^\/{0,2}www\./i', $server)) {
                // Tenta redirecionar a URL com WWW
                if (!headers_sent()) {
                    header('Location: ' . preg_replace(
                        '/^(https?:\/\/)/',
                        '$1www.',
                        $this->getServer(self::SERVER_COM_PROTOCOLO, self::SERVER_COM_URI)
                    ));
                }

                return true;
            }

            // Não foi necessária alteração
            return false;
        } else {
            // Não possui o WWW?
            if (preg_match('/^\/{0,2}www\./i', $server)) {
                // Tenta redirecionar a URL sem WWW
                if (!headers_sent()) {
                    header('Location: ' . preg_replace(
                        '/\/\/www\./i',
                        '//',
                        $this->getServer(self::SERVER_COM_PROTOCOLO, self::SERVER_COM_URI)
                    ));
                }

                return true;
            }

            // Não foi necessária alteração
            return false;
        }
    }

    /**
     * Remove o WWW da URL
     * Este método deve ser chamado ANTES de qualquer saída em tela
     *
     * Obs.: Atalho para $this->addWWW(), porém com parâmetros para remoção do WWW
     */
    public function removeWWW()
    {
        return $this->addWWW(false);
    }

    /**
     * [DEPRECATED] - Pode-se agora chamar diretamente as propriedades do caminho
     *
     * Retorna o caminho da URI em um array ou objeto, conforme segue:
     *  URI: http://minhapagina.com/pagina/opcao/detalhe/outros-0/outros-1/?param1=valor
     *      $this->getCaminho()->pagina = Página atual, primeira parte da URI
     *      $this->getCaminho()->opcao = Opções da página, segunda parte da uri
     *      $this->getCaminho()->detalhe = Detalhe da opção, terceira parte da uri
     *      $this->getCaminho()->outros[x] = Da quarta parte em diante é agrupado em outros
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ
     *
     * @return array Caminho da URI
     */
    public function getCaminho($obj = self::RETORNO_OBJ)
    {
        // Retorno em forma de objeto ou array?
        if ($obj == self::RETORNO_ARRAY) {
            $caminho = [];
            foreach ($this as $campo => $valor) {
                $caminho[$campo] = $valor;
            }
        } else {
            $caminho = $this;
        }

        return $caminho;
    }

    /**
     * Pega os parâmetros (Query String + POST) da URI de maneira segura, todos com valor convertidos em string
     *
     *  URI: http://minhapagina.com/pagina/opcao/detalhe/outros-0/outros-1/?param1=valor
     *      $this->getParametros()->param1 = Pega o valor do param1
     *
     * @param $obj boolean O retorno deve ser em Objeto ou Array? Padrão = RETORNO_OBJ.
     *             Neste caso será incluído o conteúdo de body
     *
     * @return array Parâmetros da URI
     */
    public function getParametros($obj = self::RETORNO_OBJ)
    {
        // Retorno em forma de objeto ou array?
        if ($obj) {
            $parametros = new \stdClass;
            foreach ($this->parametros as $campo => $valor) {
                $parametros->$campo = $valor;
            }
            // Adicionando conteúdo de body
            $body = $this->getBody();
            if (!empty($body)) {
                foreach ($body as $campo => $valor) {
                    $parametros->$campo = $valor;
                }
            }
        } else {
            $parametros = $this->parametros;
        }

        return $parametros;
    }

    /**
     * Retorna o conteúdo de files
     *
     * @param mixed $name Se informado, devolve apenas o file com o name informado
     * @param string $field
     * @return mixed
     */
    public function getFiles($name = null, string $field = null)
    {
        if ($name) {
            return $field ? $this->files[$name][$field] : $this->files[$name];
        }

        return $this->files;
    }

    /**
     * O arquivo informado existe?
     *
     * @param mixed $name
     * @return boolean
     */
    public function isFileExists($name)
    {
        return isset($this->files[$name]);
    }

    /**
     * Devolve o nome do arquivo enviado
     *
     * @param mixed $name
     * @return string
     */
    public function getFileName($name)
    {
        return $this->files[$name][self::FILE_NAME] ?? null;
    }

    /**
     * Devolve o caminho temporário do arquivo enviado
     *
     * @param mixed $name
     * @return string
     */
    public function getFileTmpPath($name)
    {
        return $this->files[$name][self::FILE_TMP_PATH_NAME] ?? null;
    }

    /**
     * Devolve o tipo do arquivo enviado
     *
     * @param mixed $name
     * @return string
     */
    public function getFileType($name)
    {
        return $this->files[$name][self::FILE_TYPE] ?? null;
    }

    /**
     * Devolve o tamanho do arquivo enviado em bytes (ou na unidade informada)
     *
     * @param mixed $name
     * @param integer $name
     * @return string
     */
    public function getFileSize($name, int $un = self::FILE_SIZE_BYTE)
    {
        if ($un < 1) {
            $un = 1;
        }

        return ($this->files[$name][self::FILE_SIZE] ?? 0) / $un;
    }

    /**
     * Devolve o erro do arquivo enviado
     *
     * @param mixed $name
     * @return string
     */
    public function getFileError($name)
    {
        return $this->files[$name][self::FILE_ERROR] ?? null;
    }

    /**
     * Pega um parâmetro específico do post ou do get de forma segura, priorizando posts.
     * É possível especificar um tipo de filtro para o parâmetro.
     * Caso não especificado, retorna como string por padrão (FILTER_DEFAULT).
     *
     * @param string $param Nome do campo a ser pego
     * @param int    $tipo  Tipo esperado para o valor daquele campo (Ex.: FILTER_VALIDADE_EMAIL)
     *
     * @return bool|mixed Retorna o valor do campo em caso de sucesso ou FALSE em caso de não existir ou não validar
     */
    public function getParam($param, $tipo = FILTER_DEFAULT)
    {
        // Parâmetro não especificado?
        if (!$param) {
            return false;
        }

        // Verifica se tem em Body
        $body = $this->getBody();
        if (isset($body->$param)) {
            return ($tipo == FILTER_VALIDATE_BOOLEAN) ? $this->sanatizeBoolean($body->$param) : $body->$param;
        }

        // Verifica se o parâmetro foi informado na query string
        $get = filter_input(INPUT_GET, (string)$param, $tipo);
        if ($get) {
            return $get;
        }

        if ($this->getMethod() == 'POST') {
            // Verifica se o parâmetro foi postado
            $post = filter_input(INPUT_POST, (string)$param, $tipo);
            if ($post) {
                return $post;
            }

            // Se não encontrou pode ser um array
            $post = filter_input(INPUT_POST, (string)$param, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            if ($post) {
                return $post;
            }
        }

        // Verifica se é de outro tipo
        $postVars = [];
        $this->parseRawHTTPRequest($postVars);
        if (isset($postVars[$param])) {
            return ($tipo == FILTER_VALIDATE_BOOLEAN) ? $this->sanatizeBoolean($postVars[$param]) : $postVars[$param];
        }

        // Não foi encontrado o parâmetro
        return false;
    }

    /**
     * Retorna o conteúdo do Body em caso de requisição POST via http request
     *
     * @param bool $json Converte o conteúdo de JSON para array
     *
     * @return object|string Retorna o Body. Por padrão retorna um array, desde que o conteúdo do body seja JSON
     */
    public function getBody($json = true)
    {
        return $json ? @json_decode($this->body ?? '[]') : $this->body;
    }

    /**
     * Retorna o método da requisição, quando aplicável
     *
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Pega a raiz da URI, com ou sem servidor
     *
     * @param boolean $comServer    Deve ir com servidor?
     * @param boolean $comProtoloco Deve ir com protocolo (http|https) ou apenas a indicação de servidor (//)?
     *
     * @return string Raiz
     */
    public function getRaiz($comServer = self::NAO_INCLUI_SERVER, $comProtoloco = self::SERVER_SEM_PROTOCOLO)
    {
        return ($comServer
            // Com servidor
            ? $this->getServer($comProtoloco)
            // Apenas raiz
            : preg_replace('/\/+/', '/', ('/' . $this->raiz)));
    }

    /**
     * Pega a Uri completa da requisição
     *
     * @param bool $full Mostra URI completa, com os parâmetros?
     *
     * @return mixed
     */
    public function getUri($full = false)
    {
        return $full ? $this->uri : preg_replace('/\?.+$/', '', $this->uri);
    }

    /**
     * Pega todos os parametros enviados no header da requisição
     *
     * @return array
     */
    public function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Pegar um parametro específico do header da requisição
     *
     * @param string $header
     * @return mixed
     */
    public function getHeader(string $header)
    {
        return $this->getAllHeaders()[$header] ?? null;
    }

    /**
     * Pega o servidor da URL
     *
     * @param boolean $comProtoloco Deve ir com protocolo (http|https) ou apenas a indicação de servidor (//)?
     * @param boolean $comUri       Deve ir com o restante da URI?
     *
     * @return string
     */
    public function getServer($comProtoloco = self::SERVER_SEM_PROTOCOLO, $comUri = self::SERVER_SEM_URI)
    {

        $protocol = preg_match('/https/i', $_SERVER['SERVER_PROTOCOL']) ? 'https://' : 'http://';
        $server = $_SERVER['HTTP_HOST'] . '/';

        return
            // Com protocolo?
            ($comProtoloco ? $protocol : '//') .
            // Evita duplicidade nas barras
            preg_replace('/\/+/', '/', ($server . ($comUri ? $this->uri : $this->raiz)));
    }

    /**
     * Converte valores humanos para positivo e negativo para booleano
     *
     * @return bool|null
     */
    private function sanatizeBoolean($bool)
    {
        $trueValidate = [1, 'sim', 'true', 'yes', 'positivo', 'aceito', 'allowed', 'allow', 'permitir', 'ok'];
        $falseValidate = [0, 'não', 'nao', 'false', 'no', 'negativo', 'negado', 'denied', 'deny', 'negar', 'not'];

        if (is_string($bool)) {
            $bool = trim(mb_strtolower($bool, 'UTF-8'));
        }

        if (in_array($bool, $trueValidate)) {
            return true;
        }

        if (in_array($bool, $falseValidate)) {
            return false;
        }

        return null;
    }

    /**
     * Pega chamadas do tipo PUT, PATCH, etc...
     *  Fonte: https://stackoverflow.com/questions/5483851/manually-parse-raw-multipart-form-data-data-with-php/5488449#5488449
     */
    private function parseRawHTTPRequest(array &$a_data)
    {
        // read incoming data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block) {
            if (empty($block))
                continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE) {
                // match "name", then everything after "stream" (optional) except for prepending newlines 
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
            // parse all other fields
            else {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $a_data[$matches[1]] = $matches[2];
        }
    }
}
