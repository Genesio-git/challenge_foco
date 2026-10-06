# Modelagem do Banco de Dados

## Diagrama Entidade-Relacionamento

```mermaid
erDiagram
    HOTELS ||--o{ ROOMS : possui
    ROOMS ||--o{ RESERVATIONS : recebe
    RESERVATIONS ||--o{ DAILIES : possui
    RESERVATIONS ||--o{ PAYMENTS : possui
    RESERVATIONS ||--o{ RESERVATION_GUEST : associa
    GUESTS ||--o{ RESERVATION_GUEST : participa

    HOTELS {
        bigint id PK
        varchar name
    }

    ROOMS {
        bigint id PK
        bigint hotel_id FK
        varchar name
    }

    RESERVATIONS {
        bigint id PK
        bigint room_id FK
        date check_in
        date check_out
        decimal total
    }

    GUESTS {
        bigint id PK
        varchar name
        varchar last_name
        varchar phone
    }

    RESERVATION_GUEST {
        bigint reservation_id FK
        bigint guest_id FK
    }

    DAILIES {
        bigint id PK
        bigint reservation_id FK
        date date
        decimal value
    }

    PAYMENTS {
        bigint id PK
        bigint reservation_id FK
        int method
        decimal value
    }
```

## Relacionamentos

- Um hotel pode possuir vários quartos.
- Um quarto pertence a um único hotel.
- Um quarto pode possuir várias reservas ao longo do tempo.
- Uma reserva pode possuir várias diárias.
- Uma reserva pode possuir vários pagamentos.
- Uma reserva pode possuir vários hóspedes e um hóspede pode participar de várias reservas, caracterizando uma relação N:N por meio da tabela `reservation_guest`.

### Legenda

- **PK**: Primary Key (chave primária)
- **FK**: Foreign Key (chave estrangeira)