@php
    // Presentation-only helpers for this view. No query/data changes —
    // these only decide how the already-fetched results are displayed.

    // Case-insensitive keyword highlight. Escapes first, then wraps the
    // (also-escaped) search term in <mark> — safe against XSS since the
    // <mark> tag is the only raw HTML introduced, and it wraps already
    // -escaped text.
    $srHighlight = function ($text) use ($search) {
        $escaped = e((string) $text);
        if (empty($search)) {
            return $escaped;
        }
        $needle = preg_quote(e($search), '/');
        return preg_replace('/(' . $needle . ')/i', '<mark class="sr-highlight">$1</mark>', $escaped);
    };

    $peopleCount = $getEmployee->count() + $getApplicants->count() + $getEmployeeLeave->count();
    $workforceCount = $getVacancy->count() + $getPositions->count() + $getDepartments->count();
    $totalResults = $peopleCount + $getAnnouncements->count() + $getDocuments->count() + $workforceCount
        + $getLearningPrograms->count() + $getHolidays->count() + $getShopkeeper->count();

    $srGroupNames = [];
    if ($peopleCount > 0) { $srGroupNames[] = 'People'; }
    if ($getAnnouncements->count() > 0) { $srGroupNames[] = 'Announcements'; }
    if ($getDocuments->count() > 0) { $srGroupNames[] = 'Documents'; }
    if ($workforceCount > 0) { $srGroupNames[] = 'Workforce'; }
    if ($getLearningPrograms->count() > 0) { $srGroupNames[] = 'Learning'; }
    if ($getHolidays->count() > 0) { $srGroupNames[] = 'Holidays'; }
    if ($getShopkeeper->count() > 0) { $srGroupNames[] = 'Shop'; }

    $srGroupNamesText = '';
    if (count($srGroupNames) === 1) {
        $srGroupNamesText = $srGroupNames[0];
    } elseif (count($srGroupNames) > 1) {
        $last = array_pop($srGroupNames);
        $srGroupNamesText = implode(', ', $srGroupNames) . ' & ' . $last;
    }
