

@extends('layouts.vertical', ['title' => 'Patients', 'sub_title' => 'Pages', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="grid grid-cols-12">
        <div class="col-span-12">   
           <div class="card">
    <div class="flex justify-between items-center p-6">
        <h5 class="card-title">Add Patient</h5>
    </div>
    <form class="grid lg:grid-cols-3 gap-6 p-6" action="" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Profile Picture Preview --}}
        <div class="lg:col-span-3 flex justify-center">
            <div class="flex flex-col items-center gap-3">
                <img 
                    id="profile_preview" 
                    src="https://ui-avatars.com/api/?name=Patient&background=random" 
                    alt="Profile Preview"
                    class="w-24 h-24 rounded-full object-cover border-2 border-gray-200 shadow"
                >
                <label for="profile_src" class="btn border-dark text-dark cursor-pointer text-sm">
                    <i class="ri-upload-2-line me-1"></i> Upload Photo
                </label>
                <input 
                    type="file" 
                    class="hidden" 
                    id="profile_src" 
                    name="profile_src" 
                    accept="image/*"
                    onchange="previewImage(event)"
                >
            </div>
        </div>

        {{-- First Name --}}
        <div>
            <label for="fname" class="text-gray-800 text-sm font-medium inline-block mb-2">First Name</label>
            <input type="text" class="form-input" id="fname" name="fname" required>
        </div>

        {{-- Last Name --}}
        <div>
            <label for="lname" class="text-gray-800 text-sm font-medium inline-block mb-2">Last Name</label>
            <input type="text" class="form-input" id="lname" name="lname" required>
        </div>

        {{-- Middle Name --}}
        <div>
            <label for="mname" class="text-gray-800 text-sm font-medium inline-block mb-2">Middle Name</label>
            <input type="text" class="form-input" id="mname" name="mname">
        </div>

        {{-- Contact No --}}
        <div>
            <label for="contact_no" class="text-gray-800 text-sm font-medium inline-block mb-2">Contact No</label>
            <input type="text" class="form-input" id="contact_no" name="contact_no">
        </div>

        {{-- Address --}}
        <div class="lg:col-span-3">
            <label for="address" class="text-gray-800 text-sm font-medium inline-block mb-2">Address</label>
            <textarea class="form-input w-full" id="address" name="address" rows="3"></textarea>
        </div>

        {{-- Submit --}}
        <div class="lg:col-span-3">
            <button class="btn bg-primary text-white" type="submit">
                <i class="ri-save-line me-1"></i> Save Patient
            </button>
        </div>

    </form>
</div>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile_preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
</script>
        </div>
    </div>
@endsection
