<?php

namespace App\Console\Commands;

use App\Models\Daily;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHotelXml extends Command
{
    protected $signature = 'hotel:import-xml';

    protected $description = 'Importa hotéis, quartos e reservas a partir dos arquivos XML';

    public function handle(): int
    {
        try {
            DB::transaction(function () {
                $this->importHotels();
                $this->importRooms();
                $this->importReservations();
            });

            $this->info('Importação concluída com sucesso.');

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Erro durante a importação: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function importHotels(): void
    {
        $xml = $this->loadXml('hotels.xml');

        foreach ($xml->Hotel as $hotel) {
            Hotel::updateOrCreate(
                [
                    'id' => (int) $hotel['id'],
                ],
                [
                    'name' => (string) $hotel->Name,
                ]
            );
        }

        $this->info('Hotéis importados.');
    }

    private function importRooms(): void
    {
        $xml = $this->loadXml('rooms.xml');

        foreach ($xml->Room as $room) {
            Hotel::findOrFail((int) $room['hotelCode']);

            Room::updateOrCreate(
                [
                    'id' => (int) $room['id'],
                ],
                [
                    'hotel_id' => (int) $room['hotelCode'],
                    'name' => (string) $room->Name,
                ]
            );
        }

        $this->info('Quartos importados.');
    }

    private function importReservations(): void
    {
        $xml = $this->loadXml('reserves.xml');

        foreach ($xml->Reserve as $reserve) {
            $room = Room::findOrFail((int) $reserve['roomCode']);

            $hotelCode = (int) $reserve['hotelCode'];

            if ($room->hotel_id !== $hotelCode) {
                throw new \RuntimeException(
                    "A reserva {$reserve['id']} informa um hotel incompatível com o quarto."
                );
            }

            $reservation = Reservation::updateOrCreate(
                [
                    'id' => (int) $reserve['id'],
                ],
                [
                    'room_id' => $room->id,
                    'check_in' => (string) $reserve->CheckIn,
                    'check_out' => (string) $reserve->CheckOut,
                    'total' => (float) $reserve->Total,
                ]
            );

            $reservation->guests()->detach();
            Daily::where('reservation_id', $reservation->id)->delete();
            Payment::where('reservation_id', $reservation->id)->delete();

            $this->importGuests($reserve, $reservation);
            $this->importDailies($reserve, $reservation);
            $this->importPayments($reserve, $reservation);
        }

        $this->info('Reservas importadas.');
    }

    private function importGuests(
        \SimpleXMLElement $reserve,
        Reservation $reservation
    ): void {
        if (!isset($reserve->Guests)) {
            return;
        }

        foreach ($reserve->Guests->Guest as $guestXml) {
            $guest = Guest::firstOrCreate(
                [
                    'name' => (string) $guestXml->Name,
                    'last_name' => (string) $guestXml->LastName,
                    'phone' => (string) $guestXml->Phone,
                ]
            );

            $reservation->guests()->attach($guest->id);
        }
    }

    private function importDailies(
        \SimpleXMLElement $reserve,
        Reservation $reservation
    ): void {
        if (!isset($reserve->Dailies)) {
            return;
        }

        foreach ($reserve->Dailies->Daily as $dailyXml) {
            Daily::create([
                'reservation_id' => $reservation->id,
                'date' => (string) $dailyXml->Date,
                'value' => (float) $dailyXml->Value,
            ]);
        }
    }

    private function importPayments(
        \SimpleXMLElement $reserve,
        Reservation $reservation
    ): void {
        if (!isset($reserve->Payments)) {
            return;
        }

        foreach ($reserve->Payments->Payment as $paymentXml) {
            Payment::create([
                'reservation_id' => $reservation->id,
                'method' => (int) $paymentXml->Method,
                'value' => (float) $paymentXml->Value,
            ]);
        }
    }

    private function loadXml(string $filename): \SimpleXMLElement
    {
        $path = database_path("xml/{$filename}");

        if (!file_exists($path)) {
            throw new \RuntimeException(
                "Arquivo XML não encontrado: {$filename}"
            );
        }

        $xml = simplexml_load_file($path);

        if ($xml === false) {
            throw new \RuntimeException(
                "Não foi possível ler o XML: {$filename}"
            );
        }

        return $xml;
    }
}