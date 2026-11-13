# Wisdom AI® — Master AI Intent Library
### Insights · Analytics · Forecasting · Recommendations — functional blueprint for the AI Engineer

Prepared from a read-only study of the `HRVMS-Wisdom-AI` codebase (branch `Amey/design`, 2026-11-13). **No code, schema or data was changed.** Every status below was checked against the real tool definitions, controllers and table columns named in the row. Where something is only inferred, the row says so.

---

## 0. How to read this document

| Part | What it holds |
|---|---|
| **1. Architecture findings** | How WAI actually works today, what is already intelligent, what is not |
| **2. Legend** | Status codes, gap tags, role codes (used in every row) |
| **3. Intent library by module** | 389 intents: Insights (I), Analytics (A), Forecasting (F), Recommendations (R) per module |
| **4. Cross-module intelligence** | Questions that must join several modules |
| **5. Conversational / follow-up intents** | Drill-downs, refinements, "why", "what should I do" |
| **6. Build backlog for the AI Engineer** | The gaps, de-duplicated and ordered |
| **7. Appendices** | Tool inventory, data-readiness table, role/permission matrix, data-quality warnings |

Row format: `ID | Question (natural language) | Roles | Business function | Data sources (tables / tools) | Status | Gap / extra logic`.

---

# PART 1 — ARCHITECTURE FINDINGS

## 1.1 The two AI surfaces (they are NOT connected today)

