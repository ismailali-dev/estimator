<style>
/* Container */
.table-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 15px;
}

/* Table styling */
.nowrap-table th,
.nowrap-table td {
    white-space: nowrap;
    vertical-align: middle;
}

/* Header */
.nowrap-table thead th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    color: #555;
}

/* Rows */
.nowrap-table tbody tr:hover {
    background: #f5faff;
    transition: 0.2s;
}

/* Padding */
.nowrap-table td {
    padding: 10px;
    font-size: 14px;
}

/* Right align numbers */
.text-right {
    text-align: right;
}

/* Status badges */
.badge-active {
    background: #d4edda;
    color: #155724;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}

.badge-inactive {
    background: #f8d7da;
    color: #721c24;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}

/* Scroll */
.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.nowrap-table th,
.nowrap-table td {
    white-space: nowrap;
    padding: 12px 15px;   /* ✅ proper equal spacing */
    vertical-align: middle;
}

/* Optional: add row spacing feel */
.nowrap-table tbody tr {
    border-bottom: 1px solid #eee;
}
.nowrap-table {
    border-collapse: separate;
    border-spacing: 0 8px; /* space between rows */
}
.nowrap-table {
    border-collapse: collapse;   /* ✅ FIX */
    width: 100%;
}
.nowrap-table th,
.nowrap-table td {
    border: 1px solid #ddd;      /* consistent border */
    padding: 12px 15px;
    white-space: nowrap;
}

.nowrap-table thead th {
    background: #f5f5f5;
}

.nowrap-table tbody tr:hover {
    background: #f9fbff;
}
</style>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-bordered nowrap-table">
            
            <thead>
                <tr>
                    <th>Plan</th>
                    <th class="text-right">Amount</th>
                    <th>Type</th>
                    <th>Admin Allow</th>
                    <th>Status</th>
                    <th class="text-right">Start Date</th>
                    <th class="text-right">Expiry Date</th>
                </tr>
            </thead>

            <tbody>
                @forelse($subs as $sub)
                <tr>
                    <td><strong>{{ $sub->title }}</strong></td>

                    <!-- Amount Right Align -->
                    <td class="text-right">
                        ${{ number_format($sub->amount, 2) }}
                    </td>

                    <td>
                        {{ $sub->renewable_type == 'year' ? 'Yearly' : ($sub->renewable_type == 'month' ? 'Monthly' : $sub->renewable_type) }}
                    </td>

                    <td>
                        {{ $sub->is_admin_allowed ? 'Yes' : 'No' }}
                    </td>

                    <!-- Status Badge -->
                    <td>
                        @if($sub->status == 'active')
                            Active
                        @else
                           {{ ucfirst($sub->status) }}
                        @endif
                    </td>

                    <!-- Dates Right Align -->
                    <td class="text-right">
                        {{ \Carbon\Carbon::parse($sub->created_at)->format('M d, Y') }}
                    </td>

                    <td class="text-right">
                        {{ \Carbon\Carbon::parse($sub->ends_at)->format('M d, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No data found</td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>
</div>