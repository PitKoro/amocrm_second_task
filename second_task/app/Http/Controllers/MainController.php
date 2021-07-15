<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;




class MainController extends Controller
{
    public function validation(Request $request)
    {

        $request->validate([
            'name' => 'required|alpha',
            'surname' => 'required|alpha',
            'age' => 'required | numeric | min:1 | max:120',
            'phone' => 'required | digits:11'
        ]);

        $inputData = $request->all();

        return redirect()->route('submit', [
            'name'    => $inputData['name'], 
            'surname' => $inputData['surname'], 
            'age'     => $inputData['age'], 
            'phone'   => $inputData['phone'], 
            'email'   => $inputData['email'], 
            'gender'  => $inputData['gender']
        ]);
    }
}
