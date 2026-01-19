<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\HorarioFuncionamento;
use App\Models\Servico;
use App\Models\User;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

use App\Notifications\AgendamentoAtualizado;
class AgendamentoService
{
    /**
     * Tenta criar um agendamento validando disponibilidade e regras.
     */
    public function criar(array $dados)
    {
        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = Carbon::parse($dados['inicio_horario']);
        $fim = (clone $inicio)->addMinutes($servico->duracao_minutos);

        if ($inicio->isPast()) {
            throw new Exception("Não é possível agendar para um horário que já passou.");
        }
        
        $profissional = User::where('id', $dados['profissional_id'])
                        ->where('tipo', 'profissional')
                        ->first();

        if (!$profissional) {
            throw new Exception("O usuário selecionado não é um profissional válido ou não existe.");
        }

        $this->validarHorarioDentroDoFuncionamento($dados['profissional_id'], $inicio, $fim);

        return DB::transaction(function () use ($dados, $inicio, $fim) {
            
            // 1. Validar se o profissional está disponível (Lock de leitura)
            $conflito = Agendamento::where('profissional_id', $dados['profissional_id'])
                ->where('status', '!=', 'cancelado')
                ->where(function ($query) use ($inicio, $fim) {
                    $query->whereBetween('inicio_horario', [$inicio, $fim->subSecond()])
                          ->orWhereBetween('fim_horario', [$inicio->addSecond(), $fim]);
                })
                ->lockForUpdate() // Evita race condition
                ->exists();

            if ($conflito) {
                throw new Exception("O profissional já possui um agendamento neste horário.");
            }

            // 2. Criar o registro
            $agendamento  = Agendamento::create([
                'estabelecimento_id' => $dados['estabelecimento_id'],
                'cliente_id'        => $dados['cliente_id'],
                'profissional_id'   => $dados['profissional_id'],
                'servico_id'        => $dados['servico_id'],
                'inicio_horario'    => $inicio,
                'fim_horario'       => $fim,
                'status'            => 'pendente'
            ]);

            return $agendamento;
        });
    }

    /** Apresenta os horários disponíveis para agendamento
     * (filtra de acordo com o histórico de faltas do cliente
     */
    public function buscarHorariosLivres($profissionalId, $servicoId, $data, $clienteId = null)
    {
        $servico = Servico::findOrFail($servicoId);
        $duracao = $servico->duracao_minutos;

        $profissional = User::with('estabelecimento.horariosFuncionamento')
            ->where('tipo', 'profissional')
            ->findOrFail($profissionalId);

        $diaSemana = Carbon::parse($data)->dayOfWeek;
        
        $turnos = $profissional->estabelecimento->horariosFuncionamento
            ->where('dia_semana', $diaSemana);

        if ($turnos->isEmpty()) return [];

        // --- MELHORIA: Calcular as janelas apenas UMA vez antes do loop ---
        $janelasRisco = ($clienteId) ? $this->obterJanelasDeAltaTaxaDeFalta($clienteId) : [];

        $agendados = Agendamento::where('profissional_id', $profissionalId)
            ->whereDate('inicio_horario', $data)
            ->where('status', '!=', 'cancelado')
            ->orderBy('inicio_horario', 'asc')
            ->get(['inicio_horario', 'fim_horario']);

        $bloqueios = DB::table('bloqueios_agenda')
            ->where(function($query) use ($profissionalId, $profissional) {
                $query->where('profissional_id', $profissionalId)
                    ->orWhere(function($q) use ($profissional) {
                        $q->whereNull('profissional_id')
                            ->where('estabelecimento_id', $profissional->estabelecimento_id);
                    });
            })
            ->whereDate('inicio', '<=', $data)
            ->whereDate('fim', '>=', $data)
            ->get(['inicio', 'fim']);

        $todosPeriodosOcupados = $agendados->concat($bloqueios);

        $slotsDisponiveis = [];
        $step = 15; 

        foreach ($turnos as $turno) {
            $ponteiro = Carbon::parse($data . ' ' . $turno->hora_abertura);
            $limiteTurno = Carbon::parse($data . ' ' . $turno->hora_fechamento);

            while ((clone $ponteiro)->addMinutes($duracao) <= $limiteTurno) {
                $inicioSlot = clone $ponteiro;
                $fimSlot = (clone $ponteiro)->addMinutes($duracao);

                // 1. Verifica No-Show (USANDO A VARIAVEL CALCULADA FORA DO LOOP)
                $estaEmJanelaDeRisco = false;
                foreach ($janelasRisco as $janela) {
                    if ($inicioSlot->hour >= $janela['hora_inicio'] && 
                        $inicioSlot->hour < $janela['hora_fim']) {
                        $estaEmJanelaDeRisco = true;
                        break;
                    }
                }

                if ($estaEmJanelaDeRisco) {
                    $ponteiro->addMinutes($step);
                    continue;
                }

                // 2. Verifica colisão
                $conflito = $todosPeriodosOcupados->contains(function ($item) use ($inicioSlot, $fimSlot) {
                    $inicioOcupado = Carbon::parse($item->inicio_horario ?? $item->inicio);
                    $fimOcupado = Carbon::parse($item->fim_horario ?? $item->fim);
                    return $inicioSlot < $fimOcupado && $fimSlot > $inicioOcupado;
                });
                
                if (!$conflito) {
                    $slotsDisponiveis[] = $inicioSlot->format('H:i');
                }

                $ponteiro->addMinutes($step);
            }
        }

        return $slotsDisponiveis;
    }


