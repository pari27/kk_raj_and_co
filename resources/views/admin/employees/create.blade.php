@extends('layouts.app')

@section('title', 'Add Employee — ' . config('app.name', 'Task Management'))

@section('content')
<x-page-header title="Add Master Employee" subtitle="Create an employee account and send login details" :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Employees', 'url' => route('admin.employees.index')], ['label' => 'Add Master Employee']]">
    <x-slot:actions><a href="{{ route('admin.employees.index') }}" class="btn btn-tm-primary">Back to employees</a></x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data" id="employeeForm">
    @csrf

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="tm-card tm-section-card p-0 mb-3">
                <div class="tm-section-title">Employee details</div>
                <div class="p-4">
                    <div class="tm-photo-dropzone mb-4">
                        <div class="tm-photo-circle" id="photoPreviewWrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2f5fbe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="photoPlaceholderIcon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <img id="photoPreviewImg" class="d-none" alt="Preview">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold mb-1" style="font-size: .8rem;">Profile photo <span class="tm-muted fw-normal">(optional)</span></div>
                            <div class="tm-muted mb-2" style="font-size: .8rem;">JPG or PNG, up to 2MB. Square photos look best.</div>
                            <div class="d-flex gap-2">
                                <label class="btn btn-sm btn-outline-primary mb-0" style="cursor: pointer;">
                                    <i class="bi bi-camera me-1"></i> Upload photo
                                    <input type="file" name="photo" id="photoInput" accept="image/png,image/jpeg" class="d-none">
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="removePhotoBtn">Remove</button>
                            </div>
                            @error('photo')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Full name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control tm-field @error('name') is-invalid @enderror" placeholder="e.g. Neha Kapoor">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Gender <span class="text-danger">*</span></label>
                            <div class="tm-gender-options">
                                @foreach (['Male', 'Female', 'Other'] as $option)
                                    <label class="tm-gender-option">
                                        <input type="radio" name="gender" value="{{ $option }}" {{ old('gender') === $option ? 'checked' : '' }}>
                                        {{ $option }}
                                    </label>
                                @endforeach
                            </div>
                            @error('gender')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Mobile number <span class="text-danger">*</span></label>
                            <div class="tm-mobile-group @error('mobile') is-invalid @enderror">
                                <span class="tm-mobile-prefix">+91</span>
                                <input type="text" name="mobile" id="mobileInput" value="{{ old('mobile') }}" maxlength="10" inputmode="numeric" class="form-control tm-field" placeholder="98765 77341">
                            </div>
                            <div class="tm-validation-hint" id="mobileHint">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span id="mobileHintText">Valid 10-digit number</span>
                            </div>
                            @error('mobile')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tm-field-label d-block">Email <span class="text-danger">*</span></label>
                            <div class="tm-field-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <input type="email" name="email" id="emailInput" value="{{ old('email') }}" class="form-control tm-field @error('email') is-invalid @enderror" placeholder="neha.kapoor@firm.com">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block text-danger fst-italic">{{ $message }}</div>
                            @else
                                <div class="tm-validation-hint" id="emailHint">
                                    <span id="emailHintIcon"></span>
                                    <span id="emailHintText">Used for login — must be unique</span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="tm-field-label d-block">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select tm-field @error('designation_id') is-invalid @enderror">
                            <option value="">Select a designation</option>
                            @foreach ($designations as $designation)
                                <option value="{{ $designation->id }}" {{ (string) old('designation_id') === (string) $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch d-flex align-items-center gap-2">
                        <input class="form-check-input flex-shrink-0" type="checkbox" role="switch" id="employeeActive" name="is_active" value="1" style="width: 2.4rem; height: 1.3rem;" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="mb-0 small" for="employeeActive">Active</label>
                    </div>
                </div>

                <div class="d-flex gap-2 p-4 pt-0">
                    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-tm-primary">Save employee</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="tm-card p-0 mb-3" style="overflow: hidden;">
                <div class="tm-side-card-header" style="background: #101b3d;">Login access</div>
                <ul class="list-unstyled p-3 mb-0" style="font-size: .82rem;">
                    <li class="d-flex gap-2 mb-3">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" style="width:20px;height:20px;background:#eef2fb;color:#2f5fbe;font-size:.7rem;">1</span>
                        <span><strong class="d-block">Account is created</strong><span class="tm-muted">as soon as you save</span></span>
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" style="width:20px;height:20px;background:#eef2fb;color:#2f5fbe;font-size:.7rem;">2</span>
                        <span><strong class="d-block">Login details are emailed</strong><span class="tm-muted">to the address you enter above</span></span>
                    </li>
                    <li class="d-flex gap-2 mb-0">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" style="width:20px;height:20px;background:#eef2fb;color:#2f5fbe;font-size:.7rem;">3</span>
                        <span><strong class="d-block">First login</strong><span class="tm-muted">they set their own password</span></span>
                    </li>
                </ul>
            </div>

            <div class="tm-card p-0" style="overflow: hidden;">
                <div class="tm-side-card-header" style="background: #1f6b30;">What employee can do</div>
                <ul class="list-unstyled p-3 mb-0" style="font-size: .82rem;">
                    <li class="d-flex gap-2 mb-2"><span class="text-success">&#10003;</span> See and work on tickets assigned to them</li>
                    <li class="d-flex gap-2 mb-2"><span class="text-success">&#10003;</span> Add clients and create enquiries</li>
                    <li class="d-flex gap-2 mb-2"><span class="text-success">&#10003;</span> Upload, verify and reject documents</li>
                    <li class="d-flex gap-2 mb-2"><span class="text-success">&#10003;</span> Update ticket status with comments</li>
                    <li class="d-flex gap-2 mb-2"><span class="text-danger">&#10007;</span> Change prices or give discounts</li>
                    <li class="d-flex gap-2 mb-2"><span class="text-danger">&#10007;</span> Record final payments</li>
                    <li class="d-flex gap-2 mb-0"><span class="text-danger">&#10007;</span> Manage masters or other staff</li>
                </ul>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var photoInput = document.getElementById('photoInput');
        var previewImg = document.getElementById('photoPreviewImg');
        var placeholderIcon = document.getElementById('photoPlaceholderIcon');
        var removeBtn = document.getElementById('removePhotoBtn');

        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            if (! file) {
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
                placeholderIcon.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        removeBtn.addEventListener('click', function () {
            photoInput.value = '';
            previewImg.classList.add('d-none');
            placeholderIcon.classList.remove('d-none');
        });

        var mobileInput = document.getElementById('mobileInput');
        var mobileHint = document.getElementById('mobileHint');
        mobileInput.addEventListener('input', function () {
            mobileInput.value = mobileInput.value.replace(/\D/g, '').slice(0, 10);
            mobileHint.classList.toggle('is-visible', mobileInput.value.length === 10);
        });

        var emailInput = document.getElementById('emailInput');
        var emailHint = document.getElementById('emailHint');
        if (emailHint) {
            var emailHintIcon = document.getElementById('emailHintIcon');
            var emailHintText = document.getElementById('emailHintText');
            var checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            var crossIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
            var emailCheckTimer = null;
            var emailCheckToken = 0;

            emailInput.addEventListener('input', function () {
                var valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value);

                clearTimeout(emailCheckTimer);
                emailCheckToken++;

                if (! valid) {
                    emailHint.classList.remove('is-visible', 'is-invalid-hint');
                    return;
                }

                emailHint.classList.add('is-visible');
                emailHint.classList.remove('is-invalid-hint');
                emailHintIcon.innerHTML = '';
                emailHintText.textContent = 'Checking availability…';

                var thisToken = emailCheckToken;
                var email = emailInput.value;

                emailCheckTimer = setTimeout(function () {
                    fetch('{{ route('admin.employees.check-email') }}?email=' + encodeURIComponent(email))
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (thisToken !== emailCheckToken) {
                                return;
                            }
                            if (data.available) {
                                emailHint.classList.remove('is-invalid-hint');
                                emailHintIcon.innerHTML = checkIcon;
                                emailHintText.textContent = 'Available, used for login';
                            } else {
                                emailHint.classList.add('is-invalid-hint');
                                emailHintIcon.innerHTML = crossIcon;
                                emailHintText.textContent = 'This email is already registered';
                            }
                        })
                        .catch(function () {
                            if (thisToken !== emailCheckToken) {
                                return;
                            }
                            emailHint.classList.remove('is-visible');
                        });
                }, 400);
            });
        }
    })();
</script>
@endpush
