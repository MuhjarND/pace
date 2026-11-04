<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class RevokePhonePasswords extends Migration
{
    public function up()
    {
        // Preserve historical records. Old auto-registration used the public phone number as the password.
        foreach (DB::table('users')->where('role','user')->where('email','like','%@guest.ptsp')->whereNotNull('phone')->get() as $u) {
            if ($u->phone && Hash::check($u->phone,$u->password)) {
                DB::table('users')->where('id',$u->id)->update(['password'=>Hash::make(Str::random(64)),
                    'remember_token'=>Str::random(60),'auth_version'=>$u->auth_version+1,'is_online'=>false,'updated_at'=>now()]);
            }
        }
    }
    public function down() { /* Never restore a phone number as an authentication secret. */ }
}
