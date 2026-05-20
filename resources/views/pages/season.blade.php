@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background:#f8f9fa;">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0" style="border-radius:20px;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🌾 Season Management</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Block</th>
                                <th>Plot</th>
                                <th>Season</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($seasons as $index => $season)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $season->block_name }}</td>
                                    <td>{{ $season->plot_name }}</td>
                                    <td>{{ $season->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($season->start_date)->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($season->end_date)->format('d M Y') }}</td>
                                    <td>{{ $season->year }}</td>
                                    <td>
                                        @if ($season->status == 'Active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif ($season->status == 'closed')
                                            <span class="badge bg-secondary">Closed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($season->status == 'Active')
                                            <form action="{{ route('seasons.close', $season->id) }}" method="POST" onsubmit="return confirm('Close this season?')">
                                                @csrf
                                                @method('POST')
                                                <button class="btn btn-danger btn-sm">Close</button>
                                            </form>
                                        @else
                                            <button class="btn btn-outline-secondary btn-sm" disabled>—</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted">No season data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
