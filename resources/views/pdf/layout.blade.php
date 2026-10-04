{{--
    Shared shell for every generated PDF.

    dompdf understands roughly CSS 2.1: no flexbox, no grid, no CSS variables.
    Everything here is tables, floats and absolute positioning on purpose — a
    layout that looks right in a browser will not necessarily survive dompdf,
    and this file is the place that constraint is paid for once.

    DejaVu Sans is the font dompdf ships with full Unicode coverage for; the
    default (Helvetica) drops characters outside Latin-1.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page { margin: 20mm 18mm 24mm; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            color: #111;
            margin: 0;
        }

        .club { font-size: 14pt; font-weight: bold; margin: 0 0 2pt; }
        .doc-type { font-size: 9pt; color: #555; margin: 0 0 16pt; }

        h2 { font-size: 11pt; margin: 18pt 0 6pt; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 4pt 6pt; vertical-align: top; }

        table.data { margin-top: 4pt; table-layout: fixed; page-break-inside: auto; }
        table.data thead { display: table-header-group; }
        table.data tbody { display: table-row-group; page-break-inside: auto; }
        table.data tr { page-break-inside: avoid; page-break-after: auto; }
        table.data th, table.data td { overflow-wrap: break-word; word-wrap: break-word; white-space: normal; }
        h2 { page-break-after: avoid; }
        table.data th {
            border-bottom: 1px solid #999;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #555;
        }
        table.data td { border-bottom: 1px solid #e5e5e5; }
        table.data td.num, table.data th.num { text-align: right; }

        /* Key/value blocks: a two-column table, because a definition list with
           grid would collapse into one column here. */
        table.meta td { padding: 2pt 0; }
        table.meta td.label { color: #555; width: 38%; }

        .headline { font-size: 20pt; font-weight: bold; margin: 10pt 0 14pt; }
        .total-row td { border-top: 1.5px solid #333; font-weight: bold; }
        .negative { color: #a11; }

        .note {
            margin-top: 20pt;
            padding-top: 8pt;
            border-top: 1px solid #e5e5e5;
            font-size: 8.5pt;
            color: #555;
            line-height: 1.5;
        }

        .footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            font-size: 8pt;
            color: #777;
        }
        .footer .right { float: right; }
        @yield('report-styles')
    </style>
</head>
<body>
    <div class="footer">
        {{ $clubName }}
        <span class="right">Generated {{ now()->toDayDateTimeString() }}</span>
    </div>

    <img src="{{ public_path('musuwa_logo.jpeg') }}" alt="{{ $clubName }} logo" style="width: 145px; height: auto; margin-bottom: 8px;">
    <p class="club">{{ $clubName }}</p>
    <p class="doc-type">@yield('doc-type')</p>

    @yield('content')

    <div class="note">@yield('note')</div>
</body>
</html>
