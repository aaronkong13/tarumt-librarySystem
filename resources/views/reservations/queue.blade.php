<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Queue - {{ $book->title }} - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        @include('layouts.sidebar', ['active' => 'reservations'])

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Reservation Queue</h2>
                    <p class="text-sm text-gray-500">{{ $book->title }}</p>
                </div>
                <a href="{{ route('reservations.all-queues') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>All Queues</span>
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <!-- Book Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if($book->cover_image)
                                <img src="data:image/jpeg;base64,{{ base64_encode($book->cover_image) }}" 
                                     class="w-24 h-32 object-cover rounded-lg shadow" 
                                     alt="{{ $book->title }}">
                            @else
                                <div class="w-24 h-32 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg shadow flex items-center justify-center">
                                    <i class="fa-solid fa-book text-white text-4xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $book->title }}</h3>
                            <p class="text-gray-600 mb-3">
                                <i class="fa-solid fa-user-pen mr-2"></i>{{ $book->author }}
                                <span class="mx-2">•</span>
                                <i class="fa-solid fa-barcode mr-2"></i>{{ $book->isbn }}
                            </p>
                            <div class="flex items-center gap-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium
                                    @if($book->status === 'Available') bg-green-100 text-green-700 border border-green-200
                                    @elseif($book->status === 'Borrowed') bg-amber-100 text-amber-700 border border-amber-200
                                    @elseif($book->status === 'Reserved') bg-purple-100 text-purple-700 border border-purple-200
                                    @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                    <i class="fa-solid fa-circle text-[8px]"></i>
                                    {{ $book->status }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    <i class="fa-solid fa-users mr-1"></i>
                                    {{ count($queue) }} {{ count($queue) === 1 ? 'person' : 'people' }} in queue
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue List -->
                @if(count($queue) > 0)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Reservation Queue (FIFO Order)</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($queue as $reservation)
                                <div class="p-6 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4 flex-1">
                                            <!-- Position Badge -->
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold
                                                    @if($reservation['queue_position'] === 1) bg-indigo-100 text-indigo-700
                                                    @elseif($reservation['queue_position'] === 2) bg-blue-100 text-blue-700
                                                    @elseif($reservation['queue_position'] === 3) bg-purple-100 text-purple-700
                                                    @else bg-gray-100 text-gray-700 @endif">
                                                    #{{ $reservation['queue_position'] }}
                                                </div>
                                            </div>

                                            <!-- User Info -->
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-semibold text-gray-900">{{ $reservation['user_name'] }}</h4>
                                                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full
                                                        @if($reservation['status'] === 'waiting') bg-blue-100 text-blue-700 border border-blue-200
                                                        @elseif($reservation['status'] === 'notified') bg-green-100 text-green-700 border border-green-200
                                                        @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                                        <i class="fa-solid fa-circle text-[6px]"></i>
                                                        {{ $reservation['status_label'] }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600">
                                                    <i class="fa-solid fa-envelope mr-1"></i>{{ $reservation['user_email'] }}
                                                    <span class="mx-2">•</span>
                                                    <i class="fa-solid fa-id-badge mr-1"></i>User ID: {{ $reservation['user_id'] }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Timeline -->
                                        <div class="text-right text-sm">
                                            <p class="text-gray-600 mb-1">
                                                <i class="fa-solid fa-calendar-plus mr-1"></i>
                                                Reserved: {{ \Carbon\Carbon::parse($reservation['reservation_date'])->format('M d, Y') }}
                                            </p>
                                            @if($reservation['notified_at'])
                                                <p class="text-green-600 mb-1">
                                                    <i class="fa-solid fa-bell mr-1"></i>
                                                    Notified: {{ \Carbon\Carbon::parse($reservation['notified_at'])->format('M d, Y H:i') }}
                                                </p>
                                            @endif
                                            @if($reservation['expiry_date'])
                                                <p class="{{ $reservation['remaining_days'] <= 1 ? 'text-red-600' : 'text-gray-600' }}">
                                                    <i class="fa-solid fa-clock mr-1"></i>
                                                    Expires: {{ \Carbon\Carbon::parse($reservation['expiry_date'])->format('M d, Y') }}
                                                    @if($reservation['remaining_days'] !== null)
                                                        ({{ $reservation['remaining_days'] }} day(s) left)
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-users-slash text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Reservations</h3>
                        <p class="text-gray-600">There are no active reservations for this book.</p>
                    </div>
                @endif

            </div>

        </main>

    </div>

</body>
</html>
