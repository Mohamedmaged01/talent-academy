@props(['value'])

@php
    $tones = [
        'emerald' => ['active', 'paid', 'present', 'valid', 'accepted', 'verified', 'completed', 'read', 'published', 'passed'],
        'sky' => ['upcoming', 'contacted', 'partial', 'late', 'new', 'academic', 'graded', 'eligible'],
        'amber' => ['pending', 'unpaid', 'excused', 'draft', 'suspended', 'training', 'submitted'],
        'rose' => ['overdue', 'absent', 'rejected', 'revoked', 'cancelled', 'withdrawn', 'dropped', 'inactive', 'failed', 'missing'],
        'slate' => ['archived', 'graduated'],
    ];
    $tone = collect($tones)->search(fn ($values) => in_array($value, $values, true)) ?: 'slate';
    $classes = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/15',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/15',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/15',
    ][$tone];
@endphp

<span {{ $attributes->merge(['class' => "badge ring-1 ring-inset {$classes}"]) }}>{{ __(\Illuminate\Support\Str::headline($value)) }}</span>
