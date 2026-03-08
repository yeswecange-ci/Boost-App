@extends('layouts.app')

@section('page-title', 'Campagne #' . $campaign->id)
@section('page-subtitle', $campaign->campaign_name)

@section('content')

<div style="max-width:860px; margin:0 auto; display:flex; flex-direction:column; gap:1.5rem;">

    {{-- Statut + IDs Meta --}}
    <div class="card">
        <div class="card-header" style="justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-layer-group" style="color:var(--color-primary);"></i>
                Statut d'exécution
            </div>
            <span class="badge-status {{ $campaign->status_class }}">{{ $campaign->status_label }}</span>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Créée le</div>
                    <div style="font-weight:600;">{{ $campaign->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Lancée le</div>
                    <div style="font-weight:600;">{{ $campaign->launched_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
                @if($campaign->meta_campaign_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Campaign ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_campaign_id }}</div>
                </div>
                @endif
                @if($campaign->meta_adset_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">AdSet ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_adset_id }}</div>
                </div>
                @endif
                @if($campaign->meta_ad_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Ad ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_ad_id }}</div>
                </div>
                @endif
            </div>
            @if($campaign->error_message)
            <div class="alert alert-danger" style="margin-top:1rem;">
                <i class="fas fa-exclamation-triangle"></i> {{ $campaign->error_message }}
            </div>
            @endif
        </div>
    </div>

    {{-- Détails campagne --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-bullhorn" style="color:var(--color-primary);"></i>
            Campagne
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                @foreach([
                    'Nom'              => $campaign->campaign_name,
                    'Objectif'         => $campaign->campaign_objective,
                    'Catégorie spéc.'  => $campaign->special_ad_categories,
                    'Statut initial'   => $campaign->campaign_status,
                    'Campagne existante' => $campaign->existing_campaign_id ?? '—',
                    'Post ID'          => $campaign->post_id,
                ] as $label => $val)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">{{ $label }}</div>
                    <div style="font-weight:500; font-family:{{ in_array($label, ['Post ID','Campagne existante']) ? 'monospace' : 'inherit' }};">{{ $val }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Ad Set --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-crosshairs" style="color:var(--color-primary);"></i>
            Ad Set
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                @foreach([
                    'Nom Ad Set'       => $campaign->adset_name,
                    'Budget'           => $campaign->budget_formatted . ' (' . $campaign->budget_type . ')',
                    'Durée'            => $campaign->duration_days . ' jours',
                    'Optimization'     => $campaign->optimization_goal,
                    'Billing Event'    => $campaign->billing_event,
                    'Bid Strategy'     => $campaign->bid_strategy,
                ] as $label => $val)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">{{ $label }}</div>
                    <div style="font-weight:500;">{{ $val }}</div>
                </div>
                @endforeach
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Pays</div>
                    <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                        @foreach($campaign->countries as $c)
                        <span class="badge-status badge-status-draft">{{ $c }}</span>
                        @endforeach
                    </div>
                </div>
                @if($campaign->interests)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Intérêts</div>
                    <div style="font-size:0.8125rem; color:var(--color-muted);">{{ count($campaign->interests) }} sélectionné(s)</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══ RÉSULTATS META ADS ══════════════════════════════════════ --}}
    @if($campaign->meta_campaign_id)
    @php
        $totals        = $campaign->analytics_totals;
        $analyticsRows = $campaign->analytics;
        $lastSync      = $analyticsRows->last()?->updated_at;
        $canSync       = auth()->user()->hasRole('admin') || $campaign->user_id === auth()->id();
        $hasData       = $analyticsRows->isNotEmpty();
    @endphp
    <div class="card">
        <div class="card-header" style="justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
            <div style="display:flex; align-items:center; gap:.5rem;">
                <i class="fas fa-chart-bar" style="color:var(--color-primary);"></i>
                <div>
                    <div>Résultats Meta Ads</div>
                    @if($lastSync)
                    <div style="font-size:.7rem; font-weight:400; color:var(--color-muted);">
                        Dernière sync : {{ $lastSync->diffForHumans() }}
                    </div>
                    @endif
                </div>
            </div>
            @if($canSync)
            <form method="POST" action="{{ route('campaigns.sync-stats', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-secondary btn-sm">
                    <i class="fas fa-sync-alt"></i> Synchroniser
                </button>
            </form>
            @endif
        </div>

        <div class="card-body">

            @if(!$hasData)
            <div style="text-align:center; padding:2rem 1rem; color:var(--color-muted);">
                <i class="fas fa-chart-line" style="font-size:2rem; margin-bottom:.75rem; opacity:.3; display:block;"></i>
                <p style="margin:0; font-size:.875rem;">
                    Aucune statistique disponible pour l'instant.<br>
                    @if(in_array($campaign->execution_status, ['active','paused_ready','done']))
                        Cliquez sur <strong>Synchroniser</strong> pour récupérer les données Meta Ads.
                    @else
                        Les statistiques seront disponibles une fois la campagne active sur Meta.
                    @endif
                </p>
            </div>

            @else

            {{-- KPI Summary ──────────────────────────────────── --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:.875rem; margin-bottom:1.5rem;">

                <div style="background:var(--color-primary-light); border-radius:.625rem; padding:.875rem; text-align:center;">
                    <div style="font-size:1.375rem; font-weight:800; color:var(--color-primary);">
                        {{ number_format($totals['impressions'], 0, ',', '\u{202F}') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">Impressions</div>
                </div>

                <div style="background:#f0fdf4; border-radius:.625rem; padding:.875rem; text-align:center;">
                    <div style="font-size:1.375rem; font-weight:800; color:#16a34a;">
                        {{ number_format($totals['reach'], 0, ',', '\u{202F}') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">Personnes touchées</div>
                </div>

                <div style="background:#fffbeb; border-radius:.625rem; padding:.875rem; text-align:center;">
                    <div style="font-size:1.375rem; font-weight:800; color:#d97706;">
                        {{ number_format($totals['clicks'], 0, ',', '\u{202F}') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">Clics</div>
                </div>

                <div style="background:#fef2f2; border-radius:.625rem; padding:.875rem; text-align:center;">
                    <div style="font-size:1.375rem; font-weight:800; color:#dc2626;">
                        ${{ number_format($totals['spend'], 2, '.', ',') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">Dépensé</div>
                </div>

                <div style="background:#f5f3ff; border-radius:.625rem; padding:.875rem; text-align:center;">
                    <div style="font-size:1.375rem; font-weight:800; color:#7c3aed;">
                        {{ number_format($totals['ctr'], 2, ',', '') }}&nbsp;%
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">CTR moyen</div>
                </div>

                <div style="background:var(--color-page-bg); border-radius:.625rem; padding:.875rem; text-align:center; border:1px solid var(--color-border);">
                    <div style="font-size:1.375rem; font-weight:800; color:var(--color-heading);">
                        ${{ number_format($totals['cpm'], 2, '.', ',') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">CPM moyen</div>
                </div>

                <div style="background:var(--color-page-bg); border-radius:.625rem; padding:.875rem; text-align:center; border:1px solid var(--color-border);">
                    <div style="font-size:1.375rem; font-weight:800; color:var(--color-heading);">
                        ${{ number_format($totals['cpc'], 2, '.', ',') }}
                    </div>
                    <div style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-top:.2rem;">CPC moyen</div>
                </div>

            </div>

            {{-- Graphique sparkline (barres CSS) ────────────────── --}}
            @if($analyticsRows->count() > 1)
            @php
                $maxImpr = $analyticsRows->max('impressions') ?: 1;
            @endphp
            <div style="margin-bottom:1.25rem;">
                <div style="font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-bottom:.5rem;">
                    Impressions par jour
                </div>
                <div style="display:flex; align-items:flex-end; gap:3px; height:56px;">
                    @foreach($analyticsRows as $row)
                    @php $h = max(4, round(($row->impressions / $maxImpr) * 56)); @endphp
                    <div title="{{ $row->date_snapshot->format('d/m') }} — {{ number_format($row->impressions) }} impressions"
                         style="flex:1; min-width:4px; height:{{ $h }}px; background:var(--color-primary); border-radius:2px 2px 0 0; opacity:.75; cursor:default; transition:opacity .15s;"
                         onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.75'">
                    </div>
                    @endforeach
                </div>
                <div style="display:flex; justify-content:space-between; font-size:.65rem; color:var(--color-muted); margin-top:.25rem;">
                    <span>{{ $analyticsRows->first()->date_snapshot->format('d M') }}</span>
                    <span>{{ $analyticsRows->last()->date_snapshot->format('d M') }}</span>
                </div>
            </div>
            @endif

            {{-- Tableau quotidien ────────────────────────────────── --}}
            <div style="overflow-x:auto;">
                <table class="data-table" style="font-size:.8125rem;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th style="text-align:right;">Impressions</th>
                            <th style="text-align:right;">Reach</th>
                            <th style="text-align:right;">Clics</th>
                            <th style="text-align:right;">Dépenses</th>
                            <th style="text-align:right;">CTR</th>
                            <th style="text-align:right;">CPM</th>
                            <th style="text-align:right;">CPC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analyticsRows as $row)
                        <tr>
                            <td style="white-space:nowrap; font-weight:500;">{{ $row->date_snapshot->format('d/m/Y') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($row->impressions, 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($row->reach, 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($row->clicks, 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace; font-weight:600;">{{ number_format($row->spend, 0, ',', '\u{202F}') }} F</td>
                            <td style="text-align:right; color:var(--color-accent);">{{ number_format($row->ctr, 2, ',', '') }}&nbsp;%</td>
                            <td style="text-align:right;">{{ number_format($row->cpm, 0, ',', '\u{202F}') }} F</td>
                            <td style="text-align:right;">{{ number_format($row->cpc, 0, ',', '\u{202F}') }} F</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--color-page-bg); font-weight:700; border-top:2px solid var(--color-border);">
                            <td>Total</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($totals['impressions'], 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($totals['reach'], 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($totals['clicks'], 0, ',', '\u{202F}') }}</td>
                            <td style="text-align:right; font-family:monospace;">{{ number_format($totals['spend'], 0, ',', '\u{202F}') }} F</td>
                            <td style="text-align:right; color:var(--color-accent);">{{ number_format($totals['ctr'], 2, ',', '') }}&nbsp;%</td>
                            <td style="text-align:right;">{{ number_format($totals['cpm'], 0, ',', '\u{202F}') }} F</td>
                            <td style="text-align:right;">{{ number_format($totals['cpc'], 0, ',', '\u{202F}') }} F</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @endif {{-- hasData --}}
        </div>
    </div>
    @endif {{-- meta_campaign_id --}}

    @php
        $user      = auth()->user();
        $isAdmin   = $user->hasRole('admin');
        $isOp      = $user->hasRole(['operator','admin']);
        $isN1      = $user->hasRole(['validator_n1','validator','admin']);
        $isN2      = $user->hasRole(['validator_n2','admin']);
        $status    = $campaign->execution_status;
    @endphp

    {{-- Info : campagne créée sur Meta en PAUSED, en attente d'activation --}}
    @if($status === 'paused_ready')
    <div class="alert alert-warning" style="display:flex; align-items:flex-start; gap:.75rem;">
        <i class="fas fa-pause-circle" style="font-size:1.125rem; margin-top:.1rem; flex-shrink:0;"></i>
        <div>
            <strong>Campagne créée sur Meta — en attente d'activation</strong>
            <p style="margin:.25rem 0 0; font-size:.875rem;">
                N8N a créé la campagne, l'ad set et l'annonce sur Meta Ads en statut <strong>PAUSED</strong>.
                Cliquez sur <strong>Activer sur Meta</strong> pour lancer la diffusion.
            </p>
        </div>
    </div>
    @endif

    {{-- Motif de rejet --}}
    @if($status === 'rejected' && $campaign->error_message)
    <div class="alert alert-danger">
        <strong><i class="fas fa-times-circle"></i> Campagne rejetée</strong>
        <p style="margin:.375rem 0 0; font-size:.875rem;">{{ $campaign->error_message }}</p>
    </div>
    @endif

    {{-- Progression de validation --}}
    @if(in_array($status, ['pending_n1','pending_n2','approved']))
    <div class="card">
        <div class="card-body" style="padding:.875rem 1.25rem;">
            <div style="display:flex; align-items:center; gap:0; font-size:.8125rem;">
                @foreach([
                    ['label'=>'Soumis',     'done'=> true],
                    ['label'=>'Validé N+1', 'done'=> in_array($status,['pending_n2','approved'])],
                    ['label'=>'Validé N+2', 'done'=> $status === 'approved'],
                    ['label'=>'Boosté',     'done'=> false],
                ] as $i => $step)
                <div style="display:flex; align-items:center; flex:1; min-width:0;">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:.25rem; flex-shrink:0;">
                        <div style="width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700;
                                    background:{{ $step['done'] ? 'var(--color-primary)' : '#e2e8f0' }};
                                    color:{{ $step['done'] ? '#fff' : '#94a3b8' }};">
                            @if($step['done'])<i class="fas fa-check"></i>@else{{ $i+1 }}@endif
                        </div>
                        <div style="font-size:.6875rem; color:{{ $step['done'] ? 'var(--color-primary)' : 'var(--color-muted)' }}; font-weight:{{ $step['done'] ? '600' : '400' }}; white-space:nowrap;">
                            {{ $step['label'] }}
                        </div>
                    </div>
                    @if($i < 3)
                    <div style="flex:1; height:2px; background:{{ $step['done'] ? 'var(--color-primary)' : '#e2e8f0' }}; margin:0 .25rem; align-self:flex-start; margin-top:13px;"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Barre d'actions --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;"
         x-data="{ rejectOpen: false, reason: '' }">

        <a href="{{ route('campaigns.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour aux campagnes
        </a>

        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">

            {{-- Opérateur : soumettre → N+1 --}}
            @if($isOp && in_array($status, ['draft','rejected']))
            <form method="POST" action="{{ route('campaigns.submit', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Soumettre pour validation
                </button>
            </form>
            @endif

            {{-- N1 : approuver → N+2 --}}
            @if($isN1 && $status === 'pending_n1')
            <form method="POST" action="{{ route('campaigns.approve', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-success">
                    <i class="fas fa-check"></i> Valider N+1 →N+2
                </button>
            </form>
            @endif

            {{-- N2 : approuver → approved --}}
            @if($isN2 && $status === 'pending_n2')
            <form method="POST" action="{{ route('campaigns.approve', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-success">
                    <i class="fas fa-check-double"></i> Valider N+2 — Approuver
                </button>
            </form>
            @endif

            {{-- N1 ou N2 : rejeter --}}
            @if(($isN1 && $status === 'pending_n1') || ($isN2 && $status === 'pending_n2'))
            <button type="button" class="btn-danger" @click="rejectOpen = true">
                <i class="fas fa-times"></i> Rejeter
            </button>
            @endif

            {{-- Opérateur : booster (seulement si fully approved) --}}
            @if($isOp && $status === 'approved')
            <form method="POST" action="{{ route('campaigns.launch', $campaign->id) }}"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Lancement…';">
                @csrf
                <button type="submit" class="btn-primary">
                    <i class="fas fa-rocket"></i> Booster ce post
                </button>
            </form>
            @endif

            {{-- Admin bypass : lancer directement depuis draft/error --}}
            @if($isAdmin && in_array($status, ['draft','error']))
            <form method="POST" action="{{ route('campaigns.launch', $campaign->id) }}"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Lancement…';">
                @csrf
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-rocket"></i> {{ $status === 'error' ? 'Relancer' : 'Lancer sans validation' }}
                </button>
            </form>
            @endif

            {{-- Opérateur/Admin : activer la campagne PAUSED sur Meta --}}
            @if($isOp && $status === 'paused_ready')
            <form method="POST" action="{{ route('campaigns.activate', $campaign->id) }}"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Activation…';">
                @csrf
                <button type="submit" class="btn-success">
                    <i class="fas fa-play"></i> Activer sur Meta
                </button>
            </form>
            @endif

        </div>

        {{-- Modal rejet (partagé N1/N2) --}}
        <div x-show="rejectOpen" x-transition
             style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:50; align-items:center; justify-content:center; padding:1rem;"
             :style="rejectOpen ? 'display:flex' : 'display:none'">
            <div style="background:#fff; border-radius:.875rem; padding:1.5rem; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.2);" @click.stop>
                <h3 style="font-size:1rem; font-weight:700; margin:0 0 .25rem;">Rejeter la campagne</h3>
                <p style="font-size:.875rem; color:var(--color-muted); margin:0 0 1rem;">L'opérateur recevra ce motif et pourra corriger puis re-soumettre.</p>
                <form method="POST" action="{{ route('campaigns.reject', $campaign->id) }}">
                    @csrf
                    <textarea name="reason" x-model="reason" required
                              placeholder="Expliquez pourquoi cette campagne est rejetée…"
                              style="width:100%; padding:.75rem; border:1.5px solid var(--color-border); border-radius:.5rem; font-size:.875rem; min-height:100px; outline:none; resize:vertical;"
                              onfocus="this.style.borderColor='var(--color-primary)'"
                              onblur="this.style.borderColor='var(--color-border)'"></textarea>
                    <div style="display:flex; gap:.75rem; justify-content:flex-end; margin-top:1rem;">
                        <button type="button" class="btn-secondary" @click="rejectOpen = false">Annuler</button>
                        <button type="submit" class="btn-danger" :disabled="!reason.trim()">
                            <i class="fas fa-times"></i> Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

@endsection
