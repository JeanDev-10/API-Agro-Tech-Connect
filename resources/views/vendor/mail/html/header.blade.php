@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ asset('assets/logo_agrotechconnectsinlienzosobrante.png') }}" class="logo" alt="Agro Tech Connect Logo">
@endif
</a>
</td>
</tr>
