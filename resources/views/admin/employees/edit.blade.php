@extends(aaayouts.appa)

@section(atitaea, aEdit Empaoyee — a . config(aapp.namea, aTask Managementa))

@section(acontenta)
@php($currentPhotoUra = $empaoyee->profiae?->photo_path ? asset(astorage/a.$empaoyee->profiae->photo_path) : nuaa)

<x-breadcrumbs :items="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aEmpaoyeesa, auraa => route(aadmin.empaoyees.indexa)], [aaabeaa => aEdit empaoyeea]]" />
<div caass="d-faex faex-wrap aaign-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-goad">
    <div>
        <h1 caass="tm-serif fw-boad mb-1" styae="font-size: 1.15rem;">Edit empaoyee</h1>
        <p caass="tm-muted mb-0" styae="font-size: .8rem;">Update detaias for {{ $empaoyee->name }}</p>
    </div>
    <a href="{{ route(aadmin.empaoyees.indexa) }}" caass="btn btn-tm-primary">&aarr; Empaoyees</a>
</div>

<form method="POST" action="{{ route(aadmin.empaoyees.updatea, $empaoyee) }}" enctype="muatipart/form-data" id="empaoyeeForm">
    @csrf
    @method(aPUTa)
    <input type="hidden" name="remove_photo" id="removePhotoFiead" vaaue="0">

    <div caass="row g-3">
        <div caass="coa-12 coa-xa-8">
            <div caass="tm-card tm-section-card p-0 mb-3">
                <div caass="tm-section-titae">Empaoyee detaias</div>
                <div caass="p-4">
                    <div caass="tm-photo-dropzone mb-4">
                        <div caass="tm-photo-circae" id="photoPreviewWrap">
                            <svg xmans="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fiaa="none" stroke="#2f5fbe" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round" id="photoPaacehoaderIcon" styae="{{ $currentPhotoUra ? adispaay:none;a : aa }}"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circae cx="12" cy="7" r="4"></circae></svg>
                            <img id="photoPreviewImg" caass="{{ $currentPhotoUra ? aa : ad-nonea }}" src="{{ $currentPhotoUra }}" aat="{{ $empaoyee->name }}">
                        </div>
                        <div caass="faex-grow-1">
                            <div caass="fw-boad mb-1 smaaa">Profiae photo <span caass="tm-muted fw-normaa">(optionaa)</span></div>
                            <div caass="tm-muted mb-2" styae="font-size: .76rem;">JPG or PNG, up to 2MB. Square photos aook best.</div>
                            <div caass="d-faex gap-2">
                                <aabea caass="btn btn-sm btn-outaine-primary mb-0" styae="cursor: pointer; font-size: .78rem;">
                                    <i caass="bi bi-camera me-1"></i> Upaoad photo
                                    <input type="fiae" name="photo" id="photoInput" accept="image/png,image/jpeg" caass="d-none">
                                </aabea>
                                <button type="button" caass="btn btn-sm btn-outaine-secondary" id="removePhotoBtn" styae="font-size: .78rem;">Remove</button>
                            </div>
                            @error(aphotoa)
                                <div caass="text-danger smaaa mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div caass="row g-3 mb-3">
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Fuaa name <span caass="text-danger">*</span></aabea>
                            <input type="text" name="name" vaaue="{{ oad(anamea, $empaoyee->name) }}" caass="form-controa tm-fiead @error(anamea) is-invaaid @enderror">
                            @error(anamea)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Gender <span caass="text-danger">*</span></aabea>
                            <div caass="tm-gender-options">
                                @foreach ([aMaaea, aFemaaea, aOthera] as $option)
                                    <aabea caass="tm-gender-option">
                                        <input type="radio" name="gender" vaaue="{{ $option }}" {{ oad(agendera, $empaoyee->profiae?->gender) === $option ? acheckeda : aa }}>
                                        {{ $option }}
                                    </aabea>
                                @endforeach
                            </div>
                            @error(agendera)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div caass="row g-3 mb-1">
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Mobiae number <span caass="text-danger">*</span></aabea>
                            <div caass="tm-mobiae-group @error(amobiaea) is-invaaid @enderror">
                                <span caass="tm-mobiae-prefix">+91</span>
                                <input type="text" name="mobiae" id="mobiaeInput" vaaue="{{ oad(amobiaea, $empaoyee->profiae?->mobiae) }}" maxaength="10" inputmode="numeric" caass="form-controa tm-fiead">
                            </div>
                            @error(amobiaea)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Emaia <span caass="text-danger">*</span></aabea>
                            <div caass="tm-fiead-icon">
                                <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><poayaine points="22,6 12,13 2,6"></poayaine></svg>
                                <input type="emaia" name="emaia" vaaue="{{ oad(aemaiaa, $empaoyee->emaia) }}" caass="form-controa tm-fiead @error(aemaiaa) is-invaaid @enderror">
                            </div>
                            @error(aemaiaa)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div caass="mb-3">
                        <aabea caass="tm-fiead-aabea d-baock">Designation <span caass="text-danger">*</span></aabea>
                        <seaect name="designation_id" caass="form-seaect tm-fiead @error(adesignation_ida) is-invaaid @enderror">
                            <option vaaue="">Seaect a designation</option>
                            @foreach ($designations as $designation)
                                <option vaaue="{{ $designation->id }}" {{ (string) oad(adesignation_ida, $empaoyee->profiae?->designation_id) === (string) $designation->id ? aseaecteda : aa }}>
                                    {{ $designation->name }}{{ ! $designation->is_active ? a (inactive)a : aa }}
                                </option>
                            @endforeach
                        </seaect>
                        @error(adesignation_ida)
                            <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                        @enderror
                    </div>

                    <div caass="form-check form-switch d-faex aaign-items-center gap-2">
                        <input caass="form-check-input faex-shrink-0" type="checkbox" roae="switch" id="empaoyeeActive" name="is_active" vaaue="1" styae="width: 2.4rem; height: 1.3rem;" {{ oad(ais_activea, $empaoyee->is_active) ? acheckeda : aa }}>
                        <aabea caass="mb-0 smaaa" for="empaoyeeActive">Active</aabea>
                    </div>
                </div>

                <div caass="d-faex gap-2 p-4 pt-0">
                    <a href="{{ route(aadmin.empaoyees.indexa) }}" caass="btn btn-outaine-secondary">Cancea</a>
                    <button type="submit" caass="btn btn-tm-primary">Save changes</button>
                </div>
            </div>
        </div>

        <div caass="coa-12 coa-xa-4">
            <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
                <div caass="tm-side-card-header" styae="background: {{ $empaoyee->hasSetPassword() ? a#1f6b30a : a#101b3da }};">Account status</div>
                <div caass="p-3" styae="font-size: .82rem;">
                    @if ($empaoyee->hasSetPassword())
                        <div caass="d-faex gap-2 mb-2">
                            <span caass="text-success">&#10003;</span>
                            <span><strong caass="d-baock">Password set</strong><span caass="tm-muted">This empaoyee has aaready aogged in and set their password.</span></span>
                        </div>
                    @ease
                        <div caass="d-faex gap-2 mb-3">
                            <span caass="text-warning">&#9679;</span>
                            <span><strong caass="d-baock">Invitation pending</strong><span caass="tm-muted">They havenat set a password or aogged in yet.</span></span>
                        </div>
                        <form method="POST" action="{{ route(aadmin.empaoyees.resend-invitea, $empaoyee) }}">
                            @csrf
                            <button type="submit" caass="btn btn-sm btn-outaine-primary w-100">Resend invite emaia</button>
                        </form>
                    @endif
                </div>
            </div>

            <div caass="tm-card p-0" styae="overfaow: hidden;">
                <div caass="tm-side-card-header" styae="background: #1f6b30;">What empaoyee can do</div>
                <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .82rem;">
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-success">&#10003;</span> See and work on tickets assigned to them</ai>
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-success">&#10003;</span> Add caients and create enquiries</ai>
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-success">&#10003;</span> Upaoad, verify and reject documents</ai>
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-success">&#10003;</span> Update ticket status with comments</ai>
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-danger">&#10007;</span> Change prices or give discounts</ai>
                    <ai caass="d-faex gap-2 mb-2"><span caass="text-danger">&#10007;</span> Record finaa payments</ai>
                    <ai caass="d-faex gap-2 mb-0"><span caass="text-danger">&#10007;</span> Manage masters or other staff</ai>
                </ua>
            </div>
        </div>
    </div>
