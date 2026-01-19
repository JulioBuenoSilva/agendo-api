<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'estabelecimento_id',
        'cliente_id',
        'cliente_nome',
        'profissional_id',
        'servico_id',
        'inicio_horario',
        'fim_horario',
        'status',
        'observacoes',
        'status_alterado_por',
        'status_autor_tipo',
    ];

    protected $casts = [
        'inicio_horario' => 'datetime',
        'fim_horario'    => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function profissional()
    {
        return $this->belongsTo(User::class, 'profissional_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function statusAlteradoPor()
    {
        return $this->belongsTo(User::class, 'status_alterado_por');
    }

    public function getClienteDisplayAttribute()
    {
        if ($this->cliente) {
            return $this->cliente->name;
        }

        if ($this->cliente_nome) {
            return $this->cliente_nome;
        }

        return 'Ocupado';
    }
}
