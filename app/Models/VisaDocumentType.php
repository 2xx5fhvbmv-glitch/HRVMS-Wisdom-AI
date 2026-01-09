<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaDocumentType extends Model
{
    use HasFactory;

    protected  $table = 'visa_document_types';
    public  $fillable = ["resort_id" ,'documentname'];

}
