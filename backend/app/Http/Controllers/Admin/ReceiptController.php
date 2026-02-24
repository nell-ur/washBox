<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Laundry;

class ReceiptController extends Controller
{
    public function show($laundryId)
    {
        $laundry = Laundry::findOrFail($laundryId);
        return view('admin.receipts.show', compact('laundry'));
    }
}

