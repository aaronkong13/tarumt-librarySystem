{{--
    Reusable Sidebar Component
    Usage: @include('layouts.sidebar', ['active' => 'dashboard'])
    
    Available 'active' values:
    - 'dashboard'
    - 'books'
    - 'borrowings'
    - 'users'
    - 'reports'
    - 'settings'
--}}

<aside class="w-64 bg-[#0F172A] text-white flex-shrink-0 hidden md:flex flex-col fixed h-full z-20">
    {{-- Logo Section --}}
    <div class="h-20 flex items-center px-8 border-b border-gray-800">
        <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
            <i class="fa-solid fa-book-open text-white text-sm"></i>
        </div>
        <div>
            <h1 class="font-bold text-lg tracking-tight">BookHub</h1>
            <p class="text-[10px] text-gray-400 uppercase tracking-wider">Management System</p>
        </div>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 px-4 py-6 space-y-2">
        {{-- Dashboard --}}
        <a href="/dashboard" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'dashboard' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-house w-6"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        {{-- Books (role-aware) --}}
        @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
        <a href="{{ route('books.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'books' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-book w-6"></i>
            <span class="font-medium text-sm">Books Management</span>
        </a>
        @else
        <a href="{{ route('books.catalog') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'books' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-book w-6"></i>
            <span class="font-medium text-sm">Books List</span>
        </a>
        @endif

        {{-- Borrow & Return --}}
        @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
        <a href="{{ route('borrowings.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'borrowings' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-hand-holding w-6"></i>
            <span class="font-medium text-sm">Borrow & Return</span>
        </a>
        @endif

        {{-- User Management (Staff/Admin Only) --}}
        @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
        <a href="{{ route('users.index') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'users' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-users w-6"></i>
            <span class="font-medium text-sm">User Management</span>
        </a>
        @endif

        {{-- Reservations (Student Only) --}}
        @if(Auth::user()->isStudent())
        <a href="{{ route('reservations.my') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'reservations' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-bookmark w-6"></i>
            <span class="font-medium text-sm">My Reservations</span>
        </a>
        @endif

        {{-- Reservation Queues (Staff/Admin Only) --}}
        @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
        <a href="{{ route('reservations.all-queues') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'reservations' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-clipboard-list w-6"></i>
            <span class="font-medium text-sm">Reservation Queues</span>
        </a>
        @endif

        {{-- Borrowing History --}}
        @if(Route::has('borrowings.history'))
        <a href="{{ route('borrowings.history') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'history' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-clock-rotate-left w-6"></i>
            <span class="font-medium text-sm">History</span>
        </a>
        @endif

        {{-- Fines --}}
        @if(Route::has('borrowings.fines'))
        <a href="{{ route('borrowings.fines') }}" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'fines' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-money-bill w-6"></i>
            <span class="font-medium text-sm">Fines</span>
        </a>
        @endif

        {{-- Reports --}}
        <a href="#" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'reports' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-chart-simple w-6"></i>
            <span class="font-medium text-sm">Reports</span>
        </a>

        {{-- Settings --}}
        <a href="#" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors {{ $active === 'settings' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            <i class="fa-solid fa-gear w-6"></i>
            <span class="font-medium text-sm">Settings</span>
        </a>
    </nav>

    {{-- User Profile Section --}}
    <div class="p-4 border-t border-gray-800">
        <a href="{{ route('profile.show') }}" class="block">
            <div class="bg-[#1E293B] rounded-xl p-3 flex items-center gap-3 hover:bg-[#2D3B52] transition-colors cursor-pointer">
                @if(Auth::user()->profile_image)
                    <img src="data:image/jpeg;base64,{{ base64_encode(Auth::user()->profile_image) }}" 
                         alt="{{ Auth::user()->name }}" 
                         class="w-10 h-10 rounded-full object-cover">
                @else
                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <p class="text-sm font-semibold truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-400">{{ ucfirst(Auth::user()->role) }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST" onclick="event.stopPropagation();">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </a>
    </div>
</aside>
