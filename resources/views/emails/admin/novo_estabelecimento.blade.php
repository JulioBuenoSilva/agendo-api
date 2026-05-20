<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;">
        <h2 style="color: #2c3e50;">🚀 Novo Parceiro à Vista!</h2>
        <p>Olá, <strong>Admin</strong>,</p>
        <p>Um novo estabelecimento acaba de se cadastrar e aguarda sua aprovação para começar a operar.</p>
        
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 5px solid #3498db; margin: 20px 0;">
            <p><strong>Estabelecimento:</strong> {{ $estabelecimentoNome }}</p>
            <p><strong>Responsável:</strong> {{ $donoNome }}</p>
            <p><strong>Identificador:</strong> {{ $identificador }}</p>
        </div>

        <p>Verifique os dados e, se estiver tudo certo, libere o acesso clicando no botão abaixo:</p>
        
        <a href="{{ $linkAprovacao }}" 
           style="display: inline-block; padding: 12px 25px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">
           Aprovar Estabelecimento
        </a>

        <p style="font-size: 0.8em; color: #777; margin-top: 30px;">
            Este é um e-mail automático do sistema de gestão.
        </p>
    </div>
</body>
</html>