<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pageTitle = 'Employee List';

        $employees = Employee::all();


        return view('employee.index', ['pageTitle' => $pageTitle], ['employees' => $employees]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee';
        $positions = Position::all();
        return view('employee.create', compact('pageTitle', 'positions'));
    }

    public function store(Request $request)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $file = $request->file('cv');

        if ($file != null) {
            $originalFilename = $file->getClientOriginalName();
            $encryptedFilename = $file->hashName();

            // Store File
            $file->store('public/files');

            // Associate filenames with the employee model
            $employee = new Employee;
            $employee->firstname = $request->firstName;
            $employee->lastname = $request->lastName;
            $employee->email = $request->email;
            $employee->age = $request->age;
            $employee->position_id = $request->position;
            $employee->original_filename = $originalFilename;
            $employee->encrypted_filename = $encryptedFilename;
            $employee->save();
        } else {
            // If no file is uploaded, save the employee without the filenames
            $employee = new Employee;
            $employee->firstname = $request->firstName;
            $employee->lastname = $request->lastName;
            $employee->email = $request->email;
            $employee->age = $request->age;
            $employee->position_id = $request->position;
            $employee->save();
        }
        return redirect()->route('employees.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';
        // RAW SQL QUERY
        $employee = Employee::find($id);

        return view('employee.show', compact('pageTitle', 'employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Employee Edit';
        $positions = Position::all();
        $employee = Employee::find($id);

        return view('employee.edit',  compact('pageTitle', 'positions', 'employee'));
    }

    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $employee = Employee::find($id);

        if ($employee) {
            $file = $request->file('cv');

            if ($file) {
                $encryptedFilename = $employee->encrypted_filename;

                if ($encryptedFilename) {
                    if (Storage::disk('public')->exists($encryptedFilename)) {
                        Storage::disk('public')->delete($encryptedFilename);
                        $employee->original_filename = null;
                        $employee->encrypted_filename = null;
                    }
                }

                $originalFilename = $file->getClientOriginalName();
                $encryptedFilename = $file->hashName();

                $file->store('public/files');

                $employee->original_filename = $originalFilename;
                $employee->encrypted_filename = $encryptedFilename;
            }

            $employee->firstname = $request->firstName;
            $employee->lastname = $request->lastName;
            $employee->email = $request->email;
            $employee->age = $request->age;
            $employee->position_id = $request->position;
            $employee->save();
        }

        return redirect()->route('employees.index');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return redirect()->route('employees.index')->with('error', 'Employee not found.');
        }

        $encryptedFilename = $employee->encrypted_filename;

        if ($encryptedFilename) {
            if (Storage::disk('public')->exists('files/' . $encryptedFilename)) {
                Storage::disk('public')->delete('files/' . $encryptedFilename);
            }
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee and CV deleted successfully.');
    }



    public function downloadFile($employeeId)
    {
        $employee = Employee::find($employeeId);
        $encryptedFilename = 'public/files/' . $employee->encrypted_filename;
        $downloadFilename = Str::lower($employee->firstname . '_' . $employee->lastname . '_cv.pdf');

        if (Storage::exists($encryptedFilename)) {
            return Storage::download($encryptedFilename, $downloadFilename);
        }
    }

    public function editCV($employeeId)
    {
        $employee = Employee::find($employeeId);
        return view('employees.edit-cv', compact('employee'));
    }

    public function updateCV(Request $request, $employeeId)
    {
        $request->validate([
            'cv' => 'required|mimes:pdf|max:2048', // Atur tipe file dan ukuran maksimal yang diizinkan
        ]);

        $employee = Employee::find($employeeId);

        if ($request->hasFile('cv')) {
            // Hapus CV lama jika ada sebelum mengunggah yang baru
            if ($employee->original_filename) {
                Storage::delete($employee->original_filename);
            }

            // Simpan CV baru ke direktori penyimpanan
            $filename = $request->file('cv')->getClientOriginalName();
            $request->file('cv')->storeAs('cv', $filename);
            $employee->original_filename = $filename;
            $employee->save();
        }

        return redirect()->route('employees.show', ['employeeId' => $employeeId])
            ->with('success', 'CV berhasil diperbarui.');
    }
}