    // Busca os horários nos quais o cliente costuma faltar ou cancelar em cima da hora
    private function obterJanelasDeAltaTaxaDeFalta($clienteId)
    {
        $cincoMesesAtras = now()->subMonths(5);
        $limiteMinimoAgendamentos = 2; 
        $taxaCorte = 0.4; 

        $historico = Agendamento::where('cliente_id', $clienteId)
            ->where('inicio_horario', '>=', $cincoMesesAtras)
            ->get();

        if ($historico->isEmpty()) return [];

        $agrupado = $historico->groupBy(function ($agendamento) {
            // Forçamos o parse para garantir que o Carbon entenda a hora do banco
            $hora = Carbon::parse($agendamento->inicio_horario)->hour;
            return (floor($hora / 2) * 2);
        });

        $janelasBloqueadas = [];

        foreach ($agrupado as $faixa => $registros) {
            $total = $registros->count();
            
            $problemas = $registros->filter(function ($item) {
                // Garantir que a comparação de string ignore espaços ou cases
                $status = trim(strtolower($item->status));
                
                if ($status === 'faltou') return true;

                if ($status === 'cancelado' && $item->status_autor_tipo === 'cliente') {
                    $inicio = Carbon::parse($item->inicio_horario);
                    $alteracao = Carbon::parse($item->updated_at);
                    // diffInHours com parâmetro false retorna a diferença absoluta
                    return $alteracao->diffInHours($inicio, false) < 6;
                }
                return false;
            })->count();

            if ($total >= $limiteMinimoAgendamentos) {
                $taxaFalta = $problemas / $total;
                if ($taxaFalta >= $taxaCorte) {
                    $janelasBloqueadas[] = [
                        'hora_inicio' => (int)$faixa,
                        'hora_fim' => (int)$faixa + 2
                    ];
                }
            }
        }

        return $janelasBloqueadas;
    }

    public function cancelar($agendamentoId, User $user)
    {
        return $this->alterarStatusDoAgendamento($agendamentoId, 'cancelado', $user);
    }

    public function listarCompromissosDoProfissional($profissionalId, $data)
    {
        // 1. Pega os agendamentos de clientes
        $agendamentos = Agendamento::with(['cliente:id,name,telefone', 'servico:id,nome,duracao_minutos'])
            ->where('profissional_id', $profissionalId)
            ->whereDate('inicio_horario', $data)
            ->where('status', '!=', 'cancelado')
            ->get()
            ->map(function($item) {
                $item->tipo_registro = 'agendamento';
                return $item;
            });

        // 2. Pega os bloqueios manuais/feriados
        $bloqueios = BloqueioAgenda::where(function($q) use ($profissionalId) {
                $q->where('profissional_id', $profissionalId)
                ->orWhereNull('profissional_id');
            })
            ->whereDate('inicio', '<=', $data)
            ->whereDate('fim', '>=', $data)
            ->get()
            ->map(function($item) {
                $item->tipo_registro = 'bloqueio';
                return $item;
            });

        // 3. Junta tudo e ordena por horário de início
        return $agendamentos->concat($bloqueios)->sortBy(function($item) {
            return $item->inicio_horario ?? $item->inicio;
        })->values();
    }

    /**
     * Altear o status de um agendamento existente para o novo status
     */ 
    public function alterarStatusDoAgendamento($id, $novoStatus, User $autor)
    {
        $agendamento = Agendamento::findOrFail($id);

        // Admin pode tudo
        if ($autor->tipo === 'admin') {
            $autorTipo = 'admin';
        }
        // Cliente só pode mexer no próprio agendamento
        elseif ($autor->id === $agendamento->cliente_id) {
            $autorTipo = 'cliente';
        }
        // Profissional só pode mexer na própria agenda
        elseif ($autor->id === $agendamento->profissional_id) {
            $autorTipo = 'profissional';
        }
        // Qualquer outro caso é abuso
        else {
            abort(403, 'Você não tem permissão para alterar este agendamento.');
        }

        $agendamento->update([
            'status' => $novoStatus,
            'status_alterado_por' => $autor->id,
            'status_autor_tipo' => $autorTipo
        ]);

        $this->dispararNotificacaoStatus($agendamento, $novoStatus, $autor);

        return $agendamento;
    }

