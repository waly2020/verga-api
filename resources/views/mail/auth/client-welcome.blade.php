<x-mail::message>
# Bonjour {{ $userName }},

Votre compte client {{ config('app.name') }} est prêt.

Vous pouvez dès maintenant consulter les offres, passer commande et suivre vos colis.

<x-mail::button :url="$appUrl">
Accéder à l'application
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
