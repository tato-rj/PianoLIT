/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/metronome/voice-effects.js"
/*!*************************************************!*\
  !*** ./resources/js/metronome/voice-effects.js ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VOICE_EFFECTS: () => (/* binding */ VOICE_EFFECTS),
/* harmony export */   applyVoiceEffect: () => (/* binding */ applyVoiceEffect),
/* harmony export */   sampleBuffer: () => (/* binding */ sampleBuffer)
/* harmony export */ });
/* harmony import */ var _voice_stretch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./voice-stretch */ "./resources/js/metronome/voice-stretch.js");
/* harmony import */ var _data_metronome_voice_effects_json__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../data/metronome-voice-effects.json */ "./resources/data/metronome-voice-effects.json");
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }


// All effects are local DSP, never speech synthesis or character impersonations.

var VOICE_EFFECTS = _data_metronome_voice_effects_json__WEBPACK_IMPORTED_MODULE_1__;
function sampleBuffer(channels, length, sampleRate) {
  var data = Array.from({
    length: channels
  }, function () {
    return new Float32Array(length);
  });
  return {
    length: length,
    sampleRate: sampleRate,
    numberOfChannels: channels,
    duration: length / sampleRate,
    getChannelData: function getChannelData(channel) {
      return data[channel];
    }
  };
}
function interpolate(samples, position) {
  var at = Math.max(0, Math.min(samples.length - 1, position));
  var left = Math.floor(at),
    mix = at - left;
  return samples[left] * (1 - mix) + (samples[left + 1] || 0) * mix;
}

// Small damped delay network, baked into the selected clips in the worker.
// Diffusion softens distinct echoes; no live reverb nodes or downloaded impulse.
function reverberate(buffer, _ref) {
  var mix = _ref.mix,
    tail = _ref.tail,
    decay = _ref.decay,
    damping = _ref.damping;
  var rate = buffer.sampleRate,
    input = buffer.getChannelData(0);
  var tailLength = Math.round(rate * tail);
  var result = sampleBuffer(1, input.length + tailLength, rate);
  var output = result.getChannelData(0);
  var combs = [29.7, 37.1, 41.1, 43.7].map(function (milliseconds) {
    var length = Math.round(rate * milliseconds / 1000);
    return {
      samples: new Float32Array(length),
      at: 0,
      low: 0,
      feedback: Math.pow(0.001, length / rate / decay)
    };
  });
  var diffusers = [5.3, 1.7].map(function (milliseconds) {
    return {
      samples: new Float32Array(Math.max(1, Math.round(rate * milliseconds / 1000))),
      at: 0
    };
  });
  var dampingAlpha = 1 - Math.exp(-2 * Math.PI * damping / rate);
  var predelay = Math.round(rate * 0.012);
  var tailFade = Math.min(tailLength, Math.round(rate * 0.08));
  for (var i = 0; i < output.length; i++) {
    var excitation = input[i - predelay] || 0;
    var wet = 0;
    var _iterator = _createForOfIteratorHelper(combs),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var comb = _step.value;
        var delayed = comb.samples[comb.at];
        comb.low += dampingAlpha * (delayed - comb.low);
        comb.samples[comb.at] = excitation + comb.low * comb.feedback;
        comb.at = (comb.at + 1) % comb.samples.length;
        wet += delayed / combs.length;
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
    var _iterator2 = _createForOfIteratorHelper(diffusers),
      _step2;
    try {
      for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
        var diffuser = _step2.value;
        var _delayed = diffuser.samples[diffuser.at];
        var diffused = _delayed - wet * 0.6;
        diffuser.samples[diffuser.at] = wet + diffused * 0.6;
        diffuser.at = (diffuser.at + 1) % diffuser.samples.length;
        wet = diffused;
      }
    } catch (err) {
      _iterator2.e(err);
    } finally {
      _iterator2.f();
    }
    var fade = Math.min(1, (output.length - 1 - i) / Math.max(1, tailFade));
    output[i] = (input[i] || 0) * (1 - mix) + wet * mix * (0.5 - 0.5 * Math.cos(Math.PI * fade));
  }
  return result;
}

