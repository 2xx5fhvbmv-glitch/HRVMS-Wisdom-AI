<html>
<head>
    <meta charset="utf-8">
    <title>Leave History PDF</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .pdf-container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            padding: 10mm 8mm;
            background-color: #fff;
        }
        .card-header {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #E7E7E7;
        }
        .empDetails-user { display: flex; align-items: center; }
        .img-circle {
            width: 70px;
            height: 70px;
            min-width: 70px;
            margin-right: 12px;
            border-radius: 50%;
            overflow: hidden;
            background: #e9ecef;
        }
        .img-circle img { width: 100%; height: 100%; object-fit: cover; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }
        table th, table td {
            border: 1px solid #E7E7E7;
            padding: 6px 5px;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .balance-table th { width: 22%; }
        .balance-table th:nth-child(2), .balance-table th:nth-child(4) { width: 28%; }
        .history-table th:nth-child(1) { width: 18%; }  /* Category */
        .history-table th:nth-child(2), .history-table td:nth-child(2) { width: 28%; }  /* Reason */
        .history-table th:nth-child(3), .history-table th:nth-child(4) { width: 12%; }
        .history-table th:nth-child(5), .history-table th:nth-child(6) { width: 10%; }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="card-header">
            <div class="row g-md-3 g-2 align-items-center">
                <div class="col-lg">
                    <div class="empDetails-user" style="display: flex;align-items: center;margin-left:5px 5px">
                        <div class="img-circle" style="background:#e9ecef;">
                            @if(!empty($employeeHeader->profile_picture))
                            <img style="width:100px;height:100px;" src="{{ $employeeHeader->profile_picture }}" alt="user">
                            @else
                            <div style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#6c757d;">Photo</div>
                            @endif
                        </div>
                        <div>
                            <h4 style="font-weight: 600;margin-bottom: 5px;">{{ $employeeHeader->first_name ?? '' }} {{ $employeeHeader->last_name ?? '' }}</h4>
                            <p class="badge badge-themeNew">{{ $employeeHeader->Emp_Code ?? '—' }}</p>
                            <p>{{ $employeeHeader->position_title ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="empDetails-leave mb-4">
            <div class="card-title">
                <div class="row g-2 align-items-center">
                        @php $total_leaves_allocated = 0; $total_leaves_available = 0; @endphp
                        @if($leaveBalances)
                            @foreach($leaveBalances as $leaves)
                                @php 
                                    $total_leaves_allocated = $total_leaves_allocated +  $leaves->allocated_days;
                                    $total_leaves_available = $total_leaves_available + ($leaves->available_days ?? $leaves->allocated_days);
                                @endphp   
                            @endforeach
                        @endif
                        <div class="col-auto ms-auto" style="margin-left: auto !important;text-align: right;">
                            <div style="padding: 12px 14px;background: #F5F8F8; border-radius: 15px;display:ruby-text;">
                            <p style="margin:0;">Total Leave Balance: {{ $total_leaves_available }}/<span>{{ $total_leaves_allocated }}</span></p>
                            </div>
                        </div>
                </div>
            </div>
           
            <table class="balance-table" style="background-color: #f8f9fa;">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Used / Allocated Days</th>
                        <th>Leave Type</th>
                        <th>Used / Allocated Days</th>
                    </tr>
                </thead>
                <tbody>
                    @if($leaveBalances && $leaveBalances->isNotEmpty())
                        @foreach($leaveBalances as $index => $child)
                            @if($index % 2 == 0)<tr>@endif
                            <td>{{ $child->leave_type ?? 'N/A' }}</td>
                            <td>{{ $child->used_days }} / {{ $child->available_days ?? $child->allocated_days }}</td>
                            @if($index % 2 == 1 || $index == $leaveBalances->count() - 1)</tr>@endif
                        @endforeach
                    @else
                        <tr><td colspan="4" style="text-align:center;">No balance data</td></tr>
                    @endif
                </tbody>
            </table> 
        </div>

        <div class="card-title">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 style="padding:10px 10px 10px 4px;">Leave History</h3>
                </div>
                <div class="col-auto"><span class="badge badge-themeNew"></span></div>
            </div>
        </div>
        
        <table class="history-table" style="background-color: #f8f9fa;">
            <thead>
                <tr>
                    <th>Leave Category</th>
                    <th>Reason</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Total Days</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @if($leaveUsage->isNotEmpty())
                    @foreach ($leaveUsage as $leave)
                        @if(isset($leave->leave_category))
                        <tr>
                            <td>{{ $leave->leave_category ?? '—' }}</td>
                            <td>{{ Str::limit($leave->reason ?? '—', 80) }}</td>
                            <td>{{ $leave->from_date ?? '—' }}</td>
                            <td>{{ $leave->to_date ?? '—' }}</td>
                            <td>{{ $leave->total_days ?? '—' }}</td>
                            <td>{{ $leave->status ?? '—' }}</td>
                        </tr>
                        @endif
                    @endforeach
                @else
                    <tr><td colspan="6" style="text-align:center;">No records found</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
