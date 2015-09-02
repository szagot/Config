# Config
Classes auxiliares de configuração (do tipo HELPER)

- <b>HttpRequest</b>: Efetua requisições Http (para acesso APIs e Webservices)
- <b>Request</b>: Efetua requisições/adições de arquivo no código a partir de uma pasta pública. Esses arquivos podem ser tanto linkados como adicionados miniatuarizados no direto no código.
- <b>Uri</b>: Recupera a URI acessada, juntamente com todos os dados enviados (para consumo de APIs e Webservices)
- <b>Sessao</b>: Auxilia no gerenciamento de Sessões

## Obs
Para fazer uso da classe Uri, sugere-se que se desvie todas as chamadas para o arquivo onde a classe será chamada. 
Além disso, se a classe Request for usada em conjunto, é importante antes apontar as chamadas da pasta pública para sua respectiva pasta.
Segue um exemplo de um arquivo <i>.htaccess</i>:
    
    RewriteEngine On
    
    # Área Pública (class Request + Uri)
    RewriteRule ^public/?(.*)$ public/$1 [NC,L]
    
    # Envia todo mundo para o index.php (class Uri)
    RewriteRule ^/?.*$ index.php [NC,L]
