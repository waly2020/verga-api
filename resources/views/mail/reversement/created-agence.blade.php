<x-mail::message>
# Reversement en attente

Bonjour {{ $gerantName }},

Un reversement de **{{ $montant }}** pour la période **{{ $periode }}** a été enregistré pour **{{ $agenceName }}**.

Il est actuellement **en attente de validation** par l'administration {{ config('app.name') }}.

Vous serez notifié dès qu'il sera effectué.

<x-mail::button :url="$reversementsUrl">
Voir mes reversements
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
