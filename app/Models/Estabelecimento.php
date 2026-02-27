<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estabelecimento extends Model
{
    protected $table = 'estabelecimentos';

    protected $fillable = [
        'nome', 
        'identificador', 
        'endereco',
        'ramo', 
        'fuso_horario',
        'foto_path'
    ];

    /**
     * Acessors adicionais para o modelo.
     */
    protected $appends = ['foto_url'];
    
    public function getFotoUrlAttribute()
    {
        return $this->foto_path 
            ? asset('storage/' . $this->foto_path) 
            : asset('storage/images/estabelecimentos/default-establishment.png');
    }


    // Um estabelecimento tem um dono (que é um usuário)
    public function dono()
    {
        return $this->hasOne(User::class, 'estabelecimento_id')
                    ->where('is_admin_estabelecimento', true);
    }

    // Um estabelecimento tem vários profissionais
    public function profissionais()
    {
        return $this->hasMany(User::class)->where('tipo', 'profissional');
    }

    // Um estabelecimento oferece vários serviços
    public function servicos()
    {
        return $this->hasMany(Servico::class);
    }

    // Um estabelecimento tem vários horários de funcionamento
    public function horariosFuncionamento()
    {
        return $this->hasMany(HorarioFuncionamento::class);
    }
}