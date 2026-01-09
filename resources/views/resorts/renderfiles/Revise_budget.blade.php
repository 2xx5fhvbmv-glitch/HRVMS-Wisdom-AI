
<div class="requestsUser-block ">
    <div class="">
        <div class="img-circle">

           <img src="{{Common::getResortUserPicture(Auth::guard('resort-admin')->user()->id) }}" alt="image">
        </div>
        <div class="">


            <h6>{{ $getNotifications['BudgetStatus']->first_name }}{{ $getNotifications['BudgetStatus']->middle_name }}</h6></h6>
            <p>{{ strtoupper($getNotifications['BudgetStatus']->DepartmentName) }}</p>
        </div>
    </div>
    <div class="dfs">
        <input type="hidden" name="text" id="message_id" value="{{(isset( $getNotifications['BudgetStatus']->message_id)?   $getNotifications['BudgetStatus']->message_id :'sdfdff') }}">
        <h5>{{ (isset($getNotifications['BudgetStatus']->reminder_message_subject )) ? $getNotifications['BudgetStatus']->reminder_message_subject : $getNotifications['BudgetStatus']->message_subject }}</h5>
    </div>
    <input type="hidden" name="Budget_id" id="Budget_id" value="{{ $getNotifications['BudgetStatus']->Budget_id }}">

</div>
<div class="text-center">
    <a href="#sendRespond-modal" data-bs-toggle="modal" class="btn btn-sm btn-theme">Send Respond</a>
</div>
