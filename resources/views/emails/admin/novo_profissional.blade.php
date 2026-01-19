<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;">

        <h2 style="color: #27ae60;">👤 Nova Solicitação de Vínculo</h2>

        <p>Olá,</p>

        <p>
            O profissional <strong>{{ $profissionalNome ?? '—' }}</strong>
            solicitou vínculo ao seu estabelecimento.
        </p>

        <p>
            Enquanto você não aprovar, ele não poderá visualizar sua agenda
            ou realizar agendamentos em seu nome.
        </p>

        <div style="background-color: #f1f8e9; padding: 15px; border-left: 5px solid #27ae60; margin: 20px 0;">
            <p>
                <strong>E-mail do Profissional:</strong>
                {{ $emailProfissional ?? '—' }}
            </p>
        </div>

        @isset($linkAprovacao)
            <p>Você reconhece este profissional? Clique abaixo para confirmar:</p>

            <a href="{{ $linkAprovacao }}"
               style="display: inline-block; padding: 12px 25px; background-color: #27ae60; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Confirmar Profissional
            </a>
        @endisset

        <p style="font-size: 0.8em; color: #777; margin-top: 30px;">
            Se você não reconhece este profissional, ignore este e-mail.
        </p>

    </div>
</body>
</html>
