@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
<img src="https://res.cloudinary.com/dwt77rk62/image/upload/fl_preserve_transparency/v1758562604/Agro%20Tech%20Connect/LOGO/logo_agrotechconnectsinlienzosobrante_iesdax.jpg?_s=public-apps" class="logo" alt="Agro Tech Connect Logo">
{{-- <img src="{{ asset('assets/logo_agrotechconnectsinlienzosobrante.png') }}" class="logo" alt="Agro Tech Connect Logo"> --}}
@endif
</a>
</td>
</tr>
