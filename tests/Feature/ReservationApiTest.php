<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reservation(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        $response = $this->postJson('/api/reservations', [
            'room_id' => $room->id,
            'check_in' => '2026-10-20',
            'check_out' => '2026-10-23',
            'guests' => [
                [
                    'name' => 'Joao',
                    'last_name' => 'Silva',
                    'phone' => '5577999999999',
                ],
            ],
            'dailies' => [
                [
                    'date' => '2026-10-20',
                    'value' => 200,
                ],
                [
                    'date' => '2026-10-21',
                    'value' => 200,
                ],
                [
                    'date' => '2026-10-22',
                    'value' => 200,
                ],
            ],
            'payments' => [
                [
                    'method' => 1,
                    'value' => 600,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Reserva criada com sucesso.');

        $reservationId = $response->json('data.id');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'room_id' => $room->id,
            'total' => 600,
        ]);

        $this->assertDatabaseHas('reservation_guest', [
            'reservation_id' => $reservationId,
        ]);

        $this->assertDatabaseHas('dailies', [
            'reservation_id' => $reservationId,
            'date' => '2026-10-20',
        ]);

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservationId,
            'method' => 1,
        ]);
    }

    public function test_check_out_must_be_after_check_in(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        $response = $this->postJson('/api/reservations', [
            'room_id' => $room->id,
            'check_in' => '2026-10-23',
            'check_out' => '2026-10-20',
            'guests' => [
                [
                    'name' => 'Joao',
                    'last_name' => 'Silva',
                    'phone' => '5577999999999',
                ],
            ],
            'dailies' => [
                [
                    'date' => '2026-10-23',
                    'value' => 200,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_room_must_exist(): void
    {
        $response = $this->postJson('/api/reservations', [
            'room_id' => 999,
            'check_in' => '2026-10-20',
            'check_out' => '2026-10-23',
            'guests' => [
                [
                    'name' => 'Joao',
                    'last_name' => 'Silva',
                    'phone' => '5577999999999',
                ],
            ],
            'dailies' => [
                [
                    'date' => '2026-10-20',
                    'value' => 200,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id']);
    }

    public function test_cannot_create_reservation_when_room_is_unavailable(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        \App\Models\Reservation::create([
            'room_id' => $room->id,
            'check_in' => '2026-10-20',
            'check_out' => '2026-10-23',
            'total' => 600,
        ]);

        $response = $this->postJson('/api/reservations', [
            'room_id' => $room->id,
            'check_in' => '2026-10-22',
            'check_out' => '2026-10-25',

            'guests' => [
                [
                    'name' => 'Joao',
                    'last_name' => 'Silva',
                    'phone' => '5577999999999',
                ],
            ],

            'dailies' => [
                [
                    'date' => '2026-10-22',
                    'value' => 200,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Quarto indisponível para o período informado.'
            );
    }
}
