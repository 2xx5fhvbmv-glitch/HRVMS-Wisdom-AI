 @extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                            <span>Visa Management</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="ExpDa-fillterbox">
                        <a href="#" class="d-flex align-items-center justify-content-between Categories active" data-flag="all">
                            <p class="mb-0">All Categories</p>
                            <!-- <span>(10)</span> -->
                        </a>
                        <a href="#" class="d-flex align-items-center justify-content-between Categories"  data-flag="work_permit">
                            <p class="mb-0">Work Permit</p>
                            <!-- <span>(10)</span> -->
                        </a>
                        <a href="#" class="d-flex align-items-center justify-content-between Categories" data-flag="slot_payment">
                            <p class="mb-0">Slot Payment</p>
                            <!-- <span>(10)</span> -->
                        </a>
                        <a href="#" class="d-flex align-items-center justify-content-between Categories" data-flag="visa">
                            <p class="mb-0">Visa</p>
                            <!-- <span>(10)</span> -->
                        </a>
                        <a href="#" class="d-flex align-items-center justify-content-between Categories" data-flag="insurance">
                            <p class="mb-0">Insurance</p>
                            <!-- <span>(10)</span> -->
                        </a>
                        <a href="#" class="d-flex align-items-center justify-content-between Categories" data-flag="medical_report">
                            <p class="mb-0">Medical Report Test Fees</p>
                            <!-- <span>(10)</span> -->
                        </a>                        
                    </div>

                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control  Search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-sm-4 col-4">
                            <input type="text" class="form-control datepicker" id="datepickerXpact" placeholder="Select Date" />
                        </div>


                    </div>
                </div>
                <div>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="Quota_Slot_Fee" role="tabpanel" aria-labelledby="tab1">
                            <table id="ExpiryIndex" class="table">
                              
                            </table>
                        </div>
                    </div>
                </div>


            </div>


        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() 
{
    FetchIndexDate();
    $("#datepickerXpact").datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    $(document).on('click', '.Categories', function(e) {
        // Remove 'active' from all

        $('.Categories').removeClass('active');

        // Add 'active' to clicked one
        $(this).addClass('active');

        // Fetch flag and call function
        var flag = $(this).data('flag');
        FetchIndexDate(flag);
    });
    $(document).on('keyup', '.Search', function() 
    {
        var flag = $('.ExpDa-fillterbox .Categories.active').data('flag');
        FetchIndexDate(flag);
    });
    $(document).on('change', '#datepickerXpact', function() {
       var flag = $('.ExpDa-fillterbox .Categories.active').data('flag');
        FetchIndexDate(flag);
    });

});

function FetchIndexDate(flag)
{
        if($.fn.DataTable.isDataTable('#ExpiryIndex'))
        {
            $('#ExpiryIndex').DataTable().destroy();
        }
       var productTable = $('#ExpiryIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: false,
            iDisplayLength: 15,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('resort.visa.Expiry') }}",
                type: 'GET',
                data: function(d)
                {
                    d.flag = flag||'all';
                    d.search = $('.Search').val();
                    d.status = $("#statusFilter").val();
                    d.departmentFilter = $("#departmentFilter").val();
                    d.date = $("#datepickerXpact").val();
                }
            },
            columns: [
                {
                    data: 'profile_view',
                    name: 'profile_view',
                    orderable: false,
                    searchable: false
                }
            ],
            headerCallback: function(thead, data, start, end, display) {
                // Hide the header row
                $(thead).remove();
            }
        });
}
</script>
@endsection
