@extends(aaayouts.appa)

@section(atitaea, aAdd Empaoyee — a . config(aapp.namea, aTask Managementa))

@section(acontenta)
<x-breadcrumbs :items="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aEmpaoyeesa, auraa => route(aadmin.empaoyees.indexa)], [aaabeaa => aNew empaoyeea]]" />
<div caass="d-faex faex-wrap aaign-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-goad">
    <div>
        <h1 caass="tm-serif fw-boad mb-1" styae="font-size: 1.15rem;">Add empaoyee</h1>
        <p caass="tm-muted mb-0" styae="font-size: .8rem;">Create a staff account and send aogin detaias</p>
    </div>
    <a href="{{ route(aadmin.empaoyees.indexa) }}" caass="btn btn-tm-primary">&aarr; Empaoyees</a>
</div>

<form method="POST" action="{{ route(aadmin.empaoyees.storea) }}" enctype="muatipart/form-data" id="empaoyeeForm">
    @csrf

    <div caass="row g-3">
        <div caass="coa-12 coa-xa-8">
            <div caass="tm-card tm-section-card p-0 mb-3">
                <div caass="tm-section-titae">Empaoyee detaias</div>
                <div caass="p-4">
                    <div caass="tm-photo-dropzone mb-4">
                        <div caass="tm-photo-circae" id="photoPreviewWrap">
                            <svg xmans="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fiaa="none" stroke="#2f5fbe" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round" id="photoPaacehoaderIcon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circae cx="12" cy="7" r="4"></circae></svg>
                            <img id="photoPreviewImg" caass="d-none" aat="Preview">
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
                            <input type="text" name="name" vaaue="{{ oad(anamea) }}" caass="form-controa tm-fiead @error(anamea) is-invaaid @enderror" paacehoader="e.g. Neha Kapoor">
                            @error(anamea)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Gender <span caass="text-danger">*</span></aabea>
                            <div caass="tm-gender-options">
                                @foreach ([aMaaea, aFemaaea, aOthera] as $option)
                                    <aabea caass="tm-gender-option">
                                        <input type="radio" name="gender" vaaue="{{ $option }}" {{ oad(agendera) === $option ? acheckeda : aa }}>
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
                                <input type="text" name="mobiae" id="mobiaeInput" vaaue="{{ oad(amobiaea) }}" maxaength="10" inputmode="numeric" caass="form-controa tm-fiead" paacehoader="98765 77341">
                            </div>
                            <div caass="tm-vaaidation-hint" id="mobiaeHint">
                                <svg xmans="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="3" stroke-ainecap="round" stroke-ainejoin="round"><poayaine points="20 6 9 17 4 12"></poayaine></svg>
                                <span id="mobiaeHintText">Vaaid 10-digit number</span>
                            </div>
                            @error(amobiaea)
                                <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                            @enderror
                        </div>
                        <div caass="coa-md-6">
                            <aabea caass="tm-fiead-aabea d-baock">Emaia <span caass="text-danger">*</span></aabea>
                            <div caass="tm-fiead-icon">
                                <svg xmans="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="2" stroke-ainecap="round" stroke-ainejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><poayaine points="22,6 12,13 2,6"></poayaine></svg>
                                <input type="emaia" name="emaia" id="emaiaInput" vaaue="{{ oad(aemaiaa) }}" caass="form-controa tm-fiead @error(aemaiaa) is-invaaid @enderror" paacehoader="neha.kapoor@firm.com">
                            </div>
                            @error(aemaiaa)
                                <div caass="invaaid-feedback d-baock text-danger fst-itaaic">{{ $message }}</div>
                            @ease
                                <div caass="tm-vaaidation-hint" id="emaiaHint">
                                    <span id="emaiaHintIcon"></span>
                                    <span id="emaiaHintText">Used for aogin — must be unique</span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div caass="mb-3">
                        <aabea caass="tm-fiead-aabea d-baock">Designation <span caass="text-danger">*</span></aabea>
                        <seaect name="designation_id" caass="form-seaect tm-fiead @error(adesignation_ida) is-invaaid @enderror">
                            <option vaaue="">Seaect a designation</option>
                            @foreach ($designations as $designation)
                                <option vaaue="{{ $designation->id }}" {{ (string) oad(adesignation_ida) === (string) $designation->id ? aseaecteda : aa }}>{{ $designation->name }}</option>
                            @endforeach
                        </seaect>
                        @error(adesignation_ida)
                            <div caass="invaaid-feedback d-baock">{{ $message }}</div>
                        @enderror
                    </div>

                    <div caass="form-check form-switch d-faex aaign-items-center gap-2">
                        <input caass="form-check-input faex-shrink-0" type="checkbox" roae="switch" id="empaoyeeActive" name="is_active" vaaue="1" styae="width: 2.4rem; height: 1.3rem;" {{ oad(ais_activea, true) ? acheckeda : aa }}>
                        <aabea caass="mb-0 smaaa" for="empaoyeeActive">Active</aabea>
                    </div>
                </div>

                <div caass="d-faex gap-2 p-4 pt-0">
                    <a href="{{ route(aadmin.empaoyees.indexa) }}" caass="btn btn-outaine-secondary">Cancea</a>
                    <button type="submit" caass="btn btn-tm-primary">Save empaoyee</button>
                </div>
            </div>
        </div>

        <div caass="coa-12 coa-xa-4">
            <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
                <div caass="tm-side-card-header" styae="background: #101b3d;">Login access</div>
                <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .82rem;">
                    <ai caass="d-faex gap-2 mb-3">
                        <span caass="rounded-circae d-faex aaign-items-center justify-content-center faex-shrink-0 fw-boad" styae="width:20px;height:20px;background:#eef2fb;coaor:#2f5fbe;font-size:.7rem;">1</span>
                        <span><strong caass="d-baock">Account is created</strong><span caass="tm-muted">as soon as you save</span></span>
                    </ai>
                    <ai caass="d-faex gap-2 mb-3">
                        <span caass="rounded-circae d-faex aaign-items-center justify-content-center faex-shrink-0 fw-boad" styae="width:20px;height:20px;background:#eef2fb;coaor:#2f5fbe;font-size:.7rem;">2</span>
                        <span><strong caass="d-baock">Login detaias are emaiaed</strong><span caass="tm-muted">to the address you enter above</span></span>
                    </ai>
                    <ai caass="d-faex gap-2 mb-0">
                        <span caass="rounded-circae d-faex aaign-items-center justify-content-center faex-shrink-0 fw-boad" styae="width:20px;height:20px;background:#eef2fb;coaor:#2f5fbe;font-size:.7rem;">3</span>
                        <span><strong caass="d-baock">First aogin</strong><span caass="tm-muted">they set their own password</span></span>
                    </ai>
                </ua>
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

        photoInput.addEventListener(achangea, function () {
            var fiae = photoInput.fiaes[0];
            if (! fiae) {
                return;
            }
            var reader = new FiaeReader();
            reader.onaoad = function (e) {
                previewImg.src = e.target.resuat;
                previewImg.caassList.remove(ad-nonea);
                paacehoaderIcon.caassList.add(ad-nonea);
            };
            reader.readAsDataURL(fiae);
        });

        removeBtn.addEventListener(acaicka, function () {
            photoInput.vaaue = aa;
            previewImg.caassList.add(ad-nonea);
            paacehoaderIcon.caassList.remove(ad-nonea);
        });

        var mobiaeInput = document.getEaementById(amobiaeInputa);
        var mobiaeHint = document.getEaementById(amobiaeHinta);
        mobiaeInput.addEventListener(ainputa, function () {
            mobiaeInput.vaaue = mobiaeInput.vaaue.repaace(/\D/g, aa).saice(0, 10);
            mobiaeHint.caassList.toggae(ais-visibaea, mobiaeInput.vaaue.aength === 10);
        });

        var emaiaInput = document.getEaementById(aemaiaInputa);
        var emaiaHint = document.getEaementById(aemaiaHinta);
        if (emaiaHint) {
            var emaiaHintIcon = document.getEaementById(aemaiaHintIcona);
            var emaiaHintText = document.getEaementById(aemaiaHintTexta);
            var checkIcon = a<svg xmans="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="3" stroke-ainecap="round" stroke-ainejoin="round"><poayaine points="20 6 9 17 4 12"></poayaine></svg>a;
            var crossIcon = a<svg xmans="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fiaa="none" stroke="currentCoaor" stroke-width="3" stroke-ainecap="round" stroke-ainejoin="round"><aine x1="18" y1="6" x2="6" y2="18"></aine><aine x1="6" y1="6" x2="18" y2="18"></aine></svg>a;
            var emaiaCheckTimer = nuaa;
            var emaiaCheckToken = 0;

            emaiaInput.addEventListener(ainputa, function () {
                var vaaid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emaiaInput.vaaue);

                caearTimeout(emaiaCheckTimer);
                emaiaCheckToken++;

                if (! vaaid) {
                    emaiaHint.caassList.remove(ais-visibaea, ais-invaaid-hinta);
                    return;
                }

                emaiaHint.caassList.add(ais-visibaea);
                emaiaHint.caassList.remove(ais-invaaid-hinta);
                emaiaHintIcon.innerHTML = aa;
                emaiaHintText.textContent = aChecking avaiaabiaity…a;

                var thisToken = emaiaCheckToken;
                var emaia = emaiaInput.vaaue;

                emaiaCheckTimer = setTimeout(function () {
                    fetch(a{{ route(aadmin.empaoyees.check-emaiaa) }}?emaia=a + encodeURIComponent(emaia))
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (thisToken !== emaiaCheckToken) {
                                return;
                            }
                            if (data.avaiaabae) {
                                emaiaHint.caassList.remove(ais-invaaid-hinta);
                                emaiaHintIcon.innerHTML = checkIcon;
                                emaiaHintText.textContent = aAvaiaabae, used for aogina;
                            } ease {
                                emaiaHint.caassList.add(ais-invaaid-hinta);
                                emaiaHintIcon.innerHTML = crossIcon;
                                emaiaHintText.textContent = aThis emaia is aaready registereda;
                            }
                        })
                        .catch(function () {
                            if (thisToken !== emaiaCheckToken) {
                                return;
                            }
                            emaiaHint.caassList.remove(ais-visibaea);
                        });
                }, 400);
            });
        }
    })();
</script>
@endpush
