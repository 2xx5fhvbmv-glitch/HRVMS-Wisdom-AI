@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <style>
        #ta-talentpool-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ta-talentpool-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding" id="ta-talentpool-hero">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tpx">
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        @if(!in_array($rank, [2, 7]))
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" name="Department" id="ResortDepartment">
                                <option selected disabled>Select Department</option>
                                @if($ResortDepartment->isNotEmpty())
                                    @foreach ($ResortDepartment as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}">{{ $item->name }}</option>

                                    @endforeach

                                @endif
                            </select>
                            <div class="dd" data-target="#ResortDepartment">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Department</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($ResortDepartment->isNotEmpty())
                                            @foreach ($ResortDepartment as $item)
                                            <div class="dd-item" role="option" data-value="{{ $item->id }}"><span class="dd-nm">{{ $item->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @elseif($rank == 2)
                        <input type="hidden" id="ResortDepartment" value="{{ $employeeDeptId }}">
                        @endif
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select Positions" name="Positions" id="talentPoolPositions">
                                <option selected disabled>Select Positions</option>
                           </select>
                           <div class="dd" data-target="#talentPoolPositions">
                               <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                   <span class="dd-lbl">Select Positions</span>
                                   <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                               </button>
                               <div class="dd-panel" role="listbox" aria-label="Position">
                                   <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                   <div class="dd-scroll">
                                       <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                   </div>
                               </div>
                           </div>
                        </div>
                        {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control" placeholder="18/10/2024">
                        </div> --}}
                        <div class="col-auto ms-auto">
                            <a href="javascript:void(0)" class="btn btn-grid active"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="javascript:void(0)" class="btn btn-list"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
                    <div class="table-responsive">
                        <table class="tbl TalentPool">
                            <thead>
                                <tr>
                                    <th style="width:26px"></th>
                                    <th>Applicant</th>
                                    <th>Position</th>
                                    <th>Applied</th>
                                    <th>WAI Rank</th>
                                    <th>Availability</th>
                                    <th>Consent expiry</th>
                                    <th style="text-align:right">Actions</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main">
                    <div class="tpx-gridhost" id="grid_main_view">

                    </div>

                </div>

            </div>

            {{-- Frosted kebab menu + rejection tooltip (inside #tpx but outside the table, so its scroll area never clips them) --}}
            <div class="kmenu" id="tpxMenu" role="menu">
                <div class="kmi userApplicants-btn" role="menuitem" data-m="profile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View profile</div>
                <div class="kmsep" data-m="sep"></div>
                <div class="kmi RejactionReason" role="menuitem" data-m="reason"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>View rejection reason</div>
                <div class="kmsep" data-m="sep2"></div>
                <div class="kmi del destoryApplicant" role="menuitem" data-m="delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14"/></svg>Delete</div>
            </div>
            <div class="rtip" id="tpxTip"></div>
            </div>


        </div>
    </div>

    <div class="userApplicants-wrapper">
    </div>

    {{-- Rejection Reason Modal --}}
    <div class="modal fade" id="Response-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejection Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='RevertResponeForm'>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="hidden" name="applicant_status_id" id="applicant_status_id">
                            <textarea class="form-control" readonly disabled id="Reason" name="Reason" placeholder="Reason"></textarea>
                        </div>
                        <input type="hidden" name="Interview_id" id="Interview_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-secondary RevertBack d-none">Revert Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Consent Request Modal --}}
    <div class="modal fade" id="consentRequest-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Consent Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="consentRequestForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Data Retention Expiry Date</label>
                            <input type="text" class="form-control" name="consent_expiry_date" id="consent_expiry_date" placeholder="DD/MM/YYYY" required readonly>
                        </div>
                        <p class="text-muted small">An email will be sent to the applicant requesting their consent to retain their profile data until the selected date.</p>
                        <input type="hidden" name="applicant_id" id="consent_applicant_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-attention">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Check Availability Modal --}}
    <div class="modal fade" id="checkAvailability-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check Availability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="checkAvailabilityForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Email Template</label>
                            <select class="form-control dd-native-select" name="email_template_id" id="checkAvailEmailTemplate" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#checkAvailEmailTemplate">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Email Template</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Email Template">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if(isset($EmailTamplete))
                                        @foreach ($EmailTamplete as $e)
                                        <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting Link (Optional)</label>
                            <input type="text" class="form-control" name="meeting_link" placeholder="Enter Meeting Link">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Job Link (Optional)</label>
                            <input type="text" class="form-control" name="job_link" placeholder="Enter Job Posting Link">
                        </div>
                        <input type="hidden" name="applicant_id" id="availability_applicant_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-attention">Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- File Viewer Modal --}}
    @include('partials._file_view_modal', ['cancelId' => 'document-dismiss'])

