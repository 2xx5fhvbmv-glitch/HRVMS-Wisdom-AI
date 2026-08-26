<style>
    .wisdom-swal-popup {
        border-radius: 20px !important;
        box-shadow: 0 24px 60px rgba(1, 30, 36, .32) !important;
    }
    .swal2-container.swal2-backdrop-show { background: rgba(6, 24, 29, .4) !important; }
    .wisdom-swal-icon {
        width: 60px !important;
        height: 60px !important;
        margin: 28px auto 16px !important;
        border: 0 !important;
        border-radius: 50% !important;
        background: #E6F0F1 !important;
        color: #014653 !important;
    }
    .wisdom-swal-icon.wisdom-swal-icon-destructive {
        background: #FFDED9 !important;
        color: #FF2400 !important;
    }
    .wisdom-swal-icon .swal2-icon-content { padding: 0; }
    .wisdom-swal-title {
        font-size: 18px !important;
        font-weight: 800 !important;
        color: #14232A !important;
        padding: 0 0 4px !important;
        margin: 0 !important;
    }
    .wisdom-swal-text {
        font-size: 13px !important;
        color: #5D6F75 !important;
        margin: 0 0 8px !important;
    }
    .wisdom-swal-actions {
        justify-content: flex-end !important;
        gap: 10px !important;
        width: 100% !important;
        margin: 18px 0 0 !important;
        padding: 0 28px !important;
    }
    /* wisdomAlert has a single OK button (no cancel) — flex-end from the
       shared class above would strand it in the bottom-right corner, so
       center it instead. wisdomConfirm keeps flex-end (its two buttons,
       reversed, read correctly right-aligned). */
    .wisdom-swal-actions.wisdom-swal-actions-center {
        justify-content: center !important;
    }
    .wisdom-swal-confirm,
    .wisdom-swal-cancel {
        margin: 0 !important;
        padding: 11px 20px !important;
        border-radius: 12px !important;
        font-size: 14px;
        font-weight: 700;
        box-shadow: none !important;
    }
    .wisdom-swal-confirm {
        background: #014653 !important;
        color: #fff !important;
        border: none !important;
    }
    .wisdom-swal-confirm.wisdom-swal-confirm-destructive {
        background: #FF2400 !important;
    }
    .wisdom-swal-confirm.wisdom-swal-confirm-positive {
        background: #1F9D6B !important;
    }
    .wisdom-swal-cancel {
        background: #DEDEDE !important;
        color: #14232A !important;
        border: none !important;
        font-weight: 600;
    }
    .wisdom-swal-icon.wisdom-swal-icon-positive {
        background: #E9F7F0 !important;
        color: #1F9D6B !important;
    }
    .wisdom-swal-icon.wisdom-swal-icon-warning {
        background: #FBF0DC !important;
        color: #D98A00 !important;
    }
    .wisdom-swal-confirm.wisdom-swal-confirm-warning {
        background: #D98A00 !important;
    }
