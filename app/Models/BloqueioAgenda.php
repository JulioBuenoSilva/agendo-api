<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloqueioAgenda extends Model
{
    use HasFactory;

    protected $table = 'bloqueios_agenda';

    protected $fillable = [
        'estabelecimento_id',
        'profissional_id',
        'inicio',
        'fim',
        'motivo',
        'dia_inteiro',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'dia_inteiro' => 'boolean',
    ];

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    public function profissional()
    {
        return $this->belongsTo(User::class, 'profissional_id');
    }
}
