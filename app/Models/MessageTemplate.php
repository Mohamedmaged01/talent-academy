<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A saved subject and body that administrators reuse for broadcasts. */
class MessageTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body', 'created_by'];
}
