# Prospect

Gestão de prospecção de clientes com diagnóstico operacional gerado por IA.

O Prospect é uma aplicação web para quem faz prospecção consultiva: você cadastra
os clientes potenciais, acompanha o contato em uma linha do tempo e, a partir do
site e do Instagram do prospect, gera com IA um diagnóstico com hipóteses de
dores operacionais, oportunidades, perguntas de descoberta, lead score e uma
sugestão de mensagem de primeiro contato.

## Funcionalidades

- **Cadastro de prospects**: nome, responsável, WhatsApp, Instagram, e-mail e
  site; canal, datas de contato e resposta; status do retorno.
- **Linha do tempo**: observações datadas de cada interação, editáveis junto do
  prospect.
- **Listagem em cards** com busca, filtros por status e faixa de lead score,
  ordenação e paginação. Os filtros ficam salvos na sessão e são restaurados ao
  voltar para a lista.
- **Modal de detalhes** com canais de contato clicáveis (WhatsApp, e-mail,
  Instagram, site), linha do tempo e diagnóstico.
- **Diagnóstico com IA** (Google Gemini): analisa o conteúdo público do site e
  do Instagram e devolve possíveis dores, oportunidades, perguntas de
  descoberta, lead score (0 a 100) e mensagem de primeiro contato pronta para
  copiar. O contexto da sua empresa no prompt é configurável por instalação.
- **Multiusuário**: cada usuário vê apenas os próprios prospects.
- **Autenticação** com login, registro, recuperação de senha e configurações de
  perfil e segurança, em português.

## Stack

