<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLembreteConfig extends Model
{
    protected $table = 'user_lembretes_config';

    protected $fillable = [
        'user_id',
        'minutos_antes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