**A. WAI chatbot** (`WisdomChatController` → `OpenRouterClient` → `WisdomTools`)
- Model: OpenRouter, default `meta-llama/llama-3.3-70b-instruct`, `temperature 0.3`, `max_tokens` from config (800; the client's own fallback is 140), **max 5 tool-call rounds per message**, 60 s timeout.
- Pattern: system prompt (resort, user, role, date, tier block + hand-written Maldives law summaries) → LLM picks from **94 function-calling tools** → tool returns JSON → LLM writes the answer. Read-only; it refuses to act, only guides.
- Memory: the running conversation is kept **in the PHP session only** (last 20 messages, cleared on logout). It stores the user text and the assistant's final text, **not the tool results** — so a follow-up like "drill into the third department" works only if the earlier *answer text* contained it. Nothing persists across sessions.
- Limits: per-minute throttle, per-user daily cap (HR 100 / GM 100 / policy 50), per-resort monthly cost budget (`wisdom_usage` table logs tokens, cost, tool calls).
- Policy/law: `search_policy_documents` → Python service (resort's "Company Policies" folder + Maldives law PDFs); how-to: `get_module_guide` (reads the user's own menu).

**B. Dashboard "AI Insights" cards** (controllers → `Common::enrichDashboardInsights()` → Python `/dashboard_insights`)
Ten modules compute **deterministic numbers in PHP**, then the Python LLM rewrites the card text and adds a `recommendation`. Cached 2 days, manual "Regenerate" (48 h cooldown). This is where the real insight/forecast/recommendation logic lives — and **none of it is callable from the chatbot**.

| Dashboard | Card keys (computed in PHP) |
|---|---|
| Leave | `occupancy` (next-3-month occupancy outlook + unused annual-leave backlog), `peak` (peak leave window by month share), `behavior` (top leave type, avg duration, approval rate) |
| Payroll | `trend` (MoM %, driver dept), `overtime` (OT $ and % of payroll, top dept), `expat` (expat vs local cost), `allowance` (top allowances) |
| Visa | `payments` (unpaid quota/WP, overdue), `liability` (deposit liability by nationality), `renewal` (backlog, due ≤30d), `expiry` (expired / 30 / 60 / 90-day pipeline) |
| Performance | `completion` (appraisal % by dept), `risk` (active PIP/PDP people), `throughput` (self vs manager review bottleneck) |
| Talent Acquisition | `rejection`, `funnel`, `acceptance`, `tth` (time to hire), `demand` (open positions by dept) |
| Learning | `completion`, `mandatory`, `requests`, `probationary` |
| Grievance & Disciplinary | `volume`, `sla`, `hotspots`, `outcomes` |
| Survey | `participation`, `activity`, `sentiment`, `hotspots` |
| Incident | `volume` (+trend), `hotspots`, `outcomes` |
| Accommodation | `maintenance` (SLA/backlog), `occupancy`, `hotspots`, `demand` (beds vs hiring) |

Other existing intelligence (all outside chat):
| Engine | Where | What it does |
|---|---|---|
| **Staffing forecast** | `WorkforcePlanningDashboardController::getAiInsights` → Python `/predict_staff_batch` | **The only trained forecast.** Monthly occupancy % → `required_staff` + reason, 12-month window. Occupancy for future months falls back to same month last year, then two years ago. Resort-level number only (no department/position split). Cached 30 min. |
| **Manning budget AI** | `BudgetController::fetchAiBudgetRecommendations` → `/budget_recommendations` | Per position: suggested headcount + budget + justification, stored on `manning_responses.ai_suggestions` |
| **Compliance rules engine** | `ComplianceController::checkCompliance` | Hard-threshold rules writing `compliances` rows: Minimum Wage, Pension Deduction Below 7%, Expat-Local Ratio, Senior HR/Management Non-Maldivian, Weekly Working Hours Limit, Without Mandatory Break, Overtime Agreement Lacked, Over Time Not Eligible, TIN Requirement, Expired Visa Documents, Extended Probation, Job Description Not Received, Leave taken on holiday / notice period, Payroll EWT / Pension / Service Charge, Reserved Position |
| **AI anomaly scan** | `ComplianceController::runAnomalyScan` → `/compliance_scan` | Salary outliers per position, dept mix, probation cohort → `compliances` rows with `module_name='AI Anomaly Detection'` (max 20 per run). `/compliance_explain` writes `description_ai`, `remediation_ai`, `severity_ai`. |
| **T&A WAI insights** | `TimeandAttendanceDashboardController::getWaiInsights` | Per employee: weekly-hours breach, >3 h OT today, >5 h without break, >4 unused day-offs |
| **Applicant AI** | `ApplicantsController` → `/score_resume`, `/ta_insights` | CV score (`AIRanking`, `Scoring`, `ai_analysis`), TA metric rewrite |
| **Report AI** | `ReportController`, `PredefinedReportActions::computeAiInsightsText` | Narrative insight block on report PDFs/Excel |
| **Payroll estimate** | `Payroll\DashboardController::estimatePayrollLines` | Live "upcoming payroll" projection (day-accrued basic + allowances + approved OT − deductions); already exposed to chat via `get_payroll_summary` |
| **Leave alt-dates** | `leave_recommendations` | Manual HOD "recommend alternative dates" — not AI |

## 1.2 Tool inventory (what the chatbot can already fetch)

94 tool definitions. Grouped (full list in Appendix A):
- **Workforce & people (≈20):** headcount, dept breakdown, status counts, list/find employee, nationality, gender, birthdays, probation, promotions, transfers, resignations, increments, loans, pending approvals, workforce summary, compliance (localization, minimum wage), compliance issues
- **Leave & attendance (≈12):** on-leave, pending leave, **leave balance (one employee)**, public holidays, boarding/island pass, attendance summary/register/employee, late arrivals, overtime, roster, understaffed today
- **Recruitment (4):** pipeline, vacancy applicants, interviews
- **Workforce planning (5):** budgeted headcount, vacancy analysis, manning status, occupancy (latest only), + budget tools below
- **Payroll / money — HR only (8):** payroll summary, payroll by dept, payroll trend, employee salary, workforce budget (by dept/division/section/position/nationality/gender), employee cost, salary increments, loans
- **Performance (10), Visa (8), Employee relations (7), Learning (6), Incident (5), Survey (3), Documents (2), Accommodation (6), SOS (1)**
- **Platform (2, all tiers):** `search_policy_documents`, `get_module_guide`
- **SQL escape hatch — HR tier only:** `list_tables`, `describe_table`, `run_read_query`

**Key characteristics of the tool layer (these drive the Status column):**
1. Tools return **snapshots / lists**, almost never **time series, comparisons or ratios**. There is no tool that returns a month-by-month history, a rate, or a projection (the sole exception: `get_payroll_trend`, `get_payroll_summary.upcoming_payroll`, `get_visa_expiries(within_days)`).
2. There is **no recommendation tool and no forecast tool.** The LLM can only reason over whatever a tool returned, in ≤5 rounds, with a small reply budget.
3. Almost every tool is **resort-wide**. None accepts a department scope driven by the *user's* identity (see 1.4).
4. Nothing returns **turnover/attrition rates, tenure, contract expiry, retirement age, leave liability (resort-wide), OT cost, absenteeism rate or headcount over time.**

## 1.3 The SQL escape hatch — what it can and cannot reach (important)

`run_read_query` (HR tier only) → `ReadQueryGuard`: single SELECT, no `UNION`/`OR`/comma-join, no schema prefix, no auth tables or credential columns, **every table must carry its own `alias.resort_id = :resort_id`**, ≤200 rows, 8 s timeout, run on the `mysql_readonly` connection.

**Consequence:** of 403 tables, **146 have no `resort_id` column**. The guard forces a tenant predicate on *every* joined table, so any query touching these is rejected:
`payroll_reviews`, `payroll_review_allowances`, `payroll_deductions`, `payroll_recovery_schedule`, `payroll_service_charges`, `position_monthly_data`, `store_manning_response_children`, `applicant_wise_statuses`, `training_participants`, `survey_employees`, `survey_results`, `performa_child_cycles`, `resort_benefit_grid_child`, `employees_allowance`, …

So the SQL fallback **cannot answer** payslip-level cost questions (OT pay by dept, deductions, allowances per person), survey-answer analysis, training enrolment, appraisal-cycle detail, manning-plan monthly detail, benefit-grid leave allocations, or the applicant stage history. These are exactly the cross-module cost/people questions an executive asks → they need **dedicated tools** that join through the parent table (`payroll`, `parent_surveys`, `training_schedules`, `performance_cycles`, …) in PHP. This is the single biggest "data exists, AI can't reach it" finding. (`vacancies` uses capital `Resort_id`; the guard is case-insensitive so it works.)

## 1.4 Roles, tiers and what each role can actually ask today

`WisdomAccess::tier()` (rank map: 1 EXCOM · 2 HOD · 3 HR · 4 MGR · 5 SUP · 6 LINE · 7 Finance · 8 GM · 12 Clinic):

| Tier | Who | Tools today |
|---|---|---|
| **FULL** | Rank 3 HR; HR-dept HOD/EXCOM; super/master admin | All data tools incl. payroll + SQL |
| **GM** | Rank 8 | All data tools **except** payroll/compensation (8 tools blocked, min-wage view blocked, SQL blocked) |
| **POLICY** | Rank 1/2/4 in a non-HR department | Policy/law search + module how-to **only — no data at all** |
| **NONE** | Supervisors, line staff, **Finance (rank 7)**, clinic | Chatbot hidden; endpoints 403 |

**What this means for the library.** The assignment lists HR, Finance, GM, HODs, EXCOM, CEO/Resort Manager as askers. Today only HR (and GM minus money) can get data. Therefore:
- Every intent whose natural asker is **HOD / EXCOM / MGR / Finance** carries the tag `[ACCESS]` — the *data exists*, the *role gate* does not allow it. Building it needs a **scoped tier**, reusing `Common::hasFullDataAccess()` + `Common::getScopedDepartmentIds()` (CLAUDE.md invariant #5): the tool `$ctx` currently carries only `resort_id`, so department/direct-report scope must be added to `WisdomAccess::context()` and honoured inside each tool.
- Every **money** intent for the **GM/CEO** carries `[POLICY]`: payroll is blocked for GM by founder decision (`WisdomTools::PAYROLL_TOOLS`, comment "founder, 2026-11-02"). Opening aggregate-only cost views (no per-person pay) to GM is a **business decision to confirm**, not an engineering default.
- **Finance (rank 7)** has no tier at all. A Finance Director asking about payroll, budget, advances, liabilities needs a new `finance` tier (payroll yes, disciplinary/grievance/confidential no). Rank 9 (MD), 10, 11 (EDHOD) exist in the commented-out rank map but not the live one.
- **Sensitive-data rules the engine must keep:** confidential grievances withhold identity (`Request_Identity_Disclosure`); surveys have `survey_privacy_type` (anonymous surveys must never be broken down to names); minimum-wage/salary/loan = compensation (FULL only); deposit/visa wallet = HR/visa roles.
- **Tenant isolation:** every tool is resort-scoped through `$ctx['resort_id']`; new tools must keep it, including child tables with no `resort_id` (join via the parent, and verify the parent's `resort_id`).

## 1.5 Data model by module (anchors used in the rows below)

Counts are from the local snapshot (5 resorts, 574 employees) — **a demo-size dataset; production volumes differ**.

| Module | Core tables (key columns) |
|---|---|
| **People** | `employees` (Dept_id, Section_id, division_id, Position_id, rank, nationality, employment_type, contract_type, status [Active/Inactive/Onboarding/Offboarding/Terminated], joining_date, **contract_end_date**, probation_end_date/status, **dob**, basic_salary + currency, benefit_grid_level, reporting_to, **entitled_overtime / entitled_service_charge / entitled_public_holiday / entitled_annual_leave_ticket**, visa_expiry_date, work_permit_expiry_date, passport_number, tin, ewt_status, pension, termination_date, resign_effective_date, last_working_day), `employee_promotions`, `employee_transfers`, `people_salary_increment`, `employee_resignation` (+`employee_resignation_reasons`), `employee_employment_audit_logs`, `employee_info_update_request`, `employee_notice_period`, `exit_clearance_form`, `final_settlements` (leave_encashment, leave_balance, net_pay) |
| **Leave** | `employees_leaves` (status Pending/Approved, total_days, leave_category_id, from/to, extends_leave_id), `leave_categories` (carry_forward, carry_max, earned_leave, is_paid), `resort_benifit_grid` + `resort_benefit_grid_child` (allocated_days per leave type per rank), `leave_recommendations`, `public_holidays`; balance engine `Common::getEmployeeLeaveBalance` (grid allocation, anniversary-cycle accrual for Annual Leave, carry-forward) |
| **Time & Attendance** | `parent_attendaces` (Status Present/DayOff/Absent/FullDayLeave/…, CheckingTime/OutTime, OverTime "H:M", OTStatus), `duty_rosters` + `duty_roster_entries`, `shift_settings`, `employee_overtimes` (pending/approved/rejected, overtime_type), `child_attendaces`, `break_attendaces` |
| **Payroll** | `payroll` (category Permanent/Casual, status draft→approved→locked, total_payroll), `payroll_reviews` (earned_salary, earnings_overtime, regular/friday/holiday OT pay, service_charge, kpi_bonus, net_salary), `payroll_review_allowances`, `payroll_deductions` (attendance, city_ledger, staff_shop, advance_loan, pension, ewt), `payroll_advance` (HR→Finance→GM approvals) + `payroll_recovery_schedule`, `resort_service_charges`, `payroll_service_charges`, `ewt_tax_brackets`, `resort_site_settings` (DollertoMVR) |
| **Workforce Planning / Budget** | `manning_responses` (dept, year, status, total_headcount/filled/vacant, ai_suggestions), `store_manning_response_parents/children`, `position_monthly_data` (position × month planned/filled/vacant), `resort_positions` (no_of_positions), `resort_budget_costs`, `resort_employee_budget_cost_configurations`, `resort_vacant_budget_costs`, `resort_nonpermanent_budget_costs`, `store_consolidate_budget_*`, `budget_statuses`, `occuplanies` (daily occupancy %, total/occupied rooms, 73 rows locally, 2024-06→2026-08) |
| **Talent Acquisition** | `vacancies` (Total_position_required, required_starting_date, budgeted vs propsed_salary, out_of_budget_status, is_replacement, replacement_employee_id, is_required_local, status), `applicant_form_data` (Parent_v_id, AIRanking, Scoring, SalaryExpectation, NotiesPeriod, Applicant_Source), `applicant_wise_statuses` (stage history), `applicant_offer_contracts`, `applicant_inter_view_details`, `job_advertisements`, `application_links`, `hiring_sources` |
| **Visa** | `visa_renewals`, `work_permits` (monthly fee rows: Due_Date, Amt, Status), `quota_slot_renewals`, `employee_insurances`, `work_permit_medical_renewals`, `visa_employee_expiry_data` (AI-extracted from documents), `visa_wallets`, `visa_transection_histories`, `payment_requests`, `visa_xpact_amounts` (deposit rates by nationality), `deposit_refounds`, plus employee-level `visa_expiry_date` / `work_permit_expiry_date` |
| **Performance** | `performance_cycles`, `performa_child_cycles` (self/manager review status), `employee_pip_plans`, `employee_pdp_plans`, `performance_kpi_parents/children`, `monthly_checking_models`, `peformance_meetings`, `performance_bonus_configs` |
| **Employee Relations** | `disciplinary_submits` (category/offence/action/severity), `grivance_submission_models` (category, outcome_type, Gm_Decision, confidential flag), investigations/committee tables, `grievance_*` SLA/escalation tables |
| **Incident** | `incidents` (category, severity, location, outcome_type, preventive_measures, resolved_at), `incidents_investigation`, `incident_committee`, `incident_resolution_timeline` |
| **Learning** | `learning_programs`, `training_schedules`, `training_participants`, `training_attendance`, `mandatory_learning_programs`, `learning_requests`, `probationary_learning_programs`, `training_feedback_*` |
| **Survey** | `parent_surveys` (survey_privacy_type, Min_response), `survey_employees` (recipients), `survey_results`, `survey_questions` |
| **Accommodation** | `assing_accommodations` (emp, bed), `available_accommodation_models` (room/bed capacity), building tables, `maintanace_requests` (priority, Status, hold_until, escalation), `housekeeping_requests`, release/transfer decision tables |
| **Island Pass / Travel** | `employee_travel_passes` (arrival/departure, status, manifest), `employee_travel_quotas`, `travel_tickets`, `employee_annual_ticket_records` |
| **Compliance** | `compliances` (module_name, compliance_breached_name, severity_ai, remediation_ai, status Breached/Resolved; 1,440 rows locally, 99 % unresolved) |
| **Platform** | `employees_documents` (expiry_date), `filemangement_systems`, `announcements`, `support`, `sos_history`, `clinic_appointment`/treatment/medical-certificate, `audit_logs`, `wisdom_usage` |

## 1.6 Existing business rules the engine must respect (found in code)

- **Money:** budgets/costs stored in **USD**; salaries per-employee in USD or MVR (`basic_salary_currency`); FX from `DollertoMVR` (15.42); `Common::formatCurrency` vs `formatRequestCurrency` must not be mixed. WAI tools already return `usd` + `mvr`.
- **Leave:** balance = grid allocation (prorated; Annual Leave accrues allocated÷12 per month on the *joining-date anniversary* cycle) − approved taken + capped carry-forward. Approval chain by rank; HR/GM shortcut.
- **Overtime:** two stores — `employee_overtimes` (approval workflow) and `parent_attendaces.OverTime/OTStatus` (what payroll pays). OT rates 1.25× normal, 1.5× Friday/holiday (payroll). Senior-management OT exemption and `entitled_overtime` flag drive compliance rows.
- **Payroll:** day counts come from roster + attendance via `Common::payrollRosterContext/payrollDayMarks/payrollDayFraction`; half-day pays 50 %; payroll locks automatically on GM approval; Permanent vs Casual runs are separate (`payroll_category`).
- **Visa expiry shown in WAI = the same date the Xpat employee page shows** (`visaDocs()` in `WisdomTools`); doc types: visa, work_permit, insurance, medical, slot.
- **Localization:** compliance target & Maldivian-majority senior management rules sit in the compliance engine (Expat-Local Ratio; Senior HR Non-Maldivian).
- **Island Pass approval order:** HOD → HR → Security Manager. **Payroll advance:** HR → Finance → GM. **Payroll run:** Finance EXCOM → HR EXCOM → GM.
- **Employee exit:** accepted resignation → `status=Offboarding` → on last working day cron `employees:apply-last-working-day` sets `Inactive` (single end status). There is **no `Resigned` status** although two WAI tool descriptions mention it.

## 1.7 Data-readiness and quality warnings the engineer must know

1. **No history tables.** There is no headcount snapshot, no occupancy-vs-staffing history, no turnover ledger, no salary-history beyond `people_salary_increment` / `employee_employment_audit_logs` (35 rows locally). Headcount-over-time and turnover must be *reconstructed* from `joining_date`, `last_working_day`, `resign_effective_date`, `status`. Any forecast that says "based on trend" is bounded by what these dates allow.
2. **Resignation volume is tiny locally (6 rows, 4 withdrawn)** — attrition/propensity models cannot be validated against the local data. Production volume must be checked before choosing method (rate-based vs model).
3. **Dirty dates exist:** `parent_attendaces` min date `1970-01-01`, `employees.joining_date` min `0000-00-00`. Time-window tools must filter these out or every "since joining" / "trend" answer is wrong.
4. **Planned vs actual are separate:** `position_monthly_data` / `manning_responses` = *plan*; `employees` = *actual*. `duty_roster_entries` = plan; `parent_attendaces` = actual. Questions about "gaps" always join plan to actual.
5. **Occupancy is manually entered** (`occuplanies`, one row per date, HR keys it). Forecast quality = how far ahead HR enters occupancy; `occupancy_thresholds` / `…critical_thresholds` are empty (feature unused). The staffing model silently falls back to last year's same month.
6. **Compliance table is 99 % "Breached"** locally (1,432 of 1,440): rules re-fire without auto-resolve → any "top compliance risks" answer needs de-duplication by (employee, rule) and a freshness window, or it will drown in noise.
7. **Two employee tables of truth for visa dates:** `employees.visa_expiry_date/work_permit_expiry_date` vs `visa_employee_expiry_data` (AI-extracted from documents). WAI uses the latter route via `visaDocs()`; keep one.
8. **Answer budget:** with 5 tool rounds and a small reply budget, multi-part executive questions must be served by **one composite tool** that returns pre-joined facts — not by hoping the LLM chains 6 tools.

---

# PART 2 — LEGEND

**Status** (capability, independent of who asks)
- ✅ **S — Supported today:** a dedicated tool (or one `run_read_query`) returns the needed data; the LLM only has to phrase it.
- 🟡 **P — Partial:** some data comes back from existing tools, but the answer needs a calculation, a comparison, several tool calls, or a missing filter/dimension that the tools don't provide.
- 🔴 **D — Development:** needs a new tool, new calculation, new data capture, or a model.

**Gap tags** (combine freely)
`[TOOL]` data exists in DB/dashboard code but **no chatbot tool exposes it** · `[CALC]` needs arithmetic/aggregation not done anywhere · `[HIST]` needs history that must be reconstructed or isn't stored · `[NEW]` new data capture needed · `[ACCESS]` role/tier gate blocks the natural asker · `[POLICY]` restricted by founder decision — confirm before opening · `[SCOPE]` needs department/direct-report scoping · `[MODEL]` needs a forecasting/scoring method · `[SQL]` answerable today only through HR-tier SQL (and only if all tables have `resort_id`) · `[DASH]` the same logic already runs in a dashboard card — reuse it.

**Roles:** `HR` HR Director/Manager (FULL tier) · `GM` General Manager · `CEO` CEO/Resort Manager/MD (treated as GM-equivalent; no separate tier exists) · `FIN` Finance Director/Controller (no tier today) · `HOD` Head of Department · `EXC` Executive Committee · `MGR` Manager (non-HR) · `SEC` Security Manager · `L&D` Learning & Development Manager · `ACC` Accommodation/Housing Manager.

"Can ask today" is implied by tier: **HR** and **GM** (money excluded) only. All other roles' rows are `[ACCESS]` even when the status is ✅.

---
# PART 3 — INTENT LIBRARY BY MODULE

> Conventions: tool names are the real `WisdomTools` function names. "SQL ✅" = answerable today by `run_read_query` for **HR tier only** and only because every table touched carries `resort_id`. Roles in **bold** are the natural askers; all non-HR/GM roles are `[ACCESS]` today (Part 1.4). Money = `[POLICY]` for GM/CEO.
> Data notes marked ⚠ come from the local sample data and must be re-checked on production.

---

## MODULE 1 — PEOPLE & WORKFORCE (employee master, probation, promotion, transfer, resignation, increment)

### 1A. Insights
| ID | Question | Roles | Business function | Data sources | Status | Gap / extra logic |
|---|---|---|---|---|---|---|
| PEO-I01 | "Give me a snapshot of our workforce today." | **HR, GM, CEO** | Workforce overview | `get_workforce_status_summary` (headcount, on leave, probation, open vacancies, localization) | ✅ | — |
| PEO-I02 | "How many people work in each department?" | HR, GM, CEO, EXC | Headcount | `get_department_breakdown` | ✅ | — |
| PEO-I03 | "What's our Maldivian vs expat split, and are we meeting the local-employment target?" | **HR, GM, CEO** | Localization | `get_nationality_breakdown`, `get_workforce_compliance(localization)` | ✅ | — |
| PEO-I04 | "Which nationalities make up our expat staff?" | HR, GM, FIN | Workforce mix | `get_nationality_breakdown` | ✅ | — |
| PEO-I05 | "What's our gender ratio, and which departments have the fewest women?" | HR, GM, CEO | Diversity | `get_gender_breakdown(department)` | 🟡 | One call per department; add `group_by=department` to return all in one call `[TOOL]` |
| PEO-I06 | "Who joined us in the last 60 days and where are they in onboarding?" | **HR**, HOD | New joiners | `employees.joining_date`, `status='Onboarding'`, onboarding tables | 🔴 | No joining-date filter in `list_employees` `[TOOL]`; SQL ✅ for the list only ⚠ some sample resorts have 0000-00-00 dates |
| PEO-I07 | "Who is still on probation and when does each person's probation end?" | **HR**, HOD | Probation | `get_probation_overview` (counts only: on probation, ending this month, confirmed, failed, by status; end date defaults to joining + 3 months when blank) | 🟡 | No per-person list or date-range filter `[TOOL]` |
| PEO-I08 | "What promotions / transfers / resignations / increments are pending?" | **HR**, GM | Approvals | `get_promotions`, `get_transfers`, `get_resignations`, `get_salary_increments`, `get_pending_approvals` | ✅ | — |
| PEO-I09 | "Who is leaving in the next 60 days — notice period, last working day?" | **HR**, HOD, GM | Exits | `get_resignations` (rows have last_working_day, status) | 🟡 | No date-window filter; list capped at 100; LLM must filter `[TOOL]` |
| PEO-I10 | "Why are people resigning? Give me the top reasons." | **HR**, GM, CEO | Exit reasons | `employee_resignation.reason` → `employee_resignation_reasons` | 🟡 | `get_resignations` returns the reason per row but not a grouped count `[CALC]` |
| PEO-I11 | "Who are our longest-serving staff, and how long have our department heads been with us?" | HR, GM, CEO | Tenure | `employees.joining_date`, rank | 🔴 | No tenure tool `[TOOL]` `[CALC]` |
| PEO-I12 | "Tell me about Ahmed — department, position, manager, probation status." | HR, GM | Profile | `find_employee` | ✅ | Salary excluded by design |
| PEO-I13 | "Whose birthday is this month? Any work anniversaries?" | HR, GM, HOD | Engagement | `get_upcoming_birthdays`; anniversaries need `joining_date` | 🟡 | Birthdays ✅ (⚠ `dob` mostly empty in sample resorts); anniversaries `[TOOL]` |
| PEO-I14 | "Which employees have incomplete profiles — no passport, no emergency contact, no bank details?" | **HR** | Data quality | `employees` cols, `employee_bank_details`, `employee_info_update_request` | 🔴 | `[TOOL]` completeness scan; high operational value because 1.7 shows empty `dob`, `reporting_to`, `basic_salary` for whole resorts |
| PEO-I15 | "Which employees have no reporting manager assigned?" | **HR** | Org structure | `employees.reporting_to` | 🔴 | ⚠ `reporting_to=0` for all 252 employees of one sample resort `[TOOL]` |
| PEO-I16 | "How many casual workers and interns do we have?" | HR, GM | Workforce mix | `employees.employment_type` (Full-Time / Probationary / Casual / Internship) | 🟡 | `get_headcount` has no type filter `[TOOL]`; SQL ✅ |
| PEO-I17 | "Who has had a position, department or salary change recently?" | **HR** | Change audit | `employee_employment_audit_logs` (field, old/new) | 🔴 | `[TOOL]`; salary = HR only |
| PEO-I18 | "Which employees are over 58 or nearing pension age?" | HR | Succession/pension | `employees.dob` | 🔴 | `[NEW]` ⚠ dob not populated; `[CALC]` age |

### 1B. Analytics
| ID | Question | Roles | Function | Data sources | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PEO-A01 | "What was our headcount at the end of each of the last 12 months?" | **HR**, GM, CEO, FIN | Workforce trend | reconstruct from `joining_date`, `last_working_day`, `status` | 🔴 | `[HIST]` no snapshot table; month-end count = joined ≤ date and (not exited or exit > date). Must exclude 0000-00-00 dates |
| PEO-A02 | "What's our annual turnover rate, overall and by department?" | **HR**, GM, CEO | Attrition | `employees` (Inactive + last_working_day), `employee_resignation`, `resort_departments` | 🔴 | `[CALC]` exits ÷ average headcount; `[TOOL]`. Define voluntary (resigned) vs involuntary (terminated via disciplinary) using `employee_resignation` vs `status='Terminated'` |
| PEO-A03 | "Show monthly resignations for the last 6 months." | **HR**, GM | Attrition trend | `employee_resignation.resignation_date` | 🟡 | Computed in People dashboard `[DASH]`; chat tool only returns `this_month` `[TOOL]` |
| PEO-A04 | "Which departments lose the most people, and what reasons do they give?" | **HR**, GM, CEO | Attrition drivers | `employee_resignation` ⨝ `employees.Dept_id` ⨝ reasons | 🔴 | `[TOOL]` `[CALC]`; `get_resignations.by_department` is limited to the last 100 rows and ignores withdrawals |
| PEO-A05 | "Do people leave in their first year? Break resignations down by length of service." | HR, GM | Early attrition | `employee_resignation`, `joining_date` | 🔴 | Logic exists in `ResignationExitReportController` (lengthOfService, separation analysis) `[DASH]` → `[TOOL]` |
| PEO-A06 | "How many probationers were confirmed vs failed this year?" | HR | Probation outcomes | `employees.probation_status` (Active/Extended/Confirmed/Failed), `confirmation_date` | 🟡 | `get_probation_overview` = current counts; period filter `[TOOL]` |
| PEO-A07 | "What's the average salary increment % this year by department?" | **HR** | Compensation | `people_salary_increment`, `employee_promotions` | 🟡 | `get_salary_increments` default = pending; need approved-this-year + group by dept `[CALC]`; `[POLICY]` for GM |
| PEO-A08 | "Promotion rate by department — who is being promoted and who isn't?" | HR, GM | Career progression | `employee_promotions` ÷ headcount | 🔴 | `[CALC]` |
| PEO-A09 | "Which departments are gaining or losing staff through transfers?" | HR, GM | Mobility | `employee_transfers` (current/target dept, Permanent/Temporary) | 🟡 | `get_transfers` lists; net flow per dept `[CALC]` |
| PEO-A10 | "Compare localization across departments." | HR, GM | Localization | `employees.nationality` × dept | 🔴 | `[TOOL]` (current tools are resort-wide only) |
| PEO-A11 | "Span of control — how many direct reports does each manager have?" | HR, CEO | Org design | `employees.reporting_to` | 🔴 | `[NEW]` ⚠ `reporting_to` empty in sample data |
| PEO-A12 | "What share of staff sit in each rank / benefit-grid level?" | HR, GM, FIN | Grade mix | `employees.rank`, `benefit_grid_level` | 🟡 | SQL ✅ (HR); no tool `[TOOL]` |
| PEO-A13 | "Employment-type mix by department (full-time, casual, intern)." | HR | Mix | `employees.employment_type` | 🟡 | `[TOOL]` group_by |

### 1C. Forecasting
| ID | Question | Roles | Function | Data sources | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PEO-F01 | "How many people will we lose over the next 3 months?" | **HR**, GM, CEO | Attrition forecast | committed exits: `employee_resignation` (Approved/Pending, last_working_day); run-rate: history | 🟡/🔴 | Committed exits ✅ via `get_resignations`. A *predicted* number needs `[MODEL]` `[HIST]`: rate = trailing 12-mo exits ÷ avg headcount × headcount, seasonally adjusted. ⚠ Local data has 6 resignations (4 withdrawn) — choose method only after checking production volume |
| PEO-F02 | "Which employees are at highest risk of leaving?" | **HR** only | Retention risk | tenure, grievance/disciplinary counts, PIP/PDP, absence & leave pattern, salary vs position median (anomaly-scan already computes medians), time since last increment/promotion (`incremented_date`) | 🔴 | `[MODEL]` — explainable score with named factors, HR-only, no automatic action. **Do not** use survey answers (anonymous) or confidential grievance identities |
| PEO-F03 | "What will headcount be at year-end after approved exits and accepted offers?" | **HR**, GM, FIN | Headcount projection | `employees`, exits (LWD), `applicant_offer_contracts` (contract Accepted), `vacancies.required_starting_date` | 🟡 | `[CALC]` current − exits + accepted-not-yet-joined; no tool joins these |
| PEO-F04 | "Whose probation ends next month so I can plan confirmations?" | **HR**, HOD | Probation planning | `probation_end_date` | 🟡 | Window param `[TOOL]` ⚠ field sparse in samples |
| PEO-F05 | "Whose contract expires in the next 90 days?" | **HR** | Contract renewal | `employees.contract_end_date`, `contract_type` | 🔴 | `[NEW]` ⚠ `contract_end_date` is NULL for every employee in the sample DB (field exists, unused) |
| PEO-F06 | "How many staff will reach retirement age in the next 5 years?" | HR, CEO | Succession | `dob` (age 65 per Pension Act) | 🔴 | `[NEW]` + `[CALC]` |
| PEO-F07 | "What will approved increments and promotions add to monthly payroll?" | **HR**, FIN | Cost projection | `get_salary_increments`, `get_promotions` (both return monthly payroll impact of approved) | 🟡 | Two tools, sum + effective-date timing `[CALC]` |
| PEO-F08 | "Do we usually see a spike in resignations after peak season?" | HR, CEO | Seasonality | monthly resignation counts, ≥24 months | 🔴 | `[HIST]` `[MODEL]` |

### 1D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PEO-R01 | "Which departments have a turnover problem and what should we do about it?" | **HR**, GM, CEO | Retention | PEO-A04 + ER cases + survey scores + OT load + vacancies | 🔴 | `[CALC]` + rule library ("turnover > X% and OT > Y h/head → review staffing/schedule") |
| PEO-R02 | "Who should I have a retention conversation with first?" | **HR** | Retention | PEO-F02 | 🔴 | `[MODEL]` |
| PEO-R03 | "Who is due for a salary review or promotion?" | **HR**, HOD | Reward | `incremented_date`, `last_increment_salary_amount`, tenure, appraisal outcome (rating sits inside `performa_child_cycles.manager_review_data` JSON), position median | 🔴 | `[TOOL]` `[CALC]`; rules must be agreed with HR; `[POLICY]` money |
| PEO-R04 | "Which probationers should be confirmed and which extended?" | **HR**, HOD | Probation | attendance, disciplinary, PIP, learning progress, review remarks | 🔴 | Composite tool `[TOOL]`; decision support only |
| PEO-R05 | "Which profile gaps should I fix first?" | HR | Data quality | PEO-I14/I15 | 🔴 | `[TOOL]` ranked by downstream impact (payroll, visa, roster) |

---

## MODULE 2 — LEAVE MANAGEMENT

### 2A. Insights
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| LEA-I01 | "Who's on leave today / this week / next month?" | **HR, GM, HOD**, MGR | Cover planning | `get_employees_on_leave(date \| from,to)` | ✅ | HOD/MGR `[ACCESS]` `[SCOPE]` |
| LEA-I02 | "Which leave requests are waiting for approval, and with whom are they stuck?" | **HR**, HOD | Approvals | `get_pending_leave_approvals`; stage detail in `employees_leaves_status` (approver_rank, approver_id, approved_at) | 🟡 | Tool returns status only, not current approver/stage/age `[TOOL]`; the status table has no `resort_id` → SQL ✗ |
| LEA-I03 | "How much annual leave does Mariyam have left?" | HR, GM | Balance | `get_leave_balance` (grid allocation, taken, carry-forward) | ✅ | One person only |
| LEA-I04 | "Which employees have the most unused annual leave?" | **HR**, GM, CEO | Leave backlog | `Common::getEmployeeLeaveBalance` looped over staff (already done for the Leave dashboard card `[DASH]`) | 🔴 | Resort-wide balance tool `[TOOL]`; `resort_benefit_grid_child` has no `resort_id` so SQL ✗ |
| LEA-I05 | "Who hasn't taken any leave this year?" | **HR**, HOD | Wellbeing | `employees_leaves` ∖ active staff | 🔴 | `[TOOL]` `[CALC]` |
| LEA-I06 | "Which departments have the most people off next week?" | HR, GM, HOD | Cover | `get_employees_on_leave(from,to)` | 🟡 | Group by dept `[CALC]` (LLM from list) |
| LEA-I07 | "What kinds of leave do people take most?" | **HR**, GM | Behaviour | `employees_leaves` ⨝ `leave_categories` | 🟡 | `[DASH]` "behavior" card; chat has none; SQL ✅ (both tables have `resort_id`) |
| LEA-I08 | "Who takes frequent short sick leaves, especially around weekends?" | **HR** | Abuse/wellbeing | `employees_leaves` (sick type, weekday), `clinic_medical_certificate` | 🔴 | `[CALC]`; medical = sensitive; HR-only; pattern not accusation |
| LEA-I09 | "Who takes leave right before or after public holidays?" | HR | Pattern | `employees_leaves` + `public_holidays` | 🔴 | `[CALC]` |
| LEA-I10 | "Which leave requests were rejected and why?" | HR | Decisions | `employees_leaves.status`, `employees_leaves_status.comments` | 🟡 | `[TOOL]` ⚠ no Rejected rows in sample |
| LEA-I11 | "Who has asked to extend their leave?" | HR, HOD | Extensions | `employees_leaves.extends_leave_id` | 🔴 | `[TOOL]` (also noted in `docs/leave-extension-missing-from-hod-view.md`) |
| LEA-I12 | "Who has unpaid leave this month (payroll impact)?" | **HR, FIN** | Payroll link | `leave_categories.is_paid`, `employees_leaves.is_paid_override` | 🔴 | `[TOOL]` |
| LEA-I13 | "What public holidays are coming up?" | all with access | Planning | `get_public_holidays` | ✅ | — |
| LEA-I14 | "Did anyone take leave on a public holiday or during their notice period?" | HR | Compliance | `compliances` rules "Leave taken on holiday / notice period" | 🟡 | `get_compliance_issues` returns all rules; rule filter `[TOOL]` |

### 2B. Analytics
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| LEA-A01 | "Leave days taken per month this year versus last year." | **HR**, GM | Trend | `employees_leaves` | 🟡 | SQL ✅ (HR); `[TOOL]` for GM |
| LEA-A02 | "Approval rate and average approval time by department / approver." | **HR**, GM | Process | `employees_leaves.created_at` vs `employees_leaves_status.approved_at` per approver_rank | 🔴 | Data exists `[TOOL]` `[CALC]`; status table has no `resort_id` so SQL ✗ |
| LEA-A03 | "Leave utilisation % against entitlement, by department." | **HR**, GM | Utilisation | benefit grid allocations ÷ approved days | 🔴 | `[TOOL]` `[CALC]`; grid child table has no `resort_id` |
| LEA-A04 | "How much leave is being carried forward, by department?" | HR, FIN | Liability driver | `Common` balance `carry_forward`, `leave_categories.carry_max` | 🔴 | `[TOOL]` |
| LEA-A05 | "Which months are our peak leave months over the last two years?" | **HR**, GM | Seasonality | `employees_leaves` by month | 🟡 | `[DASH]` "peak" card; `[TOOL]` |
| LEA-A06 | "Is most of our leave approved in high-occupancy months?" | HR, GM, CEO | Leave vs demand | `employees_leaves` + `occuplanies` | 🔴 | `[CALC]` cross-module (see X-03) |
| LEA-A07 | "How much leave is planned versus same-day/unplanned?" | HR | Absence mix | `created_at` vs `from_date` | 🔴 | `[CALC]` |
| LEA-A08 | "What leave days are owed to people who exited (settlement)?" | **HR, FIN** | Settlement | `final_settlements.leave_balance`, `leave_encashment` | 🔴 | `[TOOL]` money |
| LEA-A09 | "Annual-leave ticket entitlement: who has used or is due one?" | HR | Benefit | `employee_annual_ticket_records`, benefit grid `annual_leave_ticket`, `entitled_annual_leave_ticket` | 🔴 | ⚠ 0 rows in sample; `[TOOL]` |

### 2C. Forecasting
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| LEA-F01 | "When will leave peak in the next 3 months and can we cover it?" | **HR**, GM, HOD | Cover planning | approved future leave + forecast occupancy (`occuplanies` next 3 months) | 🟡 | Leave side ✅ (`get_employees_on_leave from/to`); occupancy side: `get_occupancy` returns latest only `[TOOL]`; dashboard `occupancy` card already does it `[DASH]` |
| LEA-F02 | "How many leave days are already approved for next month, by department?" | HR, GM, HOD | Cover | `get_employees_on_leave` | 🟡 | Sum by dept `[CALC]` |
| LEA-F03 | "What is our accrued annual-leave liability today and at year-end?" | **HR, FIN** | Liability | balance days × daily rate; precedent: `final_settlements.leave_encashment` formula | 🔴 | `[TOOL]` `[CALC]` `[POLICY]` (GM) — per-person + total; daily rate rule must be taken from the final-settlement code, not invented |
| LEA-F04 | "Who will lose leave at year-end under the carry-forward cap?" | HR, HOD | Use-it-or-lose-it | balance vs `carry_max` | 🔴 | `[TOOL]` `[CALC]` |
| LEA-F05 | "Which weeks next quarter will have too many people on leave in one department?" | HR, HOD, GM | Cover | approved leave × `duty_rosters` × dept headcount | 🔴 | `[CALC]` threshold per dept must be defined (e.g. >15 % of dept) |

### 2D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| LEA-R01 | "When's the best window to encourage staff to take their leave?" | **HR**, GM | Leave planning | `[DASH]` occupancy outlook (rule: low-occupancy next 3 months → clear backlog) | 🟡 | Dashboard text only; expose as tool `[TOOL]` |
| LEA-R02 | "Should I approve Ali's leave for 10–20 December?" | **HOD**, HR, MGR | Approval decision support | balance, dept overlap on those dates, occupancy, roster, notice/holiday rules, pending OT | 🔴 | Composite `[TOOL]`; read-only recommendation, never approve |
| LEA-R03 | "Suggest alternative dates for this request." | HOD, HR | Approval | `leave_recommendations` (manual today) | 🔴 | `[CALC]` find nearest window with lower overlap/occupancy |
| LEA-R04 | "Who should I nudge to use their leave before it's lost?" | HR, HOD | Wellbeing/liability | LEA-F04 | 🔴 | `[TOOL]` |

---

## MODULE 3 — TIME & ATTENDANCE (attendance, roster, overtime)

### 3A. Insights
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TNA-I01 | "Who was absent today / didn't punch in?" | **HR, HOD**, GM | Daily control | `get_attendance_register` | ✅ | HOD `[ACCESS]` `[SCOPE]` |
| TNA-I02 | "Who arrived late today and by how many minutes?" | HR, HOD | Discipline | `get_late_arrivals` | ✅ | — |
| TNA-I03 | "What's tomorrow's duty roster? Who's off?" | HR, HOD, MGR | Roster | `get_duty_roster(date, off)` | ✅ | ⚠ roster data sparse in samples |
| TNA-I04 | "Which departments are short-handed right now?" | **HR**, GM, HOD | Cover | `get_understaffed_today` | 🟡 | Compares *active headcount* to *punched in* — scheduled day-offs and approved leave count as "absent", so coverage is understated. Needs roster-aware denominator `[CALC]` |
| TNA-I05 | "Who worked the most overtime this month?" | **HR**, GM, HOD | OT control | `get_overtime(period)` | ✅ | Hours only, no cost, no dept |
| TNA-I06 | "Which overtime requests are waiting for approval?" | **HR, HOD** | OT approval | `employee_overtimes.status='pending'` | 🔴 | `[TOOL]`; `get_overtime` reads attendance OT, not the approval table. Approving OT feeds payroll (`Common::syncAttendanceOvertime`) |
| TNA-I07 | "Who is working more than 48 hours a week, or skipping breaks?" | **HR**, HOD | Labour-law compliance | compliance rows "Weekly Working Hours Limit", "Without Mandatory Break"; dashboard `getWaiInsights` `[DASH]` | 🟡 | `get_compliance_issues` is noisy (see 1.7 #6); direct tool `[TOOL]` |
| TNA-I08 | "Who has too many unused day-offs?" | HR, HOD | Rest compliance | `getWaiInsights.day_off` (>4 unused in cycle; accrual from benefit grid `day_off_per_week`) | 🔴 | `[DASH]` logic → `[TOOL]` |
| TNA-I09 | "Which attendance records have no punch-out?" | HR | Data quality | `get_attendance_register.unverified` | ✅ | — |
| TNA-I10 | "Which geofence reviews are pending?" | HR | Control | `parent_attendaces.geofence_review_status` | 🔴 | `[TOOL]` |
| TNA-I11 | "Who was rostered today but didn't show?" | HR, HOD | No-show | `duty_rosters`/`duty_roster_entries` ⨝ `parent_attendaces` | 🟡 | Two tools; join `[CALC]` |

### 3B. Analytics
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TNA-A01 | "Attendance rate this month by department." | **HR**, GM, HOD | Attendance KPI | `parent_attendaces.Status` ⨝ employees | 🟡 | `get_attendance_summary` = one day only; SQL ✅ (HR); denominator must use roster (exclude `DayOff`, `FullDayLeave`) — payroll already does this via `Common::payrollDayMarks` |
| TNA-A02 | "Absenteeism trend for the last 6 months." | **HR**, GM, CEO | Absence | `parent_attendaces` | 🔴 | `[HIST]` ⚠ one sample resort holds only 1 month of punches; `[CALC]` |
| TNA-A03 | "Who are the top repeat latecomers?" | HR, HOD | Discipline | `CheckingTime` vs shift start | 🔴 | `[TOOL]` (late tool = single day) |
| TNA-A04 | "Overtime hours and cost this month vs last month." | **HR, FIN**, GM | OT cost | hours: `parent_attendaces.OverTime`; cost: `payroll_reviews.earnings_overtime/regularOTPay/fridayOTPay/holidayOTPay` | 🔴 | Cost exists only for **finalised payroll**; `payroll_reviews` has no `resort_id` → SQL ✗ `[TOOL]` `[POLICY]` |
| TNA-A05 | "Overtime by department, and who is above 40 h." | **HR**, HOD | OT control | attendance OT ⨝ employees.Dept_id | 🔴 | `[TOOL]` |
| TNA-A06 | "How long does OT approval take, and what's the rejection rate?" | HR, GM | Process | `employee_overtimes` (approved_at, status, overtime_type before/after/split) | 🟡 | SQL ✅ (has `resort_id`); `[TOOL]` for GM |
| TNA-A07 | "Planned vs actual hours worked by department." | HR, HOD | Utilisation | `duty_roster_entries.DayWiseTotalHours` vs `parent_attendaces` | 🔴 | `[CALC]` plan↔actual join |
| TNA-A08 | "Who works the most Fridays, holidays and weekends?" | HR | Fairness / OT rate | roster + `public_holidays` | 🔴 | `[CALC]` |
| TNA-A09 | "Staff-hours per occupied room." | **GM, CEO**, HR | Productivity | attendance hours ÷ `occuplanies.occupancyOccupiedRooms` | 🔴 | Cross TNA×WFP `[CALC]` (see X-12) |
| TNA-A10 | "Any unusual punch patterns — identical times, odd hours, far from the zone?" | HR | Anomaly | punch times, geofence, `CheckInCheckOut_Type`, `employee_overtimes.start_location` | 🔴 | `[MODEL]`; needs agreed definitions to avoid false accusations |

### 3C. Forecasting
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TNA-F01 | "How many staff will we need on the roster next month?" | **HR, GM**, HOD | Demand | occupancy → required staff via `/predict_staff_batch` `[DASH]` | 🔴 | `[TOOL]`: expose; resort-level only; daily/department split would need `[MODEL]` |
| TNA-F02 | "At this pace, how much overtime will we have by month-end?" | **HR, FIN** | OT run-rate | `get_overtime(month)` | 🟡 | Linear extrapolation `[CALC]` (days elapsed ÷ working days) |
| TNA-F03 | "Which days next month will we be short after leave and day-offs?" | HR, HOD, GM | Cover | future `duty_rosters` − approved leave − day-offs vs required | 🔴 | `[CALC]` needs required-per-shift definition `[NEW]` |
| TNA-F04 | "What public holidays fall next quarter and what's the holiday-pay exposure?" | HR, FIN | Cost | `get_public_holidays` ✅ + roster + OT rates (1.25×/1.5×) | 🟡 | Cost `[CALC]` `[POLICY]` |

### 3D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TNA-R01 | "How can we cut overtime in Housekeeping?" | **HR, HOD, GM** | OT reduction | OT hours, vacancies, leave, roster gaps, casual-pay config | 🔴 | Composite `[TOOL]` + rules (open vacancy + OT → hire; leave clustering → re-roster) |
| TNA-R02 | "Who should get a rest day to stay within the law?" | HR, HOD | Compliance | TNA-I07/I08 | 🔴 | `[CALC]` |
| TNA-R03 | "Who could cover tomorrow's short shift?" | HOD, HR | Cover | same section/position, OT load, day-off status, transfers | 🔴 | `[TOOL]` |
| TNA-R04 | "Is it cheaper to add casual staff than pay overtime in this department?" | **HR, FIN**, GM | Cost | OT cost/hour vs `casual_position_pay_configs` | 🔴 | `[CALC]` `[POLICY]` |
| TNA-R05 | "Who is at burnout risk — long hours, no day-off, no leave?" | **HR**, HOD | Wellbeing | OT + day-off balance + leave taken | 🔴 | `[CALC]` composite |

---
## MODULE 4 — PAYROLL & FINANCE

> All Payroll tools are `PAYROLL_TOOLS` → **HR (FULL) only**. GM is blocked by founder decision; Finance has no tier. Every row therefore carries `[ACCESS]`/`[POLICY]` for FIN/GM/CEO. `payroll_reviews`, `payroll_deductions`, `payroll_review_allowances`, `payroll_recovery_schedule`, `payroll_service_charges` have **no `resort_id`** → SQL ✗; a dedicated tool must join through `payroll` (which has `resort_id`) and check it.

### 4A. Insights
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PAY-I01 | "What was our last payroll and what's the next one going to be?" | **HR, FIN**, GM* | Payroll overview | `get_payroll_summary` (last locked run + live estimate for current cutoff, flagged `is_estimated`) | ✅ | *GM `[POLICY]` |
| PAY-I02 | "Is this month's payroll a real figure or an estimate? How is it built?" | HR, FIN | Transparency | same; estimate = day-accrued basic + allowances + approved OT − absence/third-party/pension/EWT (`estimatePayrollLines`) | ✅ | Breakdown modal exists on dashboard; chat gives total only `[TOOL]` for per-component |
| PAY-I03 | "Where is the payroll run stuck — which approval step?" | **HR, FIN**, GM | Payroll control | `payroll.status` (draft→approved→locked), `payroll_approvals` (Finance EXCOM → HR EXCOM → GM) | 🟡 | Tool returns run status, not step/approver/age `[TOOL]` |
| PAY-I04 | "Who has an outstanding loan or salary advance, and how much?" | **HR, FIN** | Receivables | `get_employee_loans` (advances + outstanding) | ✅ | — |
| PAY-I05 | "Which advances are waiting for Finance or GM approval?" | **FIN**, GM, HR | Approval | `payroll_advance` (hr_status, finance_status, gm_status) | 🟡 | `get_pending_approvals` returns counts only `[TOOL]` list |
| PAY-I06 | "Who is paid below the minimum wage?" | **HR**, FIN | Compliance | `get_workforce_compliance(minimum_wage)`; `compliances` rows | ✅ | HR only |
| PAY-I07 | "How much service charge was paid last month and what's the per-head amount?" | **HR, FIN**, GM | Service charge | `resort_service_charges`, `payroll_service_charges`, `payroll_reviews.service_charge` | 🔴 | `[TOOL]`; `entitled_service_charge` flag decides who counts |
| PAY-I08 | "Who has staff-shop / city-ledger deductions outstanding?" | HR, FIN | Deductions | `payroll_deductions.staff_shop/city_ledger`, `payments` | 🔴 | `[TOOL]` |
| PAY-I09 | "Which payroll compliance flags are open — EWT, pension, service charge?" | **HR, FIN** | Statutory | `compliances` modules "Payroll EWT/Pension/Service Charge Compliance", "Pension Deduction Below 7%" | 🟡 | `get_compliance_issues` filter by rule `[TOOL]` |
| PAY-I10 | "Who got an unusually high or low net pay compared with last month?" | **HR, FIN** | Payroll QA | `payroll_reviews` across runs per employee | 🔴 | `[TOOL]` `[CALC]`; a CLI already exists for this idea (`PayrollOpenRunDiff` command) — reuse its diff logic |
| PAY-I11 | "How is Casual payroll different from Permanent this month?" | HR, FIN | Mix | `payroll.payroll_category` (Permanent/Casual), `casual_position_pay_configs` | 🔴 | Dashboard is Permanent-only by design (WP9) `[TOOL]` |
| PAY-I12 | "What full-and-final settlements are pending and for how much?" | **HR, FIN** | Exit pay | `final_settlements` (net_pay, status, leave_encashment) | 🔴 | `[TOOL]` |

### 4B. Analytics
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PAY-A01 | "What does each department cost us in salary?" | **HR, FIN** | Cost by dept | `get_payroll_by_department` (sum of active staff basic salary) | ✅ | Is **basic salary**, not the paid run; label clearly |
| PAY-A02 | "Compare this month's payroll with last month." | **HR, FIN**, GM | MoM | `get_payroll_trend(periods)` | ✅ | Totals only; driver/dept variance `[DASH]` "trend" card → `[TOOL]` |
| PAY-A03 | "What share of payroll is overtime, and which department drives it?" | **HR, FIN**, GM, CEO | OT cost | `payroll_reviews.earnings_overtime` ⨝ employees.Dept_id | 🔴 | `[DASH]` "overtime" card computes it → `[TOOL]` |
| PAY-A04 | "Where does allowance spend go — top allowance types?" | HR, FIN | Allowances | `payroll_review_allowances.allowance_type/amount` | 🔴 | `[DASH]` "allowance" card → `[TOOL]` |
| PAY-A05 | "Expat versus local cost — how much more do we spend per head on expats?" | HR, FIN, GM, CEO | Cost mix | actual: payroll by nationality; plan: `get_workforce_budget(group_by=nationality)` | 🟡 | Budget view ✅; **actual** run view `[DASH]` "expat" card → `[TOOL]`; includes visa/accommodation cost? Must define |
| PAY-A06 | "Average salary by position — and who is far above or below the median?" | **HR** | Pay structure | `employees.basic_salary` by `Position_id` (AI anomaly scan already builds position medians) | 🟡 | SQL ✅ (HR; both tables have `resort_id`); outlier definition `[CALC]`; ⚠ `basic_salary` NULL for one sample resort |
| PAY-A07 | "Is there a pay gap between men and women in the same position?" | **HR** | Pay equity | salary × gender × position | 🔴 | `[CALC]`; sensitive; min-group-size rule needed |
| PAY-A08 | "How much pension and EWT did we withhold this month?" | **HR, FIN** | Statutory | `payroll_deductions.pension/ewt`; `ewt_tax_brackets` | 🔴 | `[TOOL]` |
| PAY-A09 | "What's our cost per employee and per occupied room?" | **FIN, GM, CEO** | Unit cost | payroll total ÷ headcount; ÷ `occuplanies` | 🔴 | `[CALC]` cross-module (X-10) |
| PAY-A10 | "Are we on budget — actual payroll year-to-date vs the workforce budget, by department?" | **FIN, GM, CEO**, HR | Budget control | `get_workforce_budget(department, year)` (annual USD) vs YTD `payroll_reviews` by dept | 🔴 | `[TOOL]` `[CALC]`; **currency**: budget USD vs payroll in `payroll_unit`; use `Common::formatCurrency` rules; budget÷12 per month is the engine's own convention |
| PAY-A11 | "How much of the loan book has been recovered vs scheduled?" | HR, FIN | Recoveries | `payroll_recovery_schedule` (status Pending/…, recovery_date, remaining_balance) | 🔴 | `[TOOL]` |
| PAY-A12 | "How much did absence and unpaid leave cost us in deductions?" | HR, FIN | Absence cost | `payroll_deductions.attendance_deduction` | 🔴 | `[TOOL]` |
| PAY-A13 | "Show total employee cost for Ahmed, and the effect of his last increment." | HR | Individual | `get_employee_cost`, `get_employee_salary`, `get_salary_increments` | ✅ | — |

### 4C. Forecasting
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PAY-F01 | "What will this month's payroll be?" | **HR, FIN**, GM | Cash planning | `get_payroll_summary.upcoming_payroll` | ✅ | Already a true projection; mid-cycle quality depends on attendance completeness |
| PAY-F02 | "What will our full-year payroll be at the current run-rate?" | **FIN, HR**, CEO | Annual outlook | `get_payroll_trend` + remaining months | 🟡 | `[CALC]` YTD + run-rate ± known changes (increments, promotions, hires, exits) |
| PAY-F03 | "What would payroll cost if we filled every approved vacancy?" | **FIN, HR**, GM | Budget | `get_workforce_budget` (includes vacant-slot costs) | 🟡 | Vacant-slot cost isn't returned separately `[TOOL]` |
| PAY-F04 | "What will increments and promotions add per month once effective?" | HR, FIN | Cost | `get_salary_increments`, `get_promotions` | 🟡 | Sum with effective dates `[CALC]` |
| PAY-F05 | "How much loan money comes back over the next 3 months?" | FIN, HR | Cash inflow | `payroll_recovery_schedule` (future repayment_date) | 🔴 | `[TOOL]` |
| PAY-F06 | "Who will cross the EWT threshold next month?" | HR, FIN | Statutory | projected RSWT vs `ewt_tax_brackets` + law (MVR 60,000 rules in system prompt) | 🔴 | `[CALC]`; EWT calc already exists in `Common`; applies four trigger rules (regular / expected annual / 2 consecutive months / cumulative >720,000) |
| PAY-F07 | "What will service charge look like next quarter?" | HR, FIN, GM | Service charge | `resort_service_charges` history + occupancy forecast | 🔴 | `[MODEL]` simple regression on occupancy; needs ≥12 months |
| PAY-F08 | "If the dollar rate moves to 16, what happens to our MVR payroll?" | FIN | FX | `DollertoMVR`, `basic_salary_currency` | 🔴 | `[CALC]`; FX rule: derive only from `DollertoMVR` (CLAUDE.md invariant #4) |
| PAY-F09 | "What overtime cost should I expect this month?" | HR, FIN | OT | TNA-F02 × OT rates | 🟡 | `[CALC]` `[POLICY]` |

### 4D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| PAY-R01 | "Where can we cut payroll cost without cutting people?" | **FIN, GM, CEO**, HR | Cost optimisation | OT, vacancies, allowances, expat cost, loan recoveries | 🔴 | Composite + rule library; must state it is an estimate |
| PAY-R02 | "Which salaries should I review as outliers?" | **HR** | Pay review | AI anomaly scan rows (`module_name='AI Anomaly Detection'`) + PAY-A06 | 🟡 | `get_compliance_issues` returns them but mixed with rule breaches `[TOOL]` filter |
| PAY-R03 | "How do I fix the below-minimum-wage cases?" | **HR** | Compliance | `get_compliance_issues` returns suggested fix (`remediation_ai`) | ✅ | — |
| PAY-R04 | "Should we turn overtime-heavy roles into new hires?" | **FIN, HR, GM** | Cost | OT cost per FTE vs salary+costs of a vacant slot | 🔴 | `[CALC]` |
| PAY-R05 | "Which advances should we approve — who can afford the recovery?" | FIN, GM, HR | Credit risk | request vs salary, existing balance, tenure, exit/contract flags | 🔴 | `[CALC]` decision support only; guarantor table exists (`payroll_advance_guarantor`) |

---

## MODULE 5 — WORKFORCE PLANNING, BUDGET & OCCUPANCY

### 5A. Insights
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| WFP-I01 | "How many positions are approved, filled and vacant this year?" | **HR, GM, CEO**, EXC | Manning | `get_budgeted_headcount` (total/division/department/position) | ✅ | — |
| WFP-I02 | "Which departments haven't submitted their manning plan?" | **HR**, GM | Planning control | `get_manning_status` | ✅ | — |
| WFP-I03 | "What's our current occupancy?" | all with access | Demand | `get_occupancy` (latest or as of a date) | ✅ | ⚠ manually keyed |
| WFP-I04 | "Which vacancies are most critical?" | HR, GM, EXC | Hiring priority | `get_vacancy_analysis(critical_only)` | ✅ | Ranking = count of vacancies, not operational criticality |
| WFP-I05 | "What's our total workforce budget and how is it split?" | **HR, FIN**, CEO* | Budget | `get_workforce_budget` (department/division/section/position/nationality/gender) | ✅ | *GM/CEO `[POLICY]`; annual USD (÷12 for a month) |
| WFP-I06 | "Which manning plans are waiting for budget approval?" | HR, GM, FIN | Budget workflow | `manning_responses.budget_process_status`, `budget_statuses` | 🟡 | `get_manning_status` shows request status; budget-stage detail `[TOOL]` |
| WFP-I07 | "Which vacancies are out of budget?" | **HR, FIN**, GM | Budget control | `vacancies.out_of_budget_status/comment` | 🔴 | `[TOOL]` |
| WFP-I08 | "What did the AI suggest versus what the department asked for, by position?" | **HR**, GM | Planning review | `manning_responses.ai_suggestions` (suggested_headcount, suggested_budget, justification) | 🔴 | `[DASH]` data exists, not in a tool `[TOOL]` |
| WFP-I09 | "Which vacancies are replacements for people who left?" | HR | Backfill | `vacancies.is_replacement`, `replacement_employee_id` | 🔴 | `[TOOL]` |
| WFP-I10 | "Which approved positions are reserved for Maldivians?" | HR, GM | Localization | `resort_positions.is_reserved`, `vacancies.is_required_local` | 🔴 | `[TOOL]` |

### 5B. Analytics
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| WFP-A01 | "Vacancy rate by department compared with last year." | **HR, GM, CEO** | Fill rate | `get_vacancy_analysis(year)` ×2 | 🟡 | Call twice + compare `[CALC]` |
| WFP-A02 | "Plan vs actual headcount, month by month, by position." | **HR**, GM | Plan vs actual | `position_monthly_data` (planned/filled/vacant) vs `employees` | 🔴 | `[TOOL]`; child table has no `resort_id` → SQL ✗ |
| WFP-A03 | "Occupancy by month for the last 12 months — peak and trough." | **GM, CEO**, HR | Demand history | `occuplanies` (daily %, rooms) | 🔴 | `get_occupancy` = latest only `[TOOL]`; SQL ✅ (HR). ⚠ 73 rows locally across resorts |
| WFP-A04 | "What's the staff-to-occupied-room ratio by month?" | **GM, CEO**, HR | Productivity | headcount (history `[HIST]`) ÷ occupied rooms | 🔴 | `[CALC]` (X-12) |
| WFP-A05 | "Budget vs actual cost by department." | **FIN, GM, CEO** | Variance | see PAY-A10 | 🔴 | `[TOOL]` `[CALC]` |
| WFP-A06 | "How much is the cost of our vacant positions?" | **FIN, HR**, GM | Budget | `resort_vacant_budget_costs`, budget engine | 🟡 | `[TOOL]` split |
| WFP-A07 | "Compare fill rates by division or section." | HR, GM | Comparison | `get_budgeted_headcount(division)` | 🟡 | `get_vacancy_analysis` lacks division `[TOOL]` |
| WFP-A08 | "How long do manning plans take to move from draft to approved?" | HR | Process | `manning_responses`, `budget_statuses` timestamps | 🔴 | `[HIST]` verify timestamps |
| WFP-A09 | "Which departments are over their approved headcount?" | HR, GM, FIN | Control | `employees` per dept vs `resort_positions.no_of_positions` | 🟡 | Two tools, join by dept `[CALC]` |

### 5C. Forecasting
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| WFP-F01 | "How many staff will we need in each of the next 12 months, based on occupancy?" | **HR, GM, CEO** | Demand forecast | `/predict_staff_batch` (monthly occupancy % → `required_staff` + reason) via `WorkforcePlanningDashboardController::getAiInsights` | 🔴 | **`[TOOL]` highest-value quick win** — the model exists and is cached 30 min. Constraints: resort-level total; occupancy for future months is HR-entered, falling back to same month last year/2 years ago (tell the user which basis was used) |
| WFP-F02 | "What will our manning gap be at peak season?" | **HR, GM, CEO** | Gap | WFP-F01 − (headcount − committed exits + accepted offers) | 🔴 | `[CALC]` + PEO-F03 |
| WFP-F03 | "What budget do we need for next year's approved plan?" | **HR, FIN**, GM | Budget | `get_workforce_budget(year=next)` | 🟡 | ✅ if next-year manning exists; else `[CALC]` |
| WFP-F04 | "What happens to staffing need if occupancy is 10 % higher than planned?" | **GM, CEO**, HR | Scenario | WFP-F01 with changed input | 🔴 | `[MODEL]` scenario parameter on the occupancy input |
| WFP-F05 | "When must I start recruiting to have people ready for the season?" | HR, GM | Hiring timing | required date − time-to-hire (TA dashboard `tth`) − notice period (`NotiesPeriod`) | 🔴 | `[CALC]` |
| WFP-F06 | "How many positions will be vacant at year-end given likely exits?" | HR, FIN, GM | Vacancy outlook | vacancies + PEO-F01 | 🔴 | `[CALC]` `[MODEL]` |

### 5D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| WFP-R01 | "Which departments should get extra headcount next year?" | **HR, GM, CEO**, EXC | Planning | AI suggestion vs request, OT, vacancy, occupancy, leave load | 🔴 | Composite + explainable rule |
| WFP-R02 | "Where are we over-staffed compared with occupancy?" | GM, CEO, FIN | Efficiency | WFP-A04 by department | 🔴 | `[CALC]` needs a staffing norm per department `[NEW]` |
| WFP-R03 | "Is this department's manning request reasonable?" | HR, GM, FIN | Review | request vs AI suggestion vs history | 🔴 | `[TOOL]` (data in `manning_responses`) |
| WFP-R04 | "Which vacancies should I fill first?" | HR, GM | Prioritisation | WFP-I04 + OT + occupancy + compliance (reserved/min wage) | 🔴 | Composite |
| WFP-R05 | "Which positions can we freeze or merge?" | CEO, GM, FIN | Cost | long-vacant + low utilisation | 🔴 | `[CALC]` + business policy |

---

## MODULE 6 — TALENT ACQUISITION (recruitment)

### 6A. Insights
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TAC-I01 | "How is the recruitment pipeline looking?" | **HR**, GM | Pipeline | `get_recruitment_pipeline` | ✅ | — |
| TAC-I02 | "Which vacancies are open and which has the most applicants?" | HR, GM, HOD | Sourcing | `get_vacancy_applicants(open_only)` | ✅ | — |
| TAC-I03 | "What interviews are scheduled tomorrow?" | HR, HOD | Scheduling | `get_scheduled_interviews` | ✅ | — |
| TAC-I04 | "Who are the top-ranked applicants for the Chef de Partie vacancy?" | **HR**, HOD | Shortlisting | `applicant_form_data.AIRanking/Scoring/ai_analysis` | 🔴 | `[TOOL]` — scoring already produced by `/score_resume` |
| TAC-I05 | "Why are candidates being rejected?" | HR | Funnel | `applicant_wise_statuses.Comments`, `applicant_offer_contracts.rejection_reason` | 🔴 | `[DASH]` "rejection" card → `[TOOL]`; `applicant_wise_statuses` has no `resort_id` |
| TAC-I06 | "What's our offer and contract acceptance rate?" | HR, GM | Offers | `applicant_offer_contracts` (type, status) | 🔴 | `[DASH]` "acceptance" → `[TOOL]` |
| TAC-I07 | "Which vacancies are past their required start date and still open?" | **HR**, GM, HOD | Hiring SLA | `vacancies.required_starting_date`, `status` | 🔴 | `[TOOL]`; SQL ✅ for the vacancy list |
| TAC-I08 | "Which candidates still owe us a consent or availability reply?" | HR | Candidate follow-up | `applicant_form_data.consent_status/availability_status` | 🔴 | `[TOOL]` |

### 6B. Analytics
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TAC-A01 | "What's our hiring funnel — applied, shortlisted, interviewed, offered, hired?" | **HR**, GM, CEO | Conversion | `applicant_wise_statuses` stage history | 🔴 | `[DASH]` "funnel" → `[TOOL]` (stage list is in the TA controller) |
| TAC-A02 | "What's our average time to hire, by department?" | **HR**, GM, CEO | Speed | `applicant_form_data.Application_date` → `applicant_offer_contracts.responded_at` (contract Accepted) | 🔴 | `[DASH]` "tth" → `[TOOL]` |
| TAC-A03 | "Which source gives us the best hires?" | HR | Sourcing ROI | `Applicant_Source`, `hiring_sources`, hired status | 🔴 | `[CALC]` ⚠ 1 source row locally |
| TAC-A04 | "Do applicants' salary expectations exceed our budgeted salary?" | **HR, FIN** | Offer realism | `SalaryExpectation` vs `vacancies.budgeted_salary/propsed_salary` | 🔴 | `[CALC]` currency units differ (`amount_unit`) |
| TAC-A05 | "How many applicants can join within our required start date given their notice period?" | HR, HOD | Timing | `NotiesPeriod`, `Joining_availability` vs `required_starting_date` | 🔴 | `[CALC]` |
| TAC-A06 | "How many local vs expat hires this year?" | HR, GM | Localization | hires × `is_required_local`/nationality | 🔴 | `[TOOL]` |
| TAC-A07 | "Application volume by job advert / link." | HR | Marketing | `job_advertisements`, `application_links` | 🔴 | `[TOOL]` |
| TAC-A08 | "What does a hire cost us?" | **FIN, HR** | Cost per hire | `vacancies` cost fields (`recruitment`, `salary`, `accomodation`, `insurance`…) | 🔴 | `[NEW]` — confirm what `vacancies.recruitment` holds before using; no external agency/advert spend table |

### 6C. Forecasting
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TAC-F01 | "How many hires do we need over the next quarter?" | **HR**, GM, CEO | Demand | open vacancies + replacements (LWD in window) + planned growth (WFP-F02) | 🔴 | `[CALC]` cross (X-02) |
| TAC-F02 | "When will vacancy X realistically be filled?" | HR, HOD | Fill date | funnel stage counts + average stage durations + notice period | 🔴 | `[MODEL]` simple duration model from `tth` |
| TAC-F03 | "How many offers will we need to send to hire 10?" | HR | Funnel | acceptance rates | 🔴 | `[CALC]` |
| TAC-F04 | "How many new joiners will arrive next month — do we have beds and visas ready?" | HR, ACC, GM | Onboarding load | accepted offers/joining dates + beds + visa readiness | 🔴 | Cross TA×ACC×VIS (X-14) |

### 6D. Recommendations
| ID | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|
| TAC-R01 | "Which applicants should I shortlist?" | **HR**, HOD | Screening | AI score + experience + notice + salary fit | 🔴 | `[TOOL]`; decision support only; avoid protected attributes (gender, nationality, marital status are in the form data) |
| TAC-R02 | "Which vacancies need more sourcing effort?" | HR | Sourcing | `get_vacancy_applicants` + required start date | 🟡 | Threshold rule `[CALC]` |
| TAC-R03 | "Where are we losing candidates and what should we change?" | HR, GM | Funnel | TAC-A01 + TAC-I05 | 🔴 | `[CALC]` + text themes |
| TAC-R04 | "Which budgeted salaries look too low for the market we're seeing?" | HR, FIN | Offer policy | rejections citing pay + expectation gap | 🔴 | `[CALC]` + NLP on `Comments` |
| TAC-R05 | "Which vacancies break minimum-wage or reserved-position rules?" | HR | Compliance | `compliances` ("Minimum Wage", "Reserved Position") | 🟡 | `[TOOL]` filter |

---
## MODULE 7 — VISA & IMMIGRATION

> Expiry dates shown by WAI = the same date the Xpat employee page shows (`visaDocs()`): documents of type visa, work permit, insurance, medical, slot fee. Fee rows: `work_permits` (monthly, `Due_Date`, `Amt`, `Status` Paid/Unpaid — 94 of 124 unpaid in the sample), `quota_slot_renewals`. Wallet: `visa_wallets` + `visa_transection_histories`. Deposit rates: `visa_xpact_amounts` (by nationality).

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| VIS-I01 | I | "Give me our immigration status — are we compliant?" | **HR**, GM, CEO | Overview | `get_visa_summary` | ✅ | — |
| VIS-I02 | I | "Which visas, work permits, insurance or medicals expire in the next 30 days?" | **HR**, GM | Expiry watch | `get_visa_expiries(status=upcoming, within_days, doc_type)` (roll-up by employee) | ✅ | — |
| VIS-I03 | I | "Which documents have already expired?" | HR, GM, CEO | Compliance | `get_visa_expiries(status=expired)` | ✅ | — |
| VIS-I04 | I | "When does Rani's visa expire? Is she compliant?" | HR, GM | Individual | `get_employee_immigration` | ✅ | — |
| VIS-I05 | I | "What's our immigration wallet balance and how much is reserved?" | **HR, FIN**, GM | Cash | `get_visa_wallet` | ✅ | — |
| VIS-I06 | I | "Which payment requests are pending? Which deposit refunds are pending?" | HR, FIN | Payments | `get_visa_payment_requests`, `get_deposit_refunds` | ✅ | — |
| VIS-I07 | I | "Which expat employees have no work-permit or visa record at all?" | **HR** | Data gap | non-Maldivian `employees` ∖ `work_permits`/`visa_renewals`/`visa_employee_expiry_data` | 🔴 | `[TOOL]` — also catches AI-extraction failures |
| VIS-I08 | I | "Which work-permit fees are overdue and for whom?" | HR, FIN | Fees | `work_permits` (Unpaid, Due_Date < today) | 🟡 | `get_visa_summary` gives counts; named overdue list `[TOOL]` |
| VIS-A01 | A | "What's our immigration liability by document type — and by nationality?" | **HR, FIN**, CEO | Liability | `get_visa_liability` (by type); nationality: `visa_xpact_amounts` | 🟡 | Nationality view = dashboard "liability" card `[DASH]` → `[TOOL]` |
| VIS-A02 | A | "Average visa cost per expat, by nationality, since joining." | **FIN, HR** | Cost | `total_expensess_since_joings`, fees tables | 🔴 | `[TOOL]` `[CALC]` |
| VIS-A03 | A | "Which departments depend most on expats on expiring documents?" | HR, GM, HOD | Exposure | expiries ⨝ `Dept_id` | 🔴 | `[TOOL]` (expiry tool has no dept) — see X-01 |
| VIS-A04 | A | "How fast do we renew — average days before expiry that we complete renewal?" | HR | Process | `visa_renewals` (start/end, paid_date) | 🔴 | `[CALC]` |
| VIS-A05 | A | "How quickly is the wallet being used — monthly spend?" | FIN, HR | Burn rate | `visa_transection_histories` | 🔴 | `[CALC]` |
| VIS-A06 | A | "Is our expat-to-local ratio within the legal limit?" | HR, GM, CEO | Compliance | `get_workforce_compliance`, `compliances` "Expat-Local Ratio" | ✅ | — |
| VIS-F01 | F | "What renewals and fees fall due over the next 3 / 6 / 12 months, in dollars?" | **FIN, HR**, GM | Cash plan | `work_permits.Due_Date/Amt`, `quota_slot_renewals`, expiries × fee amounts | 🔴 | `get_visa_liability` supports today/week/month only → forward-window param `[TOOL]` |
| VIS-F02 | F | "Will the wallet cover the next three months of fees?" | **FIN, HR** | Cash | VIS-F01 vs `visa_wallets` available | 🔴 | `[CALC]` |
| VIS-F03 | F | "How many renewals will the visa officer handle each week next month?" | HR | Workload | `get_visa_expiries(within_days=30)` grouped by week | 🟡 | Weekly bucketing `[CALC]` |
| VIS-F04 | F | "How much deposit will we get back from people leaving in the next 60 days?" | FIN, HR | Refunds | `employee_resignation` (Deposit_Amt, LWD) ∪ `deposit_refounds` | 🟡 | `get_deposit_refunds` = already-approved pending only; add pipeline `[TOOL]` |
| VIS-F05 | F | "How will upcoming visa expiries affect workforce availability over the next three months?" | **HR**, GM, CEO | Availability | expiries × roster × leave × dept headcount | 🔴 | Cross-module — see **X-01** |
| VIS-R01 | R | "Which renewals should I start first?" | **HR** | Prioritisation | days-left, position criticality, fee status, replacement ease | 🔴 | `[CALC]` rule ranking |
| VIS-R02 | R | "Which expats are on expired papers but still rostered?" | HR, GM | Legal risk | expiries × `duty_rosters`/attendance | 🔴 | Cross (X-05) |
| VIS-R03 | R | "Should I top up the wallet now?" | FIN, HR | Cash | VIS-F02 | 🔴 | `[CALC]` |
| VIS-R04 | R | "How can we avoid late-payment penalties?" | HR, FIN | Cost | overdue fees + calendar of due dates | 🔴 | `[CALC]`; penalty amounts not in DB `[NEW]` |

---

## MODULE 8 — PERFORMANCE MANAGEMENT

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| PRF-I01 | I | "Give me a performance summary — pending appraisals, PIP, PDP." | **HR**, GM | Overview | `get_performance_summary` | ✅ | — |
| PRF-I02 | I | "Who is on a PIP and why?" | **HR**, GM | Risk | `get_pip_overview`, `get_employee_performance` (PIP `factors`) | ✅ | Names + department ✅; factors per person via employee tool |
| PRF-I03 | I | "How many appraisals are pending and which cycle is active?" | HR, GM | Cycle | `get_appraisal_status`, `get_performance_cycles` | ✅ | — |
| PRF-I04 | I | "Who hasn't done their self-review? Which managers haven't reviewed?" | **HR**, GM | Follow-up | `performa_child_cycles.self_review_status/manager_review_status` | 🟡 | Tool gives counts only → named list `[TOOL]`; table has no `resort_id` |
| PRF-I05 | I | "Which KPIs are approved, pending or rejected?" | HR, GM | Goals | `get_kpi_overview`, `get_kpi_details` | ✅ | — |
| PRF-I06 | I | "What check-ins are pending or upcoming?" | HR, HOD | Coaching | `get_monthly_checkins`, `get_performance_meetings` | ✅ | — |
| PRF-I07 | I | "Who got the highest and lowest ratings?" | **HR**, GM | Talent | rating inside `performa_child_cycles.manager_review_data` (JSON) | 🔴 | `[TOOL]` parse JSON; ratings are confidential — HR only |
| PRF-A01 | A | "Appraisal completion by department — who's furthest behind?" | **HR**, GM | Completion | `performa_child_cycles` ⨝ dept | 🔴 | `[DASH]` "completion" card → `[TOOL]` |
| PRF-A02 | A | "Is the bottleneck self-review or manager review?" | HR, GM | Process | `[DASH]` "throughput" card | 🔴 | `[TOOL]` |
| PRF-A03 | A | "What's the rating distribution — and is any department rating too leniently?" | HR, CEO | Calibration | rating JSON × dept | 🔴 | `[CALC]` |
| PRF-A04 | A | "Do PIPs work — how many completed vs cancelled vs ended in exit?" | HR | Effectiveness | `employee_pip_plans.status` + resignation/termination | 🟡 | `get_pip_overview(status)` per status; outcome join `[CALC]` |
| PRF-A05 | A | "Are KPI targets being met — actual vs target budget?" | GM, CEO, HR | Goals | `performance_kpi_parents` (budget, weightage, score, response_*) | 🟡 | `get_kpi_details` lists; achievement calc `[CALC]` |
| PRF-A06 | A | "Do high performers get bigger increments?" | HR, CEO | Pay-for-performance | rating × `people_salary_increment` | 🔴 | `[CALC]` |
| PRF-F01 | F | "Will the appraisal cycle finish on time? Which departments will miss it?" | **HR**, GM | Cycle risk | `performance_cycles.End_Date`, completion pace | 🔴 | `[CALC]` required-pace vs current-pace |
| PRF-F02 | F | "What performance bonus will we pay this year?" | FIN, HR, GM | Cost | `performance_bonus_configs`, KPI score, `payroll_reviews.kpi_bonus` | 🔴 | `[CALC]` `[POLICY]` |
| PRF-R01 | R | "Which managers should I chase on reviews?" | **HR** | Process | PRF-I04 | 🔴 | `[TOOL]` |
| PRF-R02 | R | "Who might be a promotion candidate?" | HR, HOD | Talent | ratings + tenure + no recent promotion | 🔴 | `[TOOL]` `[CALC]` |
| PRF-R03 | R | "What development should Ahmed get?" | HR, HOD | PDP | PDP `factors`, learning catalogue, rating gaps | 🔴 | Composite; ties to LRN-R01 |

---

## MODULE 9 — EMPLOYEE RELATIONS (Grievance & Disciplinary)

> Confidentiality rules the engine must keep: confidential grievances **never** disclose the complainant (`Request_Identity_Disclosure`, `Identity_Disclosed_To`). Cases are HR/GM-restricted data. Nothing here may be used to *score* an individual for adverse action without HR review.

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| ER-I01 | I | "How many grievance and disciplinary cases are open?" | **HR**, GM | Overview | `get_employee_relations_summary` | ✅ | — |
| ER-I02 | I | "Show open disciplinary cases / grievances (by department)." | HR, GM | Caseload | `get_disciplinary_cases`, `get_grievance_cases` | ✅ | — |
| ER-I03 | I | "Who are the repeat offenders?" | **HR** | Risk | `get_repeat_offenders` | ✅ | — |
| ER-I04 | I | "Has Ahmed had any warnings or grievances?" | HR | History | `get_employee_relations_history` | ✅ | — |
| ER-I05 | I | "Show confidential grievances." | HR, GM | Sensitive | `get_confidential_cases` (identity withheld) | ✅ | — |
| ER-I06 | I | "Which cases are overdue against SLA?" | **HR**, GM | Case control | `grivance_escaltion_models`, `escalation_days`, resolution timelines | 🔴 | `[DASH]` "sla" card → `[TOOL]` |
| ER-I07 | I | "Which written warnings expire soon?" | HR | Record hygiene | `disciplinary_submits.Expiry_date` | 🔴 | `[TOOL]` |
| ER-A01 | A | "Which departments generate the most cases, and per 100 staff?" | **HR**, GM, CEO | Hotspots | `get_disciplinary_cases/get_grievance_cases.by_department` | 🟡 | Normalise by headcount `[CALC]` |
| ER-A02 | A | "What are the most common offences and grievance categories?" | HR, GM | Root cause | by_category | ✅ | — |
| ER-A03 | A | "What actions do we take — warnings, suspensions, terminations?" | HR, GM | Consistency | `get_disciplinary_outcomes` | ✅ | — |
| ER-A04 | A | "How long do cases take to resolve?" | HR, GM | SLA | resolution timeline tables | 🔴 | `[DASH]` → `[TOOL]` |
| ER-A05 | A | "Are similar offences getting similar outcomes across departments?" | HR, CEO | Fairness | offence × action × dept | 🔴 | `[CALC]` |
| ER-A06 | A | "Case volume trend by month." | HR, GM | Trend | `disciplinary_submits.created_at`, grievance dates | 🔴 | `[HIST]` `[TOOL]` |
| ER-F01 | F | "Which open cases will breach SLA this week?" | **HR**, GM | Case control | escalation days vs age | 🔴 | `[CALC]` |
| ER-F02 | F | "How many cases should we expect next quarter?" | HR | Capacity | trend × headcount | 🔴 | `[MODEL]` simple rate; low volume ⚠ |
| ER-R01 | R | "Which department needs an intervention?" | **HR, GM**, CEO | Culture | ER hotspots + turnover + incidents + survey (aggregate only) | 🔴 | Composite (X-09) |
| ER-R02 | R | "Do the grievances suggest a policy gap or training need?" | HR, L&D | Prevention | categories + descriptions (free text) | 🔴 | `[CALC]` + text themes; free text is untrusted content |
| ER-R03 | R | "Who should I assign this case to?" | HR | Workload | committee membership + current load | 🟡 | `get_committee_workload` covers incident side `[TOOL]` for ER committees |

---

## MODULE 10 — INCIDENT & SAFETY

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| INC-I01 | I | "Summarise incidents — open, severity, where." | **HR**, GM, EXC | Overview | `get_incident_summary` | ✅ | — |
| INC-I02 | I | "Show high-severity or open incidents." | HR, GM | Caseload | `get_incidents(status,severity,category)` | ✅ | — |
| INC-I03 | I | "Which investigations are overdue?" | **HR**, GM | Control | `get_incident_investigations(overdue_only)` | ✅ | — |
| INC-I04 | I | "Who has the most delegated investigation work?" | HR | Workload | `get_committee_workload` | ✅ | — |
| INC-I05 | I | "Has Ahmed been involved in incidents?" | HR | History | `get_employee_incidents` | ✅ | — |
| INC-A01 | A | "Which locations and categories have the most incidents?" | HR, GM, EXC | Hotspots | `get_incident_summary` breakdowns | ✅ | — |
| INC-A02 | A | "Incident trend by month and average resolution time." | HR, GM | Trend | `[DASH]` volume card; summary has avg resolution | 🟡 | Monthly series `[TOOL]` |
| INC-A03 | A | "Incident rate per 1,000 staff-hours by department." | GM, CEO, HR | Safety KPI | incidents ÷ attendance hours | 🔴 | `[CALC]` cross T&A |
| INC-A04 | A | "Which preventive actions were taken, and did repeat incidents fall?" | HR, GM | Effectiveness | `incidents.preventive_measures/action_taken`, `incident_resolution_timeline` | 🔴 | `[CALC]` |
| INC-F01 | F | "Which investigations will go overdue next week?" | HR, GM | Control | expected resolution dates | 🟡 | `[CALC]` on `get_incident_investigations` |
| INC-F02 | F | "Do we expect more incidents in peak months?" | GM, HR | Seasonality | monthly incidents × occupancy | 🔴 | `[HIST]` `[MODEL]` ⚠ 11 incidents locally |
| INC-R01 | R | "What should we do about our worst hotspot?" | **GM, HR**, EXC | Prevention | INC-A01 + outcomes + training coverage | 🔴 | Composite; links to L&D |
| INC-R02 | R | "Who should investigate this incident?" | HR | Assignment | committee workload + conflict (same dept) | 🟡 | `[TOOL]` |

---

## MODULE 11 — LEARNING & DEVELOPMENT

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| LRN-I01 | I | "Give me a learning summary — programs, sessions, attendance." | **HR, L&D**, GM | Overview | `get_learning_summary` | ✅ | — |
| LRN-I02 | I | "What training is scheduled this week?" | HR, L&D, HOD | Calendar | `get_training_schedule` | ✅ | — |
| LRN-I03 | I | "Which trainings are mandatory?" | HR, L&D | Compliance | `get_mandatory_training` | ✅ | — |
| LRN-I04 | I | "What training has Ahmed done?" | HR, L&D, HOD | History | `get_employee_training` | ✅ | — |
| LRN-I05 | I | "Who hasn't completed mandatory training X?" | **HR, L&D**, HOD | Compliance | `mandatory_learning_programs`, `training_participants`/`training_attendance` | 🔴 | `[DASH]` "mandatory" card → `[TOOL]`; `training_participants` has no `resort_id` |
| LRN-I06 | I | "What learning requests are waiting?" | L&D, HR | Pipeline | `learning_requests`, `learning_requests_employees` | 🔴 | `[DASH]` "requests" card → `[TOOL]` |
| LRN-I07 | I | "Do probationers have their required training?" | HR, L&D | Probation | `probationary_learning_programs`, `probation_end_date` | 🔴 | `[DASH]` "probationary" → `[TOOL]` |
| LRN-A01 | A | "What's training completion/attendance by department?" | HR, L&D, GM | Completion | `get_training_attendance` (resort-wide) | 🟡 | Dept split `[TOOL]` |
| LRN-A02 | A | "Average training hours per employee, by department." | HR, L&D | Investment | `learning_programs.hours/days` × attendance | 🔴 | `[CALC]` |
| LRN-A03 | A | "How do people rate our training?" | L&D | Quality | `training_feedback_responses` | 🔴 | `[TOOL]` |
| LRN-A04 | A | "Training cost per employee." | FIN, L&D | Cost | — | 🔴 | `[NEW]` no cost field on programs (only trainer/external flags) |
| LRN-F01 | F | "Which mandatory trainings will fall due in the next 60 days?" | HR, L&D | Compliance | `learning_programs.frequency/frequency_day` + last attendance per employee | 🔴 | `[NEW]` there is no per-employee "next due" ledger; derive from frequency + last completion `[CALC]` |
| LRN-F02 | F | "How much training demand is coming from requests and probation?" | L&D, HR | Capacity | `learning_requests` + probation ends | 🔴 | `[CALC]` |
| LRN-R01 | R | "Which skills gaps should we train for next?" | **L&D, HR**, GM | Planning | PIP/PDP `factors`, incidents, appraisal gaps, grievance themes | 🔴 | Composite + text themes |
| LRN-R02 | R | "Which sessions are under-subscribed — cancel or promote?" | L&D | Efficiency | `training_participants` vs capacity | 🔴 | `[TOOL]` capacity not stored `[NEW]` |

---

## MODULE 12 — SURVEY & ENGAGEMENT

> **Anonymity rule:** `parent_surveys.survey_privacy_type` + `Min_response` define how a survey may be broken down. The engine must not slice any survey below `Min_response` and must never show who answered what on an anonymous survey. `get_survey_participation` lists non-respondents by name — fine for chasing participation, never combined with answers.

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| SUR-I01 | I | "How are our surveys doing — active, participation?" | **HR**, GM | Overview | `get_survey_summary`, `get_surveys` | ✅ | — |
| SUR-I02 | I | "Who hasn't completed the survey?" | HR, HOD | Follow-up | `get_survey_participation(survey)` | ✅ | HOD `[SCOPE]` own team only |
| SUR-A01 | A | "What's the sentiment/score pulse from the latest survey?" | **HR, GM, CEO** | Engagement | `survey_results.Emp_Ans` for rating questions | 🔴 | `[DASH]` "sentiment" → `[TOOL]`; child tables no `resort_id` |
| SUR-A02 | A | "Which answers stand out — what do most people choose?" | HR, GM | Hotspots | `[DASH]` "hotspots" | 🔴 | `[TOOL]` |
| SUR-A03 | A | "Participation by department." | HR, GM | Response | `survey_employees` ⨝ dept | 🔴 | `[TOOL]` |
| SUR-A04 | A | "How did scores change versus the last run of this recurring survey?" | HR, GM, CEO | Trend | `parent_surveys.Recurring_survey` + results | 🔴 | `[HIST]` `[CALC]` |
| SUR-F01 | F | "What response rate should we expect for the next pulse?" | HR | Planning | past rates | 🔴 | `[MODEL]` simple |
| SUR-R01 | R | "Which departments should we focus on to improve engagement?" | **HR, GM, CEO** | Culture | SUR-A01 by dept (above `Min_response`) + turnover + ER | 🔴 | Composite (X-09) |
| SUR-R02 | R | "Which questions drive low scores?" | HR | Insight | per-question means | 🔴 | `[CALC]` |

---

## MODULE 13 — ACCOMMODATION & FACILITIES

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| ACM-I01 | I | "How many beds are free? Where?" | **ACC, HR**, GM | Capacity | `get_accommodation_summary`, `get_available_beds(gender)` | ✅ | — |
| ACM-I02 | I | "Where does Ahmed live and who are his roommates?" | HR, ACC | Lookup | `get_employee_accommodation` | ✅ | — |
| ACM-I03 | I | "Which building is fullest?" | ACC, HR | Occupancy | `get_building_occupancy` | ✅ | — |
| ACM-I04 | I | "What maintenance requests are open?" | **ACC**, HR | Maintenance | `get_maintenance_requests` | ✅ | — |
| ACM-I05 | I | "Which requests are on hold or escalated?" | ACC | SLA | `maintanace_requests.ReasonOnHold/hold_until/escalation_notified_at` | 🟡 | Tool returns status; hold/escalation detail `[TOOL]` |
| ACM-A01 | A | "What's our maintenance backlog and average completion time?" | ACC, HR, GM | SLA | `maintanace_requests` (date/start_time/end_time/Status) | 🔴 | `[DASH]` "maintenance" → `[TOOL]`; SQL ✅ (has `resort_id`) |
| ACM-A02 | A | "Which rooms or buildings generate the most repairs?" | ACC | Hotspots | `[DASH]` "hotspots" | 🔴 | `[TOOL]`; SQL ✅ |
| ACM-A03 | A | "Occupancy by gender block / nationality / department." | ACC, HR | Allocation | `assing_accommodations` ⨝ employees | 🟡 | `[TOOL]` |
| ACM-F01 | F | "Will we have enough beds for the people we're about to hire?" | **ACC, HR**, GM | Capacity | free beds vs accepted offers/joiners/onboarding | 🔴 | `[DASH]` "demand" card (beds vs hiring) → `[TOOL]` — X-14 |
| ACM-F02 | F | "When will we run out of beds at the current hiring pace?" | ACC, HR | Capacity | `[CALC]` | 🔴 | `[CALC]` |
| ACM-R01 | R | "Where should I house the new joiners?" | ACC, HR | Allocation | gender, dept, free beds, nationality mix | 🔴 | `[TOOL]` read-only suggestion |
| ACM-R02 | R | "Which repairs should I schedule first?" | ACC | Maintenance | priority, age, occupancy of the room | 🔴 | `[CALC]` |

---

## MODULE 14 — ISLAND PASS / TRAVEL

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| ISL-I01 | I | "Which island-pass / boarding-pass requests are pending?" | **HR, HOD, SEC** | Approvals (order HOD → HR → Security Manager) | `get_boarding_pass_requests(status)` | ✅ | Both names mean the same thing |
| ISL-I02 | I | "Who travels out or returns this week — how many seats do we need?" | SEC, HR | Logistics | `employee_travel_passes` (arrival/departure dates, mode), `manifest` | 🔴 | `[TOOL]` ⚠ boarding tool is filtered by status, not by travel date |
| ISL-A01 | A | "How long do island passes take to approve at each step?" | HR, SEC | Process | pass approval status table timestamps | 🔴 | `[TOOL]` `[HIST]` |
| ISL-A02 | A | "Travel demand by month and transport mode." | SEC, GM | Demand | `employee_travel_passes.arrival_mode/departure_mode` | 🔴 | `[TOOL]`; SQL ✅ |
| ISL-F01 | F | "How many staff will travel around Eid or peak holiday weeks?" | SEC, HR | Planning | approved leave with travel + past patterns | 🔴 | `[CALC]` |
| ISL-R01 | R | "Should we stagger these requests to keep department coverage?" | HOD, HR | Coverage | passes × roster × leave | 🔴 | Composite |

---

## MODULE 15 — COMPLIANCE (statutory & policy)

> `compliances` is a *log of rule hits*, not a clean register: 1,432 of 1,440 rows are "Breached" locally and rules re-fire. Every answer needs de-duplication by (employee, rule), a recency window and the severity label (`severity_ai`: Critical/High/Medium).

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| CMP-I01 | I | "What compliance issues should I fix first?" | **HR**, GM, CEO | Prioritisation | `get_compliance_issues` (by severity, module, top actions with suggested fix) | ✅ | Needs de-dup (above) |
| CMP-I02 | I | "Are we meeting the localization requirement?" | HR, GM | Legal | `get_workforce_compliance(localization)` | ✅ | — |
| CMP-I03 | I | "Which statutory rules are we breaching — pension, min wage, working hours, TIN, visa?" | HR, GM | Legal | `compliances.compliance_breached_name` | 🟡 | Filter-by-rule `[TOOL]` |
| CMP-A01 | A | "Breaches by rule and by department." | HR, GM | Hotspots | `compliances` ⨝ employee dept | 🟡 | Dept split `[TOOL]` |
| CMP-A02 | A | "Are breaches going down? How long do they stay open?" | HR, GM, CEO | Trend | `compliances.reported_on`, `status`, resolution date? | 🔴 | `[HIST]` — no resolved-at column, only `status`/`updated_at` |
| CMP-F01 | F | "Which employees will breach a rule next month if nothing changes?" | **HR** | Early warning | probation end (Extended Probation), visa expiry, OT hours, contract end | 🔴 | `[CALC]` per-rule forward check — see X-05 |
| CMP-R01 | R | "Give me a remediation plan with owners and deadlines." | **HR**, GM | Action plan | `compliances.assigned_to`, `remediation_ai` | 🟡 | `[TOOL]` group by owner |
| CMP-R02 | R | "Explain why this employee triggered this rule." | HR | Explain | `/compliance_explain` output (`description_ai`) | 🟡 | `[TOOL]` read stored explanation |

---

## MODULE 16 — OTHER MODULES (documents, SOS, clinic, onboarding, platform)

| ID | Cat | Question | Roles | Function | Data | Status | Gap / logic |
|---|---|---|---|---|---|---|---|
| DOC-I01 | I | "What documents does Ahmed have on file? Does he have a passport on file?" | HR | Records | `get_employee_documents` | ✅ | Metadata only |
| DOC-I02 | I | "Which employee documents expire in the next 60 days?" | **HR** | Expiry | `employees_documents.expiry_date` | 🔴 | `[TOOL]` (visa-type docs covered by `get_visa_expiries`) |
| DOC-I03 | I | "Find all employment contracts / certificates." | HR | Search | `search_documents` | ✅ | — |
| DOC-A01 | A | "Which job descriptions are not acknowledged?" | HR | Compliance | `job_descriptions`, `job_description_employee_records`; rule "Job Description Not Received" | 🟡 | `[TOOL]` |
| SOS-I01 | I | "Any active emergencies?" | **HR**, GM, SEC | Safety | `get_active_sos` | ✅ | — |
| SOS-A01 | A | "How fast do we respond to SOS alerts?" | SEC, GM | Response time | `sos_history` (date/time, `sos_approved_time`) | 🔴 | `[TOOL]` |
| CLN-A01 | A | "How many clinic visits this month and which departments?" | HR, GM | Health | `clinic_appointment`, `clinic_treatment` | 🔴 | `[TOOL]` — **medical data**: aggregate only, no diagnosis; separate permission decision |
| CLN-A02 | A | "Do medical certificates match sick-leave days?" | HR | Control | `clinic_medical_certificate` ↔ `employees_leaves` | 🔴 | `[CALC]` sensitive |
| ONB-I01 | I | "Where is each new joiner in onboarding — itinerary, acknowledgements, facility tour?" | **HR** | Onboarding | `employee_itineraries`, `employee_onboarding_acknowledgements`, onboarding events | 🔴 | `[TOOL]` |
| ONB-F01 | F | "Who arrives next week and what do they need — pickup, bed, visa, medical?" | HR, ACC | Logistics | itineraries (arrival_date, pickup, medical) + beds + visa | 🔴 | X-14 |
| PLT-I01 | I | "What's our policy on X? What does the law say?" | all tiers | Policy | `search_policy_documents` | ✅ | Policy-tier users (HOD/EXCOM/MGR) can already ask this |
| PLT-I02 | I | "How do I do X in Wisdom AI?" | all tiers | Guidance | `get_module_guide` | ✅ | — |
| PLT-I03 | I | "Which announcements/support tickets are open?" | HR | Admin | `announcement`, `support` | 🔴 | Low priority `[TOOL]` |

---
# PART 4 — CROSS-MODULE INTELLIGENCE

These are the questions an HR Director, Finance Director, GM or CEO actually asks. None can be answered by one existing tool. With a 5-round tool budget and a short reply, the LLM should **not** be asked to chain 4–6 tools; each row names the **composite tool** that should return the pre-joined facts (the LLM then only explains and recommends). Cat: **I** insight · **A** analytics · **F** forecast · **R** recommendation.

| ID | Cat | Question | Roles | Modules joined | Data sources (tables / existing tools) | Status | Logic / what must be built |
|---|---|---|---|---|---|---|---|
| X-01 | F | "How will upcoming visa expiries affect our workforce availability over the next three months?" | **HR, GM, CEO** | Visa × People × Roster × Leave | `visaDocs()` expiries (≤90 d) ⨝ `employees.Dept_id`; `duty_rosters`; approved `employees_leaves`; `visa_renewals` (renewal lead time) | 🔴 | Composite `expiring_workforce_impact(window)`: per department → staff with documents expiring in window, % of department, overlap with leave, renewal already started?, earliest date capacity drops. Assumption to state: an expired permit means *cannot lawfully work*, not that the person leaves. Flag depts > a configurable % (e.g. 10 %) |
| X-02 | F | "Which departments are likely to face staffing shortages based on approved manpower, current headcount and resignations?" | **HR, GM, CEO, EXC** | WFP × People × TA | `get_budgeted_headcount(department)`, `get_department_breakdown`, `get_resignations` (LWD in window), accepted offers (`applicant_offer_contracts`), open `vacancies` | 🟡 | The three inputs exist; projected_gap = approved − (current − exits_in_window + accepted_not_joined). Wrap as composite `projected_manning_gap(months)`; chaining 3 tools in chat is fragile (dept-name matching between tools) |
| X-03 | A | "How much are we spending on overtime compared with our departmental manpower budget?" | **FIN, HR, GM, CEO** | T&A × Payroll × Budget | OT cost: `payroll_reviews.earnings_overtime` (finalised runs) by dept; OT hours: `parent_attendaces`; budget: `get_workforce_budget(department)` | 🔴 | **Baseline problem:** the budget engine has *no overtime line* (components are "Operational Cost" and "Recruitment Cost" + salary + allowances). Decide the denominator first: OT ÷ department salary budget (÷12 for a month), or add an OT allowance to the budget `[NEW]`. Currency: budget USD, payroll per `payroll_unit`. Needs tool (child table has no `resort_id`). `[POLICY]` GM/CEO |
| X-04 | A/R | "Which employees have accumulated excessive leave balances, and what financial or operational impact could that create?" | **HR, FIN, GM, CEO** | Leave × Payroll × Roster × Occupancy | `Common::getEmployeeLeaveBalance` per employee (balance, carry-forward, cap); daily rate (follow `final_settlements.leave_encashment` formula); future occupancy; dept headcount | 🔴 | Composite `leave_balance_exposure`: list > N days; **financial** = days × daily rate (liability, HR-only); **operational** = if all took it in the next 3 months → peak-leave days per dept vs low/high-occupancy weeks (dashboard rule). Threshold N is a business setting `[NEW]` (e.g. > 30 days) |
| X-05 | F | "Who is likely to breach a legal or policy rule in the next 30 days if nothing changes?" | **HR** | Compliance × Visa × People × T&A | probation end (`Extended Probation`), visa/WP expiry, weekly hours / OT, min-wage vs salary, pension/TIN rules | 🔴 | Forward-looking version of the rule engine: run each rule with a date shifted forward; output (employee, rule, date it trips, fix) |
| X-06 | A/R | "Which departments have the highest employee turnover, and what factors might be contributing?" | **HR, GM, CEO** | People × ER × Incident × Survey × T&A × Payroll × Leave | exits per dept (`employee_resignation`, reasons); OT/head; unused leave; disciplinary + grievance per 100 staff; incidents; time since increment (`incremented_date`); salary vs position median; survey scores by dept **(only above `Min_response`)**; vacancy age | 🔴 | Composite `turnover_drivers(dept)`: turnover % + ranked drivers with each driver's dept value vs resort average (descriptive, **not causal**). Say so in the answer. Reasons given by leavers are the only direct evidence |
| X-07 | A/R | "Where are we over-staffed compared with occupancy?" | GM, CEO, FIN, HR | WFP × T&A × People | headcount by dept; hours worked; `occuplanies`; required staff (`/predict_staff_batch`) | 🔴 | Needs a per-department staffing norm `[NEW]`; without it only resort-level comparison is honest |
| X-08 | A | "What does it cost us when someone leaves?" | **HR, FIN, CEO** | People × TA × Visa × Payroll × Accommodation | replacement vacancy cost fields, visa spend (`total_expensess_since_joings`), settlement (`final_settlements`), deposit, OT backfill, empty-bed days | 🔴 | Partial `[NEW]`: no recruitment-agency or training-cost data; present as "known cost components" and name the missing ones |
| X-09 | I/R | "Which departments are our biggest people-risk hotspots overall?" | **HR, GM, CEO** | ER × Incident × Survey × People × T&A | per dept: cases/100, incidents, OT, turnover, PIPs, engagement score | 🔴 | Department heat-map `[TOOL]`; weights are a business choice — expose the components, don't hide them in one score |
| X-10 | A | "What's our payroll cost per occupied room, and how is it moving?" | **FIN, GM, CEO** | Payroll × WFP | finalised payroll ÷ `occuplanies.occupancyOccupiedRooms` | 🔴 | `[CALC]` `[POLICY]`. **There is no revenue/ADR/RevPAR data in the system** — do not promise "labour cost as % of revenue" |
| X-11 | A | "How much are our vacancies costing us in overtime and lost capacity?" | GM, FIN, HR | TA × T&A × Payroll | open vacancies by dept; OT in the same dept | 🔴 | `[CALC]` descriptive |
| X-12 | A | "How many staff-hours do we use per occupied room, by month?" | **GM, CEO**, HR | T&A × WFP | `parent_attendaces` hours ÷ occupied rooms | 🔴 | `[CALC]` `[HIST]` (attendance coverage ⚠) |
| X-13 | F | "What will total labour cost look like over the next 3 months — increments, promotions, new hires, exits, overtime?" | **FIN, HR, CEO** | Payroll × People × TA × T&A | `get_payroll_summary` base; `get_salary_increments`/`get_promotions` impact; accepted offers; exits; OT run-rate | 🔴 | Composite `labour_cost_projection(months)` with each driver itemised; `[POLICY]` GM |
| X-14 | F/I | "Who joins in the next 30 days, and are their visa, bed, medical and onboarding ready?" | **HR, ACC, GM** | TA × Visa × Accommodation × Onboarding | accepted offers/joining dates, `employee_itineraries`, `work_permits`/`visa_renewals`, free beds (`get_available_beds`), onboarding acknowledgements | 🔴 | Readiness matrix `[TOOL]` per joiner |
| X-15 | F/I | "Who is leaving, and what is still open for each exit — clearance, settlement, loan, deposit, bed, visa?" | **HR, FIN** | People × Payroll × Visa × Accommodation | `employee_resignation`, `exit_clearance_form` responses, `final_settlements`, `payroll_recovery_schedule` (loan balance), `deposit_refounds`, `accommodation_release_decisions` | 🔴 | Exit-readiness table + **total cash needed** for settlements and refunds in the next 30–60 days |
| X-16 | I | "Which employees have several warning signs at once — PIP, disciplinary, high absence, heavy overtime?" | **HR** | Performance × ER × T&A | PIP/PDP, disciplinary count, absence rate, OT hours | 🔴 | HR-only watchlist; list the signs, don't show a "risk score"; confidential grievance identities excluded |
| X-17 | R | "Should we confirm, extend or fail each probationer?" | **HR, HOD** | People × T&A × ER × Learning × Performance | probation end, attendance, warnings, probationary training, check-ins | 🔴 | Composite per probationer — decision support only |
| X-18 | A/R | "Which roles are most dependent on expats, and where can we localise?" | HR, GM, CEO | People × Visa × TA | nationality by position/dept; reserved positions; Maldivian applicants per vacancy; expiries | 🔴 | `[TOOL]`; uses `is_reserved`, `is_required_local` |
| X-19 | F | "Where is leave plus holidays plus day-offs going to leave us short, and when?" | HR, HOD, GM | Leave × T&A × WFP | approved leave, `duty_rosters`, `public_holidays`, required staff | 🔴 | See TNA-F03/LEA-F05 |
| X-20 | A | "What did absence really cost us last month — deductions and backfill overtime?" | FIN, HR, GM | T&A × Payroll | `payroll_deductions.attendance_deduction`, OT in the same departments | 🔴 | `[CALC]` |
| X-21 | I | "How much loan money is out with people who are leaving or on short contracts?" | **FIN, HR** | Payroll × People | `payroll_advance` + `payroll_recovery_schedule` ⨝ resignations / contract end | 🔴 | `[TOOL]`; contract end `[NEW]` (field unused) |
| X-22 | A | "Show approved positions, filled, budget and actual cost by department in one view." | **FIN, GM, CEO, HR** | WFP × People × Payroll | `get_budgeted_headcount`, `get_workforce_budget`, `get_payroll_by_department` | 🟡 | Three tools exist; join by department name and align units/periods `[CALC]` |
| X-23 | A | "How is accommodation affecting us — occupancy, maintenance backlog, and staff living off-site?" | ACC, HR, GM | Accommodation × People | beds, `assing_accommodations`, maintenance, headcount | 🔴 | `[TOOL]` |
| X-24 | R | "Which departments with incidents haven't had safety training?" | HR, L&D, GM | Incident × Learning | incident dept/location × training attendance | 🔴 | `[TOOL]`; incident location vs department mapping must be defined |
| X-25 | R | "Do employees on a PIP or PDP have a training plan?" | HR, L&D | Performance × Learning | PIP/PDP × `training_participants` | 🔴 | `[TOOL]` |
| X-26 | A | "Are disciplinary cases concentrated among probationers or new hires?" | HR | ER × People | cases ⨝ tenure/probation | 🔴 | `[CALC]` |
| X-27 | I | "What needs my attention today?" (executive briefing) | **HR, GM, CEO** | All | pending approvals, compliance breaches, upcoming interviews, expiring documents, high-severity incidents (already described as an *aggregation instruction* in the HR system prompt) | 🟡 | Today the LLM must chain many tools in ≤5 rounds. Provide `get_executive_briefing` returning ranked, de-duplicated items with counts and links |
| X-28 | I/R | "Are we ready to run payroll — what is blocking it?" | **HR, FIN** | Payroll × Leave × T&A × Loans | the **hard validation gates inside `PayrollController::fetchTimeAttendance`**: pending leave approvals, missing OT status, attendance gaps; unapproved advances | 🔴 | High value; the checks exist in code — expose them as a read-only `payroll_readiness` tool (do not duplicate the rules by hand) |
| X-29 | F/R | "Are we ready for peak season — staffing need, leave, visas, beds, vacancies?" | **GM, CEO, HR** | WFP × Leave × Visa × Accommodation × TA | WFP-F01, LEA-F01, VIS expiries, beds, open vacancies | 🔴 | Readiness scorecard per dimension with the limiting factor named |
| X-30 | F | "What will service charge per head be next quarter given occupancy and headcount?" | HR, FIN, GM | Payroll × WFP × People | `resort_service_charges`, occupancy forecast, `entitled_service_charge` headcount | 🔴 | `[MODEL]` simple |
| X-31 | F | "What do public holidays cost us in roster and overtime next quarter?" | FIN, HR | T&A × Payroll | `public_holidays`, roster, OT rates 1.25×/1.5× | 🔴 | `[CALC]` |
| X-32 | R | "Which key roles have a single point of failure — head of department with expiring visa, near retirement, on notice, or on PIP?" | **CEO, GM, HR** | People × Visa × Performance | rank 1/2/4 holders × expiries, notice, PIP; `dob` | 🔴 | `[TOOL]`; `dob` ⚠ sparse |
| X-33 | A | "What is the total cost of an expat versus a local employee — salary, visa, medical, insurance, accommodation, ticket?" | **FIN, HR, CEO** | Payroll × Visa × Accommodation | salary, `work_permits`, `quota_slot_renewals`, `employee_insurances`, medical, deposit, accommodation cost?, ticket | 🔴 | Accommodation has no cost field `[NEW]`; present known components only |
| X-34 | R | "If we hire 10 more in Housekeeping, what does it cost and do we have beds and visas?" | HR, GM, FIN | WFP × Accommodation × Visa | budget per position (`annualBudgetForVacantSlot`), free beds, visa fees | 🔴 | Scenario tool; read-only |

---

# PART 5 — CONVERSATIONAL & FOLLOW-UP INTENTS

**Why this needs its own design.** Today the chat history is replayed to the model as plain user/assistant text only; tool results are dropped (`WisdomChatController`: `$history[] = ['role'=>'assistant','content'=>$reply]`). A follow-up such as "only the Kitchen ones" works only if the previous *answer text* happened to list those rows. Every chain below depends on the engine remembering **structured context**: last tool + arguments, the entity set returned (employee ids, department ids, vacancy ids, dates), the time window, department scope, currency, and the data's `as_of` time.

| ID | Chain (each line = next user turn) | Context the engine must carry | Status | Gap |
|---|---|---|---|---|
| CNV-01 | "Who is on leave next week?" → "Which of them are in Housekeeping?" → "Is Housekeeping short-handed on those days?" → "What should I do?" | result set (employee ids, dates), dept filter, date window | 🔴 | Structured context + `[TOOL]` dept filter + coverage calc (TNA-I04 fix) + recommendation tool |
| CNV-02 | "Which documents expire this month?" → "Only work permits." → "For the kitchen." → "What will renewal cost?" → "Is the wallet enough?" → "Which first?" | doc_type filter, dept filter, window, USD/MVR | 🔴 | Dept in expiry tool, fee projection (VIS-F01), wallet check, priority rule |
| CNV-03 | "How many people resigned this year?" → "Which department is worst?" → "What reasons did they give?" → "Compare with last year." → "What should we do about it?" | year, dept entity, previous answer's metric | 🔴 | Turnover tooling (PEO-A02/A04), prior-year compare, rule-based recommendations |
| CNV-04 | "What's this month's payroll?" → "Compare with last month." → "What drove the change?" → "How much is overtime?" → "Which department?" → "How is that against its budget?" | period, dept, currency, `is_estimated` flag | 🔴 | PAY-A02 driver detail, PAY-A03, X-03 baseline decision |
| CNV-05 | "Are we short-staffed today?" → "Which department?" → "Who's absent there?" → "Is anyone on approved leave?" → "Who could cover?" | date, dept, absent list | 🟡 | Roster-aware coverage; cover-suggestion tool (TNA-R03) |
| CNV-06 | "How many vacancies are open?" → "Which has the most applicants?" → "Show the top three." → "When could they start?" | vacancy id, applicant ids | 🟡 | AI ranking tool (TAC-I04); `NotiesPeriod` / `Joining_availability` exposure |
| CNV-07 | "What compliance issues should I resolve first?" → "Why is the first one critical?" → "Who is affected?" → "How do I fix it in the system?" | compliance row id, rule name | 🟡 | Explanation field exposure (`description_ai`); how-to via `get_module_guide` ✅ |
| CNV-08 | "What's our workforce budget?" → "Just Rooms Division." → "In rufiyaa." → "And how much have we spent?" → "Are we over?" | year, group, currency, division | 🟡 | Budget ✅; spent-to-date and variance tools (PAY-A10) |
| CNV-09 | "Who is at risk of leaving?" → "Why?" → "What can we offer?" → "Show their last appraisal." | employee ids, factor list | 🔴 | `[MODEL]` PEO-F02; appraisal detail tool |
| CNV-10 | Time shifts: "What about last month?" · "Same for next quarter." · "Compare with last year." · "Since January." | inherit metric + filters, change only the window | 🔴 | Every time-aware tool must accept explicit `from`/`to` (many accept only a single date or a keyword period) |
| CNV-11 | Entity references: "What about her?" · "Her manager?" · "The second one." · "Those three." | ordered entity list from the previous answer | 🔴 | Entity registry in session context |
| CNV-12 | Scope changes: "Only expats." · "Exclude interns." · "Top 5." · "Only critical." · "By department instead." | filter stack on last query | 🔴 | Filter parameters on tools; filter stack in context |
| CNV-13 | Currency/unit: "In dollars." · "Per month." · "Per head." | display preference persists for the session | 🟡 | Tools already return `usd` + `mvr`; remember user's choice |
| CNV-14 | Why / how: "Why did overtime go up?" · "How did you calculate that?" · "What is this based on, as of when?" | previous tool's `basis` and `as_of` | 🟡 | Only some tools return `basis`; standardise `as_of` + `basis` + `assumptions` on every tool |
| CNV-15 | What-if: "What if occupancy hits 90 %?" · "What if we hire five more?" · "What if the rate becomes 16?" | base scenario + changed parameter | 🔴 | Scenario parameters on forecast tools (WFP-F04, PAY-F08, X-34) |
| CNV-16 | Clarify: engine should ask back when ambiguous — "Which department?" · "Headcount or approved positions?" · "Which year?" | pending-question state | 🟡 | Prompt rule; already used for employee name/module disambiguation |
| CNV-17 | Permission follow-ups: GM asks "and their salary?" → refuse consistently; HOD asks "now show another department" → refuse consistently | role, tier | ✅ GM money refusal exists (prompt + tool gate); 🔴 HOD scope | `[SCOPE]` once HOD tier exists |
| CNV-18 | Action requests after an insight: "Approve it." · "Send them a reminder." · "Update the roster." | — | ✅ | Existing rule: read-only; names the module and steps. Keep for every new tool |
| CNV-19 | Export requests: "Give me that as Excel." | last report | 🔴 | Reports module exports exist (`ReportController`, predefined reports) but chat cannot trigger them; point to the report path via `get_module_guide` or add a safe "open report" deep link |
| CNV-20 | Recap: "Summarise what we've discussed." · "Turn that into a note for the GM." | session context | 🟡 | Works on text history; quality depends on retained facts |

---
# PART 6 — BUILD BACKLOG FOR THE AI ENGINEER

## 6.0 Summary of the library

**389 intents** in total: ✅ 77 supported · 🟡 81 partial · 🔴 231 development.

| Module | ✅ | 🟡 | 🔴 | Total |
|---|---|---|---|---|
| People & Workforce | 6 | 16 | 22 | 44 |
| Leave | 3 | 10 | 19 | 32 |
| Time & Attendance | 6 | 6 | 18 | 30 |
| Payroll & Finance | 9 | 10 | 20 | 39 |
| Workforce Planning, Budget & Occupancy | 5 | 6 | 19 | 30 |
| Talent Acquisition | 3 | 2 | 20 | 25 |
| Visa & Immigration | 7 | 4 | 12 | 23 |
| Performance | 5 | 3 | 10 | 18 |
| Employee Relations | 7 | 2 | 9 | 18 |
| Incident & Safety | 6 | 3 | 4 | 13 |
| Learning & Development | 4 | 1 | 10 | 15 |
| Survey & Engagement | 2 | 0 | 7 | 9 |
| Accommodation | 4 | 2 | 6 | 12 |
| Island Pass / Travel | 1 | 0 | 5 | 6 |
| Compliance | 2 | 4 | 2 | 8 |
| Documents | 2 | 1 | 1 | 4 |
| SOS | 1 | 0 | 1 | 2 |
| Clinic | 0 | 0 | 2 | 2 |
| Onboarding | 0 | 0 | 2 | 2 |
| Platform | 2 | 0 | 1 | 3 |
| Cross-module | 0 | 3 | 31 | 34 |
| Conversational | 2 | 8 | 10 | 20 |

| Intelligence category (modules 1–16) | ✅ | 🟡 | 🔴 |
|---|---|---|---|
| Insights | 65 | 22 | 40 |
| Analytics | 7 | 25 | 71 |
| Forecasting | 2 | 15 | 38 |
| Recommendations | 1 | 8 | 41 |

Gap tags across all rows: `[TOOL]` 153 · `[CALC]` 108 · `[DASH]` 33 · `[MODEL]` 15 · `[NEW]` 17 · `[HIST]` 12 · `[POLICY]` 13 · `[SCOPE]` 4. (`[ACCESS]` applies to every non-HR/GM role by definition and is not repeated per row.)

**Reading the numbers:** ✅ means "answerable now by a tool"; it does **not** mean a non-HR/GM role can ask it (those tiers have no data access). Most 🔴 rows are not "AI problems" — the data and often the logic already exist (33 rows are tagged `[DASH]`: the same calculation already runs in a dashboard card). They are missing a **chat-facing tool**.

## 6.1 Foundations — do these first; every module depends on them

| # | Item | Why | Evidence in code |
|---|---|---|---|
| F1 | **Structured conversation context** (last tool + args, entity ids, window, scope, currency, `as_of`) stored in the session, replayed to the model as a compact system block | All 20 CNV chains and every drill-down fail without it. Today only user/assistant text is kept | `WisdomChatController::chat` stores text only; `MAX_HISTORY = 20` |
| F2 | **Role model beyond HR/GM:** (a) `finance` tier (payroll, advances, budget, no ER/confidential); (b) scoped `leader` tiers for HOD / EXCOM / MGR reusing `Common::hasFullDataAccess()` + `getScopedDepartmentIds()` (dept or direct-reports); (c) written decision on **aggregate-only money for GM/CEO** | HOD/EXCOM/MGR/FIN are the main askers of the questions in this library and currently get no data; GM is blocked from every cost question | `WisdomAccess::tier()`; `WisdomTools::PAYROLL_TOOLS`; CLAUDE.md invariant #5 |
| F3 | **Scope in tool context:** add `dept_ids` / `scope` to `WisdomAccess::context()` and make every tool honour it | Without it a scoped tier would leak other departments | `$ctx` currently carries only `resort_id` |
| F4 | **Uniform tool contract:** every tool returns `as_of`, `basis`, `assumptions`, `data_quality` (e.g. "42 employees have no joining date, excluded") | Answers must be explainable; CNV-14; dirty dates (1970 / 0000-00-00) must be filtered | Only some tools return `basis` today (budget, payroll estimate) |
| F5 | **Explicit `from`/`to` + `group_by` + `department` parameters** on tools that are single-day or resort-wide today | Time comparisons and drill-downs (CNV-10/12) | `get_attendance_summary`, `get_late_arrivals`, `get_occupancy`, `get_visa_liability`, `get_gender_breakdown`, `get_headcount` |
| F6 | **Tenant-safe child-table access:** write tools that join through the parent (`payroll`, `parent_surveys`, `training_schedules`, `performance_cycles`, `manning_responses`) and assert the parent's `resort_id`. **Do not relax `ReadQueryGuard`'s per-table `resort_id` rule** | 146 tables have no `resort_id`; the SQL fallback can't reach payslip lines, survey answers, training enrolments, appraisal detail, monthly plan | `ReadQueryGuard::scopeError` |
| F7 | **Answer budget and composite tools:** raise reply budget (default fallback 140 tokens is too small for executive answers; config is 800) and replace multi-tool chains with composite tools for the Part 4 questions | 5 tool rounds max; LLM chaining 4–6 tools with name-matching between tools is the main hallucination/timeout risk | `OpenRouterClient::MAX_TOOL_ROUNDS = 5` |
| F8 | **De-duplicated compliance view** (one row per employee × rule, latest, severity, age) | 1,432 of 1,440 rows are "Breached" — answers drown in noise | `compliances` |

## 6.2 Tier 1 — expose what already exists (highest value / lowest risk)

| # | Build | Unlocks (examples) | Reuses |
|---|---|---|---|
| T1 | `get_dashboard_insights(module)` — return the cached deterministic `details` + the AI `recommendation` for each card of the 10 insight dashboards | ~40 rows across Leave, Payroll, Visa, Performance, TA, Learning, ER, Survey, Incident, Accommodation (all `[DASH]`) | `build*Insights()` methods + `Common::enrichDashboardInsights`. Respect each dashboard's scoping (Leave/Performance already scope by dept) |
| T2 | `get_staffing_forecast(months, occupancy_override?)` | WFP-F01/F02/F04/F05, TNA-F01, X-29 | `getAiInsights` → `/predict_staff_batch`; say which occupancy basis was used (entered / last year / two years ago) |
| T3 | `get_leave_balances(scope)` resort-wide + liability | LEA-I04, F03, F04, A04, X-04 | `Common::getEmployeeLeaveBalance` (already looped for the dashboard); daily-rate rule from final-settlement code |
| T4 | `get_resignation_analytics(from,to,group_by)` — monthly series, by dept, reason, tenure band | PEO-A02–A05, X-06 | People dashboard + `ResignationExitReportController` separation analysis |
| T5 | `get_overtime_approvals` + OT by department | TNA-I06, A05, A06 | `employee_overtimes`; `Common::syncAttendanceOvertime` untouched |
| T6 | Payroll component tools (OT pay, allowances, service charge, deductions, EWT/pension) via parent `payroll` join; runs filtered Permanent/Casual | PAY-A03, A04, A08, I07, I08, TNA-A04, X-03 | `payroll_reviews`, `payroll_review_allowances`, `payroll_deductions` |
| T7 | `get_visa_forecast(months)` + dept on expiry rows | VIS-F01–F03, A03, X-01 | `visaDocs()`, `work_permits.Due_Date` |
| T8 | Named-list variants: appraisals not done, ER cases overdue, warnings expiring, vacancies past start date, applicants by AI rank, contract/consent pending | PRF-I04, ER-I06/I07, TAC-I04/I07/I08 | existing queries behind the dashboard counts |
| T9 | `get_manning_ai_suggestions(dept, year)` | WFP-I08, R03 | `manning_responses.ai_suggestions` |
| T10 | Compliance filters (rule, department, severity) + stored `remediation_ai` | CMP-I03, A01, R01, R02, LEA-I14, PAY-I09 | `compliances` |

## 6.3 Tier 2 — new calculations on existing data

| # | Calculation | Rows | Notes |
|---|---|---|---|
| C1 | **Headcount at month-end (reconstruction)** from `joining_date` / `last_working_day` / `status` | PEO-A01, A02, F03, WFP-A04, X-12 | Exclude 0000-00-00 and 1970 dates; document the approximation |
| C2 | **Turnover rate** (exits ÷ average headcount), voluntary vs involuntary | PEO-A02, X-06, X-08 | Needs C1 |
| C3 | **Roster-aware coverage** (present ÷ rostered, excluding day-off/approved leave) | TNA-I04, I11, A01, F03, CNV-05 | Payroll already has `payrollRosterContext/DayMarks` — reuse rather than re-derive |
| C4 | **Availability impact of expiring documents / leave / exits** | X-01, X-02, X-19, X-29 | Define the "lost capacity" rule explicitly in the answer |
| C5 | **OT vs budget** with an agreed baseline | X-03, PAY-A10 | **Decision needed:** budget has no OT line |
| C6 | **Leave liability** (days × daily rate) | LEA-F03, X-04 | Follow `final_settlements` formula; `[POLICY]` |
| C7 | **Plan vs actual** (position × month) | WFP-A02, A09, X-22 | `position_monthly_data` vs `employees` |
| C8 | **Forward windows** on every "next N months" question (fees, probation, contracts, increments, loans) | VIS-F01, PEO-F04, PAY-F05 | |
| C9 | **Readiness checks** (payroll, new joiner, exit) by *reusing* the existing validation gates | X-14, X-15, X-28 | Do not duplicate `PayrollController::fetchTimeAttendance` rules by hand |
| C10 | Normalisation (per 100 staff, per 1,000 hours, per occupied room) | ER-A01, INC-A03, X-10 | |

## 6.4 Tier 3 — needs a model, new data capture, or a business decision

| # | Item | Why it is not just a tool |
|---|---|---|
| M1 | **Attrition-risk scoring** (PEO-F02, R02, CNV-09) | No labelled history locally (6 resignations); needs production volume check; explainability, HR-only, never auto-action; exclude anonymous survey data and confidential complainants |
| M2 | **Seasonality models** for resignations, incidents, absence, service charge (PEO-F08, INC-F02, TNA-F05, PAY-F07) | Needs ≥ 24 months; attendance history is thin in samples |
| M3 | **Punch-pattern anomaly detection** (TNA-A10) | Definitions and false-positive policy |
| M4 | **Capture `contract_end_date`, `dob`, `reporting_to`, `benefit_grid_level`** (PEO-F05, F06, A11, X-32) | Fields exist, unpopulated in sample data — a data-entry/import problem, not a code problem |
| M5 | **Per-department staffing norms** (X-07, WFP-R02) | No standard of "required staff per occupied room per department" exists |
| M6 | **Training due ledger / program cost / session capacity** (LRN-F01, A04, R02) | No per-employee next-due date; no cost or capacity field |
| M7 | **Cost-per-hire components, accommodation cost, penalty amounts** (TAC-A08, X-08, X-33, VIS-R04) | Not stored |
| M8 | **Occupancy discipline:** HR must enter forward occupancy; `occupancy_thresholds` tables are empty | Forecast quality depends on it; the model silently falls back to last year |
| M9 | **Business-rule library for recommendations** (thresholds like "turnover > X %", "OT > Y h/head") signed off by HR/GM | The 33 existing recommendation texts are LLM-written from numbers; rule thresholds are not stored anywhere (T&A uses 48 h / 3 h OT / 5 h break / 4 day-offs; Leave uses an occupancy low/moderate/high split; Visa uses 30/60/90 days) |

## 6.5 Decisions the business owners must make (not engineering)

1. Open aggregate-only cost views to **GM/CEO**? (currently blocked by founder decision, 2026-11-02)
2. Create a **Finance** tier — which modules, which people data?
3. What may **HOD / EXCOM / MGR** ask — own department only, own direct reports, or aggregate resort KPIs?
4. Medical/clinic data in chat — allowed at aggregate level at all?
5. Individual-level **attrition or burnout** flags — wanted? who sees them?
6. Overtime budget baseline (X-03) and the "excessive leave balance" threshold (X-04).
7. Whether chat may deep-link into report exports (CNV-19).

---

# PART 7 — APPENDICES

## Appendix A — Existing tool inventory (generated from `WisdomTools.php`)

94 distinct tools (GM loses the 8 compensation tools and the 3 SQL tools; the policy tier gets only the first two platform tools).

| # | Tool | What it returns | Tier |
|---|---|---|---|
| 1 | `search_policy_documents` | Search this resort's own company policy documents (the staff handbook and policies HR uploaded to the File Management \"Company Po | all tiers |
| 2 | `get_module_guide` | How to do something in Wisdom AI (e.g. | all tiers |
| 3 | `get_headcount` | Get the number of active employees at the resort. | HR + GM |
| 4 | `get_department_breakdown` | Get the count of active employees grouped by department. | HR + GM |
| 5 | `count_employees_by_status` | Get counts of employees grouped by their employment status (Active, Resigned, etc.). | HR + GM |
| 6 | `get_employees_on_leave` | List employees who are/were on approved leave. | HR + GM |
| 7 | `get_public_holidays` | Public holidays for the resort — official non-working days. | HR + GM |
| 8 | `get_pending_leave_approvals` | Leave requests that are still awaiting approval (employee, leave type, dates, days, status). | HR + GM |
| 9 | `get_leave_balance` | Leave balance for ONE employee by name, from their Benefit Grid: grade/rank, then per eligible leave type the allocated days (pror | HR + GM |
| 10 | `get_boarding_pass_requests` | Boarding pass / island pass / travel pass requests (employee, departure & arrival dates, status). | HR + GM |
| 11 | `list_employees` | List active employees with their names, department, position and nationality. | HR + GM |
| 12 | `get_nationality_breakdown` | Count active employees grouped by nationality, including a local (Maldivian) vs foreign split. | HR + GM |
| 13 | `find_employee` | Look up an employee by name, employee ID, passport number (reverse lookup — "whose passport is X"), OR job/position title (e.g. | HR + GM |
| 14 | `get_recruitment_pipeline` | Get a summary of the recruitment pipeline: vacancies grouped by status and applicants grouped by status. | HR + GM |
| 15 | `get_vacancy_applicants` | Vacancies with how many applicants each has received (highest first), plus the position, department, required headcount and status | HR + GM |
| 16 | `get_scheduled_interviews` | Scheduled applicant interviews: applicant, date, time and status. | HR + GM |
| 17 | `get_attendance_summary` | Get an attendance summary for a given date (defaults to today): how many employees checked in vs. | HR + GM |
| 18 | `get_employee_attendance` | Attendance for ONE specific employee. | HR + GM |
| 19 | `get_attendance_register` | Who was present / absent on a date (defaults today) across the whole resort. | HR + GM |
| 20 | `get_late_arrivals` | Employees who punched in AFTER their shift start time on a date (defaults today), with how many minutes late. | HR + GM |
| 21 | `get_overtime` | Overtime hours by employee over a period, highest first. | HR + GM |
| 22 | `get_duty_roster` | The duty roster (who is scheduled, and on which shift) for a date, or — with off=true — who has a day off / is not scheduled. | HR + GM |
| 23 | `get_understaffed_today` | Departments that are short-handed on a date (defaults today): active headcount vs how many are actually present, with the absent c | HR + GM |
| 24 | `get_upcoming_birthdays` | List active employees whose birthday falls in a given month (defaults to the current month). | HR + GM |
| 25 | `get_active_sos` | List active / ongoing SOS emergency incidents at the resort (those not yet resolved, rejected or closed): emergency type, who rais | HR + GM |
| 26 | `get_accommodation_summary` | Staff accommodation capacity & occupancy: total rooms, total beds (capacity), occupied beds, available (free) beds and occupancy r | HR + GM |
| 27 | `get_available_beds` | List the rooms that have FREE beds, with building, room, capacity and free-bed count; optionally filtered by gender. | HR + GM |
| 28 | `get_maintenance_requests` | Accommodation maintenance requests with room, priority, status, issue and date. | HR + GM |
| 29 | `get_employee_accommodation` | Where a specific employee is staying (building, room, bed) and who their roommates are. | HR + GM |
| 30 | `get_building_occupancy` | Occupancy per accommodation building (beds, occupied, available, occupancy %), highest first. | HR + GM |
| 31 | `get_budgeted_headcount` | Workforce Planning: approved/budgeted headcount vs filled vs vacant seats for a year, from the manning plan. | HR + GM |
| 32 | `get_vacancy_analysis` | Workforce Planning: vacant positions for a year with fill rate, grouped by "department" (default), "position" or "total". | HR + GM |
| 33 | `get_gender_breakdown` | Count active employees by gender (male/female), optionally filtered by department, with the female ratio. | HR + GM |
| 34 | `get_manning_status` | Workforce Planning: status of departmental manning / budget requests — counts by status and the latest status per department (Genr | HR + GM |
| 35 | `get_occupancy` | Resort room occupancy: the latest recorded occupancy (or as of a given date) — occupancy percentage, total rooms and occupied room | HR + GM |
| 36 | `get_workforce_compliance` | Workforce compliance check. | HR + GM |
| 37 | `get_compliance_issues` | Flagged compliance breaches from the general HR compliance directory (minimum wage, overtime eligibility, salary outliers, etc.),  | HR + GM |
| 38 | `get_workforce_status_summary` | A single "today\'s workforce status" snapshot: total active headcount, employees on leave today, employees on probation, open vaca | HR + GM |
| 39 | `get_performance_summary` | Performance Management dashboard summary: pending appraisals, employees on PIP, employees on PDP, monthly check-in status, active  | HR + GM |
| 40 | `get_pip_overview` | Employees on a Performance Improvement Plan (PIP): count and list (name, department, duration, status). | HR + GM |
| 41 | `get_pdp_overview` | Employees on a Professional Development Plan (PDP): count and list (name, department, duration, status). | HR + GM |
| 42 | `get_appraisal_status` | Appraisal completion across active performance cycles: total appraisals, completed, manager-review pending, self-review pending, a | HR + GM |
| 43 | `get_performance_cycles` | List performance (appraisal) cycles with status (Active/Pending/Closed) and dates. | HR + GM |
| 44 | `get_kpi_overview` | KPI overview: total KPIs and counts grouped by workflow status (pending, responded, approved, rejected). | HR + GM |
| 45 | `get_kpi_details` | Detailed KPI list: each goal name, its target budget, weightage and approval status. | HR + GM |
| 46 | `get_monthly_checkins` | Monthly check-in status: totals grouped by check-in status (Pending/Conducted/Confirm/Rescheduled) and by approval status (pending | HR + GM |
| 47 | `get_performance_meetings` | Upcoming performance meetings from a date (defaults to today): title, date, time, location, participant count. | HR + GM |
| 48 | `get_employee_performance` | Performance snapshot for one employee by name: their PIP, PDP, latest appraisal (self/manager review status) and latest monthly ch | HR + GM |
| 49 | `get_visa_summary` | Immigration/visa dashboard summary: expat employee count, employees with work permits, documents expiring in 30 days, unpaid work- | HR + GM |
| 50 | `get_visa_expiries` | Visa/immigration documents (visa, work permit, insurance, medical, slot fee) by expiry status, for active expats. | HR + GM |
| 51 | `get_visa_liability` | Immigration liability (money due) including overdue, up to the end of a period. | HR + GM |
| 52 | `get_visa_wallet` | Immigration wallet status for the resort: Available, Reserved, Deposited and Withdrawn amounts. | HR + GM |
| 53 | `get_visa_payment_requests` | Visa payment requests with their status (default Pending). | HR + GM |
| 54 | `get_deposit_refunds` | Pending visa deposit-refund requests: employees (resigned, HR-approved) whose security deposit has not yet been refunded. | HR + GM |
| 55 | `get_employee_immigration` | Full immigration profile for one employee by name: visa, work permit, insurance, medical and slot-payment expiry dates with days-l | HR + GM |
| 56 | `get_employee_relations_summary` | Employee Relations dashboard: open / pending / under-review / resolved counts for both grievance and disciplinary cases, plus case | HR + GM |
| 57 | `get_disciplinary_cases` | Disciplinary cases: count and list (employee, department, category, action taken, priority, status, date), with by-department and  | HR + GM |
| 58 | `get_grievance_cases` | Grievance cases: count and list (employee, department, category, priority, status, confidential flag, date), with by-department an | HR + GM |
| 59 | `get_disciplinary_outcomes` | Disciplinary outcomes: counts of cases grouped by the action taken (e.g. | HR + GM |
| 60 | `get_repeat_offenders` | Employees with more than one disciplinary case, with each one\'s case count (highest first). | HR + GM |
| 61 | `get_confidential_cases` | Confidential grievance cases (where the complainant chose NOT to disclose their identity): category, status and date. | HR + GM |
| 62 | `get_employee_relations_history` | Grievance and disciplinary history for one employee by name: their disciplinary cases (category, action, status) and grievances fi | HR + GM |
| 63 | `get_learning_summary` | Learning & Development dashboard: total training programs, mandatory programs, scheduled sessions (ongoing / upcoming / completed  | HR + GM |
| 64 | `get_training_programs` | Training program library: list (name, category, delivery mode, frequency, hours) with by-category and by-delivery-mode breakdowns. | HR + GM |
| 65 | `get_training_schedule` | Scheduled / upcoming training sessions from a date (defaults to today): program, dates, venue, date-derived status (Scheduled/Ongo | HR + GM |
| 66 | `get_training_attendance` | Training attendance summary across the resort: counts by status (Present/Absent/Late/Pending) and the overall attendance rate. | HR + GM |
| 67 | `get_mandatory_training` | Mandatory / compulsory training programs configured for the resort: count and the program names. | HR + GM |
| 68 | `get_employee_training` | Training history for one employee by name: the programs they were enrolled in with attendance status and date. | HR + GM |
| 69 | `get_incident_summary` | Incident dashboard: total / open / resolved counts, breakdowns by status, severity, category and location, plus incidents this mon | HR + GM |
| 70 | `get_incidents` | Incident cases: count and list (name, category, severity, priority, status, location, date, reporter) with by-status / by-severity | HR + GM |
| 71 | `get_incident_investigations` | Incidents currently under investigation, with investigation start date, expected resolution date, findings and an "overdue" flag ( | HR + GM |
| 72 | `get_committee_workload` | Incident-investigation workload per committee member: how many cases have been delegated to each member (from investigations they  | HR + GM |
| 73 | `get_employee_incidents` | Incidents involving one employee by name (as reporter, victim or involved party): name, category, severity, status, date and their | HR + GM |
| 74 | `get_survey_summary` | Survey dashboard: total, draft, active (published/ongoing), completed and expired survey counts, plus total recipients, responses  | HR + GM |
| 75 | `get_surveys` | List surveys with status (Draft/Active/Complete/Expired derived from dates), start/end dates, recipients, responses and per-survey | HR + GM |
| 76 | `get_survey_participation` | Participation detail. | HR + GM |
| 77 | `get_employee_documents` | List the documents on file for one employee by name: document title, category, expiry date and whether a file is attached. | HR + GM |
| 78 | `search_documents` | Search employee documents across the resort by keyword in the title or category (e.g. | HR + GM |
| 79 | `get_probation_overview` | Probation overview: how many employees are currently on probation, by probation status (Active/Extended/Confirmed/Failed), and how | HR + GM |
| 80 | `get_promotions` | Employee promotions: count and list (employee, from → to position, effective date, status) plus total monthly payroll impact of ap | HR + GM |
| 81 | `get_transfers` | Employee transfers: count and list (employee, from → to department, type Permanent/Temporary, effective date, status). | HR + GM |
| 82 | `get_resignations` | Employee resignations: count and list (employee, reason, resignation date, last working day, status), plus resignations this month | HR + GM |
| 83 | `get_salary_increments` | Salary increments (people module workflow): count and list (employee, previous → new salary, increment amount, status, effective d | HR only (compensation) |
| 84 | `get_employee_loans` | Salary advances / employee loans: list (employee, type, amount, approval status, outstanding balance). | HR only (compensation) |
| 85 | `get_pending_approvals` | Centralized pending-approvals summary: counts of pending promotions, transfers, resignations, salary increments and salary advance | HR + GM |
| 86 | `get_payroll_summary` | Payroll dashboard figures: the last processed/locked payroll (period, status, total paid) AND the upcoming payroll for the current | HR only (compensation) |
| 87 | `get_employee_salary` | Get the basic salary / compensation for a specific employee by name. | HR only (compensation) |
| 88 | `get_workforce_budget` | Resort annual workforce budget for a year (USD), using the exact same engine as the official View Budget page (staff salaries + bu | HR only (compensation) |
| 89 | `get_employee_cost` | Annual budgeted cost for ONE employee (USD) for a year, with the monthly average — salary + budgeted cost components + allowances, | HR only (compensation) |
| 90 | `get_payroll_by_department` | Current monthly payroll (sum of active employees\' basic salary) grouped by department, highest first. | HR only (compensation) |
| 91 | `get_payroll_trend` | Payroll totals for the last several pay periods (period, status, total employees, total payroll), most recent first — for month-to | HR only (compensation) |
| 92 | `list_tables` | Discover which database tables exist before writing a custom query. | HR only (SQL) |
| 93 | `describe_table` | Show the column names of a specific table so you can write a correct query. | HR only (SQL) |
| 94 | `run_read_query` | Run a custom READ-ONLY MySQL SELECT for questions the dedicated tools don't cover (budget, accommodation, overtime, etc.). | HR only (SQL) |

## Appendix B — Data readiness (local sample snapshot — re-check on production)

| Table | Rows (sample) | Tenant column | Usable for | Caveat |
|---|---|---|---|---|
| `employees` | 574 (5 resorts) | `resort_id` | everything | `dob`, `reporting_to`, `basic_salary`, `contract_end_date`, `benefit_grid_level` empty for whole resorts; some `joining_date` = 0000-00-00 |
| `parent_attendaces` | 9,540 | `resort_id` | attendance, OT hours | min date 1970-01-01; one resort has a single month |
| `duty_rosters` / `duty_roster_entries` | 419 / 9,115 | `resort_id` | roster plan | sparse |
| `employees_leaves` | 486 | `resort_id` | leave | only Pending/Approved in sample |
| `employees_leaves_status` | — | none | approval timing | SQL ✗ |
| `employee_overtimes` | 65 | `resort_id` | OT approvals | — |
| `payroll` | 18 (1 locked) | `resort_id` | payroll history | thin history; Permanent vs Casual |
| `payroll_reviews` / `_deductions` / `_review_allowances` | 45 / 52 / 225 | **none** | payroll cost detail | need parent join |
| `employee_resignation` | 6 | `resort_id` | exits | 4 of 6 withdrawn — no attrition baseline |
| `people_salary_increment` / `employee_promotions` / `employee_transfers` | 11 / 6 / 5 | `resort_id` | changes | small |
| `manning_responses` / `position_monthly_data` / `store_manning_response_*` | 12 / 600 / 13+120 | `resort_id` / **none** / varies | plan | AI suggestions stored as JSON |
| `occuplanies` | 73 | `resort_id` | occupancy | one sample resort has none; thresholds tables empty |
| `vacancies` / `applicant_form_data` / `applicant_wise_statuses` / `applicant_offer_contracts` | 23 / 19 / 30 / 10 | `Resort_id` / `resort_id` / **none** / `resort_id` | TA | small |
| `work_permits` / `quota_slot_renewals` / `visa_renewals` / `employee_insurances` | 124 / 167 / 23 / 18 | `resort_id` | visa fees & expiry | 94 of 124 permits "Unpaid" |
| `visa_employee_expiry_data` | 38 | `resort_id` | AI-extracted expiry | OCR/LLM quality |
| `disciplinary_submits` / `grivance_submission_models` / `incidents` | 7 / 13 / 11 | `resort_id` | ER / incidents | small |
| `performa_child_cycles` / `employee_pip_plans` / `employee_pdp_plans` | 32 / 2 / 1 | **none** / `resort_id` | performance | rating inside JSON |
| `training_participants` / `training_attendance` | 12 / 5 | **none** / none | learning | small |
| `survey_employees` / `survey_results` | 253 / 44 | **none** | surveys | anonymity |
| `assing_accommodations` / `maintanace_requests` | 29 / 19 | `resort_id` | accommodation | — |
| `employee_travel_passes` | 53 | `resort_id` | island pass | — |
| `compliances` | 1,440 | `resort_id` | compliance | 99 % "Breached" |
| `wisdom_usage` | 16 | `resort_id` | chat usage/cost | — |

## Appendix C — Role & permission matrix (as built)

| Role (rank) | Chat access | Database tools | Payroll/compensation | SQL fallback | Policy & how-to | Scope today |
|---|---|---|---|---|---|---|
| HR Director/Manager (3), HR-dept HOD/EXCOM, super/master admin | FULL | all | yes | yes | yes | whole resort |
| General Manager (8) | GM | all except payroll set + min-wage view | **no** | **no** | yes | whole resort |
| HOD (2) / EXCOM (1) / Manager (4), non-HR dept | POLICY | none | no | no | yes | n/a |
| Finance (7) | none (chat hidden, 403) | — | — | — | — | — |
| Supervisor (5), Line worker (6), Clinic (12) | none | — | — | — | — | — |

Data-access invariants to carry into any new tier: every query carries `resort_id`; non-HR/GM HOD/EXCOM only ever see their own department (`Common::hasFullDataAccess` + `getScopedDepartmentIds`); compensation = FULL (and any future Finance) only; confidential grievance identity withheld; anonymous surveys never de-anonymised; free-text fields from tools are untrusted content (prompt-injection rule already in the system prompt).

## Appendix D — What was verified, and what was not

**Verified against the running code/DB (read-only):** the tool list, tier gates, SQL guard rules, every table name and column list quoted in this document, row counts and value distributions, the dashboard insight builders and their card keys, the staffing-forecast call path, the budget engine components, the payroll validation gates, the leave-balance engine, the exit status flow, the existing compliance rule names.

**Not verified / outside this repository:**
- The Python AI service (`HRVMS-Wisdom-AI-Code`: `/dashboard_insights`, `/predict_staff_batch`, `/budget_recommendations`, `/compliance_scan`, `/compliance_explain`, `/ta_insights`, `/score_resume`, `/policies/*`) is not in this repo — endpoint behaviour is taken from the PHP callers only. Model type and training data for `/predict_staff_batch` are unknown.
- Production data volumes and field completeness (the numbers above are a small demo snapshot).
- LLM answer quality; this document defines *what to ask and what must exist*, not tested prompts.
- Recommendation business rules beyond the thresholds quoted in 6.4/M9 — none are stored in code or config.

*No code, schema or data was modified in producing this document.*
