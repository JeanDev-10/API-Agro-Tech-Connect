@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
<img src="https://res.cloudinary.com/dwt77rk62/image/upload/v1743815951/Agro%20Tech%20Connect/LOGO/logo_agrotechconnectlblack_jytjko.png" class="logo" alt="Agro Tech Connect Logo">
@endif
</a>
</td>
</tr>
