<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessages extends Model
{
    use HasFactory;
    protected $table = 'support_messages';


    protected $fillable = [
        'ticket_id',
        'sender',
        'sender_id',
        'message',
        'attachments',
    ];

    public function support() {
        return $this->belongsTo(Support::class);
    }

}
