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

class TravelArrangementNotification extends ResetPasswordNotification
{
    private $travel_partners;
    private $leave;
    private $sender;

    public function __construct($partner, $leave, $sender)
    {
        $this->partner = $partner;
        $this->leave = $leave; // Correct assignment
        $this->sender = $sender;   // Correct assignment
        // dd( $this->sender );
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $emailPage = 'emails.commonEmail';
        // dd($this->partner);
        $travel_partner_email = $this->partner->agents_email;
        $travel_partner_name = $this->partner->name;

        $emailTemplate = EmailTemplate::find(config('settings.email_template.travel_arrangement_for_employee'));

        $subjectLine = isset($emailTemplate) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Travel Arrangements for Employee. | WisdomAI';

        $data['body'] = isset($emailTemplate) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [Travel Partner Name],
</p><p>
We have an upcoming leave request from an employee requiring transportation to the Maldives. </p><p>The details are as follows:

- </p><p>Employee Name : [Employee Name]&nbsp;</p><p>Employee ID: [Employee ID]&nbsp;</p><p>Position: [Employee Position]&nbsp;</p><p>Destination: [Destination]
</p><p>
Transportation details:</p><p>[Transporataion Mode]:&nbsp; Arrival on [Arrival Date],&nbsp; Departure on [Departure Date] </p><p>&nbsp;Kindly arrange the necessary travel and confirm.
</p><p>
Best regards,</p><p>[Sender Name],</p><p>HR Department.</p>";

        $settings = Settings::first();
        $data['siteLogo'] = $settings ? $settings->site_logo : '';
        $data['facebookLink'] = $settings->facebook_link;
        $data['instagramLink'] = $settings->instagram_link;
        $data['website'] = $settings->website;

        $healthy = [
            "[Travel Partner Name]",
            "[Employee Name]",
            "[Employee ID]",
            "[Employee Position]",
            "[Destination]",
            "[Transporataion Mode]",
            "[Arrival Date]",
            "[Departure  Date]",
            "[Your Full Name]"
        ];

        $yummy = [
            $travel_partner_name,
            $this->leave->first_name." ".$this->leave->last_name,
            $this->leave->Emp_id,
            $this->leave->position_title,
            $this->leave->destination,
            $this->leave->transportation_mode,
            $this->leave->trans_arrival_date,
            $this->leave->trans_departure_date,
            $this->sender
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
