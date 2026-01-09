<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Lang;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\Settings;
use App\Helpers\Common;

class AlternativeDateSuggestedNotification extends ResetPasswordNotification
{
    private $leaveRecommend;
    private $recipient;
    private $leave;
    private $from;

    public function __construct($leaveRecommend, $recipient, $leave, $from)
    {
        $this->leaveRecommend = $leaveRecommend;
        $this->recipient = $recipient;
        $this->leave = $leave; // Correct assignment
        $this->from = $from;   // Correct assignment
        // dd( $this->recipient);
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $emailPage = 'emails.commonEmail';

        $emailTemplate = EmailTemplate::find(config('settings.email_template.new_leave_date_recommendation'));

        $subjectLine = isset($emailTemplate) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Request for Leave Date Adjustment/Recommendation | WisdomAI';

        $data['body'] = isset($emailTemplate) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [Recipient's Name],</p><p>I hope this email finds you well. I am writing to discuss the leave request I submitted from [from date] to [to date]. Due to [reason for suggesting an alternate date, if applicable], I would like to propose an adjustment to the leave period.</p><p>The new dates I have in mind are from [new suggested start date] to [new suggested end date]. I have ensured that these dates minimize any potential disruption to our ongoing tasks and responsibilities.</p><p>Please let me know if the proposed dates work for you or if you would like me to explore alternate options. I am happy to discuss this further and ensure a smooth transition during my absence.</p><p>Thank you for your understanding and support.</p><p>Best regards,<br>[Your Full Name],<br> HR Team.</p>";

        $settings = Settings::first();
        $data['siteLogo'] = $settings ? $settings->site_logo : '';
        $data['facebookLink'] = $settings->facebook_link;
        $data['instagramLink'] = $settings->instagram_link;
        $data['website'] = $settings->website;

        $healthy = [
            "[Recipient's Name]",
            "[from date]",
            "[to date]",
            "[reason for suggesting an alternate date, if applicable]",
            "[new suggested start date]",
            "[new suggested end date]",
            "[Your Full Name]"
        ];

        $yummy = [
            $this->recipient->resortAdmin->first_name . " " . $this->recipient->resortAdmin->last_name,
            $this->leave->from_date,
            $this->leave->to_date,
            $this->leaveRecommend->comments,
            $this->leaveRecommend->alt_start_date,
            $this->leaveRecommend->alt_end_date,
            $this->from
        ];

        $subject = str_replace($healthy, $yummy, $subjectLine);
        $data['mainbody'] = str_replace($healthy, $yummy, $data['body']);

        $data['settings'] = $settings;

        // dd($data['mainbody']);
        $mail = (new MailMessage)
            ->from(env("MAIL_FROM_ADDRESS"))
            ->view($emailPage, $data)
            ->subject(Lang::get($subject));
        // dd($mail);

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
