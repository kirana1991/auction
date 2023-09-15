<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    public function getProfileImgAttribute($value)
    {
        return !is_null($value) ? asset('storage/images/'.$value) : null;
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_selected');
    }
}
