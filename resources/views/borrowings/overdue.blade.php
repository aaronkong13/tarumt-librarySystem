@extends('layouts.app')

@section('title', 'Overdue Books')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-alarm"></i> Overdue Books Report</h2>
        <a href="{{ route('borrowings.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @php
        $overdueRecords = App\Models\Borrowing::where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->orderBy('due_date')
            ->get();
    @endphp

    {{-- Summary --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-0">{{ $overdueRecords->count() }}</h3>
                    <small>Overdue Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-0">{{ $overdueRecords->unique('user_id')->count() }}</h3>
                    <small>Users with Overdue</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    @php
                        $totalPotentialFines = $overdueRecords->sum(fn($b) => $b->getDaysOverdue() * 0.50);
                    @endphp
                    <h3 class="text-info mb-0">RM {{ number_format($totalPotentialFines, 2) }}</h3>
                    <small>Potential Fines</small>
                </div>
            </div>
        </div>
    </div>

    @if($overdueRecords->isEmpty())
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> No overdue books at the moment!
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-danger">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Book</th>
                                <th>Borrowed</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Potential Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueRecords as $index => $borrowing)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $borrowing->user->name }}</strong><br>
                                        <small class="text-muted">{{ $borrowing->user->email }}</small><br>
                                        <span class="badge bg-secondary">{{ $borrowing->user->role }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $borrowing->book->title }}</strong><br>
                                        <small class="text-muted">{{ $borrowing->book->author }}</small>
                                    </td>
                                    <td>{{ $borrowing->borrow_date->format('M d, Y') }}</td>
                                    <td>{{ $borrowing->due_date->format('M d, Y') }}</td>
                                    <td>
                                        @php $days = $borrowing->getDaysOverdue(); @endphp
                                        <span class="badge bg-danger">{{ $days }} {{ Str::plural('day', $days) }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-danger">RM {{ number_format($borrowing->calculateFine(), 2) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-warning">
                            <tr>
                                <th colspan="6">Total Potential Fines</th>
                                <th>RM {{ number_format($totalPotentialFines, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Grouped by User --}}
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-people"></i> Grouped by User</h5>
            </div>
            <div class="card-body">
                @foreach($overdueRecords->groupBy('user_id') as $userId => $userBorrowings)
                    @php $user = $userBorrowings->first()->user; @endphp
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <span class="badge bg-{{ $user->role == 'Admin' ? 'danger' : ($user->role == 'Staff' ? 'warning' : 'primary') }} ms-2">{{ $user->role }}</span>
                                <br>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger">{{ $userBorrowings->count() }} overdue</span>
                                <br>
                                <small class="text-danger">RM {{ number_format($userBorrowings->sum(fn($b) => $b->calculateFine()), 2) }}</small>
                            </div>
                        </div>
                        <ul class="mb-0">
                            @foreach($userBorrowings as $borrowing)
                                <li>
                                    {{ $borrowing->book->title }}
                                    <span class="text-danger">({{ $borrowing->getDaysOverdue() }} days late)</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
