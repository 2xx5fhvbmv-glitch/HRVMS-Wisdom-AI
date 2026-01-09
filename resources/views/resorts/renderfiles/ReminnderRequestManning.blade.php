






<div class="requestsUser-block ">
    <div class="">
        <div class="img-circle">

            <img src="{{Common::getResortUserPicture(Auth::guard('resort-admin')->user()->id) }}" alt="image">
        </div>
        <div class="">
            <h6>{{ $getNotifications->first_name }}{{ $getNotifications->middle_name }}</h6></h6>
            <p>{{ strtoupper($getNotifications->DepartmentName) }}</p>
        </div>
    </div>
    <div class="dfs">
        <h5>{{ $getNotifications->reminder_message_subject }}</h5>
        <input type="hidden" name="message_id" id="message_id" value="{{ $getNotifications->message_id }}">
    </div>
</div>
<div class="text-center">
    <a href="#sendRespond-modal" data-bs-toggle="modal" class="btn btn-sm btn-theme">Send
        Respond</a>
</div>
