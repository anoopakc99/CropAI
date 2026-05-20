<?php
$u = \App\Models\User::where("email", "sandeep.kumar@anagar.ccbf.in")->first();
$u->password = \Illuminate\Support\Facades\Hash::make("12345678");
$u->save();
echo "Password Reset\n";
