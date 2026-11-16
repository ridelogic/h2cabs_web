@props(['tone' => 'gray'])

@php
    $tones = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'blue' => 'bg-admin-accent text-brand-blue-700 ring-brand-blue-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'gray' => 'bg-slate-50 text-slate-600 ring-slate-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'admin-status '.($tones[$tone] ?? $tones['gray'])]) }}>{{ $slot }}</span>
