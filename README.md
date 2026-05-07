<div align="center">

# Estoque MH

**Sistema web corporativo para controle de estoque de TI, patrimônio em uso, movimentações, usuários, localizações e exportações para BI.**

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-10.x-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Compatible-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-Dependency%20Manager-885630?style=for-the-badge&logo=composer&logoColor=white)
![PHPMailer](https://img.shields.io/badge/PHPMailer-7.0.1-2B6CB0?style=for-the-badge)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=111)
![CSS3](https://img.shields.io/badge/CSS3-Responsive-1572B6?style=for-the-badge&logo=css3&logoColor=white)

</div>

---

## Visão Geral

O **Estoque MH** é uma aplicação PHP procedural para operação interna de estoque de TI. O sistema centraliza cadastro de itens, categorias hierárquicas, locais físicos, usuários por setor, movimentações de entrada/saída e itens já alocados em uso.

A solução foi desenhada para ambiente Apache/XAMPP, com persistência em MySQL/MariaDB e interface web responsiva em PHP, CSS e JavaScript vanilla. Também fornece exportações CSV para análise operacional e consumo por ferramentas de BI.

---

## Funcionalidades

### Operação de Estoque

- Cadastro, edição e exclusão de itens de estoque.
- Categorias e subcategorias com quantidade mínima configurável.
- Controle de quantidade atual por item.
- Status calculado no banco para estoque `Normal`, `Baixo` e `Zerado`.
- Filtros por busca, categoria, subcategoria, localização, status e paginação.
- KPIs de total de itens, categorias, itens em baixo estoque e itens zerados.
- Upload e visualização de fotos de localização.
- Redimensionamento/compressão de imagens via GD quando disponível.

### Movimentações

- Registro de entrada e saída de itens.
- Validação de estoque insuficiente em saídas.
- Registro histórico em `movimentacoes`.
- Registro de destinos por setor em `movimentacoes_destinos`.
- Geração de logs de auditoria em `logs`.
- Envio de notificações por e-mail via SMTP/PHPMailer.

### Itens em Uso

- Cadastro de patrimônios/equipamentos em uso.
- Associação de item a setor.
- Controle de status ativo/desativado.
- Edição, exclusão e detalhamento de patrimônio.
- Filtros por item, setor, categoria e status.

### Usuários e Autenticação

- Login com sessão PHP.
- Verificação de senha com `password_verify`.
- Cadastro e edição de usuários.
- Hash de senha com `password_hash`.
- Controle de perfil `admin` e `user`.
- Proteção de páginas internas por `ensureLoggedInUser`.
- Token CSRF no formulário de login.
- Foto de perfil armazenada em BLOB no banco.

### Relatórios e BI

- Dashboard dedicado a exportações.
- Exportação CSV de estoque atual.
- Exportação CSV de movimentações por período, item, usuário e tipo.
- Exportação CSV de logs.
- Saídas em UTF-8 com BOM e delimitador `;`, compatíveis com Excel/Power BI.
- Queries adaptativas com validação de schema antes da exportação.

---

## Arquitetura

Aplicação web monolítica em PHP procedural, organizada por páginas renderizadas no servidor e endpoints PHP para ações de escrita, consultas AJAX, exportação e manipulação de arquivos.

```text
estoquemh/
├── config/
│   └── email.php
├── css/
│   └── style.css
├── img/
│   ├── cancel.png
│   ├── check.png
│   ├── editar.png
│   ├── excluir.png
│   ├── info.png
│   ├── loc.png
│   └── move.png
├── js/
│   ├── camera-upload.js
│   ├── cards-interativo.js
│   ├── cascading_select.js
│   ├── categoria.js
│   ├── file-upload-enhanced.js
│   ├── modal.js
│   ├── responsive.js
│   └── table-enhanced.js
├── pages/
│   ├── em-uso.php
│   ├── login.php
│   ├── relatorio_bi_dashboard.php
│   ├── sistema.php
│   └── usuario.php
├── php/
│   ├── add_em_uso.php
│   ├── add_location.php
│   ├── addcategoria.php
│   ├── additem.php
│   ├── editaritem.php
│   ├── envio_entrada_email.php
│   ├── envio_saida_email.php
│   ├── exibir_foto.php
│   ├── logs_exportar.php
│   ├── movimento.php
│   ├── relatorio_bi_estoque.php
│   ├── usuario_salvar.php
│   └── visualizar_logs.php
├── sql/
│   └── estoque.sql
├── uploads/
│   └── .gitkeep
├── views/
│   └── auth/
│       └── login.php
├── composer.json
├── cone.php
├── helpers.php
├── image_handler.php
├── index.php
├── locations_crud.php
└── status.html
```

### Responsabilidades

| Área | Responsabilidade |
| --- | --- |
| `pages/` | Telas principais renderizadas no servidor: estoque, em uso, usuários, login e BI. |
| `php/` | Endpoints e actions para CRUD, movimentação, upload, e-mail, relatórios e logs. |
| `js/` | Interações de UI: modais, responsividade, upload com câmera, filtros e selects hierárquicos. |
| `css/` | Design responsivo, tema visual escuro e componentes da interface. |
| `config/` | Configuração SMTP externalizada por ambiente. |
| `sql/` | Dump de estrutura e dados do banco MariaDB/MySQL. |
| `uploads/` | Diretório de runtime para imagens enviadas pelo usuário. |
| `vendor/` | Dependências Composer instaladas localmente. Não deve ser versionado. |

---

## Tecnologias

| Tecnologia | Uso no projeto | Referência |
| --- | --- | --- |
| PHP | Backend, renderização server-side, sessões, endpoints e regras de negócio. | https://www.php.net/ |
| MySQL/MariaDB | Persistência relacional, constraints, índices e status gerado de estoque. | https://mariadb.org/ |
| Composer | Gerenciamento da dependência PHPMailer. | https://getcomposer.org/ |
| PHPMailer `v7.0.1` | Envio de e-mails SMTP para notificações de movimentação. | https://github.com/PHPMailer/PHPMailer |
| JavaScript Vanilla | Modais, filtros, AJAX, câmera/upload e interações responsivas. | https://developer.mozilla.org/docs/Web/JavaScript |
| CSS3 | Interface responsiva, dark UI, cards, tabelas e formulários. | https://developer.mozilla.org/docs/Web/CSS |
| Apache/XAMPP | Ambiente local esperado pelo uso de `/estoquemh` e `$_SERVER['DOCUMENT_ROOT']`. | https://www.apachefriends.org/ |

Não foram detectados frameworks como Laravel, Symfony, React, Vue ou Angular. Também não foram detectados Docker, filas, cache dedicado, WebSocket, serviços de IA, workers/background jobs ou suíte automatizada de testes.

---

## Como Executar

### Pré-requisitos

- PHP 8.x com extensões `mysqli`, `fileinfo` e, opcionalmente, `gd`.
- Apache apontando para `htdocs`.
- MySQL ou MariaDB.
- Composer.
- Ambiente local compatível com o path `/estoquemh` usado nas rotas.

### Instalação

```bash
git clone <url-do-repositorio> estoquemh
cd estoquemh
composer install
```

### Configuração

Crie um arquivo `.env` com base em `.env.example`:

```bash
cp .env.example .env
```

Configure as credenciais de banco e SMTP no `.env`.

### Banco de Dados

Crie o banco e importe o dump:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS estoqueti CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p estoqueti < sql/estoque.sql
```

### Execução Local

Com XAMPP/Apache ativo, acesse:

```text
http://localhost/estoquemh/
```

O `index.php` redireciona para:

```text
/estoquemh/pages/sistema.php
```

### Docker

Não há `Dockerfile`, `docker-compose.yml` ou configuração Docker detectada neste repositório.

### Build

Não há etapa de build frontend/backend. CSS e JavaScript são servidos diretamente.

### Testes

Não há PHPUnit, Pest, Codeception ou outra suíte automatizada detectada. A validação básica disponível é lint de PHP:

```bash
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

No Windows PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php -File |
  Where-Object { $_.FullName -notmatch '\\vendor\\' } |
  ForEach-Object { php -l $_.FullName }
```

---

## Variáveis de Ambiente

| Variável | Descrição |
| --- | --- |
| `DB_HOST` | Host do MySQL/MariaDB. |
| `DB_PORT` | Porta do banco de dados. Padrão: `3306`. |
| `DB_NAME` | Nome do banco utilizado pela aplicação. Padrão local: `estoqueti`. |
| `DB_USER` | Usuário de conexão com o banco. |
| `DB_PASS` | Senha de conexão com o banco. |
| `SMTP_HOST` | Host SMTP para envio de notificações. |
| `SMTP_PORT` | Porta SMTP. Padrão: `465`. |
| `SMTP_SECURE` | Tipo de segurança SMTP esperado. Padrão: `ssl`. |
| `SMTP_USER` | Usuário/remetente SMTP. |
| `SMTP_PASS` | Senha SMTP. |
| `SMTP_FROM_NAME` | Nome exibido no remetente dos e-mails. |

---

## Endpoints e Rotas

### Páginas

| Método | Rota | Descrição |
| --- | --- | --- |
| `GET` | `/estoquemh/` | Redireciona para o sistema. |
| `GET/POST` | `/estoquemh/pages/login.php` | Login e logout via sessão. |
| `GET` | `/estoquemh/pages/sistema.php` | Dashboard principal de estoque. |
| `GET` | `/estoquemh/pages/em-uso.php` | Gestão de itens em uso. |
| `GET` | `/estoquemh/pages/usuario.php` | Gestão de usuários por setor. |
| `GET` | `/estoquemh/pages/relatorio_bi_dashboard.php` | Tela de exportações para BI. |

### Actions e APIs Internas

| Método | Endpoint | Descrição |
| --- | --- | --- |
| `POST` | `/estoquemh/php/additem.php` | Cadastra item de estoque. |
| `POST` | `/estoquemh/php/editaritem.php` | Edita item de estoque. |
| `POST` | `/estoquemh/php/excluir.php` | Exclui item de estoque. |
| `POST` | `/estoquemh/php/movimento.php` | Processa entrada/saída de estoque. |
| `POST` | `/estoquemh/php/addcategoria.php` | Cadastra categoria ou subcategoria. |
| `POST` | `/estoquemh/php/editarcategoria.php` | Edita categoria. |
| `POST` | `/estoquemh/php/excluircategoria.php` | Exclui categoria. |
| `POST` | `/estoquemh/php/add_location.php` | Cria, edita ou remove localizações. |
| `POST` | `/estoquemh/php/get_child_locations.php` | Retorna sublocais em JSON. |
| `POST` | `/estoquemh/php/get_location_info.php` | Retorna dados de uma localização em JSON. |
| `POST` | `/estoquemh/php/add_em_uso.php` | Cadastra item em uso. |
| `POST` | `/estoquemh/php/edit_em_uso.php` | Edita item em uso. |
| `POST` | `/estoquemh/php/status_em_uso.php` | Ativa/desativa item em uso. |
| `POST` | `/estoquemh/php/usuario_salvar.php` | Cria ou edita usuário. |
| `POST` | `/estoquemh/php/usuario_excluir.php` | Exclui usuário via JSON. |
| `GET` | `/estoquemh/php/exibir_foto.php` | Exibe foto de localização. |
| `GET` | `/estoquemh/php/foto_usuario.php` | Exibe foto do usuário. |
| `GET` | `/estoquemh/php/relatorio_bi_estoque.php` | Exporta CSV de estoque ou movimentações. |
| `GET` | `/estoquemh/php/logs_exportar.php` | Exporta CSV de logs. |

---

## Banco de Dados

O schema principal está em `sql/estoque.sql` e utiliza InnoDB com `utf8mb4`.

| Tabela | Finalidade |
| --- | --- |
| `categorias` | Hierarquia de categorias e subcategorias, com estoque mínimo. |
| `itens` | Cadastro de itens de estoque, quantidade, observação, localização e status gerado. |
| `itens_em_uso` | Patrimônios/equipamentos alocados por setor. |
| `locations` | Locais e sublocais físicos em estrutura hierárquica. |
| `logs` | Auditoria de ações executadas por usuários. |
| `movimentacoes` | Histórico de entradas e saídas de estoque. |
| `movimentacoes_destinos` | Destinos/setores relacionados a movimentações de saída. |
| `setores` | Setores corporativos agrupados por andar. |
| `usuarios` | Usuários, perfil, setor, senha com hash e foto. |

### Relacionamentos

- `categorias.parent_id` referencia `categorias.id`.
- `itens.categoria_id` referencia `categorias.id`.
- `itens_em_uso.setor_id` referencia `setores.id`.
- `locations.parent_id` referencia `locations.id`.
- `logs.usuario_id` referencia `usuarios.id`.
- `movimentacoes.item_id` referencia `itens.id`.
- `movimentacoes.usuario_id` referencia `usuarios.id`.
- `movimentacoes_destinos.movimentacao_id` referencia `movimentacoes.id`.
- `movimentacoes_destinos.setor_id` referencia `setores.id`.
- `usuarios.setor_id` referencia `setores.id`.

---

## Segurança

- Sessões PHP para autenticação.
- Senhas verificadas por `password_verify` e geradas com `password_hash`.
- `ensureLoggedInUser()` protege páginas e endpoints internos.
- Respostas AJAX não autenticadas retornam HTTP `401`.
- Formulário de login usa token CSRF.
- Perfis `admin` e `user` controlam ações administrativas em telas específicas.
- Upload de imagens valida MIME type com `fileinfo`.
- Upload limita tamanho máximo a 5 MB.
- Exclusão de imagem valida o path real dentro de `uploads/imagens`.
- Credenciais de banco e SMTP são lidas de variáveis de ambiente.

### Pontos de Atenção

- O projeto ainda utiliza SQL interpolado em algumas consultas com sanitização manual.
- Alguns endpoints de escrita não possuem token CSRF próprio.
- `display_errors` está ativo em algumas páginas e deve ser desativado em produção.
- O dump SQL contém dados de seed e BLOBs; revise antes de publicar externamente.

---

## Testes e Qualidade

Não foi detectada suíte automatizada de testes. A verificação executada no projeto foi:

```text
php -l em todos os arquivos PHP fora de vendor
```

Resultado: todos os arquivos PHP analisados passaram na validação de sintaxe.

Recomenda-se adicionar testes automatizados para:

- Login e autorização por perfil.
- CRUD de itens, categorias, locais e usuários.
- Movimentações de entrada/saída.
- Exportações CSV.
- Upload e exibição de imagens.
- Proteção CSRF nos endpoints críticos.

---

## Organização do Repositório

Arquivos gerados, sensíveis e dependências locais devem permanecer fora do versionamento:

- `.env`
- `vendor/`
- `uploads/*`
- arquivos `.log`
- arquivos `.csv`
- caches temporários
- metadados de IDE

O diretório `uploads/` mantém apenas `.gitkeep` para preservar a pasta no repositório.

---

## Manutenção

### Instalar/atualizar dependências

```bash
composer install
composer update phpmailer/phpmailer
```

### Revalidar sintaxe PHP

```powershell
Get-ChildItem -Recurse -Filter *.php -File |
  Where-Object { $_.FullName -notmatch '\\vendor\\' } |
  ForEach-Object { php -l $_.FullName }
```

### Gerar backup do banco

```bash
mysqldump -u root -p estoqueti > backup_estoqueti.sql
```

---

<div align="center">

**Estoque MH** · Controle operacional de estoque e patrimônio de TI

</div>
