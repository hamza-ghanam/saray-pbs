@props([
    'valueEn' => '',        
    'valueAr' => '',       
])

<tr>
    <td class="en" lang="en" dir="ltr">
        {!! $valueEn !!}
    </td>
    <td class="separator">
        &nbsp;
    </td>
    <td class="ar" lang="ar" dir="rtl">
        {!! $valueAr !!}
    </td>
</tr
