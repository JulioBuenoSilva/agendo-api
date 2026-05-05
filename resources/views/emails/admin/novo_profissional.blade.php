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
            Enquanto você não aprovar, ele não poderá ter uma agenda
            ou realizar agendamentos em seu estabelecimento.
        </p>

        <div style="background-color: #f1f8e9; padding: 15px; border-left: 5px solid #27ae60; margin: 20px 0;">
            <p>
                <strong>E-mail do Profissional:</strong>
                {{ $emailProfissional ?? '—' }}
            </p>
        </div>

        @isset($codigoAprovacao)
            <p>Você reconhece este profissional? Envie a ele o código abaixo para confirmar:</p>

            <div style="background-color: #e3f2fd; padding: 15px; border-left: 5px solid #2196f3; margin: 20px 0; text-align: center;">
                <span style="font-size: 1.5em; font-weight: bold; color: #2196f3;">{{ $codigoAprovacao }}</span>
            </div>
        @endisset

        <p style="font-size: 0.8em; color: #777; margin-top: 30px;">
            Se você não reconhece este profissional, ignore este e-mail.
        </p>

    </div>
</body>
</html>
