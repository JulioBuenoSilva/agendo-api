<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SupportController extends Controller
{
    /**
     * Exibe a página de FAQ e o formulário de suporte.
     */
    public function index()
    {
        // Centralizei as perguntas aqui para facilitar a manutenção sem mexer no HTML
        $faqs = [
            [
                'question' => 'Como criar uma conta como profissional?',
                'answer' => 'Na tela de login, clique em "Não tem uma conta? Cadastre-se" Então, no topo da página, selecione "Pro" se você for um funcionário de um estabelecimento cadastrado ou "Dono" se você estiver querendo cadastrar seu estabelecimento. Após preencher os campos, você deverá aguardar a aprovação da sua conta, que normalmente leva de 1 a 3 dias úteis .'
            ],
            [
                'question' => 'Como realizar um agendamento?',
                'answer' => 'Abra o aplicativo, escolha o profissional desejado, selecione o serviço e escolha um horário disponível no calendário.'
            ],
            [
                'question' => 'Posso cancelar um horário agendado?',
                'answer' => 'Sim. Vá na aba "Meus Agendamentos", selecione o horário e clique em "Cancelar". Verifique a política de cancelamento do profissional.'
            ],
            [
                'question' => 'Esqueci minha senha, o que fazer?',
                'answer' => 'Na tela de login, clique em "Esqueci minha senha" para receber um link de recuperação por e-mail.'
            ]
        ];

        return view('pages.support.index', compact('faqs'));
    }

    /**
     * Processa o envio do formulário de contato.
     */
    public function store(Request $request)
    {
        // Validação rigorosa: evita injeção de lixo no seu e-mail
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|min:10|max:2000',
        ]);

        try {
            // Envio direto via Raw para não precisar criar classes extras agora
            Mail::raw("Nome: {$validated['name']}\nEmail: {$validated['email']}\n\nMensagem:\n{$validated['message']}", function ($message) use ($validated) {
                $message->to('seu-email@exemplo.com') // <--- COLOQUE SEU E-MAIL AQUI
                        ->subject("SUPORTE APP: " . $validated['subject'])
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->replyTo($validated['email'], $validated['name']);
            });

            return back()->with('success', 'Sua mensagem foi enviada com sucesso! Responderemos em breve.');
            
        } catch (\Exception $e) {
            // Log do erro para depuração em produção (crucial no Render)
            Log::error("Erro ao enviar e-mail de suporte: " . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Desculpe, ocorreu um erro ao enviar sua mensagem. Tente novamente mais tarde.');
        }
    }
}