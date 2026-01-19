<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-sm w-full">
        <div id="status-icon" class="mb-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        </div>
        
        <h2 id="status-text" class="text-xl font-semibold text-gray-700">
            {{ $titulo }}
        </h2>
        <p id="sub-text" class="text-gray-500 mt-2">Aguarde um instante.</p>
    </div>

    <script>
        window.onload = function() {
            // Dispara o fetch para a sua API interna
            fetch('{{ $endpoint }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    // Nota: Se a rota da API exigir auth:sanctum, 
                    // o Admin precisaria estar logado no navegador.
                }
            })
            .then(response => {
                if (response.ok) {
                    sucesso();
                } else {
                    erro();
                }
            })
            .catch(() => erro());
        };

        function sucesso() {
            document.getElementById('status-icon').innerHTML = '<div class="text-green-500 text-5xl">✓</div>';
            document.getElementById('status-text').innerText = 'Confirmado com sucesso!';
            document.getElementById('sub-text').innerText = 'Você já pode fechar esta aba.';
        }

        function erro() {
            document.getElementById('status-icon').innerHTML = '<div class="text-red-500 text-5xl">✕</div>';
            document.getElementById('status-text').innerText = 'Ocorreu um erro.';
            document.getElementById('sub-text').innerText = 'Esta solicitação pode ter expirado ou você não tem permissão.';
        }
    </script>
</body>
</html>