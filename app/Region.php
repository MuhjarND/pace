<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    protected $guarded = [];

    public function ancestors()
    {
        $parts = explode('.', $this->code);
        $codes = [];
        while ($parts) { $codes[] = implode('.', $parts); array_pop($parts); }
        return $codes;
    }
}
