# Foco Multimídia - Project Challenge

API REST desenvolvida em Laravel para o desafio técnico da Foco Multimídia.

Repositório: https://github.com/Genesio-git/challenge_foco

O projeto realiza:

- Importação de hotéis, quartos e reservas a partir de arquivos XML;
- Persistência dos dados em MySQL;
- CRUD REST de quartos;
- Criação de reservas via API REST;
- Relacionamento entre reservas, hóspedes, diárias e pagamentos;
- Execução da importação XML através de comando Artisan, permitindo agendamento via CRON.

## Tecnologias

- PHP 8.4
- Laravel 13
- MySQL 8
- Composer

## Requisitos

Antes de executar o projeto, é necessário possuir:

- PHP 8.4+
- Composer
- MySQL 8+
- Extensões PHP:
  - PDO MySQL
  - SimpleXML
  - XML

## Instalação

Clone o repositório:

```bash
git clone https://github.com/Genesio-git/challenge_foco.git
cd challenge_foco
```

Instale as dependências:

```bash
composer install
```

Crie o arquivo de configuração:

```bash
cp .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

## Banco de dados

Crie um banco MySQL:

```sql
CREATE DATABASE foco_challenge
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Configure o `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foco_challenge
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Execute as migrations:

```bash
php artisan migrate
```

Após executar as migrations, importe os dados iniciais dos XMLs:

```bash
php artisan hotel:import-xml

## Modelagem do banco

O diagrama entidade-relacionamento e a descrição das relações estão disponíveis em:

```text
docs/database.md
```
O arquivo utiliza Mermaid para representar o DER e pode ser visualizado de forma gráfica diretamente pelo GitHub.

As principais entidades são:

- Hotels
- Rooms
- Reservations
- Guests
- Dailies
- Payments

Reservas e hóspedes possuem relacionamento N:N através da tabela `reservation_guest`.

## Importação dos XMLs

Os arquivos XML utilizados na importação ficam em:

```text
database/xml/
```

Arquivos esperados:

```text
hotels.xml
rooms.xml
reserves.xml
```

Para executar a importação:

```bash
php artisan hotel:import-xml
```

O comando importa:

```text
hotels.xml   -> hotels
rooms.xml    -> rooms
reserves.xml -> reservations, guests, dailies e payments
```

A importação utiliza transação de banco de dados e pode ser executada novamente sem duplicar hotéis, quartos ou reservas.

## Execução através de CRON

O comando de importação pode ser executado automaticamente através do CRON do Linux.

Abra o editor do CRON:

```bash
crontab -e
```

Exemplo para executar a importação diariamente às 02:00:

```cron
0 2 * * * /usr/bin/php /caminho/do/projeto/artisan hotel:import-xml >> /caminho/do/projeto/storage/logs/xml-import.log 2>&1
```

O caminho do PHP pode ser consultado com:

```bash
which php
```

A frequência do CRON pode ser alterada conforme a necessidade.

## Executando a aplicação

Inicie o servidor local:

```bash
php artisan serve
```

Por padrão:

```text
http://127.0.0.1:8000
```

## API REST

Todas as respostas da API são retornadas em JSON.

### Listar quartos

```http
GET /api/rooms
```

Resposta:

```json
{
    "data": [
        {
            "id": 1,
            "hotel_id": 1,
            "name": "Room 1 Hotel 1"
        }
    ]
}
```

### Consultar quarto

```http
GET /api/rooms/{id}
```

### Cadastrar quarto

```http
POST /api/rooms
```

Exemplo:

```json
{
    "hotel_id": 1,
    "name": "Room 3 Hotel 1"
}
```

Resposta de sucesso:

```text
201 Created
```

### Atualizar quarto

```http
PUT /api/rooms/{id}
```

Exemplo:

```json
{
    "hotel_id": 1,
    "name": "Room Updated"
}
```

### Excluir quarto

```http
DELETE /api/rooms/{id}
```

## Criar reserva

```http
POST /api/reservations
```

Exemplo:

```json
{
    "room_id": 5,
    "check_in": "2026-10-20",
    "check_out": "2026-10-23",
    "guests": [
        {
            "name": "Joao",
            "last_name": "Silva",
            "phone": "5577999999999"
        }
    ],
    "dailies": [
        {
            "date": "2026-10-20",
            "value": 200
        },
        {
            "date": "2026-10-21",
            "value": 200
        },
        {
            "date": "2026-10-22",
            "value": 200
        }
    ],
    "payments": [
        {
            "method": 1,
            "value": 600
        }
    ]
}
```

O valor total da reserva é calculado pela aplicação através da soma das diárias.

Exemplo:

```text
200 + 200 + 200 = 600
```

Resposta de sucesso:

```text
201 Created
```

## Swagger / OpenAPI

A API possui documentação no padrão OpenAPI 3.0.0.

Com a aplicação em execução, a interface Swagger UI pode ser acessada em:

```text
http://127.0.0.1:8000/docs/
```

A especificação OpenAPI está disponível em:

```text
public/openapi.yaml
```

## Validações

Algumas validações implementadas:

- O hotel informado ao cadastrar um quarto deve existir;
- O quarto informado em uma reserva deve existir;
- `check_out` deve ser posterior ao `check_in`;
- É obrigatório informar ao menos um hóspede;
- É obrigatório informar ao menos uma diária;
- Valores das diárias não podem ser negativos.

Erros de validação são retornados em JSON com HTTP `422`.

## Principais códigos HTTP

| Código | Significado |
|---|---|
| 200 | Operação realizada com sucesso |
| 201 | Recurso criado com sucesso |
| 404 | Recurso não encontrado |
| 422 | Erro de validação |

## Testes automatizados

Os testes da API podem ser executados com:

```bash
php artisan test
```

A suíte cobre:

- Listagem de quartos;
- Cadastro de quartos;
- Atualização de quartos;
- Exclusão de quartos;
- Validação de hotel inexistente;
- Criação de reservas;
- Validação de datas da reserva;
- Validação de quarto inexistente.

## Decisões técnicas

### Transações

A importação XML e a criação de reservas utilizam transações de banco de dados para evitar persistência parcial caso ocorra algum erro.

### Hotel da reserva

A tabela `reservations` armazena apenas `room_id`.

O hotel da reserva pode ser obtido através da relação:

```text
Reservation -> Room -> Hotel
```

Isso evita duplicação desnecessária de informação.

### Relacionamento entre hóspedes e reservas

Uma reserva pode possuir vários hóspedes e um hóspede pode participar de diferentes reservas.

Por isso foi utilizado um relacionamento N:N através da tabela:

```text
reservation_guest
```

## Estrutura principal

```text
app/
├── Console/Commands/
│   └── ImportHotelXml.php
├── Http/Controllers/
│   ├── RoomController.php
│   └── ReservationController.php
└── Models/

database/
├── migrations/
└── xml/

docs/
└── database.md

routes/
└── api.php
```