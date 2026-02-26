@extends('layouts.app')

@section('page-title', 'Posts Facebook')
@section('page-subtitle', 'Sélectionnez un post à booster')

@section('content')

{{-- Header bar --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">

    {{-- Page selector --}}
    @if($pages->count() > 1)
    <form method="GET" action="{{ route('posts.index') }}" style="display:flex; align-items:center; gap:0.5rem;">
        <label style="font-size:0.875rem; color:#64748b; white-space:nowrap;">Page :</label>
        <select name="page_id"
                onchange="this.form.submit()"
                style="padding:0.375rem 2rem 0.375rem 0.75rem; border:1px solid var(--color-border); border-radius:0.5rem; font-size:0.875rem; background:#fff; color:#374151; cursor:pointer; outline:none; appearance:auto;">
            @foreach($pages as $page)
            <option value="{{ $page->page_id }}"
                {{ $selectedPage?->page_id == $page->page_id ? 'selected' : '' }}>
                {{ $page->page_name }}
            </option>
            @endforeach
        </select>
    </form>
    @else
    <div style="font-size:0.875rem; color:#64748b;">
        <i class="fab fa-facebook" style="color:#1877f2;"></i>
        {{ $selectedPage?->page_name ?? '—' }}
    </div>
    @endif

    <div style="font-size:0.875rem; color:#94a3b8;">
        {{ count($posts['data'] ?? []) }} post(s) trouvé(s)
    </div>
</div>

{{-- Alerts --}}
@if(!empty($posts['error']))
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle" style="margin-right:0.5rem;"></i>
    <strong>Erreur Meta API :</strong> {{ $posts['error'] }}
</div>
@endif

@if($pages->isEmpty())
<div class="alert alert-warning">
    <i class="fas fa-exclamation-circle" style="margin-right:0.5rem;"></i>
    Aucune page Facebook configurée. Contactez un administrateur.
</div>
@endif

{{-- Posts grid --}}
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1.25rem;">

    @forelse($posts['data'] as $post)
    <div class="card" style="display:flex; flex-direction:column; overflow:hidden; transition:box-shadow 0.2s;"
         onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
         onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.06)'">

        {{-- Thumbnail --}}
        <div style="position:relative; overflow:hidden;">
            @if($post['thumbnail'])
            <img src="{{ $post['thumbnail'] }}"
                 alt="Post thumbnail"
                 style="width:100%; height:200px; object-fit:cover; display:block;">
            @else
            <div style="height:200px; background:linear-gradient(135deg,#eef2ff,#f3e8ff); display:flex; align-items:center; justify-content:center;">
                <i class="fab fa-facebook" style="font-size:3rem; color:#a5b4fc;"></i>
            </div>
            @endif

            {{-- Type badge --}}
            <span style="
                position:absolute; top:0.75rem; right:0.75rem;
                padding:0.25rem 0.625rem;
                border-radius:9999px;
                font-size:0.6875rem; font-weight:700;
                text-transform:uppercase; letter-spacing:0.05em;
                {{ match($post['type']) {
                    'video'  => 'background:#fee2e2; color:#b91c1c;',
                    'photo'  => 'background:#dbeafe; color:#1d4ed8;',
                    'link'   => 'background:#fef9c3; color:#854d0e;',
                    default  => 'background:#f1f5f9; color:#64748b;',
                } }}
            ">
                @if($post['type'] === 'video') <i class="fas fa-video" style="margin-right:0.25rem;"></i>
                @elseif($post['type'] === 'photo') <i class="fas fa-image" style="margin-right:0.25rem;"></i>
                @else <i class="fas fa-link" style="margin-right:0.25rem;"></i>
                @endif
                {{ ucfirst($post['type']) }}
            </span>
        </div>

        {{-- Body --}}
        <div style="padding:1rem; flex:1; display:flex; flex-direction:column;">
            <p style="
                font-size:0.875rem;
                color:#374151;
                line-height:1.5;
                flex:1;
                margin:0 0 0.875rem;
                display:-webkit-box;
                -webkit-line-clamp:3;
                -webkit-box-orient:vertical;
                overflow:hidden;
            ">
                {{ $post['message'] ?: '(Aucun texte)' }}
            </p>

            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:0.75rem; border-top:1px solid #f1f5f9;">
                <span style="font-size:0.75rem; color:#94a3b8;">
                    <i class="far fa-clock" style="margin-right:0.25rem;"></i>
                    {{ \Carbon\Carbon::parse($post['created_time'])->diffForHumans() }}
                </span>
                <span style="font-size:0.75rem; color:#94a3b8;">
                    <i class="far fa-eye" style="margin-right:0.25rem;"></i>
                    {{ number_format($post['impressions']) }}
                </span>
            </div>
        </div>

        {{-- Footer / Actions --}}
        <div style="padding:0.75rem 1rem; border-top:1px solid #f1f5f9; display:flex; flex-direction:column; gap:0.5rem;">
            <a href="{{ route('boost.create', ['post_id' => $post['id'], 'page_id' => $selectedPage->page_id]) }}"
               class="btn-primary"
               style="width:100%; justify-content:center;">
                <i class="fas fa-rocket"></i>
                Booster ce post
            </a>
            <a href="{{ $post['permalink_url'] }}"
               target="_blank"
               class="btn-secondary"
               style="width:100%; justify-content:center;">
                <i class="fab fa-facebook" style="color:#1877f2;"></i>
                Voir sur Facebook
            </a>
        </div>

    </div>
    @empty
    <div style="grid-column:1/-1; text-align:center; padding:4rem 1rem; color:#94a3b8;">
        <i class="fab fa-facebook" style="font-size:3.5rem; display:block; margin-bottom:1rem; color:#e2e8f0;"></i>
        <div style="font-size:1rem; font-weight:500; margin-bottom:0.25rem;">Aucun post trouvé</div>
        <div style="font-size:0.875rem;">Cette page ne contient pas encore de publications.</div>
    </div>
    @endforelse

</div>

@endsection
