@extends('layouts.app')

@section('page-title', 'Détail du boost #' . $boost->id)

@section('content')

@php
$statusMap = [
    'draft'         => ['label'=>'Brouillon',       'class'=>'badge-status-draft',     'icon'=>'fa-file-alt'],
    'pending_n1'    => ['label'=>'En attente N+1',  'class'=>'badge-status-pending',   'icon'=>'fa-clock'],
    'rejected_n1'   => ['label'=>'Rejeté N+1',      'class'=>'badge-status-rejected',  'icon'=>'fa-times-circle'],
    'pending_n2'    => ['label'=>'En attente N+2',  'class'=>'badge-status-pending',   'icon'=>'fa-shield-halved'],
    'rejected_n2'   => ['label'=>'Rejeté N+2',      'class'=>'badge-status-rejected',  'icon'=>'fa-times-circle'],
    'approved'      => ['label'=>'Approuvé',        'class'=>'badge-status-approved',  'icon'=>'fa-check-circle'],
    'creating'      => ['label'=>'En création…',   'class'=>'badge-status-pending',   'icon'=>'fa-spinner'],
    'paused_ready'  => ['label'=>'Prêt à activer',  'class'=>'badge-status-created',   'icon'=>'fa-pause-circle'],
    'active'        => ['label'=>'Actif',           'class'=>'badge-status-active',    'icon'=>'fa-play-circle'],
    'paused'        => ['label'=>'En pause',        'class'=>'badge-status-paused',    'icon'=>'fa-pause'],
    'completed'     => ['label'=>'Terminé',         'class'=>'badge-status-completed', 'icon'=>'fa-flag-checkered'],
    'failed'        => ['label'=>'Échec',           'class'=>'badge-status-rejected',  'icon'=>'fa-exclamation-circle'],
];
$s = $statusMap[$boost->status] ?? ['label'=>ucfirst($boost->status), 'class'=>'badge-status-draft', 'icon'=>'fa-circle'];

$sensColors = [
    'faible'  => ['bg'=>'#dcfce7','color'=>'#166534','label'=>'Faible'],
    'moyenne' => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'Moyenne'],
    'elevee'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Élevée'],
];
$sc = $sensColors[$boost->sensitivity] ?? $sensColors['faible'];
@endphp

