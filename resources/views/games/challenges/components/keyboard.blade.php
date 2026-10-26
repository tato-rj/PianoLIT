<div id="music-keyboard" style="display: none" class="w-100 position-fixed bottom-0 left-0 pb-4 text-center">
		<div class="rounded border border shadow-lg py-1 px-3 mx-auto d-inline-block" style="background: rgba(255,255,255,0.95);">
			<label class="w-100 text-center fw-bold mb-1 opacity-4" style="font-size: 70%">MUSIC KEYBOARD</label>
			<div class="d-flex justify-content-around flex-wrap">
				<div class="m-1 btn-floating">
					<button class="rounded btn btn-white text-dark" type="button" data-lettername="C" data-solfege="DO">C</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="D" data-solfege="RE">D</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="E" data-solfege="MI">E</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="F" data-solfege="FA">F</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="G" data-solfege="SOL">G</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="A" data-solfege="LA">A</button>
				</div>
				<div class="m-1 btn-floating">
				<button class="rounded btn btn-white text-dark" type="button" data-lettername="B" data-solfege="SI">B</button>
				</div>
			</div>
			<div class="d-flex justify-content-between flex-wrap">
				<div class="btn-floating-group m-1">
					<div class="btn-floating">
						<button class="rounded btn btn-white text-dark" data-accidental="sharp" type="button">♯</button>
					</div>
					<div class="btn-floating">
						<button class="rounded btn btn-white text-dark" data-accidental="flat" type="button">♭</button>
					</div>
				</div>
				<div class="m-1 btn-floating">
					<button class="rounded btn btn-white text-dark" data-delete type="button">@include('games.challenges.components.icon', ['icon' => 'delete-left', 'mr' => 0])</button>
				</div>
			</div>
		</div>

</div>
