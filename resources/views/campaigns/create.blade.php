@extends('layouts.app')

@section('page-title', 'Nouvelle campagne')
@section('page-subtitle', 'Agent Media Buyer YWC — Configurez votre boost')

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
                        <div style="font-size:.75rem; font-weight:400; color:var(--color-muted);">Paramètres généraux de la campagne</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom de la campagne <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="campaign_name" class="form-control"
                               value="{{ $defaultCampaignName }}"
                               placeholder="ex: BOOST POST – Lancement produit – Mars 2026"
                               style="{{ $errors->has('campaign_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('campaign_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Objectif publicitaire <span style="color:#ef4444;">*</span></label>
                        <select name="campaign_objective" class="form-control" id="sel_objective" onchange="updateSummary()">
                            <option value="OUTCOME_TRAFFIC"       {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_TRAFFIC'       ? 'selected' : '' }}>Trafic (visites & clics)</option>
                            <option value="OUTCOME_AWARENESS"     {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_AWARENESS'     ? 'selected' : '' }}>Notoriété de la marque</option>
                            <option value="OUTCOME_ENGAGEMENT"    {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_ENGAGEMENT'    ? 'selected' : '' }}>Engagement (likes, partages)</option>
                            <option value="OUTCOME_LEADS"         {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_LEADS'         ? 'selected' : '' }}>Génération de prospects</option>
                            <option value="OUTCOME_SALES"         {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_SALES'         ? 'selected' : '' }}>Ventes</option>
                            <option value="OUTCOME_APP_PROMOTION" {{ old('campaign_objective','OUTCOME_TRAFFIC') === 'OUTCOME_APP_PROMOTION' ? 'selected' : '' }}>Promotion d'application</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Catégorie réglementaire</label>
                        <select name="special_ad_categories" class="form-control">
                            <option value="NONE"                      {{ old('special_ad_categories','NONE') === 'NONE'                      ? 'selected' : '' }}>Aucune (standard)</option>
                            <option value="CREDIT"                    {{ old('special_ad_categories','NONE') === 'CREDIT'                    ? 'selected' : '' }}>Crédit / Finance</option>
                            <option value="EMPLOYMENT"                {{ old('special_ad_categories','NONE') === 'EMPLOYMENT'                ? 'selected' : '' }}>Offres d'emploi</option>
                            <option value="HOUSING"                   {{ old('special_ad_categories','NONE') === 'HOUSING'                   ? 'selected' : '' }}>Immobilier</option>
                            <option value="ISSUES_ELECTIONS_POLITICS" {{ old('special_ad_categories','NONE') === 'ISSUES_ELECTIONS_POLITICS' ? 'selected' : '' }}>Politique / Élections</option>
                        </select>
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Obligatoire pour certains secteurs réglementés</p>
                    </div>

                    <div>
                        <label class="form-label">Démarrage de la campagne</label>
                        <select name="campaign_status" class="form-control" id="sel_status" onchange="updateSummary()">
                            <option value="PAUSED" {{ old('campaign_status','PAUSED') === 'PAUSED' ? 'selected' : '' }}>En pause — vérifier avant de diffuser (recommandé)</option>
                            <option value="ACTIVE" {{ old('campaign_status','PAUSED') === 'ACTIVE' ? 'selected' : '' }}>Active immédiatement</option>
                        </select>
                    </div>

                    {{-- Toggle campagne existante --}}
                    <div style="grid-column:1/-1;"
                         x-data="{
                            open:   {{ old('existing_campaign_id') ? 'true' : 'false' }},
                            manual: {{ ($existingCampaigns->isEmpty() || old('existing_campaign_id', '') !== '' && !$existingCampaigns->pluck('meta_campaign_id')->contains(old('existing_campaign_id', ''))) ? 'true' : 'false' }}
                         }">
                        <label style="display:flex; align-items:center; gap:.75rem; padding:.875rem 1rem; border:1.5px solid var(--color-border); border-radius:.625rem; cursor:pointer; transition:all .15s;"
                               :style="open ? 'border-color:var(--color-primary); background:var(--color-primary-light);' : ''">
                            <input type="checkbox" x-model="open"
                                   style="width:18px; height:18px; accent-color:var(--color-primary); flex-shrink:0; cursor:pointer;">
                            <div>
                                <div style="font-size:.875rem; font-weight:600; color:var(--color-heading);">Rattacher à une campagne existante</div>
                                <div style="font-size:.75rem; color:var(--color-muted);">
                                    L'Ad Set sera ajouté à une campagne déjà créée sur Meta Ads
                                    @if($existingCampaigns->isNotEmpty())
                                        · <strong style="color:var(--color-primary);">{{ $existingCampaigns->count() }} campagne(s) disponible(s)</strong>
                                    @endif
                                </div>
                            </div>
                        </label>

                        <div x-show="open" x-transition style="margin-top:.75rem;">

                            @if($existingCampaigns->isEmpty())
                            {{-- Aucune campagne en BDD : saisie manuelle uniquement --}}
                            <label class="form-label">ID de la campagne Meta <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="existing_campaign_id" class="form-control"
                                   value="{{ old('existing_campaign_id') }}"
                                   placeholder="ex: 120241034883010205">
                            <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">
                                Aucune campagne créée via l'app pour l'instant.
                                Retrouvez l'ID dans Meta Ads Manager → Campagnes → colonne ID.
                            </p>

                            @else
                            {{-- Select depuis les campagnes existantes --}}
                            <div x-show="!manual">
                                <label class="form-label">Choisir une campagne existante <span style="color:#ef4444;">*</span></label>
                                <select name="existing_campaign_id" class="form-control"
                                        :disabled="manual"
                                        style="font-family:inherit;">
                                    <option value="">— Sélectionner une campagne —</option>
                                    @foreach($existingCampaigns as $ec)
                                    <option value="{{ $ec->meta_campaign_id }}"
                                            {{ old('existing_campaign_id') === $ec->meta_campaign_id ? 'selected' : '' }}>
                                        {{ $ec->campaign_name }}
                                        &nbsp;·&nbsp;
                                        ID&nbsp;{{ $ec->meta_campaign_id }}
                                    </option>
                                    @endforeach
                                </select>
                                <p style="margin:.375rem 0 0; font-size:.75rem; color:var(--color-muted); display:flex; align-items:center; justify-content:space-between;">
                                    <span>Campagnes actives ou en pause créées via cette app.</span>
                                    <button type="button" @click="manual = true"
                                            style="background:none; border:none; color:var(--color-primary); font-size:.75rem; cursor:pointer; text-decoration:underline; padding:0;">
                                        Saisir un ID manuellement
                                    </button>
                                </p>
                            </div>

                            {{-- Fallback saisie manuelle --}}
                            <div x-show="manual" x-transition>
                                <label class="form-label">ID de la campagne Meta <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="existing_campaign_id" class="form-control"
                                       :disabled="!manual"
                                       value="{{ old('existing_campaign_id', '') }}"
                                       placeholder="ex: 120241034883010205">
                                <p style="margin:.375rem 0 0; font-size:.75rem; color:var(--color-muted); display:flex; align-items:center; justify-content:space-between;">
                                    <span>Retrouvez cet ID dans Meta Ads Manager → Campagnes → colonne ID.</span>
                                    <button type="button" @click="manual = false"
                                            style="background:none; border:none; color:var(--color-primary); font-size:.75rem; cursor:pointer; text-decoration:underline; padding:0;">
                                        ← Choisir dans la liste
                                    </button>
                                </p>
                            </div>
                            @endif

                            @error('existing_campaign_id')<div class="invalid-feedback" style="display:block; margin-top:.25rem;">{{ $message }}</div>@enderror
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
                        <div>Ciblage &amp; Budget</div>
                        <div style="font-size:.75rem; font-weight:400; color:var(--color-muted);">Qui voit votre pub, combien vous dépensez et pendant combien de temps</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom du groupe d'annonces <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="adset_name" class="form-control"
                               value="{{ $defaultAdsetName }}"
                               style="{{ $errors->has('adset_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('adset_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Type de budget</label>
                        <select name="budget_type" class="form-control" id="sel_budget_type" onchange="updateBudgetHint()">
                            <option value="lifetime_budget" {{ old('budget_type','lifetime_budget') === 'lifetime_budget' ? 'selected' : '' }}>Budget total (réparti sur la durée)</option>
                            <option value="daily_budget"    {{ old('budget_type','lifetime_budget') === 'daily_budget'    ? 'selected' : '' }}>Budget quotidien (par jour)</option>
                        </select>
                        <p id="budget_type_hint" style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">Le montant sera réparti automatiquement sur toute la durée</p>
                    </div>

                    <div>
                        <label class="form-label">Durée de diffusion <span style="color:#ef4444;">*</span></label>
                        <select name="duration_days" class="form-control" id="sel_duration" onchange="updateSummary()">
                            @foreach([1=>'1 jour',3=>'3 jours',7=>'7 jours (recommandé)',14=>'14 jours',30=>'30 jours'] as $d => $lbl)
                            <option value="{{ $d }}" {{ old('duration_days',7) == $d ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('duration_days')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Budget ($) <span style="color:#ef4444;">*</span></label>
                        <input type="hidden" name="budget_value" id="budget_value" value="{{ old('budget_value',10) }}">
                        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;" id="budgetPills">
                            @foreach([5,10,25,50,100] as $b)
                            <button type="button" onclick="selectBudget({{ $b }})" data-budget="{{ $b }}"
                                    style="padding:.5rem 1rem; border-radius:9999px; font-family:monospace; font-size:.875rem; cursor:pointer; transition:all .15s;
                                           border:2px solid {{ old('budget_value',10) == $b ? 'var(--color-primary)' : 'var(--color-border)' }};
                                           background:{{ old('budget_value',10) == $b ? 'var(--color-primary-light)' : '#fff' }};
                                           color:{{ old('budget_value',10) == $b ? 'var(--color-primary)' : '#374151' }};
                                           font-weight:{{ old('budget_value',10) == $b ? '700' : '500' }};">
                                ${{ $b }}
                            </button>
                            @endforeach
                            <input type="number" id="budget_custom" placeholder="Autre montant…" min="1" step="1"
                                   oninput="selectBudget(this.value ? parseInt(this.value) : null, true)"
                                   style="width:160px; padding:.5rem .75rem; border:2px solid var(--color-border); border-radius:.5rem; font-size:.875rem; outline:none;"
                                   onfocus="this.style.borderColor='var(--color-primary)'"
                                   onblur="if(!this.value)this.style.borderColor='var(--color-border)'">
                        </div>
                        @error('budget_value')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Pays cibles <span style="color:#ef4444;">*</span></label>
                        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem;" id="countriesGrid">
                            @foreach([
                                'CI'=>'🇨🇮 Côte d\'Ivoire', 'SN'=>'🇸🇳 Sénégal',
                                'ML'=>'🇲🇱 Mali',           'BF'=>'🇧🇫 Burkina Faso',
                                'GN'=>'🇬🇳 Guinée',         'TG'=>'🇹🇬 Togo',
                                'BJ'=>'🇧🇯 Bénin',          'CM'=>'🇨🇲 Cameroun',
                                'GH'=>'🇬🇭 Ghana',          'NG'=>'🇳🇬 Nigeria',
                                'MA'=>'🇲🇦 Maroc',          'FR'=>'🇫🇷 France',
                            ] as $code => $lbl)
                            @php $checked = in_array($code, old('countries', ['CI'])); @endphp
                            <label style="display:flex; align-items:center; gap:.5rem; padding:.5rem .75rem;
                                          border:1.5px solid {{ $checked ? 'var(--color-primary)' : 'var(--color-border)' }};
                                          border-radius:.5rem; cursor:pointer; font-size:.8125rem; transition:all .15s;
                                          background:{{ $checked ? 'var(--color-primary-light)' : '#fff' }};
                                          color:{{ $checked ? 'var(--color-primary)' : '#374151' }};
                                          font-weight:{{ $checked ? '600' : '400' }};">
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

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Centres d'intérêt</label>
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
                                '6003279598823'=>'Marketing',       '6003127206524'=>'Digital Marketing',
                                '6003389760112'=>'Social Media',    '6003232518619'=>'Publicité',
                                '6003139057932'=>'Entrepreneuriat', '6002990402487'=>'Automobile',
                                '6003107902433'=>'Business',        '6002925729260'=>'Technologie',
                                '6003330421807'=>'Vidéo',           '6003008043877'=>'E-commerce',
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

                    <div>
                        <label class="form-label">Optimiser pour</label>
                        <select name="optimization_goal" class="form-control">
                            <option value="LINK_CLICKS"        {{ old('optimization_goal','LINK_CLICKS') === 'LINK_CLICKS'        ? 'selected' : '' }}>Clics sur le lien (recommandé)</option>
                            <option value="IMPRESSIONS"        {{ old('optimization_goal','LINK_CLICKS') === 'IMPRESSIONS'        ? 'selected' : '' }}>Nombre d'affichages</option>
                            <option value="REACH"              {{ old('optimization_goal','LINK_CLICKS') === 'REACH'              ? 'selected' : '' }}>Portée maximale</option>
                            <option value="LANDING_PAGE_VIEWS" {{ old('optimization_goal','LINK_CLICKS') === 'LANDING_PAGE_VIEWS' ? 'selected' : '' }}>Vues de la page de destination</option>
                            <option value="VIDEO_VIEWS"        {{ old('optimization_goal','LINK_CLICKS') === 'VIDEO_VIEWS'        ? 'selected' : '' }}>Vues de la vidéo</option>
                            <option value="POST_ENGAGEMENT"    {{ old('optimization_goal','LINK_CLICKS') === 'POST_ENGAGEMENT'    ? 'selected' : '' }}>Interactions avec le post</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Mode de facturation</label>
                        <select name="billing_event" class="form-control">
                            <option value="IMPRESSIONS"     {{ old('billing_event','IMPRESSIONS') === 'IMPRESSIONS'     ? 'selected' : '' }}>À chaque affichage (recommandé)</option>
                            <option value="LINK_CLICKS"     {{ old('billing_event','IMPRESSIONS') === 'LINK_CLICKS'     ? 'selected' : '' }}>À chaque clic sur le lien</option>
                            <option value="POST_ENGAGEMENT" {{ old('billing_event','IMPRESSIONS') === 'POST_ENGAGEMENT' ? 'selected' : '' }}>À chaque interaction avec le post</option>
                            <option value="VIDEO_VIEWS"     {{ old('billing_event','IMPRESSIONS') === 'VIDEO_VIEWS'     ? 'selected' : '' }}>À chaque vue de la vidéo</option>
                        </select>
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Stratégie de coût</label>
                        <select name="bid_strategy" class="form-control">
                            <option value="LOWEST_COST_WITHOUT_CAP"  {{ old('bid_strategy','LOWEST_COST_WITHOUT_CAP') === 'LOWEST_COST_WITHOUT_CAP'  ? 'selected' : '' }}>Coût minimum automatique (recommandé)</option>
                            <option value="LOWEST_COST_WITH_BID_CAP" {{ old('bid_strategy','LOWEST_COST_WITHOUT_CAP') === 'LOWEST_COST_WITH_BID_CAP' ? 'selected' : '' }}>Coût minimum avec plafond d'enchère</option>
                            <option value="COST_CAP"                 {{ old('bid_strategy','LOWEST_COST_WITHOUT_CAP') === 'COST_CAP'                 ? 'selected' : '' }}>Plafond de coût cible fixe</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             SECTION 3 — ANNONCE
        ════════════════════════════════ --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:0.625rem;">
                    <i class="fas fa-image" style="color:var(--color-primary);"></i>
                    <div>
                        <div>Annonce</div>
                        <div style="font-size:.75rem; font-weight:400; color:var(--color-muted);">Le post Facebook que vous souhaitez mettre en avant</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Nom de l'annonce <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="ad_name" class="form-control"
                               value="{{ $defaultAdName }}"
                               style="{{ $errors->has('ad_name') ? 'border-color:#ef4444;' : '' }}">
                        @error('ad_name')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="form-label">Post Facebook à booster <span style="color:#ef4444;">*</span></label>

                        @if($post)
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
                        <input type="text" name="post_id" class="form-control"
                               value="{{ old('post_id') }}"
                               placeholder="ex: 668925849823227_1234567890123"
                               style="{{ $errors->has('post_id') ? 'border-color:#ef4444;' : '' }}">
                        <p style="margin:.25rem 0 0; font-size:.75rem; color:var(--color-muted);">
                            Format : PAGE_ID_POST_ID · Ou <a href="{{ route('posts.index') }}" style="color:var(--color-primary);">sélectionner depuis la liste des posts</a>
                        </p>
                        @error('post_id')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <div>
                        <label class="form-label">Statut de l'annonce</label>
                        <select name="ad_status" class="form-control">
                            <option value="PAUSED" {{ old('ad_status','PAUSED') === 'PAUSED' ? 'selected' : '' }}>En pause (recommandé)</option>
                            <option value="ACTIVE" {{ old('ad_status','PAUSED') === 'ACTIVE' ? 'selected' : '' }}>Active dès création</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             RÉCAP + BOUTON
        ════════════════════════════════ --}}
        <div class="card" style="border-color:var(--color-primary);">
            <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <div style="display:flex; gap:2rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Budget</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_budget">$10.00</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Durée</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_duration">7 jours</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Objectif</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_objective">Trafic</div>
                    </div>
                    <div>
                        <div style="font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted);">Statut</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-primary);" id="s_status">En pause</div>
                    </div>
                </div>
                <div style="display:flex; gap:.75rem; align-items:center;">
                    <a href="{{ route('posts.index') }}" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Enregistrer la campagne
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
var currentBudget = {{ old('budget_value', 10) }};

function selectBudget(val, fromCustom) {
    if (!val || val < 1) return;
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

function updateBudgetHint() {
    var type = document.getElementById('sel_budget_type').value;
    document.getElementById('budget_type_hint').textContent = type === 'lifetime_budget'
        ? 'Le montant sera réparti automatiquement sur toute la durée'
        : 'Ce montant est dépensé chaque jour — total = budget × durée';
}

var objLabels = {
    'OUTCOME_TRAFFIC': 'Trafic', 'OUTCOME_AWARENESS': 'Notoriété',
    'OUTCOME_ENGAGEMENT': 'Engagement', 'OUTCOME_LEADS': 'Prospects',
    'OUTCOME_SALES': 'Ventes', 'OUTCOME_APP_PROMOTION': 'Application'
};

function updateSummary() {
    var budget   = parseInt(document.getElementById('budget_value').value) || 0;
    var duration = parseInt(document.getElementById('sel_duration').value) || 7;
    var objSel   = document.getElementById('sel_objective');
    var stSel    = document.getElementById('sel_status');
    var obj      = objSel ? (objLabels[objSel.value] || objSel.value.replace('OUTCOME_','')) : 'Trafic';
    var st       = stSel  ? (stSel.value === 'PAUSED' ? 'En pause' : 'Active') : 'En pause';

    document.getElementById('s_budget').textContent   = '$' + budget.toFixed(2);
    document.getElementById('s_duration').textContent = duration + ' jour' + (duration > 1 ? 's' : '');
    document.getElementById('s_objective').textContent = obj;
    document.getElementById('s_status').textContent   = st;
}

document.getElementById('boostForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
});

updateSummary();
</script>
@endpush
