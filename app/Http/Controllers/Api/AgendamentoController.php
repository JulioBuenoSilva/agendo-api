<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Services\AgendamentoService;
use Illuminate\Http\Request;
use App\Models\Agendamento;

use Exception;

class AgendamentoController extends Controller
{
    protected $service;

    public function __construct(AgendamentoService $service)
    {
        $this->service = $service;
    }
 
    /**
     * Listar horários disponíveis para o Flutter
     */
    public function horariosDisponiveis(Request $request)
    {
        // Validação básica de entrada
        $request->validate([
            'servico_id'      => 'required|exists:servicos,id',
            'data'            => 'required|date_format:Y-m-d',
        ]);

        $horarios = $this->service->buscarHorariosLivres(
            $request->profissional_id,
            $request->servico_id,
            $request->data,
            $request->user()->id
        );

        return response()->json($horarios);
    }

    /**
     * Efetivar o agendamento
     */
    public function store(Request $request)
    {
        $request->validate([
            'estabelecimento_id' => 'required|exists:estabelecimentos,id',
            'profissional_id'   => 'required|exists:users,id',
            'servico_id'        => 'required|exists:servicos,id',
            'inicio_horario'    => 'required|date_format:Y-m-d H:i:s',
        ]);

        try {
            $dados = $request->all();

            // Identidade vem do token, não do cliente
            $dados['cliente_id'] = $request->user()->id;

            $agendamento = $this->service->criar($dados);

            return response()->json([
                'mensagem' => 'Agendamento realizado com sucesso!',
                'dados' => $agendamento
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'erro' => $e->getMessage()
            ], 422);
        }
    }


    /**
     * Cancela um agendamento existente
     */
    public function cancelar(Request $request, $id)
    {

        try {
            $agendamento = $this->service->cancelar($id, $request->user());
            
            return response()->json([
                'mensagem' => 'Agendamento cancelado com sucesso.',
                'dados' => $agendamento
            ]);
        } catch (Exception $e) {
            return response()->json([
                'erro' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Consulta a lista de compromissos para um profissional em uma data específica
     */
    public function minhaAgenda(Request $request)
    {

        $agenda = $this->service->listarAgendaFuturaAgrupada(
            $request->user()
        );

        return response()->json($agenda);
    }

    /**
     * Registra a mudança de estado de um agendamento (Realizado, Falta, etc)
     */
    public function atualizarStatusAgendamento(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmado,realizado,faltou,cancelado',
            'user_id' => 'required|exists:users,id' // Autor da ação
        ]);

        try {
            // Agora passamos os 3 argumentos: ID, STATUS e USER_ID
            $agendamento = $this->service->alterarStatusDoAgendamento(
                $id, 
                $request->status, 
                $request->user()
            );

            return response()->json([
                'mensagem' => 'Status atualizado com sucesso',
                'dados' => $agendamento
            ]);
        } catch (\Exception $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    /**
     * Cria um bloqueio na agenda (ex: folga, médico, feriado)
     */
    public function criarBloqueio(Request $request)
    {
        if ($request->user()->tipo !== 'profissional') {
            return response()->json(['erro' => 'Acesso negado. Apenas profissionais.'], 403);
        }

        $request->validate([
            'estabelecimento_id' => 'required|exists:estabelecimentos,id',
            'inicio'            => 'required|date_format:Y-m-d H:i:s',
            'fim'               => 'required|date_format:Y-m-d H:i:s|after:inicio',
        ]);

        $bloqueio = $this->service->registrarBloqueio($request->all());
        return response()->json(['mensagem' => 'Horário bloqueado com sucesso', 'dados' => $bloqueio]);
    }

    /**
     * Remove um bloqueio da agenda
     */
    public function excluirBloqueio($id)
    {
        $this->service->removerBloqueio($id);
        return response()->json(['mensagem' => 'Bloqueio removido. Horário liberado.']);
    }

    /**
     * Realiza um agendamento manual (pela visão do profissional/balcão)
     */
    public function realizarAgendamentoManual(Request $request)
    {
        $request->validate([
            'estabelecimento_id' => 'required|exists:estabelecimentos,id',
            'profissional_id'   => 'required|exists:users,id',
            'servico_id'        => 'required|exists:servicos,id',
            'inicio_horario'    => 'required|date_format:Y-m-d H:i:s',
            'cliente_id'        => 'nullable|exists:users,id',
            'cliente_nome'      => 'nullable|string|max:255',
            'observacoes'       => 'nullable|string|max:255'
        ]);


        try {
            $agendamento = $this->service->registrarAgendamentoManual($request->all());
            return response()->json([
                'mensagem' => 'Agendamento manual registrado!',
                'dados' => $agendamento
            ], 201);
        } catch (Exception $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    // Método para o cliente confirmar que realmente virá
    public function confirmarPresenca($id)
    {
        try {
            $agendamento = Agendamento::findOrFail($id);
            
            // Só faz sentido confirmar se estiver aguardando
            if ($agendamento->status !== 'pendente') {
                return response()->json(['mensagem' => 'Este agendamento não precisa de confirmação no momento.'], 400);
            }

            // Voltamos para 'confirmado', mas agora com a certeza do cliente
            $agendamento->update([
                'status' => 'confirmado',
                'status_alterado_por' => $agendamento->cliente_id,
                'status_autor_tipo' => 'cliente'
            ]);

            return response()->json(['mensagem' => 'Presença confirmada com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['erro' => $e->getMessage()], 500);
        }
    }

    public function registrarFalta(Request $request, $id)
    {
        try {
            $agendamento = $this->service->registrarFalta($id, $request->user());
            
            return response()->json([
                'message' => 'Falta registrada com sucesso. O histórico do cliente foi atualizado.',
                'agendamento' => $agendamento
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}