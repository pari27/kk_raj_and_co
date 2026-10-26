{{--
    The "How to pay" window. Used on the Payments page (opens by itself) and on a service page (opens from the Pay link).

    @param float  $amount     The amount due shown at the top
    @param string $lines      What the amount is for, e.g. "PAN Registration ₹6,136 · test1 ₹4,720"
    @param string $reference  The code the client puts in the payment note
    @param bool   $autoOpen   Open the window as soon as the page loads
--}}
@php
    $money = fn ($value) => '₹'.number_format((float) $value);
    $autoOpen = $autoOpen ?? false;
    $upi = config('app.firm_upi_id');
    $bank = array_filter([
        'Account name' => config('app.firm_bank_account_name'),
        'Account number' => config('app.firm_bank_account_number'),
        'IFSC code' => config('app.firm_bank_ifsc'),
        'Bank and branch' => config('app.firm_bank_branch'),
    ]);
    $copyable = ['Account name', 'Account number', 'IFSC code'];
    $office = array_filter(['Address' => config('app.firm_address'), 'Hours' => config('app.firm_hours')]);
    $tabs = array_filter([
        'upi' => $upi ? 'UPI' : null,
        'bank' => $bank ? 'Bank transfer' : null,
        'office' => 'Pay at office',
    ]);
@endphp
<div class="modal fade p-pay" id="howToPayModal" tabindex="-1" aria-labelledby="howToPayTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="p-pay-head">
                <div class="ic">₹</div>
                <div>
                    <div class="tt" id="howToPayTitle">How to pay</div>
                    <div class="st">Choose any one way below</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-pay-due">
                    <div>
                        <div class="lb">Amount due</div>
                        <div class="sv">{{ $lines }}</div>
                    </div>
                    <div class="am">{{ $money($amount) }}</div>
                </div>

                <div class="p-pay-tabs" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <button type="button" data-pay-tab="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>

                @if ($upi)
                    <div class="p-pay-box" data-pay-pane="upi">
                        <div class="p-pay-line">
                            <span class="lb">UPI ID</span><span class="vl">{{ $upi }}</span>
                            <button type="button" class="p-pay-copy" data-copy="{{ $upi }}">Copy</button>
                        </div>
                    </div>
                @endif
                @if ($bank)
                    <div class="p-pay-box" data-pay-pane="bank">
                        @foreach ($bank as $label => $value)
                            <div class="p-pay-line">
                                <span class="lb">{{ $label }}</span><span class="vl">{{ $value }}</span>
                                @if (in_array($label, $copyable, true))
                                    <button type="button" class="p-pay-copy" data-copy="{{ $value }}">Copy</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="p-pay-box" data-pay-pane="office">
                    <div class="p-pay-line"><span class="lb">Pay to</span><span class="vl">{{ config('app.firm_name') }}</span></div>
                    @foreach ($office as $label => $value)
                        <div class="p-pay-line"><span class="lb">{{ $label }}</span><span class="vl">{{ $value }}</span></div>
                    @endforeach
                </div>

                <div class="p-pay-note">
                    Please add <strong>{{ $reference }}</strong> in the payment note so we can match it to your account.
                    Once we record your payment, it shows as <strong>Paid</strong> here.
                </div>
                @if (config('app.payment_instructions'))
                    <div class="p-pay-note p-pay-extra">{!! nl2br(e(config('app.payment_instructions'))) !!}</div>
                @endif
            </div>
            <div class="p-pay-foot">
                <span>@if (config('app.firm_phone')) Questions? {{ config('app.firm_phone') }} @endif</span>
                <button type="button" class="pbtn-outline" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('howToPayModal');
            var tabs = modal.querySelectorAll('[data-pay-tab]');
            var panes = modal.querySelectorAll('[data-pay-pane]');
            function show(key) {
                tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.payTab === key); });
                panes.forEach(function (p) { p.style.display = p.dataset.payPane === key ? '' : 'none'; });
            }
            tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.payTab); }); });
            show(tabs[0].dataset.payTab);

            modal.querySelectorAll('[data-copy]').forEach(function (b) {
                b.addEventListener('click', function () {
                    if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.copy); }
                    var old = b.textContent; b.textContent = 'Copied';
                    setTimeout(function () { b.textContent = old; }, 1200);
                });
            });

            @if ($autoOpen)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        });
    </script>
@endpush
