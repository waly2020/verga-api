<x-mail::message>
# Bonjour {{ $userName }},

L'agence **{{ $agenceName }}** est inscrite sur {{ config('app.name') }}.

Vous pouvez gérer vos offres, commandes, colis et publicités depuis votre espace agence.

<x-mail::button :url="$appUrl">
Ouvrir l'espace agence
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
