@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset(config('app.tenant.logo_url') ?: 'musuwa_logo.jpeg') }}" alt="{{ config('app.tenant.name') }} logo" width="180" style="display: block; width: 180px; max-width: 100%; height: auto; margin: 0 auto 12px;">
{{ config('app.tenant.name') }}
</a>
</td>
</tr>
