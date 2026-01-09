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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Peple</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
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
                            
                        </div>
                    </div>
                    <table id="itiernariesTable" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Arrival Date</th>
                                <th>Arrival Time</th>
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
            $(".select2t-none").select2();
            $('#itiernariesTable tbody').empty();
            var viewForm = $('#itiernariesTable').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[4, 'desc']],
                ajax: {
                    url: '{{ route("people.onboarding.itinerary.list") }}',
                    type: 'GET',
                    data: function(d) {
                        var searchTerm = $('.search').val();
                        d.searchTerm = searchTerm;
                    }
                },
                columns: [
                    { data: 'employee_name', name: 'employee_name', className: 'text-nowrap'},
                    { data: 'arrival_date', name: 'arrival_date', className: 'text-nowrap'},
                    { data: 'arrival_time', name: 'arrival_time', className: 'text-nowrap'},
                    { data: 'action', name: 'Action', orderable: false, searchable: false },
                    { data:'created_at',visible:false,searchable:false,orderable:false}
                ]
            });
  
            $('.search').on('keyup', function() {
                viewForm.ajax.reload();
            });

            $(document).on('click','#itiernariesTable .delete-row-btn',function(e){
                e.preventDefault();
                var itineraryId = $(this).data('id');
                Swal.fire({
                    title:'Sure want to delete?',
                    text:'This cannot be undone',
                    icon:'warning',
                    showCancelButton:true,
                    confirmButtonText:'Yes',
                    cancelButtonText:'No',
                    confirmButtonColor:"#DD6B55"
                }).then((result)=>{
                    if(result.isConfirmed){
                        $.ajax({
                            type:'DELETE',
                            url:"{{ route('onboarding.itinerary-template.destroy', '') }}/" + itineraryId,
                            dataType:'json',
                            headers:{
                                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                            }
                        }).done(function(result){
                            if(result.success == true){
                                toastr.success(result.message,"Success",{
                                    positionClass:'toast-bottom-right'
                                });
                                viewForm.ajax.reload();
                            }else{
                                toastr.error(result.message,"Error",{
                                    positionClass:'toast-bottom-right'
                                });
                            }
                        }).fail(function(error){
                            toastr.error("Something went wrong","Error",{
                                positionClass:'toast-bottom-right'
                            });
                        });
                    }
                });
            });

        });
    </script>
@endsection
