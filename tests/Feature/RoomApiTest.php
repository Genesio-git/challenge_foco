<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_rooms(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        $response = $this->getJson('/api/rooms');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_room(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $response = $this->postJson('/api/rooms', [
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Room Test');

        $this->assertDatabaseHas('rooms', [
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);
    }

    public function test_can_update_room(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Old Room',
        ]);

        $response = $this->putJson("/api/rooms/{$room->id}", [
            'hotel_id' => $hotel->id,
            'name' => 'Updated Room',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Room');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Updated Room',
        ]);
    }

    public function test_can_delete_room(): void
    {
        $hotel = Hotel::create([
            'name' => 'Hotel Test',
        ]);

        $room = Room::create([
            'hotel_id' => $hotel->id,
            'name' => 'Room Test',
        ]);

        $response = $this->deleteJson("/api/rooms/{$room->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    public function test_cannot_create_room_with_invalid_hotel(): void
    {
        $response = $this->postJson('/api/rooms', [
            'hotel_id' => 999,
            'name' => 'Invalid Room',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hotel_id']);
    }
}