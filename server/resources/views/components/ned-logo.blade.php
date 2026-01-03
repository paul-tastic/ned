@props(['class' => 'w-10 h-10'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <!-- Background circle -->
    <circle cx="20" cy="20" r="18" fill="currentColor" class="text-emerald-600"/>
    <!-- Letter N -->
    <path d="M12 28V12H16L24 22V12H28V28H24L16 18V28H12Z" fill="white"/>
</svg>