// Pure, bounded processing, run in a worker once per choice/source, never per beat.
function applyVoiceEffect(buffer, id) {
  var effect = VOICE_EFFECTS[id];
  if (!effect) throw new Error('Unknown voice effect');
  if (id === 'classic') return buffer;
  var rate = buffer.sampleRate;
  var input = buffer.getChannelData(0);
  var pitched = sampleBuffer(1, Math.round(input.length / effect.pitch), rate);
  var resampled = pitched.getChannelData(0);
  for (var i = 0; i < resampled.length; i++) {
    var at = i * effect.pitch,
      left = Math.floor(at),
      mix = at - left;
    resampled[i] = (input[left] || 0) * (1 - mix) + (input[left + 1] || 0) * mix;
  }
  // Restore articulation length after pitch shifting; do not just speed up playback.
  var result = (0,_voice_stretch__WEBPACK_IMPORTED_MODULE_0__.stretchVoice)(pitched, buffer.duration / (effect.tempo || 1), sampleBuffer);
  var output = result.getChannelData(0);
  if (effect.vibrato) {
    var dry = output.slice();
    var speed = 2 * Math.PI * effect.vibrato.rate / rate;
    var depth = effect.vibrato.depth / speed;
    for (var _i = 0; _i < output.length; _i++) {
      // Pin the word's edges while bending the pitch through its middle.
      var envelope = Math.sin(Math.PI * _i / Math.max(1, output.length - 1));
      output[_i] = interpolate(dry, _i + depth * Math.sin(speed * _i) * envelope);
    }
  }
  var low = 0,
    high = 0,
    previous = 0;
  var lowAlpha = effect.lowpass ? 1 - Math.exp(-2 * Math.PI * effect.lowpass / rate) : 1;
  var highAlpha = effect.highpass ? Math.exp(-2 * Math.PI * effect.highpass / rate) : 0;
  for (var _i2 = 0; _i2 < output.length; _i2++) {
    var value = output[_i2];
    if (effect.highpass) {
      high = highAlpha * (high + value - previous);
      previous = value;
      value = high;
    }
    low += lowAlpha * (value - low);
    value = low;
    if (effect.brightness) value += effect.brightness * (value - (output[_i2 - 1] || 0));
    // Bipolar ring modulation creates metallic sidebands, rather than the
    // previous shallow volume pulse that left most of the natural voice.
    if (effect.robot) value *= 1 - effect.robot.mix + effect.robot.mix * Math.sin(2 * Math.PI * effect.robot.rate * _i2 / rate);
    if (effect.tremolo) value *= 1 - effect.tremolo.depth * (0.5 + 0.5 * Math.sin(2 * Math.PI * effect.tremolo.rate * _i2 / rate));
    output[_i2] = value;
  }
  if (effect.chorus) {
    var _dry = output.slice(),
      _effect$chorus = effect.chorus,
      delay = _effect$chorus.delay,
      _depth = _effect$chorus.depth,
      _speed = _effect$chorus.rate,
      _mix = _effect$chorus.mix;
    for (var _i3 = 0; _i3 < output.length; _i3++) {
      var sweep = _depth * Math.sin(2 * Math.PI * _speed * _i3 / rate);
      var first = _i3 - rate * (delay + sweep) / 1000;
      var second = _i3 - rate * (delay * 1.7 - sweep) / 1000;
      var voices = ((first >= 0 ? interpolate(_dry, first) : 0) + (second >= 0 ? interpolate(_dry, second) : 0)) / 2;
      output[_i3] = _dry[_i3] * (1 - _mix) + voices * _mix;
    }
  }
  if (effect.echo) {
    var _dry2 = output.slice();
    var delays = effect.echoDelays || [28, 57];
    for (var _i4 = 0, _arr = [[delays[0], 1], [delays[1], 0.45]]; _i4 < _arr.length; _i4++) {
      var _arr$_i = _slicedToArray(_arr[_i4], 2),
        milliseconds = _arr$_i[0],
        scale = _arr$_i[1];
      var _delay = Math.round(rate * milliseconds / 1000);
      for (var _i5 = _delay; _i5 < output.length; _i5++) output[_i5] += _dry2[_i5 - _delay] * effect.echo * scale;
    }
  }
  if (effect.reverb) {
    result = reverberate(result, effect.reverb);
    output = result.getChannelData(0);
  }
  var sourceEnergy = 0,
    energy = 0,
    peak = 0;
  var _iterator3 = _createForOfIteratorHelper(input),
    _step3;
  try {
    for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
      var _value = _step3.value;
      sourceEnergy += _value * _value;
    }
  } catch (err) {
    _iterator3.e(err);
  } finally {
    _iterator3.f();
  }
  var _iterator4 = _createForOfIteratorHelper(output),
    _step4;
  try {
    for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
      var _value2 = _step4.value;
      energy += _value2 * _value2;
      peak = Math.max(peak, Math.abs(_value2));
    }
    // A reverb tail adds space, not an increase in the word's loudness.
  } catch (err) {
    _iterator4.e(err);
  } finally {
    _iterator4.f();
  }
  var energyLength = effect.reverb ? input.length : output.length;
  var gain = Math.min(Math.sqrt(sourceEnergy / input.length / Math.max(1e-12, energy / energyLength)), 0.85 / Math.max(1e-6, peak));
  var fade = Math.round(rate * 0.005);
  for (var _i6 = 0; _i6 < output.length; _i6++) {
    output[_i6] *= gain * Math.min(1, _i6 / fade, (output.length - 1 - _i6) / fade);
  }
  return result;
}

