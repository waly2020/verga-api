<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $paiement['code'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 24px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .muted { color: #555; }
        .header { margin-bottom: 24px; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .grid th, .grid td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        .grid th { background: #f5f5f5; }
        .totals { width: 100%; margin-top: 16px; }
        .totals td { padding: 4px 0; }
        .totals .label { text-align: right; padding-right: 12px; color: #555; width: 70%; }
        .totals .value { text-align: right; font-weight: bold; width: 30%; }
        .total-row .value { font-size: 14px; color: #0f766e; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #f3f4f6; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>VERGA — Facture de paiement</h1>
        <p class="muted">Référence paiement : <strong>{{ $paiement['code'] }}</strong></p>
        @if($commande)
            <p class="muted">Commande : <strong>{{ $commande['code'] }}</strong></p>
        @endif
        @if($paiement['created_at'])
            <p class="muted">Date : {{ \Illuminate\Support\Carbon::parse($paiement['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
        @endif
        <p><span class="badge">{{ ucfirst($paiement['statut']) }}</span></p>
    </div>

    <table style="width:100%; margin-bottom: 16px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <h2>Client</h2>
                <p><strong>{{ $client['nom'] ?? '—' }}</strong></p>
                @if($client['telephone'])<p>{{ $client['telephone'] }}</p>@endif
                @if($client['email'])<p>{{ $client['email'] }}</p>@endif
            </td>
            <td style="width:50%; vertical-align:top;">
                @if($agence)
                    <h2>Agence</h2>
                    <p><strong>{{ $agence['nom'] }}</strong></p>
                    @if($agence['ville'])<p>{{ $agence['ville'] }}</p>@endif
                    @if($agence['telephone'])<p>{{ $agence['telephone'] }}</p>@endif
                @endif
            </td>
        </tr>
    </table>

    @if($offre)
        <h2>Offre</h2>
        <p><strong>{{ $offre['titre'] }}</strong></p>
        <p class="muted">{{ $offre['origine'] }} → {{ $offre['destination'] }}</p>
        <p>Prix unitaire : {{ number_format($offre['prix'], 0, ',', ' ') }} FCFA</p>
    @endif

    <h2>Détail du paiement</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantité</th>
                <th style="text-align:right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Transport{{ $offre ? ' — '.$offre['titre'] : '' }}</td>
                <td>{{ $paiement['quantite_label'] ?? $paiement['quantite'] }}</td>
                <td style="text-align:right;">{{ number_format($paiement['montant_sous_total'], 0, ',', ' ') }} FCFA</td>
            </tr>
            @if($paiement['montant_commission_client'] > 0)
                <tr>
                    <td>Commission VERGA</td>
                    <td>—</td>
                    <td style="text-align:right;">{{ number_format($paiement['montant_commission_client'], 0, ',', ' ') }} FCFA</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr class="total-row">
            <td class="label">Total payé</td>
            <td class="value">{{ number_format($paiement['montant'], 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($commande)
        <h2>Suivi commande</h2>
        <p>Quantité réservée : {{ $commande['quantite_label'] ?? $commande['quantite'] }}</p>
        <p>Quantité payée : {{ $commande['quantite_payee_label'] ?? $commande['quantite_payee'] }}</p>
        @if(($commande['quantite_restante'] ?? 0) > 0)
            <p>Reste à payer : {{ $commande['quantite_restante_label'] }}</p>
        @endif
    @endif

    @if(count($colis) > 0)
        <h2>Colis ({{ count($colis) }})</h2>
        <table class="grid">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Description</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($colis as $item)
                    <tr>
                        <td>{{ $item['reference'] }}</td>
                        <td>{{ $item['description'] ?? '—' }}</td>
                        <td>{{ $item['statut'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($paiement['bamboo_reference'])
        <p class="muted" style="margin-top:24px;">Référence Bamboo Pay : {{ $paiement['bamboo_reference'] }}</p>
    @endif
</body>
</html>