</style>
<script>
    /* Shared on-brand replacement for ad-hoc Swal.fire({confirmButtonColor:...})
       calls scattered across the app (see design-token consolidation audit —
       118 calls, 6 uncoordinated color patterns, several still SweetAlert-blue).
       Same visual mechanism as the logout modal (js.blade.php): buttonsStyling
       false + fully custom classes, not confirmButtonColor/cancelButtonColor
       (which only sets background and leaves SweetAlert's default white text,
       unreadable on a light cancel button). Icons are inline SVG via iconHtml,
       not SweetAlert's stock icon types — 'warning' renders its own orange
       triangle and 'question' its own blue-grey mark regardless of button
       color, which would quietly reintroduce the alarm-orange/blue this
       design system moved away from. */
    var WISDOM_CONFIRM_ICONS = {
        confirm: '<svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        destructive: '<svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
        warning: '<svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="7" x2="12" y2="13"/><circle cx="12" cy="16.5" r="1" fill="currentColor" stroke="none"/></svg>',
        error: '<svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>',
        info: '<svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="11" x2="12" y2="16"/><circle cx="12" cy="7.5" r="1" fill="currentColor" stroke="none"/></svg>'
    };

    window.wisdomConfirm = function (opts) {
        opts = opts || {};
        // opts: { role: 'destructive'|'confirm'|'positive', title, text,
        //         confirmText, cancelText, onConfirm, extra }
        // `extra` is merged in last so callers can pass SweetAlert options
        // the branded defaults below don't cover (input, inputValidator,
        // preConfirm, didOpen, html, ...) without losing the branded
        // icon/buttons/classes — every dialog routes through one helper
        // instead of hand-rolled copies of this config.
        var ROLES = {
            destructive: { confirm: 'Delete', icon: WISDOM_CONFIRM_ICONS.destructive, iconClass: 'wisdom-swal-icon-destructive', confirmClass: 'wisdom-swal-confirm-destructive' },
            confirm:     { confirm: 'Confirm', icon: WISDOM_CONFIRM_ICONS.confirm, iconClass: '', confirmClass: '' },
            positive:    { confirm: 'Yes', icon: WISDOM_CONFIRM_ICONS.confirm, iconClass: 'wisdom-swal-icon-positive', confirmClass: 'wisdom-swal-confirm-positive' },
            warning:     { confirm: 'Yes', icon: WISDOM_CONFIRM_ICONS.warning, iconClass: 'wisdom-swal-icon-warning', confirmClass: 'wisdom-swal-confirm-warning' }
        };
        var role = ROLES[opts.role] ? opts.role : 'confirm';
        var r = ROLES[role];

        var config = {
            title: opts.title,
            text: opts.text,
            iconHtml: r.icon,
            showCancelButton: true,
            reverseButtons: true,
            buttonsStyling: false,
            confirmButtonText: opts.confirmText || r.confirm,
            cancelButtonText: opts.cancelText || 'Cancel',
            customClass: {
                popup: 'wisdom-swal-popup',
                icon: ('wisdom-swal-icon ' + r.iconClass).trim(),
                title: 'wisdom-swal-title',
                htmlContainer: 'wisdom-swal-text',
                actions: 'wisdom-swal-actions',
                confirmButton: ('wisdom-swal-confirm ' + r.confirmClass).trim(),
                cancelButton: 'wisdom-swal-cancel'
            }
        };
        Object.assign(config, opts.extra || {});

        return Swal.fire(config).then(function (res) {
            if (res.isConfirmed && typeof opts.onConfirm === 'function') opts.onConfirm(res);
            return res;
        });
    };

    /* Single-button branded acknowledgment popup — replacement for the
       app-wide Swal.fire({title:'Success!'/'Error!', ..., confirmButtonColor})
       follow-ups left untouched by wisdomConfirm's migration (see design-
       token consolidation audit). Not wired into any view yet — helper
       only, per Phase 2b. */
    window.wisdomAlert = function (opts) {
        opts = opts || {};
        // opts: { type: 'success'|'error'|'info', title, text, extra }
        // `extra` merges in last, same mechanism as wisdomConfirm — lets
        // callers pass html/didOpen/etc without losing the branded icon
        // and button.
        var TYPES = {
            success: { icon: WISDOM_CONFIRM_ICONS.confirm, iconClass: 'wisdom-swal-icon-positive', confirmClass: 'wisdom-swal-confirm-positive' },
            error:   { icon: WISDOM_CONFIRM_ICONS.error, iconClass: 'wisdom-swal-icon-destructive', confirmClass: 'wisdom-swal-confirm-destructive' },
            info:    { icon: WISDOM_CONFIRM_ICONS.info, iconClass: '', confirmClass: '' }
        };
        var t = TYPES[opts.type] ? TYPES[opts.type] : TYPES.info;

        var config = {
            title: opts.title,
            text: opts.text,
            iconHtml: t.icon,
            buttonsStyling: false,
            confirmButtonText: 'OK',
            customClass: {
                popup: 'wisdom-swal-popup',
                icon: ('wisdom-swal-icon ' + t.iconClass).trim(),
                title: 'wisdom-swal-title',
                htmlContainer: 'wisdom-swal-text',
                actions: 'wisdom-swal-actions wisdom-swal-actions-center',
                confirmButton: ('wisdom-swal-confirm ' + t.confirmClass).trim()
            }
        };
        Object.assign(config, opts.extra || {});

        return Swal.fire(config);
    };
</script>
