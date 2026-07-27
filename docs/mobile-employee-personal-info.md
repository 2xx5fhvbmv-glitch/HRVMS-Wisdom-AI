# Employee Personal Information — Mobile API

For the mobile team. Ticket: align mobile's Personal Information, Contact
Information, Education, Emergency Contact, and Additional Information
(incl. Language Skills / Proficiency Levels) with the web portal.

## `GET resort/profile` — same endpoint, one field was missing

Everything the ticket asked for was already returned via `resort/profile`
(`profile.get_employee`), **except one field**:

- **Personal/Contact Information** — already covered. Plain `employees`
  columns: `dob`, `marital_status`, `nationality`, `blood_group`,
  `personal_phone`/contact fields, `religion` (+ computed `religion_type`
  label, already existed).
- **Emergency Contact Details** — already covered, plain columns:
  `emg_cont_first_name`, `emg_cont_last_name`, `emg_cont_no`,
  `emg_cont_alt_no`, `emg_cont_relationship`, `emg_cont_email`,
  `emg_cont_nationality`, `emg_cont_dob`, `emg_cont_age`,
  `emg_cont_education`, `emg_cont_passport_no`,
  `emg_cont_passport_expiry_date`, `emg_cont_current_address`,
  `emg_cont_permanent_address`.
- **Additional Information → Leave Destination** — already covered:
  `leave_destination` column, plain field on the employee record.
- **Education** — already covered (fixed in an earlier pass this session):
  `education` returns a collapsed human-readable summary string (not the
  raw relation array), e.g. `"Bachelor's - University of Colombo (2015-2019); Master's - LSE (2020-2021)"`,
  or `null` if none recorded.
- **Language Skills / Proficiency Levels — this was the actual gap.**
  `employeeLanguage` was eager-loaded with an explicit column select that
  named `language` but not `proficiency_level`, so every language row's
  Native/Fluent/Intermediate rating was silently dropped even though it's
  captured on the web Additional Information tab. Fixed:

```json
"employee_language": [
  { "id": 94, "language": "Dhivehi", "proficiency_level": "Native" },
  { "id": 95, "language": "English", "proficiency_level": "Fluent" },
  { "id": 96, "language": "Arabic", "proficiency_level": "Intermediate" }
]
```

No new endpoint was built — the existing `resort/profile` response now has
everything requested. Verified against a real employee with 3 language
rows; `proficiency_level` now returns correctly for all 3.
