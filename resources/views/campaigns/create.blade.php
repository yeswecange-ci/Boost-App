@extends('layouts.app')

@section('page-title', 'Nouvelle campagne')
@section('page-subtitle', 'Agent Media Buyer YWC — Créer une campagne Meta Ads')

@section('content')

<div style="max-width:900px; margin:0 auto;" x-data="campaignForm()">

    {{-- ═══ SECTION 1 — CAMPAGNE ═══ --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:0.625rem;">
                <i class="fas fa-bullhorn" style="color:var(--color-primary);"></i>
                <div>
                    <div style="font-weight:700; font-size:0.9375rem;">Campagne</div>
                    <div style="font-size:0.75rem; color:var(--color-muted); font-family:monospace;">Niveau 1 · Campaign Object · Meta Ads API</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                {{-- Nom campagne --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">Nom de la campagne <span style="color:#ef4444;">*</span></label>
                    <input type="text" x-model="form.campaign_name" class="form-control"
                        placeholder="ex: BOOST POST – Lancement Peugeot 3008 – Mar 2026">
                    <div class="hint" style="font-size:0.75rem; color:var(--color-muted); margin-top:0.25rem;">Inclure le mois et l'objectif pour retrouver facilement dans Meta Ads Manager</div>
                </div>

                {{-- Objectif --}}
                <div>
                    <label class="form-label">
                        Objectif <span style="color:#ef4444;">*</span>
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">objective</span>
                    </label>
                    <select x-model="form.campaign_objective" class="form-control">
                        <option value="OUTCOME_TRAFFIC">OUTCOME_TRAFFIC — Trafic (défaut)</option>
                        <option value="OUTCOME_AWARENESS">OUTCOME_AWARENESS — Notoriété</option>
                        <option value="OUTCOME_ENGAGEMENT">OUTCOME_ENGAGEMENT — Engagement</option>
                        <option value="OUTCOME_LEADS">OUTCOME_LEADS — Leads</option>
                        <option value="OUTCOME_SALES">OUTCOME_SALES — Ventes</option>
                    </select>
                </div>

                {{-- Catégorie spéciale --}}
                <div>
                    <label class="form-label">
                        Catégorie spéciale
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">special_ad_categories</span>
                    </label>
                    <select x-model="form.special_ad_categories" class="form-control">
                        <option value="NONE">NONE — Aucune (défaut)</option>
                        <option value="CREDIT">CREDIT — Crédit / Finance</option>
                        <option value="EMPLOYMENT">EMPLOYMENT — Emploi</option>
                        <option value="HOUSING">HOUSING — Immobilier</option>
                        <option value="ISSUES_ELECTIONS_POLITICS">ISSUES_ELECTIONS_POLITICS — Politique</option>
                    </select>
                </div>

                {{-- Statut --}}
                <div>
                    <label class="form-label">
                        Statut initial
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">status</span>
                    </label>
                    <select x-model="form.campaign_status" class="form-control">
                        <option value="PAUSED">PAUSED — En pause (recommandé)</option>
                        <option value="ACTIVE">ACTIVE — Active immédiatement</option>
                    </select>
                    <div style="font-size:0.75rem; color:var(--color-muted); margin-top:0.25rem;">PAUSED permet de vérifier avant diffusion — approche Co-Pilot</div>
                </div>

                {{-- Campagne existante --}}
                <div>
                    <label class="form-label">ID campagne existante
                        <span style="font-family:monospace; font-size:0.7rem; color:var(--color-muted);">(optionnel)</span>
                    </label>
                    <input type="text" x-model="form.existing_campaign_id" class="form-control"
                        placeholder="ex: 120241034883010205">
                    <div style="font-size:0.75rem; color:var(--color-muted); margin-top:0.25rem;">Laisser vide pour créer une nouvelle campagne</div>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══ SECTION 2 — AD SET ═══ --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:0.625rem;">
                <i class="fas fa-crosshairs" style="color:var(--color-primary);"></i>
                <div>
                    <div style="font-weight:700; font-size:0.9375rem;">Ad Set — Ciblage & Budget</div>
                    <div style="font-size:0.75rem; color:var(--color-muted); font-family:monospace;">Niveau 2 · AdSet Object · QUI voit la pub et COMBIEN on dépense</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                {{-- Nom Ad Set --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">Nom de l'Ad Set <span style="color:#ef4444;">*</span></label>
                    <input type="text" x-model="form.adset_name" class="form-control"
                        placeholder="ex: AdSet CI – Marketing – 7j">
                </div>

                {{-- Budget type --}}
                <div>
                    <label class="form-label">
                        Type de budget
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">budget_type</span>
                    </label>
                    <select x-model="form.budget_type" class="form-control">
                        <option value="lifetime_budget">lifetime_budget — Budget total sur la période</option>
                        <option value="daily_budget">daily_budget — Budget par jour</option>
                    </select>
                </div>

                {{-- Durée --}}
                <div>
                    <label class="form-label">
                        Durée
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">end_time</span>
                    </label>
                    <select x-model="form.duration_days" class="form-control">
                        <option value="1">1 jour</option>
                        <option value="3">3 jours</option>
                        <option value="7" selected>7 jours (défaut)</option>
                        <option value="14">14 jours</option>
                        <option value="30">30 jours</option>
                    </select>
                </div>

                {{-- Budget pills --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">
                        Budget (FCFA) <span style="color:#ef4444;">*</span>
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">budget × 100 centimes → API</span>
                    </label>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.5rem;">
                        @foreach([3500, 7000, 14000, 35000, 70000] as $preset)
                        <button type="button"
                            @click="selectBudget({{ $preset }})"
                            :class="form.budget_value == {{ $preset }} ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'">
                            {{ number_format($preset, 0, ',', ' ') }}
                        </button>
                        @endforeach
                        <input type="number" class="form-control" style="width:160px;"
                            placeholder="Autre montant…"
                            @input="form.budget_value = $event.target.value">
                    </div>
                    <div style="font-size:0.75rem; color:var(--color-muted);">
                        Meta reçoit la valeur en centimes — ex: 7 000 FCFA = 700 centimes
                    </div>
                </div>

                {{-- Pays --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">
                        Pays cibles
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">geo_locations.countries</span>
                    </label>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        @foreach(['CI'=>"🇨🇮 Côte d'Ivoire",'SN'=>'🇸🇳 Sénégal','ML'=>'🇲🇱 Mali','BF'=>'🇧🇫 Burkina Faso','GN'=>'🇬🇳 Guinée','TG'=>'🇹🇬 Togo','BJ'=>'🇧🇯 Bénin','CM'=>'🇨🇲 Cameroun','GH'=>'🇬🇭 Ghana','NG'=>'🇳🇬 Nigeria','MA'=>'🇲🇦 Maroc','FR'=>'🇫🇷 France'] as $code => $label)
                        <button type="button"
                            @click="toggleCountry('{{ $code }}')"
                            :class="form.countries.includes('{{ $code }}') ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'"
                            style="font-size:0.8125rem;">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Centres d'intérêt --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">
                        Centres d'intérêt
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">targeting.interests</span>
                    </label>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        @foreach([
                            '6003279598823' => 'Marketing',
                            '6003127206524' => 'Digital Marketing',
                            '6003389760112' => 'Social Media Marketing',
                            '6003232518619' => 'Advertising',
                            '6003139057932' => 'Entrepreneurship',
                            '6002990402487' => 'Automobile',
                            '6003107902433' => 'Business',
                            '6002925729260' => 'Technology',
                            '6003330421807' => 'Video',
                            '6003008043877' => 'E-commerce',
                        ] as $id => $label)
                        <button type="button"
                            @click="toggleInterest('{{ $id }}')"
                            :class="hasInterest('{{ $id }}') ? 'btn-primary btn-sm' : 'btn-secondary btn-sm'"
                            style="font-size:0.8125rem;">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Optimization goal --}}
                <div>
                    <label class="form-label">
                        Optimization Goal
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">optimization_goal</span>
                    </label>
                    <select x-model="form.optimization_goal" class="form-control">
                        <option value="LINK_CLICKS">LINK_CLICKS — Clics lien (défaut)</option>
                        <option value="IMPRESSIONS">IMPRESSIONS — Impressions</option>
                        <option value="REACH">REACH — Portée</option>
                        <option value="VIDEO_VIEWS">VIDEO_VIEWS — Vues vidéo</option>
                        <option value="POST_ENGAGEMENT">POST_ENGAGEMENT — Engagement</option>
                    </select>
                </div>

                {{-- Billing event --}}
                <div>
                    <label class="form-label">
                        Billing Event
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">billing_event</span>
                    </label>
                    <select x-model="form.billing_event" class="form-control">
                        <option value="IMPRESSIONS">IMPRESSIONS — Aux impressions (défaut)</option>
                        <option value="LINK_CLICKS">LINK_CLICKS — Aux clics</option>
                        <option value="POST_ENGAGEMENT">POST_ENGAGEMENT — À l'engagement</option>
                    </select>
                </div>

                {{-- Bid strategy --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">
                        Stratégie d'enchères
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">bid_strategy</span>
                    </label>
                    <select x-model="form.bid_strategy" class="form-control">
                        <option value="LOWEST_COST_WITHOUT_CAP">LOWEST_COST_WITHOUT_CAP — Coût le plus bas auto (défaut)</option>
                        <option value="LOWEST_COST_WITH_BID_CAP">LOWEST_COST_WITH_BID_CAP — Coût le plus bas avec plafond</option>
                        <option value="COST_CAP">COST_CAP — Plafond de coût cible</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══ SECTION 3 — AD ═══ --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:0.625rem;">
                <i class="fas fa-image" style="color:var(--color-primary);"></i>
                <div>
                    <div style="font-weight:700; font-size:0.9375rem;">Ad — Création publicitaire</div>
                    <div style="font-size:0.75rem; color:var(--color-muted); font-family:monospace;">Niveau 3 · Ad Object · Le post Facebook qui sera boosté</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">

                {{-- Nom Ad --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">Nom de l'Ad <span style="color:#ef4444;">*</span></label>
                    <input type="text" x-model="form.ad_name" class="form-control"
                        value="Ad – Boost Existing Post">
                </div>

                {{-- Post ID --}}
                <div style="grid-column:1/-1;">
                    <label class="form-label">
                        ID du post Facebook à booster <span style="color:#ef4444;">*</span>
                        <span style="font-family:monospace; font-size:0.7rem; background:var(--color-primary-light); color:var(--color-primary); padding:1px 6px; border-radius:3px; margin-left:4px;">object_story_id</span>
                    </label>

                    @if($posts->isNotEmpty())
                    <div style="margin-bottom:0.75rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:0.5rem; max-height:260px; overflow-y:auto; padding:0.5rem; background:#f8fafc; border-radius:0.5rem; border:1px solid var(--color-border);">
                        @foreach($posts as $post)
                        <div @click="selectPost('{{ $post->post_id }}')"
                             :class="form.post_id === '{{ $post->post_id }}' ? 'border-2' : ''"
                             :style="form.post_id === '{{ $post->post_id }}' ? 'border:2px solid var(--color-primary); border-radius:0.5rem;' : 'border:1px solid var(--color-border); border-radius:0.5rem;'"
                             style="cursor:pointer; padding:0.5rem; background:#fff; display:flex; gap:0.5rem; align-items:center;">
                            @if($post->thumbnail_url)
                            <img src="{{ $post->thumbnail_url }}" style="width:48px; height:48px; object-fit:cover; border-radius:0.375rem; flex-shrink:0;">
                            @else
                            <div style="width:48px; height:48px; background:var(--color-primary-light); border-radius:0.375rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fab fa-facebook" style="color:var(--color-primary);"></i>
                            </div>
                            @endif
                            <div style="font-size:0.75rem; overflow:hidden;">
                                <div style="font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Str::limit($post->message, 40) ?: 'Post sans texte' }}</div>
                                <div style="color:var(--color-muted); font-family:monospace; font-size:0.65rem;">{{ $post->post_id }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <input type="text" x-model="form.post_id" class="form-control"
                        placeholder="ex: 668925849823227_14"
                        style="font-family:monospace;">
                    <div style="font-size:0.75rem; color:var(--color-muted); margin-top:0.25rem;">
                        Format : <code>PAGE_ID_POST_ID</code> · Retrouvable via Meta Graph API Explorer
                    </div>
                </div>

                {{-- Statut Ad --}}
                <div>
                    <label class="form-label">Statut de l'Ad</label>
                    <select x-model="form.ad_status" class="form-control">
                        <option value="PAUSED">PAUSED — En pause (recommandé)</option>
                        <option value="ACTIVE">ACTIVE — Active dès création</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══ RÉCAP + SUBMIT ═══ --}}
    <div class="card">
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; gap:1.5rem; flex-wrap:wrap;">

            {{-- Récap --}}
            <div style="display:flex; gap:2rem; flex-wrap:wrap;">
                <div>
                    <div style="font-size:0.7rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; font-family:monospace;">Budget</div>
                    <div style="font-size:1.25rem; font-weight:700; color:var(--color-primary);" x-text="budgetFormatted()">7 000 FCFA</div>
                </div>
                <div>
                    <div style="font-size:0.7rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; font-family:monospace;">Durée</div>
                    <div style="font-size:1.25rem; font-weight:700; color:var(--color-primary);" x-text="form.duration_days + ' jour(s)'">7 jour(s)</div>
                </div>
                <div>
                    <div style="font-size:0.7rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; font-family:monospace;">Objectif</div>
                    <div style="font-size:1.25rem; font-weight:700; color:var(--color-primary);" x-text="form.campaign_objective.replace('OUTCOME_','')">TRAFFIC</div>
                </div>
                <div>
                    <div style="font-size:0.7rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; font-family:monospace;">Statut</div>
                    <div style="font-size:1.25rem; font-weight:700; color:var(--color-primary);" x-text="form.campaign_status">PAUSED</div>
                </div>
            </div>

            {{-- Erreur --}}
            <div x-show="errorMsg" style="display:none; color:#ef4444; font-size:0.875rem;" x-text="errorMsg"></div>

            {{-- Bouton --}}
            <button type="button" class="btn-primary" @click="submitCampaign()" :disabled="loading"
                style="display:flex; align-items:center; gap:0.5rem; min-width:180px; justify-content:center;">
                <i class="fas fa-rocket" x-show="!loading"></i>
                <i class="fas fa-spinner fa-spin" x-show="loading" style="display:none;"></i>
                <span x-text="loading ? 'Envoi en cours…' : 'Enregistrer & Envoyer'">Enregistrer & Envoyer</span>
            </button>

        </div>
    </div>

    {{-- ═══ OVERLAY SUCCÈS ═══ --}}
    <div x-show="success" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(4px); z-index:200; display:flex; align-items:center; justify-content:center;">
        <div class="card" style="max-width:420px; width:90%; text-align:center; padding:2.5rem;">
            <div style="width:64px; height:64px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; font-size:1.75rem;">
                <i class="fas fa-check" style="color:#15803d;"></i>
            </div>
            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:0.5rem;">Campagne enregistrée !</h2>
            <p style="color:var(--color-muted); font-size:0.875rem; line-height:1.6; margin-bottom:1rem;">
                Les données ont été sauvegardées. L'ID ci-dessous a été envoyé au workflow n8n.
            </p>
            <div style="display:inline-block; padding:0.625rem 1.5rem; background:#f8fafc; border:1px solid var(--color-border); border-radius:0.5rem; font-family:monospace; font-size:1.25rem; color:var(--color-primary); font-weight:700; letter-spacing:2px; margin-bottom:1rem;"
                x-text="'#' + createdId"></div>
            <div style="font-size:0.75rem; color:var(--color-muted); margin-bottom:1.5rem; font-family:monospace;">
                n8n récupère toutes les valeurs via SQL → exécute l'API Meta Ads
            </div>
            <a :href="redirectUrl" class="btn-primary" style="display:inline-flex;">
                <i class="fas fa-list"></i> Voir mes campagnes
            </a>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function campaignForm() {
    return {
        loading: false,
        success: false,
        errorMsg: '',
        createdId: null,
        redirectUrl: '{{ route('campaigns.index') }}',

        form: {
            campaign_name:         'BOOST POST MVP – ',
            campaign_objective:    'OUTCOME_TRAFFIC',
            special_ad_categories: 'NONE',
            campaign_status:       'PAUSED',
            existing_campaign_id:  '',
            adset_name:            'AdSet CI – Marketing & Communication – 7j',
            budget_type:           'lifetime_budget',
            budget_value:          7000,
            duration_days:         7,
            countries:             ['CI'],
            interests:             [
                { id: '6003279598823' },
                { id: '6003127206524' },
                { id: '6003389760112' },
            ],
            optimization_goal: 'LINK_CLICKS',
            billing_event:     'IMPRESSIONS',
            bid_strategy:      'LOWEST_COST_WITHOUT_CAP',
            ad_name:           'Ad – Boost Existing Post',
            post_id:           '',
            ad_status:         'PAUSED',
        },

        selectBudget(val) {
            this.form.budget_value = val;
        },

        budgetFormatted() {
            return new Intl.NumberFormat('fr-FR').format(this.form.budget_value || 0) + ' FCFA';
        },

        toggleCountry(code) {
            const idx = this.form.countries.indexOf(code);
            if (idx >= 0) {
                if (this.form.countries.length === 1) return;
                this.form.countries.splice(idx, 1);
            } else {
                this.form.countries.push(code);
            }
        },

        toggleInterest(id) {
            const idx = this.form.interests.findIndex(i => i.id === id);
            if (idx >= 0) {
                this.form.interests.splice(idx, 1);
            } else {
                this.form.interests.push({ id });
            }
        },

        hasInterest(id) {
            return this.form.interests.some(i => i.id === id);
        },

        selectPost(postId) {
            this.form.post_id = postId;
        },

        async submitCampaign() {
            this.errorMsg = '';

            // Validation basique
            if (!this.form.campaign_name.trim()) { this.errorMsg = 'Le nom de la campagne est requis.'; return; }
            if (!this.form.adset_name.trim())     { this.errorMsg = 'Le nom de l\'Ad Set est requis.'; return; }
            if (!this.form.post_id.trim())         { this.errorMsg = 'L\'ID du post est requis.'; return; }
            if (!this.form.budget_value || this.form.budget_value < 500) {
                this.errorMsg = 'Budget minimum : 500 FCFA.'; return;
            }

            this.loading = true;

            try {
                const payload = {
                    ...this.form,
                    _token: document.querySelector('meta[name="csrf-token"]').content,
                };

                const res = await axios.post('{{ route('campaigns.store') }}', payload);

                if (res.data.success) {
                    this.createdId   = res.data.campaign_id;
                    this.redirectUrl = res.data.redirect;
                    this.success     = true;
                }
            } catch (err) {
                const errors = err.response?.data?.errors;
                if (errors) {
                    this.errorMsg = Object.values(errors).flat().join(' ');
                } else {
                    this.errorMsg = 'Une erreur est survenue. Veuillez réessayer.';
                }
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
