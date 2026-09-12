{{--
    Reusable e-signature block for PDF templates. Renders one or more
    frozen approval signatures — signature image, name, and the exact
    date/time they approved/consented — plus a small "Electronically
    signed" caption so it reads as a real signature block.

    ALWAYS reads from fields already frozen on the approval record at the
    moment the approval happened (Common::snapshotSignature()) — never
    calls back to ResortAdmin.signature_img directly. A past approval must
    keep showing the exact signature that was used at the time, even if
    the approver has since re-uploaded a new one or left the company.

    Usage:
        @include('resorts.pdf_partials._signature_block', ['signatures' => [
            ['name' => 'Jane HOD', 'signature_img' => $approval->signature_img, 'timestamp' => $approval->approved_at],
        ]])

    $signatures: array of ['name' => string, 'signature_img' => ?string (StorageHelper-relative path), 'timestamp' => ?\Carbon\Carbon|string]
--}}
<style>
    .wai-sig-block-row { display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px; }
    .wai-sig-block { display: inline-block; min-width: 180px; }
    .wai-sig-block .wai-sig-img { height: 45px; max-width: 160px; display: block; margin-bottom: 2px; }
    .wai-sig-block .wai-sig-line { border-top: 1px solid #333; width: 160px; margin-top: 2px; margin-bottom: 4px; }
    .wai-sig-block .wai-sig-name { font-size: 12px; font-weight: bold; }
    .wai-sig-block .wai-sig-time { font-size: 10px; color: #555; }
    .wai-sig-block .wai-sig-caption { font-size: 9px; color: #888; font-style: italic; margin-top: 2px; }
</style>
<div class="wai-sig-block-row">
    @foreach (($signatures ?? []) as $sig)
        @php
            $sigName = $sig['name'] ?? null;
            $sigPath = $sig['signature_img'] ?? null;
            $sigTime = $sig['timestamp'] ?? null;
            $sigDataUri = $sigPath ? \App\Helpers\Common::signatureImageDataUri($sigPath) : null;
            $sigTimeFormatted = null;
            if ($sigTime) {
                try {
                    $sigTimeFormatted = \Carbon\Carbon::parse($sigTime)->format('d M Y, h:i A');
                } catch (\Throwable $e) {
                    $sigTimeFormatted = (string) $sigTime;
                }
            }
        @endphp
        @if ($sigName)
            <div class="wai-sig-block">
                @if ($sigDataUri)
                    <img src="{{ $sigDataUri }}" class="wai-sig-img" alt="signature">
                @else
                    <div class="wai-sig-line"></div>
                @endif
                <div class="wai-sig-name">{{ $sigName }}</div>
                @if ($sigTimeFormatted)
                    <div class="wai-sig-time">Signed on {{ $sigTimeFormatted }}</div>
                @endif
                <div class="wai-sig-caption">Electronically signed</div>
            </div>
        @endif
    @endforeach
</div>
