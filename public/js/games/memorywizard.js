/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/games/duel/DuelClient.js"
/*!***********************************************!*\
  !*** ./resources/js/games/duel/DuelClient.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DuelClient: () => (/* binding */ DuelClient),
/* harmony export */   bootGame: () => (/* binding */ bootGame)
/* harmony export */ });
/* harmony import */ var _transport__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./transport */ "./resources/js/games/duel/transport.js");
/* harmony import */ var _dialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./dialog */ "./resources/js/games/duel/dialog.js");
/* harmony import */ var _adapters__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./adapters */ "./resources/js/games/duel/adapters.js");
/* harmony import */ var _answerFeedback__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./answerFeedback */ "./resources/js/games/duel/answerFeedback.js");
/* harmony import */ var _presence__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./presence */ "./resources/js/games/duel/presence.js");
/* harmony import */ var _idle__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./idle */ "./resources/js/games/duel/idle.js");
/* harmony import */ var _results__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./results */ "./resources/js/games/duel/results.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _regeneratorValues(e) { if (null != e) { var t = e["function" == typeof Symbol && Symbol.iterator || "@@iterator"], r = 0; if (t) return t.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) return { next: function next() { return e && r >= e.length && (e = void 0), { value: e && e[r++], done: !e }; } }; } throw new TypeError(_typeof(e) + " is not iterable"); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }







var DuelClient = /*#__PURE__*/function () {
  function DuelClient(state, createGame) {
    _classCallCheck(this, DuelClient);
    this.state = state;
    this.createGame = createGame;
    this.transport = new _transport__WEBPACK_IMPORTED_MODULE_0__.DuelTransport();
    this.game = null;
    this.queue = Promise.resolve();
    this.connected = false;
    this.starting = false;
    this.localProgress = this.you().progress;
    this.sequence = state.sequence;
    this.finishPending = false;
    this.leaving = false;
    this.rematching = false;
    this.gameNeedsRestart = false;
    this.answerIds = new Set();
    this.hud = document.getElementById('duel-hud');
    this.results = this.hud.querySelector('[data-duel-results]');
  }
  return _createClass(DuelClient, [{
    key: "you",
    value: function you() {
      var _this = this;
      return this.state.players.find(function (player) {
        return player.role === _this.state.role;
      });
    }
  }, {
    key: "opponent",
    value: function opponent() {
      var _this2 = this;
      return this.state.players.find(function (player) {
        return player.role !== _this2.state.role;
      });
    }
  }, {
    key: "begin",
    value: function begin() {
      var _document$querySelect,
        _this3 = this;
      document.body.classList.add('duel-mode');
      this.hud.hidden = false;
      (_document$querySelect = document.querySelector('#progress-bar')) === null || _document$querySelect === void 0 || (_document$querySelect = _document$querySelect.closest('.mb-2')) === null || _document$querySelect === void 0 || _document$querySelect.classList.add('d-none');
      $('#page-wrapper').show();
      $('#page-wrapper').attr('inert', '');
      this.render();
      document.addEventListener('click', function (event) {
        if (event.target.closest('[data-duel-leave]')) _this3.leave();
        if (event.target.closest('[data-duel-rematch]')) _this3.rematch();
      });
      if (['cancelled', 'expired'].includes(this.state.status)) {
        this.receive(this.state);
        return;
      }
      if (this.state.status !== 'finished') (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
        phase: 'connecting',
        message: 'Connecting to your Duel…',
        leave: true
      });
      this.idle = new _idle__WEBPACK_IMPORTED_MODULE_5__.DuelIdle({
        onExpire: function onExpire() {
          return _this3.leave({
            automatic: true
          });
        },
        onResume: function onResume() {
          _this3.receive(_this3.state);
          if (_this3.state.status === 'countdown' || !_this3.game && _this3.state.status === 'playing') _this3.tick();else if (_this3.state.status === 'playing') (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.closeDialog)();
        }
      }).start();
      this.presence = new _presence__WEBPACK_IMPORTED_MODULE_4__.DuelPresence(this.transport, this.state.id, function (state) {
        return _this3.receive(state);
      }, function (error) {
        return _this3.error(error);
      });
      if (this.state.status !== 'finished') this.presence.start();else this.idle.stop();
      this.transport.subscribe(this.state.id, {
        update: function update(state) {
          return _this3.receive(state);
        },
        answer: function answer(event) {
          return _this3.receiveAnswer(event);
        },
        connected: function () {
          var _connected = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
            var _t, _t2;
            return _regenerator().w(function (_context) {
              while (1) switch (_context.p = _context.n) {
                case 0:
                  _this3.connected = true;
                  _context.p = 1;
                  _t = _this3;
                  _context.n = 2;
                  return _this3.transport.request("/".concat(_this3.state.id));
                case 2:
                  _t.receive.call(_t, _context.v);
                  _context.n = 4;
                  break;
                case 3:
                  _context.p = 3;
                  _t2 = _context.v;
                  _this3.error(_t2);
                case 4:
                  return _context.a(2);
              }
            }, _callee, null, [[1, 3]]);
          }));
          function connected() {
            return _connected.apply(this, arguments);
          }
          return connected;
        }(),
        disconnected: function disconnected() {
          _this3.connected = false;
          _this3.render();
        },
        error: function error() {
          return _this3.error(new Error('Could not connect to multiplayer. Please try again.'));
        }
      });
      document.querySelector('[data-duel-ready]').addEventListener('click', /*#__PURE__*/function () {
        var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(event) {
          var button, _window$Tone, _window$Tone$start, _t3, _t4, _t5;
          return _regenerator().w(function (_context2) {
            while (1) switch (_context2.p = _context2.n) {
              case 0:
                button = event.currentTarget;
                button.disabled = true;
                // Ready is also an audio-unlock gesture for browser autoplay policies.
                _context2.p = 1;
                _context2.n = 2;
                return (_window$Tone = window.Tone) === null || _window$Tone === void 0 || (_window$Tone$start = _window$Tone.start) === null || _window$Tone$start === void 0 ? void 0 : _window$Tone$start.call(_window$Tone);
              case 2:
                _context2.n = 4;
                break;
              case 3:
                _context2.p = 3;
                _t3 = _context2.v;
              case 4:
                _context2.p = 4;
                _t4 = _this3;
                _context2.n = 5;
                return _this3.transport.request("/".concat(_this3.state.id, "/ready"), {
                  seed: _this3.state.seed
                });
              case 5:
                _t4.receive.call(_t4, _context2.v);
                _context2.n = 7;
                break;
              case 6:
                _context2.p = 6;
                _t5 = _context2.v;
                button.disabled = false;
                _this3.error(_t5);
              case 7:
                return _context2.a(2);
            }
          }, _callee2, null, [[4, 6], [1, 3]]);
        }));
        return function (_x) {
          return _ref.apply(this, arguments);
        };
      }());
      this.tickTimer = setInterval(function () {
        return _this3.tick();
      }, 100);
      window.addEventListener('online', function () {
        return _this3.transport.request("/".concat(_this3.state.id)).then(function (state) {
          return _this3.receive(state);
        })["catch"](function (error) {
          return _this3.error(error);
        });
      });
    }
  }, {
    key: "receive",
    value: function receive(state) {
      var _this4 = this,
        _this$idle4,
        _this$idle5;
      if (state.id !== this.state.id || state.revision < this.state.revision) return;
      var newMatch = state.seed && state.seed !== this.state.seed;
      this.state = _objectSpread(_objectSpread({}, this.state), state);
      if (newMatch) {
        var _this$clearAnswerFeed, _this$idle, _this$idle2, _this$presence;
        this.localProgress = this.you().progress;
        this.sequence = this.state.sequence || 0;
        this.finishPending = false;
        this.queue = Promise.resolve();
        this.answerIds.clear();
        (_this$clearAnswerFeed = this.clearAnswerFeedback) === null || _this$clearAnswerFeed === void 0 || _this$clearAnswerFeed.call(this);
        this.gameNeedsRestart = !!this.game;
        this.results.hidden = true;
        delete this.results.dataset.outcome;
        document.body.classList.remove('duel-results-open');
        this.hud.hidden = false;
        (_this$idle = this.idle) === null || _this$idle === void 0 || _this$idle.stop();
        (_this$idle2 = this.idle) === null || _this$idle2 === void 0 || _this$idle2.start();
        (_this$presence = this.presence) === null || _this$presence === void 0 || _this$presence.stop();
        this.presence = new _presence__WEBPACK_IMPORTED_MODULE_4__.DuelPresence(this.transport, this.state.id, function (next) {
          return _this4.receive(next);
        }, function (error) {
          return _this4.error(error);
        });
        this.presence.start();
      }
      this.render();
      if (['cancelled', 'expired'].includes(this.state.status)) {
        var _this$idle3, _this$clearAnswerFeed2, _this$game, _this$game$_stopLoop, _this$game2, _this$game2$_stopGame, _this$game3, _this$game3$_cancelTi, _this$game4, _this$game4$_cancelCa, _this$presence2;
        (_this$idle3 = this.idle) === null || _this$idle3 === void 0 || _this$idle3.stop();
        (_this$clearAnswerFeed2 = this.clearAnswerFeedback) === null || _this$clearAnswerFeed2 === void 0 || _this$clearAnswerFeed2.call(this);
        (_this$game = this.game) === null || _this$game === void 0 || (_this$game$_stopLoop = _this$game._stopLoop) === null || _this$game$_stopLoop === void 0 || _this$game$_stopLoop.call(_this$game);
        (_this$game2 = this.game) === null || _this$game2 === void 0 || (_this$game2$_stopGame = _this$game2._stopGameTimer) === null || _this$game2$_stopGame === void 0 || _this$game2$_stopGame.call(_this$game2);
        (_this$game3 = this.game) === null || _this$game3 === void 0 || (_this$game3$_cancelTi = _this$game3._cancelTimers) === null || _this$game3$_cancelTi === void 0 || _this$game3$_cancelTi.call(_this$game3);
        (_this$game4 = this.game) === null || _this$game4 === void 0 || (_this$game4$_cancelCa = _this$game4._cancelCardAudition) === null || _this$game4$_cancelCa === void 0 || _this$game4$_cancelCa.call(_this$game4);
        (_this$presence2 = this.presence) === null || _this$presence2 === void 0 || _this$presence2.stop();
        this.results.hidden = true;
        document.body.classList.remove('duel-results-open');
        $('#page-wrapper').attr('inert', '');
        var message = this.state.left_by ? this.state.left_by === this.state.role ? 'You left the Duel.' : 'Opponent left the Duel.' : "This Duel is ".concat(this.state.status, ".");
        (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
          phase: this.state.left_by ? this.state.left_by === this.state.role ? 'you-left' : 'opponent-left' : this.state.status,
          message: message,
          exit: true
        });
        return;
      }
      if (this.state.status === 'finished') (_this$idle4 = this.idle) === null || _this$idle4 === void 0 || _this$idle4.stop();
      if ((_this$idle5 = this.idle) !== null && _this$idle5 !== void 0 && _this$idle5.warning) return;
      if (this.state.status === 'ready') {
        var _this$opponent;
        (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
          phase: this.you().ready ? 'waiting-ready' : 'ready',
          opponentReady: (_this$opponent = this.opponent()) === null || _this$opponent === void 0 ? void 0 : _this$opponent.ready,
          message: this.you().ready ? '✓ You are ready · Waiting for opponent…' : 'Opponent connected ✓',
          ready: !this.you().ready,
          leave: true
        });
      }
      if (this.you().finished_at) (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.closeDialog)();
      if (this.state.status === 'finished') {
        var _this$presence3;
        (_this$presence3 = this.presence) === null || _this$presence3 === void 0 || _this$presence3.stop();
        (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.closeDialog)();
        this.showResults();
      }
    }
  }, {
    key: "tick",
    value: function () {
      var _tick = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
        var _this$idle6,
          _this5 = this;
        var remaining, _this$idle7, state, _t6;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              this.renderConnection();
              if (!((_this$idle6 = this.idle) !== null && _this$idle6 !== void 0 && _this$idle6.warning)) {
                _context3.n = 1;
                break;
              }
              return _context3.a(2);
            case 1:
              if (['countdown', 'playing'].includes(this.state.status)) {
                _context3.n = 2;
                break;
              }
              return _context3.a(2);
            case 2:
              remaining = Date.parse(this.state.starts_at) - (Date.now() + this.transport.offset);
              if (!(remaining > 0)) {
                _context3.n = 3;
                break;
              }
              (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
                phase: 'countdown',
                message: String(Math.min(3, Math.ceil(remaining / 1000))),
                leave: true
              });
              return _context3.a(2);
            case 3:
              if (!(this.game && !this.gameNeedsRestart || this.starting || this.leaving || this.you().finished_at)) {
                _context3.n = 4;
                break;
              }
              return _context3.a(2);
            case 4:
              this.starting = true;
              _context3.p = 5;
              _context3.n = 6;
              return this.transport.request("/".concat(this.state.id));
            case 6:
              state = _context3.v;
              this.receive(state);
              if (!(this.state.status !== 'playing' || this.leaving || (_this$idle7 = this.idle) !== null && _this$idle7 !== void 0 && _this$idle7.warning)) {
                _context3.n = 7;
                break;
              }
              return _context3.a(2);
            case 7:
              (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
                phase: 'go',
                message: 'GO!'
              });
              setTimeout(function () {
                var _this5$idle;
                if (_this5.state.status === 'playing' && !((_this5$idle = _this5.idle) !== null && _this5$idle !== void 0 && _this5$idle.warning)) (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.closeDialog)();
              }, 350);
              $('#page-wrapper').removeAttr('inert');
              window.__activeDuel = this;
              if (!this.gameNeedsRestart) {
                _context3.n = 8;
                break;
              }
              this.gameNeedsRestart = false;
              this.game._restartDuel();
              return _context3.a(2);
            case 8:
              if (!(this.you().progress === this.state.total)) {
                _context3.n = 9;
                break;
              }
              this.game = (0,_adapters__WEBPACK_IMPORTED_MODULE_2__.connectGame)(this.createGame(this.state.options), this, {
                finishOnly: true
              });
              return _context3.a(2);
            case 9:
              this.game = (0,_adapters__WEBPACK_IMPORTED_MODULE_2__.connectGame)(this.createGame(this.state.options), this);
              _context3.n = 11;
              break;
            case 10:
              _context3.p = 10;
              _t6 = _context3.v;
              this.error(_t6);
            case 11:
              _context3.p = 11;
              this.starting = false;
              return _context3.f(11);
            case 12:
              return _context3.a(2);
          }
        }, _callee3, this, [[5, 10, 11, 12]]);
      }));
      function tick() {
        return _tick.apply(this, arguments);
      }
      return tick;
    }()
  }, {
    key: "enqueue",
    value: function enqueue(action, data) {
      var _this6 = this;
      data = _objectSpread(_objectSpread({}, data), {}, {
        seed: this.state.seed
      });
      var work = /*#__PURE__*/function () {
        var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4() {
          var _loop, _ret, attempt;
          return _regenerator().w(function (_context5) {
            while (1) switch (_context5.n) {
              case 0:
                _loop = /*#__PURE__*/_regenerator().m(function _loop(attempt) {
                  var state, _t7;
                  return _regenerator().w(function (_context4) {
                    while (1) switch (_context4.p = _context4.n) {
                      case 0:
                        if (!(data.seed !== _this6.state.seed || _this6.leaving || ['cancelled', 'expired', 'finished'].includes(_this6.state.status))) {
                          _context4.n = 1;
                          break;
                        }
                        return _context4.a(2, {
                          v: void 0
                        });
                      case 1:
                        _context4.p = 1;
                        _context4.n = 2;
                        return _this6.transport.request("/".concat(_this6.state.id, "/").concat(action), data);
                      case 2:
                        state = _context4.v;
                        _this6.receive(state);
                        $('[data-duel-error]').text('').prop('hidden', true).hide();
                        return _context4.a(2, {
                          v: void 0
                        });
                      case 3:
                        _context4.p = 3;
                        _t7 = _context4.v;
                        if (!(data.seed !== _this6.state.seed || _this6.leaving || ['cancelled', 'expired'].includes(_this6.state.status))) {
                          _context4.n = 4;
                          break;
                        }
                        return _context4.a(2, {
                          v: void 0
                        });
                      case 4:
                        _this6.error(_t7);
                        if (!(_t7.status && _t7.status < 500 && _t7.status !== 429)) {
                          _context4.n = 5;
                          break;
                        }
                        throw _t7;
                      case 5:
                        _context4.n = 6;
                        return new Promise(function (resolve) {
                          return setTimeout(resolve, Math.min(10000, 500 * Math.pow(2, attempt)));
                        });
                      case 6:
                        return _context4.a(2);
                    }
                  }, _loop, null, [[1, 3]]);
                });
                attempt = 0;
              case 1:
                return _context5.d(_regeneratorValues(_loop(attempt)), 2);
              case 2:
                _ret = _context5.v;
                if (!_ret) {
                  _context5.n = 3;
                  break;
                }
                return _context5.a(2, _ret.v);
              case 3:
                attempt++;
                _context5.n = 1;
                break;
              case 4:
                return _context5.a(2);
            }
          }, _callee4);
        }));
        return function work() {
          return _ref2.apply(this, arguments);
        };
      }();
      this.queue = this.queue.then(work);
      // Keep the queue rejected on invalid transitions so finishing cannot bypass progress.
      this.queue["catch"](function () {
        if (data.seed !== _this6.state.seed || _this6.leaving || ['cancelled', 'expired'].includes(_this6.state.status)) return;
        $('#page-wrapper').attr('inert', '');
        (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
          phase: 'error',
          message: 'Your game needs to reconnect. Refresh to restore the last saved round.',
          leave: true,
          exit: true
        });
      });
      return this.queue;
    }
  }, {
    key: "answer",
    value: function answer(correct) {
      if (this.leaving || this.state.status !== 'playing') return;
      // Cosmetic feedback must never delay saved progress or prevent finishing.
      this.transport.request("/".concat(this.state.id, "/answer"), {
        correct: correct
      })["catch"](function () {});
    }
  }, {
    key: "receiveAnswer",
    value: function receiveAnswer(event) {
      var _this$opponent2;
      if (this.leaving || !['playing', 'finished'].includes(this.state.status) || event.duel_id !== this.state.id || event.role !== ((_this$opponent2 = this.opponent()) === null || _this$opponent2 === void 0 ? void 0 : _this$opponent2.role) || typeof event.correct !== 'boolean' || this.answerIds.has(event.id)) return;
      this.answerIds.add(event.id);
      if (this.answerIds.size > 100) this.answerIds["delete"](this.answerIds.values().next().value);
      this.clearAnswerFeedback = (0,_answerFeedback__WEBPACK_IMPORTED_MODULE_3__.animateAnswerFeedback)(this.hud.querySelector('[data-duel-row="opponent"] [data-duel-name]'), event.correct);
    }
  }, {
    key: "progress",
    value: function progress(current, score, checkpoint) {
      if (current <= this.localProgress || this.finishPending || this.leaving || this.state.status !== 'playing') return;
      this.localProgress = current;
      this.enqueue('progress', {
        sequence: ++this.sequence,
        progress: current,
        score: score,
        checkpoint: checkpoint
      });
    }
  }, {
    key: "finished",
    value: function finished(result) {
      var _this7 = this;
      if (this.finishPending || this.you().finished_at || this.leaving || this.state.status !== 'playing') return;
      this.finishPending = true;
      $('#page-wrapper').attr('inert', '');
      this.enqueue('finish', {
        score: Math.round(result.score),
        accuracy: Math.round(result.accuracy)
      }).then(function () {
        if (_this7.you().finished_at && !['cancelled', 'expired'].includes(_this7.state.status)) _this7.showResults();
      })["catch"](function () {});
    }
  }, {
    key: "leave",
    value: function () {
      var _leave = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5() {
        var _this$idle8, _this$clearAnswerFeed3;
        var _ref3,
          _ref3$automatic,
          automatic,
          buttons,
          _this$presence4,
          _this$transport$echo,
          _this$presence5,
          _this$transport$echo2,
          _this$idle9,
          _args6 = arguments,
          _t8,
          _t9;
        return _regenerator().w(function (_context6) {
          while (1) switch (_context6.p = _context6.n) {
            case 0:
              _ref3 = _args6.length > 0 && _args6[0] !== undefined ? _args6[0] : {}, _ref3$automatic = _ref3.automatic, automatic = _ref3$automatic === void 0 ? false : _ref3$automatic;
              if (!this.leaving) {
                _context6.n = 1;
                break;
              }
              return _context6.a(2);
            case 1:
              this.leaving = true;
              (_this$idle8 = this.idle) === null || _this$idle8 === void 0 || _this$idle8.stop();
              (_this$clearAnswerFeed3 = this.clearAnswerFeedback) === null || _this$clearAnswerFeed3 === void 0 || _this$clearAnswerFeed3.call(this);
              $('#page-wrapper').attr('inert', '');
              buttons = document.querySelectorAll('[data-duel-leave]');
              buttons.forEach(function (button) {
                button.disabled = true;
              });
              if (!automatic) {
                _context6.n = 2;
                break;
              }
              (_this$presence4 = this.presence) === null || _this$presence4 === void 0 || _this$presence4.stop();
              this.transport.leaveOnExit(this.state.id);
              (_this$transport$echo = this.transport.echo) === null || _this$transport$echo === void 0 || _this$transport$echo.disconnect();
              window.location.href = window.__duelConfig.home;
              return _context6.a(2);
            case 2:
              _context6.p = 2;
              _t8 = this;
              _context6.n = 3;
              return this.transport.request("/".concat(this.state.id, "/leave"), {});
            case 3:
              _t8.receive.call(_t8, _context6.v);
              (_this$presence5 = this.presence) === null || _this$presence5 === void 0 || _this$presence5.stop();
              (_this$transport$echo2 = this.transport.echo) === null || _this$transport$echo2 === void 0 || _this$transport$echo2.disconnect();
              window.location.href = window.__duelConfig.home;
              _context6.n = 5;
              break;
            case 4:
              _context6.p = 4;
              _t9 = _context6.v;
              this.leaving = false;
              (_this$idle9 = this.idle) === null || _this$idle9 === void 0 || _this$idle9.start();
              buttons.forEach(function (button) {
                button.disabled = false;
              });
              this.error(_t9);
              (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
                phase: 'error',
                message: 'Could not leave the Duel. Try again or return to all games.',
                leave: true,
                exit: true,
                error: _t9.message
              });
            case 5:
              return _context6.a(2);
          }
        }, _callee5, this, [[2, 4]]);
      }));
      function leave() {
        return _leave.apply(this, arguments);
      }
      return leave;
    }()
  }, {
    key: "rematch",
    value: function () {
      var _rematch = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6() {
        var button, errorMessage, _window$Tone2, _window$Tone2$start, _t0, _t1, _t10;
        return _regenerator().w(function (_context7) {
          while (1) switch (_context7.p = _context7.n) {
            case 0:
              if (!(this.rematching || this.leaving || this.state.status !== 'finished' || this.you().rematch)) {
                _context7.n = 1;
                break;
              }
              return _context7.a(2);
            case 1:
              this.rematching = true;
              button = this.results.querySelector('[data-duel-rematch]');
              errorMessage = this.results.querySelector('[data-duel-rematch-error]');
              button.disabled = true;
              errorMessage.hidden = true;
              _context7.p = 2;
              _context7.p = 3;
              _context7.n = 4;
              return (_window$Tone2 = window.Tone) === null || _window$Tone2 === void 0 || (_window$Tone2$start = _window$Tone2.start) === null || _window$Tone2$start === void 0 ? void 0 : _window$Tone2$start.call(_window$Tone2);
            case 4:
              _context7.n = 6;
              break;
            case 5:
              _context7.p = 5;
              _t0 = _context7.v;
            case 6:
              _t1 = this;
              _context7.n = 7;
              return this.transport.request("/".concat(this.state.id, "/rematch"), {
                seed: this.state.seed
              });
            case 7:
              _t1.receive.call(_t1, _context7.v);
              _context7.n = 9;
              break;
            case 8:
              _context7.p = 8;
              _t10 = _context7.v;
              errorMessage.textContent = _t10.message;
              errorMessage.hidden = false;
            case 9:
              _context7.p = 9;
              this.rematching = false;
              button.disabled = !!this.you().rematch;
              return _context7.f(9);
            case 10:
              return _context7.a(2);
          }
        }, _callee6, this, [[3, 5], [2, 8, 9, 10]]);
      }));
      function rematch() {
        return _rematch.apply(this, arguments);
      }
      return rematch;
    }()
  }, {
    key: "render",
    value: function render() {
      for (var _i = 0, _arr = [['you', this.you()], ['opponent', this.opponent()]]; _i < _arr.length; _i++) {
        var _arr$_i = _slicedToArray(_arr[_i], 2),
          row = _arr$_i[0],
          player = _arr$_i[1];
        if (!player) continue;
        var root = this.hud.querySelector("[data-duel-row=\"".concat(row, "\"]"));
        var percent = Math.min(100, player.progress * 100 / this.state.total);
        root.querySelector('[data-duel-bar]').style.width = "".concat(percent, "%");
        root.querySelector('[data-duel-bar]').setAttribute('aria-valuenow', percent);
        root.querySelector('[data-duel-count]').textContent = "".concat(player.progress, " / ").concat(this.state.total);
        root.querySelector('[data-duel-score]').textContent = "\u03DF ".concat(player.score);
      }
      var status = this.hud.querySelector('[data-duel-status]');
      status.textContent = ['cancelled', 'expired'].includes(this.state.status) ? 'Duel ended' : this.state.status === 'finished' ? 'Duel finished' : this.state.status === 'ready' ? 'Getting ready' : this.state.status === 'countdown' ? 'Starting…' : 'Match in progress';
      status.classList.toggle('is-playing', this.state.status === 'playing');
      status.classList.toggle('text-green', this.state.status === 'playing');
      if (this.you().finished_at) this.showResults();
    }
  }, {
    key: "renderConnection",
    value: function renderConnection() {
      var connection = this.hud.querySelector('[data-duel-connection]');
      if (['cancelled', 'expired'].includes(this.state.status)) {
        connection.textContent = this.state.left_by && this.state.left_by !== this.state.role ? 'Opponent left the Duel' : 'Duel ended';
        connection.hidden = false;
        return;
      }
      var opponent = this.opponent();
      var disconnected = !this.connected || (opponent === null || opponent === void 0 ? void 0 : opponent.disconnected) || opponent && Date.now() + this.transport.offset - Date.parse(opponent.last_seen_at) > 45000;
      connection.textContent = !this.connected ? 'Reconnecting…' : opponent !== null && opponent !== void 0 && opponent.finished_at ? 'Opponent finished ✓' : disconnected ? 'Opponent disconnected…' : '';
      connection.hidden = !connection.textContent;
    }
  }, {
    key: "showResults",
    value: function showResults() {
      if (['cancelled', 'expired'].includes(this.state.status)) return;
      $('#page-wrapper').attr('inert', '');
      (0,_results__WEBPACK_IMPORTED_MODULE_6__.renderDuelResults)(this.results, this.state);
      this.hud.hidden = true;
    }
  }, {
    key: "error",
    value: function error(_error) {
      $('[data-duel-error]').text(_error.message).prop('hidden', false).show();
      var connection = this.hud.querySelector('[data-duel-connection]');
      connection.textContent = _error.message;
      connection.hidden = false;
    }
  }]);
}();
function bootGame(createGame) {
  if (!window.__duelState) {
    var _game$start;
    var game = createGame();
    (_game$start = game.start) === null || _game$start === void 0 || _game$start.call(game);
    return game;
  }
  var client = new DuelClient(window.__duelState, createGame);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () {
    return client.begin();
  }, {
    once: true
  });else client.begin();
  return client;
}

/***/ },

/***/ "./resources/js/games/duel/adapters.js"
/*!*********************************************!*\
  !*** ./resources/js/games/duel/adapters.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   connectGame: () => (/* binding */ connectGame)
/* harmony export */ });
/* harmony import */ var _random__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./random */ "./resources/js/games/duel/random.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var HISTORY_FIELDS = ['_stats', '_correctStreak', '_madeAnyMistake', '_targetSequence', '_lastTargetSignature', '_lastTargetName', '_previousAnswerIds', '_roundRecords', '_correctTaps', '_wrongTaps'];
var engines = {
  staff: {
    generation: 'newChallenge',
    progress: '_updateProgressBar',
    reset: '_resetProgress',
    score: 'points'
  },
  'tone-trek': {
    generation: '_startRound',
    progress: '_updateProgressBar',
    reset: '_resetRunUi',
    score: '_points',
    round: '_currentRound'
  },
  'beat-hero': {
    generation: '_startRound',
    progress: '_updateProgress',
    reset: '_resetGameUi',
    score: '_pointsValue',
    round: '_round'
  },
  'note-python': {
    generation: '_showStandardGameUi',
    progress: '_updateProgressBar',
    score: '_pointsValue',
    round: '_roundsCompleted'
  }
};
function connectGame(game, duel) {
  var _ref = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
    _ref$finishOnly = _ref.finishOnly,
    finishOnly = _ref$finishOnly === void 0 ? false : _ref$finishOnly;
  var engine = engines[duel.state.game] || engines.staff;
  var initial = duel.you();
  var completed = initial.progress;
  var earnedScore = initial.score;
  var initialized = false;
  var history = duel.state.checkpoint || {};
  var freshHistory = JSON.parse(JSON.stringify(Object.fromEntries(HISTORY_FIELDS.filter(function (key) {
    return key in game;
  }).map(function (key) {
    return [key, game[key]];
  }))));
  var checkpoint = function checkpoint() {
    var _game$staff;
    return JSON.parse(JSON.stringify(_objectSpread(_objectSpread({}, Object.fromEntries(HISTORY_FIELDS.filter(function (key) {
      return key in game;
    }).map(function (key) {
      return [key, game[key]];
    }))), (_game$staff = game.staff) !== null && _game$staff !== void 0 && _game$staff.getClef ? {
      _duelClef: game.staff.getClef()
    } : {})));
  };
  var restore = function restore() {
    var _game$$points, _game$$progressBar, _game$$progressCounte, _game$staff2;
    game[engine.score] = earnedScore;
    if (engine.round) game[engine.round] = completed + (engine.round === '_roundsCompleted' ? 0 : 1);
    (_game$$points = game.$points) === null || _game$$points === void 0 || _game$$points.text(String(earnedScore));
    (_game$$progressBar = game.$progressBar) === null || _game$$progressBar === void 0 || _game$$progressBar.data('progress', completed * 100 / duel.state.total).css({
      width: "".concat(completed * 100 / duel.state.total, "%")
    });
    (_game$$progressCounte = game.$progressCounter) === null || _game$$progressCounte === void 0 || _game$$progressCounte.text("".concat(completed, " of ").concat(duel.state.total));
    if (['treble', 'bass', 'alto', 'tenor'].includes(history._duelClef)) (_game$staff2 = game.staff) === null || _game$staff2 === void 0 || _game$staff2.setClef(history._duelClef);
    var _iterator = _createForOfIteratorHelper(HISTORY_FIELDS),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var key = _step.value;
        if (Object.hasOwn(history, key) && key in game) game[key] = history[key];
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
    game._finalStartMs = Date.parse(duel.state.starts_at);
    game._startedAt = Date.parse(duel.state.starts_at);
  };
  var checkMethod = duel.state.game === 'beat-hero' ? '_handleCardTap' : duel.state.game === 'note-python' ? '_advanceSnake' : '_onCheck';
  if (typeof game[checkMethod] === 'function') {
    var check = game[checkMethod].bind(game);
    var counts = function counts() {
      return duel.state.game === 'beat-hero' ? [game._correctTaps + game._wrongTaps, game._correctTaps] : [game._stats.checksTotal, game._stats.checksCorrect];
    };
    game[checkMethod] = function () {
      var before = counts();
      var incomplete = duel.state.game === 'tone-trek' && !game._roundLocked && !game._areAllBlockInputsFilled();
      var report = function report(result) {
        var after = counts();
        if (after[0] > before[0] || incomplete) duel.answer(after[1] > before[1]);
        return result;
      };
      var result = check.apply(void 0, arguments);
      // Beat Hero counts answer taps synchronously; awaiting its preview audio
      // would accidentally include another tap's counters in this check.
      return report(result);
    };
  }
  if (engine.reset && game[engine.reset]) {
    var original = game[engine.reset].bind(game);
    game[engine.reset] = function () {
      var result = original.apply(void 0, arguments);
      restore();
      return result;
    };
  }
  var generate = game[engine.generation].bind(game);
  game[engine.generation] = function () {
    if (!initialized) {
      restore();
      initialized = true;
    }
    game._duelRandom = (0,_random__WEBPACK_IMPORTED_MODULE_0__.seededRandom)(duel.state.seed, completed);
    game._duelChallengeRandom = (0,_random__WEBPACK_IMPORTED_MODULE_0__.seededRandom)(duel.state.seed, "music:".concat(completed));
    return generate.apply(void 0, arguments);
  };
  var progress = game[engine.progress].bind(game);
  game[engine.progress] = function () {
    var result = progress.apply(void 0, arguments);
    var next = Math.round((Number(game.$progressBar.data('progress')) || 0) * duel.state.total / 100);
    if (next > completed) {
      completed = next;
      // Reset before generating the next challenge, independent of effects or hints.
      game._duelRandom = (0,_random__WEBPACK_IMPORTED_MODULE_0__.seededRandom)(duel.state.seed, completed);
      game._duelChallengeRandom = (0,_random__WEBPACK_IMPORTED_MODULE_0__.seededRandom)(duel.state.seed, "music:".concat(completed));
      queueMicrotask(function () {
        earnedScore = Math.max(0, Number(game[engine.score]) || 0);
        history = checkpoint();
        duel.progress(next, earnedScore, history);
      });
    }
    return result;
  };
  if (duel.state.game === 'tone-trek') {
    var timedOut = game._finishRoundAsTimedOut.bind(game);
    game._finishRoundAsTimedOut = function () {
      // Tone Trek advances its round on skip without advancing its single-player bar.
      game._madeAnyMistake = true;
      if (completed < game._currentRound) game._updateProgressBar();
      return timedOut.apply(void 0, arguments);
    };
  }
  if (duel.state.game === 'note-python') {
    var start = game.start.bind(game);
    game.start = function () {
      initialized = false;
      start();
      restore();
      // Both the initial start and a crash restart retain the Duel's earned rounds.
      game._setPlayButtons(true);
      game.$playWrap.hide();
      game._placeInitialSnake();
      game._directionQueue = [];
      game._spawnFoods(2, {
        preferredRow: game._rows - 2
      });
      if (game._showBombs()) game._spawnBombs(2);
      game._ensureTargetFoodPresent();
      game._renderEntities();
      game._startLoop();
    };
  }
  game._restartDuel = function () {
    var _game$$doublePoints, _game$$doublePoints$h, _game$$bonusBadge, _game$$bonusBadge$hid;
    completed = 0;
    earnedScore = 0;
    initialized = false;
    history = {};
    Object.assign(game, JSON.parse(JSON.stringify(freshHistory)));
    (_game$$doublePoints = game.$doublePoints) === null || _game$$doublePoints === void 0 || (_game$$doublePoints$h = _game$$doublePoints.hide) === null || _game$$doublePoints$h === void 0 || _game$$doublePoints$h.call(_game$$doublePoints);
    (_game$$bonusBadge = game.$bonusBadge) === null || _game$$bonusBadge === void 0 || (_game$$bonusBadge$hid = _game$$bonusBadge.hide) === null || _game$$bonusBadge$hid === void 0 || _game$$bonusBadge$hid.call(_game$$bonusBadge);
    game.start();
  };
  if (finishOnly) {
    restore();
    // Reuse the engine's accuracy and bonus calculation if refresh interrupted results.
    game._showFinalResults();
  } else game.start();
  return game;
}

/***/ },

/***/ "./resources/js/games/duel/answerFeedback.js"
/*!***************************************************!*\
  !*** ./resources/js/games/duel/answerFeedback.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   animateAnswerFeedback: () => (/* binding */ animateAnswerFeedback)
/* harmony export */ });
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
var active = new WeakMap();
function animateAnswerFeedback(label, correct) {
  var _active$get, _label$classList2;
  if (!label) return;
  (_active$get = active.get(label)) === null || _active$get === void 0 || _active$get();
  var classes = ['animate__animated', 'animate__heartBeat', correct ? 'text-green' : 'text-red'];
  var timer;
  var cleanup = function cleanup() {
    var _label$classList;
    clearTimeout(timer);
    label.removeEventListener('animationend', ended);
    label.removeEventListener('animationcancel', ended);
    (_label$classList = label.classList).remove.apply(_label$classList, classes);
    active["delete"](label);
  };
  var ended = function ended(event) {
    if (event.target === label) cleanup();
  };
  // Restart even when consecutive answers have the same outcome.
  void label.offsetWidth;
  (_label$classList2 = label.classList).add.apply(_label$classList2, classes);
  label.addEventListener('animationend', ended);
  label.addEventListener('animationcancel', ended);
  var style = getComputedStyle(label);
  var milliseconds = function milliseconds(value) {
    return (parseFloat(value) || 0) * (value.trim().endsWith('ms') ? 1 : 1000);
  };
  var duration = Math.max.apply(Math, _toConsumableArray(style.animationDuration.split(',').map(milliseconds)));
  var delay = Math.max.apply(Math, _toConsumableArray(style.animationDelay.split(',').map(milliseconds)));
  timer = setTimeout(cleanup, duration + delay + 100);
  active.set(label, cleanup);
  return cleanup;
}

/***/ },

/***/ "./resources/js/games/duel/dialog.js"
/*!*******************************************!*\
  !*** ./resources/js/games/duel/dialog.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   closeDialog: () => (/* binding */ closeDialog),
/* harmony export */   dialog: () => (/* binding */ dialog)
/* harmony export */ });
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
var phases = {
  connecting: {
    label: 'Finding the beat',
    title: 'Connecting your duel…',
    description: 'We’re linking you with your opponent. Your game will open here.',
    icon: 'link'
  },
  ready: {
    label: 'Opponent connected',
    title: 'Ready to face the music?',
    description: 'Tap “I’m ready” below. When you’re both ready, a countdown starts the same game for both of you.',
    icon: 'bolt'
  },
  'waiting-ready': {
    label: 'You’re ready',
    title: 'One musician to go…',
    description: 'Your opponent still needs to tap “I’m ready”. Stay here — the countdown will start automatically.',
    icon: 'circle-check'
  },
  countdown: {
    label: 'Both players ready',
    title: 'Get ready to play!',
    description: 'Same challenge. Same starting line. Let’s see what you’ve got!',
    icon: 'music'
  },
  go: {
    label: 'The duel is on',
    title: 'Let’s play!',
    description: 'Make every note count.',
    icon: 'bolt'
  },
  'opponent-left': {
    label: 'Duel ended',
    title: 'Your opponent left',
    description: 'They’ve left this match, so the duel has ended. Head back to the games to play solo or challenge someone else.',
    icon: 'door-open'
  },
  'you-left': {
    label: 'Duel ended',
    title: 'You’ve left the duel',
    description: 'This match has ended. Choose another game whenever you’re ready.',
    icon: 'door-open'
  },
  expired: {
    label: 'Time’s up',
    title: 'This duel has expired',
    description: 'This room is no longer available. Head back to the games to start a fresh duel and share a new code.',
    icon: 'hourglass-end'
  },
  cancelled: {
    label: 'Duel ended',
    title: 'This duel was cancelled',
    description: 'This match is no longer active. You can start another duel or enjoy a solo game.',
    icon: 'flag-checkered'
  },
  idle: {
    label: 'Quick check-in',
    title: 'Still with us?',
    description: 'Keep the music going! Confirm before the timer reaches zero, or you’ll leave this duel and return to the games.',
    icon: 'hand'
  },
  lobby: {
    label: 'Your stage is set',
    title: 'Invite your opponent',
    description: 'Share this four-digit code. Your friend can choose “Join a Duel” and enter it. We’ll open the game as soon as they join.',
    icon: 'user-group'
  },
  joined: {
    label: 'Opponent connected',
    title: 'You’ve got a challenger!',
    description: 'Opening your game… Next, you’ll both choose when you’re ready.',
    icon: 'bolt'
  },
  join: {
    label: 'Challenge accepted',
    title: 'Join your friend’s duel',
    description: 'Enter the four-digit code they shared with you. You’ll play their game with the same settings.',
    icon: 'gamepad'
  },
  error: {
    label: 'Let’s get you back',
    title: 'A little out of tune',
    description: 'Try again, or head back to the games for a fresh start.',
    icon: 'arrows-rotate'
  }
};
function dialog() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
    _ref$message = _ref.message,
    message = _ref$message === void 0 ? '' : _ref$message,
    _ref$phase = _ref.phase,
    phase = _ref$phase === void 0 ? '' : _ref$phase,
    _ref$opponentReady = _ref.opponentReady,
    opponentReady = _ref$opponentReady === void 0 ? false : _ref$opponentReady,
    _ref$code = _ref.code,
    code = _ref$code === void 0 ? '' : _ref$code,
    _ref$join = _ref.join,
    join = _ref$join === void 0 ? false : _ref$join,
    _ref$ready = _ref.ready,
    ready = _ref$ready === void 0 ? false : _ref$ready,
    _ref$cancel = _ref.cancel,
    cancel = _ref$cancel === void 0 ? false : _ref$cancel,
    _ref$leave = _ref.leave,
    leave = _ref$leave === void 0 ? false : _ref$leave,
    _ref$exit = _ref.exit,
    exit = _ref$exit === void 0 ? false : _ref$exit,
    _ref$idle = _ref.idle,
    idle = _ref$idle === void 0 ? false : _ref$idle,
    _ref$seconds = _ref.seconds,
    seconds = _ref$seconds === void 0 ? 10 : _ref$seconds,
    _ref$error = _ref.error,
    error = _ref$error === void 0 ? '' : _ref$error;
  var root = document.getElementById('duel-modal');
  phase || (phase = join ? 'join' : idle ? 'idle' : code ? 'lobby' : error ? 'error' : 'connecting');
  var copy = phases[phase] || {
    label: 'Multiplayer duel',
    title: message,
    description: '',
    icon: 'music'
  };
  root.dataset.duelPhase = phase;
  root.querySelector('[data-duel-label]').textContent = copy.label;
  root.querySelector('[data-duel-message]').textContent = copy.title;
  var description = phase === 'error' && message ? message : copy.description;
  root.querySelector('[data-duel-description]').textContent = description;
  root.querySelector('[data-duel-description]').hidden = !description;
  root.querySelector('[data-duel-symbol]').className = "fa-solid fa-".concat(copy.icon);
  root.querySelector('[data-duel-code]').textContent = code;
  root.querySelector('[data-duel-code]').hidden = !code;
  var participants = root.querySelector('[data-duel-participants]');
  participants.hidden = !['ready', 'waiting-ready', 'countdown'].includes(phase);
  for (var _i = 0, _arr = [['you', phase !== 'ready'], ['opponent', opponentReady || phase === 'countdown']]; _i < _arr.length; _i++) {
    var _arr$_i = _slicedToArray(_arr[_i], 2),
      role = _arr$_i[0],
      isReady = _arr$_i[1];
    var card = root.querySelector("[data-duel-participant=\"".concat(role, "\"]"));
    card.dataset.ready = String(isReady);
    card.querySelector('[data-duel-participant-status]').textContent = isReady ? 'Ready ✓' : 'Getting ready';
  }
  var countdown = root.querySelector('[data-duel-countdown]');
  countdown.hidden = phase !== 'countdown';
  var value = root.querySelector('[data-duel-countdown-value]');
  if (value.textContent !== message) value.textContent = message;
  root.querySelector('[data-duel-join-form]').hidden = !join;
  root.querySelector('[data-duel-ready]').hidden = !ready;
  root.querySelector('[data-duel-cancel]').hidden = !cancel;
  root.querySelector('[data-duel-leave]').hidden = !leave;
  root.querySelector('[data-duel-exit]').hidden = !exit;
  root.querySelector('[data-duel-idle]').hidden = !idle;
  root.querySelector('[data-duel-idle-count]').textContent = String(seconds);
  root.querySelector('[data-duel-active]').hidden = !idle;
  root.querySelector('[data-duel-actions]').hidden = !(ready || cancel || leave || exit || idle);
  var errorElement = root.querySelector('[data-duel-error]');
  errorElement.hidden = !error;
  $(errorElement).text(error).toggle(Boolean(error));
  root.querySelector('.btn-close').hidden = !join;
  // Escape, backdrop clicks and dismiss controls must not strand an ended game.
  $(root).off('hide.bs.modal.duelExit').on('hide.bs.modal.duelExit', function (event) {
    if (['opponent-left', 'you-left', 'cancelled', 'expired'].includes(phase) && !root.dataset.duelClosing) {
      event.preventDefault();
    }
  });
  $(root).modal('show');
  return root;
}
function closeDialog() {
  var root = document.getElementById('duel-modal');
  root.dataset.duelClosing = 'true';
  try {
    $(root).modal('hide');
  } finally {
    delete root.dataset.duelClosing;
  }
}

/***/ },

/***/ "./resources/js/games/duel/idle.js"
/*!*****************************************!*\
  !*** ./resources/js/games/duel/idle.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DuelIdle: () => (/* binding */ DuelIdle)
/* harmony export */ });
/* harmony import */ var _dialog__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./dialog */ "./resources/js/games/duel/dialog.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var activityEvents = ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart'];
var DuelIdle = /*#__PURE__*/function () {
  function DuelIdle(_ref) {
    var _this = this;
    var onExpire = _ref.onExpire,
      onResume = _ref.onResume;
    _classCallCheck(this, DuelIdle);
    this.onExpire = onExpire;
    this.onResume = onResume;
    this.lastActivity = Date.now();
    this.warning = false;
    this.stopped = false;
    this.seconds = null;
    this.activity = function (event) {
      if (!event.isTrusted || _this.stopped) return;
      _this.check();
      if (_this.warning && event.type === 'keydown' && String(event.key).startsWith('Arrow')) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      // Only the explicit confirmation dismisses an already visible warning.
      if (!_this.warning && !_this.stopped) _this.lastActivity = Date.now();
    };
    this.confirm = function () {
      _this.check();
      if (!_this.warning || _this.stopped) return;
      _this.lastActivity = Date.now();
      _this.warning = false;
      _this.seconds = null;
      _this.onResume();
    };
    this.checkVisible = function () {
      return _this.check();
    };
  }
  return _createClass(DuelIdle, [{
    key: "start",
    value: function start() {
      var _this2 = this;
      this.stopped = false;
      this.lastActivity = Date.now();
      this.seconds = null;
      for (var _i = 0, _activityEvents = activityEvents; _i < _activityEvents.length; _i++) {
        var event = _activityEvents[_i];
        document.addEventListener(event, this.activity, {
          capture: true,
          passive: event !== 'keydown'
        });
      }
      document.addEventListener('visibilitychange', this.checkVisible);
      this.button = document.querySelector('[data-duel-active]');
      this.button.addEventListener('click', this.confirm);
      this.timer = setInterval(function () {
        return _this2.check();
      }, 250);
      return this;
    }
  }, {
    key: "check",
    value: function check() {
      if (this.stopped) return;
      var idle = Date.now() - this.lastActivity;
      if (idle >= 40000) {
        this.stop();
        this.onExpire();
        return;
      }
      if (idle < 30000) return;
      var seconds = Math.ceil((40000 - idle) / 1000);
      if (seconds === this.seconds) return;
      var first = !this.warning;
      this.warning = true;
      this.seconds = seconds;
      (0,_dialog__WEBPACK_IMPORTED_MODULE_0__.dialog)({
        message: 'Still playing?',
        idle: true,
        seconds: seconds
      });
      if (first) this.button.focus();
    }
  }, {
    key: "stop",
    value: function stop() {
      var _this$button;
      this.stopped = true;
      this.warning = false;
      clearInterval(this.timer);
      for (var _i2 = 0, _activityEvents2 = activityEvents; _i2 < _activityEvents2.length; _i2++) {
        var event = _activityEvents2[_i2];
        document.removeEventListener(event, this.activity, true);
      }
      document.removeEventListener('visibilitychange', this.checkVisible);
      (_this$button = this.button) === null || _this$button === void 0 || _this$button.removeEventListener('click', this.confirm);
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/duel/presence.js"
/*!*********************************************!*\
  !*** ./resources/js/games/duel/presence.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DuelPresence: () => (/* binding */ DuelPresence)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var ended = function ended(state) {
  return ['cancelled', 'expired', 'finished'].includes(state.status);
};
var DuelPresence = /*#__PURE__*/function () {
  function DuelPresence(transport, id, onState, onError) {
    var _this = this;
    _classCallCheck(this, DuelPresence);
    this.transport = transport;
    this.id = id;
    this.onState = onState;
    this.onError = onError;
    this.stopped = false;
    this.hidden = false;
    this.claimed = false;
    this.onHide = function () {
      return _this.depart();
    };
    this.onShow = function (event) {
      if (event.persisted && !_this.stopped) return _this.claim();
    };
  }
  return _createClass(DuelPresence, [{
    key: "start",
    value: function start() {
      window.addEventListener('pagehide', this.onHide);
      window.addEventListener('pageshow', this.onShow);
      return this.claim();
    }
  }, {
    key: "claim",
    value: function () {
      var _claim = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this2 = this;
        var bytes, state, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              bytes = window.crypto.getRandomValues(new Uint8Array(16));
              this.connectionId = Array.from(bytes, function (_byte) {
                return _byte.toString(16).padStart(2, '0');
              }).join('');
              this.hidden = false;
              this.claimed = false;
              clearInterval(this.timer);
              _context.p = 1;
              _context.n = 2;
              return this.transport.request("/".concat(this.id, "/connect"), {
                connection_id: this.connectionId
              });
            case 2:
              state = _context.v;
              this.claimed = true;
              this.accept(state);
              _context.n = 4;
              break;
            case 3:
              _context.p = 3;
              _t = _context.v;
              if (!this.stopped && !this.hidden) this.onError(_t);
            case 4:
              if (!this.stopped && !this.hidden) this.timer = setInterval(function () {
                return _this2.heartbeat();
              }, 15000);
            case 5:
              return _context.a(2);
          }
        }, _callee, this, [[1, 3]]);
      }));
      function claim() {
        return _claim.apply(this, arguments);
      }
      return claim;
    }()
  }, {
    key: "heartbeat",
    value: function () {
      var _heartbeat = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var _t2, _t3;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.p = _context2.n) {
            case 0:
              if (!(this.stopped || this.hidden)) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2);
            case 1:
              if (this.claimed) {
                _context2.n = 2;
                break;
              }
              return _context2.a(2, this.claim());
            case 2:
              _context2.p = 2;
              _t2 = this;
              _context2.n = 3;
              return this.transport.request("/".concat(this.id, "/heartbeat"), {
                connection_id: this.connectionId
              });
            case 3:
              _t2.accept.call(_t2, _context2.v);
              _context2.n = 5;
              break;
            case 4:
              _context2.p = 4;
              _t3 = _context2.v;
              if (!this.stopped && !this.hidden) this.onError(_t3);
            case 5:
              return _context2.a(2);
          }
        }, _callee2, this, [[2, 4]]);
      }));
      function heartbeat() {
        return _heartbeat.apply(this, arguments);
      }
      return heartbeat;
    }()
  }, {
    key: "accept",
    value: function accept(state) {
      if (this.stopped || this.hidden) return;
      this.onState(state);
      if (ended(state)) this.stop();
    }
  }, {
    key: "depart",
    value: function depart() {
      if (this.stopped || this.hidden) return;
      this.hidden = true;
      clearInterval(this.timer);
      // Keep the anonymous session and CSRF protections on unload requests too.
      var body = new FormData();
      body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
      body.append('connection_id', this.connectionId);
      var url = "".concat(this.transport.config.base, "/").concat(this.id, "/depart");
      try {
        var _navigator$sendBeacon, _navigator;
        if ((_navigator$sendBeacon = (_navigator = navigator).sendBeacon) !== null && _navigator$sendBeacon !== void 0 && _navigator$sendBeacon.call(_navigator, url, body)) return;
      } catch (_) {}
      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
        keepalive: true
      })["catch"](function () {});
    }
  }, {
    key: "stop",
    value: function stop() {
      this.stopped = true;
      clearInterval(this.timer);
      window.removeEventListener('pagehide', this.onHide);
      window.removeEventListener('pageshow', this.onShow);
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/duel/random.js"
/*!*******************************************!*\
  !*** ./resources/js/games/duel/random.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   seededRandom: () => (/* binding */ seededRandom)
/* harmony export */ });
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
// Local challenge randomness. Cosmetic animation/audio randomness stays independent.
function seededRandom(seed) {
  var round = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var state = 2166136261;
  var _iterator = _createForOfIteratorHelper("".concat(seed, ":").concat(round)),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var _char = _step.value;
      state = Math.imul(state ^ _char.charCodeAt(0), 16777619);
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  return function () {
    state += 0x6D2B79F5;
    var value = Math.imul(state ^ state >>> 15, 1 | state);
    value ^= value + Math.imul(value ^ value >>> 7, 61 | value);
    return ((value ^ value >>> 14) >>> 0) / 4294967296;
  };
}

/***/ },

/***/ "./resources/js/games/duel/results.js"
/*!********************************************!*\
  !*** ./resources/js/games/duel/results.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   duelDuration: () => (/* binding */ duelDuration),
/* harmony export */   duelOutcome: () => (/* binding */ duelOutcome),
/* harmony export */   renderDuelResults: () => (/* binding */ renderDuelResults)
/* harmony export */ });
/* harmony import */ var _games_shared_resultVariants__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../games/shared/resultVariants */ "./resources/js/games/games/shared/resultVariants.js");
/* harmony import */ var _games_shared_GameAudio__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../games/shared/GameAudio */ "./resources/js/games/games/shared/GameAudio.js");
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }



// Both browsers use the same saved results. Finishing first never decides the winner.
function duelOutcome(state) {
  if (state.status !== 'finished' || state.players.length !== 2 || state.players.some(function (player) {
    return !player.finished_at || !player.result;
  })) {
    return {
      kind: 'waiting',
      winner: null,
      reason: null
    };
  }
  var _state$players = _slicedToArray(state.players, 2),
    a = _state$players[0],
    b = _state$players[1];
  var points = a.score - b.score;
  var accuracy = a.result.accuracy - b.result.accuracy;
  var difference = points || accuracy;
  var winner = difference > 0 ? a.role : difference < 0 ? b.role : null;
  return {
    kind: winner ? winner === state.role ? 'win' : 'loss' : 'draw',
    winner: winner,
    reason: points ? 'points' : accuracy ? 'accuracy' : 'draw'
  };
}
function duelDuration(startsAt, finishedAt) {
  if (!startsAt || !finishedAt) return '—';
  var seconds = Math.max(0, Math.floor((Date.parse(finishedAt) - Date.parse(startsAt)) / 1000));
  if (!Number.isFinite(seconds)) return '—';
  return "".concat(String(Math.floor(seconds / 60)).padStart(2, '0'), ":").concat(String(seconds % 60).padStart(2, '0'));
}
var greetings = {
  win: {
    tier: 'excellent',
    label: 'Duel champion',
    title: 'You won!!!',
    message: 'YES! You did it! Take a bow — that victory is yours! ✨'
  },
  loss: {
    tier: 'encouraging',
    label: 'Opponent won this round',
    title: 'Your comeback starts here!',
    message: 'This one went to your opponent. Keep playing, keep learning — your next win is waiting.'
  },
  draw: {
    tier: 'strong',
    label: 'A shared victory',
    title: 'It’s a draw!',
    message: 'Same points. Same accuracy. Two musicians, one brilliant match!'
  },
  waiting: {
    tier: 'strong',
    label: 'Your part is done ✓',
    title: 'Beautiful finish!',
    message: 'Your opponent is still playing. Stay here for the final results!'
  }
};
function renderDuelResults(root, state) {
  if (!root) return;
  var outcome = duelOutcome(state);
  var greeting = greetings[outcome.kind];
  var changed = root.dataset.outcome !== outcome.kind;
  // The game wrapper is inert after finishing. Mount outside it so result actions work.
  if (root.parentElement !== document.body) document.body.append(root);
  root.hidden = false;
  document.body.classList.add('duel-results-open');
  var text = function text(selector, value) {
    root.querySelector(selector).textContent = value;
  };
  if (changed) {
    var _state$options, _window$matchMedia, _window;
    if (root.dataset.outcome === 'waiting' && outcome.kind !== 'waiting' && ((_state$options = state.options) === null || _state$options === void 0 ? void 0 : _state$options.sound) !== false) {
      try {
        _games_shared_GameAudio__WEBPACK_IMPORTED_MODULE_1__.GameAudio.playFinalResults();
      } catch (_) {}
    }
    root.dataset.outcome = outcome.kind;
    root.dataset.resultTier = greeting.tier;
    root.dataset.resultVariant = (0,_games_shared_resultVariants__WEBPACK_IMPORTED_MODULE_0__.chooseResultVariant)(greeting.tier);
    text('[data-duel-outcome]', greeting.label);
    text('[data-duel-result-title]', greeting.title);
    text('[data-duel-result-message]', greeting.message);
    root.querySelector('[data-duel-result-title]').focus({
      preventScroll: true
    });
    root.scrollTop = 0;
    var reducedMotion = (_window$matchMedia = (_window = window).matchMedia) === null || _window$matchMedia === void 0 ? void 0 : _window$matchMedia.call(_window, '(prefers-reduced-motion: reduce)').matches;
    var confetti = window.Confetti || window.confetti;
    if (outcome.kind === 'win' && !reducedMotion && typeof confetti === 'function') {
      confetti({
        particleCount: 150,
        spread: 85,
        origin: {
          y: .6
        },
        zIndex: 1001,
        colors: ['#ffe54c', '#55b9ac', '#b18ce8', '#f49236']
      });
    }
  }
  var you = state.players.find(function (player) {
    return player.role === state.role;
  });
  text('span[name="score"]', you.score);
  root.style.setProperty('--result-score-digits', String(Math.max(3, String(you.score).length)));
  var _iterator = _createForOfIteratorHelper(state.players),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var player = _step.value;
      var row = player.role === state.role ? 'you' : 'opponent';
      for (var _i = 0, _Object$entries = Object.entries({
          score: player.score,
          accuracy: player.result ? "".concat(player.result.accuracy, "%") : 'Still playing…',
          rounds: "".concat(player.progress, " / ").concat(state.total),
          time: duelDuration(state.starts_at, player.finished_at)
        }); _i < _Object$entries.length; _i++) {
        var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
          metric = _Object$entries$_i[0],
          value = _Object$entries$_i[1];
        text("[data-duel-metric=\"".concat(row, "-").concat(metric, "\"]"), value);
      }
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  var waiting = outcome.kind === 'waiting';
  text('[data-duel-result-rule]', waiting ? 'The winner is revealed when you both finish.' : outcome.reason === 'accuracy' ? 'Points tied — higher accuracy wins.' : outcome.reason === 'draw' ? 'Equal points and accuracy. You share the honors!' : 'Highest final score wins. Accuracy breaks a tie.');
  root.querySelector('[data-duel-result-home]').hidden = waiting;
  root.querySelector('[data-duel-result-leave]').hidden = !waiting;
  root.querySelector('[data-duel-result-rematch]').hidden = waiting;
  root.querySelector('[data-duel-rematch]').disabled = !!you.rematch;
  text('[data-duel-rematch-label]', you.rematch ? 'Waiting for opponent…' : state.players.some(function (player) {
    return player.role !== state.role && player.rematch;
  }) ? 'Opponent wants to play again · Play again' : 'Play again');
}

/***/ },

/***/ "./resources/js/games/duel/transport.js"
/*!**********************************************!*\
  !*** ./resources/js/games/duel/transport.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DuelTransport: () => (/* binding */ DuelTransport)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
// PianoLIT's Laravel 8 runtime uses authenticated HTTP snapshots. Game engines,
// lobby, readiness, countdown and results share the original transport contract.
var DuelTransport = /*#__PURE__*/function () {
  function DuelTransport() {
    var _this = this;
    var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : window.__duelConfig;
    _classCallCheck(this, DuelTransport);
    this.config = config;
    this.offset = 0;
    this.subscription = 0;
    this.echo = {
      disconnect: function disconnect() {
        return _this.disconnect();
      }
    };
  }
  return _createClass(DuelTransport, [{
    key: "request",
    value: function () {
      var _request = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var path,
          data,
          signal,
          began,
          response,
          payload,
          error,
          _args = arguments;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              path = _args.length > 0 && _args[0] !== undefined ? _args[0] : '';
              data = _args.length > 1 && _args[1] !== undefined ? _args[1] : null;
              signal = _args.length > 2 && _args[2] !== undefined ? _args[2] : undefined;
              began = Date.now();
              _context.n = 1;
              return fetch("".concat(this.config.base).concat(path), _objectSpread({
                method: data === null ? 'GET' : 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                signal: signal,
                headers: {
                  Accept: 'application/json',
                  'Content-Type': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
              }, data === null ? {} : {
                body: JSON.stringify(data)
              }));
            case 1:
              response = _context.v;
              _context.n = 2;
              return response.json()["catch"](function () {
                return {};
              });
            case 2:
              payload = _context.v;
              if (response.ok) {
                _context.n = 3;
                break;
              }
              error = new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Could not reach the Duel server. Please try again.');
              error.status = response.status;
              throw error;
            case 3:
              if (payload.server_now) this.offset = Date.parse(payload.server_now) - (began + Date.now()) / 2;
              return _context.a(2, payload);
          }
        }, _callee, this);
      }));
      function request() {
        return _request.apply(this, arguments);
      }
      return request;
    }()
  }, {
    key: "leaveOnExit",
    value: function leaveOnExit(id) {
      var body = new FormData();
      body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
      var url = "".concat(this.config.base, "/").concat(id, "/leave");
      try {
        var _navigator$sendBeacon, _navigator;
        if ((_navigator$sendBeacon = (_navigator = navigator).sendBeacon) !== null && _navigator$sendBeacon !== void 0 && _navigator$sendBeacon.call(_navigator, url, body)) return;
      } catch (_) {}
      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        body: body,
        keepalive: true
      })["catch"](function () {});
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      var _this$controller;
      this.subscription++;
      clearTimeout(this.timer);
      (_this$controller = this.controller) === null || _this$controller === void 0 || _this$controller.abort();
      this.controller = null;
    }
  }, {
    key: "subscribe",
    value: function subscribe(id, _ref) {
      var _this2 = this;
      var update = _ref.update,
        answer = _ref.answer,
        connected = _ref.connected,
        disconnected = _ref.disconnected,
        error = _ref.error;
      this.disconnect();
      var subscription = this.subscription;
      var seenAnswers = new Set();
      var online = false;
      var failures = 0;
      var _poll = /*#__PURE__*/function () {
        var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
          var controller, timeout, state, _iterator, _step, player, event, recent, _t;
          return _regenerator().w(function (_context2) {
            while (1) switch (_context2.p = _context2.n) {
              case 0:
                if (!(subscription !== _this2.subscription)) {
                  _context2.n = 1;
                  break;
                }
                return _context2.a(2);
              case 1:
                controller = new AbortController();
                _this2.controller = controller;
                timeout = setTimeout(function () {
                  return controller.abort();
                }, 10000);
                _context2.p = 2;
                _context2.n = 3;
                return _this2.request("/".concat(id), null, controller.signal);
              case 3:
                state = _context2.v;
                if (!(subscription !== _this2.subscription)) {
                  _context2.n = 4;
                  break;
                }
                return _context2.a(2);
              case 4:
                if (!online) {
                  online = true;
                  connected === null || connected === void 0 || connected();
                }
                failures = 0;
                update === null || update === void 0 || update(state);
                _iterator = _createForOfIteratorHelper(state.players || []);
                try {
                  for (_iterator.s(); !(_step = _iterator.n()).done;) {
                    player = _step.value;
                    event = player.answer;
                    if (event && !seenAnswers.has(event.id)) {
                      seenAnswers.add(event.id);
                      answer === null || answer === void 0 || answer(event);
                    }
                  }
                  // Retain only recent event IDs; server feedback expires after three seconds.
                } catch (err) {
                  _iterator.e(err);
                } finally {
                  _iterator.f();
                }
                if (seenAnswers.size > 64) {
                  recent = _toConsumableArray(seenAnswers).slice(-32);
                  seenAnswers.clear();
                  recent.forEach(function (value) {
                    return seenAnswers.add(value);
                  });
                }
                _context2.n = 8;
                break;
              case 5:
                _context2.p = 5;
                _t = _context2.v;
                if (!(subscription !== _this2.subscription)) {
                  _context2.n = 6;
                  break;
                }
                return _context2.a(2);
              case 6:
                if (online) {
                  online = false;
                  disconnected === null || disconnected === void 0 || disconnected();
                }
                failures++;
                if (![403, 404, 419].includes(_t.status)) {
                  _context2.n = 7;
                  break;
                }
                error === null || error === void 0 || error(_t);
                _this2.disconnect();
                return _context2.a(2);
              case 7:
                if (failures === 3) error === null || error === void 0 || error(_t);
              case 8:
                _context2.p = 8;
                clearTimeout(timeout);
                if (subscription === _this2.subscription) {
                  _this2.timer = setTimeout(_poll, failures ? Math.min(5000, failures * 1000) : _this2.config.poll_ms || 800);
                }
                return _context2.f(8);
              case 9:
                return _context2.a(2);
            }
          }, _callee2, null, [[2, 5, 8, 9]]);
        }));
        return function poll() {
          return _ref2.apply(this, arguments);
        };
      }();
      _poll();
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/games/base/BaseStaffGame.js"
/*!********************************************************!*\
  !*** ./resources/js/games/games/base/BaseStaffGame.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BaseStaffGame: () => (/* binding */ BaseStaffGame),
/* harmony export */   PAGE_OPENED_AT_MS: () => (/* binding */ PAGE_OPENED_AT_MS)
/* harmony export */ });
/* harmony import */ var _staff_Staff_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../staff/Staff.js */ "./resources/js/games/staff/Staff.js");
/* harmony import */ var _staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../staff/staffUtils.js */ "./resources/js/games/staff/staffUtils.js");
/* harmony import */ var _shared_finalResults_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../shared/finalResults.js */ "./resources/js/games/games/shared/finalResults.js");
/* harmony import */ var _shared_PromptUi_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../shared/PromptUi.js */ "./resources/js/games/games/shared/PromptUi.js");
/* harmony import */ var _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../shared/GameAudio.js */ "./resources/js/games/games/shared/GameAudio.js");
/* harmony import */ var _shared_PianoKeyboardUi_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../shared/PianoKeyboardUi.js */ "./resources/js/games/games/shared/PianoKeyboardUi.js");
/* harmony import */ var _shared_InstructionsUi_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../shared/InstructionsUi.js */ "./resources/js/games/games/shared/InstructionsUi.js");
/* harmony import */ var _shared_noteNames_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../shared/noteNames.js */ "./resources/js/games/games/shared/noteNames.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
// resources/js/music/games/base/BaseStaffGame.js








var PAGE_OPENED_AT_MS = Date.now();

/**
 * BaseStaffGame
 * Shared runtime for staff-based games:
 * - Staff wiring (notes, accidentals, noteState events)
 * - Controls wiring (clear/check/help/continue)
 * - Scoring, progress, final overlay (CountUp), 2x perfect-game bonus
 * - UI SFX (Tone.js) + success/fail/final/perfect-game sounds
 *
 * Subclasses must implement:
 * - newChallenge()
 * - _onCheck()
 * Optionally override:
 * - _computeHintAnswer()
 * - _wireAccidentalPalette()
 * - _onFixedNoteState()
 */
var BaseStaffGame = /*#__PURE__*/function () {
  function BaseStaffGame() {
    var _this = this,
      _this$$doublePoints,
      _this$$doublePoints$h;
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, BaseStaffGame);
    var defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      sound: true,
      showNoteNames: false,
      clefUrls: null,
      initialClef: "treble",
      maxUserNotes: Infinity,
      numOfChallenges: 10,
      practiceMode: false,
      timer: false,
      levelName: "",
      successPhrases: ["Awesome", "Nicely done", "Well done", "Great job", "Hooray", "Fantastic", "Nice work", "Looks good", "Good one", "Splendid", "Way to go", "Nailed it", "Brilliant", "Excellent", "Superb", "Right on", "You got it", "Perfect", "Spot on", "Impressive", "Top notch", "That’s it"],
      // namespace for jQuery event handlers (avoid collisions if multiple games exist)
      namespace: "staffGame",
      // UI gating: how many USER notes before removing instructions / enabling Check
      instructionsAfterUserNotes: 1,
      checkAfterUserNotes: 1
    };
    this.opts = _objectSpread(_objectSpread({}, defaults), options || {});
    this.ns = this.opts.namespace || "staffGame";
    this.opts.numOfChallenges = this._normalizeNumOfChallenges(this.opts.numOfChallenges);

    // UI SFX
    this._uiSfxReady = false;
    this._uiSfxSynth = null;
    this._uiSfxNoise = null;
    this._uiTimerSfxSynth = null;

    // DOM refs
    this.$staffEl = $(this.opts.staffEl);
    this.$accidentals = $("#accidentals");
    this.$controls = $("#controls");
    this.$instructions = $("#instructions");
    this.$feedback = $("#feedback-success");
    this.$bonusBadge = this.$feedback.find(".bonus");
    this.$points = $("#points");
    this.$increment = $("#increment");
    this.$checkBtn = $("#check button");
    this.$skipWrap = $("#skip");
    this.$skipBtn = this.$skipWrap.find("button");
    this.$finalOverlay = $("#final-overlay");
    this.$doublePoints = $("#double-points");
    this.$checkWrap = this.$checkBtn.parent();
    this.$progressBar = $("#progress-bar");
    this.$progressCounter = $("#progress-counter");
    this.$helpBtn = $("#help");
    this.$timeupMessage = $("#timeup-message");
    this.$handPointer = $("#hand-pointer");
    this.$timer = $("#timer");
    this.$timerBox = this.$timer.children("div").first();
    this.$timerText = this.$timer.find("span");
    this.prompt = new _shared_PromptUi_js__WEBPACK_IMPORTED_MODULE_3__.PromptUi("#prompt");
    this.instructionsUi = new _shared_InstructionsUi_js__WEBPACK_IMPORTED_MODULE_6__.InstructionsUi("#instructions");
    this.$keyboard = $("#keyboard").first();
    this.$keyboardWrap = $("#keyboard-wrapper").first();
    this.$pianoToggleBtn = $("#piano-toggle button").first();

    // Game state
    this.successPhrases = this.opts.successPhrases;
    this.maxUserNotes = Number(this.opts.maxUserNotes);
    this.numOfChallenges = this.opts.numOfChallenges;
    this.levelName = this.opts.levelName;
    this.points = 0;
    this._madeMistakeThisRound = false;
    this._madeAnyMistake = false;
    this._continueBound = false;
    this._usedHintThisRound = false;
    this._correctStreak = 0;
    this._stats = {
      checksTotal: 0,
      checksCorrect: 0,
      finishedAtMs: null
    };
    this._timerTimeoutId = null;
    this._timerRemainingSec = 0;
    this._audioUnlockArmed = false;
    this._timerEndsAtMs = 0;
    this._finalMetricsSfxTimeouts = [];
    this._finalCountupTimeouts = [];
    this._checkWrongStateTimeout = null;
    this._keyboardSyncPatched = false;
    this._keyboardAccidentalPreviewPatched = false;
    this.keyboard = this._shouldUsePianoKeyboard() ? new _shared_PianoKeyboardUi_js__WEBPACK_IMPORTED_MODULE_5__.PianoKeyboardUi({
      rootSelector: "#keyboard",
      namespace: "".concat(this.ns, ".keyboard"),
      onKeyClick: function onKeyClick(data) {
        var _this$_onPianoKeyboar;
        return (_this$_onPianoKeyboar = _this._onPianoKeyboardKeyClick) === null || _this$_onPianoKeyboar === void 0 ? void 0 : _this$_onPianoKeyboar.call(_this, data);
      },
      canPlayNote: function canPlayNote() {
        var _this$staff, _this$staff$isSoundEn;
        return (_this$staff = _this.staff) === null || _this$staff === void 0 || (_this$staff$isSoundEn = _this$staff.isSoundEnabled) === null || _this$staff$isSoundEn === void 0 ? void 0 : _this$staff$isSoundEn.call(_this$staff);
      }
    }) : null;

    // Staff
    this.staff = new _staff_Staff_js__WEBPACK_IMPORTED_MODULE_0__.Staff(this.$staffEl, {
      clef: this.opts.initialClef || "treble",
      clefUrls: this.opts.clefUrls || window.__clefUrls,
      autoClef: false,
      getMaxUserNotes: function getMaxUserNotes() {
        return Number.isFinite(_this.maxUserNotes) ? _this.maxUserNotes : Infinity;
      },
      sound: !!this.opts.sound,
      showLineNames: this._normalizeOnOff(this.opts.showLineNames),
      formatLineName: function formatLineName(letter) {
        return _this._toDisplayNoteName(letter);
      }
    });
    this.showNoteNames = !!this.opts.showNoteNames;
    this.$staffEl.toggleClass("show-letternames", this.showNoteNames);

    // Fixed note state (for hints)
    this._fixedNote = {
      letterWithAcc: "?",
      letterOnly: "?"
    };
    this._fixedState = null;

    // init UI
    this.$increment.hide();
    this.$bonusBadge.hide();
    (_this$$doublePoints = this.$doublePoints) === null || _this$$doublePoints === void 0 || (_this$$doublePoints$h = _this$$doublePoints.hide) === null || _this$$doublePoints$h === void 0 || _this$$doublePoints$h.call(_this$$doublePoints);
    this.$points.text(String(this.points));
  }
  return _createClass(BaseStaffGame, [{
    key: "_normalizeOnOff",
    value: function _normalizeOnOff(value) {
      if (value === true) return true;
      if (value === false) return false;
      var s = String(value !== null && value !== void 0 ? value : "").trim().toLowerCase();
      return s === "on" || s === "true" || s === "1";
    }
  }, {
    key: "_showSolfegeNoteNames",
    value: function _showSolfegeNoteNames() {
      return this._normalizeOnOff(this.opts.solfege);
    }
  }, {
    key: "_shouldUsePianoKeyboard",
    value: function _shouldUsePianoKeyboard() {
      var _this$$keyboard;
      if (this.opts.usePianoKeyboard === false) return false;
      if (this._normalizeOnOff(this.opts.usePianoKeyboard)) return true;
      return !!((_this$$keyboard = this.$keyboard) !== null && _this$$keyboard !== void 0 && _this$$keyboard.length);
    }
  }, {
    key: "_keyboardStartNoteForClef",
    value: function _keyboardStartNoteForClef(clef) {
      var cleanClef = String(clef || "").trim().toLowerCase();
      if (cleanClef === "bass") return "C3";
      if (cleanClef === "alto") return "C3";
      if (cleanClef === "tenor") return "C3";
      return "C4";
    }
  }, {
    key: "_syncPianoKeyboardStartNote",
    value: function _syncPianoKeyboardStartNote() {
      if (!this.keyboard || !this.staff) return;
      this.keyboard.setStartNote(this._keyboardStartNoteForClef(this.staff.getClef()));
    }
  }, {
    key: "_collectKeyboardSyncNotes",
    value: function _collectKeyboardSyncNotes() {
      var _this2 = this;
      return this.$staffEl.find(".note").not(".preview").not(".hint").toArray().map(function (el) {
        var _this2$staff$_getAtta, _this2$staff;
        var $note = $(el);
        var noteId = String($note.attr("data-note-id") || "");
        if (!noteId) return null;
        var top = parseFloat($note.css("top"));
        var step = Number.isFinite(top) ? _this2.staff.yToStep(top) : null;
        var accidentalClass = ((_this2$staff$_getAtta = (_this2$staff = _this2.staff)._getAttachedAccidentalClass) === null || _this2$staff$_getAtta === void 0 ? void 0 : _this2$staff$_getAtta.call(_this2$staff, noteId)) || null;
        return {
          noteId: noteId,
          step: step,
          accidentalClass: accidentalClass,
          fixed: $note.hasClass("fixed")
        };
      }).filter(Boolean);
    }
  }, {
    key: "_collectUserStaffNotes",
    value: function _collectUserStaffNotes() {
      return this._collectKeyboardSyncNotes().filter(function (note) {
        return !note.fixed && Number.isFinite(note.step);
      });
    }
  }, {
    key: "_normalizedAccidentalClass",
    value: function _normalizedAccidentalClass(value) {
      return String(value || "");
    }
  }, {
    key: "_answerMatchesUserStaffNote",
    value: function _answerMatchesUserStaffNote(answer, note) {
      if (!answer || !note) return false;
      if (!Number.isFinite(answer.step) || !Number.isFinite(note.step)) return false;
      return Number(answer.step) === Number(note.step) && this._normalizedAccidentalClass(answer.accidentalClass) === this._normalizedAccidentalClass(note.accidentalClass);
    }
  }, {
    key: "_wrongUserStaffNotes",
    value: function _wrongUserStaffNotes() {
      var _this$_computeHintAns,
        _this3 = this;
      var notes = this._collectUserStaffNotes();
      if (!notes.length) return [];
      var answers = ((_this$_computeHintAns = this._computeHintAnswers) === null || _this$_computeHintAns === void 0 ? void 0 : _this$_computeHintAns.call(this)) || [];
      var remaining = Array.isArray(answers) ? answers.filter(function (answer) {
        return Number.isFinite(answer === null || answer === void 0 ? void 0 : answer.step);
      }) : [];
      if (!remaining.length) return notes;
      var wrong = [];
      notes.forEach(function (note) {
        var matchIndex = remaining.findIndex(function (answer) {
          return _this3._answerMatchesUserStaffNote(answer, note);
        });
        if (matchIndex >= 0) {
          remaining.splice(matchIndex, 1);
        } else {
          wrong.push(note);
        }
      });
      return wrong;
    }
  }, {
    key: "_shakeUserStaffNotes",
    value: function _shakeUserStaffNotes() {
      var _this4 = this;
      var notes = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      var list = Array.isArray(notes) ? notes : this._collectUserStaffNotes();
      if (!list.length) return;
      list.forEach(function (_ref) {
        var noteId = _ref.noteId;
        if (!noteId) return;
        var $targets = _this4.$staffEl.find(".note[data-note-id=\"".concat(noteId, "\"], .accidental[data-for-note-id=\"").concat(noteId, "\"], .ledger[data-for-note-id=\"").concat(noteId, "\"]"));
        if (!$targets.length) return;
        $targets.removeClass("animate__animated animate__shakeX");
        $targets.each(function (_, el) {
          // eslint-disable-next-line no-unused-expressions
          el.offsetWidth;
        });
        $targets.addClass("animate__animated animate__shakeX");
        $targets.off("animationend._wrongStaffNote.".concat(_this4.ns, " webkitAnimationEnd._wrongStaffNote.").concat(_this4.ns, " oAnimationEnd._wrongStaffNote.").concat(_this4.ns, " MSAnimationEnd._wrongStaffNote.").concat(_this4.ns)).one("animationend._wrongStaffNote.".concat(_this4.ns, " webkitAnimationEnd._wrongStaffNote.").concat(_this4.ns, " oAnimationEnd._wrongStaffNote.").concat(_this4.ns, " MSAnimationEnd._wrongStaffNote.").concat(_this4.ns), function () {
          $targets.removeClass("animate__animated animate__shakeX");
        });
      });
    }
  }, {
    key: "_shakeWrongUserStaffNotes",
    value: function _shakeWrongUserStaffNotes() {
      this._shakeUserStaffNotes(this._wrongUserStaffNotes());
    }
  }, {
    key: "_keyboardPrimaryNote",
    value: function _keyboardPrimaryNote(notes) {
      var list = Array.isArray(notes) ? notes : [];
      var userNotes = list.filter(function (note) {
        return !note.fixed;
      });
      if (userNotes.length) return userNotes[userNotes.length - 1];
      return list.length ? list[list.length - 1] : null;
    }
  }, {
    key: "_keyboardNoteNameForState",
    value: function _keyboardNoteNameForState(step, accidentalClass) {
      var _this$keyboard$_noteN, _this$keyboard;
      if (!Number.isFinite(step) || !this.keyboard) return "";
      var noteState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.stepToLetterOctave)(this.staff, step);
      var letter = String((noteState === null || noteState === void 0 ? void 0 : noteState.letter) || "").trim().toUpperCase();
      var octave = Number(noteState === null || noteState === void 0 ? void 0 : noteState.octave);
      if (!letter || !Number.isFinite(octave)) return "";
      var accidentalOffset = this.staff._accidentalClassToOffset(accidentalClass) || 0;
      var naturalPitchClass = {
        C: 0,
        D: 2,
        E: 4,
        F: 5,
        G: 7,
        A: 9,
        B: 11
      }[letter];
      if (!Number.isInteger(naturalPitchClass)) return "";
      var midi = (octave + 1) * 12 + naturalPitchClass + accidentalOffset;
      return ((_this$keyboard$_noteN = (_this$keyboard = this.keyboard)._noteNameFromMidi) === null || _this$keyboard$_noteN === void 0 ? void 0 : _this$keyboard$_noteN.call(_this$keyboard, midi)) || "";
    }
  }, {
    key: "_keyboardMarkerLabelForState",
    value: function _keyboardMarkerLabelForState(step, accidentalClass) {
      if (!this.showNoteNames) return "";
      var spelledNote = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.spellNoteTextFromState)(this.staff, step, accidentalClass);
      return this._toDisplayNoteName(spelledNote.replace(/-?\d+$/, ""));
    }
  }, {
    key: "_syncPianoKeyboardMarkerFromStaff",
    value: function _syncPianoKeyboardMarkerFromStaff() {
      var _this5 = this;
      if (!this.keyboard || !this.staff) return;
      var notes = this._collectKeyboardSyncNotes().filter(function (note) {
        return Number.isFinite(note.step);
      });
      var primary = this._keyboardPrimaryNote(notes);
      if (!primary) {
        this.keyboard.syncActiveKeys([]);
        return;
      }
      var primaryState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.stepToLetterOctave)(this.staff, primary.step);
      var keys = [this.keyboard.keyForNote(primaryState === null || primaryState === void 0 ? void 0 : primaryState.letter, primary.accidentalClass, primaryState === null || primaryState === void 0 ? void 0 : primaryState.octave)];
      var markerEntries = notes.map(function (note) {
        var noteName = _this5._keyboardNoteNameForState(note.step, note.accidentalClass);
        return {
          noteName: noteName,
          markerLabel: _this5._keyboardMarkerLabelForState(note.step, note.accidentalClass),
          tone: note.fixed ? "secondary" : "primary",
          $key: note.noteId === primary.noteId ? keys[0] : null
        };
      }).filter(function (entry) {
        return entry.noteName;
      });
      notes.forEach(function (note) {
        if (note.noteId === primary.noteId) return;
        var noteState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.stepToLetterOctave)(_this5.staff, note.step);
        var $key = _this5.keyboard.keyForNoteIfVisible(noteState === null || noteState === void 0 ? void 0 : noteState.letter, note.accidentalClass, noteState === null || noteState === void 0 ? void 0 : noteState.octave);
        if ($key.length) {
          keys.push($key);
          var entry = markerEntries.find(function (item) {
            return item.noteName === _this5._keyboardNoteNameForState(note.step, note.accidentalClass);
          });
          if (entry) entry.$key = $key;
        }
      });
      this.keyboard.syncActiveMarkers(markerEntries);
    }
  }, {
    key: "_syncPianoKeyboardMarkerFromAccidentalPreview",
    value: function _syncPianoKeyboardMarkerFromAccidentalPreview() {
      var _this$staff2,
        _this6 = this;
      if (!this.keyboard || !this.staff) return;
      var dragState = ((_this$staff2 = this.staff) === null || _this$staff2 === void 0 ? void 0 : _this$staff2._accDragSound) || {};
      var noteId = String(dragState.noteId || "");
      var previewAccidentalClass = dragState.prospectiveCls || null;
      if (!noteId || !previewAccidentalClass) {
        this._syncPianoKeyboardMarkerFromStaff();
        return;
      }
      var $note = this.$staffEl.find(".note[data-note-id=\"".concat(noteId, "\"]")).first();
      if (!$note.length) {
        this._syncPianoKeyboardMarkerFromStaff();
        return;
      }
      var top = parseFloat($note.css("top"));
      var step = Number.isFinite(top) ? this.staff.yToStep(top) : null;
      if (!Number.isFinite(step)) {
        this._syncPianoKeyboardMarkerFromStaff();
        return;
      }
      var notes = this._collectKeyboardSyncNotes().filter(function (note) {
        return Number.isFinite(note.step);
      });
      var primary = this._keyboardPrimaryNote(notes) || {
        noteId: noteId,
        step: step,
        accidentalClass: previewAccidentalClass,
        fixed: false
      };
      var primaryState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.stepToLetterOctave)(this.staff, primary.step);
      var primaryAccidental = primary.noteId === noteId ? previewAccidentalClass : primary.accidentalClass;
      var keys = [this.keyboard.keyForNote(primaryState === null || primaryState === void 0 ? void 0 : primaryState.letter, primaryAccidental, primaryState === null || primaryState === void 0 ? void 0 : primaryState.octave)];
      var markerEntries = notes.map(function (note) {
        var noteName = _this6._keyboardNoteNameForState(note.step, note.noteId === noteId ? previewAccidentalClass : note.accidentalClass);
        return {
          noteName: noteName,
          markerLabel: _this6._keyboardMarkerLabelForState(note.step, note.noteId === noteId ? previewAccidentalClass : note.accidentalClass),
          tone: note.fixed ? "secondary" : "primary",
          $key: note.noteId === primary.noteId ? keys[0] : null
        };
      }).filter(function (entry) {
        return entry.noteName;
      });
      notes.forEach(function (note) {
        if (note.noteId === primary.noteId) return;
        var noteState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.stepToLetterOctave)(_this6.staff, note.step);
        var accidentalClass = note.noteId === noteId ? previewAccidentalClass : note.accidentalClass;
        var $key = _this6.keyboard.keyForNoteIfVisible(noteState === null || noteState === void 0 ? void 0 : noteState.letter, accidentalClass, noteState === null || noteState === void 0 ? void 0 : noteState.octave);
        if ($key.length) {
          keys.push($key);
          var entry = markerEntries.find(function (item) {
            return item.noteName === _this6._keyboardNoteNameForState(note.step, accidentalClass);
          });
          if (entry) entry.$key = $key;
        }
      });
      this.keyboard.syncActiveMarkers(markerEntries);
    }
  }, {
    key: "_wirePianoKeyboardSync",
    value: function _wirePianoKeyboardSync() {
      var _this7 = this;
      if (!this.keyboard || !this.staff || this._keyboardSyncPatched) return;
      this._keyboardSyncPatched = true;
      var baseMoveNote = this.staff.moveNote.bind(this.staff);
      this.staff.moveNote = function () {
        var out = baseMoveNote.apply(void 0, arguments);
        _this7._syncPianoKeyboardMarkerFromStaff();
        return out;
      };
      var baseRemoveNote = this.staff.removeNote.bind(this.staff);
      this.staff.removeNote = function () {
        var out = baseRemoveNote.apply(void 0, arguments);
        _this7._syncPianoKeyboardMarkerFromStaff();
        return out;
      };
      var baseClearNotes = this.staff.clearNotes.bind(this.staff);
      this.staff.clearNotes = function () {
        var out = baseClearNotes.apply(void 0, arguments);
        _this7._syncPianoKeyboardMarkerFromStaff();
        return out;
      };
      this.$staffEl.off("staff:noteState.".concat(this.ns, ".keyboard")).on("staff:noteState.".concat(this.ns, ".keyboard"), function (e, data) {
        _this7._syncPianoKeyboardMarkerFromStaff();
      });
    }
  }, {
    key: "_wirePianoKeyboardAccidentalPreview",
    value: function _wirePianoKeyboardAccidentalPreview() {
      var _this8 = this;
      if (!this.keyboard || this._keyboardAccidentalPreviewPatched) return;
      this._keyboardAccidentalPreviewPatched = true;
      var $tools = $("#accidentals .accidental-tool");
      if (!$tools.length || !$tools.draggable) return;
      var originalDrag = $tools.draggable("option", "drag");
      var originalStop = $tools.draggable("option", "stop");
      $tools.draggable("option", "drag", function (event, ui) {
        if (typeof originalDrag === "function") originalDrag.call(event.currentTarget, event, ui);
        _this8._syncPianoKeyboardMarkerFromAccidentalPreview();
      });
      $tools.draggable("option", "stop", function (event, ui) {
        if (typeof originalStop === "function") originalStop.call(event.currentTarget, event, ui);
        _this8._syncPianoKeyboardMarkerFromStaff();
      });
    }
  }, {
    key: "_syncPianoKeyboardToggleUi",
    value: function _syncPianoKeyboardToggleUi() {
      var _this$$pianoToggleBtn, _this$$keyboardWrap;
      if (!((_this$$pianoToggleBtn = this.$pianoToggleBtn) !== null && _this$$pianoToggleBtn !== void 0 && _this$$pianoToggleBtn.length) || !((_this$$keyboardWrap = this.$keyboardWrap) !== null && _this$$keyboardWrap !== void 0 && _this$$keyboardWrap.length)) return;
      var visible = this.$keyboardWrap.is(":visible");
      if (visible) this.$pianoToggleBtn.attr("selected", "selected");else this.$pianoToggleBtn.removeAttr("selected");
    }
  }, {
    key: "_wirePianoKeyboardToggle",
    value: function _wirePianoKeyboardToggle() {
      var _this$$keyboardWrap2,
        _this$$pianoToggleBtn2,
        _this$$pianoToggleBtn3,
        _this9 = this;
      if (!((_this$$keyboardWrap2 = this.$keyboardWrap) !== null && _this$$keyboardWrap2 !== void 0 && _this$$keyboardWrap2.length)) return;
      if ((_this$$pianoToggleBtn2 = this.$pianoToggleBtn) !== null && _this$$pianoToggleBtn2 !== void 0 && _this$$pianoToggleBtn2.length) this.$keyboardWrap.hide();else this.$keyboardWrap.show();
      if ((_this$$pianoToggleBtn3 = this.$pianoToggleBtn) !== null && _this$$pianoToggleBtn3 !== void 0 && _this$$pianoToggleBtn3.length) {
        this.$pianoToggleBtn.off("click.".concat(this.ns, ".pianoToggle")).on("click.".concat(this.ns, ".pianoToggle"), function (e) {
          e.preventDefault();
          _this9.$keyboardWrap.toggle();
          _this9._syncPianoKeyboardToggleUi();
        });
      }
      this._syncPianoKeyboardToggleUi();
    }
  }, {
    key: "_toDisplayNoteName",
    value: function _toDisplayNoteName(letterWithAccidentals) {
      var raw = String(letterWithAccidentals || "").trim();
      if (!raw) return raw;
      if (!this._showSolfegeNoteNames()) return (0,_shared_noteNames_js__WEBPACK_IMPORTED_MODULE_7__.displayNoteName)(raw);
      var m = raw.match(/^([A-G])(.*)$/i);
      if (!m) return raw;
      var letter = String(m[1] || "").toUpperCase();
      var acc = String(m[2] || "");
      var sol = BaseStaffGame.LETTER_TO_SOLFEGE[letter] || letter;
      return (0,_shared_noteNames_js__WEBPACK_IMPORTED_MODULE_7__.displayNoteName)("".concat(sol).concat(acc));
    }
  }, {
    key: "_normalizeNumOfChallenges",
    value: function _normalizeNumOfChallenges(raw) {
      var _this$opts$numOfChall, _this$opts;
      var n = Number(raw);
      var fallback = Number((_this$opts$numOfChall = (_this$opts = this.opts) === null || _this$opts === void 0 ? void 0 : _this$opts.numOfChallenges) !== null && _this$opts$numOfChall !== void 0 ? _this$opts$numOfChall : 10);
      var safe = Number.isFinite(n) ? Math.trunc(n) : Number.isFinite(fallback) ? Math.trunc(fallback) : 10;
      var min = BaseStaffGame.MIN_CHALLENGES;
      var max = BaseStaffGame.MAX_CHALLENGES;
      if (safe < min) return min;
      if (safe > max) return max;
      return safe;
    }

    // ------------------------ sound controls ------------------------
  }, {
    key: "setSoundEnabled",
    value: function setSoundEnabled(enabled) {
      this.opts.sound = !!enabled;
      this.staff.setSoundEnabled(!!enabled);
      if (!this.opts.sound && window.Tone) {
        try {
          this._uiSfxSynth && this._uiSfxSynth.releaseAll && this._uiSfxSynth.releaseAll();
        } catch (_) {}
        try {
          this._uiSfxSynth && this._uiSfxSynth.dispose && this._uiSfxSynth.dispose();
        } catch (_) {}
        try {
          this._uiSfxNoise && this._uiSfxNoise.dispose && this._uiSfxNoise.dispose();
        } catch (_) {}
        try {
          this._uiTimerSfxSynth && this._uiTimerSfxSynth.dispose && this._uiTimerSfxSynth.dispose();
        } catch (_) {}
        this._uiSfxSynth = null;
        this._uiSfxNoise = null;
        this._uiTimerSfxSynth = null;
        this._uiSfxReady = false;
        try {
          Tone.context && Tone.context.suspend && Tone.context.suspend();
        } catch (_) {}
      }
      return this;
    }
  }, {
    key: "isSoundEnabled",
    value: function isSoundEnabled() {
      return this.staff.isSoundEnabled();
    }
  }, {
    key: "_ensureUiSfxAudio",
    value: function () {
      var _ensureUiSfxAudio2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              if (this.isSoundEnabled()) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              if (!this._uiSfxReady) {
                _context.n = 2;
                break;
              }
              return _context.a(2);
            case 2:
              if (window.Tone) {
                _context.n = 3;
                break;
              }
              return _context.a(2);
            case 3:
              _context.p = 3;
              _context.n = 4;
              return Tone.start();
            case 4:
              _context.n = 6;
              break;
            case 5:
              _context.p = 5;
              _t = _context.v;
              return _context.a(2);
            case 6:
              this._uiSfxSynth = _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.createUiPolySynth();
              this._uiSfxNoise = _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.createUiNoiseSynth();
              this._uiTimerSfxSynth = _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.createUiTimerSynth();
              this._uiSfxReady = true;
            case 7:
              return _context.a(2);
          }
        }, _callee, this, [[3, 5]]);
      }));
      function _ensureUiSfxAudio() {
        return _ensureUiSfxAudio2.apply(this, arguments);
      }
      return _ensureUiSfxAudio;
    }()
  }, {
    key: "_armUiSfxOnFirstGesture",
    value: function _armUiSfxOnFirstGesture() {
      var _this0 = this;
      if (this._audioUnlockArmed) return;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._audioUnlockArmed = true;
      var ns = ".uiSfxUnlock.".concat(this.ns);
      var unlock = function unlock() {
        _this0._ensureUiSfxAudio()["finally"](function () {
          $(document).off(ns);
        });
      };
      $(document).off(ns).one("pointerdown".concat(ns, " keydown").concat(ns, " touchstart").concat(ns), unlock);
    }
  }, {
    key: "_playSuccessSfxBasic",
    value: function _playSuccessSfxBasic() {
      var _this1 = this;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        if (!_this1._uiSfxSynth) return;
        var now = Tone.now();
        var variants = [["C6", "E6", "G6"], ["D6", "F#6", "A6"], ["E6", "G6", "B6"], ["G5", "B5", "D6", "G6"], ["A5", "C6", "E6", "A6"], ["C6", "D6", "G6"], ["F5", "A5", "C6", "F6"], ["E6", "A6", "C7"], ["B5", "D6", "G6"], ["C6", "G6", "E7"]];
        var picked = variants[Math.floor(Math.random() * variants.length)];
        picked.forEach(function (n, i) {
          _this1._uiSfxSynth.triggerAttackRelease(n, 0.07, now + i * 0.05, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("successBasic", 0.42));
        });
      });
    }
  }, {
    key: "_playSuccessSfxBonus",
    value: function _playSuccessSfxBonus() {
      var _this10 = this;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        var _this10$_uiSfxSynth$g;
        if (!_this10._uiSfxSynth) return;
        var now = Tone.now();
        var oldEnv = _objectSpread({}, _this10._uiSfxSynth.get().envelope);
        var oldOsc = (_this10$_uiSfxSynth$g = _this10._uiSfxSynth.get().oscillator) === null || _this10$_uiSfxSynth$g === void 0 ? void 0 : _this10$_uiSfxSynth$g.type;
        try {
          _this10._uiSfxSynth.set({
            oscillator: {
              type: "sine"
            },
            envelope: {
              attack: 0.004,
              decay: 0.12,
              sustain: 0.15,
              release: 0.65
            }
          });
        } catch (_) {}
        var streakLevel = Math.max(1, Math.min(24, Number(_this10._correctStreak) || 1));
        var semitoneShift = streakLevel - 1;
        var toNote = function toNote(midi) {
          return Tone.Frequency(midi, "midi").toNote();
        };

        // Start slightly higher (D4 root) and climb one semitone per streak level up to 24.
        var arp = [62, 66, 69, 73, 74].map(function (m) {
          return toNote(m + semitoneShift);
        });
        var hit = [62, 69, 74, 78].map(function (m) {
          return toNote(m + semitoneShift);
        });
        arp.forEach(function (n, i) {
          _this10._uiSfxSynth.triggerAttackRelease(n, 0.06, now + i * 0.045, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("successBonus", 0.45));
        });
        hit.forEach(function (n) {
          _this10._uiSfxSynth.triggerAttackRelease(n, 0.12, now + 0.26, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("successBonus", 0.30));
        });
        setTimeout(function () {
          try {
            _this10._uiSfxSynth.set({
              oscillator: {
                type: oldOsc || "triangle"
              },
              envelope: oldEnv
            });
          } catch (_) {}
        }, 600);
      });
    }
  }, {
    key: "_playFailSfx",
    value: function _playFailSfx() {
      var _this11 = this;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        var now = Tone.now();
        if (_this11._uiSfxNoise) {
          _this11._uiSfxNoise.triggerAttackRelease(0.06, now, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("failNoise", 0.45));
        }
        if (_this11._uiSfxSynth) {
          _this11._uiSfxSynth.triggerAttackRelease("A2", 0.10, now + 0.01, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("failNote", 0.55));
          _this11._uiSfxSynth.triggerAttackRelease("G2", 0.12, now + 0.08, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("failNote", 0.6));
        }
      });
    }
  }, {
    key: "_playFinalSfx",
    value: function _playFinalSfx() {
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.playFinalResults();
      });
    }
  }, {
    key: "_clearFinalMetricsSfxTimers",
    value: function _clearFinalMetricsSfxTimers() {
      if (!Array.isArray(this._finalMetricsSfxTimeouts)) {
        this._finalMetricsSfxTimeouts = [];
        return;
      }
      this._finalMetricsSfxTimeouts.forEach(function (id) {
        return clearTimeout(id);
      });
      this._finalMetricsSfxTimeouts = [];
    }
  }, {
    key: "_clearFinalCountupTimers",
    value: function _clearFinalCountupTimers() {
      if (!Array.isArray(this._finalCountupTimeouts)) {
        this._finalCountupTimeouts = [];
        return;
      }
      this._finalCountupTimeouts.forEach(function (id) {
        return clearTimeout(id);
      });
      this._finalCountupTimeouts = [];
    }
  }, {
    key: "_playFinalMetricPopSfx",
    value: function _playFinalMetricPopSfx(index) {
      if (!this.isSoundEnabled() || !window.Tone) return;
      if (!this._uiSfxReady) return;
      var now = Tone.now();
      var safeIdx = Math.max(0, Number(index) || 0);
      var midi = Math.min(96, 67 + safeIdx * 2); // rises as each box appears
      var synth = this._uiTimerSfxSynth || this._uiSfxSynth;
      if (!synth) return;
      synth.triggerAttackRelease(Tone.Frequency(midi, "midi"), 0.055, now, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("finalMetric", 0.44));
      synth.triggerAttackRelease(Tone.Frequency(midi + 5, "midi"), 0.045, now + 0.03, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("finalMetric", 0.34));
    }
  }, {
    key: "_animateFinalMetricsWithSfx",
    value: function _animateFinalMetricsWithSfx() {
      var _this12 = this;
      var $boxes = this.$finalOverlay.find("#metrics-boxes > div");
      if (!$boxes.length) return;
      $boxes.css({
        position: "relative",
        zIndex: 6
      });
      var BASE_DELAY_MS = 260;
      var STEP_DELAY_MS = 260; // wider spacing between boxes

      this._clearFinalMetricsSfxTimers();
      $boxes.each(function (i, el) {
        var delayMs = BASE_DELAY_MS + i * STEP_DELAY_MS;
        el.style.animationDelay = "".concat(delayMs, "ms");
        var tid = setTimeout(function () {
          _this12._playFinalMetricPopSfx(i);
        }, delayMs);
        _this12._finalMetricsSfxTimeouts.push(tid);
      });
    }
  }, {
    key: "_playPerfectGameBonusSfx",
    value: function _playPerfectGameBonusSfx() {
      var _this13 = this;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        var _this13$_uiSfxSynth$g;
        if (!_this13._uiSfxSynth) return;
        var oldEnv = _objectSpread({}, _this13._uiSfxSynth.get().envelope);
        var oldOsc = (_this13$_uiSfxSynth$g = _this13._uiSfxSynth.get().oscillator) === null || _this13$_uiSfxSynth$g === void 0 ? void 0 : _this13$_uiSfxSynth$g.type;
        try {
          _this13._uiSfxSynth.set({
            oscillator: {
              type: "triangle"
            },
            envelope: {
              attack: 0.01,
              decay: 0.18,
              sustain: 0.25,
              release: 0.8
            }
          });
        } catch (_) {}
        var now = Tone.now();
        var fanfare = ["C5", "E5", "G5", "C6", "E6", "G6", "C7"];
        fanfare.forEach(function (n, i) {
          _this13._uiSfxSynth.triggerAttackRelease(n, 0.09, now + i * 0.06, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("perfectBonus", 0.62));
        });
        var hit = ["C6", "G6", "C7", "E7"];
        hit.forEach(function (n) {
          return _this13._uiSfxSynth.triggerAttackRelease(n, 0.35, now + 0.48, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("perfectBonus", 0.46));
        });
        setTimeout(function () {
          try {
            _this13._uiSfxSynth.set({
              oscillator: {
                type: oldOsc || "triangle"
              },
              envelope: oldEnv
            });
          } catch (_) {}
        }, 1400);
      });
    }
  }, {
    key: "_playRunStartFanfareSfx",
    value: function _playRunStartFanfareSfx() {
      var _this14 = this;
      if (!this.isSoundEnabled() || !window.Tone) return;
      this._ensureUiSfxAudio().then(function () {
        if (!_this14._uiSfxSynth) return;
        var now = Tone.now();
        // French-overture style: dotted long-short gestures, ceremonial but "opening".
        // 4 rhythmic motifs x 5 tonal centers = 20 variants.
        var motifs = [
        // Motif A: tonic -> chromatic lift -> suspended color
        [[[0, 7], 0.00, 0.19, 0.20], [[3, 7], 0.24, 0.06, 0.18], [[0, 5, 10], 0.38, 0.18, 0.22], [[2, 5, 9], 0.62, 0.06, 0.18], [[0, 7, 12], 0.76, 0.15, 0.22]],
        // Motif B: minor color then bright turn
        [[[0, 3, 7], 0.00, 0.18, 0.20], [[2, 5, 9], 0.23, 0.06, 0.18], [[0, 7], 0.36, 0.18, 0.22], [[4, 7, 11], 0.60, 0.06, 0.18], [[0, 5, 9], 0.74, 0.15, 0.22]],
        // Motif C: fanfare call/response
        [[[0, 12], 0.00, 0.20, 0.19], [[7, 12], 0.25, 0.06, 0.17], [[2, 9, 14], 0.39, 0.18, 0.21], [[5, 9, 12], 0.63, 0.06, 0.17], [[0, 7, 10], 0.77, 0.16, 0.21]],
        // Motif D: stately dotted march
        [[[0, 5], 0.00, 0.19, 0.20], [[2, 5], 0.24, 0.06, 0.18], [[0, 4, 9], 0.38, 0.18, 0.22], [[-1, 4, 7], 0.62, 0.06, 0.18], [[0, 7, 11], 0.76, 0.15, 0.22]]];
        var roots = [53, 55, 57, 58, 60]; // F3..C4
        var variantIndex = Math.floor(Math.random() * 20);
        var motif = motifs[Math.floor(variantIndex / roots.length)];
        var root = roots[variantIndex % roots.length];
        var toNote = function toNote(m) {
          return Tone.Frequency(m, "midi").toNote();
        };
        motif.forEach(function (_ref2) {
          var _ref3 = _slicedToArray(_ref2, 4),
            intervals = _ref3[0],
            t = _ref3[1],
            dur = _ref3[2],
            vel = _ref3[3];
          var notes = intervals.map(function (i) {
            return toNote(root + i);
          });
          _this14._uiSfxSynth.triggerAttackRelease(notes, dur, now + t, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("runStart", vel));
        });
      });
    }

    // ------------------------ lifecycle ------------------------
  }, {
    key: "_isTimerEnabled",
    value: function _isTimerEnabled() {
      var v = this.opts.timer;
      var s = String(v || "").trim().toLowerCase();
      return v === true || s === "true" || s === "on";
    }
  }, {
    key: "_formatTimerDisplay",
    value: function _formatTimerDisplay(totalSec) {
      var sec = Math.max(0, Math.floor(Number(totalSec) || 0));
      var mm = String(Math.floor(sec / 60)).padStart(2, "0");
      var ss = String(sec % 60).padStart(2, "0");
      return "".concat(mm, ":").concat(ss);
    }
  }, {
    key: "_renderTimerDisplay",
    value: function _renderTimerDisplay() {
      var _this$$timerText;
      if (!((_this$$timerText = this.$timerText) !== null && _this$$timerText !== void 0 && _this$$timerText.length)) return;
      this.$timerText.text(this._formatTimerDisplay(this._timerRemainingSec));
      this._syncTimerWarningStyle();
    }
  }, {
    key: "_syncTimerWarningStyle",
    value: function _syncTimerWarningStyle() {
      var _this$$timerBox;
      if (!((_this$$timerBox = this.$timerBox) !== null && _this$$timerBox !== void 0 && _this$$timerBox.length)) return;
      var warning = this._timerRemainingSec <= 5;
      this.$timerBox.toggleClass("bg-red", warning);
      this.$timerBox.toggleClass("bg-primary", !warning);
    }
  }, {
    key: "_setTimedOutInteractivityDisabled",
    value: function _setTimedOutInteractivityDisabled(disabled) {
      var on = !!disabled;
      var $staff = $("#staff");
      var $staffWrapper = $("#staff-wrapper");
      var $accidentals = $("#accidentals");
      if (on) {
        $staff.attr("disabled", "disabled");
        $staffWrapper.attr("disabled", "disabled");
        $accidentals.attr("disabled", "disabled");
        $staff.attr("aria-disabled", "true");
        $staffWrapper.attr("aria-disabled", "true");
        $accidentals.attr("aria-disabled", "true");
        $staff.css("pointer-events", "none");
        $staffWrapper.css("pointer-events", "none");
        $accidentals.css("pointer-events", "none");
      } else {
        $staff.removeAttr("disabled");
        $staffWrapper.removeAttr("disabled");
        $accidentals.removeAttr("disabled");
        $staff.removeAttr("aria-disabled");
        $staffWrapper.removeAttr("aria-disabled");
        $accidentals.removeAttr("aria-disabled");
        $staff.css("pointer-events", "");
        $staffWrapper.css("pointer-events", "");
        $accidentals.css("pointer-events", "");
      }
    }
  }, {
    key: "_showSkipRoundButton",
    value: function _showSkipRoundButton() {
      var _this$$skipWrap;
      if (!((_this$$skipWrap = this.$skipWrap) !== null && _this$$skipWrap !== void 0 && _this$$skipWrap.length)) return;
      this.$skipWrap.show();
    }
  }, {
    key: "_hideSkipRoundButton",
    value: function _hideSkipRoundButton() {
      var _this$$skipWrap2;
      if (!((_this$$skipWrap2 = this.$skipWrap) !== null && _this$$skipWrap2 !== void 0 && _this$$skipWrap2.length)) return;
      this.$skipWrap.hide();
    }
  }, {
    key: "_hideTimeUpMessage",
    value: function _hideTimeUpMessage() {
      var _this$$timeupMessage;
      if (!((_this$$timeupMessage = this.$timeupMessage) !== null && _this$$timeupMessage !== void 0 && _this$$timeupMessage.length)) return;
      this.$timeupMessage.removeClass("animate__animated animate__flash").hide();
    }
  }, {
    key: "_showTimeUpMessage",
    value: function _showTimeUpMessage() {
      var _this$$timeupMessage2;
      if (!((_this$$timeupMessage2 = this.$timeupMessage) !== null && _this$$timeupMessage2 !== void 0 && _this$$timeupMessage2.length)) return;
      this.$timeupMessage.removeClass("animate__animated animate__flash").show();
      // eslint-disable-next-line no-unused-expressions
      this.$timeupMessage[0] && this.$timeupMessage[0].offsetWidth;
      this.$timeupMessage.addClass("animate__animated animate__flash");
    }
  }, {
    key: "_wouldReachLastRoundAfterAdvance",
    value: function _wouldReachLastRoundAfterAdvance() {
      if (this._isPracticeMode()) return false;
      var steps = Math.max(1, this.numOfChallenges || 1);
      var increment = 100 / steps;
      var current = parseFloat(this.$progressBar.data("progress")) || 0;
      return current + increment >= 100;
    }
  }, {
    key: "_finishRoundAsTimedOut",
    value: function _finishRoundAsTimedOut() {
      var _this15 = this;
      this._clearCorrectStreak();
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._stats.checksTotal += 1;
      var reachedEnd = this._updateProgressBar() >= 100;
      if (reachedEnd && !this._isPracticeMode()) {
        this._stats.finishedAtMs = Date.now();
        (0,_shared_finalResults_js__WEBPACK_IMPORTED_MODULE_2__.queueFinalResultsReveal)({
          $button: this.$checkBtn,
          showFinalResults: function showFinalResults() {
            return _this15._showFinalResults();
          }
        });
        return;
      }
      this._setTimedOutInteractivityDisabled(false);
      this._hideTimeUpMessage();
      this._hideSkipRoundButton();
      this.prompt.setTone("blue");
      this._resetRoundTimerIfEnabled();
      this.$accidentals.removeClass("invisible");
      this.newChallenge();
      this._armUiGates({
        resetInstructions: false
      });
      this.$checkBtn.enable();
    }
  }, {
    key: "_pulseTimerWarning",
    value: function _pulseTimerWarning() {
      var _this$$timer;
      if (!((_this$$timer = this.$timer) !== null && _this$$timer !== void 0 && _this$$timer.length)) return;
      this.$timer.removeClass("animate__animated animate__pulse");
      // eslint-disable-next-line no-unused-expressions
      this.$timer[0] && this.$timer[0].offsetWidth;
      this.$timer.addClass("animate__animated animate__pulse");
    }
  }, {
    key: "_playTimerWarningBeep",
    value: function _playTimerWarningBeep() {
      if (!this.isSoundEnabled() || !window.Tone) return;
      if (!this._uiSfxReady || !this._uiTimerSfxSynth) return;
      var now = Tone.now();
      this._uiTimerSfxSynth.triggerAttackRelease("C6", 0.06, now, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("timerBeep", 0.5));
    }
  }, {
    key: "_playTimerTimeUpSfx",
    value: function _playTimerTimeUpSfx() {
      if (!this.isSoundEnabled() || !window.Tone) return;
      if (!this._uiSfxReady || !this._uiTimerSfxSynth) return;
      var now = Tone.now();
      if (this._uiSfxNoise) {
        this._uiSfxNoise.triggerAttackRelease(0.12, now, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("timerTimeUp", 0.2));
      }
      this._uiTimerSfxSynth.triggerAttackRelease("G4", 0.11, now, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("timerTimeUp", 0.72));
      this._uiTimerSfxSynth.triggerAttackRelease("E4", 0.13, now + 0.10, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("timerTimeUp", 0.76));
      this._uiTimerSfxSynth.triggerAttackRelease("C4", 0.18, now + 0.22, _shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_4__.GameAudio.scale("timerTimeUp", 0.82));
    }
  }, {
    key: "_removeAllStaffNotesWithSmoke",
    value: function _removeAllStaffNotesWithSmoke() {
      var _this$$staffEl,
        _this$staff3,
        _this16 = this;
      if (!((_this$$staffEl = this.$staffEl) !== null && _this$$staffEl !== void 0 && _this$$staffEl.length) || !((_this$staff3 = this.staff) !== null && _this$staff3 !== void 0 && _this$staff3.removeNote)) return;
      var noteIds = this.$staffEl.find(".note").not(".preview").not(".hint").map(function (_, el) {
        return String(el.getAttribute("data-note-id") || "");
      }).get().filter(Boolean);
      if (!noteIds.length) return;
      noteIds.forEach(function (id) {
        var delay = Math.floor(Math.random() * 140); // tiny natural stagger
        setTimeout(function () {
          _this16.staff.removeNote(id, {
            smoke: true
          });
        }, delay);
      });
    }
  }, {
    key: "_stopGameTimer",
    value: function _stopGameTimer() {
      var _this$$timer2, _this$$timer2$removeC;
      if (this._timerTimeoutId != null) {
        clearTimeout(this._timerTimeoutId);
        this._timerTimeoutId = null;
      }
      this._timerEndsAtMs = 0;
      this._timerRemainingSec = 0;
      this._syncTimerWarningStyle();
      (_this$$timer2 = this.$timer) === null || _this$$timer2 === void 0 || (_this$$timer2$removeC = _this$$timer2.removeClass) === null || _this$$timer2$removeC === void 0 || _this$$timer2$removeC.call(_this$$timer2, "animate__animated animate__pulse");
    }
  }, {
    key: "_pauseGameTimer",
    value: function _pauseGameTimer() {
      var _this$$timer3, _this$$timer3$removeC;
      if (this._timerTimeoutId != null) {
        clearTimeout(this._timerTimeoutId);
        this._timerTimeoutId = null;
      }
      this._timerEndsAtMs = 0;
      (_this$$timer3 = this.$timer) === null || _this$$timer3 === void 0 || (_this$$timer3$removeC = _this$$timer3.removeClass) === null || _this$$timer3$removeC === void 0 || _this$$timer3$removeC.call(_this$$timer3, "animate__animated animate__pulse");
    }
  }, {
    key: "_runGameTimerTick",
    value: function _runGameTimerTick() {
      var _this17 = this;
      if (!this._timerEndsAtMs) return;
      var prev = this._timerRemainingSec;
      var msLeft = this._timerEndsAtMs - Date.now();
      var next = Math.max(0, Math.ceil(msLeft / 1000));
      this._timerRemainingSec = next;
      this._renderTimerDisplay();
      if (next > 0 && next <= 5 && next !== prev) {
        this._pulseTimerWarning();
        this._playTimerWarningBeep();
      } else if (next === 0 && prev !== 0) {
        $("#check").hide();
        this.$helpBtn.hide();
        this._removeAllStaffNotesWithSmoke();
        this._setTimedOutInteractivityDisabled(true);
        this._pulseTimerWarning();
        this._playTimerTimeUpSfx();
        this._showTimeUpMessage();
        this.$checkBtn.disable();
        if (this._wouldReachLastRoundAfterAdvance()) {
          this._hideSkipRoundButton();
          this._finishRoundAsTimedOut();
        } else {
          this._showSkipRoundButton();
        }
      }
      if (next <= 0) {
        this._stopGameTimer();
        return;
      }
      var nextChangeAt = this._timerEndsAtMs - (next - 1) * 1000;
      var delay = Math.max(16, nextChangeAt - Date.now());
      this._timerTimeoutId = setTimeout(function () {
        return _this17._runGameTimerTick();
      }, delay);
    }
  }, {
    key: "_startGameTimer",
    value: function _startGameTimer() {
      var startSec = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 10;
      this._stopGameTimer();
      var total = Math.max(0, Math.floor(Number(startSec) || 0));
      this._timerEndsAtMs = Date.now() + total * 1000;
      this._timerRemainingSec = total;
      this._renderTimerDisplay();
      this._runGameTimerTick();
    }
  }, {
    key: "_timerLimitSeconds",
    value: function _timerLimitSeconds() {
      var raw = Number(this.opts.timeLimit != null ? this.opts.timeLimit : this.opts.timerLimit);
      if (!Number.isFinite(raw)) return 10;
      return Math.max(0, Math.floor(raw));
    }
  }, {
    key: "_resetRoundTimerIfEnabled",
    value: function _resetRoundTimerIfEnabled() {
      if (!this._isTimerEnabled()) return;
      this.$timer.show();
      this._startGameTimer(this._timerLimitSeconds());
    }
  }, {
    key: "start",
    value: function start() {
      var _this$keyboard2, _this$keyboard2$bind;
      this._madeAnyMistake = false;
      this.instructionsUi.show().replay();
      $("#controls").show();
      this._instructionsRemoved = false;
      this._wireAccidentalPalette();
      this._wireStaffTools();
      (_this$keyboard2 = this.keyboard) === null || _this$keyboard2 === void 0 || (_this$keyboard2$bind = _this$keyboard2.bind) === null || _this$keyboard2$bind === void 0 || _this$keyboard2$bind.call(_this$keyboard2);
      this._wirePianoKeyboardSync();
      this._wirePianoKeyboardAccidentalPreview();
      this._wirePianoKeyboardToggle();
      this._wireControls();
      this._resetProgress();
      this._setTimedOutInteractivityDisabled(false);
      this._hideTimeUpMessage();
      this._hideSkipRoundButton();
      if (this._isTimerEnabled()) {
        this.$timer.show();
        this._armUiSfxOnFirstGesture();
        this._resetRoundTimerIfEnabled();
      } else {
        this._stopGameTimer();
        this.$timer.hide();
      }
      this.$accidentals.removeClass("invisible");
      this.newChallenge();
      this._syncPianoKeyboardStartNote();
      this._syncPianoKeyboardMarkerFromStaff();
      this._armUiGates({
        resetInstructions: true
      });
      $("#page-wrapper").fadeIn("fast");
    }

    // Subclasses must implement
  }, {
    key: "newChallenge",
    value: function newChallenge() {
      throw new Error("newChallenge() not implemented");
    }

    // Subclasses implement their own check logic
  }, {
    key: "_onCheck",
    value: function _onCheck() {
      throw new Error("_onCheck() not implemented");
    }

    // ------------------------ wiring ------------------------
  }, {
    key: "_wireAccidentalPalette",
    value: function _wireAccidentalPalette() {
      $("#accidentals .music-font__doublesharp, #accidentals .music-font__doubleflat").addClass("d-none");
      this.$accidentals.removeClass("invisible");
    }
  }, {
    key: "_wireStaffTools",
    value: function _wireStaffTools() {
      var _this18 = this;
      this.staff.enableNoteDragAndClickDelete();
      this.staff.enableGhostClickCreate();
      this.staff.enableAccidentalDrag($("#accidentals .music-font__sharp, #accidentals .music-font__flat, #accidentals .music-font__natural"));
      this.staff.enableAccidentalDropOnStaff();
      this.$staffEl.off("staff:noteState._log.".concat(this.ns)).on("staff:noteState._log.".concat(this.ns), function (e, data) {
        var full = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.spellNoteFromState)(_this18.staff, data.step, data.accidentalClass);
        var letterOnly = full.replace(/\d+$/, "");
        var displayName = _this18._toDisplayNoteName(letterOnly);
        if (_this18.showNoteNames) {
          _this18.$staffEl.find(".note[data-note-id=\"".concat(data.noteId, "\"] .lettername")).html(displayName);
        }
        if (data.source === "fixed") {
          var _this18$_onFixedNoteS;
          _this18._fixedNote = {
            letterWithAcc: letterOnly,
            letterOnly: letterOnly.replace(/[#b]+$/, "")
          };
          _this18._fixedState = {
            step: data.step,
            accidentalClass: data.accidentalClass || null,
            midi: data.midi
          };
          (_this18$_onFixedNoteS = _this18._onFixedNoteState) === null || _this18$_onFixedNoteS === void 0 || _this18$_onFixedNoteS.call(_this18, data, full, letterOnly);
        }

        // Keep your debug logging behavior
        // eslint-disable-next-line no-console
        console.log(data.source === "fixed" ? "Fixed note:" : "User note:", full, {
          midi: data.midi,
          noteId: data.noteId,
          step: data.step,
          clef: _this18.staff.getClef()
        });
      });
    }
  }, {
    key: "_wireControls",
    value: function _wireControls() {
      var _this19 = this;
      $("#clear").off("click.".concat(this.ns)).on("click.".concat(this.ns), function () {
        return _this19.staff.clearNotes();
      });
      this.$checkBtn.off("click.".concat(this.ns)).on("click.".concat(this.ns), function () {
        return _this19._onCheck();
      });
      this.$helpBtn.off("click.".concat(this.ns, "Help")).on("click.".concat(this.ns, "Help"), function () {
        _this19._usedHintThisRound = true;
        _this19._showHintNote();
        _this19.$helpBtn.hide();
      });
      this.$skipBtn.off("click.".concat(this.ns, "Skip")).on("click.".concat(this.ns, "Skip"), function (e) {
        e.preventDefault();
        _this19._finishRoundAsTimedOut();
      });
      if (!this._continueBound) {
        this._continueBound = true;
        $("#continue button").off("click.".concat(this.ns)).on("click.".concat(this.ns), function () {
          $("#continue").hide();
          _this19._hideSkipRoundButton();
          _this19._hideTimeUpMessage();
          _this19._setTimedOutInteractivityDisabled(false);
          _this19._resetRoundTimerIfEnabled();
          _this19.prompt.setTone("blue");
          _this19.$accidentals.removeClass("invisible");
          _this19.newChallenge();
          _this19._syncPianoKeyboardStartNote();
          _this19._syncPianoKeyboardMarkerFromStaff();
          _this19._armUiGates({
            resetInstructions: true
          });
          _this19.$checkBtn.enable();
        });
      }
    }
  }, {
    key: "_hideHelpButtonOnAnswerEdit",
    value: function _hideHelpButtonOnAnswerEdit() {
      var _this$$helpBtn, _this$$helpBtn$hide;
      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      if ((data === null || data === void 0 ? void 0 : data.source) === "fixed") return;
      (_this$$helpBtn = this.$helpBtn) === null || _this$$helpBtn === void 0 || (_this$$helpBtn$hide = _this$$helpBtn.hide) === null || _this$$helpBtn$hide === void 0 || _this$$helpBtn$hide.call(_this$$helpBtn);
    }

    // ------------------------ UI gating ------------------------
  }, {
    key: "_instructionsAfterUserNotes",
    value: function _instructionsAfterUserNotes() {
      var v = Number(this.opts.instructionsAfterUserNotes);
      return Number.isFinite(v) && v >= 0 ? Math.floor(v) : 1;
    }
  }, {
    key: "_checkAfterUserNotes",
    value: function _checkAfterUserNotes() {
      var v = Number(this.opts.checkAfterUserNotes);
      return Number.isFinite(v) && v >= 0 ? Math.floor(v) : 1;
    }
  }, {
    key: "_currentUserNoteCount",
    value: function _currentUserNoteCount() {
      if (this.staff && typeof this.staff._userNoteCount === "function") {
        return this.staff._userNoteCount();
      }
      return this.$staffEl.find(".note").not(".fixed").not(".preview").not(".hint").length;
    }
  }, {
    key: "_armUiGates",
    value: function _armUiGates() {
      var _this20 = this;
      var _ref4 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        resetInstructions = _ref4.resetInstructions;
      var needForInstructions = this._instructionsAfterUserNotes();
      var needForCheck = this._checkAfterUserNotes();
      if (resetInstructions) {
        this._instructionsRemoved = false;
        this.$instructions.show();
      }
      if (needForCheck <= 0) $("#check").show().removeClass("invisible");else $("#check").hide().addClass("invisible");
      this._userNotesSinceGate = 0;
      var syncUiGate = function syncUiGate() {
        var count = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _this20._currentUserNoteCount();
        var userNoteCount = Number.isFinite(count) ? count : _this20._currentUserNoteCount();
        if (needForInstructions > 0 && userNoteCount < needForInstructions) {
          _this20._restoreInstructions();
        } else if (!_this20._instructionsRemoved && userNoteCount >= needForInstructions) {
          _this20._removeInstructions();
        }
        if (needForCheck <= 0 || userNoteCount >= needForCheck) {
          $("#check").show().removeClass("invisible");
        } else {
          $("#check").hide().addClass("invisible");
        }
      };
      syncUiGate();
      this.$staffEl.off("staff:userNoteAdded._uiGate.".concat(this.ns, " staff:userNotesChanged._uiGate.").concat(this.ns)).on("staff:userNoteAdded._uiGate.".concat(this.ns), function () {
        _this20._userNotesSinceGate += 1;
        _this20._hideHelpButtonOnAnswerEdit();
        syncUiGate();
      }).on("staff:userNotesChanged._uiGate.".concat(this.ns), function (e, data) {
        _this20._hideHelpButtonOnAnswerEdit(data);
        syncUiGate(Number(data === null || data === void 0 ? void 0 : data.count));
      });
      this.$staffEl.off("staff:noteState._hideHelp.".concat(this.ns)).on("staff:noteState._hideHelp.".concat(this.ns), function (e, data) {
        _this20._hideHelpButtonOnAnswerEdit(data);
      });
    }

    // ------------------------ fixed note selection ------------------------
  }, {
    key: "_fixedStepBounds",
    value: function _fixedStepBounds() {
      return {
        min: this.staff.minStepAllowed(),
        max: this.staff.maxStepAllowed()
      };
    }
  }, {
    key: "_randomFixedStep",
    value: function _randomFixedStep() {
      var _this$_duelRandom, _this$_duelRandom2;
      var _this$_fixedStepBound = this._fixedStepBounds(),
        min = _this$_fixedStepBound.min,
        max = _this$_fixedStepBound.max;
      return Math.floor(((_this$_duelRandom = (_this$_duelRandom2 = this._duelRandom) === null || _this$_duelRandom2 === void 0 ? void 0 : _this$_duelRandom2.call(this)) !== null && _this$_duelRandom !== void 0 ? _this$_duelRandom : Math.random()) * (max - min + 1)) + min;
    }

    // ------------------------ hints ------------------------

    /**
     * Compute one hint note (legacy API).
     *
     * @returns {{step:number, accidentalClass?:string}|null}
     */
  }, {
    key: "_computeHintAnswer",
    value: function _computeHintAnswer() {
      return null; // subclasses override
    }

    /**
     * Compute one or more hint notes.
     *
     * Subclasses can override this to return multiple notes (e.g. triads).
     *
     * @returns {Array<{id?:string, step:number|null, accidentalClass?:string|null}>}
     */
  }, {
    key: "_computeHintAnswers",
    value: function _computeHintAnswers() {
      var single = this._computeHintAnswer();
      return single ? [single] : [];
    }
  }, {
    key: "_removeAllHintNotes",
    value: function _removeAllHintNotes() {
      var _this21 = this;
      var ids = Array.isArray(this._activeHintIds) ? this._activeHintIds : [];
      ids.forEach(function (id) {
        return _this21.staff.removeNote(id);
      });
      this._activeHintIds = [];
    }
  }, {
    key: "_removeAllUserNotesForHint",
    value: function _removeAllUserNotesForHint() {
      var _this22 = this;
      var $userNotes = this.$staffEl.find(".note").not(".fixed").not(".preview").not(".hint");
      $userNotes.each(function (_, el) {
        var id = el.getAttribute("data-note-id");
        if (id) _this22.staff.removeNote(id);
      });
    }
  }, {
    key: "_randomFreeStep",
    value: function _randomFreeStep() {
      var min = this.staff.minStepAllowed();
      var max = this.staff.maxStepAllowed();
      for (var tries = 0; tries < 50; tries++) {
        var step = Math.floor(Math.random() * (max - min + 1)) + min;
        if (!this.staff._isStepOccupied(step, null)) return step;
      }
      return null;
    }
  }, {
    key: "_attachHintBlinkRemoval",
    value: function _attachHintBlinkRemoval(noteId) {
      var _this23 = this;
      var $note = this.$staffEl.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      if (!$note.length) return;
      $note.off("animationend.hint.".concat(noteId, " webkitAnimationEnd.hint.").concat(noteId)).one("animationend.hint.".concat(noteId, " webkitAnimationEnd.hint.").concat(noteId), function () {
        _this23.staff.removeNote(noteId);
        _this23._activeHintIds = (_this23._activeHintIds || []).filter(function (x) {
          return x !== noteId;
        });
      });
    }
  }, {
    key: "_showHintNote",
    value: function _showHintNote() {
      this._removeAllHintNotes();
      this._removeAllUserNotesForHint();
      var answers = this._computeHintAnswers();
      var specs = Array.isArray(answers) && answers.length ? answers : [{
        step: null,
        accidentalClass: null
      }];
      this._activeHintIds = [];
      for (var i = 0; i < specs.length; i++) {
        var ans = specs[i] || {};
        var id = ans.id || (specs.length === 1 ? "hint" : "hint".concat(i + 1));
        var step = Number.isFinite(ans.step) ? Number(ans.step) : null;
        var accidentalClass = ans.accidentalClass || null;
        if (step == null) step = this._randomFreeStep();
        if (step == null) continue;
        var createdId = this.staff.addNote({
          id: id,
          step: step,
          className: "hint blink",
          allowOccupied: true,
          skipResolve: true
        });
        if (!createdId) continue;
        this._activeHintIds.push(id);
        if (accidentalClass) {
          this.staff.attachAccidentalToNote(id, accidentalClass);
          this.$staffEl.find(".accidental[data-for-note-id=\"".concat(id, "\"]")).addClass("hint blink");
        }
        this.$staffEl.find(".ledger[data-for-note-id=\"".concat(id, "\"]")).addClass("hint blink");
        this._attachHintBlinkRemoval(id);
      }
    }

    // ------------------------ scoring / progress ------------------------
  }, {
    key: "_isPracticeMode",
    value: function _isPracticeMode() {
      var v = this.opts.practiceMode;
      return v === true || String(v || "").trim().toLowerCase() === "on";
    }
  }, {
    key: "_syncPracticeUi",
    value: function _syncPracticeUi() {
      var practice = this._isPracticeMode();
      var $score = $("#score");
      if (practice) {
        $score.hide();
        this.$progressBar.parent().addClass("opacity-1");
      } else {
        $score.show();
        this.$progressBar.parent().removeClass("opacity-1");
      }
    }
  }, {
    key: "_successAnimation",
    value: function _successAnimation() {
      var _ref5 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        isBonus = _ref5.isBonus;
      if (isBonus) this._playSuccessSfxBonus();else this._playSuccessSfxBasic();
      this.$helpBtn.hide();
      this.$accidentals.addClass("invisible");
      this.$feedback.find(".message span").text((0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_1__.pickOne)(this.successPhrases));
      this.$feedback.stop(true, true).fadeIn("fast");
    }
  }, {
    key: "_handleCorrectAnswerUi",
    value: function _handleCorrectAnswerUi() {
      var _this24 = this;
      var _ref6 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref6$isBonus = _ref6.isBonus,
        isBonus = _ref6$isBonus === void 0 ? false : _ref6$isBonus,
        _ref6$earned = _ref6.earned,
        earned = _ref6$earned === void 0 ? 0 : _ref6$earned,
        _ref6$$prompt = _ref6.$prompt,
        $prompt = _ref6$$prompt === void 0 ? this.prompt.$root : _ref6$$prompt,
        _ref6$$extraHide = _ref6.$extraHide,
        $extraHide = _ref6$$extraHide === void 0 ? $() : _ref6$$extraHide,
        _ref6$finalDelayMs = _ref6.finalDelayMs,
        finalDelayMs = _ref6$finalDelayMs === void 0 ? 1600 : _ref6$finalDelayMs;
      this._successAnimation({
        isBonus: isBonus
      });
      if (!this._instructionsRemoved) {
        this.$instructions.hide();
        this._instructionsRemoved = true;
      }
      if ($prompt !== null && $prompt !== void 0 && $prompt.length) $prompt.hide();
      if ($extraHide !== null && $extraHide !== void 0 && $extraHide.length) $extraHide.hide();
      $("#score").animateCSS && $("#score").animateCSS("heartBeat");
      if (earned > 0) this._showIncrement(earned);
      if (this._updateProgressBar() >= 100) {
        this._stats.finishedAtMs = Date.now();
        (0,_shared_finalResults_js__WEBPACK_IMPORTED_MODULE_2__.queueFinalResultsReveal)({
          $button: this.$checkBtn,
          showFinalResults: function showFinalResults() {
            return _this24._showFinalResults();
          },
          delayMs: finalDelayMs
        });
      } else {
        $("#check").hide();
        $("#continue").show();
      }
    }
  }, {
    key: "_runSuccessFeedbackTransition",
    value: function _runSuccessFeedbackTransition() {
      var _this25 = this;
      var _ref7 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref7$$interval = _ref7.$interval,
        $interval = _ref7$$interval === void 0 ? null : _ref7$$interval,
        _ref7$delayMs = _ref7.delayMs,
        delayMs = _ref7$delayMs === void 0 ? 800 : _ref7$delayMs,
        _ref7$onDone = _ref7.onDone,
        onDone = _ref7$onDone === void 0 ? null : _ref7$onDone;
      if (this._successFeedbackTimeoutId != null) {
        clearTimeout(this._successFeedbackTimeoutId);
        this._successFeedbackTimeoutId = null;
      }
      if ($interval !== null && $interval !== void 0 && $interval.length) $interval.hide();
      var safeDelay = Math.max(0, Number(delayMs) || 0);
      this._successFeedbackTimeoutId = setTimeout(function () {
        var _this25$$feedback, _this25$$feedback$hid;
        _this25._successFeedbackTimeoutId = null;
        (_this25$$feedback = _this25.$feedback) === null || _this25$$feedback === void 0 || (_this25$$feedback$hid = _this25$$feedback.hide) === null || _this25$$feedback$hid === void 0 || _this25$$feedback$hid.call(_this25$$feedback);
        if ($interval !== null && $interval !== void 0 && $interval.length) $interval.show();
        if (typeof onDone === "function") onDone();
      }, safeDelay);
      return this._successFeedbackTimeoutId;
    }
  }, {
    key: "_showIncrement",
    value: function _showIncrement(earned) {
      var $inc = this.$increment;
      if (!$inc || !$inc.length) return;
      $inc.stop(true, true);
      $inc.text("+".concat(earned)).show();
      if ($inc.animateCSS) {
        $inc.animateCSS("fadeOutUp").then(function () {
          return $inc.hide();
        });
      } else {
        setTimeout(function () {
          return $inc.hide();
        }, 800);
      }
    }
  }, {
    key: "_showBonusBadge",
    value: function _showBonusBadge(bonusAmount) {
      if (!this.$bonusBadge || !this.$bonusBadge.length) return;
      if (!bonusAmount || bonusAmount <= 0) {
        this.$bonusBadge.hide();
        return;
      }
      this.$bonusBadge.text("+".concat(bonusAmount, " BONUS")).show();
      if (this.$bonusBadge.animateCSS) {
        this.$bonusBadge.animateCSS("tada");
      }
    }
  }, {
    key: "_awardPointsForCorrect",
    value: function _awardPointsForCorrect() {
      if (this._isPracticeMode()) {
        this._clearCorrectStreak();
        this.$points.text("0");
        this._showBonusBadge(0);
        return {
          earned: 0,
          firstTry: false,
          bonusEarned: 0
        };
      }
      if (this._usedHintThisRound) {
        this._clearCorrectStreak();
        this._showBonusBadge(0);
        return {
          earned: 0,
          firstTry: false,
          bonusEarned: 0
        };
      }
      var firstTry = !this._madeMistakeThisRound;
      var base = Number.isFinite(this.opts.basePoints) ? this.opts.basePoints : 1;
      var bonus = Number.isFinite(this.opts.firstTryBonus) ? this.opts.firstTryBonus : 0;
      var earned = firstTry ? base + bonus : base;
      var bonusEarned = firstTry ? bonus : 0;
      this._applyCorrectStreakForOutcome({
        firstTry: firstTry
      });
      this.points += earned;
      this.$points.text(String(this.points));
      this._showBonusBadge(bonusEarned);
      return {
        earned: earned,
        firstTry: firstTry,
        bonusEarned: bonusEarned
      };
    }
  }, {
    key: "getCorrectStreak",
    value: function getCorrectStreak() {
      return Math.max(0, Number(this._correctStreak) || 0);
    }
  }, {
    key: "_syncStreakBarClass",
    value: function _syncStreakBarClass() {
      var _this$$progressBar;
      if (!((_this$$progressBar = this.$progressBar) !== null && _this$$progressBar !== void 0 && _this$$progressBar.length)) return;
      var active = this.getCorrectStreak() >= 3;
      this.$progressBar.toggleClass("streak-bar", !!active);
    }
  }, {
    key: "_applyCorrectStreakForOutcome",
    value: function _applyCorrectStreakForOutcome() {
      var _ref8 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        firstTry = _ref8.firstTry;
      if (!firstTry) {
        this._clearCorrectStreak();
        return;
      }
      this._correctStreak += 1;
      this._syncStreakBarClass();
      if (this._correctStreak <= 1) {
        // eslint-disable-next-line no-console
        console.log("[BaseStaffGame] Correct answer. No streak yet.");
      } else {
        // eslint-disable-next-line no-console
        console.log("[BaseStaffGame] Streak ".concat(this._correctStreak, "x"));
      }
    }
  }, {
    key: "_clearCorrectStreak",
    value: function _clearCorrectStreak() {
      this._correctStreak = 0;
      this._syncStreakBarClass();
    }
  }, {
    key: "_resetProgress",
    value: function _resetProgress() {
      this._syncPracticeUi();
      this._clearCorrectStreak();
      this._madeAnyMistake = false;
      this.$progressBar.data("progress", 0);
      this.$progressBar.css({
        width: "0%"
      });
      this.$progressBar.removeClass("streak-bar");
      if (this._isPracticeMode()) this.$progressCounter.text("Practice");else this.$progressCounter.text("");
    }
  }, {
    key: "_updateProgressBar",
    value: function _updateProgressBar() {
      if (this._isPracticeMode()) {
        this.$progressBar.data("progress", 0);
        this.$progressBar.css({
          width: "0%"
        });
        this.$progressCounter.text("Practice");
        return 0;
      }
      var steps = Math.max(1, this.numOfChallenges || 1);
      var increment = 100 / steps;
      var current = parseFloat(this.$progressBar.data("progress")) || 0;
      current = Math.min(100, current + increment);
      this.$progressBar.data("progress", current);
      this.$progressBar.css({
        width: "".concat(current, "%")
      });
      var completed = Math.min(steps, Math.max(0, Math.round(current / increment)));
      this.$progressCounter.text("".concat(completed, " of ").concat(steps));
      return current;
    }
  }, {
    key: "_isCheckFailTarget",
    value: function _isCheckFailTarget($target) {
      var _this$$checkBtn;
      if (!($target !== null && $target !== void 0 && $target.length) || !((_this$$checkBtn = this.$checkBtn) !== null && _this$$checkBtn !== void 0 && _this$$checkBtn.length)) return false;
      return $target.is(this.$checkBtn) || $target.has(this.$checkBtn).length > 0;
    }
  }, {
    key: "_showCheckWrongState",
    value: function _showCheckWrongState() {
      var _this$$checkBtn2,
        _this26 = this;
      if (!((_this$$checkBtn2 = this.$checkBtn) !== null && _this$$checkBtn2 !== void 0 && _this$$checkBtn2.length)) return;
      if (this._checkWrongStateTimeout != null) {
        clearTimeout(this._checkWrongStateTimeout);
        this._checkWrongStateTimeout = null;
      }
      this.$checkBtn.removeClass("animate__animated animate__shakeX").attr("state", "wrong").attr("disabled", "disabled").prop("disabled", true);
      this._checkWrongStateTimeout = setTimeout(function () {
        _this26.$checkBtn.attr("state", "waiting").removeAttr("disabled").prop("disabled", false);
        _this26._checkWrongStateTimeout = null;
      }, 2000);
    }
  }, {
    key: "_failAnimation",
    value: function _failAnimation($shakeTarget) {
      var _this27 = this;
      this._clearCorrectStreak();
      this._playFailSfx();
      this._removeInstructions();
      var $target = $shakeTarget || this.$checkWrap;
      if (this._isCheckFailTarget($target)) {
        $target.removeClass("animate__animated animate__shakeX");
        this._showCheckWrongState();
        return;
      }
      $target.removeClass("animate__animated animate__shakeX");
      // eslint-disable-next-line no-unused-expressions
      $target[0] && $target[0].offsetWidth;
      $target.addClass("animate__animated animate__shakeX");
      $target.off("animationend._fail.".concat(this.ns, " webkitAnimationEnd._fail.").concat(this.ns, " oAnimationEnd._fail.").concat(this.ns, " MSAnimationEnd._fail.").concat(this.ns)).one("animationend._fail.".concat(this.ns, " webkitAnimationEnd._fail.").concat(this.ns, " oAnimationEnd._fail.").concat(this.ns, " MSAnimationEnd._fail.").concat(this.ns), function () {
        $target.removeClass("animate__animated animate__shakeX");
        _this27.$checkBtn.enable();
      });
    }
  }, {
    key: "_removeInstructions",
    value: function _removeInstructions() {
      if (this._instructionsRemoved) return;
      this.$instructions.hide();
      this._instructionsRemoved = true;
    }
  }, {
    key: "_restoreInstructions",
    value: function _restoreInstructions() {
      if (!this._instructionsRemoved) return;
      this.$instructions.show();
      this._instructionsRemoved = false;
    }
  }, {
    key: "_bonusPointSettingKeys",
    value: function _bonusPointSettingKeys() {
      var _this$opts2;
      return Array.isArray((_this$opts2 = this.opts) === null || _this$opts2 === void 0 ? void 0 : _this$opts2.bonusPoints) ? this.opts.bonusPoints.map(function (key) {
        return String(key || "").trim();
      }).filter(Boolean) : [];
    }
  }, {
    key: "_hasConfiguredScoreDoubleBonus",
    value: function _hasConfiguredScoreDoubleBonus(accuracy) {
      var _this28 = this;
      if (Number(accuracy) !== 100) return false;
      var keys = BaseStaffGame.prototype._bonusPointSettingKeys.call(this);
      return keys.some(function (key) {
        var _this28$opts;
        return BaseStaffGame.prototype._normalizeOnOff.call(_this28, (_this28$opts = _this28.opts) === null || _this28$opts === void 0 ? void 0 : _this28$opts[key]);
      });
    }
  }, {
    key: "_buildFinalScoreSummary",
    value: function _buildFinalScoreSummary() {
      var _ref9 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref9$basePoints = _ref9.basePoints,
        basePoints = _ref9$basePoints === void 0 ? 0 : _ref9$basePoints,
        _ref9$accuracy = _ref9.accuracy,
        accuracy = _ref9$accuracy === void 0 ? 0 : _ref9$accuracy,
        _ref9$perfectGame = _ref9.perfectGame,
        perfectGame = _ref9$perfectGame === void 0 ? false : _ref9$perfectGame;
      var normalizedBasePoints = Number(basePoints) || 0;
      var initialScore = perfectGame ? normalizedBasePoints * 2 : normalizedBasePoints;
      var settingsBonus = BaseStaffGame.prototype._hasConfiguredScoreDoubleBonus.call(this, accuracy);
      var finalScore = settingsBonus ? initialScore * 2 : initialScore;
      return {
        perfectGame: !!perfectGame,
        settingsBonus: settingsBonus,
        initialScore: initialScore,
        finalScore: finalScore
      };
    }
  }, {
    key: "_showFinalResults",
    value: function _showFinalResults() {
      var _this$_stats$checksTo,
        _this$_stats$checksCo,
        _this$_stats$finished,
        _this29 = this;
      if (this._isPracticeMode()) return;
      var total = Math.max(0, (_this$_stats$checksTo = this._stats.checksTotal) !== null && _this$_stats$checksTo !== void 0 ? _this$_stats$checksTo : 0);
      var correct = Math.max(0, (_this$_stats$checksCo = this._stats.checksCorrect) !== null && _this$_stats$checksCo !== void 0 ? _this$_stats$checksCo : 0);
      var accuracy = total ? Math.round(correct / total * 100) : 0;
      var endMs = (_this$_stats$finished = this._stats.finishedAtMs) !== null && _this$_stats$finished !== void 0 ? _this$_stats$finished : Date.now();
      var totalSeconds = Math.max(0, Math.floor((endMs - (window.__activeDuel ? Date.parse(window.__activeDuel.state.starts_at) : PAGE_OPENED_AT_MS)) / 1000));
      var perfectGame = total > 0 && !this._madeAnyMistake;
      var scoreSummary = this._buildFinalScoreSummary({
        basePoints: this.points,
        accuracy: accuracy,
        perfectGame: perfectGame
      });
      if (perfectGame) {
        setTimeout(function () {
          var _this29$$doublePoints, _this29$$doublePoints2;
          (_this29$$doublePoints = _this29.$doublePoints) === null || _this29$$doublePoints === void 0 || (_this29$$doublePoints2 = _this29$$doublePoints.show) === null || _this29$$doublePoints2 === void 0 || _this29$$doublePoints2.call(_this29$$doublePoints);
          _this29._playPerfectGameBonusSfx();
        }, 1750);

        // eslint-disable-next-line no-console
        console.log("[BaseStaffGame] Perfect game! 2x bonus applied.", {
          basePoints: this.points,
          finalPoints: scoreSummary.initialScore
        });
      } else {
        // eslint-disable-next-line no-console
        console.log("[BaseStaffGame] No perfect-game bonus.", {
          basePoints: this.points,
          finalPoints: scoreSummary.initialScore,
          madeAnyMistake: this._madeAnyMistake
        });
      }
      if (scoreSummary.settingsBonus) {
        // eslint-disable-next-line no-console
        console.log("[BaseStaffGame] Extra doubling is happening because the user selected challenging settings.", {
          scoreBeforeSettingsBonus: scoreSummary.initialScore,
          finalPoints: scoreSummary.finalScore
        });
      }
      (0,_shared_finalResults_js__WEBPACK_IMPORTED_MODULE_2__.renderFinalResultsOverlay)({
        $finalOverlay: this.$finalOverlay,
        rounds: this.numOfChallenges,
        score: scoreSummary.finalScore,
        accuracy: accuracy,
        durationSec: totalSeconds,
        settingsBonus: scoreSummary.settingsBonus,
        clearCountupTimers: function clearCountupTimers() {
          return _this29._clearFinalCountupTimers();
        },
        countupTimers: this._finalCountupTimeouts,
        animateMetrics: function animateMetrics() {
          return _this29._animateFinalMetricsWithSfx();
        },
        playFinalSfx: function playFinalSfx() {
          return _this29._playFinalSfx();
        }
      });
    }
  }]);
}();
_defineProperty(BaseStaffGame, "MIN_CHALLENGES", 2);
_defineProperty(BaseStaffGame, "MAX_CHALLENGES", 12);
_defineProperty(BaseStaffGame, "LETTER_TO_SOLFEGE", {
  C: "Do",
  D: "Re",
  E: "Mi",
  F: "Fa",
  G: "Sol",
  A: "La",
  B: "Si"
});

/***/ },

/***/ "./resources/js/games/games/memorywizard/MemoryWizard.js"
/*!***************************************************************!*\
  !*** ./resources/js/games/games/memorywizard/MemoryWizard.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   MemoryWizard: () => (/* binding */ MemoryWizard)
/* harmony export */ });
/* harmony import */ var _base_BaseStaffGame_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../base/BaseStaffGame.js */ "./resources/js/games/games/base/BaseStaffGame.js");
/* harmony import */ var _shared_challengeUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../shared/challengeUtils.js */ "./resources/js/games/games/shared/challengeUtils.js");
/* harmony import */ var _staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../staff/staffUtils.js */ "./resources/js/games/staff/staffUtils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _superPropGet(t, o, e, r) { var p = _get(_getPrototypeOf(1 & r ? t.prototype : t), o, e); return 2 & r && "function" == typeof p ? function (t) { return p.apply(e, t); } : p; }
function _get() { return _get = "undefined" != typeof Reflect && Reflect.get ? Reflect.get.bind() : function (e, t, r) { var p = _superPropBase(e, t); if (p) { var n = Object.getOwnPropertyDescriptor(p, t); return n.get ? n.get.call(arguments.length < 3 ? e : r) : n.value; } }, _get.apply(null, arguments); }
function _superPropBase(t, o) { for (; !{}.hasOwnProperty.call(t, o) && null !== (t = _getPrototypeOf(t));); return t; }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }



var MemoryWizard = /*#__PURE__*/function (_BaseStaffGame) {
  function MemoryWizard() {
    var _this;
    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, MemoryWizard);
    var defaults = {
      staffEl: "#staff",
      basePoints: 1,
      firstTryBonus: 2,
      namespace: "memoryWizard",
      usePianoKeyboard: false
    };
    var merged = _objectSpread(_objectSpread({}, defaults), options || {});
    var clefPool = (0,_shared_challengeUtils_js__WEBPACK_IMPORTED_MODULE_1__.normalizeClefPool)(merged.clefs != null ? merged.clefs : merged.clef);
    _this = _callSuper(this, MemoryWizard, [_objectSpread(_objectSpread({}, merged), {}, {
      initialClef: clefPool && clefPool[0] ? clefPool[0] : "treble"
    })]);
    _this._clefPool = clefPool;
    _this._targetSequence = [];
    _this._lastTargetSignature = null;
    _this._previewTimeoutIds = [];
    _this._pendingRoundTimerStart = false;
    _this.$playWrap = null;
    _this.$playPlayBtn = null;
    _this.$playStopBtn = null;
    _this._roundPrepared = false;
    _this._previewPlaybackActive = false;
    _this._hasPlayedThisRound = false;
    _this._staffPlayStepOriginal = null;
    return _this;
  }
  _inherits(MemoryWizard, _BaseStaffGame);
  return _createClass(MemoryWizard, [{
    key: "start",
    value: function start() {
      var _this2 = this;
      this._resetSequenceState();
      _superPropGet(MemoryWizard, "start", this, 3)([]);
      this._patchStaffUserNotePlayback();
      this.$playWrap = $("#play");
      this.$playPlayBtn = this.$playWrap.find('button[action="play"]');
      this.$playStopBtn = this.$playWrap.find('button[action="stop"]');
      this.$playPlayBtn.off("click.".concat(this.ns, "Play")).on("click.".concat(this.ns, "Play"), function (e) {
        e.preventDefault();
        _this2._playPreparedRound();
      });
      this.$playStopBtn.off("click.".concat(this.ns, "Stop")).on("click.".concat(this.ns, "Stop"), function (e) {
        e.preventDefault();
        _this2._stopPreparedRoundPlayback();
      });
      this.$staffEl.off("staff:userNoteAdded._answerControls.".concat(this.ns, " staff:userNotesChanged._answerControls.").concat(this.ns)).on("staff:userNoteAdded._answerControls.".concat(this.ns, " staff:userNotesChanged._answerControls.").concat(this.ns), function (e, data) {
        return _this2._syncAnswerControls(Number(data === null || data === void 0 ? void 0 : data.count));
      });
      this._syncAnswerControls();
    }
  }, {
    key: "_displayNameForLetter",
    value: function _displayNameForLetter(letter) {
      var clean = String(letter || "").trim().toUpperCase();
      if (this._showSolfegeNoteNames()) return MemoryWizard.LETTER_TO_SOLFEGE[clean] || clean;
      return clean;
    }
  }, {
    key: "_readBoolSetting",
    value: function _readBoolSetting(key) {
      var _this$opts;
      var fallback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      var direct = (_this$opts = this.opts) === null || _this$opts === void 0 ? void 0 : _this$opts[key];
      if (direct !== undefined && direct !== null && String(direct).trim() !== "") {
        return this._normalizeOnOff(direct);
      }
      try {
        var qs = new URLSearchParams(window.location.search || "");
        if (qs.has(key)) return this._normalizeOnOff(qs.get(key));
      } catch (_) {}
      return !!fallback;
    }
  }, {
    key: "_targetAccidentalClass",
    value: function _targetAccidentalClass() {
      var _this$_duelRandom, _this$_duelRandom2;
      if (!this._readBoolSetting("allowAccidentals", false)) return null;
      var w = this.opts.accidentalWeights || {};
      var naturalWeight = Number(w.natural);
      var sharpWeight = Number(w.sharp);
      var flatWeight = Number(w.flat);
      if (Number.isFinite(naturalWeight) || Number.isFinite(sharpWeight) || Number.isFinite(flatWeight)) {
        return (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_2__.pickWeighted)([{
          value: null,
          weight: Number.isFinite(naturalWeight) ? naturalWeight : 0
        }, {
          value: "music-font__sharp",
          weight: Number.isFinite(sharpWeight) ? sharpWeight : 0
        }, {
          value: "music-font__flat",
          weight: Number.isFinite(flatWeight) ? flatWeight : 0
        }], this._duelRandom);
      }
      return ((_this$_duelRandom = (_this$_duelRandom2 = this._duelRandom) === null || _this$_duelRandom2 === void 0 ? void 0 : _this$_duelRandom2.call(this)) !== null && _this$_duelRandom !== void 0 ? _this$_duelRandom : Math.random()) < 0.5 ? "music-font__sharp" : "music-font__flat";
    }
  }, {
    key: "_pickTargetStep",
    value: function _pickTargetStep() {
      var _this$_duelRandom3, _this$_duelRandom4;
      var minStep = 0;
      var maxStep = 8;
      return Math.floor(((_this$_duelRandom3 = (_this$_duelRandom4 = this._duelRandom) === null || _this$_duelRandom4 === void 0 ? void 0 : _this$_duelRandom4.call(this)) !== null && _this$_duelRandom3 !== void 0 ? _this$_duelRandom3 : Math.random()) * (maxStep - minStep + 1)) + minStep;
    }
  }, {
    key: "_resetSequenceState",
    value: function _resetSequenceState() {
      this._clearPendingPreviewTimeouts();
      this._targetSequence = [];
      this._lastTargetSignature = null;
      this.maxUserNotes = 0;
      this._pendingRoundTimerStart = false;
      this._roundPrepared = false;
      this._previewPlaybackActive = false;
      this._hasPlayedThisRound = false;
    }
  }, {
    key: "_clearPendingPreviewTimeouts",
    value: function _clearPendingPreviewTimeouts() {
      if (!this._previewTimeoutIds.length) return;
      this._previewTimeoutIds.forEach(function (timeoutId) {
        return clearTimeout(timeoutId);
      });
      this._previewTimeoutIds = [];
    }
  }, {
    key: "_setMemoryWizardStaffInteractionDisabled",
    value: function _setMemoryWizardStaffInteractionDisabled(disabled) {
      var on = !!disabled;
      var $staff = $("#staff");
      var $accidentals = $("#accidentals");
      if (on) {
        $staff.attr("disabled", "disabled");
        $accidentals.attr("disabled", "disabled");
        $staff.attr("aria-disabled", "true");
        $accidentals.attr("aria-disabled", "true");
        $staff.css("pointer-events", "none");
        $accidentals.css("pointer-events", "none");
      } else {
        $staff.removeAttr("disabled");
        $accidentals.removeAttr("disabled");
        $staff.removeAttr("aria-disabled");
        $accidentals.removeAttr("aria-disabled");
        $staff.css("pointer-events", "");
        $accidentals.css("pointer-events", "");
      }
    }
  }, {
    key: "_patchStaffUserNotePlayback",
    value: function _patchStaffUserNotePlayback() {
      if (!this.staff || typeof this.staff._playStep !== "function" || this._staffPlayStepOriginal) return;
      this._staffPlayStepOriginal = this.staff._playStep.bind(this.staff);
      this.staff._playStep = /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              return _context.a(2);
          }
        }, _callee);
      }));
    }
  }, {
    key: "_setPlayButtons",
    value: function _setPlayButtons(isPlaying) {
      var _this$$playPlayBtn, _this$$playStopBtn;
      if ((_this$$playPlayBtn = this.$playPlayBtn) !== null && _this$$playPlayBtn !== void 0 && _this$$playPlayBtn.length) this.$playPlayBtn.toggle(!isPlaying);
      if ((_this$$playStopBtn = this.$playStopBtn) !== null && _this$$playStopBtn !== void 0 && _this$$playStopBtn.length) this.$playStopBtn.toggle(!!isPlaying);
    }
  }, {
    key: "_checkAfterUserNotes",
    value: function _checkAfterUserNotes() {
      return this._targetSequence.length || 1;
    }
  }, {
    key: "_setInstructions",
    value: function _setInstructions(message) {
      this.instructionsUi.show().setHtml(message, {
        animate: false
      });
    }
  }, {
    key: "_removeInstructions",
    value: function _removeInstructions() {
      this.$instructions.show();
      this._instructionsRemoved = true;
    }
  }, {
    key: "_restoreInstructions",
    value: function _restoreInstructions() {
      this.$instructions.show();
      this._instructionsRemoved = false;
    }
  }, {
    key: "_syncAnswerControls",
    value: function _syncAnswerControls() {
      var _this$$playWrap, _this$$playWrap$toggl, _this$$checkWrap, _this$$checkWrap$togg, _this$$checkWrap$togg2, _this$$checkWrap$togg3;
      var count = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this._currentUserNoteCount();
      var userNoteCount = Number.isFinite(count) ? count : this._currentUserNoteCount();
      var hasAnswer = userNoteCount >= this._checkAfterUserNotes();
      var roundControlsHidden = $("#continue").is(":visible") || $("#final-overlay").is(":visible");
      var showPlay = !roundControlsHidden && !hasAnswer;
      var showCheck = !roundControlsHidden && hasAnswer;
      (_this$$playWrap = this.$playWrap) === null || _this$$playWrap === void 0 || (_this$$playWrap$toggl = _this$$playWrap.toggle) === null || _this$$playWrap$toggl === void 0 || _this$$playWrap$toggl.call(_this$$playWrap, showPlay);
      (_this$$checkWrap = this.$checkWrap) === null || _this$$checkWrap === void 0 || (_this$$checkWrap$togg = _this$$checkWrap.toggle) === null || _this$$checkWrap$togg === void 0 || (_this$$checkWrap$togg2 = (_this$$checkWrap$togg3 = _this$$checkWrap$togg.call(_this$$checkWrap, showCheck)).toggleClass) === null || _this$$checkWrap$togg2 === void 0 || _this$$checkWrap$togg2.call(_this$$checkWrap$togg3, "invisible", !showCheck);
      if (!roundControlsHidden) {
        var remaining = this._checkAfterUserNotes() - userNoteCount;
        this._setInstructions(hasAnswer ? "When you’re ready, check your answer." : this._previewPlaybackActive ? "Listen closely…" : !this._hasPlayedThisRound ? "Press Play when you’re ready." : remaining === 1 ? "Add the note you heard." : "Add the ".concat(remaining, " notes you heard in order."));
      }
    }
  }, {
    key: "_setPreviewInteractionDisabled",
    value: function _setPreviewInteractionDisabled(disabled) {
      this._setMemoryWizardStaffInteractionDisabled(disabled);
      if (!disabled) {
        this._previewPlaybackActive = false;
        this._setPlayButtons(false);
        this._startPendingRoundTimerIfNeeded();
        this._syncAnswerControls();
      }
    }
  }, {
    key: "_primeRoundTimerIfEnabled",
    value: function _primeRoundTimerIfEnabled() {
      var _this$$timer, _this$$timer$show;
      if (!this._isTimerEnabled()) return;
      (_this$$timer = this.$timer) === null || _this$$timer === void 0 || (_this$$timer$show = _this$$timer.show) === null || _this$$timer$show === void 0 || _this$$timer$show.call(_this$$timer);
      this._stopGameTimer();
      this._timerRemainingSec = this._timerLimitSeconds();
      this._renderTimerDisplay();
      this._pendingRoundTimerStart = false;
    }
  }, {
    key: "_startPendingRoundTimerIfNeeded",
    value: function _startPendingRoundTimerIfNeeded() {
      if (!this._pendingRoundTimerStart || !this._isTimerEnabled()) return;
      this._pendingRoundTimerStart = false;
      this._startGameTimer(this._timerLimitSeconds());
    }
  }, {
    key: "_shouldHideLastPreviewNote",
    value: function _shouldHideLastPreviewNote(index) {
      if (!this._readBoolSetting("hideLastNote", false)) return false;
      if (this._targetSequence.length <= 1) return false;
      return index === this._targetSequence.length - 1;
    }
  }, {
    key: "_shouldRenderPreviewNote",
    value: function _shouldRenderPreviewNote(index) {
      if (this._targetSequence.length <= 1) return true;
      if (index < this._targetSequence.length - 1) return false;
      return !this._shouldHideLastPreviewNote(index);
    }
  }, {
    key: "_previewDisplayMsForIndex",
    value: function _previewDisplayMsForIndex(index) {
      return MemoryWizard.SEQUENCE_NOTE_DISPLAY_MS;
    }
  }, {
    key: "_resetRoundTimerIfEnabled",
    value: function _resetRoundTimerIfEnabled() {
      if (!this._isTimerEnabled()) return;
      this._primeRoundTimerIfEnabled();
    }
  }, {
    key: "_removeTargetPreview",
    value: function _removeTargetPreview(noteId) {
      if (!noteId) return;
      this.staff.removeNote(noteId);
    }
  }, {
    key: "_stopPreparedRoundPlayback",
    value: function _stopPreparedRoundPlayback() {
      this._clearPendingPreviewTimeouts();
      this._previewPlaybackActive = false;
      this._hasPlayedThisRound = false;
      this._pendingRoundTimerStart = false;
      this.staff.clearNotes();
      this._setPreviewInteractionDisabled(true);
      this._setPlayButtons(false);
      this._syncAnswerControls(0);
    }
  }, {
    key: "_playTargetSound",
    value: function _playTargetSound(target) {
      var _this$staff$_accident, _this$staff;
      if (!target || !this._staffPlayStepOriginal) return;
      var accidentalOffset = ((_this$staff$_accident = (_this$staff = this.staff)._accidentalClassToOffset) === null || _this$staff$_accident === void 0 ? void 0 : _this$staff$_accident.call(_this$staff, target.accidentalClass)) || 0;
      this._staffPlayStepOriginal(target.step, accidentalOffset);
    }
  }, {
    key: "_showPreviewNote",
    value: function _showPreviewNote(target) {
      var _this3 = this;
      if (!target) return null;
      var fixedId = this.staff.addNote({
        step: target.step
      });
      if (!fixedId) return null;
      var accidentalClass = target.accidentalClass || null;
      if (accidentalClass) {
        this.staff.attachAccidentalToNote(fixedId, accidentalClass);
        this.$staffEl.find(".accidental[data-for-note-id=\"".concat(fixedId, "\"]")).hide();
      }
      this.staff.setNoteFixed(fixedId, true);
      if (!accidentalClass) {
        this.staff._emitNoteState(fixedId, "fixed");
        this._playTargetSound(target);
        return fixedId;
      }
      var timeoutId = window.setTimeout(function () {
        _this3.$staffEl.find(".accidental[data-for-note-id=\"".concat(fixedId, "\"]")).show();
        _this3.staff._emitNoteState(fixedId, "fixed");
        _this3._playTargetSound(target);
      }, MemoryWizard.PREVIEW_NOTE_ANIMATION_MS);
      this._previewTimeoutIds.push(timeoutId);
      return fixedId;
    }
  }, {
    key: "_showTargetPreviewSequence",
    value: function _showTargetPreviewSequence() {
      var _this4 = this;
      if (!this._targetSequence.length) return;
      this._previewPlaybackActive = true;
      this._setPlayButtons(true);
      this._setPreviewInteractionDisabled(true);
      this._syncAnswerControls(0);
      this._clearPendingPreviewTimeouts();
      var _showAtIndex = function showAtIndex(index) {
        var target = _this4._targetSequence[index];
        if (!target) {
          _this4._setPreviewInteractionDisabled(false);
          return;
        }
        var shouldRender = _this4._shouldRenderPreviewNote(index);
        var fixedId = shouldRender ? _this4._showPreviewNote(target) : null;
        if (!shouldRender) _this4._playTargetSound(target);
        var timeoutId = window.setTimeout(function () {
          _this4._removeTargetPreview(fixedId);
          if (index >= _this4._targetSequence.length - 1) {
            _this4._setPreviewInteractionDisabled(false);
            return;
          }
          _showAtIndex(index + 1);
        }, _this4._previewDisplayMsForIndex(index));
        _this4._previewTimeoutIds.push(timeoutId);
      };
      _showAtIndex(0);
    }
  }, {
    key: "_playPreparedRound",
    value: function _playPreparedRound() {
      if (!this._roundPrepared || !this._targetSequence.length || this._previewPlaybackActive) return;
      this.staff.clearNotes();
      this.maxUserNotes = this._targetSequence.length;
      if (this._isTimerEnabled()) this._pendingRoundTimerStart = true;
      this._hasPlayedThisRound = true;
      this._showTargetPreviewSequence();
    }
  }, {
    key: "_targetSignature",
    value: function _targetSignature(target) {
      if (!target) return "";
      return [String(target.letter || ""), String(target.accidentalClass || ""), String(target.step), String(target.octave)].join("|");
    }
  }, {
    key: "_sequenceHasTarget",
    value: function _sequenceHasTarget(target) {
      var _this5 = this;
      var signature = this._targetSignature(target);
      return this._targetSequence.some(function (note) {
        return _this5._targetSignature(note) === signature;
      });
    }
  }, {
    key: "_pickTargetNote",
    value: function _pickTargetNote() {
      var maxAttempts = 24;
      for (var attempt = 0; attempt < maxAttempts; attempt += 1) {
        var step = this._pickTargetStep();
        var noteState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_2__.stepToLetterOctave)(this.staff, step);
        var target = {
          step: step,
          letter: noteState.letter,
          octave: noteState.octave,
          accidentalClass: this._targetAccidentalClass()
        };
        if (this._targetSignature(target) !== this._lastTargetSignature && !this._sequenceHasTarget(target)) {
          return target;
        }
      }
      var minStep = this.staff.minStepAllowed();
      var maxStep = this.staff.maxStepAllowed();
      for (var _step = minStep; _step <= maxStep; _step += 1) {
        var _fallbackState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_2__.stepToLetterOctave)(this.staff, _step);
        var accidentalOptions = this._readBoolSetting("allowAccidentals", false) ? [null, "music-font__sharp", "music-font__flat"] : [null];
        for (var i = 0; i < accidentalOptions.length; i += 1) {
          var _target = {
            step: _step,
            letter: _fallbackState.letter,
            octave: _fallbackState.octave,
            accidentalClass: accidentalOptions[i]
          };
          if (this._targetSignature(_target) !== this._lastTargetSignature && !this._sequenceHasTarget(_target)) {
            return _target;
          }
        }
      }
      var fallbackStep = this._pickTargetStep();
      var fallbackState = (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_2__.stepToLetterOctave)(this.staff, fallbackStep);
      return {
        step: fallbackStep,
        letter: fallbackState.letter,
        octave: fallbackState.octave,
        accidentalClass: this._targetAccidentalClass()
      };
    }
  }, {
    key: "newChallenge",
    value: function newChallenge() {
      var _this$$doublePoints, _this$$doublePoints$h;
      if (!this._targetSequence.length) {
        var clef = (0,_shared_challengeUtils_js__WEBPACK_IMPORTED_MODULE_1__.pickChallengeClef)(this._clefPool, this._duelRandom);
        if (clef && clef !== this.staff.getClef()) this.staff.setClef(clef);
      }
      this._madeMistakeThisRound = false;
      this._usedHintThisRound = false;
      this._clearPendingPreviewTimeouts();
      this._pendingRoundTimerStart = false;
      this._setPreviewInteractionDisabled(true);
      this.staff.clearNotes();
      this.$feedback.hide();
      this.$helpBtn.hide();
      this.$bonusBadge.hide();
      (_this$$doublePoints = this.$doublePoints) === null || _this$$doublePoints === void 0 || (_this$$doublePoints$h = _this$$doublePoints.hide) === null || _this$$doublePoints$h === void 0 || _this$$doublePoints$h.call(_this$$doublePoints);
      var nextTarget = this._pickTargetNote();
      this._targetSequence.push(nextTarget);
      this._lastTargetSignature = this._targetSignature(nextTarget);
      this.maxUserNotes = 0;
      this._roundPrepared = true;
      this._hasPlayedThisRound = false;
      $("#continue").hide();
      this._setPlayButtons(false);
      this._syncAnswerControls(0);
    }
  }, {
    key: "_collectUserNotes",
    value: function _collectUserNotes() {
      var _this6 = this;
      return this.$staffEl.find(".note").toArray().map(function (el) {
        var _this6$staff$_getAtta, _this6$staff;
        var $note = $(el);
        var noteId = String($note.attr("data-note-id") || "");
        if (!noteId || _this6.staff.isNoteFixed(noteId)) return null;
        var top = parseFloat($note.css("top"));
        var step = Number.isFinite(top) ? _this6.staff.yToStep(top) : null;
        var accidentalClass = ((_this6$staff$_getAtta = (_this6$staff = _this6.staff)._getAttachedAccidentalClass) === null || _this6$staff$_getAtta === void 0 ? void 0 : _this6$staff$_getAtta.call(_this6$staff, noteId)) || null;
        return {
          noteId: noteId,
          step: step,
          accidentalClass: accidentalClass
        };
      }).filter(Boolean);
    }
  }, {
    key: "_isUserAnswerCorrect",
    value: function _isUserAnswerCorrect() {
      var targetSequence = this._targetSequence;
      if (!targetSequence.length) return false;
      var notes = this._collectUserNotes();
      if (notes.length !== targetSequence.length) return false;
      return notes.every(function (note, index) {
        var target = targetSequence[index];
        if (!target) return false;
        return Number(note.step) === Number(target.step) && String(note.accidentalClass || "") === String(target.accidentalClass || "");
      });
    }
  }, {
    key: "_computeHintAnswers",
    value: function _computeHintAnswers() {
      if (!this._targetSequence.length) return [{
        step: null,
        accidentalClass: null
      }];
      return this._targetSequence.map(function (target) {
        return {
          step: target.step,
          accidentalClass: target.accidentalClass || null
        };
      });
    }
  }, {
    key: "_showHintNote",
    value: function _showHintNote() {
      var _this7 = this;
      this._removeAllHintNotes();
      this._removeAllUserNotesForHint();
      this._clearPendingPreviewTimeouts();
      this.staff.clearNotes();
      this._activeHintIds = [];
      this._setPreviewInteractionDisabled(true);
      var answers = this._computeHintAnswers();
      var specs = Array.isArray(answers) && answers.length ? answers : [{
        step: null,
        accidentalClass: null
      }];
      var _showAtIndex2 = function showAtIndex(index) {
        var ans = specs[index];
        if (!ans) {
          _this7._setPreviewInteractionDisabled(false);
          return;
        }
        var step = Number.isFinite(ans.step) ? Number(ans.step) : null;
        var accidentalClass = ans.accidentalClass || null;
        if (step == null) step = _this7._randomFreeStep();
        if (step == null) {
          _this7._setPreviewInteractionDisabled(false);
          return;
        }
        var noteId = ans.id || (specs.length === 1 ? "hint" : "hint".concat(index + 1));
        var createdId = _this7.staff.addNote({
          id: noteId,
          step: step,
          className: "hint blink",
          allowOccupied: true,
          skipResolve: true
        });
        if (!createdId) {
          _this7._setPreviewInteractionDisabled(false);
          return;
        }
        _this7._activeHintIds.push(createdId);
        if (accidentalClass) {
          _this7.staff.attachAccidentalToNote(createdId, accidentalClass);
          _this7.$staffEl.find(".accidental[data-for-note-id=\"".concat(createdId, "\"]")).addClass("hint blink");
        }
        _this7.$staffEl.find(".ledger[data-for-note-id=\"".concat(createdId, "\"]")).addClass("hint blink");
        _this7._playTargetSound({
          step: step,
          accidentalClass: accidentalClass
        });
        var timeoutId = window.setTimeout(function () {
          _this7.staff.removeNote(createdId);
          _this7._activeHintIds = (_this7._activeHintIds || []).filter(function (id) {
            return id !== createdId;
          });
          if (index >= specs.length - 1) {
            _this7._setPreviewInteractionDisabled(false);
            return;
          }
          _showAtIndex2(index + 1);
        }, MemoryWizard.SEQUENCE_NOTE_DISPLAY_MS);
        _this7._previewTimeoutIds.push(timeoutId);
      };
      _showAtIndex2(0);
    }
  }, {
    key: "_onCheck",
    value: function _onCheck() {
      this.$checkBtn.disable();
      this._stats.checksTotal += 1;
      if (this._isUserAnswerCorrect()) {
        this._stats.checksCorrect += 1;
        this._pauseGameTimer();
        var _this$_awardPointsFor = this._awardPointsForCorrect(),
          earned = _this$_awardPointsFor.earned,
          bonusEarned = _this$_awardPointsFor.bonusEarned;
        this._handleCorrectAnswerUi({
          isBonus: bonusEarned > 0,
          earned: earned,
          $prompt: $(),
          $extraHide: this.$playWrap
        });
        return;
      }
      this._madeAnyMistake = true;
      this._madeMistakeThisRound = true;
      this._shakeWrongUserStaffNotes();
      this._failAnimation(this.$checkWrap);
      this.$helpBtn.show();
    }
  }]);
}(_base_BaseStaffGame_js__WEBPACK_IMPORTED_MODULE_0__.BaseStaffGame);
_defineProperty(MemoryWizard, "PREVIEW_NOTE_ANIMATION_MS", 360);
_defineProperty(MemoryWizard, "SEQUENCE_NOTE_DISPLAY_MS", 1200);
_defineProperty(MemoryWizard, "LETTER_TO_SOLFEGE", {
  C: "Do",
  D: "Re",
  E: "Mi",
  F: "Fa",
  G: "Sol",
  A: "La",
  B: "Si"
});

/***/ },

/***/ "./resources/js/games/games/shared/GameAudio.js"
/*!******************************************************!*\
  !*** ./resources/js/games/games/shared/GameAudio.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GameAudio: () => (/* binding */ GameAudio)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var GameAudio = /*#__PURE__*/function () {
  function GameAudio() {
    _classCallCheck(this, GameAudio);
  }
  return _createClass(GameAudio, null, [{
    key: "scale",
    value: function scale(kind) {
      var base = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
      var mult = Number(GameAudio.VELOCITY[kind]);
      return (Number.isFinite(mult) ? mult : 1) * (Number(base) || 0);
    }
  }, {
    key: "getSoundLibrary",
    value: function getSoundLibrary() {
      return GameAudio.SOUND_LIBRARY.map(function (sound) {
        return _objectSpread(_objectSpread({}, sound), {}, {
          valuePercent: GameAudio.getVelocityPercent(sound.volumeKey)
        });
      });
    }
  }, {
    key: "getVelocityPercent",
    value: function getVelocityPercent(kind) {
      var value = Number(GameAudio.VELOCITY[kind]);
      if (!Number.isFinite(value)) return 0;
      return Math.max(0, Math.min(100, Math.round(value * 100)));
    }
  }, {
    key: "setVelocityPercent",
    value: function setVelocityPercent(kind, percent) {
      var next = Math.max(0, Math.min(100, Number(percent) || 0));
      if (!Object.prototype.hasOwnProperty.call(GameAudio.VELOCITY, kind)) return 0;
      GameAudio.VELOCITY[kind] = next / 100;
      return next;
    }
  }, {
    key: "previewSound",
    value: function () {
      var _previewSound = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(soundId) {
        var _previewers$soundId;
        var previewers;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              if (window.Tone) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              _context.n = 2;
              return Tone.start();
            case 2:
              previewers = {
                staffNote: function staffNote() {
                  GameAudio._getPreviewSynth("staffNote", function () {
                    return GameAudio.createStaffNoteSynth();
                  }).triggerAttackRelease("C4", 0.5, undefined, GameAudio.scale("staffNote", 1));
                },
                dictation: function dictation() {
                  GameAudio._getPreviewSynth("dictation", function () {
                    return GameAudio.createDictationSynth();
                  }).triggerAttackRelease(["C4", "E4"], 0.3, undefined, GameAudio.scale("dictation", 1));
                },
                sequence: function sequence() {
                  GameAudio._getPreviewSynth("sequence", function () {
                    return GameAudio.createSequenceSynth();
                  }).triggerAttackRelease(["C4", "G4"], 0.26, undefined, GameAudio.scale("sequence", 1));
                },
                successBasic: function successBasic() {
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var now = Tone.now();
                  ["C6", "E6", "G6"].forEach(function (n, i) {
                    synth.triggerAttackRelease(n, 0.07, now + i * 0.05, GameAudio.scale("successBasic", 0.42));
                  });
                },
                successBonus: function successBonus() {
                  var _synth$get$oscillator;
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var now = Tone.now();
                  var oldEnv = _objectSpread({}, synth.get().envelope);
                  var oldOsc = (_synth$get$oscillator = synth.get().oscillator) === null || _synth$get$oscillator === void 0 ? void 0 : _synth$get$oscillator.type;
                  try {
                    synth.set({
                      oscillator: {
                        type: "sine"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.12,
                        sustain: 0.15,
                        release: 0.65
                      }
                    });
                  } catch (_) {}
                  var semitoneShift = 3;
                  var toNote = function toNote(midi) {
                    return Tone.Frequency(midi, "midi").toNote();
                  };
                  [62, 66, 69, 73, 74].map(function (m) {
                    return toNote(m + semitoneShift);
                  }).forEach(function (n, i) {
                    synth.triggerAttackRelease(n, 0.06, now + i * 0.045, GameAudio.scale("successBonus", 0.45));
                  });
                  [62, 69, 74, 78].map(function (m) {
                    return toNote(m + semitoneShift);
                  }).forEach(function (n) {
                    synth.triggerAttackRelease(n, 0.12, now + 0.26, GameAudio.scale("successBonus", 0.30));
                  });
                  setTimeout(function () {
                    try {
                      synth.set({
                        oscillator: {
                          type: oldOsc || "triangle"
                        },
                        envelope: oldEnv
                      });
                    } catch (_) {}
                  }, 600);
                },
                failNoise: function failNoise() {
                  GameAudio._getPreviewSynth("uiNoise", function () {
                    return GameAudio.createUiNoiseSynth();
                  }).triggerAttackRelease(0.06, Tone.now(), GameAudio.scale("failNoise", 0.45));
                },
                failNote: function failNote() {
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var now = Tone.now();
                  synth.triggerAttackRelease("A2", 0.10, now + 0.01, GameAudio.scale("failNote", 0.55));
                  synth.triggerAttackRelease("G2", 0.12, now + 0.08, GameAudio.scale("failNote", 0.6));
                },
                bombFail: function bombFail() {
                  var _synth$get$oscillator2;
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var noiseSynth = GameAudio._getPreviewSynth("uiNoise", function () {
                    return GameAudio.createUiNoiseSynth();
                  });
                  var now = Tone.now();
                  var oldEnv = _objectSpread({}, synth.get().envelope);
                  var oldOsc = (_synth$get$oscillator2 = synth.get().oscillator) === null || _synth$get$oscillator2 === void 0 ? void 0 : _synth$get$oscillator2.type;
                  try {
                    synth.set({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.16,
                        sustain: 0.08,
                        release: 0.38
                      }
                    });
                  } catch (_) {}
                  ["E5", "D5", "C5", "A4", "G4", "E4", "D4", "B3", "A3", "F3", "E3"].forEach(function (n, i) {
                    var when = now + i * 0.17;
                    synth.triggerAttackRelease(n, 0.15, when, GameAudio.scale("bombFail", 0.42));
                    if (i < 8) noiseSynth.triggerAttackRelease(0.05, when + 0.015, GameAudio.scale("bombFail", 0.14));
                  });
                  setTimeout(function () {
                    try {
                      synth.set({
                        oscillator: {
                          type: oldOsc || "triangle"
                        },
                        envelope: oldEnv
                      });
                    } catch (_) {}
                  }, 2200);
                },
                wallCrash: function wallCrash() {
                  var synth = GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  });
                  var noiseSynth = GameAudio._getPreviewSynth("uiNoise", function () {
                    return GameAudio.createUiNoiseSynth();
                  });
                  var now = Tone.now();
                  noiseSynth.triggerAttackRelease(0.12, now, GameAudio.scale("wallCrash", 0.32));
                  noiseSynth.triggerAttackRelease(0.09, now + 0.045, GameAudio.scale("wallCrash", 0.22));
                  synth.triggerAttackRelease("G3", 0.08, now, GameAudio.scale("wallCrash", 0.85));
                  synth.triggerAttackRelease("D3", 0.12, now + 0.04, GameAudio.scale("wallCrash", 0.7));
                  synth.triggerAttackRelease("A2", 0.18, now + 0.11, GameAudio.scale("wallCrash", 0.62));
                },
                "final": function _final() {
                  GameAudio.playFinalResults();
                },
                finalMetric: function finalMetric() {
                  var synth = GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  });
                  var now = Tone.now();
                  synth.triggerAttackRelease("G5", 0.055, now, GameAudio.scale("finalMetric", 0.44));
                  synth.triggerAttackRelease("C6", 0.045, now + 0.03, GameAudio.scale("finalMetric", 0.34));
                },
                perfectBonus: function perfectBonus() {
                  var _synth$get$oscillator3;
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var now = Tone.now();
                  var oldEnv = _objectSpread({}, synth.get().envelope);
                  var oldOsc = (_synth$get$oscillator3 = synth.get().oscillator) === null || _synth$get$oscillator3 === void 0 ? void 0 : _synth$get$oscillator3.type;
                  try {
                    synth.set({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.01,
                        decay: 0.18,
                        sustain: 0.25,
                        release: 0.8
                      }
                    });
                  } catch (_) {}
                  ["C5", "E5", "G5", "C6", "E6", "G6", "C7"].forEach(function (n, i) {
                    synth.triggerAttackRelease(n, 0.09, now + i * 0.06, GameAudio.scale("perfectBonus", 0.62));
                  });
                  setTimeout(function () {
                    try {
                      synth.set({
                        oscillator: {
                          type: oldOsc || "triangle"
                        },
                        envelope: oldEnv
                      });
                    } catch (_) {}
                  }, 1400);
                },
                runStart: function runStart() {
                  var synth = GameAudio._getPreviewSynth("uiPoly", function () {
                    return GameAudio.createUiPolySynth();
                  });
                  var now = Tone.now();
                  var toNote = function toNote(m) {
                    return Tone.Frequency(m, "midi").toNote();
                  };
                  [[[0, 7], 0.00, 0.19, 0.20], [[3, 7], 0.24, 0.06, 0.18], [[0, 5, 10], 0.38, 0.18, 0.22], [[2, 5, 9], 0.62, 0.06, 0.18], [[0, 7, 12], 0.76, 0.15, 0.22]].forEach(function (_ref) {
                    var _ref2 = _slicedToArray(_ref, 4),
                      intervals = _ref2[0],
                      t = _ref2[1],
                      dur = _ref2[2],
                      vel = _ref2[3];
                    synth.triggerAttackRelease(intervals.map(function (i) {
                      return toNote(60 + i);
                    }), dur, now + t, GameAudio.scale("runStart", vel));
                  });
                },
                timerBeep: function timerBeep() {
                  GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  }).triggerAttackRelease("C6", 0.06, Tone.now(), GameAudio.scale("timerBeep", 0.5));
                },
                timerTimeUp: function timerTimeUp() {
                  var timerSynth = GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  });
                  var noiseSynth = GameAudio._getPreviewSynth("uiNoise", function () {
                    return GameAudio.createUiNoiseSynth();
                  });
                  var now = Tone.now();
                  noiseSynth.triggerAttackRelease(0.12, now, GameAudio.scale("timerTimeUp", 0.2));
                  timerSynth.triggerAttackRelease("G4", 0.11, now, GameAudio.scale("timerTimeUp", 0.72));
                  timerSynth.triggerAttackRelease("E4", 0.13, now + 0.10, GameAudio.scale("timerTimeUp", 0.76));
                  timerSynth.triggerAttackRelease("C4", 0.18, now + 0.22, GameAudio.scale("timerTimeUp", 0.82));
                },
                countdownBeep: function countdownBeep() {
                  GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  }).triggerAttackRelease("B5", 0.06, Tone.now(), GameAudio.scale("countdownBeep", 0.2));
                },
                metronomeBeat: function metronomeBeat() {
                  GameAudio.playMetronomeClick(false);
                },
                metronomeDownbeat: function metronomeDownbeat() {
                  GameAudio.playMetronomeClick(true);
                },
                rhythmHit: function rhythmHit() {
                  GameAudio.playRhythmHit();
                },
                hinge: function hinge() {
                  var noiseSynth = GameAudio._getPreviewSynth("uiNoise", function () {
                    return GameAudio.createUiNoiseSynth();
                  });
                  var synth = GameAudio._getPreviewSynth("uiTimer", function () {
                    return GameAudio.createUiTimerSynth();
                  });
                  var now = Tone.now();
                  noiseSynth.triggerAttackRelease(0.04, now, GameAudio.scale("hinge", 0.07));
                  synth.triggerAttackRelease("E4", 0.04, now, GameAudio.scale("hinge", 0.12));
                  synth.triggerAttackRelease("C4", 0.05, now + 0.04, GameAudio.scale("hinge", 0.16));
                },
                notePythonMelody: function notePythonMelody() {
                  var lead = GameAudio._getPreviewSynth("notePythonLead", function () {
                    return new Tone.Synth({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.06,
                        sustain: 0.3,
                        release: 0.07
                      },
                      volume: -23
                    }).toDestination();
                  });
                  var arp = GameAudio._getPreviewSynth("notePythonArp", function () {
                    return new Tone.Synth({
                      oscillator: {
                        type: "square"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.06,
                        sustain: 0.3,
                        release: 0.04
                      },
                      volume: -30
                    }).toDestination();
                  });
                  var now = Tone.now();
                  ["E4", "G4", "C5", "G4"].forEach(function (note, i) {
                    lead.triggerAttackRelease(note, 0.18, now + i * 0.2, GameAudio.scale("notePythonMelody", 0.52));
                    arp.triggerAttackRelease(["C4", "E4", "G4", "B4"][i], 0.07, now + i * 0.2 + 0.1, GameAudio.scale("notePythonMelody", 0.5));
                  });
                },
                notePythonLowEnd: function notePythonLowEnd() {
                  var bass = GameAudio._getPreviewSynth("notePythonBass", function () {
                    return new Tone.Synth({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.06,
                        sustain: 0.3,
                        release: 0.06
                      },
                      volume: -16
                    }).toDestination();
                  });
                  var keys = GameAudio._getPreviewSynth("notePythonKeys", function () {
                    return new Tone.PolySynth(Tone.Synth, {
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.008,
                        decay: 0.12,
                        sustain: 0.2,
                        release: 0.09
                      },
                      volume: -27
                    }).toDestination();
                  });
                  var now = Tone.now();
                  bass.triggerAttackRelease("C2", 0.42, now, GameAudio.scale("notePythonLowEnd", 0.75));
                  bass.triggerAttackRelease("G2", 0.32, now + 0.4, GameAudio.scale("notePythonLowEnd", 0.68));
                  keys.triggerAttackRelease(["C3", "E3", "G3"], 0.5, now, GameAudio.scale("notePythonLowEnd", 0.55));
                },
                notePythonDrums: function notePythonDrums() {
                  var kick = GameAudio._getPreviewSynth("notePythonKick", function () {
                    return new Tone.MembraneSynth({
                      pitchDecay: 0.025,
                      octaves: 2,
                      oscillator: {
                        type: "sine"
                      },
                      envelope: {
                        attack: 0.001,
                        decay: 0.15,
                        sustain: 0,
                        release: 0.04
                      },
                      volume: -17
                    }).toDestination();
                  });
                  var snare = GameAudio._getPreviewSynth("notePythonSnare", function () {
                    return new Tone.NoiseSynth({
                      noise: {
                        type: "pink"
                      },
                      envelope: {
                        attack: 0.001,
                        decay: 0.07,
                        sustain: 0,
                        release: 0.02
                      },
                      volume: -26
                    }).toDestination();
                  });
                  var hats = GameAudio._getPreviewSynth("notePythonHats", function () {
                    return new Tone.NoiseSynth({
                      noise: {
                        type: "white"
                      },
                      envelope: {
                        attack: 0.001,
                        decay: 0.012,
                        sustain: 0,
                        release: 0.008
                      },
                      volume: -38
                    }).toDestination();
                  });
                  var now = Tone.now();
                  [0, 0.2, 0.4, 0.6].forEach(function (offset, i) {
                    if (i % 2 === 0) kick.triggerAttackRelease("C1", 0.06, now + offset, GameAudio.scale("notePythonDrums", 0.8));else snare.triggerAttackRelease(0.045, now + offset, GameAudio.scale("notePythonDrums", 0.65));
                    hats.triggerAttackRelease(0.01, now + offset + 0.1, GameAudio.scale("notePythonDrums", 0.3));
                  });
                },
                notePythonVictory: function notePythonVictory() {
                  var lead = GameAudio._getPreviewSynth("notePythonVictoryLead", function () {
                    return new Tone.Synth({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.06,
                        sustain: 0.3,
                        release: 0.07
                      },
                      volume: -23
                    }).toDestination();
                  });
                  var bass = GameAudio._getPreviewSynth("notePythonVictoryBass", function () {
                    return new Tone.Synth({
                      oscillator: {
                        type: "triangle"
                      },
                      envelope: {
                        attack: 0.004,
                        decay: 0.06,
                        sustain: 0.3,
                        release: 0.06
                      },
                      volume: -16
                    }).toDestination();
                  });
                  var now = Tone.now();
                  [[0, 0.09, "E4"], [0.125, 0.09, "G4"], [0.25, 0.175, "C5"]].forEach(function (_ref3) {
                    var _ref4 = _slicedToArray(_ref3, 3),
                      offset = _ref4[0],
                      duration = _ref4[1],
                      note = _ref4[2];
                    lead.triggerAttackRelease(note, duration, now + offset, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonMelody", 0.5)));
                  });
                  bass.triggerAttackRelease("C2", 0.175, now + 0.25, GameAudio.scale("notePythonVictory", GameAudio.scale("notePythonLowEnd", 0.7)));
                }
              };
              (_previewers$soundId = previewers[soundId]) === null || _previewers$soundId === void 0 || _previewers$soundId.call(previewers);
            case 3:
              return _context.a(2);
          }
        }, _callee);
      }));
      function previewSound(_x) {
        return _previewSound.apply(this, arguments);
      }
      return previewSound;
    }()
  }, {
    key: "createUiPolySynth",
    value: function createUiPolySynth() {
      return new Tone.PolySynth(Tone.Synth, {
        oscillator: {
          type: "triangle"
        },
        envelope: {
          attack: 0.005,
          decay: 0.12,
          sustain: 0.0,
          release: 0.25
        },
        volume: GameAudio.SYNTH_VOLUME_DB.uiPoly
      }).toDestination();
    }
  }, {
    key: "playFinalResults",
    value: function playFinalResults() {
      if (!window.Tone) return;
      var synth = GameAudio._getPreviewSynth("finalResults", function () {
        return new Tone.PolySynth(Tone.Synth, {
          oscillator: {
            type: "sine"
          },
          envelope: {
            attack: 0.02,
            decay: 0.25,
            sustain: 0.35,
            release: 0.9
          },
          volume: GameAudio.SYNTH_VOLUME_DB.uiPoly
        }).toDestination();
      });
      var now = Tone.now();
      var variants = [function () {
        ["C4", "G4", "C5", "E5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.9, now, GameAudio.scale("final", 0.6));
        });
        ["G5", "A5", "B5", "C6", "D6", "E6", "G6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.08, now + 0.25 + index * 0.06, GameAudio.scale("final", 0.35));
        });
      }, function () {
        ["C5", "E5", "G5", "B5", "D6", "G6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.11, now + index * 0.08, GameAudio.scale("final", 0.44));
        });
        ["C6", "E6", "G6"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.28, now + 0.62, GameAudio.scale("final", 0.5));
        });
      }, function () {
        ["A3", "E4", "A4", "C5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.45, now, GameAudio.scale("final", 0.45));
        });
        ["F4", "A4", "C5", "F5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.52, now + 0.35, GameAudio.scale("final", 0.48));
        });
        ["C5", "F5", "A5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.24, now + 0.78, GameAudio.scale("final", 0.4));
        });
      }, function () {
        ["E5", "G5", "A5", "B5", "D6", "E6", "G6", "A6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.075, now + index * 0.055, GameAudio.scale("final", 0.34));
        });
        ["A5", "A6", "C7"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.2, now + 0.52, GameAudio.scale("final", 0.42));
        });
      }, function () {
        ["D4", "A4", "D5", "F#5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.36, now, GameAudio.scale("final", 0.45));
        });
        ["D5", "F#5", "A5", "D6", "F#6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.095, now + 0.2 + index * 0.065, GameAudio.scale("final", 0.4));
        });
      }, function () {
        ["G3", "D4", "G4", "B4"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.4, now, GameAudio.scale("final", 0.4));
        });
        ["C4", "E4", "G4", "C5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.44, now + 0.32, GameAudio.scale("final", 0.46));
        });
        ["E5", "G5", "C6"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.22, now + 0.72, GameAudio.scale("final", 0.4));
        });
      }, function () {
        ["C6", "D6", "E6", "G6", "A6", "G6", "E6", "C7"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.06, now + index * 0.048, GameAudio.scale("final", 0.3));
        });
        ["G6", "C7"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.2, now + 0.48, GameAudio.scale("final", 0.38));
        });
      }, function () {
        ["F4", "C5", "A5"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.16, now + index * 0.06, GameAudio.scale("final", 0.45));
        });
        ["G4", "D5", "B5"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.16, now + 0.28 + index * 0.06, GameAudio.scale("final", 0.48));
        });
        ["C5", "E5", "G5", "C6"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.26, now + 0.54, GameAudio.scale("final", 0.44));
        });
      }, function () {
        ["A6", "G6", "E6", "D6", "C6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.08, now + index * 0.06, GameAudio.scale("final", 0.34));
        });
        ["E6", "G6", "C7"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.24, now + 0.4, GameAudio.scale("final", 0.42));
        });
      }, function () {
        ["C4", "E4", "G4", "C5"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.34, now, GameAudio.scale("final", 0.48));
        });
        ["E5", "G5", "B5", "D6", "E6"].forEach(function (note, index) {
          synth.triggerAttackRelease(note, 0.09, now + 0.18 + index * 0.055, GameAudio.scale("final", 0.38));
        });
        ["C6", "E6", "G6", "C7"].forEach(function (note) {
          return synth.triggerAttackRelease(note, 0.3, now + 0.58, GameAudio.scale("final", 0.5));
        });
      }];
      variants[Math.floor(Math.random() * variants.length)]();
    }
  }, {
    key: "createUiNoiseSynth",
    value: function createUiNoiseSynth() {
      return new Tone.NoiseSynth({
        noise: {
          type: "pink"
        },
        envelope: {
          attack: 0.001,
          decay: 0.08,
          sustain: 0.0,
          release: 0.06
        },
        volume: GameAudio.SYNTH_VOLUME_DB.uiNoise
      }).toDestination();
    }
  }, {
    key: "createUiTimerSynth",
    value: function createUiTimerSynth() {
      return new Tone.Synth({
        oscillator: {
          type: "square"
        },
        envelope: {
          attack: 0.001,
          decay: 0.03,
          sustain: 0.0,
          release: 0.06
        },
        volume: GameAudio.SYNTH_VOLUME_DB.uiTimer
      }).toDestination();
    }
  }, {
    key: "ensureMetronomeAudio",
    value: function () {
      var _ensureMetronomeAudio = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              if (window.Tone) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2, null);
            case 1:
              _context2.n = 2;
              return Tone.start();
            case 2:
              return _context2.a(2, GameAudio._getPreviewSynth("metronome", function () {
                return GameAudio.createMetronomeSynth();
              }));
          }
        }, _callee2);
      }));
      function ensureMetronomeAudio() {
        return _ensureMetronomeAudio.apply(this, arguments);
      }
      return ensureMetronomeAudio;
    }()
  }, {
    key: "playMetronomeClick",
    value: function playMetronomeClick() {
      var isDownbeat = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      if (!window.Tone) return;
      var synth = GameAudio._getPreviewSynth("metronome", function () {
        return GameAudio.createMetronomeSynth();
      });
      var kind = isDownbeat ? "metronomeDownbeat" : "metronomeBeat";
      synth.triggerAttackRelease(isDownbeat ? "C6" : "C5", "16n", Tone.now(), GameAudio.scale(kind, 1));
    }
  }, {
    key: "createMetronomeSynth",
    value: function createMetronomeSynth() {
      return new Tone.Synth({
        oscillator: {
          type: "square"
        },
        envelope: {
          attack: 0.001,
          decay: 0.04,
          sustain: 0,
          release: 0.01
        },
        volume: GameAudio.SYNTH_VOLUME_DB.metronome
      }).toDestination();
    }
  }, {
    key: "playRhythmHit",
    value: function playRhythmHit() {
      if (!window.Tone) return;
      var synth = GameAudio._getPreviewSynth("rhythmHit", function () {
        return GameAudio.createRhythmHitSynth();
      });
      synth.triggerAttackRelease("C2", "8n", Tone.now(), GameAudio.scale("rhythmHit", 1));
    }
  }, {
    key: "createRhythmHitSynth",
    value: function createRhythmHitSynth() {
      return new Tone.MembraneSynth({
        pitchDecay: 0.035,
        octaves: 2.5,
        oscillator: {
          type: "sine"
        },
        envelope: {
          attack: 0.001,
          decay: 0.12,
          sustain: 0,
          release: 0.06
        },
        volume: GameAudio.SYNTH_VOLUME_DB.rhythmHit
      }).toDestination();
    }
  }, {
    key: "createStaffNoteSynth",
    value: function createStaffNoteSynth() {
      return new Tone.Synth({
        oscillator: {
          type: "sine"
        },
        envelope: {
          attack: 0.01,
          decay: 0.08,
          sustain: 0.6,
          release: 0.12
        },
        volume: GameAudio.SYNTH_VOLUME_DB.staffNote
      }).toDestination();
    }
  }, {
    key: "createDictationSynth",
    value: function createDictationSynth() {
      return new Tone.PolySynth(Tone.Synth, {
        oscillator: {
          type: "sine"
        },
        envelope: {
          attack: 0.01,
          decay: 0.08,
          sustain: 0.35,
          release: 0.25
        },
        volume: GameAudio.SYNTH_VOLUME_DB.dictation
      }).toDestination();
    }
  }, {
    key: "createSequenceSynth",
    value: function createSequenceSynth() {
      return new Tone.PolySynth(Tone.Synth, {
        oscillator: {
          type: "sine"
        },
        envelope: {
          attack: 0.01,
          decay: 0.08,
          sustain: 0.35,
          release: 0.25
        },
        volume: GameAudio.SYNTH_VOLUME_DB.sequence
      }).toDestination();
    }
  }, {
    key: "_getPreviewSynth",
    value: function _getPreviewSynth(key, factory) {
      if (!GameAudio._previewSynths[key]) {
        GameAudio._previewSynths[key] = factory();
      }
      return GameAudio._previewSynths[key];
    }
  }]);
}();
// Tweak these values to rebalance the whole app.
_defineProperty(GameAudio, "SYNTH_VOLUME_DB", {
  uiPoly: -10,
  uiNoise: -16,
  uiTimer: -14,
  metronome: -12,
  rhythmHit: -10,
  staffNote: -8,
  dictation: -9,
  sequence: -9
});
_defineProperty(GameAudio, "VELOCITY", {
  staffNote: 1.0,
  dictation: 1.0,
  sequence: 1.0,
  successBasic: 0.7,
  successBonus: 0.7,
  failNoise: 1,
  failNote: 1,
  bombFail: 1,
  wallCrash: .4,
  "final": 0.5,
  finalMetric: 0.85,
  perfectBonus: 0.25,
  runStart: 0.9,
  timerBeep: 0.7,
  timerTimeUp: 0.95,
  countdownBeep: 1,
  metronomeBeat: 0.4,
  metronomeDownbeat: 0.6,
  rhythmHit: 0.65,
  hinge: 0.55,
  notePythonMelody: 1,
  notePythonLowEnd: 1,
  notePythonDrums: 1,
  notePythonVictory: 1
});
_defineProperty(GameAudio, "SOUND_LIBRARY", [{
  id: "staffNote",
  label: "Staff Note",
  volumeKey: "staffNote",
  description: "Base loudness for staff note playback."
}, {
  id: "dictation",
  label: "Dictation",
  volumeKey: "dictation",
  description: "Dictation playback loudness in PitchDetective."
}, {
  id: "sequence",
  label: "Sequence",
  volumeKey: "sequence",
  description: "Sequence playback loudness in ToneTrek and similar games."
}, {
  id: "successBasic",
  label: "Success",
  volumeKey: "successBasic",
  description: "Normal correct-answer sound."
}, {
  id: "successBonus",
  label: "Streak Bonus",
  volumeKey: "successBonus",
  description: "Streak / bonus correct-answer sound."
}, {
  id: "failNoise",
  label: "Fail Noise",
  volumeKey: "failNoise",
  description: "Noise portion of the fail sound."
}, {
  id: "failNote",
  label: "Fail Notes",
  volumeKey: "failNote",
  description: "Pitched portion of the fail sound."
}, {
  id: "bombFail",
  label: "Bomb Hit",
  volumeKey: "bombFail",
  description: "Long stumbling fail sound when the snake hits a bomb."
}, {
  id: "wallCrash",
  label: "Wall Crash",
  volumeKey: "wallCrash",
  description: "Sharp breaking impact when the snake crashes into a wall."
}, {
  id: "final",
  label: "Final Results",
  volumeKey: "final",
  description: "Final results reveal fanfare."
}, {
  id: "finalMetric",
  label: "Metric Pop",
  volumeKey: "finalMetric",
  description: "Small pop sound as each final metric box appears."
}, {
  id: "perfectBonus",
  label: "Perfect Bonus",
  volumeKey: "perfectBonus",
  description: "Extra reward sound for perfect/no-mistakes games."
}, {
  id: "runStart",
  label: "Run Start",
  volumeKey: "runStart",
  description: "Opening fanfare at the start of a run/countdown."
}, {
  id: "timerBeep",
  label: "Timer Warning",
  volumeKey: "timerBeep",
  description: "Repeating warning beep in the last timer seconds."
}, {
  id: "timerTimeUp",
  label: "Time Up",
  volumeKey: "timerTimeUp",
  description: "Stronger sound when the timer actually runs out."
}, {
  id: "countdownBeep",
  label: "Countdown Tick",
  volumeKey: "countdownBeep",
  description: "Simple 3-2-1 countdown tick sound."
}, {
  id: "metronomeBeat",
  label: "Metronome Beat",
  volumeKey: "metronomeBeat",
  description: "Regular metronome click."
}, {
  id: "metronomeDownbeat",
  label: "Metronome Downbeat",
  volumeKey: "metronomeDownbeat",
  description: "Higher-pitched click on the first beat of each measure."
}, {
  id: "rhythmHit",
  label: "Rhythm Hit",
  volumeKey: "rhythmHit",
  description: "Low percussive sound for Beat Hero rhythm notes."
}, {
  id: "hinge",
  label: "Hinge",
  volumeKey: "hinge",
  description: "Short hinge/fall sound used by ToneTrek block reveals."
}, {
  id: "notePythonMelody",
  label: "Note Python Melody",
  volumeKey: "notePythonMelody",
  description: "Lead melody and fast arpeggios in the Note Python soundtrack."
}, {
  id: "notePythonLowEnd",
  label: "Note Python Bass & Chords",
  volumeKey: "notePythonLowEnd",
  description: "Bass line and low harmony in the Note Python soundtrack."
}, {
  id: "notePythonDrums",
  label: "Note Python Drums",
  volumeKey: "notePythonDrums",
  description: "Kick, snare, and hi-hat layers in the Note Python soundtrack."
}, {
  id: "notePythonVictory",
  label: "Note Python Victory",
  volumeKey: "notePythonVictory",
  description: "Brief three-note victory cue at the Note Python soundtrack volume."
}]);
_defineProperty(GameAudio, "_previewSynths", {});

/***/ },

/***/ "./resources/js/games/games/shared/InstructionsUi.js"
/*!***********************************************************!*\
  !*** ./resources/js/games/games/shared/InstructionsUi.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   InstructionsUi: () => (/* binding */ InstructionsUi)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var InstructionsUi = /*#__PURE__*/function () {
  function InstructionsUi() {
    var rootSelector = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "#instructions";
    _classCallCheck(this, InstructionsUi);
    this.$root = $(rootSelector).first();
    this.$content = this.$root.find("h6").first();
    if (!this.$content.length) this.$content = this.$root;
    this._typed = null;
    this._lastHtml = this.$content.html() || "";
  }
  return _createClass(InstructionsUi, [{
    key: "show",
    value: function show() {
      this.$root.show();
      return this;
    }
  }, {
    key: "hide",
    value: function hide() {
      this.$root.hide();
      return this;
    }
  }, {
    key: "setHtml",
    value: function setHtml(value) {
      var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref$animate = _ref.animate,
        animate = _ref$animate === void 0 ? true : _ref$animate;
      if (!this.$content.length) return this;
      var html = String(value !== null && value !== void 0 ? value : "");
      this._lastHtml = html;
      this._destroyTyped();
      this.$content.html("");
      if (!html) return this;
      var shouldAnimate = animate && typeof window.Typed === "function";
      if (!shouldAnimate) {
        this.$content.html(html);
        return this;
      }
      var $typedTarget = $("<span></span>").addClass("instructions__typed");
      this.$content.append($typedTarget);
      this._typed = new window.Typed($typedTarget[0], {
        strings: [html],
        typeSpeed: 24,
        startDelay: 180,
        showCursor: true,
        contentType: "html"
      });
      return this;
    }
  }, {
    key: "replay",
    value: function replay() {
      return this.setHtml(this._lastHtml);
    }
  }, {
    key: "getHtml",
    value: function getHtml() {
      return this._lastHtml;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this._destroyTyped();
      return this;
    }
  }, {
    key: "_destroyTyped",
    value: function _destroyTyped() {
      var _this$_typed;
      if (!((_this$_typed = this._typed) !== null && _this$_typed !== void 0 && _this$_typed.destroy)) return;
      this._typed.destroy();
      this._typed = null;
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/games/shared/PianoKeyboardUi.js"
/*!************************************************************!*\
  !*** ./resources/js/games/games/shared/PianoKeyboardUi.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PianoKeyboardUi: () => (/* binding */ PianoKeyboardUi)
/* harmony export */ });
/* harmony import */ var _GameAudio_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GameAudio.js */ "./resources/js/games/games/shared/GameAudio.js");
/* harmony import */ var _noteNames_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./noteNames.js */ "./resources/js/games/games/shared/noteNames.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }


var PianoKeyboardUi = /*#__PURE__*/function () {
  function PianoKeyboardUi() {
    var _this$_naturalMidiFro;
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      _ref$rootSelector = _ref.rootSelector,
      rootSelector = _ref$rootSelector === void 0 ? "#keyboard" : _ref$rootSelector,
      _ref$namespace = _ref.namespace,
      namespace = _ref$namespace === void 0 ? "pianoKeyboard" : _ref$namespace,
      _ref$onKeyClick = _ref.onKeyClick,
      onKeyClick = _ref$onKeyClick === void 0 ? null : _ref$onKeyClick,
      _ref$canPlayNote = _ref.canPlayNote,
      canPlayNote = _ref$canPlayNote === void 0 ? null : _ref$canPlayNote,
      _ref$visibleWhiteKeys = _ref.visibleWhiteKeys,
      visibleWhiteKeys = _ref$visibleWhiteKeys === void 0 ? 7 : _ref$visibleWhiteKeys,
      _ref$initialStartNote = _ref.initialStartNote,
      initialStartNote = _ref$initialStartNote === void 0 ? "C4" : _ref$initialStartNote;
    _classCallCheck(this, PianoKeyboardUi);
    this.rootSelector = rootSelector;
    this.ns = namespace;
    this.onKeyClick = typeof onKeyClick === "function" ? onKeyClick : null;
    this.canPlayNote = typeof canPlayNote === "function" ? canPlayNote : null;
    this.visibleWhiteKeys = Math.max(1, Number(visibleWhiteKeys) || 7);
    this._startWhiteMidi = (_this$_naturalMidiFro = this._naturalMidiFromNoteName(initialStartNote)) !== null && _this$_naturalMidiFro !== void 0 ? _this$_naturalMidiFro : 60;
    this._activeMarkers = new Map();
    this._renderTimeoutId = null;
    this._audioReady = false;
    this._synth = null;
    this._drag = {
      active: false,
      pointerId: null,
      startPageX: 0,
      originWhiteMidi: this._startWhiteMidi,
      lastStepOffset: 0,
      didMove: false,
      suppressClickUntil: 0
    };
  }
  return _createClass(PianoKeyboardUi, [{
    key: "setStartNote",
    value: function setStartNote(noteName) {
      var midi = this._naturalMidiFromNoteName(noteName);
      if (!Number.isFinite(midi)) return this;
      this._startWhiteMidi = midi;
      this.clearActive();
      this.render();
      return this;
    }
  }, {
    key: "bind",
    value: function bind() {
      var _this = this;
      this.render();
      $(document).off("click.".concat(this.ns), this.rootSelector).on("click.".concat(this.ns), this.rootSelector, function (e) {
        if (Date.now() < _this._drag.suppressClickUntil) return;
        e.preventDefault();
        _this._activateKeyFromTarget(e.target);
      });
      $(document).off("pointerdown.".concat(this.ns, "Drag"), this.rootSelector).on("pointerdown.".concat(this.ns, "Drag"), this.rootSelector, function (e) {
        var _e$originalEvent;
        e.preventDefault();
        var pointerId = (_e$originalEvent = e.originalEvent) === null || _e$originalEvent === void 0 ? void 0 : _e$originalEvent.pointerId;
        var $key = _this._resolveKeyFromTarget(e.target);
        if ($key.length) {
          _this._drag.suppressClickUntil = Date.now() + 250;
          _this._activateKeyFromTarget($key[0]);
        }
        _this._drag.active = true;
        _this._drag.pointerId = pointerId != null ? pointerId : null;
        _this._drag.startPageX = e.pageX;
        _this._drag.originWhiteMidi = _this._startWhiteMidi;
        _this._drag.lastStepOffset = 0;
        _this._drag.didMove = false;
        $(e.currentTarget).addClass("dragging");
        if (e.currentTarget.setPointerCapture && pointerId != null) {
          e.currentTarget.setPointerCapture(pointerId);
        }
      });
      $(document).off("pointermove.".concat(this.ns, "Drag")).on("pointermove.".concat(this.ns, "Drag"), function (e) {
        var _e$originalEvent2;
        if (!_this._drag.active) return;
        var pointerId = (_e$originalEvent2 = e.originalEvent) === null || _e$originalEvent2 === void 0 ? void 0 : _e$originalEvent2.pointerId;
        if (_this._drag.pointerId != null && pointerId != null && pointerId !== _this._drag.pointerId) return;
        var whiteKeyWidth = _this._whiteKeyWidth();
        if (!Number.isFinite(whiteKeyWidth) || whiteKeyWidth <= 0) return;
        var deltaX = e.pageX - _this._drag.startPageX;
        var nextOffset = _this._stepOffsetFromDeltaX(deltaX, whiteKeyWidth);
        if (nextOffset === _this._drag.lastStepOffset) return;
        _this._drag.lastStepOffset = nextOffset;
        _this._drag.didMove = true;
        _this._startWhiteMidi = _this._shiftNaturalMidi(_this._drag.originWhiteMidi, -nextOffset);
        _this.render();
      });
      $(document).off("pointerup.".concat(this.ns, "Drag pointercancel.").concat(this.ns, "Drag")).on("pointerup.".concat(this.ns, "Drag pointercancel.").concat(this.ns, "Drag"), function (e) {
        var _e$originalEvent3;
        var pointerId = (_e$originalEvent3 = e.originalEvent) === null || _e$originalEvent3 === void 0 ? void 0 : _e$originalEvent3.pointerId;
        if (_this._drag.pointerId != null && pointerId != null && pointerId !== _this._drag.pointerId) return;
        _this._finishDrag();
      });
      return this;
    }
  }, {
    key: "unbind",
    value: function unbind() {
      $(document).off("click.".concat(this.ns), this.rootSelector);
      $(document).off("pointerdown.".concat(this.ns, "Drag"), this.rootSelector);
      $(document).off("pointermove.".concat(this.ns, "Drag"));
      $(document).off("pointerup.".concat(this.ns, "Drag pointercancel.").concat(this.ns, "Drag"));
      return this;
    }
  }, {
    key: "render",
    value: function render() {
      var $root = $(this.rootSelector).first();
      if (!$root.length) return this;
      var whiteNotes = this._visibleWhiteNotes();
      var nextIds = whiteNotes.map(function (white) {
        return String(white.midi);
      });
      var currentIds = $root.children(".key-wrapper").map(function (_, el) {
        return String(el.getAttribute("data-white-midi") || "");
      }).get();
      var changed = currentIds.join(",") !== nextIds.join(",");
      if (!changed) return this;
      var existing = new Map();
      $root.children(".key-wrapper").each(function (_, el) {
        var $el = $(el);
        existing.set(String($el.attr("data-white-midi") || ""), $el);
      });
      $root.children(".key-wrapper").each(function (_, el) {
        var id = String(el.getAttribute("data-white-midi") || "");
        if (!nextIds.includes(id)) $(el).remove();
      });
      var newWrappers = [];
      for (var i = 0; i < whiteNotes.length; i += 1) {
        var white = whiteNotes[i];
        var id = String(white.midi);
        var $wrapper = existing.get(id);
        if (!$wrapper || !$wrapper.length) {
          $wrapper = this._buildWrapper(white);
          newWrappers.push($wrapper);
        }
        $root.append($wrapper);
      }
      if (!currentIds.length) {
        $root.find(".white-key, .black-key").show();
        this._reapplyActiveMarkers();
        return this;
      }
      newWrappers.forEach(function ($wrapper) {
        $wrapper.find(".white-key, .black-key").show();
      });
      this._reapplyActiveMarkers();
      return this;
    }
  }, {
    key: "noteNameForKey",
    value: function noteNameForKey($key) {
      return $key !== null && $key !== void 0 && $key.length ? String($key.attr("data-note") || "") : "";
    }
  }, {
    key: "_activateKeyFromTarget",
    value: function _activateKeyFromTarget(target) {
      var $key = this._resolveKeyFromTarget(target);
      if (!$key.length) return;
      var noteName = this.noteNameForKey($key);
      void this._playNoteName(noteName);
      if (this.onKeyClick) this.onKeyClick({
        $key: $key,
        noteName: noteName,
        manual: true
      });
    }
  }, {
    key: "_activateKeyFromPointerEvent",
    value: function _activateKeyFromPointerEvent(event) {
      var pointerTarget = this._pointerEventTarget(event);
      this._activateKeyFromTarget(pointerTarget || (event === null || event === void 0 ? void 0 : event.target));
    }
  }, {
    key: "keyForNote",
    value: function keyForNote(letter, accidentalClass, octave) {
      var targetMidi = this._midiForNoteSpec(letter, accidentalClass, octave);
      if (!Number.isFinite(targetMidi)) return $();
      this._ensureMidiVisible(targetMidi);
      var selector = "".concat(this.rootSelector, " [data-midi=\"").concat(targetMidi, "\"]");
      return $(selector).first();
    }
  }, {
    key: "keyForNoteIfVisible",
    value: function keyForNoteIfVisible(letter, accidentalClass, octave) {
      var targetMidi = this._midiForNoteSpec(letter, accidentalClass, octave);
      if (!Number.isFinite(targetMidi) || !this._isMidiVisible(targetMidi)) return $();
      var selector = "".concat(this.rootSelector, " [data-midi=\"").concat(targetMidi, "\"]");
      return $(selector).first();
    }
  }, {
    key: "clickKey",
    value: function clickKey($key) {
      if ($key !== null && $key !== void 0 && $key.length) $key.trigger("click");
      return this;
    }
  }, {
    key: "clearActive",
    value: function clearActive() {
      this._hideAllMarkers();
      this._activeMarkers = new Map();
      return this;
    }
  }, {
    key: "syncActiveKey",
    value: function syncActiveKey($nextKey) {
      return this.syncActiveKeys($nextKey !== null && $nextKey !== void 0 && $nextKey.length ? [$nextKey] : []);
    }
  }, {
    key: "syncActiveKeys",
    value: function syncActiveKeys(keys) {
      var _this2 = this;
      var nextKeys = Array.isArray(keys) ? keys.filter(function ($key) {
        return $key === null || $key === void 0 ? void 0 : $key.length;
      }) : [];
      var entries = nextKeys.map(function ($key) {
        var noteName = _this2.noteNameForKey($key);
        if (!noteName) return null;
        return {
          noteName: noteName,
          tone: "primary",
          $key: $key
        };
      }).filter(Boolean);
      return this.syncActiveMarkers(entries);
    }
  }, {
    key: "syncActiveNoteNames",
    value: function syncActiveNoteNames(noteNames) {
      var _this3 = this;
      var visibleKeys = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
      var nextNoteNames = noteNames instanceof Set ? _toConsumableArray(noteNames).filter(Boolean) : (Array.isArray(noteNames) ? noteNames : []).filter(Boolean);
      var nextKeys = Array.isArray(visibleKeys) ? visibleKeys.filter(function ($key) {
        return $key === null || $key === void 0 ? void 0 : $key.length;
      }) : [];
      var entries = nextNoteNames.map(function (noteName) {
        var $key = nextKeys.find(function ($candidate) {
          return _this3.noteNameForKey($candidate) === noteName;
        }) || null;
        return {
          noteName: noteName,
          markerLabel: _this3._markerLabelFromNoteName(noteName),
          tone: "primary",
          $key: $key
        };
      });
      return this.syncActiveMarkers(entries);
    }
  }, {
    key: "syncActiveMarkers",
    value: function syncActiveMarkers(entries) {
      var _this4 = this;
      var list = Array.isArray(entries) ? entries.filter(Boolean) : [];
      var nextMarkers = new Map();
      list.forEach(function (entry) {
        var _entry$markerLabel;
        var noteName = String(entry.noteName || "").trim();
        if (!noteName) return;
        var hasMarkerLabel = Object.prototype.hasOwnProperty.call(entry, "markerLabel");
        nextMarkers.set(noteName, {
          tone: entry.tone === "secondary" ? "secondary" : "primary",
          label: hasMarkerLabel ? String((_entry$markerLabel = entry.markerLabel) !== null && _entry$markerLabel !== void 0 ? _entry$markerLabel : "").trim() : _this4._markerLabelFromNoteName(noteName),
          color: String(entry.markerColor || "").trim()
        });
      });
      if (this._markerMapsEqual(this._activeMarkers, nextMarkers)) {
        list.forEach(function (entry) {
          var _entry$$key;
          if (!(entry !== null && entry !== void 0 && (_entry$$key = entry.$key) !== null && _entry$$key !== void 0 && _entry$$key.length)) return;
          var marker = nextMarkers.get(String(entry.noteName || "").trim()) || {};
          var $marker = entry.$key.find(".key-marker").first();
          _this4._applyMarkerTone($marker, marker.tone, marker.color);
          _this4._showMarker($marker, marker.label);
        });
        return this;
      }
      this._hideAllMarkers();
      this._activeMarkers = new Map(nextMarkers);
      list.forEach(function (entry) {
        var _entry$$key2;
        if (!(entry !== null && entry !== void 0 && (_entry$$key2 = entry.$key) !== null && _entry$$key2 !== void 0 && _entry$$key2.length)) return;
        var noteName = String(entry.noteName || "").trim();
        var marker = _this4._activeMarkers.get(noteName);
        if (!noteName || !marker) return;
        var $marker = entry.$key.find(".key-marker").first();
        _this4._applyMarkerTone($marker, marker.tone, marker.color);
        _this4._showMarker($marker, marker.label);
      });
      return this;
    }
  }, {
    key: "toggleKey",
    value: function toggleKey($key) {
      if (!($key !== null && $key !== void 0 && $key.length)) return this;
      var noteName = this.noteNameForKey($key);
      // eslint-disable-next-line no-console
      console.log("[PianoKeyboardUi] Keyboard key clicked", {
        note: noteName
      });
      if (this.onKeyClick) this.onKeyClick({
        $key: $key,
        noteName: noteName,
        manual: true
      });
      return this;
    }
  }, {
    key: "_markerHtml",
    value: function _markerHtml() {
      return "\n      <div class=\"key-marker\">\n        <div class=\"d-center w-100\">\n          <span class=\"bg-primary\"></span>\n        </div>\n      </div>\n    ";
    }
  }, {
    key: "_buildWrapper",
    value: function _buildWrapper(white) {
      var black = this._blackNoteAfter(white.midi);
      var $wrapper = $('<div class="position-relative key-wrapper"></div>').attr("data-white-midi", String(white.midi));
      if (black) {
        var $black = $('<div class="black-key"></div>').attr({
          "data-note": black.note,
          "data-midi": String(black.midi)
        });
        $black.append('<button type="button" class="btn btn-dark"></button>');
        $black.append(this._markerHtml());
        $wrapper.append($black);
      }
      var $white = $('<div class="white-key"></div>').attr({
        "data-note": white.note,
        "data-midi": String(white.midi)
      });
      $white.append('<button type="button" class="btn btn-white"></button>');
      $white.append(this._markerHtml());
      $wrapper.append($white);
      return $wrapper;
    }
  }, {
    key: "_resolveKeyFromTarget",
    value: function _resolveKeyFromTarget(target) {
      var $key = $(target).closest(".white-key, .black-key");
      if ($key.length) return $key;
      var $wrapper = $(target).closest("".concat(this.rootSelector, " .key-wrapper"));
      if ($wrapper.length) $key = $wrapper.find(".black-key, .white-key").first();
      return $key;
    }
  }, {
    key: "_pointerEventTarget",
    value: function _pointerEventTarget(event) {
      var _document;
      var source = (event === null || event === void 0 ? void 0 : event.originalEvent) || event;
      var clientX = Number(source === null || source === void 0 ? void 0 : source.clientX);
      var clientY = Number(source === null || source === void 0 ? void 0 : source.clientY);
      if (!Number.isFinite(clientX) || !Number.isFinite(clientY) || typeof ((_document = document) === null || _document === void 0 ? void 0 : _document.elementFromPoint) !== "function") {
        return null;
      }
      return document.elementFromPoint(clientX, clientY);
    }
  }, {
    key: "_visibleWhiteNotes",
    value: function _visibleWhiteNotes() {
      var out = [];
      var midi = this._startWhiteMidi;
      for (var i = 0; i < this.visibleWhiteKeys; i += 1) {
        out.push({
          midi: midi,
          note: this._naturalNoteNameFromMidi(midi)
        });
        midi = this._nextNaturalMidi(midi);
      }
      return out;
    }
  }, {
    key: "_blackNoteAfter",
    value: function _blackNoteAfter(whiteMidi) {
      var pitchClass = (whiteMidi % 12 + 12) % 12;
      if (pitchClass === 4 || pitchClass === 11) return null; // E/B have no black key above

      var midi = whiteMidi + 1;
      return {
        midi: midi,
        note: this._noteNameFromMidi(midi)
      };
    }
  }, {
    key: "_ensureMidiVisible",
    value: function _ensureMidiVisible(targetMidi) {
      if (!Number.isFinite(targetMidi)) return;
      while (targetMidi < this._startWhiteMidi) {
        this._startWhiteMidi = this._prevNaturalMidi(this._startWhiteMidi);
        this.render();
      }
      while (true) {
        var lastWhiteMidi = this._lastWhiteMidi();
        var lastBlack = this._blackNoteAfter(lastWhiteMidi);
        var maxVisibleMidi = lastBlack ? lastBlack.midi : lastWhiteMidi;
        if (targetMidi <= maxVisibleMidi) break;
        this._startWhiteMidi = this._nextNaturalMidi(this._startWhiteMidi);
        this.render();
      }
    }
  }, {
    key: "_isMidiVisible",
    value: function _isMidiVisible(targetMidi) {
      if (!Number.isFinite(targetMidi)) return false;
      var lastWhiteMidi = this._lastWhiteMidi();
      var lastBlack = this._blackNoteAfter(lastWhiteMidi);
      var maxVisibleMidi = lastBlack ? lastBlack.midi : lastWhiteMidi;
      return targetMidi >= this._startWhiteMidi && targetMidi <= maxVisibleMidi;
    }
  }, {
    key: "_lastWhiteMidi",
    value: function _lastWhiteMidi() {
      var midi = this._startWhiteMidi;
      for (var i = 1; i < this.visibleWhiteKeys; i += 1) {
        midi = this._nextNaturalMidi(midi);
      }
      return midi;
    }
  }, {
    key: "_nextNaturalMidi",
    value: function _nextNaturalMidi(midi) {
      var _current$match;
      var current = this._naturalNoteNameFromMidi(midi);
      var letter = current.replace(/\d+$/, "");
      var octave = Number(((_current$match = current.match(/-?\d+$/)) === null || _current$match === void 0 ? void 0 : _current$match[0]) || 4);
      var idx = PianoKeyboardUi.NATURAL_ORDER.indexOf(letter);
      var nextIdx = (idx + 1) % PianoKeyboardUi.NATURAL_ORDER.length;
      var nextOctave = nextIdx === 0 ? octave + 1 : octave;
      return this._naturalMidiFromLetterOctave(PianoKeyboardUi.NATURAL_ORDER[nextIdx], nextOctave);
    }
  }, {
    key: "_prevNaturalMidi",
    value: function _prevNaturalMidi(midi) {
      var _current$match2;
      var current = this._naturalNoteNameFromMidi(midi);
      var letter = current.replace(/\d+$/, "");
      var octave = Number(((_current$match2 = current.match(/-?\d+$/)) === null || _current$match2 === void 0 ? void 0 : _current$match2[0]) || 4);
      var idx = PianoKeyboardUi.NATURAL_ORDER.indexOf(letter);
      var prevIdx = (idx - 1 + PianoKeyboardUi.NATURAL_ORDER.length) % PianoKeyboardUi.NATURAL_ORDER.length;
      var prevOctave = idx === 0 ? octave - 1 : octave;
      return this._naturalMidiFromLetterOctave(PianoKeyboardUi.NATURAL_ORDER[prevIdx], prevOctave);
    }
  }, {
    key: "_midiForNoteSpec",
    value: function _midiForNoteSpec(letter, accidentalClass, octave) {
      var cleanLetter = String(letter || "").trim().toUpperCase();
      var numOctave = Number(octave);
      var basePc = PianoKeyboardUi.NATURAL_PITCH_CLASS[cleanLetter];
      if (!Number.isInteger(basePc) || !Number.isFinite(numOctave)) return null;
      var midi = (numOctave + 1) * 12 + basePc + this._accidentalOffset(accidentalClass);
      return midi;
    }
  }, {
    key: "_accidentalOffset",
    value: function _accidentalOffset(accidentalClass) {
      if (!accidentalClass) return 0;
      if (accidentalClass.includes("music-font__doublesharp")) return 2;
      if (accidentalClass.includes("music-font__sharp")) return 1;
      if (accidentalClass.includes("music-font__doubleflat")) return -2;
      if (accidentalClass.includes("music-font__flat")) return -1;
      return 0;
    }
  }, {
    key: "_naturalMidiFromNoteName",
    value: function _naturalMidiFromNoteName(noteName) {
      return (0,_noteNames_js__WEBPACK_IMPORTED_MODULE_1__.naturalMidiFromNoteName)(noteName);
    }
  }, {
    key: "_naturalMidiFromLetterOctave",
    value: function _naturalMidiFromLetterOctave(letter, octave) {
      return (0,_noteNames_js__WEBPACK_IMPORTED_MODULE_1__.naturalMidiFromLetterOctave)(letter, octave);
    }
  }, {
    key: "_noteNameFromMidi",
    value: function _noteNameFromMidi(midi) {
      return (0,_noteNames_js__WEBPACK_IMPORTED_MODULE_1__.noteNameFromMidi)(midi);
    }
  }, {
    key: "_naturalNoteNameFromMidi",
    value: function _naturalNoteNameFromMidi(midi) {
      return (0,_noteNames_js__WEBPACK_IMPORTED_MODULE_1__.naturalNoteNameFromMidi)(midi);
    }
  }, {
    key: "_keyByNoteName",
    value: function _keyByNoteName(noteName) {
      if (!noteName) return $();
      return $("".concat(this.rootSelector, " [data-note=\"").concat(noteName, "\"]")).first();
    }
  }, {
    key: "_hideAllMarkers",
    value: function _hideAllMarkers() {
      $("".concat(this.rootSelector, " .key-marker")).hide().find("span").text("").css({
        backgroundColor: "",
        color: ""
      }).removeClass("bg-primary bg-secondary");
    }
  }, {
    key: "_reapplyActiveMarkers",
    value: function _reapplyActiveMarkers() {
      var _this5 = this;
      this._hideAllMarkers();
      if (!(this._activeMarkers instanceof Map) || !this._activeMarkers.size) return;
      this._activeMarkers.forEach(function (marker, noteName) {
        var $key = _this5._keyByNoteName(noteName);
        if (!$key.length) return;
        var $marker = $key.find(".key-marker").first();
        _this5._applyMarkerTone($marker, marker.tone, marker.color);
        _this5._showMarker($marker, marker.label);
      });
    }
  }, {
    key: "_whiteKeyWidth",
    value: function _whiteKeyWidth() {
      var $whiteKey = $("".concat(this.rootSelector, " .white-key")).first();
      return $whiteKey.length ? $whiteKey.outerWidth() || 0 : 0;
    }
  }, {
    key: "_stepOffsetFromDeltaX",
    value: function _stepOffsetFromDeltaX(deltaX, whiteKeyWidth) {
      if (!Number.isFinite(deltaX) || !Number.isFinite(whiteKeyWidth) || whiteKeyWidth <= 0) return 0;
      if (deltaX > 0) return Math.floor(deltaX / whiteKeyWidth);
      if (deltaX < 0) return -Math.floor(Math.abs(deltaX) / whiteKeyWidth);
      return 0;
    }
  }, {
    key: "_shiftNaturalMidi",
    value: function _shiftNaturalMidi(midi, whiteSteps) {
      if (!Number.isFinite(midi) || !Number.isFinite(whiteSteps) || whiteSteps === 0) return midi;
      var nextMidi = midi;
      var stepCount = Math.abs(Math.trunc(whiteSteps));
      for (var i = 0; i < stepCount; i += 1) {
        nextMidi = whiteSteps > 0 ? this._nextNaturalMidi(nextMidi) : this._prevNaturalMidi(nextMidi);
      }
      return nextMidi;
    }
  }, {
    key: "_finishDrag",
    value: function _finishDrag() {
      if (!this._drag.active) return;
      var $root = $(this.rootSelector).first();
      if ($root.length) {
        var _$root$;
        $root.removeClass("dragging");
        if ((_$root$ = $root[0]) !== null && _$root$ !== void 0 && _$root$.releasePointerCapture && this._drag.pointerId != null) {
          try {
            $root[0].releasePointerCapture(this._drag.pointerId);
          } catch (_) {
            // Ignore capture release errors when the pointer is already gone.
          }
        }
      }
      this._drag.active = false;
      this._drag.pointerId = null;
      this._drag.startPageX = 0;
      this._drag.originWhiteMidi = this._startWhiteMidi;
      this._drag.suppressClickUntil = this._drag.didMove ? Date.now() + 250 : Math.max(this._drag.suppressClickUntil || 0, Date.now() + 250);
      this._drag.lastStepOffset = 0;
      this._drag.didMove = false;
    }
  }, {
    key: "_markerMapsEqual",
    value: function _markerMapsEqual(a, b) {
      if (!(a instanceof Map) || !(b instanceof Map)) return false;
      if (a.size !== b.size) return false;
      var _iterator = _createForOfIteratorHelper(a),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var _step$value = _slicedToArray(_step.value, 2),
            key = _step$value[0],
            value = _step$value[1];
          var next = b.get(key);
          if (!next || next.tone !== value.tone || next.label !== value.label || next.color !== value.color) return false;
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
      return true;
    }
  }, {
    key: "_markerLabelFromNoteName",
    value: function _markerLabelFromNoteName(noteName) {
      var match = String(noteName || "").trim().match(/^([A-G][#b]?)-?\d+$/);
      return (0,_noteNames_js__WEBPACK_IMPORTED_MODULE_1__.displayNoteName)(match ? match[1] : String(noteName || "").trim());
    }
  }, {
    key: "_applyMarkerTone",
    value: function _applyMarkerTone($marker, tone) {
      var color = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : "";
      if (!($marker !== null && $marker !== void 0 && $marker.length)) return;
      var $swatch = $marker.find("span").first();
      if (!$swatch.length) return;
      $swatch.removeClass("bg-primary bg-secondary");
      $swatch.css({
        backgroundColor: "",
        color: ""
      });
      if (color) {
        $swatch.css({
          backgroundColor: color,
          color: "#111"
        });
        return;
      }
      $swatch.addClass(tone === "secondary" ? "bg-secondary" : "bg-primary");
    }
  }, {
    key: "_showMarker",
    value: function _showMarker($marker) {
      var noteName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : "";
      if (!($marker !== null && $marker !== void 0 && $marker.length)) return;
      $marker.find("span").first().text(noteName || "");
      $marker.show();
    }
  }, {
    key: "_ensureAudio",
    value: function () {
      var _ensureAudio2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              if (!(this._audioReady && this._synth)) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              if (window.Tone) {
                _context.n = 2;
                break;
              }
              return _context.a(2);
            case 2:
              _context.n = 3;
              return Tone.start();
            case 3:
              this._synth = this._synth || _GameAudio_js__WEBPACK_IMPORTED_MODULE_0__.GameAudio.createStaffNoteSynth();
              this._audioReady = true;
            case 4:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function _ensureAudio() {
        return _ensureAudio2.apply(this, arguments);
      }
      return _ensureAudio;
    }()
  }, {
    key: "_canPlayNote",
    value: function _canPlayNote() {
      if (this.canPlayNote) return !!this.canPlayNote();
      return true;
    }
  }, {
    key: "_playNoteName",
    value: function () {
      var _playNoteName2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(noteName) {
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              if (this._canPlayNote()) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2);
            case 1:
              if (String(noteName || "").trim()) {
                _context2.n = 2;
                break;
              }
              return _context2.a(2);
            case 2:
              _context2.n = 3;
              return this._ensureAudio();
            case 3:
              if (this._synth) {
                _context2.n = 4;
                break;
              }
              return _context2.a(2);
            case 4:
              this._synth.triggerAttackRelease(String(noteName).trim(), 0.45, undefined, _GameAudio_js__WEBPACK_IMPORTED_MODULE_0__.GameAudio.scale("staffNote", 1));
            case 5:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function _playNoteName(_x) {
        return _playNoteName2.apply(this, arguments);
      }
      return _playNoteName;
    }()
  }]);
}();
_defineProperty(PianoKeyboardUi, "NATURAL_ORDER", _noteNames_js__WEBPACK_IMPORTED_MODULE_1__.NATURAL_NOTE_ORDER);
_defineProperty(PianoKeyboardUi, "NATURAL_PITCH_CLASS", _noteNames_js__WEBPACK_IMPORTED_MODULE_1__.NATURAL_PITCH_CLASS);
_defineProperty(PianoKeyboardUi, "PITCH_CLASS_TO_NOTE", _noteNames_js__WEBPACK_IMPORTED_MODULE_1__.PITCH_CLASS_TO_NOTE);

/***/ },

/***/ "./resources/js/games/games/shared/PromptUi.js"
/*!*****************************************************!*\
  !*** ./resources/js/games/games/shared/PromptUi.js ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PromptUi: () => (/* binding */ PromptUi)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var PromptUi = /*#__PURE__*/function () {
  function PromptUi() {
    var rootSelector = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "#prompt";
    _classCallCheck(this, PromptUi);
    this.$root = $(rootSelector).first();
    this.$short = $("#prompt-short");
    if (!this.$short.length) this.$short = this.$root.find("label").first();
    this.$direction = $("#prompt-direction");
    if (!this.$direction.length) this.$direction = this.$root.find("i").first();
    this.$long = $("#prompt-long");
    if (!this.$long.length) this.$long = this.$root.find("div").last();
  }
  return _createClass(PromptUi, [{
    key: "show",
    value: function show() {
      this.$root.show();
      return this;
    }
  }, {
    key: "hide",
    value: function hide() {
      this.$root.hide();
      return this;
    }
  }, {
    key: "setShort",
    value: function setShort(value) {
      var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref$html = _ref.html,
        html = _ref$html === void 0 ? true : _ref$html;
      if (!this.$short.length) return this;
      if (html) this.$short.html(value !== null && value !== void 0 ? value : "");else this.$short.text(value !== null && value !== void 0 ? value : "");
      return this;
    }
  }, {
    key: "getShortText",
    value: function getShortText() {
      return this.$short.length ? this.$short.text() : "";
    }
  }, {
    key: "setLong",
    value: function setLong(value) {
      var _ref2 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
        _ref2$html = _ref2.html,
        html = _ref2$html === void 0 ? true : _ref2$html;
      if (!this.$long.length) return this;
      if (html) this.$long.html(value !== null && value !== void 0 ? value : "");else this.$long.text(value !== null && value !== void 0 ? value : "");
      return this;
    }
  }, {
    key: "clearLong",
    value: function clearLong() {
      if (this.$long.length) this.$long.html("");
      return this;
    }
  }, {
    key: "showDirection",
    value: function showDirection() {
      var direction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;
      if (!this.$direction.length) return this;
      this.$direction.show().removeClass("fa-up-long fa-down-long").addClass(Number(direction) === -1 ? "fa-down-long" : "fa-up-long");
      return this;
    }
  }, {
    key: "hideDirection",
    value: function hideDirection() {
      if (this.$direction.length) this.$direction.hide();
      return this;
    }
  }, {
    key: "setTone",
    value: function setTone() {
      var color = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : "blue";
      this.$root.removeClass("incorrect");
      if (color === "red" || color === "incorrect") this.$root.addClass("incorrect");
      return this;
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/games/shared/challengeUtils.js"
/*!***********************************************************!*\
  !*** ./resources/js/games/games/shared/challengeUtils.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   accidentalClassFromOffset: () => (/* binding */ accidentalClassFromOffset),
/* harmony export */   fixedNoteToStaffPosition: () => (/* binding */ fixedNoteToStaffPosition),
/* harmony export */   normalizeClefPool: () => (/* binding */ normalizeClefPool),
/* harmony export */   parseIntervalAbbr: () => (/* binding */ parseIntervalAbbr),
/* harmony export */   parsePitch: () => (/* binding */ parsePitch),
/* harmony export */   pickChallengeClef: () => (/* binding */ pickChallengeClef)
/* harmony export */ });
/* harmony import */ var _staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../staff/staffUtils.js */ "./resources/js/games/staff/staffUtils.js");

function normalizeClefPool(clefsOrSingle) {
  var raw = Array.isArray(clefsOrSingle) ? clefsOrSingle : clefsOrSingle != null ? [clefsOrSingle] : ["treble", "bass"];
  var normalized = raw.map(function (c) {
    return (0,_staff_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(c);
  }).filter(Boolean);
  var uniq = [];
  for (var i = 0; i < normalized.length; i += 1) {
    if (!uniq.includes(normalized[i])) uniq.push(normalized[i]);
  }
  return uniq.length ? uniq : ["treble", "bass"];
}
function pickChallengeClef(clefPool) {
  var random = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : Math.random;
  var pool = Array.isArray(clefPool) && clefPool.length ? clefPool : ["treble", "bass"];
  if (pool.length === 1) return pool[0];
  return pool[Math.floor(random() * pool.length)];
}
function accidentalClassFromOffset(off) {
  if (off === 2) return "music-font__doublesharp";
  if (off === 1) return "music-font__sharp";
  if (off === 0) return "music-font__natural";
  if (off === -1) return "music-font__flat";
  if (off === -2) return "music-font__doubleflat";
  return null;
}
function parseIntervalAbbr(abbr) {
  var s = String(abbr || "").trim();
  var m = s.match(/^([PMAmd]+)(\d+)$/);
  if (!m) return null;
  return {
    quality: m[1],
    number: parseInt(m[2], 10)
  };
}
function parsePitch(pitch) {
  var s = String(pitch || "").trim();
  var m = s.match(/^([A-Ga-g])((?:#{1,2})|(?:b{1,2})|)?(\d+)$/);
  if (!m) return null;
  var letter = m[1].toUpperCase();
  var acc = m[2] || "";
  var octave = parseInt(m[3], 10);
  var baseSemitoneFromC = {
    C: 0,
    D: 2,
    E: 4,
    F: 5,
    G: 7,
    A: 9,
    B: 11
  }[letter];
  var accOffset = acc === "𝄪" ? 2 : acc === "#" ? 1 : acc === "bb" ? -2 : acc === "b" ? -1 : 0;
  var accidentalClass = accidentalClassFromOffset(accOffset);
  var midi = 12 * (octave + 1) + baseSemitoneFromC + accOffset;
  return {
    midi: midi,
    accOffset: accOffset,
    accidentalClass: accidentalClass
  };
}
function fixedNoteToStaffPosition(staff, noteStr) {
  var parsed = parsePitch(noteStr);
  if (!parsed) return null;
  var midi = parsed.midi,
    accOffset = parsed.accOffset,
    accidentalClass = parsed.accidentalClass;
  for (var step = staff.minStepAllowed(); step <= staff.maxStepAllowed(); step += 1) {
    var baseMidi = staff._stepToMidi(step);
    if (baseMidi + accOffset === midi) return {
      step: step,
      accidentalClass: accidentalClass
    };
  }
  var best = null;
  for (var _step = staff.minStepAllowed(); _step <= staff.maxStepAllowed(); _step += 1) {
    var _baseMidi = staff._stepToMidi(_step);
    var dist = Math.abs(_baseMidi + accOffset - midi);
    if (!best || dist < best.dist) best = {
      step: _step,
      dist: dist
    };
  }
  return best ? {
    step: best.step,
    accidentalClass: accidentalClass
  } : null;
}

/***/ },

/***/ "./resources/js/games/games/shared/finalResults.js"
/*!*********************************************************!*\
  !*** ./resources/js/games/games/shared/finalResults.js ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DEFAULT_FINAL_RESULTS_REVEAL_DELAY_MS: () => (/* binding */ DEFAULT_FINAL_RESULTS_REVEAL_DELAY_MS),
/* harmony export */   queueFinalResultsReveal: () => (/* binding */ queueFinalResultsReveal),
/* harmony export */   renderFinalResultsOverlay: () => (/* binding */ renderFinalResultsOverlay)
/* harmony export */ });
/* harmony import */ var _resultVariants_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./resultVariants.js */ "./resources/js/games/games/shared/resultVariants.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var DEFAULT_FINAL_RESULTS_REVEAL_DELAY_MS = 1600;
function queueFinalResultsReveal() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
    _ref$$button = _ref.$button,
    $button = _ref$$button === void 0 ? null : _ref$$button,
    showFinalResults = _ref.showFinalResults,
    _ref$delayMs = _ref.delayMs,
    delayMs = _ref$delayMs === void 0 ? DEFAULT_FINAL_RESULTS_REVEAL_DELAY_MS : _ref$delayMs;
  if ($button !== null && $button !== void 0 && $button.length) {
    $button.attr("state", "final").empty().disable();
  }
  var delay = Math.max(0, Number(delayMs) || 0);
  return setTimeout(function () {
    return showFinalResults === null || showFinalResults === void 0 ? void 0 : showFinalResults();
  }, delay);
}
function renderFinalResultsOverlay(_ref2) {
  var _window, _window$matchMedia, _window2, _$finalOverlay$, _$greetingTitle$, _window3, _window4;
  var $finalOverlay = _ref2.$finalOverlay,
    _ref2$rounds = _ref2.rounds,
    rounds = _ref2$rounds === void 0 ? 0 : _ref2$rounds,
    _ref2$score = _ref2.score,
    score = _ref2$score === void 0 ? 0 : _ref2$score,
    _ref2$accuracy = _ref2.accuracy,
    accuracy = _ref2$accuracy === void 0 ? 0 : _ref2$accuracy,
    _ref2$durationSec = _ref2.durationSec,
    durationSec = _ref2$durationSec === void 0 ? 0 : _ref2$durationSec,
    _ref2$settingsBonus = _ref2.settingsBonus,
    settingsBonus = _ref2$settingsBonus === void 0 ? false : _ref2$settingsBonus,
    _ref2$clearCountupTim = _ref2.clearCountupTimers,
    clearCountupTimers = _ref2$clearCountupTim === void 0 ? null : _ref2$clearCountupTim,
    _ref2$countupTimers = _ref2.countupTimers,
    countupTimers = _ref2$countupTimers === void 0 ? null : _ref2$countupTimers,
    _ref2$animateMetrics = _ref2.animateMetrics,
    animateMetrics = _ref2$animateMetrics === void 0 ? null : _ref2$animateMetrics,
    _ref2$playFinalSfx = _ref2.playFinalSfx,
    playFinalSfx = _ref2$playFinalSfx === void 0 ? null : _ref2$playFinalSfx;
  if (window.__activeDuel) {
    clearCountupTimers === null || clearCountupTimers === void 0 || clearCountupTimers();
    window.__activeDuel.finished({
      score: score,
      accuracy: accuracy
    });
    return;
  }
  if (!$finalOverlay || !$finalOverlay.length) return;
  var CountUpCtor = (_window = window) === null || _window === void 0 || (_window = _window.CountUp) === null || _window === void 0 ? void 0 : _window.CountUp;
  var reducedMotion = !!((_window$matchMedia = (_window2 = window).matchMedia) !== null && _window$matchMedia !== void 0 && _window$matchMedia.call(_window2, "(prefers-reduced-motion: reduce)").matches);
  var DURATION = 1.4;
  if (typeof clearCountupTimers === "function") clearCountupTimers();
  var setMetricAnimationDelays = function setMetricAnimationDelays() {
    var $boxes = $finalOverlay.find("#metrics-boxes > div");
    if (!$boxes.length) return;
    var BASE_DELAY_MS = 260;
    var STEP_DELAY_MS = 260;
    $boxes.each(function (i, el) {
      var delayMs = BASE_DELAY_MS + i * STEP_DELAY_MS;
      el.style.animationDelay = "".concat(delayMs, "ms");
    });
  };
  var mmss = function mmss(secs) {
    var v = Math.max(0, Math.floor(Number(secs) || 0));
    var mm = String(Math.floor(v / 60)).padStart(2, "0");
    var ss = String(v % 60).padStart(2, "0");
    return "".concat(mm, ":").concat(ss);
  };
  var setSaveResultField = function setSaveResultField(name, value) {
    var $input = $("#save-results-modal input[name=\"".concat(name, "\"]")).first();
    if (!$input.length) return;
    $input.val(String(value !== null && value !== void 0 ? value : "").trim());
  };
  var getFinalPointsPreviewElements = function getFinalPointsPreviewElements() {
    var $modal = $("#save-results-modal");
    var $finalPoints = $modal.find("#finalPoints");
    return {
      $modal: $modal,
      $finalPoints: $finalPoints
    };
  };
  var countFinalPoints = function countFinalPoints(value) {
    var _getFinalPointsPrevie = getFinalPointsPreviewElements(),
      $finalPoints = _getFinalPointsPrevie.$finalPoints;
    if (!$finalPoints.length) return;
    var finalPoints = Number(value);
    if (!Number.isFinite(finalPoints)) {
      $finalPoints.text(value !== null && value !== void 0 ? value : "");
      return;
    }
    if (!CountUpCtor || reducedMotion) {
      $finalPoints.text(String(Math.round(finalPoints)));
      return;
    }
    var counter = new CountUpCtor($finalPoints[0], finalPoints, {
      duration: 1.4,
      decimalPlaces: 0
    });
    if (!counter.error) counter.start();else $finalPoints.text(String(Math.round(finalPoints)));
  };
  var fetchFinalPointsPreview = function fetchFinalPointsPreview() {
    var _getFinalPointsPrevie2 = getFinalPointsPreviewElements(),
      $modal = _getFinalPointsPrevie2.$modal,
      $finalPoints = _getFinalPointsPrevie2.$finalPoints;
    if (!$modal.length || !$finalPoints.length || !window.axios) return;
    var url = $modal.data("final-points-url");
    if (!url) return;
    $modal.removeData("final-points-value");
    $finalPoints.text("");
    window.axios.get(url, {
      params: {
        game: $modal.find('input[name="game"]').val(),
        rounds: rounds,
        score: score,
        accuracy: accuracy,
        duration: Math.max(0, Math.floor(Number(durationSec) || 0))
      }
    }).then(function (response) {
      var _response$data$finalP, _response$data;
      var value = (_response$data$finalP = response === null || response === void 0 || (_response$data = response.data) === null || _response$data === void 0 ? void 0 : _response$data.finalPoints) !== null && _response$data$finalP !== void 0 ? _response$data$finalP : response === null || response === void 0 ? void 0 : response.data;
      $modal.data("final-points-value", value);
      if ($modal.hasClass("show")) countFinalPoints(value);
    })["catch"](function () {
      $modal.data("final-points-value", "");
      if ($modal.hasClass("show")) $finalPoints.text("");
    });
  };
  var bindFinalPointsPreview = function bindFinalPointsPreview() {
    var _getFinalPointsPrevie3 = getFinalPointsPreviewElements(),
      $modal = _getFinalPointsPrevie3.$modal,
      $finalPoints = _getFinalPointsPrevie3.$finalPoints;
    if (!$modal.length || !$finalPoints.length) return;
    $modal.off("show.bs.modal.finalPoints").on("show.bs.modal.finalPoints", function () {
      $finalPoints.text("");
    }).off("shown.bs.modal.finalPoints").on("shown.bs.modal.finalPoints", function () {
      var value = $modal.data("final-points-value");
      if (value == null) {
        $finalPoints.text("...");
        return;
      }
      countFinalPoints(value);
    });
  };
  var pushCountupTimer = function pushCountupTimer(id) {
    if (Array.isArray(countupTimers)) countupTimers.push(id);
  };
  var countTo = function countTo(selector, endVal) {
    var opts = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var el = $finalOverlay.find(selector)[0];
    if (!el) return;
    var startCount = function startCount() {
      if (!CountUpCtor || reducedMotion) {
        el.textContent = String(opts.formattingFn ? opts.formattingFn(endVal) : endVal) + (opts.suffix || "");
        return;
      }
      var c = new CountUpCtor(el, endVal, _objectSpread({
        duration: DURATION
      }, opts));
      if (!c.error) c.start();else el.textContent = String(opts.formattingFn ? opts.formattingFn(endVal) : endVal) + (opts.suffix || "");
    };
    var $box = $(el).closest("#metrics-boxes > div");
    var rawDelay = $box.length ? parseFloat($box[0].style.animationDelay || "0") : 0;
    var delayMs = !reducedMotion && Number.isFinite(rawDelay) ? Math.max(0, rawDelay) : 0;
    if (delayMs <= 0) {
      startCount();
      return;
    }
    var tid = setTimeout(startCount, delayMs + 40);
    pushCountupTimer(tid);
  };
  var $greeting = $finalOverlay.find("#result-greeting");
  var $greetingTitle = $greeting.find("h1");
  var $settingsBonus = $finalOverlay.find("#settings-bonus-earned");
  var resultGreetings = {
    encouraging: ["Keep going!", "Nice try!", "You are learning!", "Getting there!", "Good effort!", "Keep practicing!", "Almost there!", "Let's try that again!", "Try another round!", "You are getting closer!"],
    strong: ["Great job!", "Well done!", "Nice work!", "Good one!", "Solid round!", "Looking good!", "You did it!", "That was good!", "Way to go!", "Good progress!"],
    excellent: ["You got it!", "Impressive!", "Fantastic!", "Excellent!", "Nailed it!", "Brilliant!", "Outstanding!", "Amazing round!", "That was sharp!", "Top notch!"]
  };
  var randomFrom = function randomFrom(items) {
    return items[Math.floor(Math.random() * items.length)];
  };
  // Keep the existing accuracy bands consistent across every game.
  var tier = accuracy < 50 ? "encouraging" : accuracy <= 80 ? "strong" : "excellent";
  $greetingTitle.text(accuracy >= 100 ? "Perfect run!" : randomFrom(resultGreetings[tier]));
  $finalOverlay.attr("data-result-tier", tier);
  $finalOverlay.attr("data-result-variant", (0,_resultVariants_js__WEBPACK_IMPORTED_MODULE_0__.chooseResultVariant)(tier));
  (_$finalOverlay$ = $finalOverlay[0]) === null || _$finalOverlay$ === void 0 || _$finalOverlay$.style.setProperty("--result-score-digits", String(Math.max(3, String(Math.round(Number(score) || 0)).length)));
  $settingsBonus.toggle(!!settingsBonus);
  $finalOverlay.show();
  (_$greetingTitle$ = $greetingTitle[0]) === null || _$greetingTitle$ === void 0 || _$greetingTitle$.focus({
    preventScroll: true
  });
  var Confetti = ((_window3 = window) === null || _window3 === void 0 ? void 0 : _window3.Confetti) || ((_window4 = window) === null || _window4 === void 0 ? void 0 : _window4.confetti);
  if (!reducedMotion && tier === "excellent" && typeof Confetti === "function") {
    Confetti({
      particleCount: 100,
      spread: 70,
      origin: {
        y: 0.6
      },
      zIndex: 1001,
      colors: ["#ffe54c", "#55b9ac", "#b18ce8", "#f49236"]
    });
  }
  if (!reducedMotion) {
    if (typeof animateMetrics === "function") animateMetrics();else setMetricAnimationDelays();
  }
  countTo('span[name="rounds"]', rounds);
  countTo('span[name="score"]', score);
  countTo('span[name="accuracy"]', accuracy, {
    suffix: "%"
  });
  countTo('span[name="duration"]', durationSec, {
    formattingFn: mmss
  });
  setSaveResultField("rounds", rounds);
  setSaveResultField("score", score);
  setSaveResultField("accuracy", "".concat(accuracy, "%"));
  setSaveResultField("duration", mmss(durationSec));
  bindFinalPointsPreview();
  fetchFinalPointsPreview();
  if (typeof playFinalSfx === "function") playFinalSfx();
}

/***/ },

/***/ "./resources/js/games/games/shared/mojsEffects.js"
/*!********************************************************!*\
  !*** ./resources/js/games/games/shared/mojsEffects.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   playBurstConfettiAtElement: () => (/* binding */ playBurstConfettiAtElement),
/* harmony export */   playSmokePuffAtElement: () => (/* binding */ playSmokePuffAtElement),
/* harmony export */   playSnakeCellBreakBurstAtElement: () => (/* binding */ playSnakeCellBreakBurstAtElement)
/* harmony export */ });
function playBurstConfettiAtElement(targetEl) {
  var _ref = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
    _ref$parentEl = _ref.parentEl,
    parentEl = _ref$parentEl === void 0 ? document.body : _ref$parentEl,
    _ref$index = _ref.index,
    index = _ref$index === void 0 ? 0 : _ref$index;
  var mojs = window.mojs;
  if (!mojs || !targetEl) return;
  var parentRect = parentEl.getBoundingClientRect();
  var rect = targetEl.getBoundingClientRect();
  var x = rect.left - parentRect.left + rect.width / 2;
  var y = rect.top - parentRect.top + rect.height / 2;
  var countBoost = Math.min(18, Math.max(0, Number(index) || 0));
  var burst = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    radius: {
      6: 56
    },
    angle: 45,
    count: 22 + countBoost,
    zIndex: 5,
    children: {
      radius: "rand(3,6)",
      fill: "#ffe54c",
      scale: {
        2: 0,
        easing: "quad.in"
      },
      pathScale: [1.8, null],
      degreeShift: [13, null],
      duration: [500, 760],
      easing: "quint.out",
      isForce3d: true
    }
  });
  burst.tune({
    x: x,
    y: y
  }).replay();
}
var smokeBurstCache = new WeakMap();
function _getSmokeBurst(parentEl) {
  var fill = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : "black";
  var mojs = window.mojs;
  if (!mojs || !mojs.Burst || !parentEl) return null;
  if (fill !== "black") {
    var _DURATION = 400;
    return new mojs.Burst({
      parent: parentEl,
      left: 0,
      top: 0,
      degree: 0,
      count: 3,
      radius: {
        0: 100
      },
      children: {
        fill: fill,
        pathScale: "rand(0.5, 1)",
        radius: "rand(12, 15)",
        swirlSize: "rand(10, 15)",
        swirlFrequency: "rand(2, 4)",
        direction: [1, -1],
        duration: "rand(".concat(1 * _DURATION, ", ").concat(2 * _DURATION, ")"),
        delay: "rand(0, 75)",
        easing: "quad.out",
        isSwirl: true,
        isForce3d: true
      }
    });
  }
  var cached = smokeBurstCache.get(parentEl);
  if (cached) return cached;
  var DURATION = 400;
  var smoke = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    degree: 0,
    count: 3,
    radius: {
      0: 100
    },
    children: {
      fill: "black",
      pathScale: "rand(0.5, 1)",
      radius: "rand(12, 15)",
      swirlSize: "rand(10, 15)",
      swirlFrequency: "rand(2, 4)",
      direction: [1, -1],
      duration: "rand(".concat(1 * DURATION, ", ").concat(2 * DURATION, ")"),
      delay: "rand(0, 75)",
      easing: "quad.out",
      isSwirl: true,
      isForce3d: true
    }
  });
  smokeBurstCache.set(parentEl, smoke);
  return smoke;
}
function playSmokePuffAtElement(targetEl) {
  var _ref2 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
    _ref2$parentEl = _ref2.parentEl,
    parentEl = _ref2$parentEl === void 0 ? document.body : _ref2$parentEl,
    _ref2$fill = _ref2.fill,
    fill = _ref2$fill === void 0 ? "black" : _ref2$fill;
  var mojs = window.mojs;
  if (!mojs || !targetEl || !parentEl) return;
  var parentRect = parentEl.getBoundingClientRect();
  var rect = targetEl.getBoundingClientRect();
  var x = rect.left - parentRect.left + rect.width / 2;
  var y = rect.top - parentRect.top + rect.height / 2;
  var smoke = _getSmokeBurst(parentEl, fill);
  if (!smoke) return;
  smoke.tune({
    x: x,
    y: y
  }).generate().replay();
}
function playSnakeCellBreakBurstAtElement(targetEl) {
  var _ref3 = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {},
    _ref3$parentEl = _ref3.parentEl,
    parentEl = _ref3$parentEl === void 0 ? document.body : _ref3$parentEl,
    _ref3$index = _ref3.index,
    index = _ref3$index === void 0 ? 0 : _ref3$index;
  var mojs = window.mojs;
  if (!mojs || !targetEl || !parentEl) return;
  var parentRect = parentEl.getBoundingClientRect();
  var rect = targetEl.getBoundingClientRect();
  var x = rect.left - parentRect.left + rect.width / 2;
  var y = rect.top - parentRect.top + rect.height / 2;
  var boost = Math.min(4, Math.max(0, Number(index) || 0));
  var yellowShards = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    x: x,
    y: y,
    count: 14 + boost,
    radius: {
      0: 62 + boost * 9
    },
    zIndex: 9,
    children: {
      shape: "rect",
      fill: "#ffe54c",
      radius: "rand(8.5,15.5)",
      pathScale: [1, 0.3],
      degreeShift: "rand(-28,28)",
      duration: "rand(760,1100)",
      delay: "rand(0,85)",
      easing: "quart.out",
      isForce3d: true
    }
  });
  var blackBits = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    x: x,
    y: y,
    count: 18 + boost,
    radius: {
      0: 74 + boost * 10
    },
    zIndex: 9,
    children: {
      shape: "circle",
      fill: "black",
      radius: "rand(7.2,13.2)",
      pathScale: [1.1, 0.35],
      degreeShift: "rand(-35,35)",
      duration: "rand(820,1200)",
      delay: "rand(0,95)",
      easing: "quint.out",
      isForce3d: true
    }
  });
  new mojs.Timeline().add(yellowShards, blackBits).play();
}

/***/ },

/***/ "./resources/js/games/games/shared/noteNames.js"
/*!******************************************************!*\
  !*** ./resources/js/games/games/shared/noteNames.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   NATURAL_NOTE_ORDER: () => (/* binding */ NATURAL_NOTE_ORDER),
/* harmony export */   NATURAL_PITCH_CLASS: () => (/* binding */ NATURAL_PITCH_CLASS),
/* harmony export */   PITCH_CLASS_TO_NOTE: () => (/* binding */ PITCH_CLASS_TO_NOTE),
/* harmony export */   displayAccidental: () => (/* binding */ displayAccidental),
/* harmony export */   displayNoteName: () => (/* binding */ displayNoteName),
/* harmony export */   naturalMidiFromLetterOctave: () => (/* binding */ naturalMidiFromLetterOctave),
/* harmony export */   naturalMidiFromNoteName: () => (/* binding */ naturalMidiFromNoteName),
/* harmony export */   naturalNoteNameFromMidi: () => (/* binding */ naturalNoteNameFromMidi),
/* harmony export */   noteNameFromMidi: () => (/* binding */ noteNameFromMidi),
/* harmony export */   octaveFromMidi: () => (/* binding */ octaveFromMidi),
/* harmony export */   pitchClassFromMidi: () => (/* binding */ pitchClassFromMidi)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var NATURAL_NOTE_ORDER = ["C", "D", "E", "F", "G", "A", "B"];
var NATURAL_PITCH_CLASS = {
  C: 0,
  D: 2,
  E: 4,
  F: 5,
  G: 7,
  A: 9,
  B: 11
};
var PITCH_CLASS_TO_NOTE = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];

// Keep ASCII spellings for pitch calculations and input values; use these only
// when showing a note name to a player.
function displayAccidental(offset) {
  return _defineProperty(_defineProperty({
    2: "𝄪",
    1: "♯"
  }, -1, "♭"), -2, "𝄫")[offset] || "";
}
function displayNoteName(noteName) {
  var raw = String(noteName !== null && noteName !== void 0 ? noteName : "");
  return raw.replace(/^((?:Do|Re|Mi|Fa|Sol|La|Si|[A-G]))(##|bb|#|b)(?=$|-?\d+$)/i, function (_, base, accidental) {
    return "".concat(base).concat({
      "##": "𝄪",
      bb: "𝄫",
      "#": "♯",
      b: "♭"
    }[accidental]);
  });
}
function pitchClassFromMidi(midi) {
  if (!Number.isFinite(midi)) return null;
  return (midi % 12 + 12) % 12;
}
function octaveFromMidi(midi) {
  if (!Number.isFinite(midi)) return null;
  return Math.floor(midi / 12) - 1;
}
function noteNameFromMidi(midi) {
  var pitchClass = pitchClassFromMidi(midi);
  var octave = octaveFromMidi(midi);
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return "";
  return "".concat(PITCH_CLASS_TO_NOTE[pitchClass]).concat(octave);
}
function naturalNoteNameFromMidi(midi) {
  var pitchClass = pitchClassFromMidi(midi);
  var octave = octaveFromMidi(midi);
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return "";
  var letter = Object.keys(NATURAL_PITCH_CLASS).find(function (key) {
    return NATURAL_PITCH_CLASS[key] === pitchClass;
  });
  return letter ? "".concat(letter).concat(octave) : "";
}
function naturalMidiFromLetterOctave(letter, octave) {
  var pitchClass = NATURAL_PITCH_CLASS[String(letter || "").toUpperCase()];
  if (!Number.isInteger(pitchClass) || !Number.isFinite(octave)) return null;
  return (octave + 1) * 12 + pitchClass;
}
function naturalMidiFromNoteName(noteName) {
  var match = String(noteName || "").trim().match(/^([A-G])(-?\d+)$/);
  if (!match) return null;
  return naturalMidiFromLetterOctave(match[1], Number(match[2]));
}

/***/ },

/***/ "./resources/js/games/games/shared/resultVariants.js"
/*!***********************************************************!*\
  !*** ./resources/js/games/games/shared/resultVariants.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   chooseResultVariant: () => (/* binding */ chooseResultVariant)
/* harmony export */ });
var lastVariants = new Map();

// Remember each result band separately, including across Play again page reloads.
function chooseResultVariant(tier) {
  var count = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 5;
  var key = "musicGames.resultVariant.".concat(tier);
  var previous = lastVariants.get(tier);
  try {
    var saved = window.sessionStorage.getItem(key);
    if (saved !== null && /^\d+$/.test(saved)) previous = Number(saved);
  } catch (_) {
    // Storage may be unavailable in a private or restricted browser session.
  }
  var hasPrevious = Number.isInteger(previous) && previous >= 0 && previous < count;
  var choices = count - (hasPrevious && count > 1 ? 1 : 0);
  var next = Math.floor(Math.random() * choices);
  if (hasPrevious && count > 1 && next >= previous) next += 1;
  lastVariants.set(tier, next);
  try {
    window.sessionStorage.setItem(key, String(next));
  } catch (_) {
    // The in-memory fallback still prevents repeats during this page's lifetime.
  }
  return next;
}

/***/ },

/***/ "./resources/js/games/staff/Staff.js"
/*!*******************************************!*\
  !*** ./resources/js/games/staff/Staff.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Staff: () => (/* binding */ Staff)
/* harmony export */ });
/* harmony import */ var _staffUtils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./staffUtils.js */ "./resources/js/games/staff/staffUtils.js");
/* harmony import */ var _StaffAnimations_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StaffAnimations.js */ "./resources/js/games/staff/StaffAnimations.js");
/* harmony import */ var _games_shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../games/shared/GameAudio.js */ "./resources/js/games/games/shared/GameAudio.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }




/**
 * Staff engine: draws staff, manages note interactions, emits events.
 * Requires: jQuery, jQuery UI (draggable/droppable), optional Tone.js.
 */
var Staff = /*#__PURE__*/function () {
  function Staff($el, opts) {
    _classCallCheck(this, Staff);
    this.$el = $el;
    var css = getComputedStyle($el[0]);
    this.opts = $.extend({
      paddingX: (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.pxFromCss)(css, "--staff-padding-x", 20),
      lineGap: (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.pxFromCss)(css, "--staff-line-gap", 16),
      lineThickness: (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.pxFromCss)(css, "--staff-line-thickness", 3),
      noteOverlapGap: (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.pxFromCss)(css, "--note-overlap-gap", -6),
      noteIdPrefix: "n",
      /** If you want default clef URLs, pass them in from page/game:
       *  clefUrls: { treble,bass,alto,tenor }
       */
      clefUrls: null,
      clef: null,
      clefUrl: null,
      autoClef: true,
      maxLedgerAbove: 2,
      maxLedgerBelow: 2,
      accidentalTopPx: 20,
      accidentalGapPx: 16,
      getMaxUserNotes: function getMaxUserNotes() {
        return Infinity;
      },
      sound: true,
      showLineNames: false,
      formatLineName: null,
      accSnapMaxPx: (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.pxFromCss)(css, "--staff-line-gap", 25) * 1.2
    }, opts || {});
    this.opts.clef = this.opts.clef == null ? null : (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(this.opts.clef);
    if (this.opts.clef && !this.opts.clefUrl) {
      this.opts.clefUrl = this._clefUrlFor(this.opts.clef);
    }
    this.opts.stepSize = this.opts.lineGap / 2;
    this.$el.css("position", "relative");
    this._baseHeightPx = this.$el.height();
    this._idCounter = 1;
    this._drag = {
      isDragging: false,
      movedPx: 0,
      startPageY: 0,
      noteId: null,
      thresholdPx: 5,
      swallowClick: false,
      startStep: null,
      lastTargetStep: null,
      lastSoundStep: null,
      dropOnOccupied: false,
      outOfRange: false
    };
    this._previewState = {
      active: false,
      step: null
    };
    this._preview = null;
    this._previewStep = null;
    this._audioReady = false;
    this._synth = null;
    this._heldMidi = null;
    this._holdSoundTimer = null;
    this._pendingHeldStep = null;
    this._pendingHeldAccidentalOffset = 0;
    this._holdSoundDelayMs = 140;
    this._accDragSound = {
      noteId: null,
      step: null,
      toolType: null,
      prospectiveCls: null
    };
    this._accSnap = {
      noteId: null,
      dist: null,
      localY: null
    };
    this._suppressNextClick = {
      noteId: null,
      until: 0
    };
    this._animations = new _StaffAnimations_js__WEBPACK_IMPORTED_MODULE_1__.StaffAnimations(this.$el);
    this._blockedSteps = new Set();
    this._applyClefCssVars(this.opts.clef);
    this._computeLayout();
    this._drawLines();
    if (this.opts.autoClef && !this.opts.clef) {
      this.setClef("treble");
    } else if (this.opts.clef) {
      this._applyClefCssVars(this.opts.clef);
      this.relayout();
    }
  }
  return _createClass(Staff, [{
    key: "_clefUrlFor",
    value: function _clefUrlFor(clef) {
      var c = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(clef);
      if (!c) return null;
      var urls = this.opts.clefUrls || {};
      return urls[c] || urls.treble || null;
    }
  }, {
    key: "setSoundEnabled",
    value: function setSoundEnabled(enabled) {
      this.opts.sound = !!enabled;
      if (!this.opts.sound && window.Tone) {
        try {
          Tone.Transport && Tone.Transport.stop();
        } catch (_) {}
        try {
          Tone.context && Tone.context.suspend && Tone.context.suspend();
        } catch (_) {}
        try {
          this._synth && this._synth.releaseAll && this._synth.releaseAll();
        } catch (_) {}
        this._releaseHeldStep();
        this._audioReady = false;
      }
    }
  }, {
    key: "isSoundEnabled",
    value: function isSoundEnabled() {
      return !!this.opts.sound;
    }
  }, {
    key: "_soundEnabled",
    value: function _soundEnabled() {
      return !!this.opts.sound;
    }
  }, {
    key: "_applyClefCssVars",
    value: function _applyClefCssVars(clef) {
      var c = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(clef);
      var vars = _staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.CLEF_LAYOUT_VARS[c] || _staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.CLEF_LAYOUT_VARS.treble;
      var el = this.$el[0];
      Object.keys(vars).forEach(function (k) {
        return el.style.setProperty(k, vars[k]);
      });
    }
  }, {
    key: "setClef",
    value: function setClef(clef) {
      this.opts.clef = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(clef);
      this.opts.clefUrl = this._clefUrlFor(this.opts.clef);
      this._applyClefCssVars(this.opts.clef);
      this.relayout();
    }
  }, {
    key: "getClef",
    value: function getClef() {
      return this.opts.clef || "treble";
    }
  }, {
    key: "isStepAllowed",
    value: function isStepAllowed(step) {
      return this._isStepAllowed(step);
    }
  }, {
    key: "setBlockedSteps",
    value: function setBlockedSteps(steps) {
      var next = new Set();
      var list = Array.isArray(steps) ? steps : [];
      for (var i = 0; i < list.length; i++) {
        var step = Number(list[i]);
        if (Number.isFinite(step)) next.add(step);
      }
      this._blockedSteps = next;
    }
  }, {
    key: "clearBlockedSteps",
    value: function clearBlockedSteps() {
      this._blockedSteps.clear();
    }
  }, {
    key: "isStepBlocked",
    value: function isStepBlocked(step) {
      var excludeId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      if (excludeId && this.isNoteFixed(excludeId)) return false;
      return this._blockedSteps.has(Number(step));
    }
  }, {
    key: "ledgerStepsFor",
    value: function ledgerStepsFor(step) {
      return this._ledgerStepsFor(step);
    }
  }, {
    key: "_maxUserNotes",
    value: function _maxUserNotes() {
      var v = this.opts.getMaxUserNotes ? this.opts.getMaxUserNotes() : Infinity;
      return Number.isFinite(v) ? v : Infinity;
    }
  }, {
    key: "_userNoteCount",
    value: function _userNoteCount() {
      return this.$el.find(".note").not(".fixed").not(".preview").not(".hint").length;
    }
  }, {
    key: "_computeLayout",
    value: function _computeLayout() {
      var h = Number.isFinite(this._baseHeightPx) && this._baseHeightPx > 0 ? this._baseHeightPx : this.$el.height();
      var staffHeight = this.opts.lineGap * 4;
      var topLineY = Math.round((h - staffHeight) / 2);
      this.opts.bottomLineY = topLineY + staffHeight;
    }
  }, {
    key: "_syncDynamicHeight",
    value: function _syncDynamicHeight() {
      var _this = this;
      var baseHeight = Number.isFinite(this._baseHeightPx) && this._baseHeightPx > 0 ? this._baseHeightPx : this.$el.height();
      var requiredHeight = baseHeight;
      this.$el.find(".note, .ledger").each(function (_, el) {
        var $node = $(el);
        var top = parseFloat($node.css("top"));
        if (!Number.isFinite(top)) return;
        var outerHeight = $node.outerHeight() || 0;
        var bottom = top + outerHeight + _this.opts.lineGap;
        if (bottom > requiredHeight) requiredHeight = bottom;
      });
      var finalHeight = Math.max(baseHeight, Math.ceil(requiredHeight));
      if (this.$el.height() !== finalHeight) this.$el.height(finalHeight);
    }
  }, {
    key: "_drawLines",
    value: function _drawLines() {
      this.$el.find(".staff-line, .staff-clef, #clef-wrapper").remove();
      for (var i = 0; i < 5; i++) {
        var y = this.opts.bottomLineY - (4 - i) * this.opts.lineGap;
        var step = (4 - i) * 2;
        var durationSec = (0.12 + Math.random() * 0.36).toFixed(3); // ~0.12s..0.48s
        var $line = $('<div class="staff-line"></div>').css({
          top: "".concat(y, "px"),
          animationDuration: "".concat(durationSec, "s")
        });
        var lineName = this._lineNameForStep(step);
        if (lineName) {
          $('<span class="line-note-name"></span>').text(lineName).appendTo($line);
        }
        $line.appendTo(this.$el);
      }
      this._drawClef();
    }
  }, {
    key: "_lineNameForStep",
    value: function _lineNameForStep(step) {
      if (!this.opts.showLineNames) return "";
      var noteState = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.stepToLetterOctave)(this, step);
      var letter = String((noteState === null || noteState === void 0 ? void 0 : noteState.letter) || "").trim().toUpperCase();
      if (!letter) return "";
      var formatter = this.opts.formatLineName;
      if (typeof formatter === "function") {
        return String(formatter(letter, {
          step: step,
          octave: noteState === null || noteState === void 0 ? void 0 : noteState.octave,
          clef: this.getClef()
        }) || "");
      }
      return letter;
    }
  }, {
    key: "_drawClef",
    value: function _drawClef() {
      if (!this.opts.clefUrl) return;
      var clef = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.normalizeClef)(this.opts.clef);
      var $img = $('<img alt="">').attr("src", this.opts.clefUrl);
      $('<div id="clef-wrapper"></div>').addClass("".concat(clef, "-clef")).append($img).appendTo(this.$el);
    }
  }, {
    key: "relayout",
    value: function relayout() {
      this._computeLayout();
      this._drawLines();
      this._resolveNoteOverlaps();
      this._repositionAllAccidentals();
      this._syncDynamicHeight();
    }
  }, {
    key: "centerX",
    value: function centerX() {
      return this.$el.width() / 2;
    }
  }, {
    key: "stepToY",
    value: function stepToY(step) {
      return this.opts.bottomLineY - step * this.opts.stepSize;
    }
  }, {
    key: "yToStep",
    value: function yToStep(y) {
      return Math.round((this.opts.bottomLineY - y) / this.opts.stepSize);
    }
  }, {
    key: "_pageYToLocalY",
    value: function _pageYToLocalY(pageY) {
      return pageY - this.$el.offset().top;
    }
  }, {
    key: "minStepAllowed",
    value: function minStepAllowed() {
      return 0 - this.opts.maxLedgerBelow * 2;
    }
  }, {
    key: "maxStepAllowed",
    value: function maxStepAllowed() {
      return 8 + this.opts.maxLedgerAbove * 2;
    }
  }, {
    key: "_isStepAllowed",
    value: function _isStepAllowed(step) {
      return step >= this.minStepAllowed() && step <= this.maxStepAllowed();
    }
  }, {
    key: "_ledgerStepsFor",
    value: function _ledgerStepsFor(step) {
      var ledgers = [];
      var topMost = 8 + this.opts.maxLedgerAbove * 2;
      var bottomMost = 0 - this.opts.maxLedgerBelow * 2;
      if (step > 8) {
        var capped = Math.min(step, topMost);
        for (var s = 10; s <= capped; s += 2) ledgers.push(s);
      } else if (step < 0) {
        var _capped = Math.max(step, bottomMost);
        for (var _s = -2; _s >= _capped; _s -= 2) ledgers.push(_s);
      }
      return ledgers;
    }
  }, {
    key: "_renderLedgers",
    value: function _renderLedgers(id, x, step) {
      this.$el.find(".ledger[data-for-note-id=\"".concat(id, "\"]")).remove();
      var isDragging = this.$el.find(".note[data-note-id=\"".concat(id, "\"]")).hasClass("dragging");
      var steps = this._ledgerStepsFor(step);
      for (var i = 0; i < steps.length; i++) {
        var $l = $('<div class="ledger"></div>').attr("data-for-note-id", id).css({
          left: "".concat(x, "px"),
          top: "".concat(this.stepToY(steps[i]), "px")
        });
        if (isDragging) $l.addClass("dragging");
        $l.appendTo(this.$el);
      }
    }
  }, {
    key: "_previewLedgersClear",
    value: function _previewLedgersClear() {
      this.$el.find(".ledger.preview").remove();
    }
  }, {
    key: "_previewLedgersSet",
    value: function _previewLedgersSet(step) {
      this._previewLedgersClear();
      var x = this.centerX();
      var steps = this._ledgerStepsFor(step);
      for (var i = 0; i < steps.length; i++) {
        $('<div class="ledger preview"></div>').css({
          left: "".concat(x, "px"),
          top: "".concat(this.stepToY(steps[i]), "px")
        }).appendTo(this.$el);
      }
    }
  }, {
    key: "_stepOfNoteEl",
    value: function _stepOfNoteEl(el) {
      var topStr = el.style.top || window.getComputedStyle(el).top;
      return this.yToStep(parseFloat(topStr));
    }
  }, {
    key: "_noteLocksX",
    value: function _noteLocksX(elOrId) {
      if (!elOrId) return false;
      var el = typeof elOrId === "string" ? this.$el.find(".note[data-note-id=\"".concat(elOrId, "\"]"))[0] : elOrId;
      if (!el) return false;
      var attr = String(el.getAttribute("data-lock-x") || "").trim().toLowerCase();
      return attr === "true" || attr === "1" || $(el).hasClass("lock-x");
    }
  }, {
    key: "_isStepOccupied",
    value: function _isStepOccupied(step, excludeId) {
      if (this.isStepBlocked(step, excludeId)) return true;
      var nodes = this.$el.find(".note").toArray();
      for (var i = 0; i < nodes.length; i++) {
        var el = nodes[i];
        if (!el) continue;
        var id = el.getAttribute("data-note-id");
        if (excludeId && id === excludeId) continue;
        if (this._stepOfNoteEl(el) === step) return true;
      }
      return false;
    }
  }, {
    key: "_getNoteIdAtStep",
    value: function _getNoteIdAtStep(step, excludeId) {
      if (this.isStepBlocked(step, excludeId)) return null;
      var nodes = this.$el.find(".note").toArray();
      for (var i = 0; i < nodes.length; i++) {
        var el = nodes[i];
        if (!el) continue;
        var id = el.getAttribute("data-note-id");
        if (excludeId && id === excludeId) continue;
        if (this._stepOfNoteEl(el) === step) return id;
      }
      return null;
    }
  }, {
    key: "_isCenteredX",
    value: function _isCenteredX(noteId) {
      var $n = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      if (!$n.length) return true;
      return Math.abs(parseFloat($n.css("left")) - this.centerX()) <= 0.5;
    }
  }, {
    key: "isNoteFixed",
    value: function isNoteFixed(noteId) {
      var $note = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      return $note.length ? $note.hasClass("fixed") : false;
    }
  }, {
    key: "_nearestEditableNoteByLocalY",
    value: function _nearestEditableNoteByLocalY(localY) {
      var maxD = Number.isFinite(this.opts.accSnapMaxPx) ? this.opts.accSnapMaxPx : this.opts.lineGap * 1.2;
      var best = null;
      var nodes = this.$el.find(".note").not(".preview").toArray();
      for (var i = 0; i < nodes.length; i++) {
        var el = nodes[i];
        var id = el.getAttribute("data-note-id");
        if (!id) continue;
        if ($(el).hasClass("fixed")) continue;
        var top = parseFloat(el.style.top || window.getComputedStyle(el).top);
        var d = Math.abs(top - localY);
        if (best == null || d < best.dist) best = {
          noteId: id,
          dist: d
        };
      }
      if (!best || best.dist > maxD) return null;
      return best;
    }
  }, {
    key: "_removeAccidentalForNote",
    value: function _removeAccidentalForNote(noteId) {
      this.$el.find(".accidental[data-for-note-id=\"".concat(noteId, "\"]")).remove();
    }
  }, {
    key: "_accidentalAnchorXForNote",
    value: function _accidentalAnchorXForNote(noteLeftPx) {
      var cx = this.centerX();
      var EPS = 0.5;
      if (Number.isFinite(noteLeftPx) && noteLeftPx > cx + EPS) return cx;
      return noteLeftPx;
    }
  }, {
    key: "_positionAccidentalForNote",
    value: function _positionAccidentalForNote(noteId) {
      var $note = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      var $acc = this.$el.find(".accidental[data-for-note-id=\"".concat(noteId, "\"]"));
      if (!$note.length || !$acc.length) return;
      var noteLeft = parseFloat($note.css("left"));
      var noteTop = parseFloat($note.css("top"));
      var anchorX = this._accidentalAnchorXForNote(noteLeft);
      $acc.css({
        left: "".concat(anchorX - this.opts.accidentalGapPx, "px"),
        top: "".concat(noteTop - this.opts.accidentalTopPx, "px")
      });
    }
  }, {
    key: "_rectsOverlap",
    value: function _rectsOverlap(a, b) {
      return !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);
    }
  }, {
    key: "_repositionAllAccidentals",
    value: function _repositionAllAccidentals() {
      var _this2 = this;
      this.$el.find(".accidental").each(function (_, node) {
        var id = node.getAttribute("data-for-note-id");
        if (id) _this2._positionAccidentalForNote(id);
      });
      var noteEls = this.$el.find(".note").not(".preview").toArray();
      var stepToNoteId = {};
      for (var i = 0; i < noteEls.length; i++) {
        var el = noteEls[i];
        var id = el.getAttribute("data-note-id");
        if (!id) continue;
        stepToNoteId[this._stepOfNoteEl(el)] = id;
      }
      var steps = Object.keys(stepToNoteId).map(function (s) {
        return parseInt(s, 10);
      }).sort(function (a, b) {
        return a - b;
      });
      for (var _i = 0; _i < steps.length; _i++) {
        var lowerStep = steps[_i];
        var upperStep = lowerStep + 1;
        var lowerId = stepToNoteId[lowerStep];
        var upperId = stepToNoteId[upperStep];
        if (!lowerId || !upperId) continue;
        var $lowerAcc = this.$el.find(".accidental[data-for-note-id=\"".concat(lowerId, "\"]"));
        var $upperAcc = this.$el.find(".accidental[data-for-note-id=\"".concat(upperId, "\"]"));
        if (!$lowerAcc.length || !$upperAcc.length) continue;
        var lowerRect = $lowerAcc[0].getBoundingClientRect();
        var upperRect = $upperAcc[0].getBoundingClientRect();
        if (!this._rectsOverlap(lowerRect, upperRect)) continue;
        var overlapPx = Math.max(0, upperRect.right - lowerRect.left);
        var padPx = -6;
        var curLeft = parseFloat($upperAcc.css("left"));
        if (!Number.isFinite(curLeft)) continue;
        $upperAcc.css("left", "".concat(curLeft - (overlapPx + padPx), "px"));
      }
    }
  }, {
    key: "_getAttachedAccidentalClass",
    value: function _getAttachedAccidentalClass(noteId) {
      var $acc = this.$el.find(".accidental[data-for-note-id=\"".concat(noteId, "\"]"));
      if (!$acc.length) return null;
      for (var i = 0; i < _staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.ACCIDENTAL_CLASSES.length; i++) {
        var cls = _staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.ACCIDENTAL_CLASSES[i];
        if ($acc.hasClass(cls)) return cls;
      }
      return null;
    }
  }, {
    key: "attachAccidentalToNote",
    value: function attachAccidentalToNote(noteId, accidentalClass) {
      if (!noteId || this.isNoteFixed(noteId)) return;
      this._removeAccidentalForNote(noteId);
      var $acc = $('<div class="accidental music-font"></div>').addClass(accidentalClass).attr("data-for-note-id", noteId);
      this.$el.append($acc);
      this._repositionAllAccidentals();
    }
  }, {
    key: "_hintIgnoredAccidental",
    value: function _hintIgnoredAccidental(noteId) {
      var $note = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      if (!$note.length) return;
      $note.removeClass("animate__animated animate__headShake");
      void $note[0].offsetWidth;
      $note.addClass("animate__animated animate__headShake");
      $note.off("animationend._hint webkitAnimationEnd._hint oAnimationEnd._hint MSAnimationEnd._hint").one("animationend._hint webkitAnimationEnd._hint oAnimationEnd._hint MSAnimationEnd._hint", function () {
        $note.removeClass("animate__animated animate__headShake");
      });
    }
  }, {
    key: "applyAccidentalToolToNote",
    value: function applyAccidentalToolToNote(noteId, toolType) {
      if (!noteId || this.isNoteFixed(noteId)) return false;
      var currentCls = this._getAttachedAccidentalClass(noteId);
      if ((0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.isMaxedDouble)(currentCls, toolType)) {
        this._hintIgnoredAccidental(noteId);
        return false;
      }
      var nextCls = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.nextAccidentalClass)(currentCls, toolType);
      if (toolType === "natural" && currentCls === "music-font__natural") return false;
      if (nextCls === currentCls) return false;
      this.attachAccidentalToNote(noteId, nextCls);
      this._emitNoteState(noteId, "user");
      return true;
    }
  }, {
    key: "_ensureAudio",
    value: function () {
      var _ensureAudio2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              if (this._soundEnabled()) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              if (!this._audioReady) {
                _context.n = 2;
                break;
              }
              return _context.a(2);
            case 2:
              if (window.Tone) {
                _context.n = 3;
                break;
              }
              return _context.a(2);
            case 3:
              _context.n = 4;
              return Tone.start();
            case 4:
              this._synth = _games_shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_2__.GameAudio.createStaffNoteSynth();
              this._audioReady = true;
            case 5:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function _ensureAudio() {
        return _ensureAudio2.apply(this, arguments);
      }
      return _ensureAudio;
    }()
  }, {
    key: "_stepToMidi",
    value: function _stepToMidi(step) {
      var diatonic = [0, 2, 4, 5, 7, 9, 11];
      var baseC;
      var baseIndex;
      switch (this.opts.clef) {
        case "bass":
          baseC = 36;
          baseIndex = 4;
          break;
        case "alto":
          baseC = 48;
          baseIndex = 3;
          break;
        case "tenor":
          baseC = 48;
          baseIndex = 1;
          break;
        case "treble":
        default:
          baseC = 60;
          baseIndex = 2;
          break;
      }
      var idx = baseIndex + step;
      var octaveShift = Math.floor(idx / 7);
      var noteIndex = (idx % 7 + 7) % 7;
      return baseC + diatonic[noteIndex] + octaveShift * 12;
    }
  }, {
    key: "_accidentalClassToOffset",
    value: function _accidentalClassToOffset(cls) {
      if (!cls) return 0;
      if (cls.includes("music-font__doublesharp")) return +2;
      if (cls.includes("music-font__sharp")) return +1;
      if (cls.includes("music-font__doubleflat")) return -2;
      if (cls.includes("music-font__flat")) return -1;
      return 0;
    }
  }, {
    key: "_emitNoteState",
    value: function _emitNoteState(noteId, source) {
      var $note = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      if (!$note.length) return;
      var step = this.yToStep(parseFloat($note.css("top")));
      var accCls = this._getAttachedAccidentalClass(noteId);
      var accOff = this._accidentalClassToOffset(accCls);
      var midi = this._stepToMidi(step) + accOff;
      this.$el.trigger("staff:noteState", {
        noteId: noteId,
        step: step,
        accidentalClass: accCls,
        midi: midi,
        source: source || "unknown"
      });
    }
  }, {
    key: "_playStep",
    value: function () {
      var _playStep2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(step, accidentalOffset) {
        var midi;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              if (!(!this._soundEnabled() || !Number.isFinite(step))) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2);
            case 1:
              _context2.n = 2;
              return this._ensureAudio();
            case 2:
              if (this._synth) {
                _context2.n = 3;
                break;
              }
              return _context2.a(2);
            case 3:
              midi = this._stepToMidi(step) + (accidentalOffset || 0);
              if (this._synth.triggerRelease) this._synth.triggerRelease();
              this._synth.triggerAttackRelease(Tone.Frequency(midi, "midi"), 0.5, undefined, _games_shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_2__.GameAudio.scale("staffNote", 1));
            case 4:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function _playStep(_x, _x2) {
        return _playStep2.apply(this, arguments);
      }
      return _playStep;
    }()
  }, {
    key: "playStep",
    value: function () {
      var _playStep3 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(step) {
        var accidentalOffset,
          _args3 = arguments;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.n) {
            case 0:
              accidentalOffset = _args3.length > 1 && _args3[1] !== undefined ? _args3[1] : 0;
              _context3.n = 1;
              return this._playStep(step, accidentalOffset);
            case 1:
              return _context3.a(2);
          }
        }, _callee3, this);
      }));
      function playStep(_x3) {
        return _playStep3.apply(this, arguments);
      }
      return playStep;
    }()
  }, {
    key: "_releaseHeldStep",
    value: function _releaseHeldStep() {
      var _this$_synth;
      if (this._holdSoundTimer) {
        window.clearTimeout(this._holdSoundTimer);
        this._holdSoundTimer = null;
      }
      this._pendingHeldStep = null;
      this._pendingHeldAccidentalOffset = 0;
      if (this._heldMidi != null && (_this$_synth = this._synth) !== null && _this$_synth !== void 0 && _this$_synth.triggerRelease) this._synth.triggerRelease();
      this._heldMidi = null;
    }
  }, {
    key: "_startHeldStep",
    value: function () {
      var _startHeldStep2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(step) {
        var accidentalOffset,
          midi,
          _args4 = arguments;
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              accidentalOffset = _args4.length > 1 && _args4[1] !== undefined ? _args4[1] : 0;
              if (!(!this._soundEnabled() || !Number.isFinite(step))) {
                _context4.n = 1;
                break;
              }
              return _context4.a(2);
            case 1:
              _context4.n = 2;
              return this._ensureAudio();
            case 2:
              if (this._synth) {
                _context4.n = 3;
                break;
              }
              return _context4.a(2);
            case 3:
              if (!(this._pendingHeldStep !== step)) {
                _context4.n = 4;
                break;
              }
              return _context4.a(2);
            case 4:
              midi = this._stepToMidi(step) + (accidentalOffset || 0);
              if (!(this._heldMidi === midi)) {
                _context4.n = 5;
                break;
              }
              return _context4.a(2);
            case 5:
              if (this._synth.triggerRelease) this._synth.triggerRelease();
              this._synth.triggerAttack(Tone.Frequency(midi, "midi"), undefined, _games_shared_GameAudio_js__WEBPACK_IMPORTED_MODULE_2__.GameAudio.scale("staffNote", 1));
              this._heldMidi = midi;
            case 6:
              return _context4.a(2);
          }
        }, _callee4, this);
      }));
      function _startHeldStep(_x4) {
        return _startHeldStep2.apply(this, arguments);
      }
      return _startHeldStep;
    }()
  }, {
    key: "_scheduleHeldStep",
    value: function _scheduleHeldStep(step) {
      var _this3 = this;
      var accidentalOffset = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      if (!this._soundEnabled() || !Number.isFinite(step)) return;
      this._pendingHeldStep = step;
      this._pendingHeldAccidentalOffset = accidentalOffset || 0;
      if (this._heldMidi != null) {
        void this._startHeldStep(step, this._pendingHeldAccidentalOffset);
        return;
      }
      if (this._holdSoundTimer) return;
      this._holdSoundTimer = window.setTimeout(function () {
        _this3._holdSoundTimer = null;
        void _this3._startHeldStep(_this3._pendingHeldStep, _this3._pendingHeldAccidentalOffset);
      }, this._holdSoundDelayMs);
    }
  }, {
    key: "setNoteFixed",
    value: function setNoteFixed(noteId, fixed) {
      var on = !!fixed;
      this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]")).toggleClass("fixed", on);
      this.$el.find(".ledger[data-for-note-id=\"".concat(noteId, "\"]")).toggleClass("fixed", on);
      this.$el.find(".accidental[data-for-note-id=\"".concat(noteId, "\"]")).toggleClass("fixed", on);
    }
  }, {
    key: "addFixedNote",
    value: function addFixedNote(cfg) {
      var c = cfg || {};
      var id = this.addNote({
        step: c.step,
        y: c.y,
        x: c.x,
        id: c.id,
        ledger: c.ledger,
        className: c.className || ""
      });
      if (!id) return null;
      if (c.accidentalClass) this.attachAccidentalToNote(id, c.accidentalClass);
      this.setNoteFixed(id, true);
      return id;
    }
  }, {
    key: "clearNotes",
    value: function clearNotes() {
      this.$el.find(".note, .ledger, .accidental").remove();
      this._previewClear();
      this._syncDynamicHeight();
      this.$el.trigger("staff:userNotesChanged", {
        count: this._userNoteCount()
      });
    }
  }, {
    key: "removeNote",
    value: function removeNote(id) {
      var opts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      if (opts.smoke === true) {
        var noteEl = this.$el.find(".note[data-note-id=\"".concat(id, "\"]"))[0];
        this._animations.playNoteRemoveSmoke(noteEl);
      }
      this.$el.find(".note[data-note-id=\"".concat(id, "\"]")).remove();
      this.$el.find(".ledger[data-for-note-id=\"".concat(id, "\"]")).remove();
      this._removeAccidentalForNote(id);
      this._resolveNoteOverlaps();
      this._syncDynamicHeight();
      this.$el.trigger("staff:userNotesChanged", {
        count: this._userNoteCount()
      });
    }
  }, {
    key: "addNote",
    value: function addNote(cfg) {
      var c = cfg || {};
      var step = Number.isFinite(c.step) ? Number(c.step) : null;
      if (Number.isFinite(step) && !this._isStepAllowed(step)) return null;
      if (Number.isFinite(step) && !c.allowOccupied && this._isStepOccupied(step, null)) return null;
      var y = Number.isFinite(c.y) ? Number(c.y) : Number.isFinite(step) ? this.stepToY(step) : null;
      if (!Number.isFinite(y)) throw new Error("addNote: provide either y or step");
      var x = Number.isFinite(c.x) ? Number(c.x) : this.centerX();
      var id = c.id || "".concat(this.opts.noteIdPrefix).concat(this._idCounter++);
      var $note = $('<div class="note"><span class="lettername"></span></div>').attr("data-note-id", id).css({
        left: "".concat(x, "px"),
        top: "".concat(y, "px")
      });
      if (c.className) $note.addClass(c.className);
      this.$el.append($note);
      if (c.ledger === true || c.ledger !== false && Number.isFinite(step)) {
        this._renderLedgers(id, x, step);
      }
      if (!c.skipResolve) this._resolveNoteOverlaps();else this._repositionAllAccidentals();
      this._syncDynamicHeight();
      return id;
    }
  }, {
    key: "moveNote",
    value: function moveNote(id, pos) {
      var p = pos || {};
      var $note = this.$el.find(".note[data-note-id=\"".concat(id, "\"]"));
      if (!$note.length) return;
      var x = Number.isFinite(p.x) ? Number(p.x) : null;
      var step = Number.isFinite(p.step) ? Number(p.step) : null;
      if (Number.isFinite(step) && !this._isStepAllowed(step)) return;
      var y = Number.isFinite(p.y) ? Number(p.y) : Number.isFinite(step) ? this.stepToY(step) : null;
      if (Number.isFinite(x)) $note.css("left", "".concat(x, "px"));
      if (Number.isFinite(y)) $note.css("top", "".concat(y, "px"));
      if (Number.isFinite(step)) {
        var noteX = Number.isFinite(x) ? x : parseFloat($note.css("left"));
        this._renderLedgers(id, noteX, step);
      }
      this._repositionAllAccidentals();
      this._syncDynamicHeight();
    }
  }, {
    key: "_previewSet",
    value: function _previewSet(step) {
      if (!this._preview) this._preview = $('<div class="note preview"></div>').appendTo(this.$el);
      this._preview.css({
        left: "".concat(this.centerX(), "px"),
        top: "".concat(this.stepToY(step), "px")
      });
      this._previewStep = step;
      this._previewLedgersSet(step);
      this._syncDynamicHeight();
    }
  }, {
    key: "_previewClear",
    value: function _previewClear() {
      if (this._preview) {
        this._preview.remove();
        this._preview = null;
        this._previewStep = null;
      }
      this._previewLedgersClear();
      this._syncDynamicHeight();
    }
  }, {
    key: "_resolveNoteOverlaps",
    value: function _resolveNoteOverlaps() {
      var self = this;
      var GAP = this.opts.noteOverlapGap;
      var MAX_ITERS = 20;
      function notesArray() {
        return self.$el.find(".note").toArray().map(function (el) {
          var $el = $(el);
          return {
            el: el,
            $el: $el,
            step: self.yToStep(parseFloat($el.css("top"))),
            lockX: self._noteLocksX(el)
          };
        });
      }
      function noteByStep(list) {
        var map = {};
        for (var i = 0; i < list.length; i++) {
          var s = list[i].step;
          (map[s] || (map[s] = [])).push(list[i]);
        }
        return map;
      }
      function centerAll(list) {
        var cx = self.centerX();
        for (var i = 0; i < list.length; i++) {
          if (list[i].lockX) continue;
          var id = list[i].$el.attr("data-note-id");
          self.moveNote(id, {
            x: cx,
            step: list[i].step
          });
          list[i]._shifted = false;
        }
      }
      function shiftUpperToTouch(upper, stepMap) {
        if (upper.lockX) return false;
        var upperRect = upper.el.getBoundingClientRect();
        var lowerEls = stepMap[upper.step - 1];
        if (!lowerEls || !lowerEls.length) return false;
        var lowerRect = lowerEls[0].el.getBoundingClientRect();
        if (!self._rectsOverlap(upperRect, lowerRect)) return false;
        var dx = lowerRect.right - upperRect.left + GAP;
        var id = upper.$el.attr("data-note-id");
        self.moveNote(id, {
          x: parseFloat(upper.$el.css("left")) + dx,
          step: upper.step
        });
        return true;
      }
      for (var iter = 0; iter < MAX_ITERS; iter++) {
        var list = notesArray();
        if (!list.length) return;
        centerAll(list);
        var stepMap = noteByStep(list);
        var steps = Object.keys(stepMap).map(function (s) {
          return parseInt(s, 10);
        }).sort(function (a, b) {
          return a - b;
        });
        var changed = false;
        for (var i = 0; i < steps.length; i++) {
          var s = steps[i];
          if (!stepMap[s] || !stepMap[s + 1]) continue;
          var lower = stepMap[s][0];
          var upper = stepMap[s + 1][0];
          if (!lower._shifted) {
            if (shiftUpperToTouch(upper, stepMap)) changed = true;
            upper._shifted = true;
          }
        }
        if (!changed) {
          self._repositionAllAccidentals();
          return;
        }
      }
      self._repositionAllAccidentals();
    }
  }, {
    key: "_setDraggingVisual",
    value: function _setDraggingVisual(noteId, on) {
      var $note = this.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
      var $ledgers = this.$el.find(".ledger[data-for-note-id=\"".concat(noteId, "\"]"));
      var $acc = this.$el.find(".accidental[data-for-note-id=\"".concat(noteId, "\"]"));
      $note.toggleClass("dragging", !!on);
      $ledgers.toggleClass("dragging", !!on);
      $acc.toggleClass("dragging", !!on);
    }
  }, {
    key: "_applyDraggedAdjacencyX",
    value: function _applyDraggedAdjacencyX(dragId) {
      if (this._noteLocksX(dragId)) return;
      var $drag = this.$el.find(".note[data-note-id=\"".concat(dragId, "\"]"));
      if (!$drag.length) return;
      var dragStep = this.yToStep(parseFloat($drag.css("top")));
      var center = this.centerX();
      var gap = this.opts.noteOverlapGap;
      this.moveNote(dragId, {
        x: center
      });
      var lowerId = this._getNoteIdAtStep(dragStep - 1, dragId);
      if (!lowerId) return;
      if (!this._isCenteredX(lowerId)) return;
      var $lower = this.$el.find(".note[data-note-id=\"".concat(lowerId, "\"]"));
      if (!$lower.length) return;
      var upperRect = $drag[0].getBoundingClientRect();
      var lowerRect = $lower[0].getBoundingClientRect();
      if (!this._rectsOverlap(upperRect, lowerRect)) return;
      var dx = lowerRect.right - upperRect.left + gap;
      this.moveNote(dragId, {
        x: center + dx
      });
    }
  }, {
    key: "enableGhostClickCreate",
    value: function enableGhostClickCreate() {
      var self = this;
      this.$el.off(".previewCreate");
      $(window).off("blur.previewCreate");
      this.$el.on("pointerdown.previewCreate", function (e) {
        if ($(e.target).closest(".note, .accidental").length) return;
        if (self._userNoteCount() >= self._maxUserNotes()) return;
        e.preventDefault();
        var _getPointerPageXY = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerPageXY)(e),
          pageY = _getPointerPageXY.y;
        var initialStep = self.yToStep(self._pageYToLocalY(pageY));
        if (!self._isStepAllowed(initialStep)) return;
        if (self.isStepBlocked(initialStep, null)) return;
        self._previewState.active = true;
        self._previewState.step = initialStep;
        self._previewState.lastSoundStep = initialStep;
        self._previewSet(initialStep);
        if (self._soundEnabled()) {
          self._ensureAudio();
          self._playStep(initialStep, 0);
          self._scheduleHeldStep(initialStep, 0);
        }
        var pointerId = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerId)(e);
        if (this.setPointerCapture && pointerId != null) this.setPointerCapture(pointerId);
        self.$el.off("pointermove.previewCreate").on("pointermove.previewCreate", function (ev) {
          if (!self._previewState.active) return;
          if (self._userNoteCount() >= self._maxUserNotes()) {
            self._previewState.active = false;
            self._releaseHeldStep();
            self._previewClear();
            self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");
            return;
          }
          var _getPointerPageXY2 = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerPageXY)(ev),
            py = _getPointerPageXY2.y;
          var s = self.yToStep(self._pageYToLocalY(py));
          if (!self._isStepAllowed(s)) return;
          if (self.isStepBlocked(s, null)) {
            self._previewState.step = null;
            self._releaseHeldStep();
            self._previewClear();
            return;
          }
          self._previewState.step = s;
          self._previewSet(s);
          if (self._soundEnabled() && s !== self._previewState.lastSoundStep) {
            self._previewState.lastSoundStep = s;
            self._scheduleHeldStep(s, 0);
          }
        });
        self.$el.off("pointerup.previewCreate pointercancel.previewCreate").on("pointerup.previewCreate pointercancel.previewCreate", function () {
          if (!self._previewState.active) return;
          self._previewState.active = false;
          var finalStep = self._previewState.step;
          self._releaseHeldStep();
          self._previewClear();
          self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");
          if (!Number.isFinite(finalStep)) return;
          if (!self._isStepAllowed(finalStep)) return;
          if (self.isStepBlocked(finalStep, null)) return;
          if (self._isStepOccupied(finalStep, null)) return;
          if (self._userNoteCount() >= self._maxUserNotes()) return;
          var createdId = self.addNote({
            step: finalStep
          });
          if (createdId) {
            self.$el.trigger("staff:userNoteAdded", {
              noteId: createdId,
              step: finalStep
            });
            self._emitNoteState(createdId, "user");
            self._suppressNextClick.noteId = createdId;
            self._suppressNextClick.until = Date.now() + 700;
          }
        });
      });
      $(window).on("blur.previewCreate", function () {
        if (!self._previewState.active) return;
        self._previewState.active = false;
        self._releaseHeldStep();
        self._previewClear();
        self.$el.off("pointermove.previewCreate pointerup.previewCreate pointercancel.previewCreate");
      });
    }
  }, {
    key: "enableNoteDragAndClickDelete",
    value: function enableNoteDragAndClickDelete() {
      var self = this;
      var d = this._drag;
      this.$el.off(".noteDrag");
      function startDragFromNoteEl(noteEl, e) {
        e.preventDefault();
        if ($(noteEl).hasClass("fixed")) return;
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();else e.stopPropagation();
        var pointerId = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerId)(e);
        var $note = $(noteEl);
        d.isDragging = false;
        d.movedPx = 0;
        d.startPageY = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerPageXY)(e).y;
        d.noteId = $note.attr("data-note-id");
        d.startStep = self.yToStep(parseFloat($note.css("top")));
        d.lastTargetStep = d.startStep;
        d.lastSoundStep = d.startStep;
        d.dropOnOccupied = false;
        d.outOfRange = false;
        self._setDraggingVisual(d.noteId, true);
        if (self._soundEnabled()) {
          var accCls = self._getAttachedAccidentalClass(d.noteId);
          var accOff = self._accidentalClassToOffset(accCls);
          self._scheduleHeldStep(d.startStep, accOff);
        }
        var capEl = e.currentTarget && e.currentTarget.setPointerCapture ? e.currentTarget : null;
        if (capEl && pointerId != null) capEl.setPointerCapture(pointerId);
        self.$el.off("pointermove.noteDrag").on("pointermove.noteDrag", function (ev) {
          var evPointerId = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerId)(ev);
          if (pointerId != null && evPointerId != null && evPointerId !== pointerId) return;
          var py = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerPageXY)(ev).y;
          var dy = py - d.startPageY;
          d.movedPx = Math.max(d.movedPx, Math.abs(dy));
          if (!d.isDragging && d.movedPx >= d.thresholdPx) {
            d.isDragging = true;
            var _accCls = self._getAttachedAccidentalClass(d.noteId);
            var _accOff = self._accidentalClassToOffset(_accCls);
            self._pendingHeldStep = d.lastTargetStep;
            self._pendingHeldAccidentalOffset = _accOff;
            void self._startHeldStep(d.lastTargetStep, _accOff);
          }
          if (!d.isDragging) return;
          var targetStep = self.yToStep(self._pageYToLocalY(py));
          if (!self._isStepAllowed(targetStep)) {
            d.outOfRange = true;
            return;
          }
          d.outOfRange = false;
          d.lastTargetStep = targetStep;
          self.moveNote(d.noteId, {
            step: targetStep
          });
          d.dropOnOccupied = self._isStepOccupied(targetStep, d.noteId);
          self._applyDraggedAdjacencyX(d.noteId);
          if (self._soundEnabled() && targetStep !== d.lastSoundStep) {
            d.lastSoundStep = targetStep;
            var _accCls2 = self._getAttachedAccidentalClass(d.noteId);
            var _accOff2 = self._accidentalClassToOffset(_accCls2);
            self._scheduleHeldStep(targetStep, _accOff2);
          }
        });
        self.$el.off("pointerup.noteDrag pointercancel.noteDrag").on("pointerup.noteDrag pointercancel.noteDrag", function (ev2) {
          var evPointerId = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerId)(ev2);
          if (pointerId != null && evPointerId != null && evPointerId !== pointerId) return;
          self.$el.off("pointermove.noteDrag pointerup.noteDrag pointercancel.noteDrag");
          var hadHeldSound = self._heldMidi != null;
          self._releaseHeldStep();
          d.swallowClick = d.isDragging || hadHeldSound;
          self._setDraggingVisual(d.noteId, false);
          if (d.outOfRange || d.dropOnOccupied) {
            self.removeNote(d.noteId);
          } else {
            self.moveNote(d.noteId, {
              step: d.lastTargetStep
            });
            self._resolveNoteOverlaps();
            self._emitNoteState(d.noteId, "user");
          }
          d.noteId = null;
          d.isDragging = false;
          d.movedPx = 0;
          d.startStep = null;
          d.lastTargetStep = null;
          d.lastSoundStep = null;
          d.dropOnOccupied = false;
          d.outOfRange = false;
        });
      }
      this.$el.on("pointerdown.noteDrag", ".accidental", function (e) {
        e.stopPropagation();
      });
      this.$el.on("pointerdown.noteDrag", ".note", function (e) {
        startDragFromNoteEl(this, e);
      });
      this.$el.on("pointerdown.noteDrag", function (e) {
        if ($(e.target).closest(".accidental").length) return;
        if ($(e.target).closest(".note").length) return;
        var _getPointerPageXY3 = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.getPointerPageXY)(e),
          pageY = _getPointerPageXY3.y;
        var step = self.yToStep(self._pageYToLocalY(pageY));
        var idAtStep = self._getNoteIdAtStep(step, null);
        if (!idAtStep) return;
        var noteEl = self.$el.find(".note[data-note-id=\"".concat(idAtStep, "\"]"))[0];
        if (!noteEl) return;
        startDragFromNoteEl(noteEl, e);
      });
      this.$el.on("click.noteDrag", ".accidental", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var noteId = this.getAttribute("data-for-note-id");
        if (!noteId) return;
        if (self.isNoteFixed(noteId)) return;
        self._removeAccidentalForNote(noteId);
        self._repositionAllAccidentals();
        self._emitNoteState(noteId, "user");
      });
      this.$el.on("click.noteDrag", ".note", function (e) {
        var clickedId = $(this).attr("data-note-id");
        if (self._suppressNextClick.noteId && clickedId === self._suppressNextClick.noteId && Date.now() < self._suppressNextClick.until) {
          self._suppressNextClick.noteId = null;
          self._suppressNextClick.until = 0;
          e.preventDefault();
          e.stopPropagation();
          return;
        }
        if (d.swallowClick) {
          e.preventDefault();
          e.stopPropagation();
          d.swallowClick = false;
          return;
        }
        self.removeNote(clickedId, {
          smoke: true
        });
      });
    }
  }, {
    key: "enableAccidentalDrag",
    value: function enableAccidentalDrag($toolEls) {
      var self = this;
      $toolEls.addClass("accidental-tool");
      function resetAccDrag() {
        self._accDragSound.noteId = null;
        self._accDragSound.step = null;
        self._accDragSound.toolType = null;
        self._accDragSound.prospectiveCls = null;
        self._accSnap.noteId = null;
        self._accSnap.dist = null;
        self._accSnap.localY = null;
      }
      $toolEls.draggable({
        helper: "clone",
        appendTo: "body",
        zIndex: 9999,
        revert: "invalid",
        scroll: false,
        start: function start(_event, ui) {
          ui.helper.addClass("dragging accidental-tool");
          if (self._soundEnabled()) self._ensureAudio();
          resetAccDrag();
          self._accDragSound.toolType = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.toolTypeFromEl)($(this));
        },
        drag: function drag(event) {
          var toolType = self._accDragSound.toolType;
          if (!toolType) return;
          var pageX = event.pageX;
          var pageY = event.pageY;
          if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
          if (!Number.isFinite(pageX) && event.originalEvent) pageX = event.originalEvent.pageX;
          var off = self.$el.offset();
          var x = pageX - off.left;
          var y = pageY - off.top;
          self._accSnap.localY = y;
          if (x < 0 || y < 0 || x > self.$el.width() || y > self.$el.height()) {
            resetAccDrag();
            self._accDragSound.toolType = toolType;
            return;
          }
          var nearest = self._nearestEditableNoteByLocalY(y);
          if (!nearest) {
            self._accSnap.noteId = null;
            self._accSnap.dist = null;
            self._accDragSound.noteId = null;
            self._accDragSound.step = null;
            self._accDragSound.prospectiveCls = null;
            return;
          }
          var noteId = nearest.noteId;
          self._accSnap.noteId = noteId;
          self._accSnap.dist = nearest.dist;
          var $note = self.$el.find(".note[data-note-id=\"".concat(noteId, "\"]"));
          if (!$note.length) return;
          var step = self.yToStep(parseFloat($note.css("top")));
          if (!self._isStepAllowed(step)) return;
          var currentCls = self._getAttachedAccidentalClass(noteId);
          var prospectiveCls = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.nextAccidentalClass)(currentCls, toolType);
          if ((0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.isMaxedDouble)(currentCls, toolType)) {
            self._accDragSound.noteId = null;
            self._accDragSound.step = null;
            self._accDragSound.prospectiveCls = null;
            return;
          }
          if (noteId === self._accDragSound.noteId && step === self._accDragSound.step && prospectiveCls === self._accDragSound.prospectiveCls) return;
          self._accDragSound.noteId = noteId;
          self._accDragSound.step = step;
          self._accDragSound.prospectiveCls = prospectiveCls;
          if (self._soundEnabled()) {
            var accOff = self._accidentalClassToOffset(prospectiveCls);
            self._playStep(step, accOff);
          }
        },
        stop: function stop() {
          resetAccDrag();
        }
      });
    }
  }, {
    key: "enableAccidentalDropOnStaff",
    value: function enableAccidentalDropOnStaff() {
      var self = this;
      this.$el.droppable({
        accept: ".accidental-tool",
        tolerance: "pointer",
        drop: function drop(event, ui) {
          var toolType = (0,_staffUtils_js__WEBPACK_IMPORTED_MODULE_0__.toolTypeFromEl)(ui.draggable);
          if (!toolType) return;
          var localY;
          if (self._accSnap && self._accSnap.localY != null) localY = self._accSnap.localY;else {
            var pageY = event.pageY;
            if (!Number.isFinite(pageY) && event.originalEvent) pageY = event.originalEvent.pageY;
            localY = self._pageYToLocalY(pageY);
          }
          var nearest = self._nearestEditableNoteByLocalY(localY);
          if (!nearest) return;
          self.applyAccidentalToolToNote(nearest.noteId, toolType);
        }
      });
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/staff/StaffAnimations.js"
/*!*****************************************************!*\
  !*** ./resources/js/games/staff/StaffAnimations.js ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   StaffAnimations: () => (/* binding */ StaffAnimations)
/* harmony export */ });
/* harmony import */ var _games_shared_mojsEffects_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../games/shared/mojsEffects.js */ "./resources/js/games/games/shared/mojsEffects.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var StaffAnimations = /*#__PURE__*/function () {
  function StaffAnimations($container) {
    _classCallCheck(this, StaffAnimations);
    this.$container = $container;
  }
  return _createClass(StaffAnimations, [{
    key: "playNoteRemoveSmoke",
    value: function playNoteRemoveSmoke(noteEl) {
      var opts = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      if (!noteEl) return;
      (0,_games_shared_mojsEffects_js__WEBPACK_IMPORTED_MODULE_0__.playSmokePuffAtElement)(noteEl, _objectSpread({
        parentEl: document.body
      }, opts));
    }
  }]);
}();

/***/ },

/***/ "./resources/js/games/staff/staffUtils.js"
/*!************************************************!*\
  !*** ./resources/js/games/staff/staffUtils.js ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACCIDENTAL_CLASSES: () => (/* binding */ ACCIDENTAL_CLASSES),
/* harmony export */   CLEF_LAYOUT_VARS: () => (/* binding */ CLEF_LAYOUT_VARS),
/* harmony export */   accidentalClassToSymbol: () => (/* binding */ accidentalClassToSymbol),
/* harmony export */   accidentalClassToText: () => (/* binding */ accidentalClassToText),
/* harmony export */   getPointerId: () => (/* binding */ getPointerId),
/* harmony export */   getPointerPageXY: () => (/* binding */ getPointerPageXY),
/* harmony export */   isMaxedDouble: () => (/* binding */ isMaxedDouble),
/* harmony export */   nextAccidentalClass: () => (/* binding */ nextAccidentalClass),
/* harmony export */   normalizeClef: () => (/* binding */ normalizeClef),
/* harmony export */   pickOne: () => (/* binding */ pickOne),
/* harmony export */   pickWeighted: () => (/* binding */ pickWeighted),
/* harmony export */   pxFromCss: () => (/* binding */ pxFromCss),
/* harmony export */   randomInt: () => (/* binding */ randomInt),
/* harmony export */   spellNoteFromState: () => (/* binding */ spellNoteFromState),
/* harmony export */   spellNoteTextFromState: () => (/* binding */ spellNoteTextFromState),
/* harmony export */   stepToLetterOctave: () => (/* binding */ stepToLetterOctave),
/* harmony export */   toArrayMaybe: () => (/* binding */ toArrayMaybe),
/* harmony export */   toolTypeFromEl: () => (/* binding */ toolTypeFromEl)
/* harmony export */ });
// resources/js/music/staff/staffUtils.js

var ACCIDENTAL_CLASSES = ["music-font__sharp", "music-font__doublesharp", "music-font__flat", "music-font__doubleflat", "music-font__natural"];
var CLEF_LAYOUT_VARS = {
  treble: {
    "--clef-width": "140px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "34px",
    "--clef-left-nudge": "28px"
  },
  bass: {
    "--clef-width": "76px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "15px",
    "--clef-left-nudge": "-12px"
  },
  alto: {
    "--clef-width": "88px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "27.5px",
    "--clef-left-nudge": "-10px"
  },
  tenor: {
    "--clef-width": "88px",
    "--clef-height": "calc(var(--staff-line-gap) * 6)",
    "--clef-top": "2.5px",
    "--clef-left-nudge": "-10px"
  }
};
function pxFromCss(css, varName, fallback) {
  var v = css.getPropertyValue(varName);
  var n = parseFloat(v);
  return Number.isFinite(n) ? n : fallback;
}
function getPointerPageXY(e) {
  var oe = e.originalEvent || e;
  if (oe.touches && oe.touches.length) return {
    x: oe.touches[0].pageX,
    y: oe.touches[0].pageY
  };
  if (oe.changedTouches && oe.changedTouches.length) return {
    x: oe.changedTouches[0].pageX,
    y: oe.changedTouches[0].pageY
  };
  return {
    x: oe.pageX,
    y: oe.pageY
  };
}
function getPointerId(e) {
  var oe = e.originalEvent || e;
  return oe && oe.pointerId != null ? oe.pointerId : null;
}
function randomInt(min, maxInclusive) {
  var random = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : Math.random;
  return Math.floor(random() * (maxInclusive - min + 1)) + min;
}
function pickOne(arr) {
  var random = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : Math.random;
  if (!Array.isArray(arr) || !arr.length) return null;
  return arr[Math.floor(random() * arr.length)];
}
function pickWeighted(items) {
  var random = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : Math.random;
  var list = Array.isArray(items) ? items.filter(function (x) {
    return x && Number.isFinite(x.weight) && x.weight > 0;
  }) : [];
  if (!list.length) return null;
  var total = list.reduce(function (sum, x) {
    return sum + x.weight;
  }, 0);
  var r = random() * total;
  for (var i = 0; i < list.length; i++) {
    r -= list[i].weight;
    if (r <= 0) return list[i].value;
  }
  return list[list.length - 1].value;
}
function toArrayMaybe(v) {
  if (v == null) return [];
  return Array.isArray(v) ? v : [v];
}
function normalizeClef(clef) {
  if (clef == null) return null;
  var c = String(clef || "treble").toLowerCase();
  if (c === "bass") return "bass";
  if (c === "alto") return "alto";
  if (c === "tenor") return "tenor";
  return "treble";
}
function toolTypeFromEl($el) {
  if ($el.hasClass("music-font__sharp")) return "sharp";
  if ($el.hasClass("music-font__flat")) return "flat";
  if ($el.hasClass("music-font__natural")) return "natural";
  return null;
}
function nextAccidentalClass(currentCls, toolType) {
  if (toolType === "sharp") {
    if (currentCls === "music-font__doublesharp") return "music-font__doublesharp";
    if (currentCls === "music-font__sharp") return "music-font__doublesharp";
    return "music-font__sharp";
  }
  if (toolType === "flat") {
    if (currentCls === "music-font__doubleflat") return "music-font__doubleflat";
    if (currentCls === "music-font__flat") return "music-font__doubleflat";
    return "music-font__flat";
  }
  if (toolType === "natural") return "music-font__natural";
  return currentCls || null;
}
function isMaxedDouble(currentCls, toolType) {
  return toolType === "sharp" && currentCls === "music-font__doublesharp" || toolType === "flat" && currentCls === "music-font__doubleflat";
}
function accidentalClassToText(cls) {
  if (!cls) return "";
  if (cls.includes("music-font__doublesharp")) return "<span class='doublesharp-symbol'>𝄪</span>";
  if (cls.includes("music-font__sharp")) return "<span class='sharp-symbol'>♯</span>";
  if (cls.includes("music-font__doubleflat")) return "<span class='doubleflat-symbol'>𝄫</span>";
  if (cls.includes("music-font__flat")) return "<span class='flat-symbol'>♭</span>";
  return "";
}
function accidentalClassToSymbol(cls) {
  if (!cls) return "";
  if (cls.includes("music-font__doublesharp")) return "𝄪";
  if (cls.includes("music-font__sharp")) return "♯";
  if (cls.includes("music-font__doubleflat")) return "𝄫";
  if (cls.includes("music-font__flat")) return "♭";
  return "";
}
function stepToLetterOctave(staff, step) {
  var letters = ["C", "D", "E", "F", "G", "A", "B"];
  var baseC;
  var baseIndex;
  switch (staff.getClef()) {
    case "bass":
      baseC = 36;
      baseIndex = 4;
      break;
    case "alto":
      baseC = 48;
      baseIndex = 3;
      break;
    case "tenor":
      baseC = 48;
      baseIndex = 1;
      break;
    case "treble":
    default:
      baseC = 60;
      baseIndex = 2;
      break;
  }
  var idx = baseIndex + step;
  var octaveShift = Math.floor(idx / 7);
  var letterIndex = (idx % 7 + 7) % 7;
  var baseOctave = Math.floor(baseC / 12) - 1;
  var octave = baseOctave + octaveShift;
  return {
    letter: letters[letterIndex],
    octave: octave
  };
}
function spellNoteFromState(staff, step, accidentalClass) {
  var _stepToLetterOctave = stepToLetterOctave(staff, step),
    letter = _stepToLetterOctave.letter,
    octave = _stepToLetterOctave.octave;
  var acc = accidentalClassToText(accidentalClass);
  return "".concat(letter).concat(acc).concat(octave);
}
function spellNoteTextFromState(staff, step, accidentalClass) {
  var _stepToLetterOctave2 = stepToLetterOctave(staff, step),
    letter = _stepToLetterOctave2.letter,
    octave = _stepToLetterOctave2.octave;
  var acc = accidentalClassToSymbol(accidentalClass);
  return "".concat(letter).concat(acc).concat(octave);
}

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
/*!**************************************************!*\
  !*** ./resources/js/games/games/memorywizard.js ***!
  \**************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _duel_DuelClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../duel/DuelClient.js */ "./resources/js/games/duel/DuelClient.js");
/* harmony import */ var _memorywizard_MemoryWizard_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./memorywizard/MemoryWizard.js */ "./resources/js/games/games/memorywizard/MemoryWizard.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }


var options = window.__challengeOptions || {};
var clefUrls = window.__clefUrls || null;
(0,_duel_DuelClient_js__WEBPACK_IMPORTED_MODULE_0__.bootGame)(function (duelOptions) {
  return new _memorywizard_MemoryWizard_js__WEBPACK_IMPORTED_MODULE_1__.MemoryWizard(_objectSpread(_objectSpread({}, duelOptions || options), {}, {
    clefUrls: clefUrls
  }));
});
})();

/******/ })()
;