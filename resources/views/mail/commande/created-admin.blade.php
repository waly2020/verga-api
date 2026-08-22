<x-mail::message>
# Nouvel achat client

- **Commande** : {{ $commandeCode }}
- **Client** : {{ $clientName }}
- **Agence** : {{ $agenceName }}
- **Offre** : {{ $offreTitre }}
- **Paiement** : {{ $paiementCode }} — {{ $montantTotal }}

<x-mail::button :url="$commandeUrl">
Ouvrir dans l'admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
