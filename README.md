# Solana Wallet Dashboard

Laravel 12 + Livewire 3 + Pest (SQLite). Cada usuário tem uma conta PF e quantas contas PJ quiser. Cada conta vincula carteiras Solana e vê saldo, tokens SPL e transações recentes, consultados na API do Solscan.

## Como rodar (sem API key)

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan serve
php artisan test
```

Entre em `/login` com `demo@example.com` / `password` e abra `/accounts`.

O padrão é `SOLSCAN_DRIVER=fake`, que lê as fixtures de `tests/Fixtures/solscan`. Para usar a API real, defina `SOLSCAN_DRIVER=solscan` e `SOLSCAN_API_KEY` no `.env`. A chave nunca vai para o repositório.

## Decisões

- **RN-09, 404 em vez de 403.** Conta ou carteira de outro usuário responde 404. Um 403 confirmaria que o recurso existe para quem tenta adivinhar ids. Toda consulta parte de `auth()->user()->accounts()`, e os ids guardados nos componentes Livewire são `#[Locked]`.
- **RN-09a.** O model `Wallet` não tem relacionamento com contas, então não há como listar quem mais vincula a carteira. A carteira só é acessada por `$account->wallets()`, e o label fica no pivô.
- **RN-09b, desvincular mantém a carteira.** Desvincular remove só a linha do pivô. O endereço é um dado público da blockchain, então guardá-lo não expõe nada, e apagá-lo poderia conflitar com outra conta vinculando no mesmo instante.
- **RN-10, conta na URL.** `/accounts/{account}/wallets/{wallet}`. Não há estado escondido na sessão, cada requisição diz qual conta usa, e é fácil de testar.
- **RN-02 garantida no banco** por um índice único parcial (`WHERE type = 'individual'`), e não só no formulário. A aplicação não expõe ação de excluir conta, então a PF não pode ser excluída.
- **Valores sem float.** `amount_str` e `JSON_BIGINT_AS_STRING` mantêm exatos os inteiros acima de `PHP_INT_MAX`, e o `brick/math` divide por 10^decimais.
- **Integração.** Interface `WalletDataProvider` com drivers `fake` e `solscan`, DTOs, exceções de domínio, timeout explícito, retry só em erro de conexão e 5xx, 429 como indisponibilidade temporária com cooldown, 401 registrado como erro de configuração, cache por endereço (60 s para saldo e tokens, 30 s para transações) e Refresh ignorando o cache.
- **Mensagens de validação** em inglês (`Invalid CPF`, `Invalid CNPJ`, `Invalid Solana address`), como a interface do app. As descrições dos testes estão em português.

## O que ficou de fora e o que faria com mais tempo

- Estrangeiros sem CPF não conseguem se cadastrar, porque a RN-01 exige CPF. Eu tornaria o documento da PF genérico (`cpf` ou `passport`), com unicidade por (tipo, número).
- O índice parcial funciona em SQLite e PostgreSQL, mas não em MySQL, que precisaria de outra solução.
- Produção: `APP_DEBUG=false`, PostgreSQL, Redis para o cache e o cooldown do 429, e rate limiting local nas chamadas ao Solscan.
- Segurança: CPF e CNPJ ficam em texto no banco (criptografia em repouso por causa da LGPD), sem verificação de e-mail, 2FA ou log de auditoria.
- CNPJ alfanumérico, metadados de token e link de contas no menu lateral.