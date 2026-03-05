@extends('layouts.app')

@section('page-title', 'Nouvelle campagne')
@section('page-subtitle', 'Agent Media Buyer YWC — Boost Post · Meta Ads API')

@section('content')

@php
    $defaultCampaignName = old('campaign_name', 'BOOST POST – ' . now()->format('d/m/Y'));
    $defaultAdsetName    = old('adset_name', 'AdSet CI – Marketing & Communication – 7j');
    $defaultAdName       = old('ad_name', 'Ad – Boost Existing Post');
@endphp

<div style="max-width:860px; margin:0 auto;">

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('campaigns.store') }}" id="boostForm">
        @csrf

        {{-- ════════════════════════════════
             SECTION 1 — CAMPAGNE
        ════════════════════════════════ --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:0.625rem;">
                    <i class="fas fa-bullhorn" style="color:var(--color-primary);"></i>
                    <div>
                        <div>Campagne</div>
                        <div style="font-size:0.75rem; font-weight:400; color:var(--color-muted);">Niveau 1 · Campaign Object · Meta Ads API</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    {{-- Nom campagne --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom de la campagne <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="campaign_name" class="form-control"
                               value="{{ $defaultCampaignName }}"
                               placeholder="ex: BOOST POST – Lancement Peugeot 3008 – Mar 2026"
                               style="{{ $errors->has('campaign_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('campaign_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Inclure le mois et l'objectif pour retrouver facilement dans Meta Ads Manager</p>
                    </div>

                    {{-- Objectif --}}
                    <div>
                        <label class="form-label">
                            Objectif <span style="color:#ef4444;">*</span>
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: objective</span>
                        </label>
                        <select name="campaign_objective" class="form-control" id="sel_objective" onchange="updateSummary()">
                            @foreach([
                                'OUTCOME_TRAFFIC'       => 'OUTCOME_TRAFFIC — Trafic (défaut)',
                                'OUTCOME_AWARENESS'     => 'OUTCOME_AWARENESS — Notoriété',
                                'OUTCOME_ENGAGEMENT'    => 'OUTCOME_ENGAGEMENT — Engagement',
                                'OUTCOME_LEADS'         => 'OUTCOME_LEADS — Génération de leads',
                                'OUTCOME_SALES'         => 'OUTCOME_SALES — Ventes',
                                'OUTCOME_APP_PROMOTION' => 'OUTCOME_APP_PROMOTION — App',
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ old('campaign_objective','OUTCOME_TRAFFIC') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">OUTCOME_TRAFFIC → optimise pour les clics vers le post</p>
                    </div>

                    {{-- Catégorie spéciale --}}
                    <div>
                        <label class="form-label">
                            Catégorie spéciale
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: special_ad_categories</span>
                        </label>
                        <select name="special_ad_categories" class="form-control">
                            @foreach([
                                'NONE'                      => 'NONE — Aucune (défaut)',
                                'CREDIT'                    => 'CREDIT — Crédit / Finance',
                                'EMPLOYMENT'                => 'EMPLOYMENT — Emploi',
                                'HOUSING'                   => 'HOUSING — Immobilier',
                                'ISSUES_ELECTIONS_POLITICS' => 'ISSUES_ELECTIONS_POLITICS — Politique',
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ old('special_ad_categories','NONE') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Obligatoire pour certains secteurs</p>
                    </div>

                    {{-- Statut campagne --}}
                    <div>
                        <label class="form-label">
                            Statut initial
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: status</span>
                        </label>
                        <select name="campaign_status" class="form-control" id="sel_status" onchange="updateSummary()">
                            <option value="PAUSED" {{ old('campaign_status','PAUSED') === 'PAUSED' ? 'selected' : '' }}>PAUSED — En pause (recommandé)</option>
                            <option value="ACTIVE" {{ old('campaign_status','PAUSED') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE — Active immédiatement</option>
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">PAUSED permet de vérifier avant diffusion</p>
                    </div>

                    {{-- Toggle campagne existante --}}
                    <div style="grid-column:1/-1;" x-data="{ open: {{ old('existing_campaign_id') ? 'true' : 'false' }} }">
                        <label style="display:flex; align-items:center; gap:.75rem; padding:.875rem 1rem; border:1.5px solid var(--color-border); border-radius:.625rem; cursor:pointer; transition:all .15s;"
                               :style="open ? 'border-color:var(--color-primary); background:var(--color-primary-light);' : ''">
                            <input type="checkbox" x-model="open"
                                   style="width:18px; height:18px; accent-color:var(--color-primary); flex-shrink:0; cursor:pointer;">
                            <div>
                                <div style="font-size:.875rem; font-weight:600; color:var(--color-heading);">Utiliser une campagne existante</div>
                                <div style="font-size:.75rem; color:var(--color-muted);">Si coché → l'Ad Set sera ajouté à une campagne déjà créée dans Meta</div>
                            </div>
                        </label>

                        <div x-show="open" x-transition style="margin-top:.75rem;">
                            <label class="form-label">ID de la campagne existante <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="existing_campaign_id" class="form-control"
                                   value="{{ old('existing_campaign_id') }}"
                                   placeholder="ex: 120241034883010205">
                            <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Récupère l'ID dans Meta Ads Manager → Campagnes → colonne ID</p>
                            @error('existing_campaign_id')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             SECTION 2 — AD SET
        ════════════════════════════════ --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:0.625rem;">
                    <i class="fas fa-crosshairs" style="color:var(--color-primary);"></i>
                    <div>
                        <div>Ad Set — Ciblage &amp; Budget</div>
                        <div style="font-size:.75rem; font-weight:400; color:var(--color-muted);">Niveau 2 · Définit QUI voit la pub et COMBIEN on dépense</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    {{-- Nom Ad Set --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom de l'Ad Set <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="adset_name" class="form-control"
                               value="{{ $defaultAdsetName }}"
                               style="{{ $errors->has('adset_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('adset_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Type de budget --}}
                    <div>
                        <label class="form-label">
                            Type de budget
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: lifetime_budget / daily_budget</span>
                        </label>
                        <select name="budget_type" class="form-control" id="sel_budget_type" onchange="updateBudgetHint()">
                            <option value="lifetime_budget" {{ old('budget_type','lifetime_budget') === 'lifetime_budget' ? 'selected' : '' }}>lifetime_budget — Budget total sur la période</option>
                            <option value="daily_budget"    {{ old('budget_type','lifetime_budget') === 'daily_budget'    ? 'selected' : '' }}>daily_budget — Budget par jour</option>
                        </select>
                        <p id="budget_type_hint" style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Le budget total est réparti automatiquement sur la durée choisie</p>
                    </div>

                    {{-- Durée --}}
                    <div>
                        <label class="form-label">
                            Durée de diffusion
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: end_time</span>
                        </label>
                        <select name="duration_days" class="form-control" id="sel_duration" onchange="updateSummary()">
                            @foreach([1=>'1 jour',3=>'3 jours',7=>'7 jours (défaut)',14=>'14 jours',30=>'30 jours'] as $d => $lbl)
                            <option value="{{ $d }}" {{ old('duration_days',7) == $d ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('duration_days')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Budget pills --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">
                            Budget (FCFA)
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: budget en centimes × 100</span>
                        </label>
                        <input type="hidden" name="budget_value" id="budget_value" value="{{ old('budget_value',7000) }}">
                        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;" id="budgetPills">
                            @foreach([3500,7000,14000,35000,70000] as $b)
                            <button type="button" onclick="selectBudget({{ $b }})" data-budget="{{ $b }}"
                                    style="padding:.5rem 1rem; border-radius:9999px; font-family:monospace; font-size:.875rem; cursor:pointer; transition:all .15s;
                                           border:2px solid {{ old('budget_value',7000) == $b ? 'var(--color-primary)' : 'var(--color-border)' }};
                                           background:{{ old('budget_value',7000) == $b ? 'var(--color-primary-light)' : '#fff' }};
                                           color:{{ old('budget_value',7000) == $b ? 'var(--color-primary)' : '#374151' }};
                                           font-weight:{{ old('budget_value',7000) == $b ? '700' : '500' }};">
                                {{ number_format($b) }}
                            </button>
                            @endforeach
                            <input type="number" id="budget_custom" placeholder="Autre montant…" min="500" step="500"
                                   oninput="selectBudget(this.value ? parseInt(this.value) : null, true)"
                                   style="width:160px; padding:.5rem .75rem; border:2px solid var(--color-border); border-radius:.5rem; font-size:.875rem; outline:none;"
                                   onfocus="this.style.borderColor='var(--color-primary)'"
                                   onblur="if(!this.value)this.style.borderColor='var(--color-border)'">
                        </div>
                        @error('budget_value')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        <p style="margin:0; font-size:.75rem; color:var(--color-muted);">Meta attend la valeur en centimes → 7 000 FCFA = 700 centimes envoyés à l'API</p>
                    </div>

                    {{-- Pays cibles --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">
                            Pays cibles
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: geo_locations.countries</span>
                        </label>
                        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem;" id="countriesGrid">
                            @foreach([
                                'CI'=>'🇨🇮 CI — Côte d\'Ivoire','SN'=>'🇸🇳 SN — Sénégal',
                                'ML'=>'🇲🇱 ML — Mali',          'BF'=>'🇧🇫 BF — Burkina Faso',
                                'GN'=>'🇬🇳 GN — Guinée',        'TG'=>'🇹🇬 TG — Togo',
                                'BJ'=>'🇧🇯 BJ — Bénin',         'CM'=>'🇨🇲 CM — Cameroun',
                                'GH'=>'🇬🇭 GH — Ghana',         'NG'=>'🇳🇬 NG — Nigeria',
                                'MA'=>'🇲🇦 MA — Maroc',         'FR'=>'🇫🇷 FR — France',
                            ] as $code => $lbl)
                            @php $checked = in_array($code, old('countries', ['CI'])); @endphp
                            <label class="country-label" style="display:flex; align-items:center; gap:.5rem; padding:.5rem .75rem; border:1.5px solid {{ $checked ? 'var(--color-primary)' : 'var(--color-border)' }}; border-radius:.5rem; cursor:pointer; font-size:.8125rem; transition:all .15s; background:{{ $checked ? 'var(--color-primary-light)' : '#fff' }}; color:{{ $checked ? 'var(--color-primary)' : '#374151' }}; font-weight:{{ $checked ? '600' : '400' }};">
                                <input type="checkbox" name="countries[]" value="{{ $code }}"
                                       style="accent-color:var(--color-primary); flex-shrink:0;"
                                       {{ $checked ? 'checked' : '' }}
                                       onchange="styleCountry(this)">
                                {{ $lbl }}
                            </label>
                            @endforeach
                        </div>
                        @error('countries')<div class="invalid-feedback" style="display:block; margin-top:.375rem;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Centres d'intérêt --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">
                            Centres d'intérêt
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: targeting.interests</span>
                        </label>
                        @php
                            $defaultInterestIds = ['6003279598823','6003127206524','6003389760112'];
                            $oldInterestIds = old('interests_value')
                                ? collect(json_decode(old('interests_value'), true) ?? [])->pluck('id')->toArray()
                                : $defaultInterestIds;
                        @endphp
                        <input type="hidden" name="interests_value" id="interests_value"
                               value="{{ old('interests_value', json_encode(array_map(fn($id)=>['id'=>$id], $defaultInterestIds))) }}">
                        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.375rem;">
                            @foreach([
                                '6003279598823'=>'Marketing',      '6003127206524'=>'Digital Marketing',
                                '6003389760112'=>'Social Media',   '6003232518619'=>'Advertising',
                                '6003139057932'=>'Entrepreneurship','6002990402487'=>'Automobile',
                                '6003107902433'=>'Business',       '6002925729260'=>'Technology',
                                '6003330421807'=>'Video',          '6003008043877'=>'E-commerce',
                            ] as $iid => $iname)
                            @php $sel = in_array($iid, $oldInterestIds); @endphp
                            <span onclick="toggleInterest(this,'{{ $iid }}')" data-id="{{ $iid }}"
                                  style="padding:.375rem .875rem; border-radius:9999px; font-size:.8125rem; cursor:pointer; user-select:none; transition:all .15s;
                                         border:1.5px solid {{ $sel ? 'var(--color-primary)' : 'var(--color-border)' }};
                                         background:{{ $sel ? 'var(--color-primary-light)' : '#fff' }};
                                         color:{{ $sel ? 'var(--color-primary)' : '#64748b' }};
                                         font-weight:{{ $sel ? '600' : '400' }};">
                                {{ $iname }}
                            </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Optimization goal --}}
                    <div>
                        <label class="form-label">
                            Optimization Goal
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: optimization_goal</span>
                        </label>
                        <select name="optimization_goal" class="form-control">
                            @foreach([
                                'LINK_CLICKS'        => 'LINK_CLICKS — Clics lien (défaut)',
                                'IMPRESSIONS'        => 'IMPRESSIONS — Impressions',
                                'REACH'              => 'REACH — Portée',
                                'LANDING_PAGE_VIEWS' => 'LANDING_PAGE_VIEWS — Vues page',
                                'VIDEO_VIEWS'        => 'VIDEO_VIEWS — Vues vidéo',
                                'POST_ENGAGEMENT'    => 'POST_ENGAGEMENT — Engagement',
                            ] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('optimization_goal','LINK_CLICKS') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Doit être compatible avec l'objectif de campagne</p>
                    </div>

                    {{-- Billing event --}}
                    <div>
                        <label class="form-label">
                            Billing Event
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: billing_event</span>
                        </label>
                        <select name="billing_event" class="form-control">
                            @foreach([
                                'IMPRESSIONS'     => 'IMPRESSIONS — Paiement aux impressions (défaut)',
                                'LINK_CLICKS'     => 'LINK_CLICKS — Paiement aux clics',
                                'POST_ENGAGEMENT' => 'POST_ENGAGEMENT — Paiement à l\'engagement',
                                'VIDEO_VIEWS'     => 'VIDEO_VIEWS — Paiement aux vues',
                            ] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('billing_event','IMPRESSIONS') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Bid strategy --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">
                            Stratégie d'enchères
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: bid_strategy</span>
                        </label>
                        <select name="bid_strategy" class="form-control">
                            @foreach([
                                'LOWEST_COST_WITHOUT_CAP'  => 'LOWEST_COST_WITHOUT_CAP — Coût le plus bas automatique (défaut)',
                                'LOWEST_COST_WITH_BID_CAP' => 'LOWEST_COST_WITH_BID_CAP — Coût le plus bas avec plafond',
                                'COST_CAP'                 => 'COST_CAP — Plafond de coût cible',
                            ] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('bid_strategy','LOWEST_COST_WITHOUT_CAP') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">LOWEST_COST_WITHOUT_CAP → Meta optimise automatiquement sans limite d'enchère</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             SECTION 3 — AD
        ════════════════════════════════ --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:0.625rem;">
                    <i class="fas fa-image" style="color:var(--color-primary);"></i>
                    <div>
                        <div>Ad — Création publicitaire</div>
                        <div style="font-size:.75rem; font-weight:400; color:var(--color-muted);">Niveau 3 · Le post Facebook qui sera boosté</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    {{-- Nom Ad --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom de l'Ad <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="ad_name" class="form-control"
                               value="{{ $defaultAdName }}"
                               style="{{ $errors->has('ad_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('ad_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Post à booster --}}
                    <div style="grid-column:1/-1;">
                        <label class="form-label">
                            Post Facebook à booster <span style="color:#ef4444;">*</span>
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: object_story_id</span>
                        </label>

                        @if($post)
                        {{-- Post pré-sélectionné via ?post_id= --}}
                        <input type="hidden" name="post_id" value="{{ $post->post_id }}">
                        <div style="display:flex; gap:1rem; align-items:flex-start; padding:.875rem 1rem; border:1.5px solid var(--color-border); border-radius:.625rem; background:#f8fafc;">
                            @if($post->thumbnail_url)
                            <img src="{{ $post->thumbnail_url }}" alt=""
                                 style="width:72px; height:72px; object-fit:cover; border-radius:.5rem; flex-shrink:0;">
                            @else
                            <div style="width:72px; height:72px; border-radius:.5rem; background:#eef2ff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fab fa-facebook" style="font-size:1.5rem; color:#a5b4fc;"></i>
                            </div>
                            @endif
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:.875rem; color:#374151; margin-bottom:.375rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    {{ $post->message ?: '(Aucun texte)' }}
                                </div>
                                <div style="font-family:monospace; font-size:.8125rem; color:var(--color-primary);">{{ $post->post_id }}</div>
                                @if($post->page)<div style="font-size:.75rem; color:#94a3b8; margin-top:.2rem;"><i class="fab fa-facebook" style="color:#1877f2;"></i> {{ $post->page->page_name }}</div>@endif
                            </div>
                            <a href="{{ route('posts.index') }}" class="btn-secondary btn-sm" title="Changer de post" style="flex-shrink:0;">
                                <i class="fas fa-exchange-alt"></i> Changer
                            </a>
                        </div>

                        @else
                        {{-- Saisie manuelle --}}
                        <input type="text" name="post_id" class="form-control"
                               value="{{ old('post_id') }}"
                               placeholder="ex: 668925849823227_1234567890123"
                               style="{{ $errors->has('post_id') ? 'border-color:#ef4444;' : '' }}">
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Format : PAGE_ID_POST_ID · Retrouvable dans l'URL du post ou via Meta Graph API Explorer.
                            Ou <a href="{{ route('posts.index') }}" style="color:var(--color-primary);">sélectionner depuis la liste des posts</a>.</p>
                        @error('post_id')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    {{-- Statut Ad --}}
                    <div>
                        <label class="form-label">
                            Statut de l'Ad
                            <span style="font-size:.7rem; background:#eef2ff; color:var(--color-primary); padding:1px 5px; border-radius:3px; margin-left:4px;">API: status</span>
                        </label>
                        <select name="ad_status" class="form-control">
                            <option value="PAUSED" {{ old('ad_status','PAUSED') === 'PAUSED' ? 'selected' : '' }}>PAUSED — En pause (recommandé)</option>
                            <option value="ACTIVE" {{ old('ad_status','PAUSED') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE — Active dès création</option>
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Synchronisé avec le statut campagne/adset</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             BARRE RÉCAPITULATIVE + SUBMIT
        ════════════════════════════════ --}}
        <div class="card" style="border-color:var(--color-primary);">
            <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <div style="display:flex; gap:2rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Budget</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_budget">7 000 FCFA</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Durée</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_duration">7 jours</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Objectif</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_objective">TRAFFIC</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Statut</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_status">PAUSED</div>
                    </div>
                </div>
                <div style="display:flex; gap:.75rem; align-items:center;">
                    <a href="{{ route('posts.index') }}" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-rocket"></i> Enregistrer &amp; Envoyer
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
// ─── BUDGET PILLS ───────────────────────────────
var currentBudget = {{ old('budget_value', 7000) }};

function selectBudget(val, fromCustom) {
    if (!val || val < 500) return;
    currentBudget = val;
    document.getElementById('budget_value').value = val;
    document.querySelectorAll('#budgetPills button').forEach(function(btn) {
        var active = parseInt(btn.dataset.budget) === val;
        btn.style.border     = '2px solid ' + (active ? 'var(--color-primary)' : 'var(--color-border)');
        btn.style.background = active ? 'var(--color-primary-light)' : '#fff';
        btn.style.color      = active ? 'var(--color-primary)' : '#374151';
        btn.style.fontWeight = active ? '700' : '500';
    });
    if (!fromCustom) document.getElementById('budget_custom').value = '';
    updateSummary();
}

// ─── INTERESTS CHIPS ───────────────────────────
var selInterests = {!! json_encode(
    old('interests_value')
        ? collect(json_decode(old('interests_value'), true) ?? [])->pluck('id')->toArray()
        : ['6003279598823','6003127206524','6003389760112']
) !!};

function toggleInterest(el, id) {
    var idx = selInterests.indexOf(id);
    if (idx >= 0) { selInterests.splice(idx, 1); } else { selInterests.push(id); }
    var active = selInterests.indexOf(id) >= 0;
    el.style.border     = '1.5px solid ' + (active ? 'var(--color-primary)' : 'var(--color-border)');
    el.style.background = active ? 'var(--color-primary-light)' : '#fff';
    el.style.color      = active ? 'var(--color-primary)' : '#64748b';
    el.style.fontWeight = active ? '600' : '400';
    document.getElementById('interests_value').value =
        JSON.stringify(selInterests.map(function(i) { return {id: i}; }));
}

// ─── COUNTRY CHECKBOXES ─────────────────────────
function styleCountry(cb) {
    var lbl = cb.closest('label');
    if (!lbl) return;
    lbl.style.borderColor = cb.checked ? 'var(--color-primary)' : 'var(--color-border)';
    lbl.style.background  = cb.checked ? 'var(--color-primary-light)' : '#fff';
    lbl.style.color       = cb.checked ? 'var(--color-primary)' : '#374151';
    lbl.style.fontWeight  = cb.checked ? '600' : '400';
}
document.querySelectorAll('#countriesGrid input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() { styleCountry(this); });
});

// ─── BUDGET TYPE HINT ───────────────────────────
function updateBudgetHint() {
    var type = document.getElementById('sel_budget_type').value;
    document.getElementById('budget_type_hint').textContent = type === 'lifetime_budget'
        ? 'Le budget total est réparti automatiquement sur la durée choisie'
        : 'Ce montant est dépensé chaque jour — total = budget × durée';
}

// ─── SUMMARY BAR ────────────────────────────────
function updateSummary() {
    var budget   = parseInt(document.getElementById('budget_value').value) || 0;
    var duration = parseInt(document.getElementById('sel_duration').value) || 7;
    var objSel   = document.getElementById('sel_objective');
    var stSel    = document.getElementById('sel_status');
    var obj      = objSel ? objSel.value.replace('OUTCOME_', '') : 'TRAFFIC';
    var st       = stSel  ? stSel.value : 'PAUSED';

    document.getElementById('s_budget').textContent   = new Intl.NumberFormat('fr-FR').format(budget) + ' FCFA';
    document.getElementById('s_duration').textContent = duration + ' jour' + (duration > 1 ? 's' : '');
    document.getElementById('s_objective').textContent = obj;
    document.getElementById('s_status').textContent   = st;
}

// ─── SUBMIT FEEDBACK ────────────────────────────
document.getElementById('boostForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours…';
});

// Init
updateSummary();
</script>
@endpush
