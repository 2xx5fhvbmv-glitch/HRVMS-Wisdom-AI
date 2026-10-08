<?php

namespace App\Support\Demo;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Email for the Demo ENV resort is still really sent — but only ever to one
 * inbox (config demo.mail_to), with a banner naming who it was meant for, so a
 * client sees exactly what an employee or candidate would receive.
 *
 * A message counts as demo mail when it is sent while the demo resort is the
 * current resort (Common::applyResortSmtpConfig marks it — every request and
 * job that mails on behalf of a resort calls that first), or when any
 * recipient is a demo login address. Real resorts' mail is never touched.
 */
class DemoMail
{
    private static bool $demoContext = false;

    public static function enterResort($resortId): void
    {
        self::$demoContext = Demo::isDemoResort($resortId);
    }

    /** Queue workers live across jobs: forget the previous job's resort. */
    public static function reset(): void
    {
        self::$demoContext = false;
    }

    public static function redirect(MessageSending $event): void
    {
        if (!Demo::enabled() || !($event->message instanceof Email)) {
            return;
        }
        $email = $event->message;
        $recipients = array_merge($email->getTo(), $email->getCc(), $email->getBcc());
        $domain = '@' . strtolower(config('demo.login_domain'));
        $toDemoLogin = collect($recipients)->contains(fn (Address $a) => str_ends_with(strtolower($a->getAddress()), $domain));
        if (!self::$demoContext && !$toDemoLogin) {
            return;
        }

        $intended = implode(', ', array_map(fn (Address $a) => $a->getAddress(), $recipients)) ?: 'no recipient';
        $email->to(new Address(config('demo.mail_to'), 'Demo ENV'));
        $email->getHeaders()->remove('Cc');
        $email->getHeaders()->remove('Bcc');
        $email->subject('[Demo ENV] ' . $email->getSubject());

        $note = "Demo ENV — this email was meant for: {$intended}";
        if (is_string($html = $email->getHtmlBody())) {
            $email->html('<div style="background:#fff3cd;border:1px solid #ffe08a;color:#664d03;padding:10px 14px;margin:0 0 16px;font-family:Arial,sans-serif;font-size:13px">'
                . e($note) . '</div>' . $html);
        }
        if (is_string($text = $email->getTextBody())) {
            $email->text($note . "\n\n" . $text);
        }
    }
}
