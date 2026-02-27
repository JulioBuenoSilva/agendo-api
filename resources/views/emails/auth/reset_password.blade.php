@component('mail::message')
# Olá!

Você recebeu este e-mail porque solicitou a redefinição de senha da sua conta no **Agendo**.

Copie o código abaixo e insira no aplicativo:

@component('mail::panel')
## {{ $token }}
@endcomponent

**Atenção:** Este código é válido por apenas 60 minutos. 

Se você não solicitou essa alteração, nenhuma ação adicional é necessária.

Obrigado,<br>
Equipe {{ config('app.name') }}
@endcomponent