@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
<style>
#tpx{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9; --g4:#C7CDCF;
  --line:#E2EBEC; --line-2:#EEF4F4; --bg:#EEF2F2; --card:#fff;
  --ok:#1F9D6B; --ok-bg:#E7F4EE; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --violet:#6B5FC7; --violet-bg:#EEE9FB; --info:#1E7A85; --info-bg:#E2F0F2; --lime:#E0FF02;
  --shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.06);
  --spring:cubic-bezier(.34,1.56,.64,1);
  --font:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
#tpx .pill{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
#tpx .pill.teal{background:var(--teal-soft);color:var(--teal)}
#tpx .pill.ok{background:var(--ok-bg);color:var(--ok)}
#tpx .pill.warn{background:var(--warn-bg);color:var(--warn)}
#tpx .pill.err{background:var(--err-bg);color:var(--err)}
#tpx .pill.muted{background:var(--line-2);color:var(--g2)}
#tpx .tpx-cs{font-size:11.5px;font-weight:600}
#tpx .tpx-cs.ok{color:var(--ok)}
#tpx .tpx-cs.warn{color:var(--warn)}
#tpx .tpx-cs.err{color:var(--err)}
#tpx .tpx-cs.muted{color:var(--faint)}
#tpx .statuscell{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
#tpx .rejchip{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:var(--err-bg);color:var(--err);cursor:default;white-space:nowrap}
#tpx .rejchip svg{width:12px;height:12px}
#tpx .rtip{position:fixed;max-width:232px;background:rgba(20,35,42,.93);-webkit-backdrop-filter:blur(10px) saturate(140%);backdrop-filter:blur(10px) saturate(140%);color:#fff;padding:10px 12px;border-radius:10px;z-index:140;display:none;box-shadow:0 10px 30px rgba(1,20,25,.3)}
#tpx .rtip.is-on{display:block}
#tpx .rtip .rt-h{font-size:11.5px;font-weight:600}
#tpx .rtip .rt-r{font-size:11.5px;color:rgba(255,255,255,.82);margin-top:3px;line-height:1.5}
#tpx .rtip .rt-d{font-size:10.5px;color:rgba(255,255,255,.55);margin-top:5px}
#tpx .tpx-av{flex:none;border-radius:50%;background:#E1EBEC;color:var(--teal);font-weight:600;display:grid;place-items:center;overflow:hidden;border:2px solid #fff;box-shadow:0 0 0 1.5px var(--teal-3)}
#tpx .tpx-av img{width:100%;height:100%;object-fit:cover}
#tpx .metric .ml{font-size:9.5px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
#tpx .metric .mv{font-size:16px;font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums;line-height:1.1}
#tpx .metric .mv small{font-size:10px;font-weight:500;color:var(--faint)}
#tpx .metric .bar{height:4px;border-radius:3px;background:var(--line);margin-top:6px;overflow:hidden}
#tpx .metric .bar i{display:block;height:100%;border-radius:3px}
#tpx .metric.wai .bar i{background:linear-gradient(90deg,#9db800,#dff23f)}
#tpx .metric.score .bar i{background:var(--teal)}
#tpx .metric.wai .ml{color:var(--teal)}
#tpx .tpx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(262px,1fr));gap:16px}
#tpx .tp-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:16px;cursor:pointer;transition:box-shadow .18s,transform .18s var(--spring)}
#tpx .tp-card:hover{box-shadow:0 4px 10px rgba(1,70,83,.07),0 16px 36px rgba(1,70,83,.09);transform:translateY(-2px)}
#tpx .tp-top{display:flex;flex-direction:column;align-items:center;text-align:center;gap:2px;padding-top:4px}
#tpx .tp-top .tpx-av{width:64px;height:64px;font-size:19px;margin-bottom:7px}
#tpx .tp-top .nm{font-size:15px;font-weight:600;color:var(--ink);line-height:1.25}
#tpx .tp-top .ro{font-size:12.5px;color:var(--muted);margin-top:1px}
#tpx .kebab{flex:none;background:none;border:none;color:var(--faint);width:28px;height:28px;border-radius:7px;display:grid;place-items:center}
#tpx .kebab:hover{background:var(--line-2);color:var(--g1)}
#tpx .tp-card .kebab{position:absolute;top:12px;right:12px}
#tpx .tp-status{margin-top:10px;display:flex;gap:7px;flex-wrap:wrap;justify-content:center}
#tpx .tp-metrics{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
#tpx .tp-metrics .metric{background:var(--teal-soft);border-radius:10px;padding:9px 11px}
#tpx .tp-info{margin-top:14px;display:flex;flex-direction:column;gap:7px}
#tpx .inf{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12.5px}
#tpx .inf .k{color:var(--muted);flex:none}
#tpx .inf .v{color:var(--g1);font-weight:500;text-align:right;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#tpx .docs{display:flex;gap:6px}
#tpx .tpx-doc{display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--teal);background:var(--teal-soft);border-radius:7px;padding:3px 8px;text-decoration:none;font-weight:500}
#tpx .tpx-doc:hover{background:var(--teal-3)}
#tpx .tpx-doc svg{width:11px;height:11px}
#tpx .tp-actions{display:flex;gap:8px;margin-top:14px;padding-top:13px;border-top:1px solid var(--line-2)}
#tpx .tpx-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:12.5px;font-weight:600;border-radius:10px;padding:9px 12px;border:1px solid transparent;transition:background .15s,border-color .15s,transform .15s var(--spring)}
#tpx .tpx-btn:active{transform:scale(.97)}
#tpx .tpx-btn.ghost{background:#fff;color:var(--teal);border-color:var(--line);flex:1;white-space:nowrap}
#tpx .tpx-btn.ghost:hover{border-color:var(--teal);background:var(--teal-soft)}
#tpx .scrollwrap{border:1px solid var(--line);border-radius:14px;overflow:auto}
#tpx .tbl{border-collapse:separate;border-spacing:0;width:100%;font-size:12.5px;min-width:900px}
#tpx .tbl th,#tpx .tbl td{padding:12px 14px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--line-2);vertical-align:middle}
#tpx .tbl thead th{position:sticky;top:0;background:var(--teal-soft);z-index:2;font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
#tpx .tbl tbody tr.main:hover td{background:#fafcfc}
#tpx .tbl tbody tr.main{cursor:pointer}
#tpx .appcell{display:flex;align-items:center;gap:11px;min-width:0}
#tpx .appcell .tpx-av{width:36px;height:36px;font-size:11px}
#tpx .appcell .nm{font-size:13.5px;font-weight:500;color:var(--ink)}
#tpx .appcell .sub{font-size:11px;color:var(--muted);margin-top:1px}
#tpx .poscell .p{font-size:13px;color:var(--g1)}
#tpx .poscell .d{font-size:11.5px;color:var(--muted)}
#tpx .wrank{display:flex;align-items:center;gap:9px}
#tpx .wrank .rv{font-size:13px;font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums}
#tpx .wrank .rb{width:46px;height:4px;border-radius:3px;background:var(--line);overflow:hidden}
#tpx .wrank .rb i{display:block;height:100%;border-radius:3px;background:linear-gradient(90deg,#9db800,#dff23f)}
#tpx .actcell{display:flex;align-items:center;gap:6px;justify-content:flex-end}
#tpx .iact{width:30px;height:30px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--teal);display:grid;place-items:center}
#tpx .iact:hover{border-color:var(--teal);background:var(--teal-soft)}
#tpx .iact svg{width:14px;height:14px}
#tpx .chev{transition:transform .2s}
#tpx tr.main.open .chev{transform:rotate(90deg)}
#tpx .tp-detail td{background:#fafcfc;border-bottom:1px solid var(--line-2)}
#tpx .detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px 26px;padding:4px 2px}
#tpx .detail-grid .d-k{font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--faint)}
#tpx .detail-grid .d-v{font-size:13px;color:var(--g1);margin-top:3px}
#tpx .kmenu{position:fixed;width:196px;border-radius:14px;padding:6px;z-index:100;display:none;
  background:rgba(255,255,255,.9);-webkit-backdrop-filter:blur(20px) saturate(150%);backdrop-filter:blur(20px) saturate(150%);
  border:1px solid var(--line);box-shadow:0 2px 6px rgba(1,70,83,.08),0 18px 40px rgba(1,70,83,.16)}
