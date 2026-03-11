<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserLembreteConfig;
use App\Models\Estabelecimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Mail\NovoEstabelecimentoAguardando;
use App\Mail\SolicitacaoVinculoProfissional;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Carbon\Carbon;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;

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

        if (! $user) {
            return response()->json([
                'code' => 'USER_NOT_FOUND',
                'message' => 'Credenciais inválidas.'
            ], 404);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'code' => 'INVALID_PASSWORD',
                'message' => 'Credenciais inválidas.'
            ], 401);
        }

        if (! $user->ativo) {
            return response()->json([
                'code' => 'USER_INACTIVE',
                'message' => 'Conta ainda não aprovada, por favor, aguarde.'
            ], 403);
        }


        // Criamos o token e retornamos para o Flutter
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'tipo' => $user->tipo,
                'ativo' => $user->ativo,
                'estabelecimento_id' => $user->estabelecimento_id,
                'is_admin_estabelecimento' => $user->is_admin_estabelecimento,
                'foto_url' => $user->foto_url,
            ],
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
        try { 
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
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'errors' => $e->errors()
            ], 422);
        }

    }

    /**
     * Autenticação via Google (Login ou Registro automático).
     * Se o usuário não existir, cria automaticamente como cliente.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginGoogle(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
            'device_name' => 'required|string',
            'tipo' => 'nullable|in:cliente,profissional,estabelecimento', // Opcional, padrão é cliente
        ]);

        try {
            // Validar token e obter dados do usuário do Google
            $response = Http::get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'access_token' => $request->access_token
            ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Token do Google inválido ou expirado.'], 401);
            }

            $googleUserData = $response->json();
            
            if (!isset($googleUserData['id']) || !isset($googleUserData['email'])) {
                return response()->json(['message' => 'Dados do Google incompletos.'], 401);
            }

            return DB::transaction(function () use ($googleUserData, $request) {
                // Verificar se o usuário já existe pelo google_id ou email
                $user = User::where('google_id', $googleUserData['id'])
                    ->orWhere('email', $googleUserData['email'])
                    ->first();

                $isNewUser = false;

                if (!$user) {
                    // Criar novo usuário
                    $isNewUser = true;
                    $tipo = $request->tipo ?? 'cliente'; // Padrão é cliente
                    
                    $user = User::create([
                        'name' => $googleUserData['name'] ?? $googleUserData['email'],
                        'email' => $googleUserData['email'],
                        'google_id' => $googleUserData['id'],
                        'password' => null, // Sem senha para login via Google
                        'tipo' => ($tipo === 'estabelecimento') ? 'profissional' : $tipo,
                        'ativo' => ($tipo === 'cliente'), // Apenas cliente nasce ativo
                        'email_verified_at' => now(), // Email já verificado pelo Google
                    ]);

                    // Criar configuração de lembrete padrão
                    UserLembreteConfig::create([
                        'user_id' => $user->id,
                        'minutos_antes' => 1440, // 1 dia
                    ]);

                    // Se for estabelecimento, precisa criar o estabelecimento
                    if ($tipo === 'estabelecimento') {
                        $request->validate([
                            'nome_estabelecimento' => 'required|string|max:255',
                            'endereco' => 'required|string',
                            'ramo' => 'nullable|in:beleza,saude,terapia,outros',
                        ]);

                        $estabelecimento = Estabelecimento::create([
                            'nome' => $request->nome_estabelecimento,
                            'endereco' => $request->endereco,
                            'identificador' => Str::slug($request->nome_estabelecimento) . '-' . Str::random(5),
                            'ramo' => $request->ramo ?? 'outros',
                        ]);

                        $user->update([
                            'estabelecimento_id' => $estabelecimento->id,
                            'is_admin_estabelecimento' => true,
                        ]);

                        Mail::to('juliosilvaguilherme1@gmail.com')->send(new NovoEstabelecimentoAguardando($user, $estabelecimento));
                    }

                    // Se for profissional, precisa do estabelecimento_id
                    if ($tipo === 'profissional') {
                        $request->validate([
                            'estabelecimento_id' => 'required|exists:estabelecimentos,id',
                        ]);

                        $user->update([
                            'estabelecimento_id' => $request->estabelecimento_id,
                        ]);

                        $dono = $user->estabelecimento->dono;
                        if ($dono) {
                            Mail::to($dono->email)->send(new SolicitacaoVinculoProfissional($user, $user->estabelecimento));
                        }
                    }
                } else {
                    // Atualizar google_id se o usuário existir mas não tiver google_id
                    if (!$user->google_id) {
                        $user->update(['google_id' => $googleUserData['id']]);
                    }
                }

                // Gerar token
                $token = $user->createToken($request->device_name)->plainTextToken;

                return response()->json([
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'tipo' => $user->tipo,
                    ],
                    'is_new_user' => $isNewUser,
                    'mensagem' => $isNewUser 
                        ? ($user->ativo 
                            ? 'Cadastro realizado com sucesso via Google!' 
                            : 'Cadastro realizado via Google! Aguarde a aprovação para acessar o sistema.')
                        : 'Login realizado com sucesso via Google!'
                ], $isNewUser ? 201 : 200);
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao autenticar com Google: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter URL de redirecionamento para autenticação Google (opcional, para web).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectToGoogle()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');
        
        $scopes = 'openid email profile';
        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return response()->json(['redirect_url' => $url]);
    }

    public function salvarToken(Request $request)
    {
        // 1. Validação básica
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
          $novoToken = $request->fcm_token;
            $usuarioAtual = $request->user();

            // 1. Segurança: Se esse token já estiver em OUTRO usuário, removemos dele
            // Isso evita que duas pessoas recebam notificações no mesmo celular
            User::where('fcm_token', $novoToken)
                ->where('id', '!=', $usuarioAtual->id)
                ->update(['fcm_token' => null]);

            // 2. Salva o token no usuário que acabou de logar
            $usuarioAtual->update([
                'fcm_token' => $novoToken
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Token do dispositivo atualizado com sucesso.',
                'user_id' => $usuarioAtual->id // Confirmação para o Flutter
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Passo 1: Enviar e-mail com o código/token
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'code' => 'USER_NOT_FOUND',
                'message' => 'Não encontramos um usuário com esse endereço de e-mail.'
            ], 404);
        }

        // Geramos um token único
        $token = Str::random(60);

        // Inserimos na tabela nativa do Laravel
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Aqui você enviaria o e-mail. 
        // Dica de Senior: Para Mobile, enviar um código de 6 números é mais prático que um link longo.
        // Mas para manter simples agora, vamos imaginar o envio do token.
        Mail::to($user->email)->send(new ResetPasswordMail($token));

        return response()->json([
            'message' => 'Link de recuperação enviado para o seu e-mail.',
            // 'token_debug' => $token // APENAS para teste, remova em produção!
        ]);
    }

    /**
     * Passo 2: Resetar a senha com o token recebido
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetData || !Hash::check($request->token, $resetData->token)) {
            return response()->json([
                'code' => 'INVALID_TOKEN',
                'message' => 'Token de recuperação inválido ou expirado.'
            ], 400);
        }

        // Verifica se o token expirou (ex: 1 hora)
        if (Carbon::parse($resetData->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Token expirado.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Limpa o token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }
}