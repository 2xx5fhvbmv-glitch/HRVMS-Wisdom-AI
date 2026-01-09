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
                            <span>Performance</span>
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

            <div>
                <form id="CreateKPIForm" data-parsley-validate>
                    <div class="card">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-sm-6">
                                <label for="property_goal" class="form-label">Property Goal</label>
                                <input type="text" class="form-control" id="property_goal" name="property_goal"
                                       placeholder="Property Goal" required data-parsley-trigger="keyup">
                            </div>
                            <div class="col-sm-6">
                                <label for="budget" class="form-label">Budget</label>
                                <input type="number" class="form-control" id="budget" name="PropertyGoalbudget"  placeholder="Budget" required min="1" data-parsley-type="number">
                            </div>
                            <div class="col-sm-6">
                                <label for="weightage" class="form-label">WEIGHTAGE (VALUE)</label>
                                <input type="number" class="form-control" id="weightage" name="PropertyGoalweightage"
                                       placeholder="Weightage" required min="1" data-parsley-type="number">
                            </div>
                            <div class="col-sm-6">
                                <label for="score" class="form-label">Score</label>
                                <input type="number" class="form-control" id="score" name="PropertyGoalscore" placeholder="Score" required min="1" data-parsley-type="number">
                            </div>
                        </div>

                        <div class="md-mb-4 mb-3">
                            <a href="#" class="btn btn-themeSkyblue btn-sm createKpi-add">Add More</a>
                        </div>

                        <div class="card-title">
                            <h3>Actual</h3>
                        </div>

                        <div class="createKpi-main">
                            <div class="createKpi-block">
                                <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                                    <div class="col-sm-6">
                                        <label for="budget" class="form-label">Budget</label>
                                        <input type="number" class="form-control" name="budget[]"
                                               placeholder="Budget" required min="1" data-parsley-type="number">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="weightage" class="form-label">WEIGHTAGE (VALUE)</label>
                                        <input type="number" class="form-control" name="weightage[]"
                                               placeholder="Weightage" required min="1" data-parsley-type="number">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="score" class="form-label">Score</label>
                                        <input type="number" class="form-control" name="score[]"
                                               placeholder="Score" required min="1" data-parsley-type="number">
                                    </div>
                                    <div class="col-sm-6 d-none">
                                        <a href="#" class="btn btn-danger createKpi-remove">Remove</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm CreateKPIForm">Send</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {

    $('#CreateKPIForm').parsley();
    $(document).on('submit', '#CreateKPIForm', function(e) {
        e.preventDefault(); // Prevent default form submission

        var form = $(this);

        // Check if form is valid using Parsley
        if (form.parsley().isValid())
        {

            // var formData = new FormData(form); // Use FormData to include file
            $.ajax({
                url: '{{route("Performance.kpi.store")}}',
                type: 'post',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success)
                    {
                        form[0].reset();
                        form.parsley().reset();
                    }
                },
                error: function(xhr) {
                    alert('Something went wrong! Please try again.');
                },
                complete: function() {
                    $('.btn-themeBlue').prop('disabled', false); // Re-enable submit button
                }
            });
        }
    });
});
            $(document).on('click', '.createKpi-add', function (e) {
                e.preventDefault();

                // Clone the first shift-block
                var newBlock = $('.createKpi-block').first().clone();

                newBlock.find('select').each(function () {
                    $(this).prop('selectedIndex', 0); // Reset to first option
                });
                newBlock.find('input').val('');

                newBlock.find('.createKpi-remove').parent('div').removeClass('d-none')

                $('.createKpi-main').append(newBlock);
            });
            $(document).on('click', '.createKpi-remove', function (e) {
                e.preventDefault();

                // Remove the parent .shift-block
                $(this).closest('.createKpi-block').remove();
            });
</script>
@endsection
