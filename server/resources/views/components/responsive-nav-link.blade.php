@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-emerald-500 text-start text-base font-medium text-white bg-zinc-700 focus:outline-none focus:text-white focus:bg-zinc-600 focus:border-emerald-400 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-700 hover:border-zinc-500 focus:outline-none focus:text-white focus:bg-zinc-700 focus:border-zinc-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
