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
        'ativo'
    ];

    protected $casts = [
        'preco' => 'float',
        'ativo' => 'boolean'
    ];

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }
}