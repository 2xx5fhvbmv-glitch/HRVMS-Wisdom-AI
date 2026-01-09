
<?php $__env->startSection('page_tab_title' ,$page_title); ?>

    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <?php $__env->startSection('content'); ?>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1><?php echo e($page_title); ?></h1>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-5 col-sm-4 col-6">
                            <select class="form-select Department" id="department" name="department">
                                <option ></option>
                                <?php $__currentLoopData = $ResortDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($r->id); ?>"><?php echo e($r->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select Position" name="position" id="position">

                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>

                        <div class="col-auto ms-auto">
                            <a href="javascript::void(0)" class="btn btn-grid "><img src=" <?php echo e(URL::asset('resorts_assets/images/grid.svg')); ?>" alt="icon"></a>
                            <a href="javascript::void(0)" class="btn btn-list active"><img src=" <?php echo e(URL::asset('resorts_assets/images/list.svg')); ?>" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-block">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-ta-employeeslist">
                            <thead>
                                <tr>
                                    <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Position<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Leave</th>
                                    <th>Absent<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Present</th>
                                    <th>Dayoff</th>
                                    <th>Total Working Days<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Total Day Offs<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main d-none ">
                    <div class="row g-md-4 g-3 mb-4">
                        <?php if($employees->isNotEmpty()): ?>
                        <?php
                            if(Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false){
                                $edit_class = 'd-none';
                            } else {
                                $edit_class = '';
                            }
                        ?>
                            <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                    <div class="employeesGrid-block">
                                        <div class="img-circle"><img src=" <?php echo e($e->profile_picture); ?>" alt="image"></div>
                                        <h6><?php echo e($e->name); ?></h6>
                                        <span class="badge badge-themeLight"><?php echo e($e->Emp_id); ?></span>
                                        <p><?php echo e($e->Position); ?></p>
                                        <div class="bg">
                                            <div>
                                                <p>Leave</p>
                                                <p><?php echo e($e->Leave); ?></p>
                                            </div>
                                            <div>
                                                <p>Absent</p>
                                                <p><?php echo e($e->Absent); ?></p>
                                            </div>
                                            <div>
                                                <p>Present</p>
                                                <p><?php echo e($e->Present); ?></p>
                                            </div>
                                            <div>
                                                <p>Dayoff</p>
                                                <p><?php echo e($e->Dayoff); ?></p>
                                            </div>
                                        </div>
                                        <div class="employees-progress">
                                            <span>Working Days</span>
                                            <span><?php echo e($e->Present); ?>/<?php echo e($e->TotalWorkingDays - $e->TotalDayoff); ?></span>
                                            <div class="progress progress-custom progress-themeBlue">
                                                <?php
                                                    // Calculate percentage for working days
                                                    $workingDaysProgress = $e->TotalWorkingDays - $e->TotalDayoff > 0
                                                        ? ($e->Present / ($e->TotalWorkingDays - $e->TotalDayoff)) * 100
                                                        : 0;
                                                ?>
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo e($workingDaysProgress); ?>%"
                                                    aria-valuenow="<?php echo e($workingDaysProgress); ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="employees-progress">
                                            <span>Day Offs</span>
                                            <span><?php echo e($e->Dayoff); ?>/<?php echo e($e->TotalDayoff); ?></span>
                                            <div class="progress progress-custom progress-themeBlue">
                                                <?php
                                                    // Calculate percentage for day offs
                                                    $dayOffProgress = $e->TotalDayoff > 0
                                                        ? ($e->Dayoff / $e->TotalDayoff) * 100
                                                        : 0;
                                                ?>
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo e($dayOffProgress); ?>%"
                                                    aria-valuenow="<?php echo e($dayOffProgress); ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <a target="_blank" href="<?php echo e(route('resort.timeandattendance.employee.details', [ base64_encode($e->employee_id) ])); ?>" class="btn btn-themeSkyblue btn-sm edit_class" data-id="' . $row->id . '">View Details</a>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>

                    </div>
                    <div class="pagination-custom">
                        <nav aria-label="Page navigation example">

                            <?php echo $employees->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4'); ?>


                        </nav>
                    </div>
                </div>

            </div>


        </div>
    </div>

    <?php $__env->stopSection(); ?>
    <?php $__env->startSection('import-css'); ?>

    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('import-scripts'); ?>

    <script type="text/javascript">
    $(document).ready(function () {



        $("#position").select2({
            placeholder: "Position",
            allowClear: true
        });
        $(".Department").select2({
            placeholder: "Department",
            allowClear: true
        });

            $(document).on('change', '.Department', function() {
                var deptId = $(this).val();
                $.ajax({
                    url: "<?php echo e(route('resort.ta.PositionSections')); ?>",
                    type: "post",
                    data: {
                        deptId: deptId
                    },
                    success: function(d) {
                        // Clear the dropdown and add a placeholder option


                        if (d.success == true) {

                            let string = '<option></option>';
                            $.each(d.data.ResortPosition, function(key, value) {
                                string += '<option value="' + value.id + '">' + value
                                    .position_title + '</option>';
                            });
                            $(".Position").html(string);

                            let string1 = '<option></option>';
                            $.each(d.data.ResortSection, function(key, value) {
                                string1 += '<option value="' + value.id + '">' + value
                                    .name + '</option>';
                            });
                            $(".Section").html(string1);

                        }
                    },
                    error: function(response) {
                        toastr.error("Position Not Found", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
        datatablelist()
        const urlParams = new URLSearchParams(window.location.search);

        const currentView = urlParams.get('view')  // Default to 'list'
        // Initialize the view on page load
        setView(currentView);
    });

    function setView(view)
    {
        if(view=="grid")
        {
            $(".btn-grid").trigger("click");
        }
        if(view=="list")
        {
            $(".btn-list").trigger("click");
        }
    }

            $(".btn-grid").click(function () {
                $(this).addClass("active");
                $(".grid-main").addClass("d-block");
                $(".grid-main").removeClass("d-none");
                $(".btn-list").removeClass("active");
                $(".list-main").addClass("d-none");
                $(".list-main").removeClass("d-block");
                DatatableGrid()
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
                $('.table-ta-employeeslist').DataTable().ajax.reload();
                ApplicantProgress();
            });
    $(document).on('input', '.Search', function () {
        EmployeeGrid();
        datatablelist()
    });

    $(document).on('change', '#position', function () {
        EmployeeGrid();
        datatablelist()
    });
    function EmployeeGrid()
    {
        var search = $(".Search").val();
        var Poitions = $("#position").val();
        $.ajax({
                url: "<?php echo e(route('resort.timeandattendance.SearchEmployeegird')); ?>",
                type: "get",
                data: {"_token":"<?php echo e(csrf_token()); ?>","search":search,"Poitions":Poitions},
                success: function (response) {

                    if (response.success)
                    {

                        $(".grid-main").html(response.view);

                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });

    }
    function datatablelist()
    {
        if ($.fn.DataTable.isDataTable('.table-ta-employeeslist'))
        {
            $('.table-ta-employeeslist').DataTable().destroy();
        }

        var divisionTable = $('.table-ta-employeeslist').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[9, 'desc']],
                ajax: {
                    url: "<?php echo e(route('resort.timeandattendance.EmployeeList')); ?>",
                    type: 'GET',
                    data: function(d) {
                        d.position = $("#position").val();
                        d.searchTerm = $('.Search').val();
                    }
                },
                columns: [
                    { data: 'Applicant', name: 'Applicant',},
                    { data: 'Position', name: 'Position' },
                    { data: 'Leave', name: 'Leave' },
                    { data: 'Absent', name: 'Absent' },
                    { data: 'Present', name: 'Present'},
                    { data: 'Dayoff', name: 'Dayoff' },
                    { data: 'TotalWorkingDay', name: 'TotalWorkingDay' },
                    { data: 'TotalDayOffs', name: 'TotalDayOffs' },
                    { data: 'Action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ]

            });
    }
    $(document).on('click', '#clearFilter', function () {
        $(".Search").val('');
        $("#department").val('').trigger('change');
        $("#position").val('').trigger('change');
        EmployeeGrid();
        datatablelist();
    });
    </script>
    <?php $__env->stopSection(); ?>

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/employee/index.blade.php ENDPATH**/ ?>