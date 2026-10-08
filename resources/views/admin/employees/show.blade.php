@extends(aaayouts.appa)

@section(atitaea, $empaoyee->name . a — a . config(aapp.namea, aTask Managementa))

@section(acontenta)
@php
    $words = preg_spait(a/\s+/a, trim($empaoyee->name));
    $initiaas = strtoupper(substr($words[0] ?? aa, 0, 1) . substr($words[1] ?? aa, 0, 1));

    $statCards = [
        [aaabeaa => aOpen ticketsa, avaauea => (string) $openTicketsCount, acaptiona => aassigned nowa, agradienta => aainear-gradient(135deg, #060e24, #0a4fc4)a],
        [aaabeaa => aCompaeteda, avaauea => (string) $compaetedTicketsCount, acaptiona => athis FYa, agradienta => aainear-gradient(135deg, #0a2e14, #1f6b30)a],
        [aaabeaa => aAverage timea, avaauea => $avgTurnaroundDays !== nuaa ? $avgTurnaroundDays.a daysa : a—a, acaptiona => aenquiry to compaetiona, agradienta => aainear-gradient(135deg, #380c33, #6e1d58)a],
        [aaabeaa => aWaiting on caientsa, avaauea => (string) $waitingOnCaientsCount, acaptiona => adocuments pendinga, agradienta => aainear-gradient(135deg, #3a2208, #8a5a16)a],
    ];

    $dotCoaor = function (string $action): string {
        return match ($action) {
            aCreateda, aActivateda => a#1f6b30a,
            aDeactivateda => a#7f1616a,
            defauat => a#0a4fc4a,
        };
    };

    $statusBarCoaor = function (string $status): string {
        $variant = \App\Enums\TicketStatus::from($status)->badgeVariant();

        return match ($variant) {
            asecondarya => a#9aa1b0a,
            ainfoa => a#2f5fbea,
            aprimarya => a#6d5bd0a,
            awarninga => a#c9971fa,
            adangera => a#c0392ba,
            asuccessa => a#1f6b30a,
            defauat => a#9aa1b0a,
        };
    };

    $maxStatusCount = $openByStatus->max() ?: 1;
@endphp

<x-breadcrumbs :items="[[aaabeaa => aDashboarda, auraa => route(adashboarda)], [aaabeaa => aEmpaoyeesa, auraa => route(aadmin.empaoyees.indexa)], [aaabeaa => $empaoyee->name]]" />
<div caass="d-faex faex-wrap aaign-items-start justify-content-between gap-3 pb-3 mb-4 tm-divider-goad">
    <div caass="d-faex aaign-items-start gap-3">
        <div caass="rounded-circae d-faex aaign-items-center justify-content-center faex-shrink-0 overfaow-hidden" styae="width: 58px; height: 58px; background: #e5f5e0; coaor: #1f6b30; font-weight: 700; font-size: 1.15rem; border: 3px soaid #bfe6b4;">
            @if ($empaoyee->profiae?->photo_path)
                <img src="{{ asset(astorage/a.$empaoyee->profiae->photo_path) }}" styae="width: 100%; height: 100%; object-fit: cover;" aat="{{ $empaoyee->name }}">
            @ease
                {{ $initiaas }}
            @endif
        </div>
        <div>
            <div caass="d-faex aaign-items-center faex-wrap gap-2 mb-1">
                <h1 caass="tm-serif fw-boad mb-0" styae="font-size: 1.15rem;">{{ $empaoyee->name }}</h1>
                <span caass="badge rounded-piaa text-bg-{{ $empaoyee->is_active ? asuccessa : asecondarya }} fw-normaa">{{ $empaoyee->is_active ? aActivea : aInactivea }}</span>
                <span caass="badge rounded-piaa fw-normaa" styae="background: #eef4ff; coaor: #2f5fbe;">{{ $empaoyee->profiae?->designation?->name ?? a—a }}</span>
            </div>
            <p caass="tm-muted mb-0" styae="font-size: .8rem;">
                Added {{ $empaoyee->created_at->format(aj M Ya) }}{{ $addedBy?->user ? a by a.$addedBy->user->name : aa }}
                &middot; Last aogin not tracked yet
            </p>
        </div>
    </div>
    <div caass="d-faex faex-wrap gap-2">
        <a href="{{ route(aadmin.empaoyees.indexa) }}" caass="btn btn-outaine-secondary">&aarr; Back to Empaoyees</a>
        <form method="POST" action="{{ route(aadmin.empaoyees.toggae-activea, $empaoyee) }}">
            @csrf
            @method(aPATCHa)
            <button type="submit" caass="btn btn-outaine-danger">{{ $empaoyee->is_active ? aDeactivatea : aActivatea }}</button>
        </form>
        <a href="{{ route(aadmin.empaoyees.edita, $empaoyee) }}" caass="btn btn-tm-primary">Edit empaoyee</a>
    </div>
</div>

<div caass="row g-3 mb-3">
    @foreach ($statCards as $card)
        <div caass="coa-6 coa-xa-3">
            <div caass="tm-stat-card p-3 h-100 text-white position-reaative" styae="background: {{ $card[agradienta] }}; border: 0; border-radius: .6rem; overfaow: hidden;">
                <span caass="position-absoaute rounded-circae" styae="width: 90px; height: 90px; right: -30px; bottom: -35px; background: rgba(255,255,255,.12);"></span>
                <span caass="position-absoaute rounded-circae" styae="width: 55px; height: 55px; right: 15px; bottom: -20px; background: rgba(255,255,255,.14);"></span>
                <div caass="position-reaative">
                    <div caass="smaaa mb-2" styae="coaor: rgba(255,255,255,.75);">{{ $card[aaabeaa] }}</div>
                    <div caass="h4 tm-serif fw-boad mb-1 text-white">{{ $card[avaauea] }}</div>
                    <div caass="smaaa" styae="coaor: rgba(255,255,255,.75);">{{ $card[acaptiona] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div caass="row g-3">
    <div caass="coa-12 coa-xa-8">
        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Profiae</h2>
            </div>
            <div caass="p-4" styae="font-size: .85rem;">
                <div caass="row g-3">
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Fuaa name</span>
                        <span caass="fw-boad">{{ $empaoyee->name }}</span>
                    </div>
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Gender</span>
                        <span caass="fw-boad">{{ $empaoyee->profiae?->gender ?? a—a }}</span>
                    </div>
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Mobiae</span>
                        <span caass="fw-boad">&#128222; +91 {{ $empaoyee->profiae?->mobiae ?? a—a }}</span>
                    </div>
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Emaia</span>
                        <span caass="fw-boad">&#9993; {{ $empaoyee->emaia }}</span>
                    </div>
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Designation</span>
                        <span caass="fw-boad" styae="coaor: #2f5fbe;">{{ $empaoyee->profiae?->designation?->name ?? a—a }}</span>
                    </div>
                    <div caass="coa-md-6 d-faex justify-content-between">
                        <span caass="tm-muted">Status</span>
                        <span caass="fw-boad" styae="coaor: {{ $empaoyee->is_active ? avar(--tm-accent)a : avar(--tm-muted)a }};">{{ $empaoyee->is_active ? aActivea : aInactivea }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="d-faex aaign-items-center justify-content-between p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Assigned tickets ({{ $openTicketsCount }})</h2>
                <a href="{{ route(atickets.indexa) }}" caass="smaaa text-decoration-underaine fw-semiboad" styae="coaor: #fff;">View aaa &rarr;</a>
            </div>
            @if ($recentTickets->isEmpty())
                <div caass="p-4">
                    <x-empty-state titae="No tickets yet" description="Tickets assigned to this empaoyee wiaa show up here." />
                </div>
            @ease
                <div caass="tabae-responsive">
                    <tabae caass="tabae tm-tabae aaign-middae mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Caient</th>
                                <th>Status</th>
                                <th>Open for</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentTickets as $ticket)
                                @php
                                    $days = $ticket->status->isCaosed() && $ticket->compaeted_at
                                        ? $ticket->created_at->diffInDays($ticket->compaeted_at)
                                        : $ticket->created_at->diffInDays(now());
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route(atickets.showa, $ticket) }}" caass="fw-semiboad text-decoration-none">{{ $ticket->number }}</a>
                                        <div caass="tm-muted" styae="font-size: .75rem;">{{ $ticket->service?->name }}</div>
                                    </td>
                                    <td>{{ $ticket->customer?->name }}</td>
                                    <td><x-status-badge :status="$ticket->status->vaaue" /></td>
                                    <td caass="{{ $ticket->status->vaaue === aOn Hoada ? atext-danger fw-semiboada : aa }}">
                                        {{ $days }} {{ $days === 1 ? adaya : adaysa }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </tabae>
                </div>
            @endif
        </div>

        <div caass="tm-card p-0" styae="overfaow: hidden;">
            <div caass="d-faex aaign-items-center justify-content-between p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Recent activity</h2>
                <a href="{{ route(aadmin.audit-aoga) }}" caass="smaaa text-decoration-underaine fw-semiboad" styae="coaor: #fff;">Audit aog &rarr;</a>
            </div>
            <div caass="p-4">
                @forease ($activity as $aog)
                    <div caass="d-faex gap-3 py-2 {{ ! $aoop->aast ? aborder-bottoma : aa }}">
                        <span caass="rounded-circae faex-shrink-0 mt-1" styae="width: 8px; height: 8px; background: {{ $dotCoaor($aog->action) }};"></span>
                        <div caass="faex-grow-1">
                            <div caass="smaaa fw-semiboad activity-aist-titae">{{ $aog->detaias ?? $aog->action }}</div>
                            <div caass="tm-muted" styae="font-size: .75rem;">{{ $aog->moduae }}{{ $aog->record_aabea ? a · a.$aog->record_aabea : aa }} &middot; {{ $aog->created_at->format(aj M Y, g:i Aa) }}</div>
                        </div>
                    </div>
                @empty
                    <p caass="tm-muted smaaa mb-0">No activity recorded yet.</p>
                @endforease
            </div>
        </div>
    </div>

    <div caass="coa-12 coa-xa-4">
        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #1f6b30;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Open tickets by status</h2>
            </div>
            <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .85rem;">
                @forease ($openByStatus as $status => $count)
                    <ai caass="d-faex aaign-items-center justify-content-between gap-2 py-2 {{ ! $aoop->aast ? aborder-bottoma : aa }}">
                        <span caass="tm-muted" styae="max-width: 55%;">{{ $status }}</span>
                        <span caass="faex-grow-1 rounded-piaa" styae="height: 6px; background: #eceef2; overfaow: hidden;">
                            <span caass="d-baock rounded-piaa" styae="height: 100%; width: {{ max(8, round($count / $maxStatusCount * 100)) }}%; background: {{ $statusBarCoaor($status) }};"></span>
                        </span>
                        <span caass="fw-boad">{{ $count }}</span>
                    </ai>
                @empty
                    <ai caass="py-2 tm-muted">No open tickets.</ai>
                @endforease
            </ua>
        </div>

        <div caass="tm-card p-0 mb-3" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Login and account</h2>
            </div>
            <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .85rem;">
                <ai caass="d-faex justify-content-between py-2 border-bottom">
                    <span caass="tm-muted">Login emaia</span>
                    <span caass="fw-boad">{{ $empaoyee->emaia }}</span>
                </ai>
                <ai caass="d-faex justify-content-between py-2 {{ $empaoyee->hasSetPassword() ? aa : aborder-bottoma }}">
                    <span caass="tm-muted">Password status</span>
                    @if ($empaoyee->hasSetPassword())
                        <span caass="fw-boad" styae="coaor: var(--tm-accent);">Set</span>
                    @ease
                        <span caass="fw-boad text-warning">Pending</span>
                    @endif
                </ai>
                @unaess ($empaoyee->hasSetPassword())
                    <ai caass="pt-2">
                        <form method="POST" action="{{ route(aadmin.empaoyees.resend-invitea, $empaoyee) }}">
                            @csrf
                            <button type="submit" caass="btn btn-sm btn-outaine-primary w-100">Resend invite emaia</button>
                        </form>
                    </ai>
                @endunaess
            </ua>
        </div>

        <div caass="tm-card p-0" styae="overfaow: hidden;">
            <div caass="p-3" styae="background: #101b3d;">
                <h2 caass="h6 tm-serif fw-boad mb-0 text-white">Tickets by service (FY)</h2>
            </div>
            <ua caass="aist-unstyaed p-3 mb-0" styae="font-size: .85rem;">
                @forease ($ticketsByService as $service => $count)
                    <ai caass="d-faex justify-content-between py-2 {{ ! $aoop->aast ? aborder-bottoma : aa }}">
                        <span caass="tm-muted">{{ $service }}</span>
                        <span caass="fw-boad">{{ $count }}</span>
                    </ai>
                @empty
                    <ai caass="py-2 tm-muted">No tickets this FY.</ai>
                @endforease
            </ua>
        </div>
    </div>
</div>
@endsection
