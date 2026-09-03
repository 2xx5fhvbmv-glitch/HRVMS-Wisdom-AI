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
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control"/>
                                    </div>
                                    <div class="col-6">
                                        <label for="position" class="form-label">Select Positions:</label>
                                        <select name="positions" id="positions" class="dd-native-select">
                                            <option value="">Select Positions</option>
                                            @if($positions)
                                                @foreach($positions as $pos)
                                                    <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="dd" data-target="#positions">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Positions</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Position">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($positions)
                                                        @foreach($positions as $pos)
                                                        <div class="dd-item" role="option" data-value="{{ $pos->id }}"><span class="dd-nm">{{ $pos->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="save-form" class="btn ta-btn-primary btn-sm">Save Form</button>
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
            // Per-field "Responder Roles" attribute. HR picks who fills each
            // field when building the form; only matching roles can edit
            // the field at submit time. Empty / missing = everyone can fill
            // (backward-compatible with old forms).
            const RESPONDER_ROLE_OPTIONS = {
                'GM': 'GM',
                'EXCOM': 'EXCOM',
                'HOD': 'HOD',
                'HR': 'HR',
                'MGR': 'MGR',
                'SUP': 'SUP',
                'LINE WORKERS': 'LINE WORKERS',
                'Finance': 'Finance',
                'Self': 'Self (the candidate)'
            };
            // formBuilder looks up custom attrs by `type + '-' + subtype`
            // (e.g. "header-h1") whenever a field has a subtype set — a
            // per-type key like "header" (added for every field type,
            // incl. the 'button' the user once noticed was missing the
            // picker) only ever matches a header BEFORE its H1/H2/H3 level
            // is chosen, so picking a level made this section vanish. The
            // '*' key applies to every field regardless of subtype,
            // sidestepping that lookup entirely — covers every type
            // (including button) in one entry instead of enumerating them.
            const typeUserAttrs = {
                '*': {
                    responder_roles: {
                        label: 'Responder Roles (who fills)',
                        multiple: true,
                        options: RESPONDER_ROLE_OPTIONS
                    }
                }
            };

            const options = {
                disableFields: ['autocomplete', 'button'],
                i18n: {
                    locale: 'en-US',
                    override: {}
                },
                typeUserAttrs: typeUserAttrs
            };

            const formBuilder =$('#form-builder').formBuilder(options);
            $('#save-form').click(function () {
                const formStructure = formBuilder.actions.getData('json');
                // console.log(formStructure);
                    $.ajax({
                        url: "{{ route('interview-assessment.store') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            position : $('#positions').val(),
                            form_name: $('#form_name').val(), // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response) {
                            toastr.success('Form saved successfully!', "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(() => {
                                window.location.href = "{{ route('interview-assessment.index') }}";
                            }, 2000);
                        },
                        error: function (xhr) { // Add xhr here
                            let errs = '';

                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }

                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
            });
        });
    </script>
@include('resorts._dropdown_script')
@endsection
