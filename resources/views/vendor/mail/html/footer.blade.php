<tr>
    <td>
        @component('mail::layout.container', ['class' => 'footer'])
            <tr>
                <td class="content-cell" align="center">
                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                </td>
            </tr>
        @endcomponent
    </td>
</tr>
