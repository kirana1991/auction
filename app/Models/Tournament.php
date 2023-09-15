<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    public function getLogoAttribute($value)
    {
        return asset('storage/images/'.$value);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function team()
    {
        return $this->hasOne(Team::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function player()
    {
        return $this->hasOne(Player::class);
    }

    public function scopeActive($query)
    {
        $query->whereNull('deleted_at');
    }

}
