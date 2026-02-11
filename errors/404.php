<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada | SmartPark</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        body {
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center text-white p-4">
    <div class="max-w-md w-full glass-card rounded-3xl p-10 shadow-2xl text-center">
        <div class="mb-6">
            <div class="text-8xl font-black text-blue-500/20 mb-4 select-none">404</div>
            <i class="fas fa-search-location text-5xl text-blue-400 mb-6"></i>
            <h1 class="text-3xl font-bold mb-3">Página Não Encontrada</h1>
            <p class="text-gray-400">
                O caminho que você tentou acessar não existe ou foi movido permanentemente.
            </p>
        </div>

        <div class="space-y-4 mt-8">
            <a href="/smartpark/"
                class="block w-full py-4 bg-blue-600 hover:bg-blue-500 rounded-2xl font-bold transition-all shadow-lg hover:shadow-blue-500/25">
                <i class="fas fa-home mr-2"></i> Voltar para o Início
            </a>
            <a href="javascript:history.back()"
                class="block w-full py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl font-bold transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Retornar
            </a>
        </div>

        <footer class="mt-10 text-gray-500 text-xs uppercase tracking-widest">
            SmartPark System
        </footer>
    </div>
</body>

</html>