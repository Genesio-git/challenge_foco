<?php

namespace App\Http\Controllers;

use App\Models\Daily;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],

            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],

            'guests' => ['required', 'array', 'min:1'],
            'guests.*.name' => ['required', 'string', 'max:255'],
            'guests.*.last_name' => ['required', 'string', 'max:255'],
            'guests.*.phone' => ['required', 'string', 'max:20'],

            'dailies' => ['required', 'array', 'min:1'],
            'dailies.*.date' => ['required', 'date'],
            'dailies.*.value' => ['required', 'numeric', 'min:0'],

            'payments' => ['sometimes', 'array'],
            'payments.*.method' => ['required', 'integer'],
            'payments.*.value' => ['required', 'numeric', 'min:0'],
        ]);

        $hasConflict = Reservation::where('room_id', $validated['room_id'])
            ->where('check_in', '<', $validated['check_out'])
            ->where('check_out', '>', $validated['check_in'])
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'message' => 'Quarto indisponível para o período informado.',
            ], 422);
        }

        $reservation = DB::transaction(function () use ($validated) {
            $total = collect($validated['dailies'])->sum('value');

            $reservation = Reservation::create([
                'room_id' => $validated['room_id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'total' => $total,
            ]);

            foreach ($validated['guests'] as $guestData) {
                $guest = Guest::firstOrCreate([
                    'name' => $guestData['name'],
                    'last_name' => $guestData['last_name'],
                    'phone' => $guestData['phone'],
                ]);

                $reservation->guests()->syncWithoutDetaching([
                    $guest->id,
                ]);
            }

            foreach ($validated['dailies'] as $dailyData) {
                Daily::create([
                    'reservation_id' => $reservation->id,
                    'date' => $dailyData['date'],
                    'value' => $dailyData['value'],
                ]);
            }

            foreach ($validated['payments'] ?? [] as $paymentData) {
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'method' => $paymentData['method'],
                    'value' => $paymentData['value'],
                ]);
            }

            return $reservation;
        });

        return response()->json([
            'message' => 'Reserva criada com sucesso.',
            'data' => $reservation,
        ], 201);
    }
}