{{-- Page header --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <a href="{{ route('boost.my-requests') }}" style="display:flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:0.5rem; background:#f1f5f9; color:#64748b; text-decoration:none; font-size:0.875rem; flex-shrink:0;"
           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:0.125rem;">Boost</div>
            <div style="font-size:1.125rem; font-weight:700; color:#0f172a;">#{{ $boost->id }}</div>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.5rem;">
        <span style="padding:0.2rem 0.625rem; background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; border-radius:9999px; font-size:0.75rem; font-weight:600;">
            Sensibilité {{ $sc['label'] }}
        </span>
        <span class="badge-status {{ $s['class'] }}" style="font-size:0.875rem; padding:0.375rem 0.875rem;">
            <i class="fas {{ $s['icon'] }}"></i>
            {{ $s['label'] }}
        </span>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 320px; gap:1.5rem; align-items:start;">

    {{-- ── LEFT : Main info ── --}}
    <div style="display:flex; flex-direction:column; gap:1.25rem;">

        {{-- Post preview --}}
        <div class="card">
            <div class="card-header">
                <i class="fab fa-facebook" style="color:#1877f2;"></i>
                Post boosté
            </div>
            <div class="card-body">
                <div style="display:flex; gap:1rem; align-items:flex-start;">
                    @if($boost->post_thumbnail)
                    <img src="{{ $boost->post_thumbnail }}"
                         style="width:88px; height:88px; object-fit:cover; border-radius:0.625rem; flex-shrink:0;">
                    @else
                    <div style="width:88px; height:88px; background:#f1f5f9; border-radius:0.625rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fab fa-facebook" style="font-size:2rem; color:#cbd5e1;"></i>
                    </div>
                    @endif
                    <div>
                        <div style="font-weight:600; margin-bottom:0.375rem; color:#0f172a;">{{ $boost->page_name }}</div>
                        <div style="font-size:0.875rem; color:#64748b; line-height:1.5;">
                            {{ Str::limit($boost->post_message, 160) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info grid --}}
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle" style="color:var(--color-primary);"></i>
                Détails de la campagne
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem;">
                    <div style="padding:1rem; background:#f8fafc; border-radius:0.625rem; border:1px solid #f1f5f9;">
                        <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.375rem;">Période</div>
                        <div style="font-weight:600; color:#0f172a;">{{ $boost->start_date->format('d/m/Y') }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin:0.125rem 0;">→</div>
                        <div style="font-weight:600; color:#0f172a;">{{ $boost->end_date->format('d/m/Y') }}</div>
                    </div>
                    <div style="padding:1rem; background:#f8fafc; border-radius:0.625rem; border:1px solid #f1f5f9;">
                        <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.375rem;">Budget</div>
                        <div style="font-weight:700; color:var(--color-primary); font-size:1.25rem;">
                            {{ number_format($boost->budget, 0, ',', ' ') }}
                        </div>
                        <div style="font-size:0.8125rem; color:#94a3b8;">{{ $boost->currency }}</div>
                    </div>
                </div>

                {{-- Audience --}}
                <div style="padding:1rem; background:#f8fafc; border-radius:0.625rem; border:1px solid #f1f5f9; margin-bottom:1rem;">
                    <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.75rem;">Audience cible</div>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        <span style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; background:#fff; border:1px solid var(--color-border); border-radius:9999px; font-size:0.8125rem;">
                            <i class="fas fa-user" style="color:var(--color-primary); font-size:0.6875rem;"></i>
                            {{ $boost->target['age_min'] }}–{{ $boost->target['age_max'] }} ans
                        </span>
                        <span style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; background:#fff; border:1px solid var(--color-border); border-radius:9999px; font-size:0.8125rem;">
                            <i class="fas fa-venus-mars" style="color:var(--color-primary); font-size:0.6875rem;"></i>
                            {{ ['all'=>'Tous','male'=>'Hommes','female'=>'Femmes'][$boost->target['gender']] }}
                        </span>
                        @foreach($boost->target['countries'] as $country)
                        <span style="display:inline-flex; align-items:center; gap:0.25rem; padding:0.25rem 0.625rem; background:var(--color-primary-light); color:var(--color-primary); border-radius:9999px; font-size:0.75rem; font-weight:500;">
                            {{ $country }}
                        </span>
                        @endforeach
                        @foreach($boost->target['interests'] ?? [] as $interest)
                        <span style="display:inline-flex; align-items:center; padding:0.25rem 0.625rem; background:#f3e8ff; color:#7c3aed; border-radius:9999px; font-size:0.75rem; font-weight:500;">
                            {{ $interest }}
                        </span>
                        @endforeach
                    </div>
                </div>

                {{-- WhatsApp CTA --}}
                @if($boost->whatsapp_url)
                <div style="padding:0.75rem 1rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.625rem;">
                    <div style="font-size:0.75rem; color:#166534; font-weight:600; margin-bottom:0.375rem;">
                        <i class="fab fa-whatsapp" style="margin-right:0.25rem;"></i>
                        WhatsApp CTA configuré
                    </div>
                    <div style="font-size:0.8125rem; color:#15803d; word-break:break-all;">
                        <a href="{{ $boost->whatsapp_url }}" target="_blank" style="color:#15803d;">
                            {{ Str::limit($boost->whatsapp_url, 60) }}
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Meta IDs (si campagne créée) --}}
        @if($boost->meta_campaign_id)
        <div style="padding:1rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.625rem;">
            <div style="font-size:0.75rem; color:#166534; font-weight:600; margin-bottom:0.625rem; text-transform:uppercase; letter-spacing:0.05em;">
                <i class="fas fa-check-circle" style="margin-right:0.375rem;"></i>
                Campagne créée sur Meta Ads
            </div>
            <div style="display:flex; flex-direction:column; gap:0.25rem; font-size:0.8125rem; color:#15803d; font-family:monospace;">
                <div><span style="color:#166534; font-weight:600;">Campaign :</span> {{ $boost->meta_campaign_id }}</div>
                <div><span style="color:#166534; font-weight:600;">Ad Set   :</span> {{ $boost->meta_adset_id }}</div>
                <div><span style="color:#166534; font-weight:600;">Ad       :</span> {{ $boost->meta_ad_id }}</div>
                @if($boost->n8n_response['mock'] ?? false)
                <div style="margin-top:0.25rem; font-size:0.75rem; color:#94a3b8; font-family:sans-serif;">
                    <i class="fas fa-flask" style="margin-right:0.25rem;"></i>Mode mock — IDs simulés
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Rejection reason --}}
        @if(in_array($boost->status, ['rejected_n1','rejected_n2']) && $boost->rejection_reason)
        <div class="alert alert-danger">
            <div style="font-weight:600; margin-bottom:0.375rem;">
                <i class="fas fa-times-circle" style="margin-right:0.375rem;"></i>
                Raison du rejet ({{ $boost->status === 'rejected_n1' ? 'N+1' : 'N+2' }})
            </div>
            {{ $boost->rejection_reason }}
        </div>
        @endif

        {{-- Failed --}}
        @if($boost->status === 'failed')
        <div class="alert alert-danger">
            <div style="font-weight:600; margin-bottom:0.375rem;">
                <i class="fas fa-exclamation-triangle" style="margin-right:0.375rem;"></i>
                Erreur N8N
            </div>
            {{ $boost->n8n_response['error'] ?? 'Une erreur est survenue lors de la création de la campagne.' }}
        </div>
        @endif

        {{-- ─── Actions validateur N+1 ─── --}}
        @if($boost->status === 'pending_n1' && auth()->user()->hasRole(['validator_n1','validator','admin']))
        <div class="card" x-data="{ rejectOpen: false }">
            <div class="card-header">
                <i class="fas fa-gavel" style="color:var(--color-primary);"></i>
                Validation N+1
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:0.75rem;">
                <form method="POST" action="{{ route('boost.approve-n1', $boost->id) }}"
                      onsubmit="return confirm('Valider N+1 le boost #{{ $boost->id }} ?')">
                    @csrf
                    <div style="margin-bottom:0.5rem;">
                        <textarea name="comment" class="form-control" rows="2"
                                  placeholder="Commentaire (optionnel)..."
                                  style="resize:vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn-success" style="width:100%; justify-content:center;">
                        <i class="fas fa-check"></i>
                        @if($boost->needsN2())
                            Approuver → Escalader N+2 (sensibilité {{ $boost->sensitivity }})
                        @else
                            Approuver (validation finale)
                        @endif
                    </button>
                </form>

                <button @click="rejectOpen = !rejectOpen" class="btn-danger" style="width:100%; justify-content:center;">
                    <i class="fas fa-times"></i>
                    Rejeter N+1
                </button>

                <div x-show="rejectOpen" x-cloak>
                    <form method="POST" action="{{ route('boost.reject-n1', $boost->id) }}" style="display:flex; gap:0.5rem;">
                        @csrf
                        <textarea name="rejection_reason" class="form-control" rows="2"
                                  placeholder="Raison du rejet N+1 (minimum 10 caractères)..."
                                  required style="flex:1; resize:vertical;"></textarea>
                        <button type="submit" class="btn-danger btn-sm" style="align-self:flex-end;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Actions validateur N+2 ─── --}}
        @if($boost->status === 'pending_n2' && auth()->user()->hasRole(['validator_n2','admin']))
        <div class="card" x-data="{ rejectOpen: false }">
            <div class="card-header" style="background:#f5f3ff; border-color:#ddd6fe;">
                <i class="fas fa-shield-halved" style="color:#7c3aed;"></i>
                <span style="color:#5b21b6;">Validation N+2 (décision finale)</span>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:0.75rem;">
                <form method="POST" action="{{ route('boost.approve-n2', $boost->id) }}"
                      onsubmit="return confirm('Approuver définitivement le boost #{{ $boost->id }} ?')">
                    @csrf
                    <div style="margin-bottom:0.5rem;">
                        <textarea name="comment" class="form-control" rows="2"
                                  placeholder="Commentaire (optionnel)..."
                                  style="resize:vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn-success" style="width:100%; justify-content:center;">
                        <i class="fas fa-check-double"></i>
                        Approuver définitivement
                    </button>
                </form>

                <button @click="rejectOpen = !rejectOpen" class="btn-danger" style="width:100%; justify-content:center;">
                    <i class="fas fa-times"></i>
                    Rejeter N+2
                </button>

                <div x-show="rejectOpen" x-cloak>
                    <form method="POST" action="{{ route('boost.reject-n2', $boost->id) }}" style="display:flex; gap:0.5rem;">
                        @csrf
                        <textarea name="rejection_reason" class="form-control" rows="2"
                                  placeholder="Raison du rejet N+2 (minimum 10 caractères)..."
                                  required style="flex:1; resize:vertical;"></textarea>
                        <button type="submit" class="btn-danger btn-sm" style="align-self:flex-end;">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Activation --}}
        @if($boost->status === 'paused_ready' && auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
        <div class="card">
            <div class="card-header" style="background:#f0fdf4; border-color:#bbf7d0;">
                <i class="fas fa-play-circle" style="color:#16a34a;"></i>
                <span style="color:#166534;">Campagne prête — En attente d'activation</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.875rem; color:#374151; margin:0 0 1rem;">
                    La campagne a été créée sur Meta Ads et est en <strong>PAUSE</strong>.
                    Vérifiez les paramètres, puis activez-la.
                </p>
                <form method="POST" action="{{ route('boost.activate', $boost->id) }}"
                      onsubmit="return confirm('Activer la campagne du boost #{{ $boost->id }} sur Meta Ads ?')">
                    @csrf
                    <button type="submit" class="btn-success" style="width:100%; justify-content:center;">
                        <i class="fas fa-play"></i>
                        Activer la campagne Meta
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Pause --}}
        @if($boost->status === 'active' && auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
        <div class="card">
            <div class="card-header">
                <i class="fas fa-pause-circle" style="color:#c2410c;"></i>
                Gestion de la campagne
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('boost.pause', $boost->id) }}"
                      onsubmit="return confirm('Mettre en pause la campagne du boost #{{ $boost->id }} ?')">
                    @csrf
                    <button type="submit" class="btn-danger" style="width:100%; justify-content:center;">
                        <i class="fas fa-pause"></i>
                        Mettre en pause
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Reprendre --}}
        @if($boost->status === 'paused' && auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
        <div class="card">
            <div class="card-header">
                <i class="fas fa-play" style="color:#16a34a;"></i>
                Campagne en pause
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('boost.activate', $boost->id) }}"
                      onsubmit="return confirm('Relancer la campagne du boost #{{ $boost->id }} ?')">
                    @csrf
                    <button type="submit" class="btn-success" style="width:100%; justify-content:center;">
                        <i class="fas fa-play"></i>
                        Reprendre la campagne
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Retry N8N --}}
        @if($boost->status === 'approved' && auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
        <div class="card">
            <div class="card-header" style="background:#fffbeb; border-color:#fde68a;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706;"></i>
                <span style="color:#92400e;">N8N n'a pas encore été déclenché</span>
            </div>
            <div class="card-body">
                <p style="font-size:0.875rem; color:#374151; margin:0 0 1rem;">
                    Le boost est approuvé mais la campagne n'a pas encore été créée.
                    Relancez N8N manuellement.
                </p>
                <form method="POST" action="{{ route('boost.retry-n8n', $boost->id) }}">
                    @csrf
                    <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                        <i class="fas fa-redo"></i>
                        Relancer N8N
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Opérateur : soumettre --}}
        @if(in_array($boost->status, ['draft','rejected_n1','rejected_n2']) && auth()->id() === $boost->operator_id)
        <div class="card">
            @if($boost->isRejected())
            <div class="alert alert-danger" style="margin:1.25rem 1.25rem 0; border-radius:0.5rem;">
                <i class="fas fa-info-circle" style="margin-right:0.375rem;"></i>
                Rejeté par le validateur. Corrigez et resoumettez.
            </div>
            @endif
            <div class="card-footer" style="display:flex; gap:0.75rem; justify-content:flex-end; background:#fff; border-radius:0.75rem;">
                <form method="POST" action="{{ route('boost.submit', $boost->id) }}">
                    @csrf
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Soumettre pour validation N+1
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>

    {{-- ── RIGHT : Status timeline + Approvals ── --}}
    <div style="display:flex; flex-direction:column; gap:1.25rem;">

        {{-- Timeline --}}
        <div class="card">
            <div class="card-header">
                <i class="fas fa-stream" style="color:var(--color-primary);"></i>
                Parcours du boost
            </div>
            <div class="card-body">
                @php
                $steps = [
                    ['status'=>['draft'],                            'label'=>'Brouillon créé',         'icon'=>'fa-file-alt'],
                    ['status'=>['pending_n1'],                       'label'=>'Soumis — validation N+1','icon'=>'fa-clock'],
                    ['status'=>['rejected_n1'],                      'label'=>'Rejeté N+1',             'icon'=>'fa-times-circle'],
                    ['status'=>['pending_n2'],                       'label'=>'Escaladé — validation N+2','icon'=>'fa-shield-halved'],
                    ['status'=>['rejected_n2'],                      'label'=>'Rejeté N+2',             'icon'=>'fa-times-circle'],
                    ['status'=>['approved'],                         'label'=>'Approuvé',               'icon'=>'fa-check-circle'],
                    ['status'=>['creating'],                         'label'=>'N8N crée la campagne…', 'icon'=>'fa-spinner'],
                    ['status'=>['paused_ready'],                     'label'=>'Campagne prête (pause)', 'icon'=>'fa-pause-circle'],
                    ['status'=>['active'],                           'label'=>'Campagne active',        'icon'=>'fa-play-circle'],
                    ['status'=>['completed'],                        'label'=>'Terminé',                'icon'=>'fa-flag-checkered'],
                ];
                $allStatuses = ['draft','pending_n1','rejected_n1','pending_n2','rejected_n2','approved','creating','paused_ready','active','paused','completed','failed'];
                $currentIndex = array_search($boost->status, $allStatuses);
                @endphp

                <div style="position:relative;">
                    @foreach($steps as $i => $step)
                    @php
                    $stepIndexes = array_map(fn($s) => array_search($s, $allStatuses), $step['status']);
                    $stepIndex = min($stepIndexes);
                    $isDone = $stepIndex <= $currentIndex && $currentIndex !== false;
                    $isCurrent = in_array($boost->status, $step['status']);
                    @endphp

                    <div style="display:flex; align-items:flex-start; gap:0.875rem; {{ !$loop->last ? 'margin-bottom:1.25rem;' : '' }}">
                        <div style="
                            width:2rem; height:2rem; border-radius:50%;
                            display:flex; align-items:center; justify-content:center;
                            flex-shrink:0; font-size:0.75rem;
                            {{ $isDone ? 'background:var(--color-primary); color:white;' : 'background:#f1f5f9; color:#cbd5e1;' }}
                            {{ $isCurrent ? 'box-shadow:0 0 0 4px var(--color-primary-light);' : '' }}
                            position:relative; z-index:1;
                        ">
                            <i class="fas {{ $step['icon'] }}"></i>
                        </div>
                        <div style="padding-top:0.3rem;">
                            <div style="font-size:0.875rem; font-weight:{{ $isDone ? '600' : '400' }}; color:{{ $isDone ? '#0f172a' : '#94a3b8' }};">
                                {{ $step['label'] }}
                            </div>
                        </div>
                    </div>

                    @if(!$loop->last)
                    <div style="position:relative; margin-left:0.9375rem; width:2px; height:1.25rem;
                                background:{{ $stepIndex < $currentIndex ? 'var(--color-primary)' : '#e2e8f0' }};
                                margin-top:-1rem; z-index:0;"></div>
                    @endif
                    @endforeach
                </div>

                {{-- Metadata --}}
                <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid #f1f5f9; font-size:0.8125rem; color:#94a3b8;">
                    <div style="margin-bottom:0.25rem;">
                        <i class="fas fa-calendar" style="width:1rem;"></i>
                        Créé {{ $boost->created_at->diffForHumans() }}
                    </div>
                    <div>
                        <i class="fas fa-user" style="width:1rem;"></i>
                        Par {{ $boost->operator->name }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Historique des approbations --}}
        @if($boost->approvals->count() > 0)
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history" style="color:var(--color-primary);"></i>
                Historique de validation
            </div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:0.875rem;">
                    @foreach($boost->approvals as $approval)
                    <div style="padding:0.75rem; background:#f8fafc; border-radius:0.5rem; border-left:3px solid {{ $approval->isApproved() ? '#22c55e' : '#ef4444' }};">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.25rem;">
                            <span style="font-weight:600; font-size:0.875rem; color:#0f172a;">
                                {{ $approval->user->name }}
                            </span>
                            <span style="font-size:0.75rem; padding:0.1rem 0.5rem; border-radius:9999px;
                                         background:{{ $approval->isApproved() ? '#dcfce7' : '#fee2e2' }};
                                         color:{{ $approval->isApproved() ? '#166534' : '#991b1b' }}; font-weight:600;">
                                N+{{ $approval->level === 'N1' ? '1' : '2' }} — {{ $approval->isApproved() ? 'Approuvé' : 'Rejeté' }}
                            </span>
                        </div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:{{ $approval->comment ? '0.375rem' : '0' }};">
                            {{ $approval->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if($approval->comment)
                        <div style="font-size:0.8125rem; color:#64748b; font-style:italic;">
                            "{{ $approval->comment }}"
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

</div>

@endsection
