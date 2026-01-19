<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioFuncionamento extends Model
{
    use HasFactory;

    protected $table = 'horarios_funcionamento';

    protected $fillable = [
        'estabelecimento_id',
        'dia_semana',
        'hora_abertura',
        'hora_fechamento',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    /**
     * Relacionamento: horário pertence a um estabelecimento.
     */
    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }
}