- [Laravel 13](https://laravel.com) (PHP 8.4+), [Fortify](https://laravel.com/docs/fortify)
  e [Wayfinder](https://github.com/laravel/wayfinder)
- [Inertia.js 3](https://inertiajs.com) com [React 19](https://react.dev) e TypeScript
- [Tailwind CSS 4](https://tailwindcss.com) e componentes [shadcn/ui](https://ui.shadcn.com)
- [Vite 8](https://vite.dev) via [Vite+](https://viteplus.dev) (build, lint e formatação)
- MySQL/MariaDB, PostgreSQL ou SQLite
- [Google Gemini API](https://ai.google.dev) para o diagnóstico

Baseado no [Laravel React Starter Kit](https://github.com/laravel/react-starter-kit).

## Requisitos

- PHP 8.4 ou superior, com as extensões padrão do Laravel
- Composer
- Node.js 20 ou superior e npm
- Um banco de dados (MySQL/MariaDB, PostgreSQL ou SQLite)
- Uma chave da API do Gemini, se quiser usar o diagnóstico com IA

## Instalação

```bash
git clone https://github.com/jeferson-guimaraes/prospect.git
cd prospect

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edite o `.env` com os dados do banco (veja [Configuração](#configuração)) e
então:

```bash
php artisan migrate
php artisan db:seed      # cria o usuário inicial
```

Para desenvolver, o comando abaixo sobe o servidor PHP, a fila e o Vite com
hot reload:

```bash
composer run dev
```

A aplicação fica em `http://localhost:8000`.

Para produção, gere os assets com `npm run build` (ou `npm run build:ssr` para
renderização no servidor) e sirva a pasta `public/` com o seu servidor web.

## Configuração

Todas as variáveis abaixo estão documentadas no [`.env.example`](.env.example).

### Banco de dados

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prospect
DB_USERNAME=usuario
DB_PASSWORD=senha
```

Para SQLite basta `DB_CONNECTION=sqlite` e o arquivo `database/database.sqlite`.

### Usuário inicial

O `php artisan db:seed` cria um usuário para o primeiro acesso, a partir de:

```dotenv
SEED_USER_NAME=Administrador
SEED_USER_EMAIL=admin@prospect.local
SEED_USER_PASSWORD=password
```

O seeder é idempotente: se o e-mail já existir, nada é alterado. Troque a senha
antes de rodar fora do ambiente local. Novos usuários também podem se cadastrar
pela tela de registro; para desativar o registro público, remova
`Features::registration()` de [`config/fortify.php`](config/fortify.php).

### Diagnóstico com IA

```dotenv
GEMINI_API_KEY=sua-chave
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=60
```

Sem a chave, o restante do sistema funciona normalmente e o botão "Gerar
diagnóstico" informa que a API não está configurada.

#### Contexto da empresa no prompt

O prompt enviado à IA tem duas partes: um **prompt base** versionado no
repositório ([`resources/prompts/diagnosis/base.md`](resources/prompts/diagnosis/base.md)),
com o papel do consultor, as regras de análise e de pontuação e o formato da
resposta; e o **contexto da empresa**, que descreve quem você é, o que vende,
seu perfil de cliente ideal, sinais de compra, setores prioritários e temas a
evitar.

Por padrão é usado o exemplo genérico em
[`resources/prompts/diagnosis/company-context.example.md`](resources/prompts/diagnosis/company-context.example.md).
Para personalizar:

```bash
php artisan diagnostico:publicar-contexto
```

O comando copia o exemplo para `storage/app/private/diagnosis/company-context.md`
(fora do controle de versão). Edite esse arquivo em Markdown; ele passa a ser
usado no próximo diagnóstico, sem reiniciar nada. Para guardá-lo em outro
lugar, defina `DIAGNOSIS_COMPANY_CONTEXT_PATH`.

Quanto mais fiel o contexto à sua empresa, melhores ficam as hipóteses, o lead
score e a mensagem de primeiro contato.

#### Como o diagnóstico funciona

1. O servidor baixa o site e o perfil público do Instagram informados e extrai
   título, descrição, headings e texto. Só URLs HTTP(S) públicas são acessadas;
   endereços locais e de rede interna são recusados.
2. Monta o prompt com o contexto da empresa, os dados do prospect, as
   observações da linha do tempo e o conteúdo coletado.
3. Chama o Gemini pedindo resposta em JSON e formata o resultado nos campos do
   prospect. Em uma edição, o resultado já é salvo; em um cadastro novo, ele é
   preenchido no formulário e salvo junto com o prospect.

## Desenvolvimento

```bash
composer run dev          # servidor, fila e Vite
php artisan test          # testes (PHPUnit, SQLite em memória)
composer run test         # Pint, PHPStan e testes
npm run check             # lint e formatação do frontend
npm run check:fix         # corrige o que for automático
npm run types:check       # TypeScript
composer run ci:check     # tudo o que a CI roda
```

As rotas do backend chegam ao frontend pelo Wayfinder (`resources/js/routes` e
`resources/js/actions`, gerados automaticamente e fora do git). O servidor do
Vite regenera esses arquivos ao alterar `routes/`; para rotas vindas de
configuração (como as do Fortify), rode `php artisan wayfinder:generate`.

### Estrutura do módulo de prospecção

| Camada   | Arquivos                                                                                                                           |
| -------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Banco    | `database/migrations/*_create_prospeccao_table.php`, `*_create_prospeccao_timelines_table.php`                                     |
| Domínio  | `app/Models/Prospeccao.php`, `app/Models/ProspeccaoTimeline.php`, `app/Enums/`                                                     |
| Regras   | `app/Services/ProspeccaoService.php` (CRUD, filtros e timeline)                                                                    |
| IA       | `app/Services/GeminiService.php`, `ProspectWebContextService.php`, `DiagnosisContextService.php`, `ProspectDiagnosisService.php`   |
| HTTP     | `app/Http/Controllers/ProspeccaoController.php`, `app/Http/Requests/*Prospeccao*`, `GerarDiagnosticoRequest.php`, `routes/web.php` |
| Frontend | `resources/js/pages/prospects/`, `resources/js/components/prospect-*.tsx`, `resources/js/lib/prospect-*.ts`                        |
| Testes   | `tests/Feature/ProspeccaoTest.php`, `ProspectDiagnosisTest.php`, `DiagnosisContextTest.php`, `tests/Unit/`                         |

### Fluxo de trabalho com git

O projeto usa [git flow](https://github.com/petervanderdoes/gitflow-avh):
`main` recebe apenas releases, o desenvolvimento acontece na `develop` e cada
mudança entra por uma `feature/*`. As mensagens de commit são em português,
com verbo no presente descrevendo o que foi implementado (ex.: "Cria migrations
das tabelas prospeccao e prospeccao_timelines").

```bash
git flow feature start minha-feature
# ...commits...
git flow feature finish minha-feature
```

## Licença

Distribuído sob a licença [MIT](LICENSE).
