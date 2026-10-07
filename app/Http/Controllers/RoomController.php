<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::all();

        return response()->json([
            'data' => $rooms,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $room = Room::create($validated);

        return response()->json([
            'message' => 'Quarto criado com sucesso.',
            'data' => $room,
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'data' => $room,
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'hotel_id' => ['sometimes', 'required', 'integer', 'exists:hotels,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $room->update($validated);

        return response()->json([
            'message' => 'Quarto atualizado com sucesso.',
            'data' => $room,
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json([
            'message' => 'Quarto removido com sucesso.',
        ]);
    }
}