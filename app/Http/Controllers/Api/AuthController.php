<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserLembreteConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\NovoEstabelecimentoAguardando;
use App\Mail\SolicitacaoVinculoProfissional;
use Illuminate\Support\Str;




class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required', // Nome do celular (ex: iPhone do João)
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        // Criamos o token e retornamos para o Flutter
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'tipo' => $user->tipo // Lembra do 'profissional' vs 'cliente'?
            ]
        ]);
    }

    public function logout(Request $request) {
        /** @var PersonalAccessToken|null $token */
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logout realizado.']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telefone' => 'nullable|string',
            'tipo'     => 'required|in:cliente,profissional,estabelecimento',
            
            // Campos condicionais para Estabelecimento
            'nome_estabelecimento' => 'required_if:tipo,estabelecimento|string|max:255',
            'endereco'             => 'required_if:tipo,estabelecimento|string',
            'identificador'        => 'required_if:tipo,estabelecimento|string|unique:estabelecimentos,identificador',
            
            // Campo condicional para Profissional
            'estabelecimento_id'   => 'required_if:tipo,profissional|exists:estabelecimentos,id',
        ]);

        return DB::transaction(function () use ($request) {
            
            // 1. Criar o Usuário
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'telefone' => $request->telefone,
                'tipo'     => ($request->tipo === 'estabelecimento') ? 'profissional' : $request->tipo,
                // Apenas cliente nasce ativo. Profissional e Estabelecimento dependem de aprovação.
                'ativo'    => ($request->tipo === 'cliente'),
                'estabelecimento_id' => $request->estabelecimento_id ?? null,
            ]);

            // 1.1. Criar configuração de lembrete padrão (1 dia antes = 1440 minutos)
            UserLembreteConfig::create([
                'user_id' => $user->id,
                'minutos_antes' => 1440, // 1 dia
            ]);

            // 2. Lógica Específica por Tipo
            if ($request->tipo === 'estabelecimento') {
                // Cria o estabelecimento
                $estabelecimento = $user->estabelecimento()->create([
                    'nome'     => $request->nome_estabelecimento,
                    'endereco' => $request->endereco,
                    'identificador' => Str::slug($request->nome_estabelecimento) . '-' . Str::random(5), 
                    'ramo'          => $request->ramo ?? 'outros', // Define um padrão caso não venha no request
                ]);

                // Vincula o usuário (que é o dono) como o primeiro profissional do local
                $user->update([
                    'estabelecimento_id' => $estabelecimento->id, 
                    'is_admin_estabelecimento' => true,
                    'identificador' => Str::slug($request->nome_estabelecimento) . '-' . Str::random(5), 
                    'ramo'          => $request->ramo ?? 'outros', // Define um padrão caso não venha no request
                
                ]);
                Mail::to('juliosilvaguilherme1@gmail.com')->send(new NovoEstabelecimentoAguardando($user, $estabelecimento));
            }

            if ($request->tipo === 'profissional') {
                $dono = $user->estabelecimento->dono;
                Mail::to($dono->email)->send(new SolicitacaoVinculoProfissional($user, $user->estabelecimento));
            }

            // 3. Gerar Token (Opcional, mas útil para clientes que já entram ativos)
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user,
                'mensagem'     => $user->ativo 
                    ? 'Cadastro realizado com sucesso!' 
                    : 'Cadastro realizado! Aguarde a aprovação para acessar o sistema.'
            ], 201);
        });
    }
}