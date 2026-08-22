<x-mail::message>
# Reversement effectué

Bonjour {{ $gerantName }},

Le reversement de **{{ $montant }}** pour la période **{{ $periode }}** (agence **{{ $agenceName }}**) a été **effectué**.

@if ($effectueLe)
Date : {{ $effectueLe }}
@endif

Le montant devrait apparaître sur votre compte selon les délais de votre établissement bancaire.

<x-mail::button :url="$reversementsUrl">
Voir mes reversements
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
