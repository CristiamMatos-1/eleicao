# eleicao

Sistema de eleição eclesiástica multi-tenant (isolamento por igreja/empresa).

## Estrutura de banco

- `database.sql`: schema completo para instalação nova.
- `multi_tenant_migration.sql`: upgrade de bases já existentes sem reset.

## Principais proteções implementadas no schema

- Isolamento de tenant por `church_id` nas entidades críticas.
- Bloqueio de voto duplicado por eleição via `uq_vote_control_election_cpf (election_id, cpf_hash)`.
- Índices para alta concorrência nas tabelas de votos/escrutínio.
- Triggers para propagar automaticamente o tenant em inserts de votação.
- Outbox (`outbox_events`) para suportar dashboard em tempo real.

## Instalação

Consulte `INSTALL.md` para:
1. Publicação no GitHub.
2. Instalação limpa no cPanel.
3. Migração de banco existente.