/***/ },

/***/ "./resources/js/metronome/voice-stretch.js"
/*!*************************************************!*\
  !*** ./resources/js/metronome/voice-stretch.js ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   compressVoice: () => (/* binding */ compressVoice),
/* harmony export */   stretchVoice: () => (/* binding */ stretchVoice)
/* harmony export */ });
// Compress a short count with waveform-similar overlap/add. Copying samples at
// their original spacing preserves pitch; only the distance between grains
// changes. Work on a new buffer so saved recordings and previews stay intact.
function compressVoice(buffer, maxDuration, createBuffer) {
  if (buffer.duration <= maxDuration) return buffer;
  return stretchVoice(buffer, maxDuration, createBuffer);
}

// Also used off the audio thread for pitch changes whose duration stays intact.
function stretchVoice(buffer, duration, createBuffer) {
  var rate = buffer.sampleRate;
  var length = Math.max(1, Math.floor(duration * rate));
  var frame = Math.min(Math.round(rate * 0.04), Math.floor(Math.min(length, buffer.length) / 2));
  var hop = Math.max(1, Math.floor(frame / 2));
  var radius = Math.round(rate * 0.012);
  var lastInput = buffer.length - frame;
  var lastOutput = length - frame;
  var steps = Math.ceil(lastOutput / hop);
  var result = createBuffer(buffer.numberOfChannels, length, rate);
  var inputs = Array.from({
    length: buffer.numberOfChannels
  }, function (_, channel) {
    return buffer.getChannelData(channel);
  });
  var outputs = inputs.map(function (_, channel) {
    return result.getChannelData(channel);
  });
  outputs.forEach(function (output, channel) {
    return output.set(inputs[channel].subarray(0, frame));
  });
  var previousInput = 0;
  var previousOutput = 0;
  var _loop = function _loop() {
    var at = Math.round(step * lastOutput / steps);
    var overlap = previousOutput + frame - at;
    var expected = Math.round(at * lastInput / lastOutput);
    // Pin the first and last grains to retain the word's onset and ending.
    var offset = at === lastOutput ? lastInput : matchingOffset(inputs[0], outputs[0], at, overlap, expected, Math.max(previousInput + 1, expected - radius), Math.min(lastInput, expected + radius), radius);
    outputs.forEach(function (output, channel) {
      var input = inputs[channel];
      for (var i = 0; i < overlap; i++) {
        var mix = i / overlap;
        output[at + i] = output[at + i] * (1 - mix) + input[offset + i] * mix;
      }
      output.set(input.subarray(offset + overlap, offset + frame), at + overlap);
    });
    previousInput = offset;
    previousOutput = at;
  };
  for (var step = 1; step <= steps; step++) {
    _loop();
  }
  return result;
}
function matchingOffset(input, output, at, overlap, expected, first, last, radius) {
  var energy = 0;
  for (var i = 0; i < overlap; i += 4) energy += Math.pow(output[at + i], 2);
  if (energy < 1e-9) return expected;
  var score = function score(offset, stride) {
    var dot = 0,
      left = 0,
      right = 0;
    for (var _i = 0; _i < overlap; _i += stride) {
      var a = output[at + _i],
        b = input[offset + _i];
      dot += a * b;
      left += a * a;
      right += b * b;
    }
    // Prefer a nearby match when several periods of a vowel are alike.
    return dot / Math.sqrt(left * right + 1e-20) - Math.abs(offset - expected) / radius * 0.005;
  };
  var best = Math.max(first, Math.min(last, expected));
  var bestScore = score(best, 4);
  for (var offset = first; offset <= last; offset += 4) {
    var value = score(offset, 4);
    if (value > bestScore) {
      best = offset;
      bestScore = value;
    }
  }
  var coarse = best;
  bestScore = score(best, 1);
  for (var _offset = Math.max(first, coarse - 3); _offset <= Math.min(last, coarse + 3); _offset++) {
    var _value = score(_offset, 1);
    if (_value > bestScore) {
      best = _offset;
      bestScore = _value;
    }
  }
  return best;
}

