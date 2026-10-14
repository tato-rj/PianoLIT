<article class="pa-card pa-matrix"><div class="pa-card-heading"><div><h3>Levels across periods</h3><p>Darker cells contain more pieces; zero shows a gap in the catalogue.</p></div><span class="pa-matrix-key" aria-hidden="true"><i></i>0 → {{ number_format($matrix['peak']) }}</span></div>
    <div class="pa-table-scroll" tabindex="0" role="region" aria-label="Pieces by level and period"><table><caption class="visually-hidden">Catalogue pieces by main level and period, including missing and conflicting classifications</caption><thead><tr><th scope="col">Level / period</th>
@foreach($matrix['periods'] as $period)<th scope="col">{{ $period['label'] }}</th>
@endforeach</tr></thead><tbody>

@foreach($matrix['levels'] as $level)<tr><th scope="row">{{ $level['label'] }}</th>
@foreach($matrix['periods'] as $period)
@php($count = $matrix['cells'][$level['key']][$period['key']] ?? 0)<td><span class="pa-matrix-cell {{ $count ? '' : 'is-zero' }}" style="--pa-density:{{ $count ? .12 + .55 * $count / max(1, $matrix['peak']) : 0 }}">{{ number_format($count) }}</span></td>
@endforeach</tr>
@endforeach
    </tbody></table></div>
</article>
