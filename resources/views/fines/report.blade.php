<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Report - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <div class="no-print">
            @include('layouts.sidebar', ['active' => 'fines'])
        </div>

        <main class="flex-1 md:ml-64 relative print:ml-0">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10 no-print">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Fine Report</h2>
                    <p class="text-sm text-gray-500">Generated fine statistics and analysis</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                        <i class="fa-solid fa-print mr-2"></i> Print Report
                    </button>
                    <a href="{{ route('fines.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Back
                    </a>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <!-- Report Header for Print -->
                <div class="hidden print-only mb-8">
                    <h1 class="text-2xl font-bold text-center">BookHub Library - Fine Report</h1>
                    <p class="text-center text-gray-500">Generated on {{ now()->format('F d, Y H:i') }}</p>
                </div>

                <!-- Period Information -->
                <div class="bg-white rounded-2xl p-6 shadow-sm mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Period</h3>
                    <div class="flex items-center gap-4 text-gray-600">
                        <span><strong>From:</strong> {{ $report['period']['start'] }}</span>
                        <span>—</span>
                        <span><strong>To:</strong> {{ $report['period']['end'] }}</span>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-gray-400">
                        <p class="text-sm text-gray-500">Total Fines</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $report['summary']['total_fines'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">RM {{ number_format($report['summary']['total_amount'], 2) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-red-500">
                        <p class="text-sm text-gray-500">Unpaid</p>
                        <p class="text-3xl font-bold text-red-600">{{ $report['summary']['unpaid']['count'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">RM {{ number_format($report['summary']['unpaid']['amount'], 2) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-green-500">
                        <p class="text-sm text-gray-500">Paid</p>
                        <p class="text-3xl font-bold text-green-600">{{ $report['summary']['paid']['count'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">RM {{ number_format($report['summary']['paid']['amount'], 2) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-amber-500">
                        <p class="text-sm text-gray-500">Waived</p>
                        <p class="text-3xl font-bold text-amber-600">{{ $report['summary']['waived']['count'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">RM {{ number_format($report['summary']['waived']['amount'], 2) }}</p>
                    </div>
                </div>

                <!-- Collection Rate -->
                <div class="bg-white rounded-2xl p-6 shadow-sm mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection Rate</h3>
                    @php
                        $totalAmount = $report['summary']['total_amount'];
                        $paidAmount = $report['summary']['paid']['amount'];
                        $collectionRate = $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="bg-green-600 h-4 rounded-full" style="width: {{ $collectionRate }}%"></div>
                            </div>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($collectionRate, 1) }}%</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">
                        RM {{ number_format($paidAmount, 2) }} collected out of RM {{ number_format($totalAmount, 2) }} total fines
                    </p>
                </div>

                <!-- Top Users with Fines -->
                @if($report['top_users']->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fa-solid fa-users text-gray-600 mr-2"></i>
                            Top Users by Fine Amount
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <th class="pb-3 font-medium">#</th>
                                        <th class="pb-3 font-medium">User</th>
                                        <th class="pb-3 font-medium">Total Fines</th>
                                        <th class="pb-3 font-medium">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($report['top_users'] as $index => $userData)
                                        <tr>
                                            <td class="py-4 text-gray-500">{{ $index + 1 }}</td>
                                            <td class="py-4">
                                                <p class="font-medium text-gray-900">{{ $userData['user']->name ?? 'Unknown User' }}</p>
                                                <p class="text-sm text-gray-500">{{ $userData['user']->email ?? '' }}</p>
                                            </td>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">
                                                    {{ $userData['total_fines'] }} fines
                                                </span>
                                            </td>
                                            <td class="py-4 font-semibold text-red-600">
                                                RM {{ number_format($userData['total_amount'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Report Footer -->
                <div class="text-center text-sm text-gray-500 mt-8">
                    <p>This report was generated automatically by BookHub Library Management System</p>
                    <p>Report generated on {{ now()->format('F d, Y \a\t H:i') }}</p>
                </div>

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500 no-print">
                Secure Library Management System &copy; {{ date('Y') }}
            </footer>
        </main>

    </div>

</body>
</html>
