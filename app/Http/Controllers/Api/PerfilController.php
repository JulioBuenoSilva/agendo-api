<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller {
        

    /**
     * Atualiza dados básicos do perfil
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $dadosValidados = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                Rule::unique('users')->ignore($user->id)
            ],
        ]);

        $user->update($dadosValidados);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user'    => $user
        ]);
    }

    /**
     * Altera a senha do usuário
     */
    public function alterarSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required',
            'nova_senha'   => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->senha_atual, $user->password)) {
            return response()->json(['message' => 'A senha atual está incorreta.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->nova_senha)
        ]);

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }

    public function uploadFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Aceita até 5MB, mas vamos diminuir
        ]);

        $user = $request->user();
        
        // 1. Criar um nome único
        $nomeArquivo = 'perfil_' . $user->id . '_' . time() . '.webp'; // Usar .webp economiza +30%
        $caminhoRelativo = 'images/perfis/' . $nomeArquivo;

        // 2. Processar a imagem com Intervention Image
        $imagemOtimizada = Image::read($request->file('foto'))
            ->cover(400, 400) // Corta e centraliza em 400x400 (perfeito para avatar)
            ->toWebp(80);     // Converte para WebP com 80% de qualidade

        // 3. Salvar no Storage
        Storage::disk('public')->put($caminhoRelativo, (string) $imagemOtimizada);

        // 4. Limpeza: Deleta a foto anterior
        if ($user->foto_path) {
            Storage::disk('public')->delete($user->foto_path);
        }

        $user->update(['foto_path' => $caminhoRelativo]);

        return response()->json(['url' => asset('storage/' . $caminhoRelativo)]);
    }

    public function excluirConta(Request $request)
    {
        $user = $request->user();

        // 1️⃣ Verificação de Agendamentos Pendentes (Pessoais ou de subordinados)
        // Se for Admin, ele não pode fechar o estabelecimento se QUALQUER profissional dele tiver agenda.
        $queryAgendamentos = Agendamento::where('status', '!=', 'cancelado')
            ->where('status', '!=', 'faltou')
            ->where('fim_horario', '>=', now());

        if ($user->isAdminEstabelecimento() && $user->estabelecimento_id) {
            $queryAgendamentos->where('estabelecimento_id', $user->estabelecimento_id);
        } else {
            $queryAgendamentos->where(function ($q) use ($user) {
                $q->where('cliente_id', $user->id)
                ->orWhere('profissional_id', $user->id);
            });
        }

        if ($queryAgendamentos->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir a conta. Existem agendamentos futuros ativos no seu perfil ou estabelecimento.'
            ], 422);
        }

        DB::transaction(function () use ($user) {
            
            // 2️⃣ Se for ADMIN, limpamos o ecossistema do estabelecimento
            if ($user->isAdminEstabelecimento() && $user->estabelecimento_id) {
                $estId = $user->estabelecimento_id;

                // Deleta todos os agendamentos passados/cancelados do estabelecimento
                Agendamento::where('estabelecimento_id', $estId)->delete();

                // Deleta bloqueios de todos os profissionais do local
                BloqueioAgenda::where('estabelecimento_id', $estId)->delete();

                // Deleta horários de funcionamento
                DB::table('horarios_funcionamento')->where('estabelecimento_id', $estId)->delete();

                // Deleta serviços
                DB::table('servicos')->where('estabelecimento_id', $estId)->delete();

                // Busca todos os profissionais vinculados para limpar as fotos deles
                $profissionais = User::where('estabelecimento_id', $estId)->where('id', '!=', $user->id)->get();
                foreach ($profissionais as $pro) {
                    if ($pro->foto_path) {
                        Storage::disk('public')->delete($pro->foto_path);
                    }
                    // Deleta notificações e o usuário profissional
                    DB::table('notifications')->where('notifiable_id', $pro->id)->delete();
                    $pro->delete();
                }

                // Por fim, deleta o registro do estabelecimento em si
                DB::table('estabelecimentos')->where('id', $estId)->delete();
            }

            // 3️⃣ Limpeza de dados pessoais (válido para Admin, Profissional ou Cliente)
            
            // Agendamentos passados/cancelados onde o user era cliente/pro
            Agendamento::where('cliente_id', $user->id)
                ->orWhere('profissional_id', $user->id)
                ->delete();

            BloqueioAgenda::where('profissional_id', $user->id)->delete();

            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->delete();

            if ($user->foto_path) {
                Storage::disk('public')->delete($user->foto_path);
            }

            $user->delete();
        });

        return response()->json(['message' => 'Conta e dados vinculados excluídos com sucesso.']);
    }
}
