 <div class="leaveUser-main">
     @if($employeeInfoUpdateRequest->count() > 0)
          @foreach($employeeInfoUpdateRequest as $emp_info)
               @php
              
                    $profilePicture = App\Helpers\Common::GetAdminResortProfile($emp_info->employee->Admin_Parent_id);
                    
               @endphp
               <div class="leaveUser-block">
                    <div class="img-circle">
                         <img src="{{$profilePicture}}" alt="user" class="img-fluid" />
                    </div>
                    <div>
                         <h6 title="{{$emp_info->employee->resortAdmin->id}}">{{@$emp_info->employee->resortAdmin->full_name}} ({{$emp_info->employee->position->position_title}} - {{$emp_info->employee->department->name}}({{$emp_info->employee->department->code}}))</h6>
                         <p>{{$emp_info->title}}</p>
                    </div>
                    <div>
                         @if($emp_info->status == 'Pending')
                              <a href="{{route('people.info-update.show',$emp_info->id)}}"  data-bs-toggle="modal" data-bs-target="#reqApproval-modal" class="a-linkTheme open-ajax-modal {{$edit_class}}">Update</a>
                              <a href="#" class="a-linkDanger {{$edit_class}}"  data-bs-toggle="modal" data-id="{{$emp_info->id}}" data-bs-target="#reqReject-modal"  >Reject</a>
                         @else
                              <a href="#" class="@if($emp_info->status == 'Approved') a-linkTheme @else a-linkDanger @endif" >{{$emp_info->status}}</a>
                         @endif
                    </div>
               </div>
          @endforeach
     @else
               <div class="jsutify-content-between align-items-center">No Data available in table</div>
     @endif

</div>
<div class="mt-3 d-flex justify-content-end">
     {!! $employeeInfoUpdateRequest->links() !!}
</div>