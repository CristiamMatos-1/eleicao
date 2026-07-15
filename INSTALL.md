# Manual Completo (GitHub + cPanel) - Sistema de Eleição Multi-Tenant

Este guia cobre:
1. Publicação da estrutura no GitHub.
2. Instalação limpa no cPanel.
3. Migração segura de base já existente.

## Requisitos
- **PHP:** 8.1+
- **Banco:** MySQL 8+ ou MariaDB 10.3+
- **Extensões PHP:** `pdo_mysql`, `mbstring`, `json`
- **Apache:** `mod_rewrite` habilitado

---

## 1) Estrutura de Banco no GitHub

No seu computador local, dentro do projeto:

```bash
git add database.sql multi_tenant_migration.sql INSTALL.md README.md
git commit -m "Estrutura completa do banco multi-tenant e guia cPanel"
git push origin <sua-branch>
```

Depois, abra PR no GitHub para versionar oficialmente o schema.

---

## 2) Instalação Limpa no cPanel (projeto novo)

1. No cPanel, abra **MySQL® Databases**.
2. Crie o banco (`seuusuario_eleicao`) e usuário (`seuusuario_eleicao_usr`).
3. Associe o usuário ao banco com **ALL PRIVILEGES**.
4. Abra **phpMyAdmin**, selecione o banco e use **Importar**.
5. Importe o arquivo **`database.sql`**.
6. No **File Manager**, envie o projeto para `public_html/voto` (ou pasta desejada).
7. Edite `app/Config/config.php`:

```php
'app' => [
    'base_path' => dirname(__DIR__, 2),
    'base_url' => '/voto', // use '' se estiver na raiz
    'env' => 'prod',
    'session_name' => 'ELECTSESSID',
],
'db' => [
    'dsn' => 'mysql:host=localhost;dbname=SEU_BANCO;charset=utf8mb4',
    'user' => 'SEU_USUARIO',
    'pass' => 'SUA_SENHA',
],
'security' => [
    'cpf_pepper' => 'STRING_GRANDE_SECRETA_E_FIXA',
],
```

8. Garanta as pastas `storage/sessions` e `storage/uploads` com permissão `0755` (ou `0777` se o host exigir).
9. Mantenha `storage/.htaccess` com `Require all denied`.
10. Acesse `https://seu-dominio.com/voto/gerar_admin.php` para criar o primeiro admin.
11. **Apague imediatamente** `public/gerar_admin.php` após criar o usuário.

---

## 3) Migração de Banco Existente (sem perder dados)

Se já existe instalação anterior:

1. Faça backup completo no cPanel (**Backup** + export via phpMyAdmin).
2. No phpMyAdmin, selecione o banco atual.
3. Importe **`multi_tenant_migration.sql`**.
4. Valide se as novas estruturas foram criadas:
   - colunas `church_id` em tabelas de votação/escrutínio
   - `uq_vote_control_election_cpf`
   - triggers `trg_*_set_church` e `trg_vote_control_scope`
   - tabela `outbox_events`
5. Limpe cache/sessões e reinicie o ciclo de login.

---

## 4) Checklist de Segurança em Produção

1. Nunca usar usuário root de banco no `config.php`.
2. Todas as consultas de app devem continuar via `PDO::prepare` (sem SQL concatenado).
3. Trocar credenciais padrão de admin após o primeiro login.
4. Desativar `display_errors` em produção.
5. Usar HTTPS obrigatório no domínio.

---

Instalação concluída com schema multi-tenant completo, proteção de voto duplicado por eleição e base preparada para dashboard em tempo real.
