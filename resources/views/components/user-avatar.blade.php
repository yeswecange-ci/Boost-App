@props(['user', 'size' => 36])

@php
    $__presets = [
        'indigo'  => 'linear-gradient(135deg,#4f46e5,#7c3aed)',
        'violet'  => 'linear-gradient(135deg,#7c3aed,#a855f7)',
        'rose'    => 'linear-gradient(135deg,#e11d48,#f43f5e)',
        'amber'   => 'linear-gradient(135deg,#d97706,#f59e0b)',
        'emerald' => 'linear-gradient(135deg,#059669,#10b981)',
        'sky'     => 'linear-gradient(135deg,#0284c7,#38bdf8)',
        'slate'   => 'linear-gradient(135deg,#475569,#64748b)',
    ];
    $__gradient = $__presets['indigo'];
    $__imgSrc   = null;

    if ($user->avatar) {
        if (str_starts_with($user->avatar, 'avatars/')) {
            $__imgSrc = asset('storage/' . $user->avatar);
        } elseif (str_starts_with($user->avatar, 'preset:')) {
            $__gradient = $__presets[str_replace('preset:', '', $user->avatar)] ?? $__gradient;
        }
    }

    $__fontSize = round($size * 0.38) . 'px';
@endphp

<div style="
    width: {{ $size }}px;
    height: {{ $size }}px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: {{ $__imgSrc ? 'transparent' : $__gradient }};
    color: white;
    font-size: {{ $__fontSize }};
    font-weight: 700;
">
    @if($__imgSrc)
        <img src="{{ $__imgSrc }}"
             alt="{{ $user->name }}"
             style="width:100%; height:100%; object-fit:cover;"
             loading="lazy">
    @else
        {{ strtoupper(substr($user->name, 0, 1)) }}
    @endif
</div>
