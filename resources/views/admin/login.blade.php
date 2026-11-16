<!DOCTYPE html>
<html lang="en-IN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Admin login · H2 CABS</title>
        <link rel="icon" href="{{ asset(config('site.brand.logo_mark')) }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap">
        @vite(['resources/css/app.css'])
    </head>
    <body class="admin-app min-h-screen">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
            {{-- Brand panel --}}
            <aside class="relative isolate hidden overflow-hidden bg-admin-navy lg:flex lg:flex-col lg:justify-between lg:p-12" aria-hidden="true">
                <img src="{{ asset('images/photos/promenade-hero-1280.webp') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-40">
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-admin-navy-deep via-admin-navy/85 to-admin-navy/60"></div>
                <div class="flex items-center gap-3 text-white">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg"><img src="{{ asset(config('site.brand.logo_mark')) }}" alt="" class="size-full"></span>
                    <span class="leading-tight"><span class="block text-xl font-extrabold">H2 CABS</span><span class="block text-sm text-white/65">Admin Portal</span></span>
                </div>
                <div class="max-w-md text-white">
                    <p class="text-3xl leading-tight font-extrabold tracking-tight">Run your fleet, fares and bookings from one place.</p>
                    <p class="mt-3 text-white/70">Helping Hand Drivers Association Cabs — Puducherry.</p>
                </div>
            </aside>

            <main class="flex items-center justify-center px-4 py-12 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex justify-center lg:hidden"><x-ui.logo /></div>
                    <form method="POST" action="{{ route('admin.login.store') }}" class="admin-panel p-6 sm:p-8">
                        @csrf
                        <h1 class="text-2xl font-extrabold tracking-tight text-admin-navy">Admin login</h1>
                        <p class="mt-1 text-sm text-slate-500">For H2 CABS administrators only.</p>

                        <div class="mt-6 space-y-5">
                            <x-admin.field name="email" label="Email" type="email" autocomplete="username" required autofocus />
                            <x-admin.field name="password" label="Password" type="password" autocomplete="current-password" required />
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="size-4 accent-admin-green"> Keep me signed in</label>
                        </div>

                        <button type="submit" class="btn btn-primary mt-6 w-full">Sign in</button>
                    </form>
                    <p class="mt-6 text-center text-sm text-slate-500"><a href="{{ route('home') }}" class="font-semibold text-brand-blue-700 hover:underline">← Back to the website</a></p>
                </div>
            </main>
        </div>
    </body>
</html>
