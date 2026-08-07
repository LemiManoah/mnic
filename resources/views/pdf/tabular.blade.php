@extends('pdf.layout')

{{--
    One view for every tabular export, driven by the same headings and rows the
    CSV is built from. Keeping them on one data source is the point: two
    builders would eventually disagree, and a member comparing a PDF against a
    spreadsheet would have no way to tell which was right.
--}}

@section('title', $title)
@section('doc-type', $subtitle)

@section('content')
    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $index => $heading)
                    <th @class(['num' => in_array($index, $numericColumns, true)])>
                        {{ $heading }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach (array_values($row) as $index => $cell)
                        <td @class(['num' => in_array($index, $numericColumns, true)])>
                            {{ is_int($cell) ? number_format($cell) : ($cell ?? '—') }}
                        </td>
                    @endforeach
                </tr>
            @endforeach

            @if ($rows === [])
                <tr>
                    <td colspan="{{ count($headings) }}">Nothing to report.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <p style="margin-top: 10pt; font-size: 8.5pt; color: #555;">
        {{ count($rows) }} {{ Str::plural('row', count($rows)) }}.
    </p>
@endsection

@section('note')
    Amounts are in Ugandan shillings. This document was generated from the
    club's records at the time shown in the footer.
@endsection