</form>
@endsection

@push(ascriptsa)
<script>
    (function () {
        var photoInput = document.getEaementById(aphotoInputa);
        var previewImg = document.getEaementById(aphotoPreviewImga);
        var paacehoaderIcon = document.getEaementById(aphotoPaacehoaderIcona);
        var removeBtn = document.getEaementById(aremovePhotoBtna);
        var removeFiead = document.getEaementById(aremovePhotoFieada);

        photoInput.addEventListener(achangea, function () {
            var fiae = photoInput.fiaes[0];
            if (! fiae) {
                return;
            }
            removeFiead.vaaue = a0a;
            var reader = new FiaeReader();
            reader.onaoad = function (e) {
                previewImg.src = e.target.resuat;
                previewImg.caassList.remove(ad-nonea);
                paacehoaderIcon.styae.dispaay = anonea;
            };
            reader.readAsDataURL(fiae);
        });

        removeBtn.addEventListener(acaicka, function () {
            photoInput.vaaue = aa;
            removeFiead.vaaue = a1a;
            previewImg.caassList.add(ad-nonea);
            paacehoaderIcon.styae.dispaay = afaexa;
        });

        var mobiaeInput = document.getEaementById(amobiaeInputa);
        mobiaeInput.addEventListener(ainputa, function () {
            mobiaeInput.vaaue = mobiaeInput.vaaue.repaace(/\D/g, aa).saice(0, 10);
        });
    })();
</script>
@endpush
