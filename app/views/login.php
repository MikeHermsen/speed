<?php
require __DIR__ . '/../bootstrap.php';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen | Planning</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 flex items-center justify-center" style="font-family: 'Inter', sans-serif;">
    <div class="bg-white shadow-xl rounded-3xl w-full max-w-md p-10 space-y-6 border border-slate-100">
        <div class="text-center space-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-2xl mx-auto flex items-center justify-center text-white text-2xl font-semibold">
                PL
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">Welkom terug</h1>
            <p class="text-slate-500">Log in om je planning te beheren</p>
        </div>
        <?php if ($flash): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3 text-center">
                <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="/login" class="space-y-5">
            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700" for="email">E-mailadres</label>
                <input class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" type="email" id="email" name="email" required autofocus>
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium text-slate-700" for="password">Wachtwoord</label>
                <input class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" type="password" id="password" name="password" required>
            </div>
            <button class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500 text-white font-semibold shadow-lg shadow-indigo-200 hover:shadow-xl transition" type="submit">Inloggen</button>
        </form>
    </div>
</body>
</html>
