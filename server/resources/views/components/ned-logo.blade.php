@props(['class' => 'w-10 h-10'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
    <!-- Background circle -->
    <circle cx="20" cy="20" r="18" fill="currentColor" class="text-emerald-600"/>
    <!-- Lowercase letter n -->
    <path d="M14 28V17H17.5V19C18.3 17.7 19.6 17 21.5 17C24.5 17 26 19 26 22V28H22.5V23C22.5 21.3 21.8 20.5 20.3 20.5C18.7 20.5 17.5 21.5 17.5 23.5V28H14Z" fill="white"/>
</svg>
