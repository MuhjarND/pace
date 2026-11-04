<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Crypt;

trait EncryptsComplaintFields
{
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->complaintEncryptedFields, true)) {
            $this->attributes[$key] = $value === null || $value === '' ? null : Crypt::encryptString((string)$value);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        if (in_array($key, $this->complaintEncryptedFields, true)) {
            $value = $this->attributes[$key] ?? null;
            return $value === null ? null : Crypt::decryptString($value);
        }
        return parent::getAttribute($key);
    }
}
