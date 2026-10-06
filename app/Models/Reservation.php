<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'id',
        'room_id',
        'check_in',
        'check_out',
        'total',
    ];

    public function guests()
    {
        return $this->belongsToMany(
            Guest::class,
            'reservation_guest'
        );
    }
}
