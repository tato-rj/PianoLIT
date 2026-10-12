<tr class="table-light"><th colspan="{{ $columns }}" scope="rowgroup">{{ $day === 'unknown' ? 'Unknown date' : carbon($day)->format('l, F j, Y') }}</th></tr>
