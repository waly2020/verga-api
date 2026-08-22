<x-mail::message>
# Compte agence {{ $agenceName }}

@if ($isBlocked)
Votre compte agence a été **suspendu** par l'administration {{ config('app.name') }}.

Vous ne pouvez plus accéder à l'espace agence tant que le compte n'est pas réactivé.

Pour toute question, contactez le support {{ config('app.name') }}.
@else
Votre compte agence a été **réactivé**.

Vous pouvez à nouveau vous connecter et utiliser l'espace agence normalement.
@endif

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