/***/ },

/***/ "./resources/data/metronome-voice-effects.json"
/*!*****************************************************!*\
  !*** ./resources/data/metronome-voice-effects.json ***!
  \*****************************************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"classic":{"label":"Normal","icon":"mic","pitch":1},"cartoon":{"label":"Cartoon","icon":"sparkles","pitch":1.5,"highpass":180,"brightness":0.45,"vibrato":{"rate":7,"depth":0.075}},"deep-space":{"label":"Deep Space","icon":"moon","pitch":0.68,"lowpass":4600,"echo":0.12},"robot":{"label":"Robot","icon":"settings","pitch":1,"highpass":200,"lowpass":4200,"robot":{"rate":85,"mix":0.92}},"ghost":{"label":"Ghost","icon":"ghost","pitch":1.18,"highpass":420,"chorus":{"delay":18,"depth":1.5,"rate":0.65,"mix":0.25},"reverb":{"mix":0.48,"tail":0.28,"decay":0.55,"damping":3600}},"chipmunk":{"label":"Chipmunk","icon":"zap","pitch":1.75,"tempo":1.1,"highpass":240,"brightness":0.2}}');

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!********************************************************!*\
  !*** ./resources/js/metronome/voice-effects-worker.js ***!
  \********************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _voice_effects__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./voice-effects */ "./resources/js/metronome/voice-effects.js");

self.onmessage = function (event) {
  try {
    var _event$data = event.data,
      effect = _event$data.effect,
      clips = _event$data.clips;
    if (!Array.isArray(clips) || clips.length !== 4) throw new Error('Four numbers required');
    var output = clips.map(function (_ref) {
      var samples = _ref.samples,
        rate = _ref.rate;
      if (!(samples instanceof Float32Array) || rate < 8000 || rate > 96000 || samples.length / rate > 2) throw new Error('Invalid recording');
      var processed = (0,_voice_effects__WEBPACK_IMPORTED_MODULE_0__.applyVoiceEffect)({
        length: samples.length,
        duration: samples.length / rate,
        sampleRate: rate,
        numberOfChannels: 1,
        getChannelData: function getChannelData() {
          return samples;
        }
      }, effect);
      return {
        samples: processed.getChannelData(0),
        rate: rate
      };
    });
    self.postMessage({
      clips: output
    }, output.map(function (clip) {
      return clip.samples.buffer;
    }));
  } catch (error) {
    self.postMessage({
      error: 'Voice effect unavailable'
    });
  }
};
})();

/******/ })()
;