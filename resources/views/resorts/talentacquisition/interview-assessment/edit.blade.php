@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
    <style>
        #ta-interview-assessment-edit-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ta-interview-assessment-edit-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="ta-interview-assessment-edit-hero">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="row g-4">
                    <div class="col-12"> <!-- Ensures the card spans the full width -->
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-6 align-items-center justify-content-between">
                                    <div class="col-12"> <!-- Full width for the title section -->
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h2>Create Interview Assessment Form</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-6">
                                        <label for="form_name" class="form-label">Form Name:</label>
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control" value="{{ $form->form_name }}" required/>
                                    </div>
                                    <div class="col-6">
                                        <label for="position" class="form-label">Select Positions:</label>
                                        <select name="positions" id="positions" class="dd-native-select">
                                            <option value="">Select Positions</option>
                                            @if($positions)
                                                @foreach($positions as $pos)
                                                    <option value="{{$pos->id}}" {{ $form->position == $pos->id ? 'selected' : '' }}>{{$pos->position_title}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @php $selectedPos = $positions ? $positions->firstWhere('id', $form->position) : null; @endphp
                                        <div class="dd" data-target="#positions">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">{{ $selectedPos->position_title ?? 'Select Positions' }}</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Position">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item{{ $selectedPos ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($positions)
                                                        @foreach($positions as $pos)
                                                        <div class="dd-item{{ ($form->position == $pos->id) ? ' active' : '' }}" role="option" data-value="{{ $pos->id }}"><span class="dd-nm">{{ $pos->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <label for="form_structure" class="form-label">Form Structure</label>
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="update-form" class="btn ta-btn-primary btn-sm">Update Form</button>

                                <a href="{{ route('interview-assessment.index') }}" class="btn ta-btn-secondary btn-sm">Cancel</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
<style>
    #ui-datepicker-div{
        display:none!important;
    }
</style>
@endsection

@section('import-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
    <script>
        $(document).ready(function () {
            // Load the existing form structure
            let existingFormStructure = @json($form->form_structure);

            // Parse if the form structure is still a string
            if (typeof existingFormStructure === 'string') {
                try {
                    existingFormStructure = JSON.parse(existingFormStructure);
                } catch (error) {
                    console.error('Error parsing form structure:', error);
                    alert('Form structure is invalid.');
                    return;
                }
            }

            // Check if the parsed structure is an array
            if (!Array.isArray(existingFormStructure)) {
                console.error('Parsed form structure is not an array:', existingFormStructure);
                alert('Form structure is invalid.');
                return;
            }

            console.log('Existing Form Structure:', existingFormStructure);

            // Per-field "Responder Roles" attribute — same set used on Create.
            // Lets HR edit which roles can fill each existing field.
            const RESPONDER_ROLE_OPTIONS = {
                'GM': 'GM', 'EXCOM': 'EXCOM', 'HOD': 'HOD', 'HR': 'HR',
                'MGR': 'MGR', 'SUP': 'SUP', 'LINE WORKERS': 'LINE WORKERS',
                'Finance': 'Finance', 'Self': 'Self (the candidate)'
            };
            const FIELD_TYPES = [
                'text','textarea','password','email','phone','url','number','date','time','hidden',
                'select','checkbox','radio','checkbox-group','radio-group','file','autocomplete',
                'header','paragraph','button','starRating','ratingTable'
            ];
            const typeUserAttrs = {};
            FIELD_TYPES.forEach(function (t) {
                typeUserAttrs[t] = {
                    responder_roles: {
                        label: 'Responder Roles (who fills)',
                        multiple: true,
                        options: RESPONDER_ROLE_OPTIONS
                    }
                };
            });

            // Initialize FormBuilder with the role-tagging attribute.
            const formBuilder = $('#form-builder').formBuilder({
                typeUserAttrs: typeUserAttrs
            });

            // Populate FormBuilder with the existing form structure
            formBuilder.promise.then(() => {
                formBuilder.actions.setData(existingFormStructure);
            });

            // Handle the update button click
            $('#update-form').on('click', function () {
                const formName = $('#form_name').val();
                const updatedFormStructure = formBuilder.actions.getData('json'); // Get updated structure

                if (!formName) {
                    toastr.success('Form name is required!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: "{{ route('interview-assessment.update', $form->id) }}",
                    method: 'POST',
                    data: {
                        form_name: formName,
                        position : $('#positions').val(),
                        form_structure: updatedFormStructure,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (response) {
                        toastr.success('Form saved successfully!', "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = "{{ route('interview-assessment.index') }}";
                        }, 2000);
                    },
                    error: function (error) {
                        toastr.success('An error occurred while updating the form.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = "{{ route('interview-assessment.index') }}";
                        }, 2000);
                    },
                });
            });
        });
    </script>
@include('resorts._dropdown_script')
@endsection