@endphp
<div class="serchresult">
	@if(!empty($search) && $totalResults > 0)
		<div class="sr-heading-row">
			<span class="sr-heading">{{ $totalResults }} {{ Str::plural('result', $totalResults) }} for &ldquo;{{ $search }}&rdquo;</span>
		</div>

		<div class="sr-scroll">
			@if($peopleCount > 0)
				<div class="sr-group">
					<div class="sr-group-heading">People</div>
					<ul class="sr-group-list">
						@foreach($getEmployee as $employee)
							@php
								$empName = $employee->resortAdmin->full_name ?? '';
								$hasPhoto = !empty($employee->resortAdmin->profile_picture ?? null);
							@endphp
							<li>
								<a target="_blank" href="{{ route('people.employees.details', base64_encode($employee->id)) }}" class="sr-item">
									<x-wai-avatar :photo="$hasPhoto ? $employee->profile_picture_url : null" :name="$empName" :gender="$employee->resortAdmin->gender ?? null" :id="$employee->Emp_id" :size="32" />
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($empName) !!}</strong>
											<span class="sr-badge sr-badge-employee">Employee</span>
										</span>
										<span class="sr-meta">{{ collect([$employee->position->position_title ?? null, $employee->Emp_id ?? null, $employee->department->name ?? null])->filter()->implode(' · ') }}</span>
									</span>
								</a>
							</li>
						@endforeach

						@foreach($getApplicants as $applicant)
							@php $applicantName = trim($applicant->first_name . ' ' . $applicant->last_name); @endphp
							<li>
								<a target="_blank" href="{{ route('resort.applicantForm', base64_encode($applicant->id)) }}" class="sr-item">
									<x-wai-avatar :name="$applicantName" :id="$applicant->id" :size="32" />
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($applicantName) !!}</strong>
											<span class="sr-badge sr-badge-employee">Applicant</span>
										</span>
										<span class="sr-meta">{{ $applicant->email }}</span>
									</span>
								</a>
							</li>
						@endforeach

						@foreach($getEmployeeLeave as $employeeLeave)
							@php $leaveName = $employeeLeave->employee->full_name ?? ''; @endphp
							<li>
								<a target="_blank" href="{{ route('leave.details', base64_encode($employeeLeave->id)) }}" class="sr-item">
									<x-wai-avatar :name="$leaveName" :gender="$employeeLeave->employee->gender ?? null" :id="$employeeLeave->employee->Emp_id ?? null" :size="32" />
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($leaveName) !!}</strong>
											<span class="sr-badge sr-badge-employee">Leave</span>
										</span>
										<span class="sr-meta">{{ collect([$employeeLeave->LeaveCategory->leave_type ?? null, $employeeLeave->reason ?? null])->filter()->implode(' · ') }}</span>
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($getAnnouncements->count() > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Announcements</div>
					<ul class="sr-group-list">
						@foreach($getAnnouncements as $announcement)
							@php
								// $announcement->title is a foreign key into
								// announcement_category.id (see Announcement::category(),
								// belongsTo(AnnouncementCategory::class, 'title', 'id')) — the
								// raw column is a bare ID (e.g. "31"), not display text. The
								// real title is the category's name (e.g. "Employee of the
								// Month"), resolved via that relation. Falls back to a message
								// snippet, then a generic label, if the category is ever missing.
								$announcementTitle = !empty($announcement->category->name ?? null)
									? $announcement->category->name
									: (!empty($announcement->message) ? Str::limit(strip_tags($announcement->message), 70) : 'Announcement');
							@endphp
							<li>
								<a target="_blank" href="{{ route('people.announcements.view', base64_encode($announcement->id)) }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-announcement"><i class="fa-solid fa-bullhorn"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($announcementTitle) !!}</strong>
											<span class="sr-badge sr-badge-announcement">Announcement</span>
										</span>
										@if(!empty($announcement->published_date))
											<span class="sr-meta">Posted {{ \Carbon\Carbon::parse($announcement->published_date)->format('d M Y') }}</span>
										@endif
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($getDocuments->count() > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Documents</div>
					<ul class="sr-group-list">
						@foreach($getDocuments as $document)
							<li>
								<a target="_blank" href="{{ route('Categories.Documents') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-document"><i class="fa-solid fa-file-lines"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($document->File_Name) !!}</strong>
											<span class="sr-badge sr-badge-document">Document</span>
										</span>
										<span class="sr-meta">{{ collect([!empty($document->updated_at) ? 'Updated ' . \Carbon\Carbon::parse($document->updated_at)->format('d M Y') : null, $document->File_Type ?? null])->filter()->implode(' · ') }}</span>
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($workforceCount > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Workforce</div>
					<ul class="sr-group-list">
						@foreach($getVacancy as $vacancy)
							<li>
								<a target="_blank" href="{{ route('resort.vacancies.FreshApplicant') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-briefcase"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($vacancy->Getposition->position_title ?? '') !!}</strong>
											<span class="sr-badge sr-badge-muted">Vacancy</span>
										</span>
										<span class="sr-meta">{{ $vacancy->Getdepartment->name ?? '' }}</span>
									</span>
								</a>
							</li>
						@endforeach

						@foreach($getPositions as $position)
							<li>
								<a target="_blank" href="{{ route('resort.manning.index') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-sitemap"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($position->position_title) !!}</strong>
											<span class="sr-badge sr-badge-muted">Position</span>
										</span>
										<span class="sr-meta">{{ $position->code }}</span>
									</span>
								</a>
							</li>
						@endforeach

						@foreach($getDepartments as $department)
							<li>
								<a target="_blank" href="{{ route('resort.manning.index') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-building"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($department->name) !!}</strong>
											<span class="sr-badge sr-badge-muted">Department</span>
										</span>
										<span class="sr-meta">{{ $department->code }}</span>
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($getLearningPrograms->count() > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Learning</div>
					<ul class="sr-group-list">
						@foreach($getLearningPrograms as $getLearningProgram)
							<li>
								<a target="_blank" href="{{ route('learning.programs.index') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-graduation-cap"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($getLearningProgram->name) !!}</strong>
											<span class="sr-badge sr-badge-muted">Learning Program</span>
										</span>
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($getHolidays->count() > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Holidays</div>
					<ul class="sr-group-list">
						@foreach($getHolidays as $holiday)
							<li>
								<a target="_blank" href="{{ route('resort.timeandattendance.publicholidaylist') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-calendar-days"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($holiday->name) !!}</strong>
											<span class="sr-badge sr-badge-muted">Holiday</span>
										</span>
										@if(!empty($holiday->holiday_date))
											<span class="sr-meta">{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d M Y') }}</span>
										@endif
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			@if($getShopkeeper->count() > 0)
				<div class="sr-group">
					<div class="sr-group-heading">Shop</div>
					<ul class="sr-group-list">
						@foreach($getShopkeeper as $shopkeeper)
							<li>
								<a target="_blank" href="{{ route('shopkeepers.list') }}" class="sr-item">
									<span class="sr-icon-tile sr-icon-tile-muted"><i class="fa-solid fa-store"></i></span>
									<span class="sr-body">
										<span class="sr-row-top">
											<strong>{!! $srHighlight($shopkeeper->name) !!}</strong>
											<span class="sr-badge sr-badge-muted">Shopkeeper</span>
										</span>
										<span class="sr-meta">{{ $shopkeeper->email }}</span>
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			@endif
		</div>

		<div class="sr-footer">
			<span>{{ $totalResults }} {{ Str::plural('result', $totalResults) }} · {{ $srGroupNamesText }}</span>
			<span class="sr-footer-keys"><kbd>&crarr;</kbd> to open · <kbd>esc</kbd> to close</span>
		</div>
	@else
		<div class="sr-heading-row">
			<span class="sr-heading">Show all results for &ldquo;{{ $search ?? '' }}&rdquo;</span>
		</div>
		<div class="sr-empty">No results found.</div>
	@endif
</div>
