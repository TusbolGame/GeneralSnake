<?php

namespace App\Http\Controllers\Api;

use App\Entities\Bank;
use App\Entities\CoinAddress;
use App\Entities\Profile;
use App\Libs\GoogleAuthenticator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    public function get(Request $request) {
        $banks = Bank::query();

        $input = $request->input();

        if (isset($input['currency'])) {
            $banks->where('currency', $input['currency']);
        }

        $result = $banks->get();

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function save(Request $request) {
        $input = $request->input();

        $bank = new Bank();
        $bank->name = $input['name'];
        $bank->iban_code = $input['iban_code'];
        $bank->currency = $input['currency'];
        $bank->save();


        return response()->json([
            'success' => true,
        ]);
    }

    public function delete(Request $request) {
        $input = $request->input();

        $id = $input['id'];
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $bank->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function test(Request $request) {

        $banks = Bank::get();

        var_dump($banks);
    }
}
