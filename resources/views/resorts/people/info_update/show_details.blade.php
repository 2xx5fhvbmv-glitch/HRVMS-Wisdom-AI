@php
    $payload = $emp_info->info_payload;
@endphp
<div class="modal-body">
     <div class="border-bottom mb-3 pb-3">
          <div class="d-flex align-items-center">
               <div class="img-circle me-2">
                    <img src="{{Common::getResortUserPicture($emp_info->employee->Admin_Parent_id ?? null)}}" alt="user">
               </div>
               <div>
                    <h6 class="mb-1">{{@$emp_info->employee->resortAdmin->full_name}} ({{$emp_info->employee->position->short_title}} - {{$emp_info->employee->department->name}})</h6>
                    <p>{{$emp_info->title}}</p>
               </div>
          </div>
     </div>

     <div class="row g-md-4 g-2 mb-md-3">

          @php $changedCount = 0; @endphp
          @foreach ($payload as $key => $newValue)
          @php
               // Permanent Address (address_line_1/2) lives on ResortAdmin —
               // those columns were dropped from the employees table
               // entirely, so looking them up via Employee::value() below
               // would throw an "Unknown column" SQL error and crash this
               // whole page for any pending request that happens to include
               // an address field.
               if(in_array($key, ['first_name', 'middle_name', 'last_name', 'personal_phone', 'address_line_1', 'address_line_2'])){
                    $data = App\Models\ResortAdmin::where('id',$emp_info->employee->Admin_Parent_id)->value($key);

               }else{
                    $data = App\Models\Employee::where('id', $emp_info->employee_id)->value($key);
                    if($data == null){
                         $data = $emp_info->employee->resortAdmin->$key ?? '';
                    }
               }

               // dob can be stored/submitted in different formats
               // ("1992-09-16" vs "16-Sep-1992") for the same date — compare
               // the normalized form so it isn't shown as "changed" when it
               // isn't. Falls back to the raw string compare below if either
               // side isn't a parseable date.
               $isSameValue = trim((string) $data) === trim((string) $newValue);
               if (!$isSameValue && $key === 'dob' && !empty($data) && !empty($newValue)) {
                    try {
                         $isSameValue = \Carbon\Carbon::parse($data)->format('Y-m-d') === \Carbon\Carbon::parse($newValue)->format('Y-m-d');
                    } catch (\Exception $e) {
                         // Not both parseable dates — keep the raw string comparison result.
                    }
               }
          @endphp

          {{-- Show only the fields the employee actually changed — the
               payload often carries the whole profile, not just edits. --}}
          @continue($isSameValue)
          @php $changedCount++; @endphp

          {{-- Two-column comparison — current value on the left, the value
               the employee requested on the right. Only fields that actually
               changed appear (the @continue above skips unchanged entries). --}}
          <div class="col-sm-6">
               <div class="bg-themeGrayLight h-100">
                    <h6>Current {{ucwords(str_replace('_', ' ', $key))}}</h6>
                    <p>{{ ($data !== null && $data !== '') ? $data : '—' }}</p>
               </div>
          </div>
          <div class="col-sm-6">
               <div class="bg-themeGrayLight h-100">
                    <h6>Requested {{ucwords(str_replace('_', ' ', $key))}}</h6>
                    <p>{{ ($newValue !== null && $newValue !== '') ? $newValue : '—' }}</p>
               </div>
          </div>
          @endforeach

          @if ($changedCount === 0)
               <div class="col-12">
                    <p class="text-muted mb-0">No changed fields in this request.</p>
               </div>
          @endif
     </div>
</div>
<div class="modal-footer">
     <a href="javascript:void();" data-url="{{ route('people.info-update.status-change', ['id' => $emp_info->id, 'status' => 'approve']) }}" class="btn btn-themeBlue" id="update-info-btn">Update</a>
     <a href="javascript:void();" class="btn eb-btn-critical"  data-bs-toggle="modal" data-id="{{$emp_info->id}}" data-bs-target="#reqReject-modal" >Reject</a>

     {{-- <a href="{{route('people.info-update.status-change',['id'=>$emp_info->id,'status'=>'reject'])}}" class="btn btn-themeDanger">Reject</a> --}}
 </div>
@include('resorts._emotional_buttons_v2_styles')