#tpx .kmenu.is-on{display:block;animation:kpop .14s ease}
@keyframes kpop{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
#tpx .kmi{display:flex;align-items:center;gap:11px;padding:10px 11px;border-radius:9px;font-size:13px;font-weight:500;color:var(--teal);cursor:pointer}
#tpx .kmi svg{width:16px;height:16px;flex:none}
#tpx .kmi:hover{background:var(--teal-soft)}
#tpx .kmi.del{color:var(--err)}
#tpx .kmi.del:hover{background:var(--err-bg)}
#tpx .kmsep{height:1px;background:var(--line-2);margin:3px 8px}
@media (prefers-reduced-transparency:reduce){#tpx .kmenu,#tpx .rtip{background:#fff;-webkit-backdrop-filter:none;backdrop-filter:none}
#tpx .rtip{background:#14232A}}
#tpx{font-family:var(--font)}
#tpx .tpx-gridhost{padding:4px 16px 8px}
#tpx .tpx-empty{color:var(--muted);padding:30px;text-align:center;font-size:13px}
#tpx .tp-card .tp-actions .tpx-btn{text-decoration:none}
#tpx .tpx-btn.ghost{flex:1;white-space:nowrap}
#tpx .tp-top .nm.userApplicants-btn:hover{color:var(--teal)}
#tpx .tpx-doc{text-decoration:none}
#tpx .tbl{min-width:900px;margin:0}
#tpx .tbl thead th .dt-column-title{white-space:nowrap}
#tpx .tbl tbody tr.tp-main{cursor:pointer}
#tpx .tbl tbody tr.tp-main:hover td{background:#fafcfc}
#tpx .tbl td.tpx-chevcell{width:26px;padding-right:0}
#tpx .tbl tbody tr.tp-main.dt-hasChild .chev{transform:rotate(90deg)}
#tpx .tbl td.tpx-actcell{text-align:right}
#tpx .tbl .tp-detail td{background:#fafcfc}
/* single sort indicator: hidden at rest, teal on the active column */
#tpx .tbl thead th.dt-orderable-asc span.dt-column-order:before,
#tpx .tbl thead th.dt-orderable-asc span.dt-column-order:after,
#tpx .tbl thead th.dt-orderable-desc span.dt-column-order:before,
#tpx .tbl thead th.dt-orderable-desc span.dt-column-order:after{opacity:0}
#tpx .tbl thead th.dt-ordering-asc span.dt-column-order:before,
#tpx .tbl thead th.dt-ordering-desc span.dt-column-order:after{opacity:1;color:var(--teal);font-size:.7em}
#tpx .tbl thead th.dt-ordering-asc span.dt-column-order:after,
#tpx .tbl thead th.dt-ordering-desc span.dt-column-order:before{display:none}
@media (prefers-reduced-motion:reduce){#tpx *{transition:none!important;animation:none!important}}

</style>
@endsection

@section('import-scripts')
<script>
    function escHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    $(document).ready(function () {

        flatpickr('#consent_expiry_date', {
            dateFormat: 'd/m/Y',
            minDate: 'today',
            allowInput: true,
            appendTo: document.body
        });

        // Was firing a full grid-HTML AJAX reload on every keystroke — typing
        // a name re-requested and re-rendered the entire grid per character,
        // which is what made "Grid view takes too much time to load" (each
        // keystroke restarts the wait, and a slow keystroke's response can
        // land after a later one's, showing stale results briefly). Debounce
        // so it only fires once typing pauses.
        let searchDebounce;
        $('.search').on('keyup', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function() {
                let girdview = $(".btn-grid").hasClass('active');

                if(girdview)
                {
                    DatatableGrid();

                }
                else
                {
                    DatatableList();
                }
            }, 350);
        });
        @if($rank == 2 && $employeeDeptId)
        // Auto-load positions for HOD's department
        $.ajax({
            url: "{{ route('resort.get.position') }}",
            type: "post",
            data: { deptId: {{ $employeeDeptId }}, "_token": "{{ csrf_token() }}" },
            success: function(data) {
                if(data.success == true) {
                    let string = '<option selected disabled>Select Positions</option>';
                    $.each(data.data, function(key, value) {
                        string += '<option value="'+value.id+'">'+value.position_title+'</option>';
                    });
                    $(".Positions").html(string);
                    window.wisdomDD.rebuild('#talentPoolPositions');
                }
            }
        });
        @endif

        $(document).on("change", ".Positions", function() {
            let girdview = $(".btn-grid").hasClass('active');

                    if(girdview)
                    {
                        DatatableGrid();

                    }
                    else
                    {
                        DatatableList();
                    }
     });
        $(document).on('change', '#ResortDepartment', function() {
                var deptId = $(this).val();

                let currentDepartment = $(this).val();
                let isDuplicate = false;

                let string='<option selected disabled>Select Positions</option>';
                $(".Positions").html(string);
                window.wisdomDD.rebuild('#talentPoolPositions');
                    $.ajax({
                        url: "{{ route('resort.get.position') }}",
                        type: "post",
                        data: {
                            deptId: deptId,
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(data) {
                            if(data.success == true)
                            {
                                $.each(data.data, function(key, value) {
                                    console.log(value.position_title);
                                    string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                                });
                                $(".Positions").html(string);
                                window.wisdomDD.rebuild('#talentPoolPositions');
                            }
                        },
                        error: function(response) {
                            toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                        }
                    });
            });
            $(".btn-grid").click(function () {
                $(this).addClass("active");
                $(".grid-main").addClass("d-block");
                $(".grid-main").removeClass("d-none");
                $(".btn-list").removeClass("active");
                $(".list-main").addClass("d-none");
                $(".list-main").removeClass("d-block");
                DatatableGrid()
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
                $('.TalentPool').DataTable().ajax.reload();
            });
            DatatableList();
            // Grid is the default visible view now — #grid_main_view is
            // server-rendered empty and only populated by DatatableGrid(),
            // previously fired only on a manual .btn-grid click, so the grid
            // showed blank on load until the user toggled away and back.
            DatatableGrid();
            $('#RevertResponeForm').validate({
                rules: {
                    Reason: {
                        required: true,
                    },
                    applicant_status_id: {
                        required: true,
                    }
                },
                messages :
                {
                    Reason: {
                        required: "Reason does not exist.",
                    }
                    ,
                    applicant_status_id: {
                        required:  "Reason Applicant Status Not Found.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.RevertBack') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                DatatableList(); DatatableGrid();
                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        $("#Response-modal").modal('hide');

                            }

                    },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            console.log(errors.errors);
                            $.each(errors.errors, function(key, error)
                            {
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right'});
                        }
                    });
                }
            });
        });
    $(document).on("click", ".destoryApplicant", function() {
            var base64_id = $(this).attr('data-id');
            var location = $(this).attr('data-location');

            // SweetAlert confirmation dialog
            wisdomConfirm({
                role: 'destructive',
                title: "Are you sure?",
                text: "This action will permanently delete the applicant.",
                confirmText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with AJAX request after confirmation
                    $.ajax({
                        url: "{{ route('resort.ta.destoryApplicant') }}",
                        type: "POST",
                        data: { base64_id: base64_id, "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success) {
                                wisdomAlert({
                                    type: 'success',
                                    title: "Deleted!",
                                    text: response.message
                                });
                                $("#talentPool_" + location).remove();
                                DatatableList(); DatatableGrid();
                            } else {
                                wisdomAlert({
                                    type: 'error',
                                    title: "Error!",
                                    text: response.message
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                console.log(error);
                                errs += error + '<br>';
                            });
                            wisdomAlert({
                                type: 'error',
                                title: "Error!",
                                text: errs
                            });
                        }
                    });
                }
            });
        });

        $(document).on("click", ".RejactionReason", function() {
        let Comments = $(this).attr('data-Comments');
        let Rank = $(this).attr('data-Rank');
        let applicant_status_id = $(this).attr('data-applicant_status_id');


        $("#Reason").val(Comments);
        if(Rank == 0)
        {
            $(".RevertBack").removeClass('d-none');
            $("#applicant_status_id").val(applicant_status_id);
        }

        $("#Response-modal").modal('show');
    });


    // ---- Talent Pool list (DataTables, server-side — same endpoint, filters, sort and paging as before) ----
    const TPX_ASSET = @json(rtrim(URL::asset(''), '/'));
    const tpxAsset = p => !p ? '' : (/^(https?:)?\/\//.test(p) ? p : TPX_ASSET + '/' + String(p).replace(/^\//, ''));
    const tpxInitials = n => (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')) || '?';
    const TPX_AVAIL = { available: ['Available', 'ok'], pending: ['Pending Response', 'warn'], unavailable: ['Unavailable', 'muted'], consent_rejected: ['Consent Rejected', 'err'] };
    const tpxPct = v => (v === null || v === '' || isNaN(v)) ? null : Math.max(0, Math.min(100, +v));
    const tpxNum = v => +(+v).toFixed(1);
    const tpxYears = v => (v === null || v === undefined || v === '') ? '' : v + (+v === 1 ? ' yr' : ' yrs');

    function tpxConsent(row) {
        const shown = (row.ConsentExpiryDate || '').replace(/\s*\(.*\)\s*$/, '');
        if (!row.consent_expiry_date) return shown && shown !== 'N/A' ? escHtml(shown) : '—';
        const d = new Date(String(row.consent_expiry_date).replace(' ', 'T'));
        let word = 'Valid', cls = 'ok';
        if (row.consent_status === 'pending') { word = 'Pending'; cls = 'muted'; }
        else if (d < new Date()) { word = 'Expired'; cls = 'err'; }
        else if ((d - new Date()) / 864e5 <= 30) { word = 'Expiring'; cls = 'warn'; }
        return escHtml(shown) + ' · <span class="tpx-cs ' + cls + '">' + word + '</span>';
    }

    function tpxStage(row) {
        return (row.rank_name === 'Wisdom AI' ? 'WAI' : (row.rank_name || '')) + ' Rejected';
    }

    function tpxDetail(row) {
        const doc = (label, path) => path ? `<a class="tpx-doc" target="_blank" rel="noopener" href="${escHtml(tpxAsset(path))}">${label}</a>` : '';
        const docs = doc('CV', row.curriculum_vitae) + doc('Passport', row.passport_img) || '—';
        const sc = tpxPct(row.Scoring);
        const item = (k, v) => `<div><div class="d-k">${k}</div><div class="d-v">${v}</div></div>`;
        return `<div class="detail-grid">${item('Email', escHtml(row.email) || '—')}${item('Contact', escHtml(row.contact) || '—')}${item('Passport No.', escHtml(row.passport_no) || '—')}${item('Scoring', sc === null ? '—' : tpxNum(sc) + '/100')}<div><div class="d-k">Documents</div><div class="d-v docs" style="margin-top:5px">${docs}</div></div></div>`;
    }

    function DatatableList()
    {
            if ($.fn.DataTable.isDataTable('.TalentPool'))
            {
                $('.TalentPool').DataTable().destroy();
            }
            var TalentPool = $('.TalentPool').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bFilter: true,
                    bInfo: true,
                    bAutoWidth: false,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    order:[[8, 'desc']],
                    createdRow: function (tr, row) { $(tr).addClass('tp-main'); },
                    ajax: {
                        url: "{{ route('resort.ta.TalentPool')}}",
                        type: 'GET',
                        data: function(d) {
                            d.ResortDepartment = $("#ResortDepartment").val();
                            d.searchTerm = $('.search').val();
                            d.Positions  = $('.Positions ').val()
                        }
                    },
                    columns: [
                        { data: null, orderable: false, searchable: false, className: 'tpx-chevcell', defaultContent: '<svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--faint)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>' },
                        { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                            const sub = [row.countryName, tpxYears(row.total_work_exp)].filter(Boolean).join(' · ');
                            const img = row.profileImg ? `<img src="${escHtml(row.profileImg)}" alt="${escHtml(row.name)}" data-i="${tpxInitials(row.name)}" onerror="this.parentNode.textContent=this.dataset.i">` : tpxInitials(row.name);
                            return `<div class="appcell"><span class="tpx-av">${img}</span><div><div class="nm userApplicants-btn" data-id="${escHtml(row.applicant_id)}">${escHtml(row.name)}</div><div class="sub">${escHtml(sub) || '—'}</div></div></div>`;
                        }},
                        { data: 'Position', name: 'Position', render: function (data, type, row) {
                            return `<div class="poscell"><div class="p">${escHtml(data) || '—'}</div><div class="d">${escHtml(row.Department)}</div></div>`;
                        }},
                        { data: 'Application_date', name: 'Application_date' },
                        { data: 'AIRanking', name: 'AIRanking', render: function (data) {
                            const v = tpxPct(data);
                            return v === null ? '—' : `<div class="wrank"><span class="rv">${tpxNum(v)}</span><span class="rb"><i style="width:${v}%"></i></span></div>`;
                        }},
                        { data: 'availability_status', name: 'availability_status', render: function (data, type, row) {
                            const a = TPX_AVAIL[data] || ['Not checked', 'muted'];
                            return `<div class="statuscell"><span class="pill ${a[1]}">${a[0]}</span><span class="rejchip" data-stage="${escHtml(tpxStage(row))}" data-reason="${escHtml(row.Comments)}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>${escHtml(tpxStage(row))}</span></div>`;
                        }},
                        { data: 'ConsentExpiryDate', name: 'ConsentExpiryDate', render: function (data, type, row) { return tpxConsent(row); }},
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'tpx-actcell', render: function (data, type, row) {
                            // Permissions come from the d-none classes the server already put on its own action markup.
                            const canReject = !/RejactionReason\s+d-none/.test(data || ''), canDelete = !/destoryApplicant\s+d-none/.test(data || '');
                            const b64 = btoa(String(row.id));
                            return `<div class="actcell">
                                <button type="button" class="iact checkAvailabilityBtn" title="Check availability" data-id="${b64}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/></svg></button>
                                <button type="button" class="iact sendConsentRequestBtn" title="Send consent request" data-id="${b64}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></button>
                                <button type="button" class="kebab" aria-label="More actions" data-pid="${escHtml(row.applicant_id)}" data-rank="${escHtml(row.As_ApprovedBy)}" data-sid="${escHtml(row.applicant_status_id)}" data-comments="${escHtml(row.Comments)}" data-del-id="${b64}" data-del-loc="${escHtml(row.id)}" data-can-reject="${canReject ? 1 : 0}" data-can-delete="${canDelete ? 1 : 0}"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg></button>
                            </div>`;
                        }},
                        { data: 'created_at', visible: false, searchable: false },
                    ]

                });
            return TalentPool;
    }

    // Row interactions: chevron cell toggles the detail row; anywhere else opens the profile (same handler as the name).
    $(document).on('click', '.TalentPool tbody tr.tp-main', function (e) {
        const $t = $(e.target);
        if ($t.closest('.tpx-actcell, .userApplicants-btn, a').length) return;
        if ($t.closest('.tpx-chevcell').length) {
            const row = $('.TalentPool').DataTable().row(this);
            if (row.child.isShown()) { row.child.hide(); $(this).removeClass('dt-hasChild'); }
            else { row.child('<div class="tp-detail-wrap">' + tpxDetail(row.data()) + '</div>', 'tp-detail').show(); $(this).addClass('dt-hasChild'); }
            return;
        }
        $(this).find('.userApplicants-btn').first().trigger('click');
    });

    // Grid card: anywhere that isn't a control opens the profile.
    $(document).on('click', '#tpx .tp-card', function (e) {
        if ($(e.target).closest('a, button, .kebab, .userApplicants-btn').length) return;
        $(this).find('.userApplicants-btn').first().trigger('click');
    });

    // Frosted kebab menu — items reuse the existing .userApplicants-btn / .RejactionReason / .destoryApplicant handlers.
    const $tpxMenu = $('#tpxMenu');
    function tpxCloseMenu() { $tpxMenu.removeClass('is-on'); }
    $(document).on('click', '#tpx .kebab', function (e) {
        e.stopPropagation();
        const d = this.dataset;
        $tpxMenu.find('[data-m="profile"]').attr('data-id', d.pid).data('id', d.pid);
        $tpxMenu.find('[data-m="reason"]').attr({ 'data-Rank': d.rank, 'data-applicant_status_id': d.sid, 'data-Comments': d.comments }).toggle(d.canReject === '1');
        $tpxMenu.find('[data-m="sep2"]').toggle(d.canReject === '1' && d.canDelete === '1');
        $tpxMenu.find('[data-m="delete"]').attr({ 'data-id': d.delId, 'data-location': d.delLoc }).toggle(d.canDelete === '1');
        $tpxMenu.addClass('is-on');
        const r = this.getBoundingClientRect(), w = $tpxMenu.outerWidth() || 196, h = $tpxMenu.outerHeight() || 160;
        let top = r.bottom + 6; if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
        $tpxMenu.css({ left: Math.max(8, r.right - w) + 'px', top: top + 'px' });
    });
    $(document).on('click', function (e) { if (!$(e.target).closest('#tpx .kebab, #tpxMenu').length || $(e.target).closest('#tpxMenu .kmi').length) tpxCloseMenu(); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') tpxCloseMenu(); });
    window.addEventListener('scroll', tpxCloseMenu, true);

    // Rejection chip tooltip (frosted-dark, body-level so the table's scroll container can't clip it).
    $(document).on('mouseover', '#tpx .rejchip', function () {
        const tip = document.getElementById('tpxTip'), stage = this.dataset.stage, reason = this.dataset.reason;
        tip.textContent = '';
        const h = document.createElement('div'); h.className = 'rt-h'; h.textContent = stage; tip.appendChild(h);
        const r = document.createElement('div'); r.className = 'rt-r'; r.textContent = reason || 'No reason recorded.'; tip.appendChild(r);
        tip.classList.add('is-on');
        const b = this.getBoundingClientRect(), w = tip.offsetWidth, th = tip.offsetHeight;
        tip.style.left = Math.max(8, Math.min(b.left + b.width / 2 - w / 2, window.innerWidth - w - 8)) + 'px';
        tip.style.top = (b.top - th - 8 < 8 ? b.bottom + 8 : b.top - th - 8) + 'px';
    });
    $(document).on('mouseout', '#tpx .rejchip', function () { document.getElementById('tpxTip').classList.remove('is-on'); });

    // Animates the circular AI Ranking/Scoring meters rendered in the grid
    // partial (gridviwe.blade.php's .progress-container). Was called here
    // without ever being defined in this file (copy-pasted from
    // Applicants/index.blade.php, which does define it) — every grid
    // reload threw "ApplicantProgress is not defined" in the console and
    // the circles never animated.
    function ApplicantProgress() {
        const radius = 54;
        const circumference = 2 * Math.PI * radius;

        const progressContainers = document.querySelectorAll('.progress-container');
        progressContainers.forEach(container => {
            const progressCircle = container.querySelector('.progress');
            const progressValue = container.getAttribute('data-progress');
            const offset = circumference - (progressValue / 100 * circumference);
            if (progressCircle)
            {
                progressCircle.style.transition = 'none';
                progressCircle.style.strokeDasharray = circumference;
                progressCircle.style.strokeDashoffset = circumference;
                progressCircle.offsetHeight;
                setTimeout(() => {
                    progressCircle.style.transition = 'stroke-dashoffset 0.75s ease-in-out';
                    progressCircle.style.strokeDashoffset = offset;
                }, 100);
            }
        });
    }

    function DatatableGrid()
    {


                        $.ajax({
                            url:"{{ route('resort.ta.getTalentPoolApplicant') }}",
                            type: "post",
                            data:
                            {
                                id :$("#vacancy-id").val(),
                                searchTerm : $('.search').val(),
                                ResortDepartment: $("#ResortDepartment").val(),
                                searchTerm: $('.search').val(),
                                Positions : $('.Positions ').val(),
                                "_token": "{{ csrf_token() }}"
                            },
                            success: function(response)
                            {
                                if (response.success)
                                {
                                    $("#grid_main_view").html(response.view);
                                    // $('.table-applicants').DataTable().ajax.reload();
                                    ApplicantProgress();
                                }
                            },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    var errs = '';
                                    console.log(errors.errors);
                                    $.each(errors.errors, function(key, error)
                                    {
                                        console.log(error);
                                        errs += error + '<br>';
                                    });
                                    toastr.error(errs, { positionClass: 'toast-bottom-right'});
                                }
                        });
    }

    // Sidebar - View Profile
    const $userApplicantsWrapper = $(".userApplicants-wrapper");
    $(document).on("click", ".userApplicants-btn", function (e) {
        e.stopPropagation();
        let id = $(this).data("id");
        let url = "{{ route('resort.ta.TaUserApplicantsSideBar', ':id') }}";
        url = url.replace(':id', id);
        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $(".userApplicants-wrapper").html(response.view);
                }
            },
            error: function() {
                toastr.error('Something went wrong.', { positionClass: 'toast-bottom-right' });
            }
        });
        $userApplicantsWrapper.toggleClass("end-0");
    });

    $(document).on("click", ".closeSlider", function (e) {
        e.preventDefault();
        $userApplicantsWrapper.toggleClass("end-0");
    });

    $(document).on("click", "#document-dismiss", function () {
        $("#bdVisa-iframeModel-modal-lg").modal('hide');
        $("#ViewModeOfFiles").empty();
        $(".downloadLink").attr("href", "");
        $(".userApplicants-wrapper").addClass("end-0");
    });

    // Download File in sidebar
    $(document).on("click", ".DownloadFile", function () {
        let fileId = $(this).data("id");
        let fileFlag = $(this).data("flag");
        $.ajax({
            url: "{{ route('resort.ta.DownloadFile') }}",
            type: "POST",
            data: { id: fileId, flag: fileFlag, "_token": "{{ csrf_token() }}" },
            success: function(response) {
                if (response.success) {
                    $("#ViewModeOfFiles").html('<div class="text-center"><p>Loading...</p></div>');
                    $("#bdVisa-iframeModel-modal-lg").modal('show');
                    let fileUrl = response.NewURLshow;
                    $(".downloadLink").attr("href", fileUrl);
                    let mimeType = response.mimeType.toLowerCase();
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    let iframeTypes = ['video/mp4', 'application/pdf', 'text/plain'];
                    if (imageTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html('<img src="'+fileUrl+'" class="popupimgFileModule" alt="Preview">');
                    } else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html('<iframe style="width:100%;height:100%;" src="'+fileUrl+'" allowfullscreen></iframe>');
                    } else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        window.location.href = fileUrl;
                    }
                }
            }
        });
    });

    // Generate/regenerate the "Analyze Of AI" summary from notes + comments
    $(document).on("click", ".generateAiAnalysis-btn", function () {
        let $btn = $(this);
        let $block = $btn.closest(".ai-analysis-block");
        let applicantId = $block.data("applicant-id");
        let $textBox = $block.find(".ai-analysis-text");
        let originalLabel = $btn.text();
        $btn.text("Generating...").addClass("disabled");
        $.ajax({
            url: "{{ url('resort/talent-acquisition/applicant') }}/" + applicantId + "/generate-ai-analysis",
            type: "POST",
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                if (response.success) {
                    $textBox.html('<p class="mb-1"></p>');
                    $textBox.find('p').text(response.analysis);
                    $btn.text("Regenerate");
                } else {
                    toastr.error(response.message || "Could not generate analysis.", "Error", { positionClass: 'toast-bottom-right' });
                    $btn.text(originalLabel);
                }
            },
            error: function () {
                toastr.error("Something went wrong. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                $btn.text(originalLabel);
            },
            complete: function () {
                $btn.removeClass("disabled");
            }
        });
    });

    // Download All Files — a single .zip named after the candidate +
    // position, instead of opening every document in its own tab.
    $(document).on("click", ".DownloadAllFiles", function () {
        let fileId = $(this).data("id");
        window.location.href = "{{ route('resort.ta.DownloadAllFilesZip', ':id') }}".replace(':id', fileId);
    });

    // View/Hide Comments
    $(document).on("click", ".userAppInt-vCommBtn", function () {
        $(this).addClass("d-none").removeClass("d-block").siblings(".userAppInt-hCommBtn").addClass("d-block").removeClass("d-none");
        $(this).closest("tr").find(".userAppInt-commBlock").addClass("d-block");
    });
    $(document).on("click", ".userAppInt-hCommBtn", function () {
        $(this).addClass("d-none").removeClass("d-block").siblings(".userAppInt-vCommBtn").addClass("d-block").removeClass("d-none");
        $(this).closest("tr").find(".userAppInt-commBlock").removeClass("d-block");
    });

    // Send Consent Request - open modal
    $(document).on("click", ".sendConsentRequestBtn", function() {
        var applicantId = $(this).data("id");
        $("#consent_applicant_id").val(applicantId);
        $("#consentRequest-modal").modal("show");
    });

    // Send Consent Request - submit
    $('#consentRequestForm').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Sending...');

        // Convert dd/mm/yyyy to yyyy-mm-dd for backend
        var formData = $(this).serializeArray();
        formData.forEach(function(field) {
            if (field.name === 'consent_expiry_date' && field.value) {
                var parts = field.value.split('/');
                field.value = parts[2] + '-' + parts[1] + '-' + parts[0];
            }
        });

        $.ajax({
            url: "{{ route('resort.ta.sendConsentRequest') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $("#consentRequest-modal").modal("hide");
                    $('#consentRequestForm')[0].reset();
                    DatatableList();
                } else {
                    toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Send Request');
            }
        });
    });

    // Check Availability - open modal
    $(document).on("click", ".checkAvailabilityBtn", function() {
        var applicantId = $(this).data("id");
        $("#availability_applicant_id").val(applicantId);
        $("#checkAvailability-modal").modal("show");
    });

    // Check Availability - submit
    $('#checkAvailabilityForm').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: "{{ route('resort.ta.checkAvailability') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $("#checkAvailability-modal").modal("hide");
                    $('#checkAvailabilityForm')[0].reset();
                    window.wisdomDD.sync('#checkAvailEmailTemplate');
                } else {
                    toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Send Email');
            }
        });
    });

</script>
@include('resorts._dropdown_script')
@endsection

