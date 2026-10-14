<section class="pa-section admin-country-section" aria-labelledby="pa-country-title">
  <div class="pa-section-heading"><h2 id="pa-country-title">Pieces by composer country</h2><p>Saved composer countries · counts pieces, not composers or listeners.</p></div>
  <div class="pa-card admin-country-card" data-country-map data-map-unit="piece" data-map-units="pieces" data-map-share-label="of pieces with a composer country" data-located="{{ $map['located'] }}" data-default-country="{{ $map['top']['code'] ?? '' }}">
    <div class="admin-country-sidebar">
      <div class="admin-country-heading"><h3>Composer countries</h3><span>{{ count($map['countries']) }} {{ count($map['countries']) === 1 ? 'country' : 'countries' }}</span></div>
      <div class="admin-country-summary" aria-live="polite" aria-atomic="true">
        <div><strong data-country-total>{{ number_format($map['top']['total'] ?? 0) }}</strong><span data-country-flag class="{{ $map['top'] ? 'flag-icon flag-icon-'.strtolower($map['top']['code']) : '' }}" aria-hidden="true"></span></div>
        <p data-country-name>{{ $map['top']['name'] ?? 'No composer countries recorded' }}</p>
        <span data-country-share>{{ $map['top'] ? $map['top']['share'].'% of pieces with a composer country' : 'Country counts appear when a composer country is saved.' }}</span>
      </div>
      <ol class="admin-country-ranking">

@foreach(array_slice($map['countries'], 0, 6) as $country)
        <li><button type="button" data-select-country="{{ $country['code'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><i class="admin-country-dot is-color-{{ $country['color'] }}" aria-hidden="true"></i><span>{{ $country['name'] }}</span><strong>{{ number_format($country['total']) }}</strong><small>{{ $country['share'] }}%</small></button></li>

@endforeach
      </ol>
      <p class="admin-country-coverage">{{ number_format($map['located']) }} of {{ number_format($map['total']) }} pieces have a usable composer country.</p>
    </div>
    <div class="admin-country-map-area">
      <label class="admin-country-picker" hidden>Explore a country
        <select data-country-picker>

@foreach($map['countries'] as $country)<option value="{{ $country['code'] }}" data-name="{{ $country['name'] }}" data-count="{{ $country['total'] }}">{{ $country['name'] }} · {{ number_format($country['total']) }}</option>
@endforeach
        </select>
      </label>
      <div class="admin-country-stage">
        <div class="admin-country-controls" hidden>
          <button type="button" data-map-zoom="in" aria-label="Zoom in">
@icon('plus', ['mr' => 0])</button>
          <button type="button" data-map-zoom="out" aria-label="Zoom out" disabled>
@icon('minus', ['mr' => 0])</button>
          <button type="button" data-map-reset aria-label="Reset map zoom" disabled>
@icon('rotate-ccw', ['mr' => 0])</button>
        </div>
        <svg class="admin-country-map" viewBox="0 0 {{ $map['world']['width'] }} {{ $map['world']['height'] }}" role="group" aria-label="Pieces by saved composer country" data-map-width="{{ $map['world']['width'] }}" data-map-height="{{ $map['world']['height'] }}">

@foreach($map['world']['countries'] as $shape)

@php($country = $map['byCode'][$shape['code']] ?? null)
          <path class="admin-country-shape {{ $country ? 'is-color-'.$country['color'] : '' }} {{ ($map['top']['code'] ?? '') === $shape['code'] ? 'is-selected' : '' }}" d="{{ $shape['path'] }}" data-country="{{ $shape['code'] }}" data-name="{{ $shape['name'] }}" data-count="{{ $country['total'] ?? 0 }}" data-center-x="{{ $shape['center'][0] }}" data-center-y="{{ $shape['center'][1] }}" role="img" aria-label="{{ $shape['name'] }}: {{ number_format($country['total'] ?? 0) }} {{ ($country['total'] ?? 0) === 1 ? 'piece' : 'pieces' }}"><title>{{ $shape['name'] }} · {{ number_format($country['total'] ?? 0) }} {{ ($country['total'] ?? 0) === 1 ? 'piece' : 'pieces' }}</title></path>

@endforeach
        </svg>
        <div class="admin-map-tooltip" hidden role="tooltip"><strong></strong><span></span></div>
      </div>
      <div class="admin-country-legend"><span><i class="admin-country-dot is-color-other" aria-hidden="true"></i>Other recorded countries</span><span><i class="admin-country-dot" aria-hidden="true"></i>No pieces recorded</span><small>Hover or select a country for its count.</small></div>
    </div>
    <div class="admin-country-footer">
      <p>Based on the saved country on each composer record. {{ number_format($map['unlocated']) }} {{ $map['unlocated'] === 1 ? 'piece has' : 'pieces have' }} no usable composer country recorded.</p>
      <details><summary>All country counts</summary><div class="admin-country-table-scroll"><table><caption class="visually-hidden">Current catalogue pieces by saved composer country</caption><thead><tr><th scope="col">Country</th><th scope="col">Pieces</th><th scope="col">Share with composer country</th></tr></thead><tbody>
@forelse($map['countries'] as $country)<tr><th scope="row">{{ $country['name'] }}</th><td>{{ number_format($country['total']) }}</td><td>{{ $country['share'] }}%</td></tr>
@empty<tr><td colspan="3">No usable composer countries have been recorded yet.</td></tr>
@endforelse</tbody></table></div></details>
    </div>
  </div>
</section>
