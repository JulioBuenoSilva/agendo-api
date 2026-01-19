<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    /**
     * Campos que podem ser preenchidos em massa.
     * Tudo que não estiver aqui NÃO pode vir direto do request.
     */
    protected $fillable = [
        'name', 
        'email', 
        'telefone',
        'password', 
        'google_id',  
        'tipo', 
        'ativo', 
        'estabelecimento_id',
        'is_admin_estabelecimento',
    ];


    /**
     * Campos que nunca devem aparecer em JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',  
    ];

    /**
     * Conversões automáticas de tipo.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ativo' => 'boolean',
        'is_admin_estabelecimento' => 'boolean',
    ];

    /**
     * O estabelecimento onde o usuário trabalha ou é dono
     */
    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    /**
     * Configuração de lembrete do usuário.
     */
    public function lembreteConfig()
    {
        return $this->hasOne(UserLembreteConfig::class);
    }

    /**
     * Agendamentos como cliente.
     */
    public function consultas()
    {
        return $this->hasMany(Agendamento::class, 'cliente_id');
    }

    /**
     * Agendamentos como profissional (Agenda do dia).
     */
    public function agenda()
    {
        return $this->hasMany(Agendamento::class, 'profissional_id');
    }
    public function isAdmin(): bool
    {
        return $this->tipo === 'admin';
    }

    public function isProfissional(): bool
    {
        return $this->tipo === 'profissional';
    }

    public function isCliente(): bool
    {
        return $this->tipo === 'cliente';
    }


    public function isAtivo(): bool
    {
        return $this->ativo === true;
    }

    public function isInativo(): bool
    {
        return $this->ativo === false;
    }

    public function isAdminEstabelecimento(): bool
    {
        return $this->is_admin_estabelecimento === true;
    }

}
 