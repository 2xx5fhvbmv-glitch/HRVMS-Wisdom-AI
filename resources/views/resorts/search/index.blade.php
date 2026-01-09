<div class="serchresult">
	@if(!empty($search) && $getEmployee->count() > 0 || $getVacancy->count() > 0 || $getAnnouncements->count() > 0 || $getPositions->count() > 0 || $getDepartments->count() > 0 || $getLearningPrograms->count() > 0 || $getDocuments->count() > 0 || $getApplicants->count() > 0 || $getHolidays->count() > 0 || $getEmployeeLeave->count() > 0 || $getShopkeeper->count() > 0)
		<p>Show all results for <strong>{{ $search }}</strong></p>
		<ul>
               @if($getEmployee->count() > 0)
				@foreach($getEmployee as $employee)
					<li>
						<a target="_blank" href="{{ route('people.employees.details', base64_encode($employee->id)) }}">
							
							<strong>{{ $employee->resortAdmin->full_name }} </strong> {{ $employee->resortAdmin->email }} <span>Employee</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getVacancy->count() > 0)
				@foreach($getVacancy as $vacancy)
					<li>
						<a target="_blank" href="{{ route('resort.vacancies.FreshApplicant') }}">
							
							<strong>{{ $vacancy->Getposition->position_title }} </strong> {{ $vacancy->Getdepartment->name }} <span>Vacancy</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getAnnouncements->count() > 0)
				@foreach($getAnnouncements as $announcement)
					<li>
						<a target="_blank" href="{{ route('people.announcements.view', base64_encode($announcement->id)) }}">
							
							<strong>{{ $announcement->title }} </strong> {{ $announcement->published_date }} <span>Announcement</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getPositions->count() > 0)
				@foreach($getPositions as $position)
					<li>
						<a target="_blank" href="{{ route('resort.manning.index') }}">
							
							<strong>{{ $position->position_title }} </strong> {{ $position->code }} <span>Position</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getDepartments->count() > 0)
				@foreach($getDepartments as $department)
					<li>
						<a target="_blank" href="{{ route('resort.manning.index') }}">
							
							<strong>{{ $department->name }} </strong> {{ $department->code }} <span>Department</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getLearningPrograms->count() > 0)
				@foreach($getLearningPrograms as $getLearningProgram)
					<li>
						<a target="_blank" href="{{ route('learning.programs.index') }}">
							
							<strong>{{ $getLearningProgram->name }} </strong> <span>LearningProgram</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getDocuments->count() > 0)
				@foreach($getDocuments as $document)
					<li>
						<a target="_blank" href="{{ route('Categories.Documents') }}">
							
							<strong>{{ $document->File_Name }} </strong> {{ $document->File_Type }} <span>Documents</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getApplicants->count() > 0)
				@foreach($getApplicants as $applicant)
					<li>
						<a target="_blank" href="{{ route('resort.applicantForm', base64_encode($applicant->id)) }}">
							
							<strong>{{ $applicant->first_name }} {{$applicant->last_name}}</strong> {{ $applicant->email }} <span>Applicants</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getHolidays->count() > 0)
				@foreach($getHolidays as $holiday)
					<li>
						<a target="_blank" href="{{ route('resort.timeandattendance.publicholidaylist') }}">
							
							<strong>{{ $holiday->name }} </strong> {{ $holiday->holiday_date }} <span>Holidays</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getEmployeeLeave->count() > 0)
				@foreach($getEmployeeLeave as $employeeLeave)
					<li>
						<a target="_blank" href="{{ route('leave.details', base64_encode($employeeLeave->id)) }}">
							
							<strong>{{ $employeeLeave->employee->full_name }} </strong> {{ $employeeLeave->reason }} <span>Leave Employee</span>
						</a>
					</li>
				@endforeach
			@endif

               @if($getShopkeeper->count() > 0)
				@foreach($getShopkeeper as $shopkeeper)
					<li>
						<a target="_blank" href="{{ route('shopkeepers.list') }}">
							
							<strong>{{ $shopkeeper->name }} </strong> {{ $shopkeeper->email }} <span>ShopKeeper</span>
						</a>
					</li>
				@endforeach
			@endif
		</ul>
	@else
		<p>Show all results for <strong>?</strong></p>
		<ul>
			<li>Record not found!</li>
		</ul>
	@endif
</div>
