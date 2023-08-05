@extends('layouts.app')
@section('content')
    <div class="container-sm my-5">
        <div class="row justify-content-center">
            <div class="p-5 bg-light rounded-3 col-xl-4 border">
                <div class="mb-3 text-center">
                    <i class="bi-person-circle fs-1"></i>
                    <h4>Edit Curriculum Vitae (CV)</h4>
                </div>
                <hr>
                <form action="{{ route('employees.updateCV', ['employeeId' => $employee->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="cv" class="form-label">Unggah CV (PDF)</label>
                            <input type="file" name="cv" class="form-control">
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg mt-3">
                                <i class="bi bi-upload me-1"></i> Unggah CV
                            </button>
                            <a href="{{ route('employees.show', ['employeeId' => $employee->id]) }}" class="btn btn-outline-dark btn-lg mt-3">
                                <i class="bi-arrow-left-circle me-2"></i> Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
