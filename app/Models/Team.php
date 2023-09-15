<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    public function getLogoAttribute($value)
    {
        return asset('storage/'.$value);
    }

    public function players()
    {
        return $this->hasMany(Player::class,'team_selected');
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

}
