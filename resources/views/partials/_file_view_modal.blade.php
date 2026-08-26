{{--
    Shared "View / Download File" modal — used by every file-preview trigger
    across the app (File Management, Visa, Support, Incident, Talent
    Acquisition, both resort-admin and admin guards).

    Frontend shell only: the existing per-page JS still targets the same
    #ViewModeOfFiles body and .downloadLink anchor (href is set dynamically to
    the file's resolved URL, exactly as before) and still calls
    $('#'+modalId).modal('show'/'hide') — none of that changed, only the
    markup/styling around it. Self-contained CSS (own --fvm-* tokens, not the
    resort portal's design tokens) so it renders identically whether it's
    included from a resort-admin page or an admin-panel page, since the admin
    layouts don't load the resort's token stylesheet.

    Usage: @include('partials._file_view_modal', ['modalId' => 'bd-iframeModel-modal-lg'])
    $modalId defaults to 'bdVisa-iframeModel-modal-lg' (the id already used by
    11 of the 13 call sites) if not passed.
--}}
@php
    $modalId = $modalId ?? 'bdVisa-iframeModel-modal-lg';
    // A couple of call sites bind page-specific cleanup (clearing sidebar state,
    // emptying the viewer) to the Cancel button's id — pass cancelId to keep
    // that JS firing; defaults to none for every other call site.
    $cancelId = $cancelId ?? null;
@endphp
<style>
    #{{ $modalId }} {
        --fvm-teal: #014653; --fvm-teal-2: #035b6c; --fvm-teal-3: #e6f0f1;
        --fvm-ink: #1A1F22; --fvm-g2: #6B7378; --fvm-g3: #99A1A5;
        --fvm-g4: #C7CDCF; --fvm-g6: #F4F5F5; --fvm-line: #E2EBEC;
    }
    #{{ $modalId }} .fvm-content { border: none; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.25); overflow: hidden; }
    #{{ $modalId }} .fvm-head { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-bottom: 1px solid var(--fvm-line); }
    #{{ $modalId }} .fvm-fic { flex: none; width: 40px; height: 40px; border-radius: 11px; background: var(--fvm-teal-3); color: var(--fvm-teal); display: grid; place-items: center; }
    #{{ $modalId }} .fvm-fn { flex: 1; min-width: 0; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 600; color: var(--fvm-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #{{ $modalId }} .fvm-x { flex: none; width: 36px; height: 36px; border-radius: 50%; background: transparent; border: none; color: var(--fvm-g3); cursor: pointer; display: grid; place-items: center; font-size: 16px; transition: .15s; }
    #{{ $modalId }} .fvm-x:hover { background: var(--fvm-g6); color: var(--fvm-ink); }
    #{{ $modalId }} .fvm-body { padding: 0; background: var(--fvm-g6); }
    #{{ $modalId }} .fvm-foot { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 16px 22px; border-top: 1px solid var(--fvm-line); background: #fff; }
    #{{ $modalId }} .fvm-btn-secondary { height: 40px; padding: 0 18px; border: 1px solid var(--fvm-line); border-radius: 10px; background: #fff; color: var(--fvm-g2); font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; transition: .15s; }
    #{{ $modalId }} .fvm-btn-secondary:hover { border-color: var(--fvm-g4); color: var(--fvm-ink); }
    #{{ $modalId }} .fvm-btn-primary { height: 40px; padding: 0 20px; border: none; border-radius: 10px; background: var(--fvm-teal); color: #fff; font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; transition: .15s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    #{{ $modalId }} .fvm-btn-primary:hover { background: var(--fvm-teal-2); color: #fff; }
</style>
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content fvm-content">
            <div class="fvm-head">
                <div class="fvm-fic">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div class="fvm-fn" id="{{ $modalId }}Label">File Preview</div>
                <button type="button" class="fvm-x" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body fvm-body">
                <div class="ratio ratio-21x9" id="ViewModeOfFiles"></div>
            </div>
            <div class="fvm-foot">
                <button type="button" @if($cancelId) id="{{ $cancelId }}" @endif class="fvm-btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <a href="" target="_blank" class="fvm-btn-primary downloadLink">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                    Download
                </a>
            </div>
        </div>
    </div>
</div>
