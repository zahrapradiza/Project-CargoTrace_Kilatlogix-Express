@extends('layouts.admin')

@section('title', 'Users & Role Management')
@section('header', 'Users & Role Access Control')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Create User Form -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-900 mb-4">Create Platform Account</h3>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Role</label>
                    <select name="role" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        <option value="customer">Customer</option>
                        <option value="operator">Operator (Staff)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Company</label>
                    <input type="text" name="company_name" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition">
                    Create User
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <form method="GET" class="flex flex-wrap gap-2 mb-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, company..." class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm">
                <select name="role" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    <option value="">All Roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="operator" @selected(request('role') === 'operator')>Operator</option>
                    <option value="customer" @selected(request('role') === 'customer')>Customer</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">User</th>
                            <th class="py-3 px-3">Company</th>
                            <th class="py-3 px-3">Role</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $user)
                            <tr>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-600">{{ $user->company_name ?: '—' }}</td>
                                <td class="py-3 px-3">
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-1.5">
                                        @csrf @method('PATCH')
                                        <select name="role" class="text-xs rounded-lg border border-slate-300 px-2 py-1 font-semibold">
                                            <option value="admin" @selected($user->role === 'admin')>admin</option>
                                            <option value="operator" @selected($user->role === 'operator')>operator</option>
                                            <option value="customer" @selected($user->role === 'customer')>customer</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100">Save</button>
                                    </form>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Remove this user?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Current</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
