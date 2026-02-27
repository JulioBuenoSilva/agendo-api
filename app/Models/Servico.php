<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    protected $table = 'servicos';

    protected $fillable = [
        'estabelecimento_id', 
        'nome', 
        'duracao_minutos', 
        'preco', 
        'observacao', 
        'ativo',
        'foto_path'
    ];

    protected $casts = [
        'preco' => 'float',
        'ativo' => 'boolean'
    ];

    /**
     * Acessors adicionais para o modelo.
     */
    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute()
    {
        return $this->foto_path 
            ? asset('storage/' . $this->foto_path) 
            : asset('storage/images/servicos/default-service.png'); // Uma imagem padrão caso esteja nulo
    }

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }
}