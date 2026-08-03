# Payroll — Employee Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/PayrollController.php`.
All 4 endpoints below were fixed today (see ticket "Payroll – Employee Payroll
Data Not Loading" — a bug silently dropped/zeroed real payroll data for any
employee missing a deductions/reviews/service-charge row for a given period,
which was most employees). Needs deploying before retesting.

## GET payroll/payroll-dashboard

Query param: `year` (optional, defaults to current year).

```json
{
  "success": true,
  "message": "Payroll Employee Dashboard",
  "payroll_data": {
    "payrollCost": [12000, 11800, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    "otCost": [320, 0, 0, ...],
    "serviceCharge": [450, 380, 0, ...],
    "salary": 900.00,
    "earnings": 950.00,
    "deductions": 42.00,
    "pension_total": 63.00,
    "pension": {
      "employee_pension": 31.50,
      "employer_pension": 31.50,
      "pension_percentage": "7%"
    },
    "city_ledger": 10.00,
    "payslip_details": {
      "payslip_total": 908.00,
      "payslip_start_date": "2026-01-26",
      "payslip_end_date": "2026-02-25"
    },
    "net_salary": "908.00"
  }
}
```
- `payrollCost`/`otCost`/`serviceCharge` are 12-element arrays, index 0 =
  January, for the chart — resort-wide payroll cost by month plus this
  employee's own OT/service-charge cost by month, for the requested `year`.
- The `salary`/`earnings`/`deductions`/`pension`/`city_ledger`/`net_salary`
  block is for the most recently completed payroll period (last calendar
  month relative to today, not the resort's actual cutoff period — flag to
  us if that mismatch matters for your UI).
- `pension_percentage` is always `"7%"` — that's the real, hardcoded
  Maldives statutory pension rate baked into payroll calculation itself
  (`earned_salary * 0.07`, employee+employer each), not a placeholder. It's
  not configurable per employee/resort. If your UI shows a different number
  (e.g. 12%), that's a client-side value out of sync with this — use what
  the API returns.

## POST payroll/payslip-list

Body: `{"year": 2026}` (optional, defaults to current year).

```json
{
  "success": true,
  "payslip_data": {
    "employee_data": {"id": 170, "Emp_id": "DR-1", "first_name": "...", "last_name": "...", "profile_picture": "...", "position": "...", "department": "..."},
    "payroll_net_sal_ot": {"id": 812, "total_ot": 3.5, "earnings_allowance": 50, "earnings_basic": 850, "total_deductions": 42, "net_salary": 858.00},
    "payslip_list_data": [
      {"id": 810, "resort_id": 26, "start_date": "2026-01-26", "end_date": "2026-02-25", "payment_date": "2026-02-27", "service_charge_amount": 30, "earnings_allowance": 50, "earnings_basic": 850, "total_deductions": 42, "net_salary": 908.00},
      {"id": 812, "start_date": "2026-02-25", ...}
    ]
  }
}
```
`payslip_list_data` is every payroll period for the requested year this
employee was part of — this is what backs "View All Payslips." Before
today's fix, periods missing a deductions/reviews/service-charge row were
silently absent from this array entirely (not shown as zero — just missing).
That's fixed; every period the employee was actually included in payroll for
now appears here.

## POST payroll/payslip-details

Body: `{"month": 3, "year": 2026}` (both required).

```json
{
  "success": true,
  "payslip_data": {
    "employee": {"Emp_id": "DR-1", "first_name": "...", "last_name": "...", "position": "...", "department": "...", "daywork": 28, "joining_date": "...", "start_date": "2026-02-25", "end_date": "2026-03-24", "profile_picture": "..."},
    "bank_details": {"total_amount": "938.00"},
    "earning_details": {"basic_pay": 850, "allowance": 50, "bonus": "", "earning_total_amount": "900.00"},
    "deductions_details": {"monthly_tax_deduction:": 0, "insurance:": "", "loans": "", "city_ledger": 10, "total_deductions": 42},
    "net_salary": "858.00"
  }
}
```
Returns `success:false` with a plain message (still HTTP 200) if no payroll
row exists for that specific month/year — not an error, just nothing to show.
Note `deductions_details` has literal trailing colons in two of its own key
names (`"monthly_tax_deduction:"`, `"insurance:"`) — that's really how the
API returns them, not a doc typo.

## POST payroll/payslip-pdf-download

Body: `{"month": 3, "year": 2026}`. Same underlying data as `payslip-details`,
rendered to PDF server-side and saved publicly; returns a direct URL:
```json
{"success": true, "pdf_url": "https://.../storage/payslips/1735600000_payslip.pdf"}
```

## POST payroll/payslip-share-email

Body: `{"month": 3, "year": 2026}`. Emails the payslip to the employee's own
registered email (no recipient param — always self). Returns
`{"success": true, "message": "Payslip shared successfully."}`.
