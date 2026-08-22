<x-mail::message>
# Paiement non abouti

Bonjour {{ $clientName }},

Le paiement **{{ $paiementCode }}** pour la commande **{{ $commandeCode }}** n'a pas abouti.

@if ($messageBamboo)
Motif : {{ $messageBamboo }}
@endif

Vous pouvez réessayer depuis votre espace client ou contacter l'agence **{{ $agenceName }}**.

<x-mail::button :url="$retourUrl">
Réessayer / voir le détail
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
