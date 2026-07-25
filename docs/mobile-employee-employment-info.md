# Employee Employment Information — Mobile API

For the mobile team. Ticket: align mobile's Employment Information,
Salary Details, Allowances, and Bank Details with the web portal.

## `GET resort/profile` — same endpoint, now includes everything

No new endpoint was needed — `resort/profile` (`ProfileController::getProfile`)
already returns the full employee profile; it was just missing 3 of the 4
requested sections. All fields below live under `profile.get_employee`.

**Employment Information** (existing raw fields, unchanged):
`joining_date`, `employment_type`, `status`, `contract_type`,
`termination_date`, `tin`, `probation_end_date`, `probation_status`,
`confirmation_date`. Plus relation objects: `department`, `section` (new —
was missing despite `department` already being loaded), `resort_divisions`,
`resort_positions`, `reportingTo`/`reportingToAdmin`.

New computed field: `benefit_grid_level_label` — human-readable grid name
(falls back to the employee's rank if no grid is explicitly set, matching
the web page's own fallback logic). `benefit_grid_level` (raw code) is
still present too.

**Salary Details** (existing): `basic_salary`, `basic_salary_currency`,
`payment_mode`, `pension`, `entitled_service_charge`, `entitled_overtime`,
`entitled_public_holiday`.

New computed fields:
```json
{
  "total_monthly_earning_mvr": 14649.0,
  "ewt_status_label": "Not Required",
  "indicative_ewt_deduction_mvr": null
}
```
- `total_monthly_earning_mvr` = basic salary + all allowances, converted to
  MVR at the resort's configured FX rate (`ResortSiteSettings.DollertoMVR`).
- `ewt_status_label` — `"Enrolled"` (has a TIN), `"Not Enrolled"` (earning
  ≥ MVR 30,000/month, no TIN), or `"Not Required"`.
- `indicative_ewt_deduction_mvr` — MIRA-bracket tax estimate, only populated
  when `ewt_status == "yes"` and earnings > 0; otherwise `null`.

**Allowances (new):**
```json
"allowances": [
  { "id": 8, "particulars": "Male - Based / Airport Based Allowance", "amount": "300.00", "amount_unit": "USD" }
]
```
Empty array `[]` if the employee has none — not omitted, not null.

**Bank Details (new):**
```json
"bank_details": [
  {
    "id": 7, "bank_name": "Bank of Maldives", "bank_branch": "Malé Main Branch",
    "account_type": "Saving", "IFSC_BIC": "MALBMVMV",
    "account_holder_name": "Aminath Abdul", "account_no": "772000000012",
    "currency": "USD", "IBAN": null
  }
]
```
Also empty array `[]` (not null) if no bank record exists.

Verified against real employees both with and without allowance/bank
records — confirmed the empty case returns `[]`, not an error.
