<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Management - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.hiding {
            animation: slideOut 0.3s ease-out forwards;
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        @include('layouts.sidebar', ['active' => 'users'])

        <main class="flex-1 md:ml-64 relative">
            
            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">User Management</h2>
                    <p class="text-sm text-gray-500">Manage system users, roles, and permissions</p>
                </div>
                <a href="{{ route('users.create') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                    <i class="fa-solid fa-plus mr-2"></i> Create New User
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <i class="fa-solid fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $users->total() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <i class="fa-solid fa-user-graduate text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Students</dt>
                                <dd class="text-lg font-bold text-gray-900">
                                    {{ App\Models\User::where('role', 'Student')->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            @if(Auth::user()->role !== 'Staff')
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <i class="fa-solid fa-user-tie text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Staff</dt>
                                <dd class="text-lg font-bold text-gray-900">
                                    {{ App\Models\User::where('role', 'Staff')->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active</dt>
                                <dd class="text-lg font-bold text-gray-900">
                                    @if(Auth::user()->role === 'Admin')
                                        {{ App\Models\User::where('status', 'Active')->where('role', '!=', 'Admin')->count() }}
                                    @elseif(Auth::user()->role === 'Staff')
                                        {{ App\Models\User::where('status', 'Active')->whereNotIn('role', ['Admin', 'Staff'])->count() }}
                                    @else
                                        {{ App\Models\User::where('status', 'Active')->count() }}
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-check text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-md bg-red-50 p-4 mb-6 border-l-4 border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-xmark text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
            <!-- Filter Section -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-search text-gray-400"></i>
                        </div>
                        <input type="text" 
                               id="searchInput"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Search by name or email...">
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <select id="roleFilter" 
                                class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Roles</option>
                            <option value="Student">Student</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select id="statusFilter" 
                                class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="flex gap-2">
                        <select id="sortFilter" 
                                class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="name">Sort by Name</option>
                            <option value="role">Sort by Role</option>
                            <option value="created_at">Sort by Date</option>
                        </select>
                        <button id="clearFilters" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors whitespace-nowrap">
                            <i class="fa-solid fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Container with Loading State -->
            <div id="userTableContainer" class="relative min-h-[400px]">
                <!-- Loading Overlay -->
                <div id="loadingOverlay" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                    <div class="text-center">
                        <div class="spinner mx-auto mb-3"></div>
                        <p class="text-gray-600 text-sm">Loading users...</p>
                    </div>
                </div>

                <!-- Users Table -->
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User Info
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors duration-150" data-user-id="{{ $user->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if($user->profile_image)
                                            <img src="data:image/jpeg;base64,{{ base64_encode($user->profile_image) }}" 
                                                 alt="{{ $user->name }}" 
                                                 class="h-10 w-10 rounded-full object-cover">
                                        @else
                                            <div class="h-10 w-10 bg-indigo-500 rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold text-sm">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                <div class="text-xs text-gray-500">{{ $user->phone ?? 'No phone' }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->role === 'Admin')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                    </span>
                                @elseif($user->role === 'Staff')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fa-solid fa-user-tie mr-1"></i> Staff
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fa-solid fa-user-graduate mr-1"></i> Student
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $user->status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ $user->created_at->format('Y-m-d') }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @if(app(\App\Services\AccessControlService::class)->canViewUser($user))
                                    <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                        <i class="fa-solid fa-eye text-lg"></i>
                                    </a>
                                @endif

                                @if(app(\App\Services\AccessControlService::class)->canEditUser($user))
                                    <a href="{{ route('users.edit', $user->id) }}" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                                    </a>
                                @endif

                                @if(app(\App\Services\AccessControlService::class)->canDeactivateUser($user))
                                    @if($user->status === 'Active')
                                        <button class="toggle-status-btn text-red-600 hover:text-red-900" 
                                                data-user-id="{{ $user->id }}" 
                                                data-action="deactivate"
                                                title="Deactivate">
                                            <i class="fa-solid fa-ban text-lg"></i>
                                        </button>
                                    @else
                                        <button class="toggle-status-btn text-green-600 hover:text-green-900" 
                                                data-user-id="{{ $user->id }}" 
                                                data-action="activate"
                                                title="Activate">
                                            <i class="fa-solid fa-check-circle text-lg"></i>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-user-slash text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg font-medium">No users found</p>
                                    <p class="text-sm">Get started by adding a new user to the system.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                @if($users->hasPages())
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ $users->firstItem() }}</span> to 
                            <span class="font-medium">{{ $users->lastItem() }}</span> of 
                            <span class="font-medium">{{ $users->total() }}</span> results
                        </div>
                        <div class="pagination-links">
                            {{ $users->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

            </div>

        </main>

    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <!-- AJAX JavaScript -->
    <script>
        // CSRF Token Setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Debounce function for search input
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast rounded-lg shadow-lg p-4 ${
                type === 'success' ? 'bg-green-50 border-l-4 border-green-400' : 'bg-red-50 border-l-4 border-red-400'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa-solid ${type === 'success' ? 'fa-circle-check text-green-400' : 'fa-circle-xmark text-red-400'}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Show/Hide Loading Overlay
        function setLoading(isLoading) {
            const overlay = document.getElementById('loadingOverlay');
            if (isLoading) {
                overlay.classList.remove('hidden');
            } else {
                overlay.classList.add('hidden');
            }
        }

        // Get current filter values
        function getFilters() {
            return {
                q: document.getElementById('searchInput').value,
                role: document.getElementById('roleFilter').value,
                status: document.getElementById('statusFilter').value,
                sort: document.getElementById('sortFilter').value,
            };
        }

        // Update URL with current filters
        function updateURL(filters, page = 1) {
            const params = new URLSearchParams();
            if (filters.q) params.set('q', filters.q);
            if (filters.role) params.set('role', filters.role);
            if (filters.status) params.set('status', filters.status);
            if (filters.sort) params.set('sort', filters.sort);
            if (page > 1) params.set('page', page);
            
            const newURL = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
            window.history.pushState({}, '', newURL);
        }

        // Fetch users via AJAX
        async function fetchUsers(page = 1) {
            try {
                setLoading(true);
                
                const filters = getFilters();
                updateURL(filters, page);
                
                const params = new URLSearchParams({ ...filters, page });
                const response = await fetch(`/users?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch users');
                }

                const data = await response.json();
                updateUserTable(data);
                updatePagination(data);
                
                // Scroll to top of the page
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
            } catch (error) {
                console.error('Error fetching users:', error);
                showToast('Failed to load users. Please try again.', 'error');
            } finally {
                setLoading(false);
            }
        }

        // Update user table with new data
        function updateUserTable(data) {
            const tbody = document.getElementById('userTableBody');
            
            if (data.users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fa-solid fa-user-slash text-4xl mb-3 text-gray-300"></i>
                                <p class="text-lg font-medium">No users found</p>
                                <p class="text-sm">Try adjusting your search or filters.</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = data.users.map(user => `
                <tr class="hover:bg-gray-50 transition-colors duration-150" data-user-id="${user.id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                ${user.profile_image 
                                    ? `<img src="data:image/jpeg;base64,${user.profile_image}" 
                                           alt="${user.name}" 
                                           class="h-10 w-10 rounded-full object-cover">`
                                    : `<div class="h-10 w-10 bg-indigo-500 rounded-full flex items-center justify-center">
                                           <span class="text-white font-semibold text-sm">${user.name.substring(0, 2).toUpperCase()}</span>
                                       </div>`
                                }
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${user.name}</div>
                                <div class="text-xs text-gray-500">ID: ${String(user.id).padStart(4, '0')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${user.email}</div>
                        <div class="text-xs text-gray-500">${user.phone || 'No phone'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${getRoleBadge(user.role)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            user.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                        }">
                            ${user.status}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                        ${formatDate(user.created_at)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        ${getActionButtons(user)}
                    </td>
                </tr>
            `).join('');

            // Re-attach event listeners to new buttons
            attachToggleStatusListeners();
        }

        // Helper function to get role badge HTML
        function getRoleBadge(role) {
            if (role === 'Admin') {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                </span>`;
            } else if (role === 'Staff') {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    <i class="fa-solid fa-user-tie mr-1"></i> Staff
                </span>`;
            } else {
                return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    <i class="fa-solid fa-user-graduate mr-1"></i> Student
                </span>`;
            }
        }

        // Helper function to format date
        function formatDate(dateString) {
            return new Date(dateString).toISOString().split('T')[0];
        }

        // Helper function to get action buttons
        function getActionButtons(user) {
            let buttons = '';
            
            // View button (assuming all users can view)
            if (user.can_view) {
                buttons += `<a href="/users/${user.id}" class="text-blue-600 hover:text-blue-900" title="View">
                    <i class="fa-solid fa-eye text-lg"></i>
                </a>`;
            }
            
            // Edit button
            if (user.can_edit) {
                buttons += `<a href="/users/${user.id}/edit" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                </a>`;
            }
            
            // Toggle status button
            if (user.can_deactivate) {
                if (user.status === 'Active') {
                    buttons += `<button class="toggle-status-btn text-red-600 hover:text-red-900" 
                        data-user-id="${user.id}" 
                        data-action="deactivate"
                        title="Deactivate">
                        <i class="fa-solid fa-ban text-lg"></i>
                    </button>`;
                } else {
                    buttons += `<button class="toggle-status-btn text-green-600 hover:text-green-900" 
                        data-user-id="${user.id}" 
                        data-action="activate"
                        title="Activate">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </button>`;
                }
            }
            
            return buttons;
        }

        // Update pagination
        function updatePagination(data) {
            const container = document.getElementById('paginationContainer');
            
            if (data.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium">${((data.current_page - 1) * data.per_page) + 1}</span> to 
                        <span class="font-medium">${Math.min(data.current_page * data.per_page, data.total)}</span> of 
                        <span class="font-medium">${data.total}</span> results
                    </div>
                    <div class="flex space-x-2">
            `;

            // Previous button
            if (data.current_page > 1) {
                paginationHTML += `
                    <button onclick="fetchUsers(${data.current_page - 1})" 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Previous
                    </button>
                `;
            }

            // Page numbers
            const maxPages = 5;
            let startPage = Math.max(1, data.current_page - Math.floor(maxPages / 2));
            let endPage = Math.min(data.last_page, startPage + maxPages - 1);
            
            if (endPage - startPage < maxPages - 1) {
                startPage = Math.max(1, endPage - maxPages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button onclick="fetchUsers(${i})" 
                            class="px-3 py-1 border rounded-md text-sm font-medium ${
                                i === data.current_page 
                                    ? 'bg-indigo-600 text-white border-indigo-600' 
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                            }">
                        ${i}
                    </button>
                `;
            }

            // Next button
            if (data.current_page < data.last_page) {
                paginationHTML += `
                    <button onclick="fetchUsers(${data.current_page + 1})" 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next
                    </button>
                `;
            }

            paginationHTML += `
                    </div>
                </div>
            `;

            container.innerHTML = paginationHTML;
        }

        // Toggle user status (Activate/Deactivate)
        async function toggleUserStatus(userId, action) {
            try {
                const url = action === 'activate' 
                    ? `/users/${userId}/restore` 
                    : `/users/${userId}`;
                
                const method = action === 'activate' ? 'POST' : 'DELETE';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to update user status');
                }

                const data = await response.json();
                
                // Show success message
                showToast(data.message || `User ${action === 'activate' ? 'activated' : 'deactivated'} successfully!`, 'success');
                
                // Refresh the user list
                const params = new URLSearchParams(window.location.search);
                const currentPage = parseInt(params.get('page')) || 1;
                fetchUsers(currentPage);
                
            } catch (error) {
                console.error('Error toggling user status:', error);
                showToast('Failed to update user status. Please try again.', 'error');
            }
        }

        // Attach event listeners to toggle status buttons
        function attachToggleStatusListeners() {
            document.querySelectorAll('.toggle-status-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const action = this.dataset.action;
                    
                    const confirmMessage = action === 'activate' 
                        ? 'Are you sure you want to activate this user?' 
                        : 'Are you sure you want to deactivate this user?';
                    
                    if (confirm(confirmMessage)) {
                        toggleUserStatus(userId, action);
                    }
                });
            });
        }

        // Initialize filters and event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Load filters from URL
            const params = new URLSearchParams(window.location.search);
            if (params.get('q')) document.getElementById('searchInput').value = params.get('q');
            if (params.get('role')) document.getElementById('roleFilter').value = params.get('role');
            if (params.get('status')) document.getElementById('statusFilter').value = params.get('status');
            if (params.get('sort')) document.getElementById('sortFilter').value = params.get('sort');

            // Search input with debounce
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', debounce(function() {
                fetchUsers();
            }, 300));

            // Filter changes (auto-submit)
            document.getElementById('roleFilter').addEventListener('change', () => fetchUsers());
            document.getElementById('statusFilter').addEventListener('change', () => fetchUsers());
            document.getElementById('sortFilter').addEventListener('change', () => fetchUsers());

            // Clear filters button
            document.getElementById('clearFilters').addEventListener('click', function() {
                document.getElementById('searchInput').value = '';
                document.getElementById('roleFilter').value = '';
                document.getElementById('statusFilter').value = '';
                document.getElementById('sortFilter').value = 'name';
                fetchUsers();
            });

            // Attach toggle status listeners to initial page load buttons
            attachToggleStatusListeners();
        });
    </script>
</body>
</html>
