<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaDocumentSegmentation extends Model
{
    use HasFactory;
    protected  $table = 'visa_document_segmentations';
    public  $fillable = ["resort_id" ,'document_id','DocumentName'];
  
}
