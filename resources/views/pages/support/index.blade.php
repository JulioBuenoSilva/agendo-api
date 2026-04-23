<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - Agendo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <div class="max-w-3xl mx-auto px-4 py-12">
        <header class="text-center mb-12">
            <h1 class="text-3xl font-bold text-gray-900">Central de Ajuda</h1>
            <p class="mt-2 text-gray-600">Como podemos ajudar você hoje?</p>
        </header>

        <section class="mb-12">
            <h2 class="text-xl font-semibold mb-6 border-b pb-2">Perguntas Frequentes</h2>
            <div class="space-y-4">
                @foreach($faqs as $faq)
                <details class="bg-white border border-gray-200 rounded-lg p-4 cursor-pointer hover:shadow-sm transition-shadow">
                    <summary class="font-medium text-indigo-600">{{ $faq['question'] }}</summary>
                    <p class="mt-3 text-gray-600 leading-relaxed">{{ $faq['answer'] }}</p>
                </details>
                @endforeach
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold mb-6">Ainda precisa de ajuda? Envie uma mensagem</h2>

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('support.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seu Nome</label>
                        <input type="text" name="name" required value="{{ old('name') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Seu E-mail</label>
                        <input type="email" name="email" required value="{{ old('email') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assunto</label>
                    <input type="text" name="subject" required value="{{ old('subject') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem</label>
                    <textarea name="message" rows="4" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border">{{ old('message') }}</textarea>
                    @error('message') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 px-6 rounded-lg hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Enviar Solicitação
                </button>
            </form>
        </section>

        <footer class="mt-12 text-center text-gray-400 text-xs">
            &copy; {{ date('Y') }} BuenoTech Soluções. Todos os direitos reservados.
        </footer>
    </div>

</body>
</html>