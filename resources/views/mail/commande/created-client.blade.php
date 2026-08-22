<x-mail::message>
# Commande enregistrée

Bonjour {{ $clientName }},

Votre commande **{{ $commandeCode }}** pour l'offre *{{ $offreTitre }}* a été créée.

Montant à régler : **{{ $montantTotal }}**

Finalisez le paiement pour confirmer votre réservation.

<x-mail::button :url="$retourUrl">
Poursuivre le paiement
</x-mail::button>

<x-mail::button :url="$commandeUrl">
Voir ma commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
