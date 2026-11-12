<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CargoTrace KilatLogix Express (KLX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#0F172A] via-slate-900 to-slate-950 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
        <div class="bg-[#0F172A] px-8 py-6 text-white border-b-4 border-[#FF5500]">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="px-2.5 py-1 rounded-lg bg-[#FF5500] font-black text-xs tracking-wider">KLX</span>
                <span class="font-bold text-lg">CargoTrace Portal</span>
            </div>
            <p class="text-xs text-slate-300">Sign in to access your Admin, Operator, or Customer workspace.</p>
        </div>

        <div class="p-8">
            @include('partials.flash')

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] focus:border-[#FF5500] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] focus:border-[#FF5500] outline-none">
                </div>
                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#FF5500]">
                        Remember me
                    </label>
                    <a href="{{ route('public.track') }}" class="text-[#FF5500] font-semibold hover:underline">Public Track &rarr;</a>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white font-bold text-sm shadow-lg shadow-orange-500/25 transition">
                    Sign In to Workspace
                </button>
            </form>

            <!-- Demo Account Quick-Fill -->
            <div class="mt-6 pt-5 border-t border-slate-200">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Demo Accounts (Password: password)</div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillDemo('admin@kilatlogix.test')" class="px-3 py-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-semibold text-left border border-indigo-200">
                        Admin Control Tower
                    </button>
                    <button type="button" onclick="fillDemo('staff@kilatlogix.test')" class="px-3 py-2 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-semibold text-left border border-teal-200">
                        Hub Operator Desk
                    </button>
                    <button type="button" onclick="fillDemo('customer1@kilatlogix.test')" class="px-3 py-2 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-800 font-semibold text-left border border-orange-200">
                        Customer 1 Portal
                    </button>
                    <button type="button" onclick="fillDemo('customer2@kilatlogix.test')" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-left border border-slate-200">
                        Customer 2 Portal
                    </button>
                </div>
            </div>

            <div class="mt-5 text-center text-xs text-slate-500">
                New customer? <a href="{{ route('register') }}" class="text-[#FF5500] font-bold hover:underline">Create an account</a>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
        }
    </script>
</body>
</html>

