<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] ?? 'Document' }}</title>
    <style>
        @page {
            margin: 14px 16px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 10px;
            line-height: 1.35;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .banner {
            border: 1.5px solid #000;
            background: #dcdcdc;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            padding: 6px 0;
            margin-bottom: 8px;
        }

        .party-box {
            border: 1.5px solid #000;
            border-radius: 12px;
            height: 78px;
            padding: 8px 10px;
            text-align: center;
        }

        .party-box .name {
            font-weight: 700;
            font-size: 12px;
        }

        .party-box .phone {
            font-weight: 700;
            font-size: 12px;
            margin-top: 32px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4px;
        }

        .meta td {
            padding: 1px 3px;
            font-weight: 700;
            font-size: 10px;
        }

        .meta .label {
            text-align: right;
            width: 38%;
        }

        .meta .value {
            text-align: left;
        }

        .items {
            margin-top: 9px;
        }

        .items th {
            background: #d9d9d9;
            border: 1.2px solid #000;
            padding: 5px 3px;
            font-size: 10px;
            text-align: center;
        }

        .items td {
            border: 1px solid #000;
            padding: 6px 3px;
            text-align: center;
            font-size: 10px;
        }

        .items td.name {
            text-align: right;
        }

        .totals {
            margin-top: 6px;
        }

        .stat {
            text-align: right;
            font-weight: 700;
            padding: 1px 3px;
        }

        .totals td {
            vertical-align: middle;
            font-weight: 700;
            padding: 1px 3px;
        }

        .total-box td {
            border: 1.5px solid #000;
            text-align: center;
            font-size: 13px;
            padding: 6px 4px;
        }

        .total-box td.label {
            background: #e4e4e4;
        }

        .pay th {
            background: #d9d9d9;
            border: 1.2px solid #000;
            padding: 3px;
            text-align: center;
        }

        .pay td {
            border: 1.2px solid #000;
            padding: 4px;
            text-align: center;
            font-weight: 700;
        }

        .note {
            margin-top: 8px;
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="banner">{{ $document['banner'] ?? 'POS' }}</div>

    <table>
        <tr>
            <td style="width: 44%; vertical-align: top;">
                <div class="party-box">
                    <div class="name">{{ $document['party_name'] ?: '-' }}</div>
                    <div class="phone">{{ $document['party_phone'] ?? '' }}</div>
                </div>
            </td>
            <td style="width: 56%; vertical-align: top; padding-left: 8px;">
                @if (! empty($document['title']))
                    <div class="doc-title">{{ $document['title'] }}</div>
                @endif
                <table class="meta">
                    @foreach ($document['meta'] ?? [] as $row)
                        <tr>
                            <td class="value">{{ $row['value'] }}</td>
                            <td class="label">{{ $row['label'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                @foreach ($document['columns'] as $column)
                    <th style="width: {{ $column['width'] ?? 'auto' }};">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($document['rows'] as $row)
                <tr>
                    @foreach ($document['columns'] as $column)
                        <td class="{{ $column['key'] === 'article' ? 'name' : '' }}">{{ $row[$column['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td style="width: {{ (! empty($document['total']) || ! empty($document['payment'])) ? '46%' : '100%' }}; vertical-align: top; text-align: right;">
                @foreach ($document['stats'] ?? [] as $stat)
                    <div class="stat">{{ $stat['value'] }} &nbsp;&nbsp; {{ $stat['label'] }}</div>
                @endforeach
            </td>
            @if (! empty($document['total']) || ! empty($document['payment']))
                <td style="width: 54%; vertical-align: top;">
                    @if (! empty($document['total']))
                        <table class="total-box">
                            <tr>
                                <td>{{ $document['total']['value'] }}</td>
                                <td class="label" style="width: 40%;">{{ $document['total']['label'] }}</td>
                            </tr>
                        </table>
                    @endif
                    @if (! empty($document['payment']))
                        <table class="pay" style="{{ ! empty($document['total']) ? 'margin-top: 8px;' : '' }}">
                            <tr>
                                <th>{{ $document['payment']['amount_label'] }}</th>
                                <th>{{ $document['payment']['mode_label'] }}</th>
                            </tr>
                            <tr>
                                <td>{{ $document['payment']['amount'] }}</td>
                                <td>{{ $document['payment']['mode'] }}</td>
                            </tr>
                        </table>
                    @endif
                </td>
            @endif
        </tr>
    </table>
    @if (! empty($document['note']))
        <div class="note"><strong>Note :</strong> {{ $document['note'] }}</div>
    @endif
</body>

</html>
