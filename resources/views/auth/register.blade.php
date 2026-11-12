<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — CargoTrace KilatLogix Express (KLX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-[#0F172A] via-slate-900 to-slate-950 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200">
        <div class="bg-[#0F172A] px-8 py-6 text-white border-b-4 border-[#FF5500]">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="px-2.5 py-1 rounded-lg bg-[#FF5500] font-black text-xs tracking-wider">KLX</span>
                <span class="font-bold text-lg">Customer Registration</span>
            </div>
            <p class="text-xs text-slate-300">Create your KilatLogix Express account to track and manage your shipments.</p>
        </div>

        <div class="p-8">
            @include('partials.flash')

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Company</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#FF5500] outline-none">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white font-bold text-sm shadow-lg transition">
                    Create Customer Account
                </button>
            </form>

            <div class="mt-5 text-center text-xs text-slate-500">
                Already have an account? <a href="{{ route('login') }}" class="text-[#FF5500] font-bold hover:underline">Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>

