# Manual de Instalação Completa - Sistema de Eleição Eclesiástica (Multi-Tenant)

Este documento contém todas as instruções necessárias para instalar o Sistema de Eleição Eclesiástica V3 do zero em uma hospedagem cPanel padrão.

## Requisitos do Servidor
- **PHP:** 8.1 ou superior.
- **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.3+.
- **Extensões PHP:** `pdo_mysql`, `mbstring`, `json`.
- **Servidor Web:** Apache (com `mod_rewrite` ativado).

---

## Passo 1: Preparando o Banco de Dados

1. Acesse seu painel **cPanel**.
2. Vá em **Bancos de Dados MySQL®** (MySQL® Databases).
3. **Crie um Novo Banco de Dados:**
   - Nome: `seuusuario_voto` (ou o nome que preferir).
4. **Crie um Usuário MySQL:**
   - Nome: `seuusuario_admin`
   - Senha: Gere uma senha forte e anote-a.
5. **Vincule o Usuário ao Banco:**
   - Na seção "Adicionar usuário ao banco de dados", selecione o usuário e o banco que acabou de criar.
   - Marque **TODOS OS PRIVILÉGIOS** e clique em Fazer Alterações.
6. Vá até o **phpMyAdmin**.
7. Selecione o banco recém-criado na barra lateral esquerda.
8. Clique na aba **Importar** (Import).
9. Faça o upload do arquivo **`database.sql`** que acompanha este projeto e clique em Executar. (Este arquivo criará todas as tabelas, incluindo a tabela de Igrejas `churches` e injetará a "Igreja Sede" inicial).

---

## Passo 2: Configurando o Aplicativo

1. No cPanel, abra o **Gerenciador de Arquivos** (File Manager).
2. Vá até a pasta pública onde o sistema vai rodar (ex: `public_html/voto` se for usar `seusite.com.br/voto`).
3. Faça o upload de todos os arquivos do projeto para esta pasta (extraia o ZIP, se necessário).
4. Acesse a pasta `app/Config/` e edite o arquivo **`config.php`**:

```php
'app' => [
    'base_path' => dirname(__DIR__, 2),
    // IMPORTANTE: Se o sistema estiver na raiz (public_html), deixe vazio: ''
    // Se estiver em uma subpasta chamada "voto", coloque: '/voto'
    'base_url' => '/voto',
    'env' => 'prod',
    'session_name' => 'ELECTSESSID',
],
'db' => [
    'dsn' => 'mysql:host=localhost;dbname=NOME_DO_BANCO;charset=utf8mb4',
    'user' => 'USUARIO_DO_BANCO',
    'pass' => 'SENHA_DO_BANCO',
],
'security' => [
    // Gere uma string grande e aleatória. NUNCA a altere após a primeira eleição,
    // pois isso invalidará todos os hashes de controle de quem já votou!
    'cpf_pepper' => 'COLAR_UMA_STRING_GIGANTE_E_ALEATORIA_AQUI',
],
```

---

## Passo 3: Permissões de Pasta (storage)

O sistema gerencia sessões localmente para evitar problemas de permissões no cPanel.
1. No Gerenciador de Arquivos, certifique-se de que a pasta `storage/sessions` e `storage/uploads` existam na raiz do seu projeto.
2. Defina as permissões destas pastas (`storage`, `storage/sessions` e `storage/uploads`) para **`0755`** ou **`0777`** (se o servidor for muito restritivo).
3. **Importante:** A pasta `storage/` possui um arquivo `.htaccess` com a diretiva `Require all denied`. **NÃO APAGUE** este arquivo, ele protege os dados de sessão contra acesso público.

---

## Passo 4: Criando o Primeiro Super Administrador

Para acessar o painel pela primeira vez, você precisa criar o usuário Super Admin (que terá permissão para cadastrar novas igrejas).

1. Abra seu navegador e acesse a URL de geração de admin:
   `https://seusite.com.br/voto/gerar_admin.php`
2. Você verá uma tela confirmando o sucesso e exibindo as credenciais padrão:
   - **Email:** `admin@ipccg.org.br`
   - **Senha:** `123456`
3. **MUITO IMPORTANTE:** Após ver a mensagem de sucesso, vá imediatamente ao Gerenciador de Arquivos do cPanel e **EXCLUA o arquivo `gerar_admin.php`** da pasta `public/`. Se você não apagar, qualquer pessoa poderá recriar o acesso admin!

---

## Passo 5: Primeiro Acesso e Teste

1. Acesse o painel administrativo:
   `https://seusite.com.br/voto/admin/login`
2. Na lista suspensa, selecione a **Igreja Sede**.
3. Use o email e senha do Passo 4.
4. Ao entrar, clique no botão azul **"Gerenciar Igrejas"** (visível apenas para o Super Admin).
5. Cadastre as novas igrejas/sociedades conforme a necessidade da sua instituição.
6. A partir deste momento, quando um eleitor for se cadastrar (`/register`), ele verá a lista de igrejas disponíveis para se vincular.

**Instalação Concluída!**
O sistema está pronto para realizar eleições para Pastores, Oficiais, Diretoria e Sociedades, isolando dados por Igreja.
