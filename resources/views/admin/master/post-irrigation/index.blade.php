@extends('layouts.app')

@section('content')
<div class="contFainer">
    <div class="d-flex justify-content-between mb-3">
        <form method="GET" action="{{ route('post-irrigation.index') }}">
            <select name="block" onchange="this.form.submit()" class="form-select">
                <option value="">Select Block</option>
                @foreach($blocks as $block)
                    <option value="{{ $block->block_name }}" {{ request('block') == $block->block_name ? 'selected' : '' }}>
                        {{ $block->block_name }}
                    </option>
                @endforeach
            </select>
        </form>

        <form method="GET" action="{{ route('post-irrigation.index') }}" class="d-flex">
            <input type="hidden" name="block" value="{{ request('block') }}">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search...">
            <button type="submit" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>Sr.No.</th>
                            <th>Plot No</th>
                            <th>Area (Acre)</th>
                            <th>Type of Irrigation</th>
                            <th>Irrigation No.</th>
                            <th>Irrigation Date</th>
                            <th>Source of Water</th>
                            <th>Capacity</th>
                            <th>Day After Sowing</th>
                            <th>Electricity Consumption</th>
                            <th>Time</th>
                        
                            <th>Major Maintenance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($irrigationData as $index => $data)
                            <tr class="{{ $index % 2 == 0 ? 'bg-light-green' : '' }}">
                                <td>{{ ($irrigationData->currentPage() - 1) * $irrigationData->perPage() + $loop->iteration }}</td>
                                <td>{{ $data->plot_name }}</td>
                                <td>{{ $data->area_acre }}</td>
                                <td>{{ $data->irrigation_type }}</td>
                                <td>{{ $data->irrigation_no }}</td>
                                <td>{{ \Carbon\Carbon::parse($data->irrigation_date)->format('d-m-Y') }}</td>
                                <td>{{ $data->water_source }}</td>
                                <td>{{ $data->capacity }} LPH</td>
                                <td>{{ $data->day_ofter_swowing }}</td>
                                <td>{{ $data->electricity_units }} Units (₹{{ $data->electricity_cost }})</td>
                                <td>{{ $data->consumption_time }} hrs</td>
                            
                                <td>{{ $data->major_maintenance }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">No irrigation records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
    <div class="col-md-12">
    {{ $irrigationData->appends(['block' => $selectedBlock, 'search' => $search])->links('pagination::bootstrap-5') }}
</div>

    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-submit the form when block selection changes
        $('#blockSelector').change(function() {
            $('#blockFilterForm').submit();
        });
    });
</script>
@endpush
@endsection