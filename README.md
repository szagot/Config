# Config

> **DEPRECATED!** Utilize no lugar [szagot/helper](https://github.com/szagot/helper)

---

Classes auxiliares de configuração (do tipo HELPER)

- **Session**: Auxilia no gerenciamento de Sessões
- **Uri**: Recupera a URI acessada, juntamente com todos os dados enviados (para consumo de APIs e Webservices)
- **Request**: Efetua requisições/adições de arquivo no código a partir de uma pasta pública. Esses arquivos podem ser tanto linkados como adicionados miniatuarizados no direto no código.
- **HttpRequest**: Efetua requisições Http (para acesso APIs e Webservices)

## Exemplos de uso

Para fazer uso da classe Uri, sugere-se que se desvie todas as chamadas para o arquivo onde a classe será chamada. 
Além disso, se a classe Request for usada em conjunto, é importante antes apontar as chamadas da pasta pública para sua respectiva pasta.
Segue um exemplo de um arquivo *.htaccess*:

```ini
RewriteEngine On

# Área Pública (class Request + Uri)
RewriteRule ^public/?(.*)$ public/$1 [NC,L]

# Envia todo mundo para o index.php (class Uri)
RewriteRule ^/?.*$ index.php [NC,L]
```

Não esqueça de adicionar o comando abaixo no início do script, para não ter que mencionar o namespace das classes toda vez.

```php
use Sz\Config;
```

### Session

```php
// Iniciando uma Sessão
$session = Session::start();

// Gravando na sessão
$session->attr = 'Exemplo';

// Verificando a existencia de um parametro e pegando os dados gravados nele
if( $session->keyExists('attr') )
    echo $session->attr;
    
// Destruindo a sessão
$session->destroy();

// Não é necessário fechar a sessão ao final do script, posto que isso é automático
// Mas caso queira fazer isso antes do final do script, basta apenas...
$session = null;
```

### Uri

```php
// Pegando os dados atuais da URI (inclundo query string e postagens de modo seguro)
$uri = new Uri();

// Você pode definir uma raiz do projeto para que ela seja desconsiderada
// Exemplo http://meusite.com/base_do_projeto/pagina_de_teste
$uri = new Uri('base_do_projeto');

// Verificando se a uri contém WWW. Se não tiver, reinica a página adicionando o WWW
// Naturalmente, nenhum header deve ter sido enviado antes disso
// Para remover o WWW, use $uri->removeWWW();
if( $uri->addWWW() )
    exit;
    
// Pegando e filtrando um POST/GET
$email = $uri->getParam('email', FILTER_EMAIL);

// Pegando POST/GET sem qualquer filtro, jeito 1
$nome = $uri->getParam('nome');

// Pegando POST/GET sem qualquer filtro, jeito 2
$nome = $uri->getParametros()->nome;

// Pegando os detalhes da URI: 
// http://meusite.com/base_do_projeto/pagina_de_teste/opcao/detalhe/outros-0/outros-1/
echo $uri->getPage();           # Imprime 'pagina_de_teste'
echo $uri->getFirstUrlParam();  # Imprime 'opcao'
echo $uri->getSecondUrlParam(); # Imprime 'detalhe'
echo $uri->getNthUrlParam(0);   # Imprime 'outros-0'
echo $uri->getNthUrlParam(1);   # Imprime 'outros-1'

// Pegando a raiz do projeto: 
// http://meusite.com/base_do_projeto/pagina_de_teste/
echo $uri->getRaiz();                                                   # Imprime '/base_do_projeto/'
echo $uri->getRaiz( Uri::INCLUI_SERVER );                               # Imprime '//meusite.com/base_do_projeto/'
echo $uri->getRaiz( Uri::INCLUI_SERVER, Uri::SERVER_COM_PROTOCOLO );    # Imprime 'http://meusite.com/base_do_projeto/'

// Para uso em APIs: Pegando Body da requisição
echo $uri->getBody();

// Para uso em APIs: Pegando o header da requisição
echo $uri->getHeader('authorization');

// Pega os arquivos enviados
var_dump($uri->getFiles());

// Para uso em APIs: Pegando todos os headers da requisição
var_dump($uri->getAllHeaders());
```

### Request

```php
/**
 * As pastas criadas (caso não existam) no momento do instanciamento desta classe serão:
 *      /public/html/
 *      /public/css/
 *      /public/js/
 *      /public/css/
 *      /public/img/
 *      /public/mixed/
 */

// Instancia a classe, setando a raiz para a pasta pública. Se esta não existir, ela é criada
// Nota: durante a execução do script, só é possível setar uma vez a raiz do projeto
$request = Request::iniciar( 'raiz_definida' );  # Se deixar vazio, a raiz será '/' a partir do document root do projeto
$request2 = Request::iniciar( 'mudando_raiz' );  # Isso NÃO irá alterar a raiz da pasta Pública, pois a mesma já foi definida acima.

// Pegando um arquivo do tipo HTML, passando parâmetros.
// Esse arquivo deve estar na pasta /raiz_definida/public/html/
// O código abaixo irá substituir toda a ocorrência de '{{title}}' por 'Título da Página'
// Além de miniaturizar o arquivo (remover quebras de linha e comentários)
$htmlExemplo = $request->getFile( 'header.htm', Request::HTML, ['title' => 'Título da Página] );
```

### HttpRequest

```php
// URI do API
$uri = 'https://api.site.com.br/v1/collection'; 

// Preparando header
$header = [
    'Content-Type: application/json',
    'User-Agent: Exemplo'
];

// Preparando body
$body = [
    'campo1' => 'valor de exemplo',
    'campo2' => 99.9
];

// Enviando requisição do tipo POST com Auth Basic
$envioExemplo = new HttpRequest( $uri, 'POST', $header );
$envioExemplo
    ->setBodyContent(json_encode( $body ))
    ->setBasicUser('usuario')
    ->setBasicPass('senha')
    ->execute();

// Mostrando retorno
var_dump( $envioExemplo->getResponse()->getBody() );
```
