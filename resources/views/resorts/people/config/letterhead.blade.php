@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>People</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('people.config') }}" class="btn btn-themeGray btn-sm">Back to Configuration</a>
                </div>
            </div>
        </div>

        <div class="row g-30">
            <div class="col-xxl-9 col-xl-10 col-lg-12">
                <div class="card">
                    <div class="card-title">
                        <h3>Letterhead &amp; Signature</h3>
                    </div>

                    <p class="mb-3">
                        These settings are used on document / letter PDFs generated for this resort
                        (e.g. the Transfer Letter). If no header image is uploaded, letters fall back
                        to the resort logo and a typed signature.
                    </p>

                    <form id="letterheadForm" method="POST" action="{{ route('people.config.letterhead.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            {{-- Header image --}}
                            <div class="col-md-6">
                                <label class="form-label">LETTERHEAD HEADER IMAGE</label>
                                <input type="file" class="form-control" name="header_image" accept="image/*,.heic,.heif">
                                <small class="text-muted">Branded banner shown at the top of the letter. JPEG/PNG/GIF/SVG/WEBP/HEIC, max 5 MB.</small>
                                @if($letterhead && $letterhead->imageUrl('header_image'))
                                    <div class="mt-2">
                                        <img src="{{ $letterhead->imageUrl('header_image') }}" alt="Current header" style="max-height:70px; border:1px solid #ddd; padding:4px;">
                                    </div>
                                @endif
                            </div>

                            {{-- Footer image --}}
                            <div class="col-md-6">
                                <label class="form-label">LETTERHEAD FOOTER IMAGE (OPTIONAL)</label>
                                <input type="file" class="form-control" name="footer_image" accept="image/*,.heic,.heif">
                                <small class="text-muted">Optional branded footer shown at the bottom of the letter.</small>
                                @if($letterhead && $letterhead->imageUrl('footer_image'))
                                    <div class="mt-2">
                                        <img src="{{ $letterhead->imageUrl('footer_image') }}" alt="Current footer" style="max-height:70px; border:1px solid #ddd; padding:4px;">
                                    </div>
                                @endif
                            </div>

                            {{-- Signature image --}}
                            <div class="col-md-6">
                                <label class="form-label">E-SIGNATURE IMAGE</label>
                                <input type="file" class="form-control" name="signature_image" accept="image/*,.heic,.heif">
                                <small class="text-muted">Scanned / digital signature placed above the signatory name.</small>
                                @if($letterhead && $letterhead->imageUrl('signature_image'))
                                    <div class="mt-2">
                                        <img src="{{ $letterhead->imageUrl('signature_image') }}" alt="Current signature" style="max-height:60px; border:1px solid #ddd; padding:4px;">
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6"></div>

                            {{-- Signatory --}}
                            <div class="col-md-6">
                                <label class="form-label">SIGNATORY NAME</label>
                                <input type="text" class="form-control" name="signatory_name" maxlength="150"
                                       placeholder="e.g. Jane Doe" value="{{ old('signatory_name', $letterhead->signatory_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SIGNATORY TITLE / DESIGNATION</label>
                                <input type="text" class="form-control" name="signatory_title" maxlength="150"
                                       placeholder="e.g. Director of Human Resources" value="{{ old('signatory_title', $letterhead->signatory_title ?? '') }}">
                            </div>

                            {{-- Address / contact --}}
                            <div class="col-md-6">
                                <label class="form-label">ADDRESS LINE 1</label>
                                <input type="text" class="form-control" name="address_line1" maxlength="255"
                                       value="{{ old('address_line1', $letterhead->address_line1 ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ADDRESS LINE 2</label>
                                <input type="text" class="form-control" name="address_line2" maxlength="255"
                                       value="{{ old('address_line2', $letterhead->address_line2 ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CONTACT PHONE</label>
                                <input type="text" class="form-control" name="contact_phone" maxlength="100"
                                       value="{{ old('contact_phone', $letterhead->contact_phone ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CONTACT EMAIL</label>
                                <input type="email" class="form-control" name="contact_email" maxlength="150"
                                       value="{{ old('contact_email', $letterhead->contact_email ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">WEBSITE</label>
                                <input type="text" class="form-control" name="website" maxlength="150"
                                       value="{{ old('website', $letterhead->website ?? '') }}">
                            </div>
                        </div>
                    </form>

                    <div class="card-footer text-end mt-3">
                        <a href="#" class="btn btn-themeBlue btn-sm" id="letterhead-form-submit">
                            <span class="lh-btn-text">Save Settings</span>
                            <span class="lh-btn-loader spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('#letterhead-form-submit').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $form = $('#letterheadForm');

            $btn.find('.lh-btn-text').addClass('d-none');
            $btn.find('.lh-btn-loader').removeClass('d-none');

            const formData = new FormData($form[0]);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $btn.find('.lh-btn-loader').addClass('d-none');
                    $btn.find('.lh-btn-text').removeClass('d-none');
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    setTimeout(function () { window.location.reload(); }, 1200);
                },
                error: function (xhr) {
                    $btn.find('.lh-btn-loader').addClass('d-none');
                    $btn.find('.lh-btn-text').removeClass('d-none');

                    let errs = '';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (key, messages) {
                            errs += messages[0] + '<br>';
                        });
                    } else {
                        errs = (xhr.responseJSON && xhr.responseJSON.message) || 'An unexpected error occurred.';
                    }
                    toastr.error(errs, "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection
