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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>SOS</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6 ">
                <div class="card mb-30">
                    <div class="card-title">
                        <div class="row g-2 align-items-center justify-content-between">
                            <div class="col-auto">
                                <h3>Team Management</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('sos.config.IndexSOSTeamManagement')}}" class="a-link">View Existing</a>
                            </div>
                        </div>
                    </div>
                    <form id = "CreateTeamManagementForm" data-parsley-validate>
                    @csrf
                        <input type="hidden" value="1"  id="AppendTeamMemberCount">
                        <input type="text" class="form-control mb-3" id="team_name" name="team_name" placeholder="Team Name" data-parsley-required="true"
                                        data-parsley-error-message="Please Enter Team Name">
                        <div class="SOSTeam-main">
                            <div class="SOSTeam-block">
                                <div class="row g-2 mb-md-4 mb-3 AppendTeamMember">
                                    <div class="col-sm-6">
                                        <label for="team_member" class="form-label">TEAM MEMBER</label>
                                        <select class="form-select select2t-none" multiple="multiple" aria-label="Default select example" name="employee[0][team_member][]" id="team_member" data-parsley-required="true"
                                        data-parsley-error-message="Please Select employee">
                                            @if($getMembers->isNotEmpty())
                                                @foreach($getMembers as $member)
                                                    <option value="{{$member->id}}">{{$member->first_name}} {{$member->middle_name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="member_role" class="form-label">ROLE</label>
                                        <select class="form-select select2t-none" name="employee[0][member_role]" id="member_role"
                                            aria-label="Default select example" data-parsley-required="true"
                                        data-parsley-error-message="Please Select Role">
                                            <option value="" ></option>
                                            @if($Roles->isNotEmpty())
                                                @foreach($Roles as $role)
                                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm AddAppendTeamMember blockAdd-btn">Add More</a>
                                    </div>
                                </div>
                                <div class="AppendTeamMemberDiv"></div>
                            </div>
                        </div>
                        <div class="mb-md-4 mb-3"> 
                            {{-- <textarea class="form-control" 
                            id="description" name="description" 
                            placeholder="Description" rows="3" 
                            data-parsley-required="true"
                            data-parsley-error-message="Please Enter Description"
                            ></textarea> --}}
                            
                            <textarea class="form-control" 
                                id="team_description" 
                                name="description" 
                                placeholder="Description" 
                                rows="3" 
                                data-parsley-required="true" 
                                data-parsley-required-message="Please Enter Description"
                                data-parsley-validate-script 
                                data-parsley-validate-script-message="Script tags are not allowed." 
                                data-parsley-errors-container="#team-description-error" 
                                data-parsley-pattern="^(?!.*<[^>]*>).*$"></textarea>
                            <div id="team-description-error"></div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                    
                </div>
                <div class="card">
                    <div class="card-title">
                        <div class="row g-2 align-items-center justify-content-between">
                            <div class="col-auto">
                                <h3>Role And Permission Management</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('sos.config.IndexSOSRolesAndPermission')}}" class="a-link">View Existing</a>
                            </div>
                        </div>
                    </div>
                    <form id="CreateRoleAndPermissionForm" data-parsley-validate>
                        @csrf
                        <input type="hidden" value="1"  id="AppendSOSRolesAndPermCount">
                        <div class="rolePermissionManag-main">
                            <div class="rolePermissionManag-block">
                                <div class="row g-2 mb-md-4 mb-3 AppendSOSRolesAndPerm">
                                    <div class="col-sm-6">
                                        <label for="role_name" class="form-label">CREATE ROLE</label>
                                        <input type="text" class="form-control mb-3" id="role_name" name="sos[0][role_name]"placeholder="Role Name" data-parsley-required="true"
                                        data-parsley-error-message="Please Enter Role Name">
                                    </div>

                                    @php
                                        $sosAssignPermissions = config('settings.sosAssignPermissions');
                                    @endphp
                                    <div class="col-sm-6">
                                        <label for="assign_permission" class="form-label">ASSIGN PERMISSION</label>
                                        <select class="form-select select2t-none" name="sos[0][assign_permission][]" multiple="multiple" data-parsley-required="true" id="assign_permissionMain_1" data-id="1" required 
                                        data-parsley-error-message="Please select a permission">
                                            @if( !empty($sosAssignPermissions))
                                                @foreach ($sosAssignPermissions  as $k=>$d)
                                                    <option value="{{ $k }}">{{$d}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm AddAppendRolesPermission blockAdd-btn">Add More</a>
                                    </div>
                                </div>
                                <div class="AppendSOSRolesAndPermDiv"></div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="card">
                    <div class="card-title">
                        <div class="row g-3 align-items-center justify-content-between">
                            <div class="col-auto">
                                <h3>Emergency Types</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('sos.config.IndexSOSEmergencyTypes')}}" class="a-link">View Existing</a>
                            </div>
                        </div>
                    </div>
                    <form id="CreateEmergencyTypesForm" data-parsley-validate>
                    @csrf
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-12">
                                <input type="text" class="form-control" id="emergency_name" name="emergency_name" placeholder="Name Of Emergency" data-parsley-required="true" data-parsley-error-message="Please Enter Emergency Name">
                            </div>
                            <div class="col-12">
                                <textarea class="form-control" id="eme_description" name="description" placeholder="Description"
                                    rows="3" data-parsley-required="true"
                                    data-parsley-required-message="Please Enter Description"
                                    data-parsley-validate-script 
                                    data-parsley-validate-script-message="Script tags are not allowed." 
                                    data-parsley-errors-container="#eme-description-error" 
                                    data-parsley-pattern="^(?!.*<[^>]*>).*$"></textarea>
                                <div id="eme-description-error"></div>
                            </div>
                            <div class="col-12">
                                <label for="assign_default_team" class="form-label">ASSIGN DEFAULT TEAMS</label>
                                <select class="form-select select2t-none" aria-label="Default select example"
                                    id="assign_default_team" name="assign_default_team[]" multiple="multiple" data-parsley-required="true"
                                    data-parsley-error-message="Please Select Default Team">
                                    @if($allTeams->isNotEmpty())
                                        @foreach($allTeams as $team)
                                            <option value="{{$team->id}}">{{$team->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-12">
                                <button id="add-custom-field" type="button" class="btn btn-sm btn-theme">Add custom Field </button>
                            </div>
                            <div class="col-12">
                                <div id="custom-fields-container">
                                   
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

<script type="text/javascript">


    $(document).ready(function () 
    {   
        window.Parsley.addValidator('validateScript', {
            validateString: function(value) {
                // Pattern to match any <script> tags, even with attributes or content
                const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
            },
            messages: {
                en: 'Script tags are not allowed.'
            }
        });

        $('#CreateRoleAndPermissionForm').parsley();
        $('#CreateTeamManagementForm').parsley();
        $('#CreateEmergencyTypesForm').parsley();

        //Team management form start
        $(document).on('click','.AddAppendTeamMember',function() {
            var TeamMemberCount = $("#AppendTeamMemberCount").val();
            TeamMemberCount = parseInt(TeamMemberCount)+1;
            var row=`<div class="row g-md-4 g-3 mb-md-4 mb-3 TeamMember_${TeamMemberCount}">
                <!--Team member -->
                <div class="col-md-6">
                    <label for="team_member" class="form-label fw-bold">Team Member</label>
                    <select class="form-select select2t-none"
                            name="employee[${TeamMemberCount}][team_member][]" 
                            id="TeamMemberMain_${TeamMemberCount}" 
                            data-id="${TeamMemberCount}" 
                            multiple="multiple"
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a team member">
                            @if($getMembers->isNotEmpty())
                                @foreach($getMembers as $member)
                                    <option value="{{$member->id}}">{{$member->first_name}} {{$member->middle_name}}</option>
                                @endforeach
                            @endif
                    </select>
                </div>
                <!-- Role -->
                <div class="col-md-6">
                    <label for="member_role" class="form-label fw-bold">Role</label>
                    <select class="form-select select2t-none"
                            name="employee[${TeamMemberCount}][member_role]" 
                            id="RoleMain_${TeamMemberCount}" 
                            data-id="${TeamMemberCount}" 
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a role">
                            <option value="" ></option>
                            @if($Roles->isNotEmpty())
                                @foreach($Roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                @endforeach
                            @endif
                    </select>
                </div>

                <!-- Remove Button -->
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm TeamMemberRemove" 
                            data-id="${TeamMemberCount}">
                        <i class="fa fa-trash"></i> 
                    </button>
                </div>
            </div>
            `;
            // $(".AppendTeamMember").append(row);
            $(".AppendTeamMemberDiv").append(row);
            
            $("#AppendTeamMemberCount").val(TeamMemberCount);
            $("#TeamMemberMain_"+TeamMemberCount).select2({
                placeholder: "Select Employee",
                allowClear: true,
                width: '100%'        
            });
            $("#RoleMain_"+TeamMemberCount).select2({
                placeholder: "Select Role",
                allowClear: true,
                width: '100%'        
            });
        }); 
        $(document).on('click','.TeamMemberRemove',function()
        {
            var loction =$(this).data('id');
            $(".TeamMember_"+loction).remove();
            var count = parseInt($("#AppendTeamMemberCount").val())-1;
            $("#AppendTeamMemberCount").val(count);
        });
        //Team management form end
        
        //Roles and Permission form start
        $(document).on('click','.AddAppendRolesPermission',function() {
            var SoSRolesCount = $("#AppendSOSRolesAndPermCount").val();
            SoSRolesCount = parseInt(SoSRolesCount)+1;

            var permissionOptions = `
                @if(!empty($sosAssignPermissions))
                    @foreach($sosAssignPermissions as $k => $d)
                        <option value="{{ $k }}">{{ $d }}</option>
                    @endforeach
                @endif
            `;

            var row=`    <div class="row g-md-4 g-3 mb-md-4 mb-3 RolesAndPerm_${SoSRolesCount}">
                <!-- Role Name -->
                <div class="col-md-6">

                <label for="role_name" class="form-label">Role Name</label>
                <input type="text" class="form-control" id="role_name"  name="sos[${SoSRolesCount}][role_name]" placeholder="Role Name" required
                                data-parsley-required="true"
                                data-parsley-error-message="Please Enter Role Name" aria-label="Default select example">
                </div>

                <!-- Permission -->

                <div class="col-md-6">
                    <label for="assign_permission" class="form-label fw-bold">Assign Permission</label>
                    <select class="form-select select2t-none" multiple="multiple"
                        name="sos[${SoSRolesCount}][assign_permission][]" 
                        id="assign_permissionMain_${SoSRolesCount}" 
                        data-id="${SoSRolesCount}" 
                            required
                        data-parsley-required="true"
                        data-parsley-error-message="Please select a permission">
                            ${permissionOptions}
                    </select>
                </div>

                <!-- Remove Button -->
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm RolesAndPermRemove" 
                            data-id="${SoSRolesCount}">
                        <i class="fa fa-trash"></i> 
                    </button>
                </div>
            </div>
            `;
            $(".AppendSOSRolesAndPermDiv").append(row);
            $("#AppendSOSRolesAndPermCount").val(SoSRolesCount);
            $("#assign_permissionMain_"+SoSRolesCount).select2({
                placeholder: "Select Permission",
                allowClear: true,
                width: '100%'        
            });
        }); 

        $(document).on('click','.RolesAndPermRemove',function()
        {
            var loction =$(this).data('id');
            $(".RolesAndPerm_"+loction).remove();
            var count = parseInt($("#AppendSOSRolesAndPermCount").val())-1;
            $("#AppendSOSRolesAndPermCount").val(count);
        });
        //Roles and Permission form end
        
        // Initialize Select2
        $('.select2t-none').select2({
            allowClear: true,
            closeOnSelect: false
        });

        $("#member_role").select2({
            placeholder: "Select Role",
            allowClear: true,
            width: '100%'        
        });
        $("#team_member").select2({
            placeholder: "Select Employee",
            allowClear: true,
            width: '100%'        
        });
        $("#assign_permissionMain_1").select2({
            placeholder: "Select Permission",
            allowClear: true,
            width: '100%'        
        });
        $("#assign_default_team").select2({
            placeholder: "Select Team",
            allowClear: true,
            width: '100%'        
        });

        $('#CreateRoleAndPermissionForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            
            if (form.parsley().isValid()) {
                let formData = new FormData(this);
                
                $.ajax({
                    url: "{{ route('sos.config.SOSRolesAndPermissionStore') }}", 
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message,"Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                            form[0].reset();
                            $('.AppendSOSRolesAndPermDiv').empty(); // remove appended fields

                            // Reset the team member count
                            $("#AppendSOSRolesAndPermCount").val(1);
                            $('#assign_permissionMain_1').val('').trigger('change');
                        } else {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;

                        if (errors.error) { 
                            // If it's a duplicate entry error
                            toastr.error(errors.error, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            // If it's a validation error
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });

                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                });
            }
        });

        $('#CreateTeamManagementForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            
            if (form.parsley().isValid()) {
                let formData = new FormData(this);
                
                $.ajax({
                    url: "{{ route('sos.config.SOSTeamStore') }}", 
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message,"Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                            form[0].reset();
                            $('.AppendTeamMemberDiv').empty(); // remove appended fields

                            // Reset the team member count
                            $("#AppendTeamMemberCount").val(1);
                            $('#team_member').val('').trigger('change');
                            $('#member_role').val('').trigger('change');
                        } else {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;

                        if (errors.error) { 
                            // If it's a duplicate entry error
                            toastr.error(errors.error, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            // If it's a validation error
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });

                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                });
            }
        });

        document.getElementById('add-custom-field').addEventListener('click', function() {
            const container = document.createElement('div');
            container.classList.add('row', 'mb-2','g-md-4','g-3');
            container.innerHTML = `
                <div class="col-6">
                    <input type="text" name="custom_field_names[]" class="form-control" placeholder="Field Name">
                </div>
                <div class="col-6">
                    <input type="text" name="custom_field_values[]" class="form-control" placeholder="Field Value">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger remove-custom-field">Remove</button>
                </div>
            `;
            document.getElementById('custom-fields-container').appendChild(container);
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-custom-field')) {
                event.target.closest('.row').remove();
            }
        });

        $('#CreateEmergencyTypesForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            
            if (form.parsley().isValid()) {
                let formData = new FormData(this);
                
                $.ajax({
                    url: "{{ route('sos.config.SOSEmergencyTypeStore') }}", 
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message,"Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                            form[0].reset();
                            $('#custom-fields-container').empty(); // remove appended fields
                            $('#assign_default_team').val('').trigger('change');
                        } else {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;

                        if (errors.error) { 
                            // If it's a duplicate entry error
                            toastr.error(errors.error, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            // If it's a validation error
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });

                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                });
            }
        });
        

       
    });

</script>

@endsection
