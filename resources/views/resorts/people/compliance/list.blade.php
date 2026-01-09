@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
               <div class="page-hedding">
                    <div class="row  g-3">
                         <div class="col-auto">
                         <div class="page-title">
                              <span>People</span>
                              <h1>{{ $page_title }}</h1>
                         </div>
                         </div>
                         <div class="col-auto  ms-auto"><a class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('people.compliance.index',config('settings.resort_permissions.create')) == false) d-none @endif" href="{{route('people.compliance.run')}}">Run Compliance Check Now</a></div>
                    </div>
               </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                              <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                   <div class="input-group">
                                        <input type="search" class="form-control search" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                   </div>
                              </div>

                              <div class="col-auto ms-auto">
                                   <a id="compliance-download-btn" href="{{route('people.compliance.download')}}" class="btn btn-theme me-2">Download</a>
                              </div>
                        </div>
                    </div>
                    <table id="table-exitclearance-form" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Module Name</th>
                                <th>Compliance Breached</th>
                                <th>Employee Id</th>
                                <th>Employee Name</th>
                                <th>Description</th>
                                <th>Reported On</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endsection

    @section('import-css')
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
     $(document).ready(function() {
          updateDownloadUrl();
          $(".select2t-none").select2();
        

           ComplianceIndex();
          $('.search').on('keyup', function() {
               ComplianceIndex();
               updateDownloadUrl();
          });

          // Function to update the download URL with current filters
          function updateDownloadUrl() {
               var searchTerm = $('.search').val();
               var downloadUrl = "{{route('people.compliance.download')}}?searchTerm=" + searchTerm;
               $('.card-header a[href*="people.compliance.download"]').attr('href', downloadUrl);
          }

          $(document).on('click', '.dismmisal', function(e) {
               e.preventDefault();
               var complianceId = $(this).data('id');
               Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to dismiss this compliance?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, dismiss it!'
               }).then((result) => {
                    if (result.isConfirmed) {
                         $.ajax({
                              url: "{{ route('people.compliances.dismiss', '') }}/" + complianceId,
                              type: 'GET',
                              success: function(response) {
                                   Swal.fire(
                                        'Dismissed!',
                                        'Compliance has been dismissed.',
                                        'success'
                                   );
                                  ComplianceIndex();
                              },
                              error: function(xhr, status, error) {
                                   Swal.fire(
                                        'Error!',
                                        'Something went wrong. Please try again.',
                                        'error'
                                   );
                              }
                         });
                    }
               });
          });
     });

     var viewForm;
     function ComplianceIndex()
     {
          if ($.fn.DataTable.isDataTable('#table-exitclearance-form')) {
               viewForm.destroy();
          }
          $('#table-exitclearance-form tbody').empty();
          viewForm = $('#table-exitclearance-form').DataTable({
               searching: false,
               bLengthChange: false,
               bFilter: true,
               bInfo: true,
               bAutoWidth: false,
               scrollX: true,
               iDisplayLength: 10,
               processing: true,
               serverSide: true,
               order:[[9, 'desc']],
               ajax: {
                    url: '{{ route("people.compliance.list") }}',
                    type: 'GET',
                    data: function(d) {
                         var searchTerm = $('.search').val();
                         d.searchTerm = searchTerm;
                    }
               },
          columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'module_name', name: 'module_name', className: 'text-nowrap' },
                    { data: 'compliance_breached_name', name: 'compliance_breached_name' },
                    { data: 'employee_id', name: 'employee_id' },
                    { data: 'employee_name', name: 'employee_name' },
                    { data: 'description', name: 'description' },
                    { data: 'reported_on', name: 'reported_on' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at',visible:false,searchable:false},
               ]
          });
     }
    </script>
    @endsection
