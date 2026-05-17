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
               if(in_array($key, ['first_name', 'middle_name', 'last_name', 'personal_phone'])){
                    $data = App\Models\ResortAdmin::where('id',$emp_info->employee->Admin_Parent_id)->value($key);

               }else{
                    $data = App\Models\Employee::where('id', $emp_info->employee_id)->value($key);
                    if($data == null){
                         $data = $emp_info->employee->resortAdmin->$key ?? '';
                    }
               }
          @endphp

          {{-- Show only the fields the employee actually changed — the
               payload often carries the whole profile, not just edits. --}}
          @continue(trim((string) $data) === trim((string) $newValue))
          @php $changedCount++; @endphp

          {{-- Only the requested new value is shown — the current value is
               omitted per request. --}}
          <div class="col-sm-6">
               <div class="bg-themeGrayLight h-100">
                    <h6>{{ucwords(str_replace('_', ' ', $key))}}</h6>
                    <p>{{$newValue}}</p>
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
     <a href="javascript:void();" class="btn btn-danger"  data-bs-toggle="modal" data-id="{{$emp_info->id}}" data-bs-target="#reqReject-modal" >Reject</a>

     {{-- <a href="{{route('people.info-update.status-change',['id'=>$emp_info->id,'status'=>'reject'])}}" class="btn btn-themeDanger">Reject</a> --}}
 </div>
