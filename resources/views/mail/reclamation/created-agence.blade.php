<x-mail::message>
# Réclamation ouverte

Bonjour,

Une réclamation vient d'être ouverte concernant votre agence **{{ $agenceName }}**.

- **Client** : {{ $clientName }}
@if ($commandeCode)
- **Commande** : {{ $commandeCode }}
@endif
- **Objet** : {{ $objet }}

{{ $description }}

<x-mail::button :url="$reclamationUrl">
Voir la réclamation
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
