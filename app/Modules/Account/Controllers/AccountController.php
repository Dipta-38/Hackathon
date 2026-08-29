<?php
// app/Modules/Account/Controllers/AccountController.php

namespace App\Modules\Account\Controllers;

use App\Modules\Account\Models\Account;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AccountController extends Controller
{
    public function balance()
    {
        $userId = Auth::id();
        
        $balance = Cache::remember("account_balance:{$userId}", 60, function() use ($userId) {
            $account = Account::where('user_id', $userId)->first();
            return $account ? $account->balance : 0;
        });
        
        $available = Cache::remember("account_available:{$userId}", 60, function() use ($userId) {
            $account = Account::where('user_id', $userId)->first();
            return $account ? $account->getAvailableBalance() : 0;
        });
        
        return response()->json([
            'balance' => $balance,
            'available' => $available,
            'currency' => 'BDT'
        ]);
    }
}