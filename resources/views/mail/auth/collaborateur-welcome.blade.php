<x-mail::message>
# Bonjour {{ $userName }},

Votre compte **{{ $roleLabel }}** sur {{ config('app.name') }} a été créé.

@if ($verificationUrl)
Avant votre première connexion, veuillez **vérifier votre adresse e-mail** :

<x-mail::button :url="$verificationUrl">
Vérifier mon e-mail
</x-mail::button>
@endif

<x-mail::button :url="$loginUrl">
Se connecter
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
