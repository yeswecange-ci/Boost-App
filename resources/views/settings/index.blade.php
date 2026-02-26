@extends('layouts.app')

@section('page-title', 'Paramètres')
@section('page-subtitle', 'Configuration N8N et Meta API')

@section('content')

@php
$isMockN8n  = ($n8n['n8n.mock_mode']  ?? 'true') === 'true';
$isMockMeta = ($meta['meta.mock_mode'] ?? 'true') === 'true';
@endphp

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start;">

{{-- ══════════════════════════════════════════════════════
     COLONNE GAUCHE : N8N
══════════════════════════════════════════════════════ --}}
<div x-data="{
    testing: false,
    result: null,
    async testN8n() {
        this.testing = true;
        this.result = null;
        try {
            const r = await fetch('{{ route('settings.test-n8n') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            });
            this.result = await r.json();
        } catch(e) {
            this.result = { success: false, message: 'Erreur réseau : ' + e.message };
        }
        this.testing = false;
    }
}">

    {{-- Header section --}}
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem;">
        <div style="width:2.75rem; height:2.75rem; background:linear-gradient(135deg,#4f46e5,#7c3aed); border-radius:0.75rem; display:flex; align-items:center; justify-content:center; color:white; font-size:1.125rem; flex-shrink:0;">
            <i class="fas fa-webhook" style="font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1.0625rem; font-weight:700; color:#0f172a;">N8N Webhooks</div>
            <div style="font-size:0.8125rem; color:#64748b;">Connexion au moteur d'automatisation</div>
        </div>
        {{-- Mode badge --}}
        <div style="margin-left:auto;">
            <span style="padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.75rem; font-weight:700;
                {{ $isMockN8n ? 'background:#fef9c3; color:#854d0e;' : 'background:#dcfce7; color:#15803d;' }}">
                <i class="fas {{ $isMockN8n ? 'fa-flask' : 'fa-circle' }}" style="margin-right:0.25rem;"></i>
                {{ $isMockN8n ? 'MODE MOCK' : 'PRODUCTION' }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update-n8n') }}">
        @csrf

        {{-- Toggle Mock --}}
        <div class="card" style="margin-bottom:1rem; overflow:hidden;">
            <div class="card-body" style="padding:1rem 1.25rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                    <div>
                        <div style="font-weight:600; font-size:0.9375rem; color:#0f172a;">Mode Mock</div>
                        <div style="font-size:0.8125rem; color:#64748b; margin-top:0.125rem;">
                            Simule N8N localement sans appel HTTP réel.
                            Désactivez en production.
                        </div>
                    </div>
                    <label style="position:relative; display:inline-block; width:52px; height:28px; flex-shrink:0; cursor:pointer;">
                        <input type="checkbox"
                               name="n8n.mock_mode"
                               value="1"
                               {{ $isMockN8n ? 'checked' : '' }}
                               style="opacity:0; width:0; height:0; position:absolute;"
                               onchange="this.closest('label').querySelector('.toggle-track').style.background = this.checked ? '#4f46e5' : '#cbd5e1'; this.closest('label').querySelector('.toggle-thumb').style.transform = this.checked ? 'translateX(24px)' : 'translateX(2px)'">
                        <span class="toggle-track" style="position:absolute; inset:0; border-radius:9999px; transition:background 0.2s; background:{{ $isMockN8n ? '#4f46e5' : '#cbd5e1' }};"></span>
                        <span class="toggle-thumb" style="position:absolute; top:2px; width:24px; height:24px; background:white; border-radius:50%; transition:transform 0.2s; box-shadow:0 1px 3px rgba(0,0,0,.2); transform:{{ $isMockN8n ? 'translateX(24px)' : 'translateX(2px)' }};"></span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Webhook URLs --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header">
                <i class="fas fa-link" style="color:var(--color-primary);"></i>
                URLs des webhooks
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:1rem;">

                <div>
                    <label class="form-label">
                        Créer une campagne
                        <span style="margin-left:0.375rem; padding:0.1rem 0.5rem; background:#dbeafe; color:#1d4ed8; border-radius:9999px; font-size:0.6875rem; font-weight:600;">POST</span>
                    </label>
                    <input type="url"
                           name="n8n.webhook_create"
                           class="form-control"
                           value="{{ $n8n['n8n.webhook_create'] ?? '' }}"
                           placeholder="https://n8n.votredomaine.com/webhook/boost-create">
                </div>

                <div>
                    <label class="form-label">
                        Activer une campagne
                        <span style="margin-left:0.375rem; padding:0.1rem 0.5rem; background:#dbeafe; color:#1d4ed8; border-radius:9999px; font-size:0.6875rem; font-weight:600;">POST</span>
                    </label>
                    <input type="url"
                           name="n8n.webhook_activate"
                           class="form-control"
                           value="{{ $n8n['n8n.webhook_activate'] ?? '' }}"
                           placeholder="https://n8n.votredomaine.com/webhook/boost-activate">
                </div>

                <div>
                    <label class="form-label">
                        Mettre en pause
                        <span style="margin-left:0.375rem; padding:0.1rem 0.5rem; background:#dbeafe; color:#1d4ed8; border-radius:9999px; font-size:0.6875rem; font-weight:600;">POST</span>
                    </label>
                    <input type="url"
                           name="n8n.webhook_pause"
                           class="form-control"
                           value="{{ $n8n['n8n.webhook_pause'] ?? '' }}"
                           placeholder="https://n8n.votredomaine.com/webhook/boost-pause">
                </div>

            </div>
        </div>

        {{-- Secret & Timeout --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-shield-halved" style="color:var(--color-primary);"></i>
                Sécurité & Réseau
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:1rem;">

                <div>
                    <label class="form-label">
                        Secret partagé
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal; margin-left:0.25rem;">(header X-N8N-Secret)</span>
                    </label>
                    <div style="position:relative;" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'"
                               name="n8n.secret"
                               class="form-control"
                               style="padding-right:2.75rem;"
                               value="{{ $n8n['n8n.secret'] ?? '' }}"
                               placeholder="Token secret partagé avec N8N">
                        <button type="button"
                                @click="show = !show"
                                style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">
                        N8N doit envoyer ce secret dans ses callbacks vers
                        <code style="background:#f1f5f9; padding:0.1rem 0.375rem; border-radius:0.25rem;">{{ url('webhook/n8n/boost-created') }}</code>
                    </div>
                </div>

                <div>
                    <label class="form-label">Timeout HTTP (secondes)</label>
                    <input type="number"
                           name="n8n.timeout"
                           class="form-control"
                           style="max-width:120px;"
                           value="{{ $n8n['n8n.timeout'] ?? '10' }}"
                           min="3" max="60">
                </div>

            </div>
        </div>

        <div style="display:flex; gap:0.75rem; align-items:center;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>
                Sauvegarder N8N
            </button>

            {{-- Test live --}}
            <button type="button"
                    @click="testN8n()"
                    :disabled="testing"
                    class="btn-secondary"
                    style="gap:0.5rem;">
                <i class="fas fa-plug" :class="{ 'fa-spin': testing }"></i>
                <span x-text="testing ? 'Test en cours…' : 'Tester la connexion'"></span>
            </button>
        </div>

        {{-- Résultat du test --}}
        <div x-show="result !== null" x-cloak style="margin-top:1rem;">
            <div :class="result?.success ? 'alert alert-success' : 'alert alert-danger'">
                <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                    <i :class="result?.success ? 'fas fa-check-circle' : 'fas fa-times-circle'" style="margin-top:0.1rem; flex-shrink:0;"></i>
                    <div>
                        <div style="font-weight:600; margin-bottom:0.25rem;" x-text="result?.message"></div>
                        <template x-if="result?.response">
                            <pre style="margin:0; font-size:0.75rem; overflow:auto; max-height:120px; background:rgba(0,0,0,.05); padding:0.5rem; border-radius:0.375rem; white-space:pre-wrap;" x-text="JSON.stringify(result.response, null, 2)"></pre>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- ══════════════════════════════════════════════════════
     COLONNE DROITE : META API
══════════════════════════════════════════════════════ --}}
<div x-data="{
    testing: false,
    result: null,
    async testMeta() {
        this.testing = true;
        this.result = null;
        try {
            const r = await fetch('{{ route('settings.test-meta') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            });
            this.result = await r.json();
        } catch(e) {
            this.result = { success: false, message: 'Erreur réseau : ' + e.message };
        }
        this.testing = false;
    }
}">

    {{-- Header section --}}
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem;">
        <div style="width:2.75rem; height:2.75rem; background:linear-gradient(135deg,#1877f2,#0f52aa); border-radius:0.75rem; display:flex; align-items:center; justify-content:center; color:white; font-size:1.125rem; flex-shrink:0;">
            <i class="fab fa-facebook"></i>
        </div>
        <div>
            <div style="font-size:1.0625rem; font-weight:700; color:#0f172a;">Meta API</div>
            <div style="font-size:0.8125rem; color:#64748b;">Facebook / Instagram Graph API</div>
        </div>
        <div style="margin-left:auto;">
            <span style="padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.75rem; font-weight:700;
                {{ $isMockMeta ? 'background:#fef9c3; color:#854d0e;' : 'background:#dcfce7; color:#15803d;' }}">
                <i class="fas {{ $isMockMeta ? 'fa-flask' : 'fa-circle' }}" style="margin-right:0.25rem;"></i>
                {{ $isMockMeta ? 'MODE MOCK' : 'PRODUCTION' }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update-meta') }}">
        @csrf

        {{-- Toggle Mock --}}
        <div class="card" style="margin-bottom:1rem; overflow:hidden;">
            <div class="card-body" style="padding:1rem 1.25rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                    <div>
                        <div style="font-weight:600; font-size:0.9375rem; color:#0f172a;">Mode Mock</div>
                        <div style="font-size:0.8125rem; color:#64748b; margin-top:0.125rem;">
                            Retourne de faux posts Facebook. Désactivez pour utiliser la vraie API.
                        </div>
                    </div>
                    <label style="position:relative; display:inline-block; width:52px; height:28px; flex-shrink:0; cursor:pointer;">
                        <input type="checkbox"
                               name="meta.mock_mode"
                               value="1"
                               {{ $isMockMeta ? 'checked' : '' }}
                               style="opacity:0; width:0; height:0; position:absolute;"
                               onchange="this.closest('label').querySelector('.toggle-track').style.background = this.checked ? '#4f46e5' : '#cbd5e1'; this.closest('label').querySelector('.toggle-thumb').style.transform = this.checked ? 'translateX(24px)' : 'translateX(2px)'">
                        <span class="toggle-track" style="position:absolute; inset:0; border-radius:9999px; transition:background 0.2s; background:{{ $isMockMeta ? '#4f46e5' : '#cbd5e1' }};"></span>
                        <span class="toggle-thumb" style="position:absolute; top:2px; width:24px; height:24px; background:white; border-radius:50%; transition:transform 0.2s; box-shadow:0 1px 3px rgba(0,0,0,.2); transform:{{ $isMockMeta ? 'translateX(24px)' : 'translateX(2px)' }};"></span>
                    </label>
                </div>
            </div>
        </div>

        {{-- App credentials --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header">
                <i class="fas fa-key" style="color:#1877f2;"></i>
                Application Meta
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:1rem;">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div>
                        <label class="form-label">App ID</label>
                        <input type="text"
                               name="meta.app_id"
                               class="form-control"
                               value="{{ $meta['meta.app_id'] ?? '' }}"
                               placeholder="Ex: 123456789">
                    </div>
                    <div>
                        <label class="form-label">API Version</label>
                        <input type="text"
                               name="meta.api_version"
                               class="form-control"
                               value="{{ $meta['meta.api_version'] ?? 'v21.0' }}"
                               placeholder="v21.0">
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <label class="form-label">App Secret</label>
                    <div style="position:relative;">
                        <input :type="show ? 'text' : 'password'"
                               name="meta.app_secret"
                               class="form-control"
                               style="padding-right:2.75rem;"
                               value="{{ $meta['meta.app_secret'] ?? '' }}"
                               placeholder="App Secret (depuis Meta for Developers)">
                        <button type="button"
                                @click="show = !show"
                                style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Access Token --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-id-badge" style="color:#1877f2;"></i>
                Access Token
            </div>
            <div class="card-body">
                <div x-data="{ show: false }">
                    <label class="form-label">
                        Page Access Token
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:normal; margin-left:0.25rem;">(token longue durée recommandé)</span>
                    </label>
                    <div style="position:relative;">
                        <textarea :type="show ? 'text' : 'password'"
                                  name="meta.access_token"
                                  class="form-control"
                                  rows="3"
                                  style="padding-right:2.75rem; resize:vertical; font-family:monospace; font-size:0.8125rem;"
                                  placeholder="EAAxxxxxxxxxxxxxxxxxxxxxxxx...">{{ $meta['meta.access_token'] ?? '' }}</textarea>
                        <button type="button"
                                @click="show = !show"
                                style="position:absolute; right:0.75rem; top:0.625rem; background:none; border:none; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.375rem;">
                        <i class="fas fa-info-circle" style="margin-right:0.25rem;"></i>
                        Générez un token longue durée depuis
                        <a href="https://developers.facebook.com/tools/explorer/" target="_blank" style="color:var(--color-primary);">Graph API Explorer</a>
                        ou utilisez l'API de renouvellement.
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:0.75rem; align-items:center;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i>
                Sauvegarder Meta
            </button>

            <button type="button"
                    @click="testMeta()"
                    :disabled="testing"
                    class="btn-secondary">
                <i class="fab fa-facebook" :class="{ 'fa-spin': testing }" style="color:#1877f2;"></i>
                <span x-text="testing ? 'Test en cours…' : 'Tester le token'"></span>
            </button>
        </div>

        {{-- Résultat du test --}}
        <div x-show="result !== null" x-cloak style="margin-top:1rem;">
            <div :class="result?.success ? 'alert alert-success' : 'alert alert-danger'">
                <div style="display:flex; align-items:flex-start; gap:0.5rem;">
                    <i :class="result?.success ? 'fab fa-facebook' : 'fas fa-times-circle'" style="margin-top:0.1rem; flex-shrink:0;"></i>
                    <div>
                        <div style="font-weight:600; margin-bottom:0.25rem;" x-text="result?.message"></div>
                        <template x-if="result?.data">
                            <pre style="margin:0; font-size:0.75rem; overflow:auto; max-height:120px; background:rgba(0,0,0,.05); padding:0.5rem; border-radius:0.375rem; white-space:pre-wrap;" x-text="JSON.stringify(result.data, null, 2)"></pre>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SECTION BAS : Infos callbacks + aide
═══════════════════════════════════════════════════════ --}}
<div class="card" style="margin-top:1.5rem;">
    <div class="card-header">
        <i class="fas fa-circle-info" style="color:var(--color-primary);"></i>
        Endpoints de callback (à configurer dans N8N)
    </div>
    <div class="card-body">
        <p style="font-size:0.875rem; color:#64748b; margin:0 0 1rem;">
            N8N doit appeler ces URLs après chaque action. Il doit inclure le header
            <code style="background:#f1f5f9; padding:0.1rem 0.375rem; border-radius:0.25rem; font-size:0.8125rem;">X-N8N-Secret: {votre_secret}</code>.
        </p>
        <div style="display:flex; flex-direction:column; gap:0.75rem;">

            @foreach([
                ['label' => 'Campagne créée (statut → created)', 'method' => 'POST', 'url' => route('webhook.n8n.boost-created'),
                 'body'  => '{ "boost_id": 1, "meta_campaign_id": "...", "meta_adset_id": "...", "meta_ad_id": "..." }'],
                ['label' => 'Campagne activée/pausée (statut → active|paused)', 'method' => 'POST', 'url' => route('webhook.n8n.boost-activated'),
                 'body'  => '{ "boost_id": 1, "status": "active" }'],
            ] as $ep)
            <div style="background:#f8fafc; border:1px solid var(--color-border); border-radius:0.625rem; padding:0.875rem 1rem;">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                    <span style="padding:0.2rem 0.5rem; background:#dbeafe; color:#1d4ed8; border-radius:0.25rem; font-size:0.6875rem; font-weight:700;">{{ $ep['method'] }}</span>
                    <span style="font-size:0.875rem; font-weight:600; color:#0f172a;">{{ $ep['label'] }}</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; font-family:monospace; font-size:0.8125rem; color:#4f46e5; background:#eef2ff; padding:0.5rem 0.75rem; border-radius:0.375rem; margin-bottom:0.5rem;">
                    <i class="fas fa-link" style="font-size:0.6875rem; flex-shrink:0;"></i>
                    {{ $ep['url'] }}
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $ep['url'] }}'); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i>',1500)"
                            style="margin-left:auto; background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; flex-shrink:0;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div style="font-size:0.8125rem; color:#64748b;">
                    <strong>Body JSON :</strong>
                    <code style="font-size:0.8125rem; color:#374151;">{{ $ep['body'] }}</code>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</div>

@endsection
