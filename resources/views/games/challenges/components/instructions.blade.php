<p
  id="{{$instructionId ?? 'instructions'}}"
  class="game-instructions{{!empty($instructionClass) ? ' '.$instructionClass : ''}}"
  aria-live="{{$instructionAriaLive ?? 'polite'}}">{{$instructionContent ?? ($slot ?? '')}}</p>
