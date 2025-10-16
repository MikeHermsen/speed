<x-layouts.app title="Inloggen">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-10 shadow-xl">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold text-slate-900">Welkom terug</h1>
                <p class="mt-2 text-sm text-slate-500">Log in om de planning te beheren.</p>
            </div>
            <form method="POST" action="{{ url('/login') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-inner focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Wachtwoord</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-inner focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    />
                </div>
                <div class="flex items-center justify-between text-sm text-slate-500">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                        <span>Onthoud mij</span>
                    </label>
                </div>
                <button
                    type="submit"
                    class="w-full rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200 transition hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2"
                >
                    Inloggen
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
