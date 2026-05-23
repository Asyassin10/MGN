<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] ?? 'Document' }}</title>
    <style>
        @page {
            margin: 28px 34px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111111;
            font-size: 12px;
            line-height: 1.45;
            unicode-bidi: embed;
        }

        .header-table,
        .items,
        .summary {
            width: 100%;
            border-collapse: collapse;
        }

        .items {
            direction: ltr;
        }

        .header-table {
            margin-bottom: 18px;
        }

        .header-table td {
            vertical-align: top;
        }

        .meta-cell {
            width: 58%;
            padding-right: 14px;
            text-align: left;
        }

        .brand-cell {
            width: 42%;
            text-align: right;
        }

        .brand-box {
            display: inline-block;
            background: #4b5563;
            padding: 6px;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .logo {
            width: 92px;
            height: 92px;
            display: block;
            border-radius: 8px;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            font-size: 11px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: #444;
        }

        .meta-box {
            border: 1px solid #000;
            padding: 10px 12px;
        }

        .meta-box p {
            margin: 0 0 4px;
        }

        .meta-box p:last-child {
            margin-bottom: 0;
        }

        .separator {
            border-top: 1px solid #000;
            margin: 8px 0 18px;
        }

        .items th,
        .items td,
        .summary th,
        .summary td {
            border: 1px solid #000;
            padding: 9px 10px;
            vertical-align: top;
        }

        .items th,
        .summary th {
            background: #efefef;
            font-weight: 700;
            text-align: left;
        }

        .items td,
        .meta-box p,
        .note {
            unicode-bidi: embed;
        }

        .items td {
            direction: ltr;
        }

        .items td.amount,
        .items th.amount,
        .summary td.amount,
        .summary th.amount {
            text-align: right;
            white-space: nowrap;
        }

        .summary-wrap {
            margin-top: 18px;
            margin-left: auto;
            width: 52%;
        }

        .note {
            margin-top: 18px;
            border: 1px solid #000;
            padding: 12px;
        }

        .note-title {
            font-weight: 700;
            margin-bottom: 6px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td class="meta-cell">
                <div class="meta-box">
                    @foreach ($document['meta'] ?? [] as $label => $value)
                        <p><strong>{{ $label }} :</strong> {{ $value ?: '-' }}</p>
                    @endforeach
                </div>
            </td>
            <td class="brand-cell">
                @if ($logoDataUri)
                    <div class="brand-box">
                        <img src="{{ $logoDataUri }}" class="logo" alt="Droguerie Palmeraie">
                    </div>
                @endif
                <div class="subtitle">{{ $document['subtitle'] ?? 'Document' }}</div>
                <h1 class="title">{{ $document['brand'] ?? 'Droguerie Palmeraie' }}</h1>
            </td>
        </tr>
    </table>

    <div class="separator"></div>

    <table class="items">
        <thead>
            <tr>
                @foreach ($document['columns'] ?? [] as $column)
                    <th class="{{ ($column['align'] ?? null) === 'right' ? 'amount' : '' }}">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($document['rows'] ?? [] as $row)
                <tr>
                    @foreach ($document['columns'] ?? [] as $column)
                        <td class="{{ ($column['align'] ?? null) === 'right' ? 'amount' : '' }}">
                            {{ $row[$column['key']] ?? '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($document['columns'] ?? []) }}">Aucune donnée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($document['summary']))
        <div class="summary-wrap">
            <table class="summary">
                <tbody>
                    @foreach ($document['summary'] as $label => $value)
                        <tr>
                            <th>{{ $label }}</th>
                            <td class="amount">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (! empty($document['note']))
        <div class="note">
            <div class="note-title">Note</div>
            <div>{{ $document['note'] }}</div>
        </div>
    @endif
</body>

</html>
