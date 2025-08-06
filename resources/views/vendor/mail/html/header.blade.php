@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://www.aguasdelhuila.gov.co/cms/images/Aguas%20del%20Huila/Logo.png"
     class="w-40 mx-auto" alt="Logo Aguas del Huila">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
