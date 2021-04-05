# API-rest

## Iniciando
Executar o comando  ```docker-compose up ```  na pasta raiz da aplicação para subir container docker com os servidores Node.js, Mysql, Postgresql.

>  - Inserção de contatos
>    - Para cliente Macapá 
>        1. Executar rota ./loginmacapa com requisição POST para obter token JWT
>        2. Executar rota ./addcadastromacapa com body conforme [json](./contacts-macapa.json) e chave token JWT no header.
>    - Para cliente Varejão
>         1. Executar rota ./loginvarejao com requisição POST para obter token JWT
>         2. Executar rota ./addcadastromacapa com body conforme [json](./contacts-varejao.json) e chave token JWT no header.

## Rotas 

####    1. POST ./loginmacapa 
>   - Criação das tabelas predefinidas para o Cliente Macapá
>   - As Retorno token JWT do cliente
   
####   2. POST ./loginvarejao
>   - Criação das tabelas predefinidas para o Cliente Varejão
>   - As Retorno token JWT do cliente

####   3. POST ./addcadastromacapa
>   - inserção do contatos Cliente Macapá
> ###### obs: Informar o token JWT do cliente no header para autenticação 

####   3. POST ./addcadastrovarejao
>   - inserção do contatos Cliente Varejão
> ###### obs: Informar o token JWT do cliente no header para autenticação 
