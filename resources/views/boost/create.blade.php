@extends('layouts.app')

@section('page-title', 'Nouveau boost')
@section('page-subtitle', 'Configurez votre campagne de boost')

@section('content')

<div style="display:grid; grid-template-columns: 300px 1fr; gap:1.5rem; align-items:start;">

    {{-- ── LEFT : Post preview (sticky) ── --}}
    <div style="position:sticky; top:calc(var(--header-height) + 1rem);">
        <div class="card" style="overflow:hidden;">
            <div class="card-header" style="background:linear-gradient(135deg,#4f46e5,#7c3aed); color:white; border:none;">
                <i class="fab fa-facebook"></i>
                Aperçu du post
            </div>

            @if($post['thumbnail'])
            <img src="{{ $post['thumbnail'] }}"
                 style="width:100%; max-height:200px; object-fit:cover; display:block;">
            @else
            <div style="height:160px; background:linear-gradient(135deg,#eef2ff,#f3e8ff); display:flex; align-items:center; justify-content:center;">
                <i class="fab fa-facebook" style="font-size:3rem; color:#a5b4fc;"></i>
            </div>
            @endif

            <div class="card-body">
                <span style="display:inline-flex; align-items:center; gap:0.25rem; padding:0.2rem 0.6rem; background:var(--color-primary-light); color:var(--color-primary); border-radius:9999px; font-size:0.75rem; font-weight:600; margin-bottom:0.625rem;">
                    <i class="fas fa-page4"></i>
                    {{ $page->page_name }}
                </span>
                <p style="font-size:0.8125rem; color:#475569; margin:0; line-height:1.5;
                    display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden;">
                    {{ $post['message'] ?: '(Aucun texte)' }}
                </p>
                <div style="margin-top:0.75rem; font-size:0.75rem; color:#94a3b8;">
                    <i class="far fa-clock" style="margin-right:0.25rem;"></i>
                    {{ \Carbon\Carbon::parse($post['created_time'])->diffForHumans() }}
                </div>
            </div>

            <div class="card-footer">
                <a href="{{ $post['permalink_url'] }}" target="_blank"
                   class="btn-secondary btn-sm" style="width:100%; justify-content:center;">
                    <i class="fab fa-facebook" style="color:#1877f2;"></i>
                    Voir sur Facebook
                </a>
            </div>
        </div>
    </div>

    {{-- ── RIGHT : Form ── --}}
    <form action="{{ route('boost.store') }}" method="POST" id="boostForm">
        @csrf
        <input type="hidden" name="post_id"        value="{{ $post['id'] }}">
        <input type="hidden" name="page_id"        value="{{ $page->page_id }}">
        <input type="hidden" name="post_url"       value="{{ $post['permalink_url'] }}">
        <input type="hidden" name="post_thumbnail" value="{{ $post['thumbnail'] }}">
        <input type="hidden" name="post_message"   value="{{ $post['message'] }}">

        {{-- ── Section 1 : Planning & Budget ── --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-calendar-alt" style="color:var(--color-primary);"></i>
                Planning & Budget
            </div>
            <div class="card-body">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div>
                        <label class="form-label">Date de début <span style="color:#dc2626;">*</span></label>
                        <input type="date" name="start_date"
                               class="form-control {{ $errors->has('start_date') ? 'is-invalid' : '' }}"
                               value="{{ old('start_date') }}"
                               min="{{ date('Y-m-d') }}"
                               required>
                        @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Date de fin <span style="color:#dc2626;">*</span></label>
                        <input type="date" name="end_date"
                               class="form-control {{ $errors->has('end_date') ? 'is-invalid' : '' }}"
                               value="{{ old('end_date') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               required>
                        @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr auto; gap:1rem; align-items:end;">
                    <div>
                        <label class="form-label">Budget total <span style="color:#dc2626;">*</span></label>
                        <div style="display:flex; gap:0;">
                            <input type="number" name="budget"
                                   class="form-control {{ $errors->has('budget') ? 'is-invalid' : '' }}"
                                   style="border-radius:0.5rem 0 0 0.5rem; border-right:0;"
                                   placeholder="Ex: 50 000"
                                   value="{{ old('budget') }}"
                                   min="1000" step="500" required>
                            <select name="currency"
                                    style="padding:0.5rem 0.625rem; border:1px solid var(--color-border); border-left:0; border-radius:0 0.5rem 0.5rem 0; background:#f8fafc; font-size:0.875rem; color:#374151; cursor:pointer; outline:none; flex-shrink:0;">
                                @foreach($currencies as $cur)
                                <option value="{{ $cur }}" {{ old('currency','XOF') == $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('budget')
                        <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                        @enderror
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">Budget minimum : 1 000 XOF</div>
                    </div>

                    <div>
                        <label class="form-label">Durée estimée</label>
                        <div id="durationDisplay"
                             style="padding:0.5rem 0.75rem; background:#f8fafc; border:1px solid var(--color-border); border-radius:0.5rem; font-size:0.875rem; font-weight:600; color:#94a3b8; text-align:center; white-space:nowrap;">
                            — jours
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Section 2 : Audience ── --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-users" style="color:var(--color-primary);"></i>
                Audience cible
            </div>
            <div class="card-body">

                {{-- Âge --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label">Tranche d'âge <span style="color:#dc2626;">*</span></label>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="display:flex; align-items:center; border:1px solid var(--color-border); border-radius:0.5rem; overflow:hidden; flex:1;">
                            <span style="padding:0.5rem 0.75rem; background:#f8fafc; font-size:0.8125rem; color:#64748b; border-right:1px solid var(--color-border); flex-shrink:0;">Min</span>
                            <input type="number" name="target[age_min]"
                                   class="form-control {{ $errors->has('target.age_min') ? 'is-invalid' : '' }}"
                                   style="border:none; border-radius:0;"
                                   value="{{ old('target.age_min', 18) }}"
                                   min="13" max="65" required>
                        </div>
                        <span style="color:#94a3b8; font-weight:600;">—</span>
                        <div style="display:flex; align-items:center; border:1px solid var(--color-border); border-radius:0.5rem; overflow:hidden; flex:1;">
                            <span style="padding:0.5rem 0.75rem; background:#f8fafc; font-size:0.8125rem; color:#64748b; border-right:1px solid var(--color-border); flex-shrink:0;">Max</span>
                            <input type="number" name="target[age_max]"
                                   class="form-control {{ $errors->has('target.age_max') ? 'is-invalid' : '' }}"
                                   style="border:none; border-radius:0;"
                                   value="{{ old('target.age_max', 45) }}"
                                   min="13" max="65" required>
                        </div>
                        <span style="font-size:0.8125rem; color:#94a3b8; white-space:nowrap;">ans</span>
                    </div>
                </div>

                {{-- Genre --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label">Genre <span style="color:#dc2626;">*</span></label>
                    <div style="display:flex; gap:0.625rem;">
                        @foreach(['all'=>['label'=>'Tous','icon'=>'fa-users'],'male'=>['label'=>'Hommes','icon'=>'fa-mars'],'female'=>['label'=>'Femmes','icon'=>'fa-venus']] as $val => $opt)
                        <label style="flex:1; cursor:pointer;">
                            <input type="radio" name="target[gender]" value="{{ $val }}"
                                   {{ old('target.gender','all') == $val ? 'checked' : '' }}
                                   style="display:none;"
                                   class="gender-radio">
                            <div class="gender-btn" data-val="{{ $val }}"
                                 style="
                                    padding:0.5rem 0.75rem;
                                    border:2px solid {{ old('target.gender','all') == $val ? 'var(--color-primary)' : 'var(--color-border)' }};
                                    border-radius:0.5rem;
                                    text-align:center;
                                    font-size:0.875rem;
                                    font-weight:500;
                                    color:{{ old('target.gender','all') == $val ? 'var(--color-primary)' : '#64748b' }};
                                    background:{{ old('target.gender','all') == $val ? 'var(--color-primary-light)' : '#fff' }};
                                    transition:all 0.15s;
                                 ">
                                <i class="fas {{ $opt['icon'] }}" style="margin-right:0.375rem;"></i>
                                {{ $opt['label'] }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Pays --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label">
                        Pays cibles <span style="color:#dc2626;">*</span>
                        <span id="countryCount"
                              style="margin-left:0.5rem; padding:0.15rem 0.5rem; background:var(--color-primary); color:#fff; border-radius:9999px; font-size:0.6875rem; font-weight:700;">
                            0
                        </span>
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal;"> sélectionné(s)</span>
                    </label>
                    <div style="border:1px solid var(--color-border); border-radius:0.5rem; padding:0.875rem; max-height:220px; overflow-y:auto;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:0.375rem;">
                            @foreach($countries as $code => $name)
                            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.25rem; border-radius:0.375rem; transition:background 0.1s;"
                                   onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox"
                                       class="country-check"
                                       name="target[countries][]"
                                       value="{{ $code }}"
                                       id="country_{{ $code }}"
                                       {{ in_array($code, old('target.countries', ['CI'])) ? 'checked' : '' }}
                                       style="accent-color:var(--color-primary); width:1rem; height:1rem; flex-shrink:0;">
                                <span style="font-size:0.8125rem; color:#374151;">{{ $name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @error('target.countries')
                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Intérêts --}}
                <div>
                    <label class="form-label">
                        Intérêts
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal;">(optionnel)</span>
                    </label>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        @foreach($interests as $interest)
                        <label style="cursor:pointer;">
                            <input type="checkbox"
                                   name="target[interests][]"
                                   value="{{ $interest }}"
                                   id="interest_{{ Str::slug($interest) }}"
                                   {{ in_array($interest, old('target.interests', [])) ? 'checked' : '' }}
                                   style="display:none;"
                                   class="interest-check">
                            <span class="interest-badge"
                                  style="display:inline-block; padding:0.25rem 0.75rem; border:1px solid var(--color-border); border-radius:9999px; font-size:0.8125rem; color:#64748b; transition:all 0.15s; {{ in_array($interest, old('target.interests', [])) ? 'background:var(--color-primary-light); color:var(--color-primary); border-color:var(--color-primary);' : '' }}">
                                {{ $interest }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Section 3 : Paramètres de diffusion ── --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-sliders" style="color:var(--color-primary);"></i>
                Paramètres de diffusion
            </div>
            <div class="card-body">

                {{-- Sensibilité --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label">
                        Sensibilité du contenu <span style="color:#dc2626;">*</span>
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal;">(détermine le circuit de validation)</span>
                    </label>
                    <div style="display:flex; gap:0.625rem;">
                        @foreach([
                            'faible'  => ['label'=>'Faible', 'desc'=>'Validation N+1 uniquement', 'color'=>'#16a34a', 'bg'=>'#dcfce7', 'icon'=>'fa-leaf'],
                            'moyenne' => ['label'=>'Moyenne','desc'=>'Validation N+1 + N+2',      'color'=>'#d97706', 'bg'=>'#fef9c3', 'icon'=>'fa-exclamation'],
                            'elevee'  => ['label'=>'Élevée', 'desc'=>'Validation N+1 + N+2 obligatoire','color'=>'#dc2626','bg'=>'#fee2e2','icon'=>'fa-shield-halved'],
                        ] as $val => $opt)
                        <label style="flex:1; cursor:pointer;">
                            <input type="radio" name="sensitivity" value="{{ $val }}"
                                   {{ old('sensitivity','faible') === $val ? 'checked' : '' }}
                                   style="display:none;" class="sensitivity-radio">
                            <div class="sensitivity-btn" data-val="{{ $val }}"
                                 style="
                                    padding:0.625rem 0.75rem;
                                    border:2px solid {{ old('sensitivity','faible') === $val ? $opt['color'] : 'var(--color-border)' }};
                                    border-radius:0.5rem;
                                    text-align:center;
                                    font-size:0.8125rem;
                                    transition:all 0.15s;
                                    background:{{ old('sensitivity','faible') === $val ? $opt['bg'] : '#fff' }};
                                 ">
                                <div style="font-weight:600; color:{{ old('sensitivity','faible') === $val ? $opt['color'] : '#64748b' }}; margin-bottom:0.125rem;">
                                    <i class="fas {{ $opt['icon'] }}" style="margin-right:0.25rem;"></i>
                                    {{ $opt['label'] }}
                                </div>
                                <div style="font-size:0.6875rem; color:#94a3b8;">{{ $opt['desc'] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('sensitivity')
                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Lien WhatsApp --}}
                <div>
                    <label class="form-label">
                        Lien WhatsApp CTA
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal;">(optionnel — bouton d'appel à l'action)</span>
                    </label>
                    <div style="display:flex; align-items:center; border:1px solid var(--color-border); border-radius:0.5rem; overflow:hidden;">
                        <span style="padding:0.5rem 0.75rem; background:#f8fafc; color:#25d366; border-right:1px solid var(--color-border); flex-shrink:0;">
                            <i class="fab fa-whatsapp" style="font-size:1rem;"></i>
                        </span>
                        <input type="url" name="whatsapp_url"
                               class="form-control {{ $errors->has('whatsapp_url') ? 'is-invalid' : '' }}"
                               style="border:none; border-radius:0;"
                               placeholder="https://wa.me/225XXXXXXXXXX?text=Bonjour..."
                               value="{{ old('whatsapp_url') }}">
                    </div>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">
                        Format : https://wa.me/[indicatif pays][numéro]?text=[message pré-rempli]
                    </div>
                    @error('whatsapp_url')
                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex; gap:0.75rem; justify-content:flex-end; flex-wrap:wrap;">
            <a href="{{ route('posts.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Annuler
            </a>
            <button type="submit" name="action" value="draft" class="btn-secondary"
                    style="border-color:var(--color-primary); color:var(--color-primary);">
                <i class="far fa-save"></i>
                Sauvegarder brouillon
            </button>
            <button type="submit" name="action" value="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i>
                Soumettre pour validation
            </button>
        </div>

    </form>
</div>

@push('scripts')
<script>
// Calcul durée
function updateDuration() {
    const start = document.querySelector('[name="start_date"]').value;
    const end   = document.querySelector('[name="end_date"]').value;
    const el    = document.getElementById('durationDisplay');
    if (start && end) {
        const diff = Math.ceil((new Date(end) - new Date(start)) / 86400000);
        if (diff > 0) {
            el.textContent = diff + ' jour' + (diff > 1 ? 's' : '');
            el.style.color = 'var(--color-primary)';
        } else {
            el.textContent = 'Dates invalides';
            el.style.color = '#dc2626';
        }
    }
}
document.querySelector('[name="start_date"]').addEventListener('change', updateDuration);
document.querySelector('[name="end_date"]').addEventListener('change', updateDuration);

// Compteur pays
function updateCountryCount() {
    const count = document.querySelectorAll('.country-check:checked').length;
    document.getElementById('countryCount').textContent = count;
}
document.querySelectorAll('.country-check').forEach(cb => cb.addEventListener('change', updateCountryCount));
updateCountryCount();

// Genre radio styling
document.querySelectorAll('.gender-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.gender-btn').forEach(btn => {
            btn.style.borderColor = 'var(--color-border)';
            btn.style.color = '#64748b';
            btn.style.background = '#fff';
        });
        const activeBtn = document.querySelector(`.gender-btn[data-val="${radio.value}"]`);
        if (activeBtn) {
            activeBtn.style.borderColor = 'var(--color-primary)';
            activeBtn.style.color = 'var(--color-primary)';
            activeBtn.style.background = 'var(--color-primary-light)';
        }
    });
});

// Sensibilité radio styling
const sensConfigs = {
    faible:  { color: '#16a34a', bg: '#dcfce7' },
    moyenne: { color: '#d97706', bg: '#fef9c3' },
    elevee:  { color: '#dc2626', bg: '#fee2e2' },
};
document.querySelectorAll('.sensitivity-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.sensitivity-btn').forEach(btn => {
            btn.style.borderColor = 'var(--color-border)';
            btn.style.background = '#fff';
            btn.querySelector('div').style.color = '#64748b';
        });
        const cfg = sensConfigs[radio.value] || sensConfigs.faible;
        const activeBtn = document.querySelector(`.sensitivity-btn[data-val="${radio.value}"]`);
        if (activeBtn) {
            activeBtn.style.borderColor = cfg.color;
            activeBtn.style.background = cfg.bg;
            activeBtn.querySelector('div').style.color = cfg.color;
        }
    });
});

// Intérêts toggle
document.querySelectorAll('.interest-check').forEach(cb => {
    cb.addEventListener('change', function() {
        const badge = this.nextElementSibling;
        if (this.checked) {
            badge.style.background = 'var(--color-primary-light)';
            badge.style.color = 'var(--color-primary)';
            badge.style.borderColor = 'var(--color-primary)';
        } else {
            badge.style.background = '';
            badge.style.color = '#64748b';
            badge.style.borderColor = 'var(--color-border)';
        }
    });
});
</script>
@endpush
@endsection