    /**
     * Lógica interna para decidir quem recebe o alerta
     */
    private function dispararNotificacaoStatus($agendamento, $novoStatus, $autor)
    {
        // Cenário A: PROFISSIONAL cancelou -> Notifica CLIENTE (se houver cliente cadastrado)
        if ($novoStatus === 'cancelado' && $autor->id === $agendamento->profissional_id) {
            if ($agendamento->cliente) {
                $agendamento->cliente->notify(new AgendamentoAtualizado(
                    $agendamento, 
                    "O profissional cancelou seu horário de {$agendamento->servico->nome}."
                ));
            }
        }

        // Cenário B: CLIENTE cancelou -> Notifica PROFISSIONAL
        if ($novoStatus === 'cancelado' && $agendamento->cliente_id === $autor->id) {
            $nomeCliente = $agendamento->cliente ? $agendamento->cliente->name : $agendamento->cliente_nome;
            $agendamento->profissional->notify(new AgendamentoAtualizado(
                $agendamento, 
                "O cliente {$nomeCliente} cancelou o agendamento."
            ));
        }
        
        // Cenário C: PROFISSIONAL confirmou -> Notifica CLIENTE
        if ($novoStatus === 'confirmado' && $autor->id === $agendamento->profissional_id) {
            if ($agendamento->cliente) {
                $agendamento->cliente->notify(new AgendamentoAtualizado(
                    $agendamento, 
                    "Seu agendamento foi confirmado pelo profissional!"
                ));
            }
        }
    }

    /**
     * Cria um bloqueio na agenda (ex: folga, médico, feriado)
     */
    public function registrarBloqueio(array $dados)
    {
        return BloqueioAgenda::create([
            'estabelecimento_id' => $dados['estabelecimento_id'],
            'profissional_id'   => $dados['profissional_id'] ?? null, // Nulo = Estabelecimento todo
            'inicio'            => Carbon::parse($dados['inicio']),
            'fim'               => Carbon::parse($dados['fim']),
            'motivo'            => $dados['motivo'] ?? 'Bloqueio manual',
        ]);
    }

    /**
     * Remove um bloqueio existente
     */
    public function removerBloqueio($id)
    {
        $bloqueio = BloqueioAgenda::findOrFail($id);
        return $bloqueio->delete();
    }

    /**
     * Permite que o profissional agende um horário, mesmo sem um cliente cadastrado no app.
     */
    public function registrarAgendamentoManual(array $dados)
    {
        $servico = Servico::findOrFail($dados['servico_id']);
        $inicio = Carbon::parse($dados['inicio_horario'])->second(0);
        $fim = (clone $inicio)->addMinutes($servico->duracao_minutos);

        return DB::transaction(function () use ($dados, $inicio, $fim) {
            
            // Validação de Conflito (Mesma lógica de segurança)
            $conflito = Agendamento::where('profissional_id', $dados['profissional_id'])
                ->where('status', '!=', 'cancelado')
                ->where(function ($query) use ($inicio, $fim) {
                    $query->whereBetween('inicio_horario', [$inicio, $fim->subSecond()])
                        ->orWhereBetween('fim_horario', [$inicio->addSecond(), $fim]);
                })
                ->lockForUpdate()
                ->exists();

            if ($conflito) {
                throw new Exception("Conflito: Já existe um compromisso ou bloqueio neste horário.");
            }

            return Agendamento::create([
                'estabelecimento_id' => $dados['estabelecimento_id'],
                'profissional_id'   => $dados['profissional_id'],
                'servico_id'        => $dados['servico_id'],
                'cliente_id'        => $dados['cliente_id'] ?? null,
                'cliente_nome'      => $dados['cliente_nome'] ?? null,
                'inicio_horario'    => $inicio,
                'fim_horario'       => $fim,
                'status'            => 'confirmado',
                'observacoes'       => $dados['observacoes'] ?? 'Agendamento manual (via balcão)',
            ]);

        });
    }

    /**
     * Verificar se o horário solicitado pelo cliente está dentro do horário de funcionamento do estabelecimento
     */
    private function validarHorarioDentroDoFuncionamento($profissionalId, $inicio, $fim)
    {
        $diaSemana = $inicio->dayOfWeek;
        $horaInicio = $inicio->format('H:i:s');
        $horaFim = $fim->format('H:i:s');

        $profissional = User::with('estabelecimento.horariosFuncionamento')->findOrFail($profissionalId);
        
        // Verifica se existe algum turno que cubra totalmente o horário desejado
        $dentroDoTurno = $profissional->estabelecimento->horariosFuncionamento
            ->where('dia_semana', $diaSemana)
            ->filter(function ($turno) use ($horaInicio, $horaFim) {
                return $horaInicio >= $turno->hora_abertura && $horaFim <= $turno->hora_fechamento;
            })->isNotEmpty();

        if (!$dentroDoTurno) {
            throw new Exception("O estabelecimento está fechado neste horário ou o serviço ultrapassa o horário de fechamento.");
        }
    }

}