<table>
    <thead>
        <tr>
            <th>Survey Title</th>
            <th>Created By</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Privacy</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $parent->Surevey_title }}</td>
            <td>{{ $parent->EmployeeName }}</td>
            <td>{{ date("d M Y", strtotime($parent->Start_date)) }}</td>
            <td>{{ date("d M Y", strtotime($parent->End_date)) }}</td>
            <td>{{ $parent->survey_privacy_type }}</td>
        </tr>
    </tbody>
</table>

@foreach($Question as $q)
<table>
    <tr><th colspan="2">Question {{ $loop->iteration }}</th></tr>
    <tr><td>Question</td><td>{{ ucfirst($q->Question_Text) }}</td></tr>
    <tr><td>Type</td><td>{{ ucfirst($q->Question_Type) }}</td></tr>
    <tr><td>Compulsory</td><td>{{ isset($q->Question_Complusory) ? ucfirst($q->Question_Complusory) :"No" }}</td></tr>
</table>
@endforeach

<table>
    <tr><th colspan="2">Participants</th></tr>
    @foreach($participantEmp as $e)
        <tr><td colspan="2">{{ $e->EmployeeName }}</td></tr>
    @endforeach
</table>


@if($parent->Status != "OnGoing" && $parent->Status != "Complete")
<table>
    <tr>
        <th>STATUS</th>
        <td>
            @if($parent->Status == "Publish")
                Publish
            @endif
            @if($parent->Status == "SaveAsDraft")
                Save As Draft
            @endif
       
        </td>
       
    </tr>
</table>

@endif