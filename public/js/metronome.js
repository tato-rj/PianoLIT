/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@popperjs/core/lib/createPopper.js"
/*!*********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/createPopper.js ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createPopper: () => (/* binding */ createPopper),
/* harmony export */   detectOverflow: () => (/* reexport safe */ _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__["default"]),
/* harmony export */   popperGenerator: () => (/* binding */ popperGenerator)
/* harmony export */ });
/* harmony import */ var _dom_utils_getCompositeRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./dom-utils/getCompositeRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./dom-utils/listScrollParents.js */ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _utils_orderModifiers_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./utils/orderModifiers.js */ "./node_modules/@popperjs/core/lib/utils/orderModifiers.js");
/* harmony import */ var _utils_debounce_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./utils/debounce.js */ "./node_modules/@popperjs/core/lib/utils/debounce.js");
/* harmony import */ var _utils_mergeByName_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./utils/mergeByName.js */ "./node_modules/@popperjs/core/lib/utils/mergeByName.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");









var DEFAULT_OPTIONS = {
  placement: 'bottom',
  modifiers: [],
  strategy: 'absolute'
};

function areValidElements() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  return !args.some(function (element) {
    return !(element && typeof element.getBoundingClientRect === 'function');
  });
}

function popperGenerator(generatorOptions) {
  if (generatorOptions === void 0) {
    generatorOptions = {};
  }

  var _generatorOptions = generatorOptions,
      _generatorOptions$def = _generatorOptions.defaultModifiers,
      defaultModifiers = _generatorOptions$def === void 0 ? [] : _generatorOptions$def,
      _generatorOptions$def2 = _generatorOptions.defaultOptions,
      defaultOptions = _generatorOptions$def2 === void 0 ? DEFAULT_OPTIONS : _generatorOptions$def2;
  return function createPopper(reference, popper, options) {
    if (options === void 0) {
      options = defaultOptions;
    }

    var state = {
      placement: 'bottom',
      orderedModifiers: [],
      options: Object.assign({}, DEFAULT_OPTIONS, defaultOptions),
      modifiersData: {},
      elements: {
        reference: reference,
        popper: popper
      },
      attributes: {},
      styles: {}
    };
    var effectCleanupFns = [];
    var isDestroyed = false;
    var instance = {
      state: state,
      setOptions: function setOptions(setOptionsAction) {
        var options = typeof setOptionsAction === 'function' ? setOptionsAction(state.options) : setOptionsAction;
        cleanupModifierEffects();
        state.options = Object.assign({}, defaultOptions, state.options, options);
        state.scrollParents = {
          reference: (0,_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_8__.isElement)(reference) ? (0,_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(reference) : reference.contextElement ? (0,_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(reference.contextElement) : [],
          popper: (0,_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(popper)
        }; // Orders the modifiers based on their dependencies and `phase`
        // properties

        var orderedModifiers = (0,_utils_orderModifiers_js__WEBPACK_IMPORTED_MODULE_4__["default"])((0,_utils_mergeByName_js__WEBPACK_IMPORTED_MODULE_6__["default"])([].concat(defaultModifiers, state.options.modifiers))); // Strip out disabled modifiers

        state.orderedModifiers = orderedModifiers.filter(function (m) {
          return m.enabled;
        });
        runModifierEffects();
        return instance.update();
      },
      // Sync update – it will always be executed, even if not necessary. This
      // is useful for low frequency updates where sync behavior simplifies the
      // logic.
      // For high frequency updates (e.g. `resize` and `scroll` events), always
      // prefer the async Popper#update method
      forceUpdate: function forceUpdate() {
        if (isDestroyed) {
          return;
        }

        var _state$elements = state.elements,
            reference = _state$elements.reference,
            popper = _state$elements.popper; // Don't proceed if `reference` or `popper` are not valid elements
        // anymore

        if (!areValidElements(reference, popper)) {
          return;
        } // Store the reference and popper rects to be read by modifiers


        state.rects = {
          reference: (0,_dom_utils_getCompositeRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(reference, (0,_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(popper), state.options.strategy === 'fixed'),
          popper: (0,_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(popper)
        }; // Modifiers have the ability to reset the current update cycle. The
        // most common use case for this is the `flip` modifier changing the
        // placement, which then needs to re-run all the modifiers, because the
        // logic was previously ran for the previous placement and is therefore
        // stale/incorrect

        state.reset = false;
        state.placement = state.options.placement; // On each update cycle, the `modifiersData` property for each modifier
        // is filled with the initial data specified by the modifier. This means
        // it doesn't persist and is fresh on each update.
        // To ensure persistent data, use `${name}#persistent`

        state.orderedModifiers.forEach(function (modifier) {
          return state.modifiersData[modifier.name] = Object.assign({}, modifier.data);
        });

        for (var index = 0; index < state.orderedModifiers.length; index++) {
          if (state.reset === true) {
            state.reset = false;
            index = -1;
            continue;
          }

          var _state$orderedModifie = state.orderedModifiers[index],
              fn = _state$orderedModifie.fn,
              _state$orderedModifie2 = _state$orderedModifie.options,
              _options = _state$orderedModifie2 === void 0 ? {} : _state$orderedModifie2,
              name = _state$orderedModifie.name;

          if (typeof fn === 'function') {
            state = fn({
              state: state,
              options: _options,
              name: name,
              instance: instance
            }) || state;
          }
        }
      },
      // Async and optimistically optimized update – it will not be executed if
      // not necessary (debounced to run at most once-per-tick)
      update: (0,_utils_debounce_js__WEBPACK_IMPORTED_MODULE_5__["default"])(function () {
        return new Promise(function (resolve) {
          instance.forceUpdate();
          resolve(state);
        });
      }),
      destroy: function destroy() {
        cleanupModifierEffects();
        isDestroyed = true;
      }
    };

    if (!areValidElements(reference, popper)) {
      return instance;
    }

    instance.setOptions(options).then(function (state) {
      if (!isDestroyed && options.onFirstUpdate) {
        options.onFirstUpdate(state);
      }
    }); // Modifiers have the ability to execute arbitrary code before the first
    // update cycle runs. They will be executed in the same order as the update
    // cycle. This is useful when a modifier adds some persistent data that
    // other modifiers need to use, but the modifier is run after the dependent
    // one.

    function runModifierEffects() {
      state.orderedModifiers.forEach(function (_ref) {
        var name = _ref.name,
            _ref$options = _ref.options,
            options = _ref$options === void 0 ? {} : _ref$options,
            effect = _ref.effect;

        if (typeof effect === 'function') {
          var cleanupFn = effect({
            state: state,
            name: name,
            instance: instance,
            options: options
          });

          var noopFn = function noopFn() {};

          effectCleanupFns.push(cleanupFn || noopFn);
        }
      });
    }

    function cleanupModifierEffects() {
      effectCleanupFns.forEach(function (fn) {
        return fn();
      });
      effectCleanupFns = [];
    }

    return instance;
  };
}
var createPopper = /*#__PURE__*/popperGenerator(); // eslint-disable-next-line import/no-unused-modules



/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/contains.js"
/*!***************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/contains.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ contains)
/* harmony export */ });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

function contains(parent, child) {
  var rootNode = child.getRootNode && child.getRootNode(); // First, attempt with faster native method

  if (parent.contains(child)) {
    return true;
  } // then fallback to custom implementation with Shadow DOM support
  else if (rootNode && (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__.isShadowRoot)(rootNode)) {
      var next = child;

      do {
        if (next && parent.isSameNode(next)) {
          return true;
        } // $FlowFixMe[prop-missing]: need a better way to handle this...


        next = next.parentNode || next.host;
      } while (next);
    } // Give up, the result is false


  return false;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js"
/*!****************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getBoundingClientRect)
/* harmony export */ });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isLayoutViewport.js */ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js");




function getBoundingClientRect(element, includeScale, isFixedStrategy) {
  if (includeScale === void 0) {
    includeScale = false;
  }

  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }

  var clientRect = element.getBoundingClientRect();
  var scaleX = 1;
  var scaleY = 1;

  if (includeScale && (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__.isHTMLElement)(element)) {
    scaleX = element.offsetWidth > 0 ? (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_1__.round)(clientRect.width) / element.offsetWidth || 1 : 1;
    scaleY = element.offsetHeight > 0 ? (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_1__.round)(clientRect.height) / element.offsetHeight || 1 : 1;
  }

  var _ref = (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__.isElement)(element) ? (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element) : window,
      visualViewport = _ref.visualViewport;

  var addVisualOffsets = !(0,_isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__["default"])() && isFixedStrategy;
  var x = (clientRect.left + (addVisualOffsets && visualViewport ? visualViewport.offsetLeft : 0)) / scaleX;
  var y = (clientRect.top + (addVisualOffsets && visualViewport ? visualViewport.offsetTop : 0)) / scaleY;
  var width = clientRect.width / scaleX;
  var height = clientRect.height / scaleY;
  return {
    width: width,
    height: height,
    top: y,
    right: x + width,
    bottom: y + height,
    left: x,
    x: x,
    y: y
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getClippingRect)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _getViewportRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getViewportRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js");
/* harmony import */ var _getDocumentRect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getDocumentRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js");
/* harmony import */ var _listScrollParents_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./listScrollParents.js */ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js");
/* harmony import */ var _getOffsetParent_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./contains.js */ "./node_modules/@popperjs/core/lib/dom-utils/contains.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../utils/rectToClientRect.js */ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");















function getInnerBoundingClientRect(element, strategy) {
  var rect = (0,_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_8__["default"])(element, false, strategy === 'fixed');
  rect.top = rect.top + element.clientTop;
  rect.left = rect.left + element.clientLeft;
  rect.bottom = rect.top + element.clientHeight;
  rect.right = rect.left + element.clientWidth;
  rect.width = element.clientWidth;
  rect.height = element.clientHeight;
  rect.x = rect.left;
  rect.y = rect.top;
  return rect;
}

function getClientRectFromMixedType(element, clippingParent, strategy) {
  return clippingParent === _enums_js__WEBPACK_IMPORTED_MODULE_0__.viewport ? (0,_utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__["default"])((0,_getViewportRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element, strategy)) : (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__.isElement)(clippingParent) ? getInnerBoundingClientRect(clippingParent, strategy) : (0,_utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__["default"])((0,_getDocumentRect_js__WEBPACK_IMPORTED_MODULE_2__["default"])((0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(element)));
} // A "clipping parent" is an overflowable container with the characteristic of
// clipping (or hiding) overflowing elements with a position different from
// `initial`


function getClippingParents(element) {
  var clippingParents = (0,_listScrollParents_js__WEBPACK_IMPORTED_MODULE_3__["default"])((0,_getParentNode_js__WEBPACK_IMPORTED_MODULE_9__["default"])(element));
  var canEscapeClipping = ['absolute', 'fixed'].indexOf((0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_6__["default"])(element).position) >= 0;
  var clipperElement = canEscapeClipping && (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__.isHTMLElement)(element) ? (0,_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_4__["default"])(element) : element;

  if (!(0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__.isElement)(clipperElement)) {
    return [];
  } // $FlowFixMe[incompatible-return]: https://github.com/facebook/flow/issues/1414


  return clippingParents.filter(function (clippingParent) {
    return (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__.isElement)(clippingParent) && (0,_contains_js__WEBPACK_IMPORTED_MODULE_10__["default"])(clippingParent, clipperElement) && (0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_11__["default"])(clippingParent) !== 'body';
  });
} // Gets the maximum area that the element is visible in due to any number of
// clipping parents


function getClippingRect(element, boundary, rootBoundary, strategy) {
  var mainClippingParents = boundary === 'clippingParents' ? getClippingParents(element) : [].concat(boundary);
  var clippingParents = [].concat(mainClippingParents, [rootBoundary]);
  var firstClippingParent = clippingParents[0];
  var clippingRect = clippingParents.reduce(function (accRect, clippingParent) {
    var rect = getClientRectFromMixedType(element, clippingParent, strategy);
    accRect.top = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_13__.max)(rect.top, accRect.top);
    accRect.right = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_13__.min)(rect.right, accRect.right);
    accRect.bottom = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_13__.min)(rect.bottom, accRect.bottom);
    accRect.left = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_13__.max)(rect.left, accRect.left);
    return accRect;
  }, getClientRectFromMixedType(element, firstClippingParent, strategy));
  clippingRect.width = clippingRect.right - clippingRect.left;
  clippingRect.height = clippingRect.bottom - clippingRect.top;
  clippingRect.x = clippingRect.left;
  clippingRect.y = clippingRect.top;
  return clippingRect;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js"
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getCompositeRect)
/* harmony export */ });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getNodeScroll_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getNodeScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");









function isElementScaled(element) {
  var rect = element.getBoundingClientRect();
  var scaleX = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_7__.round)(rect.width) / element.offsetWidth || 1;
  var scaleY = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_7__.round)(rect.height) / element.offsetHeight || 1;
  return scaleX !== 1 || scaleY !== 1;
} // Returns the composite rect of an element relative to its offsetParent.
// Composite means it takes into account transforms as well as layout.


function getCompositeRect(elementOrVirtualElement, offsetParent, isFixed) {
  if (isFixed === void 0) {
    isFixed = false;
  }

  var isOffsetParentAnElement = (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(offsetParent);
  var offsetParentIsScaled = (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(offsetParent) && isElementScaled(offsetParent);
  var documentElement = (0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(offsetParent);
  var rect = (0,_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(elementOrVirtualElement, offsetParentIsScaled, isFixed);
  var scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  var offsets = {
    x: 0,
    y: 0
  };

  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if ((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent) !== 'body' || // https://github.com/popperjs/popper-core/issues/1078
    (0,_isScrollParent_js__WEBPACK_IMPORTED_MODULE_6__["default"])(documentElement)) {
      scroll = (0,_getNodeScroll_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent);
    }

    if ((0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(offsetParent)) {
      offsets = (0,_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(offsetParent, true);
      offsets.x += offsetParent.clientLeft;
      offsets.y += offsetParent.clientTop;
    } else if (documentElement) {
      offsets.x = (0,_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_4__["default"])(documentElement);
    }
  }

  return {
    x: rect.left + scroll.scrollLeft - offsets.x,
    y: rect.top + scroll.scrollTop - offsets.y,
    width: rect.width,
    height: rect.height
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js"
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getComputedStyle)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");

function getComputedStyle(element) {
  return (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element).getComputedStyle(element);
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js"
/*!*************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js ***!
  \*************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getDocumentElement)
/* harmony export */ });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

function getDocumentElement(element) {
  // $FlowFixMe[incompatible-return]: assume body is always available
  return (((0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__.isElement)(element) ? element.ownerDocument : // $FlowFixMe[prop-missing]
  element.document) || window.document).documentElement;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getDocumentRect)
/* harmony export */ });
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");




 // Gets the entire size of the scrollable document area, even extending outside
// of the `<html>` and `<body>` rect bounds if horizontally scrollable

function getDocumentRect(element) {
  var _element$ownerDocumen;

  var html = (0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var winScroll = (0,_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_3__["default"])(element);
  var body = (_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body;
  var width = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_4__.max)(html.scrollWidth, html.clientWidth, body ? body.scrollWidth : 0, body ? body.clientWidth : 0);
  var height = (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_4__.max)(html.scrollHeight, html.clientHeight, body ? body.scrollHeight : 0, body ? body.clientHeight : 0);
  var x = -winScroll.scrollLeft + (0,_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element);
  var y = -winScroll.scrollTop;

  if ((0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_1__["default"])(body || html).direction === 'rtl') {
    x += (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_4__.max)(html.clientWidth, body ? body.clientWidth : 0) - width;
  }

  return {
    width: width,
    height: height,
    x: x,
    y: y
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js"
/*!***************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js ***!
  \***************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getHTMLElementScroll)
/* harmony export */ });
function getHTMLElementScroll(element) {
  return {
    scrollLeft: element.scrollLeft,
    scrollTop: element.scrollTop
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js"
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getLayoutRect)
/* harmony export */ });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
 // Returns the layout rect of an element relative to its offsetParent. Layout
// means it doesn't take into account transforms.

function getLayoutRect(element) {
  var clientRect = (0,_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element); // Use the clientRect sizes if it's not been transformed.
  // Fixes https://github.com/popperjs/popper-core/issues/1223

  var width = element.offsetWidth;
  var height = element.offsetHeight;

  if (Math.abs(clientRect.width - width) <= 1) {
    width = clientRect.width;
  }

  if (Math.abs(clientRect.height - height) <= 1) {
    height = clientRect.height;
  }

  return {
    x: element.offsetLeft,
    y: element.offsetTop,
    width: width,
    height: height
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js"
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getNodeName)
/* harmony export */ });
function getNodeName(element) {
  return element ? (element.nodeName || '').toLowerCase() : null;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js"
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getNodeScroll)
/* harmony export */ });
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getHTMLElementScroll_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getHTMLElementScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js");




function getNodeScroll(node) {
  if (node === (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(node) || !(0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_2__.isHTMLElement)(node)) {
    return (0,_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node);
  } else {
    return (0,_getHTMLElementScroll_js__WEBPACK_IMPORTED_MODULE_3__["default"])(node);
  }
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getOffsetParent)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _isTableElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./isTableElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/userAgent.js */ "./node_modules/@popperjs/core/lib/utils/userAgent.js");








function getTrueOffsetParent(element) {
  if (!(0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(element) || // https://github.com/popperjs/popper-core/issues/837
  (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element).position === 'fixed') {
    return null;
  }

  return element.offsetParent;
} // `.offsetParent` reports `null` for fixed elements, while absolute elements
// return the containing block


function getContainingBlock(element) {
  var isFirefox = /firefox/i.test((0,_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__["default"])());
  var isIE = /Trident/i.test((0,_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__["default"])());

  if (isIE && (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(element)) {
    // In IE 9, 10 and 11 fixed elements containing block is always established by the viewport
    var elementCss = (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element);

    if (elementCss.position === 'fixed') {
      return null;
    }
  }

  var currentNode = (0,_getParentNode_js__WEBPACK_IMPORTED_MODULE_5__["default"])(element);

  if ((0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isShadowRoot)(currentNode)) {
    currentNode = currentNode.host;
  }

  while ((0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(currentNode) && ['html', 'body'].indexOf((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(currentNode)) < 0) {
    var css = (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(currentNode); // This is non-exhaustive but covers the most common CSS properties that
    // create a containing block.
    // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block

    if (css.transform !== 'none' || css.perspective !== 'none' || css.contain === 'paint' || ['transform', 'perspective'].indexOf(css.willChange) !== -1 || isFirefox && css.willChange === 'filter' || isFirefox && css.filter && css.filter !== 'none') {
      return currentNode;
    } else {
      currentNode = currentNode.parentNode;
    }
  }

  return null;
} // Gets the closest ancestor positioned element. Handles some edge cases,
// such as table ancestors and cross browser bugs.


function getOffsetParent(element) {
  var window = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var offsetParent = getTrueOffsetParent(element);

  while (offsetParent && (0,_isTableElement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(offsetParent) && (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent).position === 'static') {
    offsetParent = getTrueOffsetParent(offsetParent);
  }

  if (offsetParent && ((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent) === 'html' || (0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent) === 'body' && (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent).position === 'static')) {
    return window;
  }

  return offsetParent || getContainingBlock(element) || window;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js"
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getParentNode)
/* harmony export */ });
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");



function getParentNode(element) {
  if ((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element) === 'html') {
    return element;
  }

  return (// this is a quicker (but less type safe) way to save quite some bytes from the bundle
    // $FlowFixMe[incompatible-return]
    // $FlowFixMe[prop-missing]
    element.assignedSlot || // step into the shadow DOM of the parent of a slotted node
    element.parentNode || ( // DOM Element detected
    (0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_2__.isShadowRoot)(element) ? element.host : null) || // ShadowRoot detected
    // $FlowFixMe[incompatible-call]: HTMLElement is a Node
    (0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element) // fallback

  );
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getScrollParent)
/* harmony export */ });
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");




function getScrollParent(node) {
  if (['html', 'body', '#document'].indexOf((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_2__["default"])(node)) >= 0) {
    // $FlowFixMe[incompatible-return]: assume body is always available
    return node.ownerDocument.body;
  }

  if ((0,_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__.isHTMLElement)(node) && (0,_isScrollParent_js__WEBPACK_IMPORTED_MODULE_1__["default"])(node)) {
    return node;
  }

  return getScrollParent((0,_getParentNode_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node));
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getViewportRect)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isLayoutViewport.js */ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js");




function getViewportRect(element, strategy) {
  var win = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var html = (0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element);
  var visualViewport = win.visualViewport;
  var width = html.clientWidth;
  var height = html.clientHeight;
  var x = 0;
  var y = 0;

  if (visualViewport) {
    width = visualViewport.width;
    height = visualViewport.height;
    var layoutViewport = (0,_isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__["default"])();

    if (layoutViewport || !layoutViewport && strategy === 'fixed') {
      x = visualViewport.offsetLeft;
      y = visualViewport.offsetTop;
    }
  }

  return {
    width: width,
    height: height,
    x: x + (0,_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element),
    y: y
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js"
/*!****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindow.js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getWindow)
/* harmony export */ });
function getWindow(node) {
  if (node == null) {
    return window;
  }

  if (node.toString() !== '[object Window]') {
    var ownerDocument = node.ownerDocument;
    return ownerDocument ? ownerDocument.defaultView || window : window;
  }

  return node;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getWindowScroll)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");

function getWindowScroll(node) {
  var win = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node);
  var scrollLeft = win.pageXOffset;
  var scrollTop = win.pageYOffset;
  return {
    scrollLeft: scrollLeft,
    scrollTop: scrollTop
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js"
/*!**************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js ***!
  \**************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getWindowScrollBarX)
/* harmony export */ });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");



function getWindowScrollBarX(element) {
  // If <html> has a CSS width greater than the viewport, then this will be
  // incorrect for RTL.
  // Popper 1 is broken in this case and never had a bug report so let's assume
  // it's not an issue. I don't think anyone ever specifies width on <html>
  // anyway.
  // Browsers where the left scrollbar doesn't cause an issue report `0` for
  // this (e.g. Edge 2019, IE11, Safari)
  return (0,_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])((0,_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element)).left + (0,_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element).scrollLeft;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isElement: () => (/* binding */ isElement),
/* harmony export */   isHTMLElement: () => (/* binding */ isHTMLElement),
/* harmony export */   isShadowRoot: () => (/* binding */ isShadowRoot)
/* harmony export */ });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");


function isElement(node) {
  var OwnElement = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).Element;
  return node instanceof OwnElement || node instanceof Element;
}

function isHTMLElement(node) {
  var OwnElement = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).HTMLElement;
  return node instanceof OwnElement || node instanceof HTMLElement;
}

function isShadowRoot(node) {
  // IE 11 has no ShadowRoot
  if (typeof ShadowRoot === 'undefined') {
    return false;
  }

  var OwnElement = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).ShadowRoot;
  return node instanceof OwnElement || node instanceof ShadowRoot;
}



/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js"
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isLayoutViewport)
/* harmony export */ });
/* harmony import */ var _utils_userAgent_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/userAgent.js */ "./node_modules/@popperjs/core/lib/utils/userAgent.js");

function isLayoutViewport() {
  return !/^((?!chrome|android).)*safari/i.test((0,_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_0__["default"])());
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js"
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isScrollParent)
/* harmony export */ });
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");

function isScrollParent(element) {
  // Firefox wants us to check `-x` and `-y` variations as well
  var _getComputedStyle = (0,_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element),
      overflow = _getComputedStyle.overflow,
      overflowX = _getComputedStyle.overflowX,
      overflowY = _getComputedStyle.overflowY;

  return /auto|scroll|overlay|hidden/.test(overflow + overflowY + overflowX);
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js"
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isTableElement)
/* harmony export */ });
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");

function isTableElement(element) {
  return ['table', 'td', 'th'].indexOf((0,_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) >= 0;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js"
/*!************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js ***!
  \************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ listScrollParents)
/* harmony export */ });
/* harmony import */ var _getScrollParent_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");




/*
given a DOM element, return the list of all scroll parents, up the list of ancesors
until we get to the top window object. This list is what we attach scroll listeners
to, because if any of these parent elements scroll, we'll need to re-calculate the
reference element's position.
*/

function listScrollParents(element, list) {
  var _element$ownerDocumen;

  if (list === void 0) {
    list = [];
  }

  var scrollParent = (0,_getScrollParent_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var isBody = scrollParent === ((_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body);
  var win = (0,_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(scrollParent);
  var target = isBody ? [win].concat(win.visualViewport || [], (0,_isScrollParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(scrollParent) ? scrollParent : []) : scrollParent;
  var updatedList = list.concat(target);
  return isBody ? updatedList : // $FlowFixMe[incompatible-call]: isBody tells us target will be an HTMLElement here
  updatedList.concat(listScrollParents((0,_getParentNode_js__WEBPACK_IMPORTED_MODULE_1__["default"])(target)));
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/enums.js"
/*!**************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/enums.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   afterMain: () => (/* binding */ afterMain),
/* harmony export */   afterRead: () => (/* binding */ afterRead),
/* harmony export */   afterWrite: () => (/* binding */ afterWrite),
/* harmony export */   auto: () => (/* binding */ auto),
/* harmony export */   basePlacements: () => (/* binding */ basePlacements),
/* harmony export */   beforeMain: () => (/* binding */ beforeMain),
/* harmony export */   beforeRead: () => (/* binding */ beforeRead),
/* harmony export */   beforeWrite: () => (/* binding */ beforeWrite),
/* harmony export */   bottom: () => (/* binding */ bottom),
/* harmony export */   clippingParents: () => (/* binding */ clippingParents),
/* harmony export */   end: () => (/* binding */ end),
/* harmony export */   left: () => (/* binding */ left),
/* harmony export */   main: () => (/* binding */ main),
/* harmony export */   modifierPhases: () => (/* binding */ modifierPhases),
/* harmony export */   placements: () => (/* binding */ placements),
/* harmony export */   popper: () => (/* binding */ popper),
/* harmony export */   read: () => (/* binding */ read),
/* harmony export */   reference: () => (/* binding */ reference),
/* harmony export */   right: () => (/* binding */ right),
/* harmony export */   start: () => (/* binding */ start),
/* harmony export */   top: () => (/* binding */ top),
/* harmony export */   variationPlacements: () => (/* binding */ variationPlacements),
/* harmony export */   viewport: () => (/* binding */ viewport),
/* harmony export */   write: () => (/* binding */ write)
/* harmony export */ });
var top = 'top';
var bottom = 'bottom';
var right = 'right';
var left = 'left';
var auto = 'auto';
var basePlacements = [top, bottom, right, left];
var start = 'start';
var end = 'end';
var clippingParents = 'clippingParents';
var viewport = 'viewport';
var popper = 'popper';
var reference = 'reference';
var variationPlacements = /*#__PURE__*/basePlacements.reduce(function (acc, placement) {
  return acc.concat([placement + "-" + start, placement + "-" + end]);
}, []);
var placements = /*#__PURE__*/[].concat(basePlacements, [auto]).reduce(function (acc, placement) {
  return acc.concat([placement, placement + "-" + start, placement + "-" + end]);
}, []); // modifiers that need to read the DOM

var beforeRead = 'beforeRead';
var read = 'read';
var afterRead = 'afterRead'; // pure-logic modifiers

var beforeMain = 'beforeMain';
var main = 'main';
var afterMain = 'afterMain'; // modifier with the purpose to write to the DOM (or write into a framework state)

var beforeWrite = 'beforeWrite';
var write = 'write';
var afterWrite = 'afterWrite';
var modifierPhases = [beforeRead, read, afterRead, beforeMain, main, afterMain, beforeWrite, write, afterWrite];

/***/ },

/***/ "./node_modules/@popperjs/core/lib/index.js"
/*!**************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/index.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   afterMain: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.afterMain),
/* harmony export */   afterRead: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.afterRead),
/* harmony export */   afterWrite: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.afterWrite),
/* harmony export */   applyStyles: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.applyStyles),
/* harmony export */   arrow: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.arrow),
/* harmony export */   auto: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.auto),
/* harmony export */   basePlacements: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.basePlacements),
/* harmony export */   beforeMain: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.beforeMain),
/* harmony export */   beforeRead: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.beforeRead),
/* harmony export */   beforeWrite: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.beforeWrite),
/* harmony export */   bottom: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom),
/* harmony export */   clippingParents: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.clippingParents),
/* harmony export */   computeStyles: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.computeStyles),
/* harmony export */   createPopper: () => (/* reexport safe */ _popper_js__WEBPACK_IMPORTED_MODULE_4__.createPopper),
/* harmony export */   createPopperBase: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_2__.createPopper),
/* harmony export */   createPopperLite: () => (/* reexport safe */ _popper_lite_js__WEBPACK_IMPORTED_MODULE_5__.createPopper),
/* harmony export */   detectOverflow: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   end: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.end),
/* harmony export */   eventListeners: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.eventListeners),
/* harmony export */   flip: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.flip),
/* harmony export */   hide: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.hide),
/* harmony export */   left: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.left),
/* harmony export */   main: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.main),
/* harmony export */   modifierPhases: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.modifierPhases),
/* harmony export */   offset: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.offset),
/* harmony export */   placements: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.placements),
/* harmony export */   popper: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.popper),
/* harmony export */   popperGenerator: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_2__.popperGenerator),
/* harmony export */   popperOffsets: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.popperOffsets),
/* harmony export */   preventOverflow: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__.preventOverflow),
/* harmony export */   read: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.read),
/* harmony export */   reference: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.reference),
/* harmony export */   right: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.right),
/* harmony export */   start: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.start),
/* harmony export */   top: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.top),
/* harmony export */   variationPlacements: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.variationPlacements),
/* harmony export */   viewport: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.viewport),
/* harmony export */   write: () => (/* reexport safe */ _enums_js__WEBPACK_IMPORTED_MODULE_0__.write)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modifiers/index.js */ "./node_modules/@popperjs/core/lib/modifiers/index.js");
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _popper_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./popper.js */ "./node_modules/@popperjs/core/lib/popper.js");
/* harmony import */ var _popper_lite_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./popper-lite.js */ "./node_modules/@popperjs/core/lib/popper-lite.js");

 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules



/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js"
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/applyStyles.js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

 // This modifier takes the styles prepared by the `computeStyles` modifier
// and applies them to the HTMLElements such as popper and arrow

function applyStyles(_ref) {
  var state = _ref.state;
  Object.keys(state.elements).forEach(function (name) {
    var style = state.styles[name] || {};
    var attributes = state.attributes[name] || {};
    var element = state.elements[name]; // arrow is optional + virtual elements

    if (!(0,_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__.isHTMLElement)(element) || !(0,_dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
      return;
    } // Flow doesn't support to extend this property, but it's the most
    // effective way to apply styles to an HTMLElement
    // $FlowFixMe[cannot-write]


    Object.assign(element.style, style);
    Object.keys(attributes).forEach(function (name) {
      var value = attributes[name];

      if (value === false) {
        element.removeAttribute(name);
      } else {
        element.setAttribute(name, value === true ? '' : value);
      }
    });
  });
}

function effect(_ref2) {
  var state = _ref2.state;
  var initialStyles = {
    popper: {
      position: state.options.strategy,
      left: '0',
      top: '0',
      margin: '0'
    },
    arrow: {
      position: 'absolute'
    },
    reference: {}
  };
  Object.assign(state.elements.popper.style, initialStyles.popper);
  state.styles = initialStyles;

  if (state.elements.arrow) {
    Object.assign(state.elements.arrow.style, initialStyles.arrow);
  }

  return function () {
    Object.keys(state.elements).forEach(function (name) {
      var element = state.elements[name];
      var attributes = state.attributes[name] || {};
      var styleProperties = Object.keys(state.styles.hasOwnProperty(name) ? state.styles[name] : initialStyles[name]); // Set all values to an empty string to unset them

      var style = styleProperties.reduce(function (style, property) {
        style[property] = '';
        return style;
      }, {}); // arrow is optional + virtual elements

      if (!(0,_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__.isHTMLElement)(element) || !(0,_dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
        return;
      }

      Object.assign(element.style, style);
      Object.keys(attributes).forEach(function (attribute) {
        element.removeAttribute(attribute);
      });
    });
  };
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'applyStyles',
  enabled: true,
  phase: 'write',
  fn: applyStyles,
  effect: effect,
  requires: ['computeStyles']
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/arrow.js"
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/arrow.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_contains_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/contains.js */ "./node_modules/@popperjs/core/lib/dom-utils/contains.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _utils_within_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/within.js */ "./node_modules/@popperjs/core/lib/utils/within.js");
/* harmony import */ var _utils_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/mergePaddingObject.js */ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js");
/* harmony import */ var _utils_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/expandToHashMap.js */ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");








 // eslint-disable-next-line import/no-unused-modules

var toPaddingObject = function toPaddingObject(padding, state) {
  padding = typeof padding === 'function' ? padding(Object.assign({}, state.rects, {
    placement: state.placement
  })) : padding;
  return (0,_utils_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_6__["default"])(typeof padding !== 'number' ? padding : (0,_utils_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_7__["default"])(padding, _enums_js__WEBPACK_IMPORTED_MODULE_8__.basePlacements));
};

function arrow(_ref) {
  var _state$modifiersData$;

  var state = _ref.state,
      name = _ref.name,
      options = _ref.options;
  var arrowElement = state.elements.arrow;
  var popperOffsets = state.modifiersData.popperOffsets;
  var basePlacement = (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(state.placement);
  var axis = (0,_utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(basePlacement);
  var isVertical = [_enums_js__WEBPACK_IMPORTED_MODULE_8__.left, _enums_js__WEBPACK_IMPORTED_MODULE_8__.right].indexOf(basePlacement) >= 0;
  var len = isVertical ? 'height' : 'width';

  if (!arrowElement || !popperOffsets) {
    return;
  }

  var paddingObject = toPaddingObject(options.padding, state);
  var arrowRect = (0,_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(arrowElement);
  var minProp = axis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_8__.top : _enums_js__WEBPACK_IMPORTED_MODULE_8__.left;
  var maxProp = axis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_8__.bottom : _enums_js__WEBPACK_IMPORTED_MODULE_8__.right;
  var endDiff = state.rects.reference[len] + state.rects.reference[axis] - popperOffsets[axis] - state.rects.popper[len];
  var startDiff = popperOffsets[axis] - state.rects.reference[axis];
  var arrowOffsetParent = (0,_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(arrowElement);
  var clientSize = arrowOffsetParent ? axis === 'y' ? arrowOffsetParent.clientHeight || 0 : arrowOffsetParent.clientWidth || 0 : 0;
  var centerToReference = endDiff / 2 - startDiff / 2; // Make sure the arrow doesn't overflow the popper if the center point is
  // outside of the popper bounds

  var min = paddingObject[minProp];
  var max = clientSize - arrowRect[len] - paddingObject[maxProp];
  var center = clientSize / 2 - arrowRect[len] / 2 + centerToReference;
  var offset = (0,_utils_within_js__WEBPACK_IMPORTED_MODULE_5__.within)(min, center, max); // Prevents breaking syntax highlighting...

  var axisProp = axis;
  state.modifiersData[name] = (_state$modifiersData$ = {}, _state$modifiersData$[axisProp] = offset, _state$modifiersData$.centerOffset = offset - center, _state$modifiersData$);
}

function effect(_ref2) {
  var state = _ref2.state,
      options = _ref2.options;
  var _options$element = options.element,
      arrowElement = _options$element === void 0 ? '[data-popper-arrow]' : _options$element;

  if (arrowElement == null) {
    return;
  } // CSS selector


  if (typeof arrowElement === 'string') {
    arrowElement = state.elements.popper.querySelector(arrowElement);

    if (!arrowElement) {
      return;
    }
  }

  if (!(0,_dom_utils_contains_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state.elements.popper, arrowElement)) {
    return;
  }

  state.elements.arrow = arrowElement;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'arrow',
  enabled: true,
  phase: 'main',
  fn: arrow,
  effect: effect,
  requires: ['popperOffsets'],
  requiresIfExists: ['preventOverflow']
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js"
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/computeStyles.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   mapToStyles: () => (/* binding */ mapToStyles)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../dom-utils/getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../dom-utils/getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");







 // eslint-disable-next-line import/no-unused-modules

var unsetSides = {
  top: 'auto',
  right: 'auto',
  bottom: 'auto',
  left: 'auto'
}; // Round the offsets to the nearest suitable subpixel based on the DPR.
// Zooming can change the DPR, but it seems to report a value that will
// cleanly divide the values into the appropriate subpixels.

function roundOffsetsByDPR(_ref, win) {
  var x = _ref.x,
      y = _ref.y;
  var dpr = win.devicePixelRatio || 1;
  return {
    x: (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_7__.round)(x * dpr) / dpr || 0,
    y: (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_7__.round)(y * dpr) / dpr || 0
  };
}

function mapToStyles(_ref2) {
  var _Object$assign2;

  var popper = _ref2.popper,
      popperRect = _ref2.popperRect,
      placement = _ref2.placement,
      variation = _ref2.variation,
      offsets = _ref2.offsets,
      position = _ref2.position,
      gpuAcceleration = _ref2.gpuAcceleration,
      adaptive = _ref2.adaptive,
      roundOffsets = _ref2.roundOffsets,
      isFixed = _ref2.isFixed;
  var _offsets$x = offsets.x,
      x = _offsets$x === void 0 ? 0 : _offsets$x,
      _offsets$y = offsets.y,
      y = _offsets$y === void 0 ? 0 : _offsets$y;

  var _ref3 = typeof roundOffsets === 'function' ? roundOffsets({
    x: x,
    y: y
  }) : {
    x: x,
    y: y
  };

  x = _ref3.x;
  y = _ref3.y;
  var hasX = offsets.hasOwnProperty('x');
  var hasY = offsets.hasOwnProperty('y');
  var sideX = _enums_js__WEBPACK_IMPORTED_MODULE_0__.left;
  var sideY = _enums_js__WEBPACK_IMPORTED_MODULE_0__.top;
  var win = window;

  if (adaptive) {
    var offsetParent = (0,_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_1__["default"])(popper);
    var heightProp = 'clientHeight';
    var widthProp = 'clientWidth';

    if (offsetParent === (0,_dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(popper)) {
      offsetParent = (0,_dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_3__["default"])(popper);

      if ((0,_dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__["default"])(offsetParent).position !== 'static' && position === 'absolute') {
        heightProp = 'scrollHeight';
        widthProp = 'scrollWidth';
      }
    } // $FlowFixMe[incompatible-cast]: force type refinement, we compare offsetParent with window above, but Flow doesn't detect it


    offsetParent = offsetParent;

    if (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.top || (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.left || placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.right) && variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__.end) {
      sideY = _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom;
      var offsetY = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.height : // $FlowFixMe[prop-missing]
      offsetParent[heightProp];
      y -= offsetY - popperRect.height;
      y *= gpuAcceleration ? 1 : -1;
    }

    if (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.left || (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.top || placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom) && variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__.end) {
      sideX = _enums_js__WEBPACK_IMPORTED_MODULE_0__.right;
      var offsetX = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.width : // $FlowFixMe[prop-missing]
      offsetParent[widthProp];
      x -= offsetX - popperRect.width;
      x *= gpuAcceleration ? 1 : -1;
    }
  }

  var commonStyles = Object.assign({
    position: position
  }, adaptive && unsetSides);

  var _ref4 = roundOffsets === true ? roundOffsetsByDPR({
    x: x,
    y: y
  }, (0,_dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(popper)) : {
    x: x,
    y: y
  };

  x = _ref4.x;
  y = _ref4.y;

  if (gpuAcceleration) {
    var _Object$assign;

    return Object.assign({}, commonStyles, (_Object$assign = {}, _Object$assign[sideY] = hasY ? '0' : '', _Object$assign[sideX] = hasX ? '0' : '', _Object$assign.transform = (win.devicePixelRatio || 1) <= 1 ? "translate(" + x + "px, " + y + "px)" : "translate3d(" + x + "px, " + y + "px, 0)", _Object$assign));
  }

  return Object.assign({}, commonStyles, (_Object$assign2 = {}, _Object$assign2[sideY] = hasY ? y + "px" : '', _Object$assign2[sideX] = hasX ? x + "px" : '', _Object$assign2.transform = '', _Object$assign2));
}

function computeStyles(_ref5) {
  var state = _ref5.state,
      options = _ref5.options;
  var _options$gpuAccelerat = options.gpuAcceleration,
      gpuAcceleration = _options$gpuAccelerat === void 0 ? true : _options$gpuAccelerat,
      _options$adaptive = options.adaptive,
      adaptive = _options$adaptive === void 0 ? true : _options$adaptive,
      _options$roundOffsets = options.roundOffsets,
      roundOffsets = _options$roundOffsets === void 0 ? true : _options$roundOffsets;
  var commonStyles = {
    placement: (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(state.placement),
    variation: (0,_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__["default"])(state.placement),
    popper: state.elements.popper,
    popperRect: state.rects.popper,
    gpuAcceleration: gpuAcceleration,
    isFixed: state.options.strategy === 'fixed'
  };

  if (state.modifiersData.popperOffsets != null) {
    state.styles.popper = Object.assign({}, state.styles.popper, mapToStyles(Object.assign({}, commonStyles, {
      offsets: state.modifiersData.popperOffsets,
      position: state.options.strategy,
      adaptive: adaptive,
      roundOffsets: roundOffsets
    })));
  }

  if (state.modifiersData.arrow != null) {
    state.styles.arrow = Object.assign({}, state.styles.arrow, mapToStyles(Object.assign({}, commonStyles, {
      offsets: state.modifiersData.arrow,
      position: 'absolute',
      adaptive: false,
      roundOffsets: roundOffsets
    })));
  }

  state.attributes.popper = Object.assign({}, state.attributes.popper, {
    'data-popper-placement': state.placement
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'computeStyles',
  enabled: true,
  phase: 'beforeWrite',
  fn: computeStyles,
  data: {}
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js"
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/eventListeners.js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
 // eslint-disable-next-line import/no-unused-modules

var passive = {
  passive: true
};

function effect(_ref) {
  var state = _ref.state,
      instance = _ref.instance,
      options = _ref.options;
  var _options$scroll = options.scroll,
      scroll = _options$scroll === void 0 ? true : _options$scroll,
      _options$resize = options.resize,
      resize = _options$resize === void 0 ? true : _options$resize;
  var window = (0,_dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(state.elements.popper);
  var scrollParents = [].concat(state.scrollParents.reference, state.scrollParents.popper);

  if (scroll) {
    scrollParents.forEach(function (scrollParent) {
      scrollParent.addEventListener('scroll', instance.update, passive);
    });
  }

  if (resize) {
    window.addEventListener('resize', instance.update, passive);
  }

  return function () {
    if (scroll) {
      scrollParents.forEach(function (scrollParent) {
        scrollParent.removeEventListener('scroll', instance.update, passive);
      });
    }

    if (resize) {
      window.removeEventListener('resize', instance.update, passive);
    }
  };
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'eventListeners',
  enabled: true,
  phase: 'write',
  fn: function fn() {},
  effect: effect,
  data: {}
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/flip.js"
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/flip.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getOppositePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/getOppositeVariationPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _utils_computeAutoPlacement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/computeAutoPlacement.js */ "./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");






 // eslint-disable-next-line import/no-unused-modules

function getExpandedFallbackPlacements(placement) {
  if ((0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__.auto) {
    return [];
  }

  var oppositePlacement = (0,_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  return [(0,_utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(placement), oppositePlacement, (0,_utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(oppositePlacement)];
}

function flip(_ref) {
  var state = _ref.state,
      options = _ref.options,
      name = _ref.name;

  if (state.modifiersData[name]._skip) {
    return;
  }

  var _options$mainAxis = options.mainAxis,
      checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis,
      _options$altAxis = options.altAxis,
      checkAltAxis = _options$altAxis === void 0 ? true : _options$altAxis,
      specifiedFallbackPlacements = options.fallbackPlacements,
      padding = options.padding,
      boundary = options.boundary,
      rootBoundary = options.rootBoundary,
      altBoundary = options.altBoundary,
      _options$flipVariatio = options.flipVariations,
      flipVariations = _options$flipVariatio === void 0 ? true : _options$flipVariatio,
      allowedAutoPlacements = options.allowedAutoPlacements;
  var preferredPlacement = state.options.placement;
  var basePlacement = (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(preferredPlacement);
  var isBasePlacement = basePlacement === preferredPlacement;
  var fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipVariations ? [(0,_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(preferredPlacement)] : getExpandedFallbackPlacements(preferredPlacement));
  var placements = [preferredPlacement].concat(fallbackPlacements).reduce(function (acc, placement) {
    return acc.concat((0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__.auto ? (0,_utils_computeAutoPlacement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      padding: padding,
      flipVariations: flipVariations,
      allowedAutoPlacements: allowedAutoPlacements
    }) : placement);
  }, []);
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var checksMap = new Map();
  var makeFallbackChecks = true;
  var firstFittingPlacement = placements[0];

  for (var i = 0; i < placements.length; i++) {
    var placement = placements[i];

    var _basePlacement = (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement);

    var isStartVariation = (0,_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__.start;
    var isVertical = [_enums_js__WEBPACK_IMPORTED_MODULE_5__.top, _enums_js__WEBPACK_IMPORTED_MODULE_5__.bottom].indexOf(_basePlacement) >= 0;
    var len = isVertical ? 'width' : 'height';
    var overflow = (0,_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_3__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      altBoundary: altBoundary,
      padding: padding
    });
    var mainVariationSide = isVertical ? isStartVariation ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.right : _enums_js__WEBPACK_IMPORTED_MODULE_5__.left : isStartVariation ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.bottom : _enums_js__WEBPACK_IMPORTED_MODULE_5__.top;

    if (referenceRect[len] > popperRect[len]) {
      mainVariationSide = (0,_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(mainVariationSide);
    }

    var altVariationSide = (0,_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(mainVariationSide);
    var checks = [];

    if (checkMainAxis) {
      checks.push(overflow[_basePlacement] <= 0);
    }

    if (checkAltAxis) {
      checks.push(overflow[mainVariationSide] <= 0, overflow[altVariationSide] <= 0);
    }

    if (checks.every(function (check) {
      return check;
    })) {
      firstFittingPlacement = placement;
      makeFallbackChecks = false;
      break;
    }

    checksMap.set(placement, checks);
  }

  if (makeFallbackChecks) {
    // `2` may be desired in some cases – research later
    var numberOfChecks = flipVariations ? 3 : 1;

    var _loop = function _loop(_i) {
      var fittingPlacement = placements.find(function (placement) {
        var checks = checksMap.get(placement);

        if (checks) {
          return checks.slice(0, _i).every(function (check) {
            return check;
          });
        }
      });

      if (fittingPlacement) {
        firstFittingPlacement = fittingPlacement;
        return "break";
      }
    };

    for (var _i = numberOfChecks; _i > 0; _i--) {
      var _ret = _loop(_i);

      if (_ret === "break") break;
    }
  }

  if (state.placement !== firstFittingPlacement) {
    state.modifiersData[name]._skip = true;
    state.placement = firstFittingPlacement;
    state.reset = true;
  }
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'flip',
  enabled: true,
  phase: 'main',
  fn: flip,
  requiresIfExists: ['offset'],
  data: {
    _skip: false
  }
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/hide.js"
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/hide.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");



function getSideOffsets(overflow, rect, preventedOffsets) {
  if (preventedOffsets === void 0) {
    preventedOffsets = {
      x: 0,
      y: 0
    };
  }

  return {
    top: overflow.top - rect.height - preventedOffsets.y,
    right: overflow.right - rect.width + preventedOffsets.x,
    bottom: overflow.bottom - rect.height + preventedOffsets.y,
    left: overflow.left - rect.width - preventedOffsets.x
  };
}

function isAnySideFullyClipped(overflow) {
  return [_enums_js__WEBPACK_IMPORTED_MODULE_0__.top, _enums_js__WEBPACK_IMPORTED_MODULE_0__.right, _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom, _enums_js__WEBPACK_IMPORTED_MODULE_0__.left].some(function (side) {
    return overflow[side] >= 0;
  });
}

function hide(_ref) {
  var state = _ref.state,
      name = _ref.name;
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var preventedOffsets = state.modifiersData.preventOverflow;
  var referenceOverflow = (0,_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state, {
    elementContext: 'reference'
  });
  var popperAltOverflow = (0,_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state, {
    altBoundary: true
  });
  var referenceClippingOffsets = getSideOffsets(referenceOverflow, referenceRect);
  var popperEscapeOffsets = getSideOffsets(popperAltOverflow, popperRect, preventedOffsets);
  var isReferenceHidden = isAnySideFullyClipped(referenceClippingOffsets);
  var hasPopperEscaped = isAnySideFullyClipped(popperEscapeOffsets);
  state.modifiersData[name] = {
    referenceClippingOffsets: referenceClippingOffsets,
    popperEscapeOffsets: popperEscapeOffsets,
    isReferenceHidden: isReferenceHidden,
    hasPopperEscaped: hasPopperEscaped
  };
  state.attributes.popper = Object.assign({}, state.attributes.popper, {
    'data-popper-reference-hidden': isReferenceHidden,
    'data-popper-escaped': hasPopperEscaped
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'hide',
  enabled: true,
  phase: 'main',
  requiresIfExists: ['preventOverflow'],
  fn: hide
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/index.js"
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/index.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   applyStyles: () => (/* reexport safe */ _applyStyles_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   arrow: () => (/* reexport safe */ _arrow_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   computeStyles: () => (/* reexport safe */ _computeStyles_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   eventListeners: () => (/* reexport safe */ _eventListeners_js__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   flip: () => (/* reexport safe */ _flip_js__WEBPACK_IMPORTED_MODULE_4__["default"]),
/* harmony export */   hide: () => (/* reexport safe */ _hide_js__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   offset: () => (/* reexport safe */ _offset_js__WEBPACK_IMPORTED_MODULE_6__["default"]),
/* harmony export */   popperOffsets: () => (/* reexport safe */ _popperOffsets_js__WEBPACK_IMPORTED_MODULE_7__["default"]),
/* harmony export */   preventOverflow: () => (/* reexport safe */ _preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__["default"])
/* harmony export */ });
/* harmony import */ var _applyStyles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");
/* harmony import */ var _arrow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./arrow.js */ "./node_modules/@popperjs/core/lib/modifiers/arrow.js");
/* harmony import */ var _computeStyles_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony import */ var _eventListeners_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony import */ var _flip_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./flip.js */ "./node_modules/@popperjs/core/lib/modifiers/flip.js");
/* harmony import */ var _hide_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./hide.js */ "./node_modules/@popperjs/core/lib/modifiers/hide.js");
/* harmony import */ var _offset_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./offset.js */ "./node_modules/@popperjs/core/lib/modifiers/offset.js");
/* harmony import */ var _popperOffsets_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony import */ var _preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./preventOverflow.js */ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js");










/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/offset.js"
/*!*************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/offset.js ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   distanceAndSkiddingToXY: () => (/* binding */ distanceAndSkiddingToXY)
/* harmony export */ });
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");

 // eslint-disable-next-line import/no-unused-modules

function distanceAndSkiddingToXY(placement, rects, offset) {
  var basePlacement = (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  var invertDistance = [_enums_js__WEBPACK_IMPORTED_MODULE_1__.left, _enums_js__WEBPACK_IMPORTED_MODULE_1__.top].indexOf(basePlacement) >= 0 ? -1 : 1;

  var _ref = typeof offset === 'function' ? offset(Object.assign({}, rects, {
    placement: placement
  })) : offset,
      skidding = _ref[0],
      distance = _ref[1];

  skidding = skidding || 0;
  distance = (distance || 0) * invertDistance;
  return [_enums_js__WEBPACK_IMPORTED_MODULE_1__.left, _enums_js__WEBPACK_IMPORTED_MODULE_1__.right].indexOf(basePlacement) >= 0 ? {
    x: distance,
    y: skidding
  } : {
    x: skidding,
    y: distance
  };
}

function offset(_ref2) {
  var state = _ref2.state,
      options = _ref2.options,
      name = _ref2.name;
  var _options$offset = options.offset,
      offset = _options$offset === void 0 ? [0, 0] : _options$offset;
  var data = _enums_js__WEBPACK_IMPORTED_MODULE_1__.placements.reduce(function (acc, placement) {
    acc[placement] = distanceAndSkiddingToXY(placement, state.rects, offset);
    return acc;
  }, {});
  var _data$state$placement = data[state.placement],
      x = _data$state$placement.x,
      y = _data$state$placement.y;

  if (state.modifiersData.popperOffsets != null) {
    state.modifiersData.popperOffsets.x += x;
    state.modifiersData.popperOffsets.y += y;
  }

  state.modifiersData[name] = data;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'offset',
  enabled: true,
  phase: 'main',
  requires: ['popperOffsets'],
  fn: offset
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js"
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _utils_computeOffsets_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/computeOffsets.js */ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js");


function popperOffsets(_ref) {
  var state = _ref.state,
      name = _ref.name;
  // Offsets are the actual position the popper needs to have to be
  // properly positioned near its reference element
  // This is the most basic placement, and will be adjusted by
  // the modifiers in the next step
  state.modifiersData[name] = (0,_utils_computeOffsets_js__WEBPACK_IMPORTED_MODULE_0__["default"])({
    reference: state.rects.reference,
    element: state.rects.popper,
    strategy: 'absolute',
    placement: state.placement
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'popperOffsets',
  enabled: true,
  phase: 'read',
  fn: popperOffsets,
  data: {}
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js"
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _utils_getAltAxis_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/getAltAxis.js */ "./node_modules/@popperjs/core/lib/utils/getAltAxis.js");
/* harmony import */ var _utils_within_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/within.js */ "./node_modules/@popperjs/core/lib/utils/within.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _utils_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/getFreshSideObject.js */ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");












function preventOverflow(_ref) {
  var state = _ref.state,
      options = _ref.options,
      name = _ref.name;
  var _options$mainAxis = options.mainAxis,
      checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis,
      _options$altAxis = options.altAxis,
      checkAltAxis = _options$altAxis === void 0 ? false : _options$altAxis,
      boundary = options.boundary,
      rootBoundary = options.rootBoundary,
      altBoundary = options.altBoundary,
      padding = options.padding,
      _options$tether = options.tether,
      tether = _options$tether === void 0 ? true : _options$tether,
      _options$tetherOffset = options.tetherOffset,
      tetherOffset = _options$tetherOffset === void 0 ? 0 : _options$tetherOffset;
  var overflow = (0,_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__["default"])(state, {
    boundary: boundary,
    rootBoundary: rootBoundary,
    padding: padding,
    altBoundary: altBoundary
  });
  var basePlacement = (0,_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state.placement);
  var variation = (0,_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_8__["default"])(state.placement);
  var isBasePlacement = !variation;
  var mainAxis = (0,_utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(basePlacement);
  var altAxis = (0,_utils_getAltAxis_js__WEBPACK_IMPORTED_MODULE_3__["default"])(mainAxis);
  var popperOffsets = state.modifiersData.popperOffsets;
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var tetherOffsetValue = typeof tetherOffset === 'function' ? tetherOffset(Object.assign({}, state.rects, {
    placement: state.placement
  })) : tetherOffset;
  var normalizedTetherOffsetValue = typeof tetherOffsetValue === 'number' ? {
    mainAxis: tetherOffsetValue,
    altAxis: tetherOffsetValue
  } : Object.assign({
    mainAxis: 0,
    altAxis: 0
  }, tetherOffsetValue);
  var offsetModifierState = state.modifiersData.offset ? state.modifiersData.offset[state.placement] : null;
  var data = {
    x: 0,
    y: 0
  };

  if (!popperOffsets) {
    return;
  }

  if (checkMainAxis) {
    var _offsetModifierState$;

    var mainSide = mainAxis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__.top : _enums_js__WEBPACK_IMPORTED_MODULE_0__.left;
    var altSide = mainAxis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom : _enums_js__WEBPACK_IMPORTED_MODULE_0__.right;
    var len = mainAxis === 'y' ? 'height' : 'width';
    var offset = popperOffsets[mainAxis];
    var min = offset + overflow[mainSide];
    var max = offset - overflow[altSide];
    var additive = tether ? -popperRect[len] / 2 : 0;
    var minLen = variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__.start ? referenceRect[len] : popperRect[len];
    var maxLen = variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__.start ? -popperRect[len] : -referenceRect[len]; // We need to include the arrow in the calculation so the arrow doesn't go
    // outside the reference bounds

    var arrowElement = state.elements.arrow;
    var arrowRect = tether && arrowElement ? (0,_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_5__["default"])(arrowElement) : {
      width: 0,
      height: 0
    };
    var arrowPaddingObject = state.modifiersData['arrow#persistent'] ? state.modifiersData['arrow#persistent'].padding : (0,_utils_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_9__["default"])();
    var arrowPaddingMin = arrowPaddingObject[mainSide];
    var arrowPaddingMax = arrowPaddingObject[altSide]; // If the reference length is smaller than the arrow length, we don't want
    // to include its full size in the calculation. If the reference is small
    // and near the edge of a boundary, the popper can overflow even if the
    // reference is not overflowing as well (e.g. virtual elements with no
    // width or height)

    var arrowLen = (0,_utils_within_js__WEBPACK_IMPORTED_MODULE_4__.within)(0, referenceRect[len], arrowRect[len]);
    var minOffset = isBasePlacement ? referenceRect[len] / 2 - additive - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis : minLen - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis;
    var maxOffset = isBasePlacement ? -referenceRect[len] / 2 + additive + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis : maxLen + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis;
    var arrowOffsetParent = state.elements.arrow && (0,_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_6__["default"])(state.elements.arrow);
    var clientOffset = arrowOffsetParent ? mainAxis === 'y' ? arrowOffsetParent.clientTop || 0 : arrowOffsetParent.clientLeft || 0 : 0;
    var offsetModifierValue = (_offsetModifierState$ = offsetModifierState == null ? void 0 : offsetModifierState[mainAxis]) != null ? _offsetModifierState$ : 0;
    var tetherMin = offset + minOffset - offsetModifierValue - clientOffset;
    var tetherMax = offset + maxOffset - offsetModifierValue;
    var preventedOffset = (0,_utils_within_js__WEBPACK_IMPORTED_MODULE_4__.within)(tether ? (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_10__.min)(min, tetherMin) : min, offset, tether ? (0,_utils_math_js__WEBPACK_IMPORTED_MODULE_10__.max)(max, tetherMax) : max);
    popperOffsets[mainAxis] = preventedOffset;
    data[mainAxis] = preventedOffset - offset;
  }

  if (checkAltAxis) {
    var _offsetModifierState$2;

    var _mainSide = mainAxis === 'x' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__.top : _enums_js__WEBPACK_IMPORTED_MODULE_0__.left;

    var _altSide = mainAxis === 'x' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__.bottom : _enums_js__WEBPACK_IMPORTED_MODULE_0__.right;

    var _offset = popperOffsets[altAxis];

    var _len = altAxis === 'y' ? 'height' : 'width';

    var _min = _offset + overflow[_mainSide];

    var _max = _offset - overflow[_altSide];

    var isOriginSide = [_enums_js__WEBPACK_IMPORTED_MODULE_0__.top, _enums_js__WEBPACK_IMPORTED_MODULE_0__.left].indexOf(basePlacement) !== -1;

    var _offsetModifierValue = (_offsetModifierState$2 = offsetModifierState == null ? void 0 : offsetModifierState[altAxis]) != null ? _offsetModifierState$2 : 0;

    var _tetherMin = isOriginSide ? _min : _offset - referenceRect[_len] - popperRect[_len] - _offsetModifierValue + normalizedTetherOffsetValue.altAxis;

    var _tetherMax = isOriginSide ? _offset + referenceRect[_len] + popperRect[_len] - _offsetModifierValue - normalizedTetherOffsetValue.altAxis : _max;

    var _preventedOffset = tether && isOriginSide ? (0,_utils_within_js__WEBPACK_IMPORTED_MODULE_4__.withinMaxClamp)(_tetherMin, _offset, _tetherMax) : (0,_utils_within_js__WEBPACK_IMPORTED_MODULE_4__.within)(tether ? _tetherMin : _min, _offset, tether ? _tetherMax : _max);

    popperOffsets[altAxis] = _preventedOffset;
    data[altAxis] = _preventedOffset - _offset;
  }

  state.modifiersData[name] = data;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'preventOverflow',
  enabled: true,
  phase: 'main',
  fn: preventOverflow,
  requiresIfExists: ['offset']
});

/***/ },

/***/ "./node_modules/@popperjs/core/lib/popper-lite.js"
/*!********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/popper-lite.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createPopper: () => (/* binding */ createPopper),
/* harmony export */   defaultModifiers: () => (/* binding */ defaultModifiers),
/* harmony export */   detectOverflow: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   popperGenerator: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_0__.popperGenerator)
/* harmony export */ });
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modifiers/eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony import */ var _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modifiers/popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony import */ var _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./modifiers/computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony import */ var _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./modifiers/applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");





var defaultModifiers = [_modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_2__["default"], _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_3__["default"], _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_4__["default"], _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_5__["default"]];
var createPopper = /*#__PURE__*/(0,_createPopper_js__WEBPACK_IMPORTED_MODULE_0__.popperGenerator)({
  defaultModifiers: defaultModifiers
}); // eslint-disable-next-line import/no-unused-modules



/***/ },

/***/ "./node_modules/@popperjs/core/lib/popper.js"
/*!***************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/popper.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   applyStyles: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.applyStyles),
/* harmony export */   arrow: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.arrow),
/* harmony export */   computeStyles: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.computeStyles),
/* harmony export */   createPopper: () => (/* binding */ createPopper),
/* harmony export */   createPopperLite: () => (/* reexport safe */ _popper_lite_js__WEBPACK_IMPORTED_MODULE_11__.createPopper),
/* harmony export */   defaultModifiers: () => (/* binding */ defaultModifiers),
/* harmony export */   detectOverflow: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   eventListeners: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.eventListeners),
/* harmony export */   flip: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.flip),
/* harmony export */   hide: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.hide),
/* harmony export */   offset: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.offset),
/* harmony export */   popperGenerator: () => (/* reexport safe */ _createPopper_js__WEBPACK_IMPORTED_MODULE_0__.popperGenerator),
/* harmony export */   popperOffsets: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.popperOffsets),
/* harmony export */   preventOverflow: () => (/* reexport safe */ _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__.preventOverflow)
/* harmony export */ });
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modifiers/eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony import */ var _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modifiers/popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony import */ var _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./modifiers/computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony import */ var _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./modifiers/applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");
/* harmony import */ var _modifiers_offset_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./modifiers/offset.js */ "./node_modules/@popperjs/core/lib/modifiers/offset.js");
/* harmony import */ var _modifiers_flip_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./modifiers/flip.js */ "./node_modules/@popperjs/core/lib/modifiers/flip.js");
/* harmony import */ var _modifiers_preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./modifiers/preventOverflow.js */ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js");
/* harmony import */ var _modifiers_arrow_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./modifiers/arrow.js */ "./node_modules/@popperjs/core/lib/modifiers/arrow.js");
/* harmony import */ var _modifiers_hide_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./modifiers/hide.js */ "./node_modules/@popperjs/core/lib/modifiers/hide.js");
/* harmony import */ var _popper_lite_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./popper-lite.js */ "./node_modules/@popperjs/core/lib/popper-lite.js");
/* harmony import */ var _modifiers_index_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./modifiers/index.js */ "./node_modules/@popperjs/core/lib/modifiers/index.js");










var defaultModifiers = [_modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_2__["default"], _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_3__["default"], _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_4__["default"], _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_5__["default"], _modifiers_offset_js__WEBPACK_IMPORTED_MODULE_6__["default"], _modifiers_flip_js__WEBPACK_IMPORTED_MODULE_7__["default"], _modifiers_preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__["default"], _modifiers_arrow_js__WEBPACK_IMPORTED_MODULE_9__["default"], _modifiers_hide_js__WEBPACK_IMPORTED_MODULE_10__["default"]];
var createPopper = /*#__PURE__*/(0,_createPopper_js__WEBPACK_IMPORTED_MODULE_0__.popperGenerator)({
  defaultModifiers: defaultModifiers
}); // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules



/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js"
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ computeAutoPlacement)
/* harmony export */ });
/* harmony import */ var _getVariation_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _detectOverflow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _getBasePlacement_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");




function computeAutoPlacement(state, options) {
  if (options === void 0) {
    options = {};
  }

  var _options = options,
      placement = _options.placement,
      boundary = _options.boundary,
      rootBoundary = _options.rootBoundary,
      padding = _options.padding,
      flipVariations = _options.flipVariations,
      _options$allowedAutoP = _options.allowedAutoPlacements,
      allowedAutoPlacements = _options$allowedAutoP === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_1__.placements : _options$allowedAutoP;
  var variation = (0,_getVariation_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  var placements = variation ? flipVariations ? _enums_js__WEBPACK_IMPORTED_MODULE_1__.variationPlacements : _enums_js__WEBPACK_IMPORTED_MODULE_1__.variationPlacements.filter(function (placement) {
    return (0,_getVariation_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement) === variation;
  }) : _enums_js__WEBPACK_IMPORTED_MODULE_1__.basePlacements;
  var allowedPlacements = placements.filter(function (placement) {
    return allowedAutoPlacements.indexOf(placement) >= 0;
  });

  if (allowedPlacements.length === 0) {
    allowedPlacements = placements;
  } // $FlowFixMe[incompatible-type]: Flow seems to have problems with two array unions...


  var overflows = allowedPlacements.reduce(function (acc, placement) {
    acc[placement] = (0,_detectOverflow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      padding: padding
    })[(0,_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_3__["default"])(placement)];
    return acc;
  }, {});
  return Object.keys(overflows).sort(function (a, b) {
    return overflows[a] - overflows[b];
  });
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/computeOffsets.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ computeOffsets)
/* harmony export */ });
/* harmony import */ var _getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _getVariation_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");




function computeOffsets(_ref) {
  var reference = _ref.reference,
      element = _ref.element,
      placement = _ref.placement;
  var basePlacement = placement ? (0,_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement) : null;
  var variation = placement ? (0,_getVariation_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) : null;
  var commonX = reference.x + reference.width / 2 - element.width / 2;
  var commonY = reference.y + reference.height / 2 - element.height / 2;
  var offsets;

  switch (basePlacement) {
    case _enums_js__WEBPACK_IMPORTED_MODULE_3__.top:
      offsets = {
        x: commonX,
        y: reference.y - element.height
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__.bottom:
      offsets = {
        x: commonX,
        y: reference.y + reference.height
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__.right:
      offsets = {
        x: reference.x + reference.width,
        y: commonY
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__.left:
      offsets = {
        x: reference.x - element.width,
        y: commonY
      };
      break;

    default:
      offsets = {
        x: reference.x,
        y: reference.y
      };
  }

  var mainAxis = basePlacement ? (0,_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(basePlacement) : null;

  if (mainAxis != null) {
    var len = mainAxis === 'y' ? 'height' : 'width';

    switch (variation) {
      case _enums_js__WEBPACK_IMPORTED_MODULE_3__.start:
        offsets[mainAxis] = offsets[mainAxis] - (reference[len] / 2 - element[len] / 2);
        break;

      case _enums_js__WEBPACK_IMPORTED_MODULE_3__.end:
        offsets[mainAxis] = offsets[mainAxis] + (reference[len] / 2 - element[len] / 2);
        break;

      default:
    }
  }

  return offsets;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/debounce.js"
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/debounce.js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ debounce)
/* harmony export */ });
function debounce(fn) {
  var pending;
  return function () {
    if (!pending) {
      pending = new Promise(function (resolve) {
        Promise.resolve().then(function () {
          pending = undefined;
          resolve(fn());
        });
      });
    }

    return pending;
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/detectOverflow.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ detectOverflow)
/* harmony export */ });
/* harmony import */ var _dom_utils_getClippingRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getClippingRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js");
/* harmony import */ var _dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _dom_utils_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _computeOffsets_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./computeOffsets.js */ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js");
/* harmony import */ var _rectToClientRect_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./rectToClientRect.js */ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./mergePaddingObject.js */ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js");
/* harmony import */ var _expandToHashMap_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./expandToHashMap.js */ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js");








 // eslint-disable-next-line import/no-unused-modules

function detectOverflow(state, options) {
  if (options === void 0) {
    options = {};
  }

  var _options = options,
      _options$placement = _options.placement,
      placement = _options$placement === void 0 ? state.placement : _options$placement,
      _options$strategy = _options.strategy,
      strategy = _options$strategy === void 0 ? state.strategy : _options$strategy,
      _options$boundary = _options.boundary,
      boundary = _options$boundary === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.clippingParents : _options$boundary,
      _options$rootBoundary = _options.rootBoundary,
      rootBoundary = _options$rootBoundary === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.viewport : _options$rootBoundary,
      _options$elementConte = _options.elementContext,
      elementContext = _options$elementConte === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.popper : _options$elementConte,
      _options$altBoundary = _options.altBoundary,
      altBoundary = _options$altBoundary === void 0 ? false : _options$altBoundary,
      _options$padding = _options.padding,
      padding = _options$padding === void 0 ? 0 : _options$padding;
  var paddingObject = (0,_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_7__["default"])(typeof padding !== 'number' ? padding : (0,_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_8__["default"])(padding, _enums_js__WEBPACK_IMPORTED_MODULE_5__.basePlacements));
  var altContext = elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__.popper ? _enums_js__WEBPACK_IMPORTED_MODULE_5__.reference : _enums_js__WEBPACK_IMPORTED_MODULE_5__.popper;
  var popperRect = state.rects.popper;
  var element = state.elements[altBoundary ? altContext : elementContext];
  var clippingClientRect = (0,_dom_utils_getClippingRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])((0,_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_6__.isElement)(element) ? element : element.contextElement || (0,_dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state.elements.popper), boundary, rootBoundary, strategy);
  var referenceClientRect = (0,_dom_utils_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state.elements.reference);
  var popperOffsets = (0,_computeOffsets_js__WEBPACK_IMPORTED_MODULE_3__["default"])({
    reference: referenceClientRect,
    element: popperRect,
    strategy: 'absolute',
    placement: placement
  });
  var popperClientRect = (0,_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_4__["default"])(Object.assign({}, popperRect, popperOffsets));
  var elementClientRect = elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__.popper ? popperClientRect : referenceClientRect; // positive = overflowing the clipping rect
  // 0 or negative = within the clipping rect

  var overflowOffsets = {
    top: clippingClientRect.top - elementClientRect.top + paddingObject.top,
    bottom: elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom,
    left: clippingClientRect.left - elementClientRect.left + paddingObject.left,
    right: elementClientRect.right - clippingClientRect.right + paddingObject.right
  };
  var offsetData = state.modifiersData.offset; // Offsets can be applied only to the popper element

  if (elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__.popper && offsetData) {
    var offset = offsetData[placement];
    Object.keys(overflowOffsets).forEach(function (key) {
      var multiply = [_enums_js__WEBPACK_IMPORTED_MODULE_5__.right, _enums_js__WEBPACK_IMPORTED_MODULE_5__.bottom].indexOf(key) >= 0 ? 1 : -1;
      var axis = [_enums_js__WEBPACK_IMPORTED_MODULE_5__.top, _enums_js__WEBPACK_IMPORTED_MODULE_5__.bottom].indexOf(key) >= 0 ? 'y' : 'x';
      overflowOffsets[key] += offset[axis] * multiply;
    });
  }

  return overflowOffsets;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js"
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/expandToHashMap.js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ expandToHashMap)
/* harmony export */ });
function expandToHashMap(value, keys) {
  return keys.reduce(function (hashMap, key) {
    hashMap[key] = value;
    return hashMap;
  }, {});
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getAltAxis.js"
/*!*************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getAltAxis.js ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getAltAxis)
/* harmony export */ });
function getAltAxis(axis) {
  return axis === 'x' ? 'y' : 'x';
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getBasePlacement.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getBasePlacement)
/* harmony export */ });

function getBasePlacement(placement) {
  return placement.split('-')[0];
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js"
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getFreshSideObject)
/* harmony export */ });
function getFreshSideObject() {
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0
  };
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js"
/*!***************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js ***!
  \***************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getMainAxisFromPlacement)
/* harmony export */ });
function getMainAxisFromPlacement(placement) {
  return ['top', 'bottom'].indexOf(placement) >= 0 ? 'x' : 'y';
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js"
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getOppositePlacement)
/* harmony export */ });
var hash = {
  left: 'right',
  right: 'left',
  bottom: 'top',
  top: 'bottom'
};
function getOppositePlacement(placement) {
  return placement.replace(/left|right|bottom|top/g, function (matched) {
    return hash[matched];
  });
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js"
/*!********************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js ***!
  \********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getOppositeVariationPlacement)
/* harmony export */ });
var hash = {
  start: 'end',
  end: 'start'
};
function getOppositeVariationPlacement(placement) {
  return placement.replace(/start|end/g, function (matched) {
    return hash[matched];
  });
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/getVariation.js"
/*!***************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getVariation.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getVariation)
/* harmony export */ });
function getVariation(placement) {
  return placement.split('-')[1];
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/math.js"
/*!*******************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/math.js ***!
  \*******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   max: () => (/* binding */ max),
/* harmony export */   min: () => (/* binding */ min),
/* harmony export */   round: () => (/* binding */ round)
/* harmony export */ });
var max = Math.max;
var min = Math.min;
var round = Math.round;

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/mergeByName.js"
/*!**************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/mergeByName.js ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ mergeByName)
/* harmony export */ });
function mergeByName(modifiers) {
  var merged = modifiers.reduce(function (merged, current) {
    var existing = merged[current.name];
    merged[current.name] = existing ? Object.assign({}, existing, current, {
      options: Object.assign({}, existing.options, current.options),
      data: Object.assign({}, existing.data, current.data)
    }) : current;
    return merged;
  }, {}); // IE11 does not support Object.values

  return Object.keys(merged).map(function (key) {
    return merged[key];
  });
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js"
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ mergePaddingObject)
/* harmony export */ });
/* harmony import */ var _getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFreshSideObject.js */ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js");

function mergePaddingObject(paddingObject) {
  return Object.assign({}, (0,_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(), paddingObject);
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/orderModifiers.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/orderModifiers.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ orderModifiers)
/* harmony export */ });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
 // source: https://stackoverflow.com/questions/49875255

function order(modifiers) {
  var map = new Map();
  var visited = new Set();
  var result = [];
  modifiers.forEach(function (modifier) {
    map.set(modifier.name, modifier);
  }); // On visiting object, check for its dependencies and visit them recursively

  function sort(modifier) {
    visited.add(modifier.name);
    var requires = [].concat(modifier.requires || [], modifier.requiresIfExists || []);
    requires.forEach(function (dep) {
      if (!visited.has(dep)) {
        var depModifier = map.get(dep);

        if (depModifier) {
          sort(depModifier);
        }
      }
    });
    result.push(modifier);
  }

  modifiers.forEach(function (modifier) {
    if (!visited.has(modifier.name)) {
      // check for visited object
      sort(modifier);
    }
  });
  return result;
}

function orderModifiers(modifiers) {
  // order based on dependencies
  var orderedModifiers = order(modifiers); // order based on phase

  return _enums_js__WEBPACK_IMPORTED_MODULE_0__.modifierPhases.reduce(function (acc, phase) {
    return acc.concat(orderedModifiers.filter(function (modifier) {
      return modifier.phase === phase;
    }));
  }, []);
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/rectToClientRect.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ rectToClientRect)
/* harmony export */ });
function rectToClientRect(rect) {
  return Object.assign({}, rect, {
    left: rect.x,
    top: rect.y,
    right: rect.x + rect.width,
    bottom: rect.y + rect.height
  });
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/userAgent.js"
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/userAgent.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getUAString)
/* harmony export */ });
function getUAString() {
  var uaData = navigator.userAgentData;

  if (uaData != null && uaData.brands && Array.isArray(uaData.brands)) {
    return uaData.brands.map(function (item) {
      return item.brand + "/" + item.version;
    }).join(' ');
  }

  return navigator.userAgent;
}

/***/ },

/***/ "./node_modules/@popperjs/core/lib/utils/within.js"
/*!*********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/within.js ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   within: () => (/* binding */ within),
/* harmony export */   withinMaxClamp: () => (/* binding */ withinMaxClamp)
/* harmony export */ });
/* harmony import */ var _math_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");

function within(min, value, max) {
  return (0,_math_js__WEBPACK_IMPORTED_MODULE_0__.max)(min, (0,_math_js__WEBPACK_IMPORTED_MODULE_0__.min)(value, max));
}
function withinMaxClamp(min, value, max) {
  var v = within(min, value, max);
  return v > max ? max : v;
}

/***/ },

/***/ "./resources/js/components/logo-tilt.js"
/*!**********************************************!*\
  !*** ./resources/js/components/logo-tilt.js ***!
  \**********************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var VanillaTilt = __webpack_require__(/*! vanilla-tilt */ "./node_modules/vanilla-tilt/lib/vanilla-tilt.js");
module.exports = function initializeLogoTilt(win) {
  function start() {
    var logos = Array.from(win.document.querySelectorAll('[data-logo-tilt]'));
    if (!logos.length || !win.matchMedia) return;
    var motion = win.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)');
    function sync() {
      logos.forEach(function (logo) {
        if (motion.matches) {
          VanillaTilt.init(logo, {
            max: 12,
            perspective: 600,
            scale: 1,
            speed: 350,
            glare: false,
            gyroscope: false,
            // Keep the link's hover area steady while its artwork tilts.
            'mouse-event-element': logo.closest('a') || logo
          });
        } else if (logo.vanillaTilt) {
          logo.vanillaTilt.destroy();
        }
      });
    }
    if (motion.addEventListener) motion.addEventListener('change', sync);else motion.addListener(sync);
    sync();
  }
  if (win.document.readyState === 'loading') {
    win.document.addEventListener('DOMContentLoaded', start, {
      once: true
    });
  } else start();
};

/***/ },

/***/ "./resources/js/controls/font-select.js"
/*!**********************************************!*\
  !*** ./resources/js/controls/font-select.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FontSelect: () => (/* binding */ FontSelect)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var FontSelect = /*#__PURE__*/function () {
  // Reuse the host bundle's Bootstrap instance and its existing data API.
  function FontSelect(root, Dropdown) {
    var _this = this,
      _root$closest;
    _classCallCheck(this, FontSelect);
    this.root = root;
    this.input = root.querySelector('select');
    this.trigger = root.querySelector('[data-font-toggle]');
    this.label = root.querySelector('[data-font-label]');
    this.current = root.querySelector('[data-font-current]');
    this.options = root.querySelector('[data-font-options]');
    this.choices = _toConsumableArray(root.querySelectorAll('[data-font-value]'));
    this.dropdown = new Dropdown(this.trigger, {
      boundary: root.closest('[data-settings-scroll]') || 'clippingParents'
    });
    this.trigger.addEventListener('shown.bs.dropdown', function () {
      _this.render();
      _this.size();
      var selected = _this.choices.find(function (choice) {
        return choice.dataset.fontValue === _this.input.value;
      });
      selected === null || selected === void 0 || selected.focus({
        preventScroll: true
      });
      if (selected) _this.options.scrollTop = selected.offsetTop - _this.options.offsetTop;
    });
    this.input.addEventListener('change', function () {
      return _this.render();
    });
    root.addEventListener('click', function (event) {
      var choice = event.target.closest('[data-font-value]');
      if (!choice) return;
      _this.input.value = choice.dataset.fontValue;
      _this.input.dispatchEvent(new Event('change', {
        bubbles: true
      }));
      _this.close();
      _this.trigger.focus();
    });
    root.addEventListener('keydown', function (event) {
      if (_this.trigger.getAttribute('aria-expanded') !== 'true') return;
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        _this.close();
        _this.trigger.focus();
      } else if (event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        _this.choices[event.key === 'Home' ? 0 : _this.choices.length - 1].focus();
      }
    });
    (_root$closest = root.closest('.offcanvas')) === null || _root$closest === void 0 || _root$closest.addEventListener('hide.bs.offcanvas', function () {
      return _this.close();
    });
    window.addEventListener('resize', function () {
      if (_this.trigger.getAttribute('aria-expanded') === 'true') _this.size();
    });
    this.render();
  }
  return _createClass(FontSelect, [{
    key: "close",
    value: function close() {
      this.dropdown.hide();
    }
  }, {
    key: "size",
    value: function size() {
      var _this$root$closest, _bounds$bottom, _bounds$top;
      var trigger = this.trigger.getBoundingClientRect();
      var bounds = (_this$root$closest = this.root.closest('[data-settings-scroll], .offcanvas')) === null || _this$root$closest === void 0 ? void 0 : _this$root$closest.getBoundingClientRect();
      var below = Math.min(window.innerHeight, (_bounds$bottom = bounds === null || bounds === void 0 ? void 0 : bounds.bottom) !== null && _bounds$bottom !== void 0 ? _bounds$bottom : window.innerHeight) - trigger.bottom - 12;
      var above = trigger.top - Math.max(0, (_bounds$top = bounds === null || bounds === void 0 ? void 0 : bounds.top) !== null && _bounds$top !== void 0 ? _bounds$top : 0) - 12;
      this.options.style.maxHeight = "".concat(Math.max(80, Math.min(352, Math.max(above, below) - 16)), "px");
      this.dropdown.update();
    }
  }, {
    key: "render",
    value: function render() {
      var _this2 = this,
        _document$querySelect;
      var selected = this.choices.find(function (choice) {
        return choice.dataset.fontValue === _this2.input.value;
      });
      if (!selected) return;
      this.label.textContent = selected.querySelector('[data-font-name]').textContent;
      this.current.dataset.numberFont = this.input.value;
      var source = this.root.dataset.fontPreviewSource;
      var sample = (source ? (_document$querySelect = document.querySelector(source)) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.textContent : null) || '120';
      this.current.textContent = sample;
      this.root.querySelectorAll('[data-font-sample]').forEach(function (preview) {
        preview.textContent = sample;
      });
      this.choices.forEach(function (choice) {
        var active = choice === selected;
        choice.classList.toggle('active', active);
        choice.setAttribute('aria-checked', String(active));
      });
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/clock.js"
/*!*****************************************!*\
  !*** ./resources/js/metronome/clock.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   MetronomeClock: () => (/* binding */ MetronomeClock)
/* harmony export */ });
/* harmony import */ var _gesture__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./gesture */ "./resources/js/metronome/gesture.js");
/* harmony import */ var _settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./settings */ "./resources/js/metronome/settings.js");
/* harmony import */ var _voice_stretch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./voice-stretch */ "./resources/js/metronome/voice-stretch.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }



var MetronomeClock = /*#__PURE__*/function () {
  function MetronomeClock(_ref) {
    var createContext = _ref.createContext,
      onStateChange = _ref.onStateChange,
      onBeat = _ref.onBeat,
      onError = _ref.onError,
      _ref$onReady = _ref.onReady,
      onReady = _ref$onReady === void 0 ? function () {} : _ref$onReady,
      _ref$onTick = _ref.onTick,
      onTick = _ref$onTick === void 0 ? function () {} : _ref$onTick;
    _classCallCheck(this, MetronomeClock);
    this.createContext = createContext;
    this.onStateChange = onStateChange;
    this.onBeat = onBeat;
    this.onError = onError;
    this.onReady = onReady;
    this.onTick = onTick;
    this.tempo = _gesture__WEBPACK_IMPORTED_MODULE_0__.DEFAULT_TEMPO;
    this.running = false;
    this.ready = false;
    this.generation = 0;
    this.context = null;
    this.audioSession = null;
    this.timer = null;
    this.notes = new Set();
    this.beats = [];
    this.presentation = [];
    this.presentedBeat = null;
    this.beatAnchor = null;
    this.beatSpan = 1;
    this.beatsPerBar = 1;
    this.sound = 'click';
    this.voiceBuffers = null;
    this.preparedVoices = null;
    this.preparedVoiceTempo = null;
    this.nextBeatIndex = 0;
    this.lastBeatIndex = null;
  }
  return _createClass(MetronomeClock, [{
    key: "start",
    value: function () {
      var _start = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this = this;
        var _ref2,
          _ref2$leadInBeats,
          leadInBeats,
          generation,
          _args = arguments,
          _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              _ref2 = _args.length > 0 && _args[0] !== undefined ? _args[0] : {}, _ref2$leadInBeats = _ref2.leadInBeats, leadInBeats = _ref2$leadInBeats === void 0 ? 0 : _ref2$leadInBeats;
              if (!this.running) {
                _context.n = 1;
                break;
              }
              return _context.a(2);
            case 1:
              generation = ++this.generation;
              this.running = true;
              this.onStateChange(true);
              _context.p = 2;
              _context.n = 3;
              return this.resumeAudio();
            case 3:
              if (!(!this.running || generation !== this.generation)) {
                _context.n = 4;
                break;
              }
              if (!this.running) this.suspendAudio();
              return _context.a(2);
            case 4:
              if (!(this.context.state !== 'running')) {
                _context.n = 5;
                break;
              }
              throw new Error('Audio is unavailable.');
            case 5:
              this.prepareVoices();
              this.ready = true;
              this.beatSpan = leadInBeats || 1;
              this.beatAnchor = leadInBeats ? this.context.currentTime : null;
              this.nextBeatIndex = 0;
              this.lastBeatIndex = null;
              this.presentation = [{
                at: this.context.currentTime,
                anchor: this.beatAnchor,
                index: null,
                beat: null,
                span: this.beatSpan,
                tempo: this.tempo
              }];
              this.presentedBeat = null;
              this.nextBeatTime = this.context.currentTime + (leadInBeats ? leadInBeats * 60 / this.tempo : 0.02);
              this.schedule(false);
              this.timer = setInterval(function () {
                return _this.schedule();
              }, 25);
              this.onReady();
              _context.n = 8;
              break;
            case 6:
              _context.p = 6;
              _t = _context.v;
              if (!(generation !== this.generation)) {
                _context.n = 7;
                break;
              }
              return _context.a(2);
            case 7:
              this.stop();
              this.onError(_t);
            case 8:
              return _context.a(2);
          }
        }, _callee, this, [[2, 6]]);
      }));
      function start() {
        return _start.apply(this, arguments);
      }
      return start;
    }()
  }, {
    key: "stop",
    value: function stop() {
      this.generation++;
      this.running = false;
      this.ready = false;
      clearInterval(this.timer);
      this.timer = null;
      var _iterator = _createForOfIteratorHelper(this.notes),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var note = _step.value;
          this.cancelNote(note);
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
      this.beats = [];
      this.presentation = [];
      this.presentedBeat = null;
      this.beatAnchor = null;
      this.lastBeatIndex = null;
      this.suspendAudio();
      this.onStateChange(false);
    }
  }, {
    key: "getContext",
    value: function getContext() {
      var _this2 = this;
      if (!this.context || this.context.state === 'closed') {
        var context = this.context = this.createContext();
        context.onstatechange = function () {
          if (_this2.ready && _this2.context === context && context.state !== 'running') _this2.stop();
        };
      }
      return this.context;
    }
  }, {
    key: "resumeAudio",
    value: function resumeAudio() {
      this.requestPlaybackAudio();
      return this.getContext().resume();
    }
  }, {
    key: "suspendAudio",
    value: function suspendAudio() {
      var _this$context, _this$context$suspend, _this$context2;
      if (((_this$context = this.context) === null || _this$context === void 0 ? void 0 : _this$context.state) === 'running') (_this$context$suspend = (_this$context2 = this.context).suspend) === null || _this$context$suspend === void 0 || _this$context$suspend.call(_this$context2)["catch"](function () {});
    }
  }, {
    key: "releaseAudio",
    value: function () {
      var _releaseAudio = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var generation, context;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              this.stop();
              generation = this.generation;
              context = this.context;
              this.context = null;
              if (context) context.onstatechange = null;
              if (!(context && context.state !== 'closed')) {
                _context2.n = 1;
                break;
              }
              _context2.n = 1;
              return context.close()["catch"](function () {});
            case 1:
              if (generation === this.generation && !this.context) this.restoreAudioSession();
            case 2:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function releaseAudio() {
        return _releaseAudio.apply(this, arguments);
      }
      return releaseAudio;
    }()
  }, {
    key: "requestPlaybackAudio",
    value: function requestPlaybackAudio() {
      try {
        var _this$audioSession, _this$audioSession$pr, _this$audioSession2;
        var session = typeof navigator === 'undefined' ? null : navigator.audioSession;
        if (!session) return;
        if (((_this$audioSession = this.audioSession) === null || _this$audioSession === void 0 ? void 0 : _this$audioSession.session) === session && session.type === 'playback') return;
        var previousType = (_this$audioSession$pr = (_this$audioSession2 = this.audioSession) === null || _this$audioSession2 === void 0 ? void 0 : _this$audioSession2.previousType) !== null && _this$audioSession$pr !== void 0 ? _this$audioSession$pr : session.type;
        // iPhone Web Audio defaults to ambient, which obeys Silent Mode.
        session.type = 'playback';
        this.audioSession = {
          session: session,
          previousType: previousType
        };
      } catch (error) {
        // This optional browser API must never prevent normal playback.
      }
    }
  }, {
    key: "restoreAudioSession",
    value: function restoreAudioSession() {
      var audioSession = this.audioSession;
      this.audioSession = null;
      if (!audioSession) return;
      try {
        var session = audioSession.session,
          previousType = audioSession.previousType;
        if (session.type === 'playback') session.type = previousType;
      } catch (error) {/* The browser may no longer allow session changes. */}
    }
  }, {
    key: "setTempo",
    value: function setTempo(value) {
      var _previous$beat;
      var tempo = Math.round((0,_gesture__WEBPACK_IMPORTED_MODULE_0__.clampTempo)(value));
      if (tempo === this.tempo) return;
      var previousTempo = this.tempo;
      this.tempo = tempo;
      if (!this.ready) return;
      this.prepareVoices();
      var now = this.context.currentTime;
      this.updateBeat(now);
      if (this.beatAnchor === null && this.beats.length) this.nextBeatTime = this.beats[0].time;
      var _iterator2 = _createForOfIteratorHelper(this.notes),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var note = _step2.value;
          if (note.time > now) this.cancelNote(note);
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
      this.beats = [];
      this.presentation = this.presentation.filter(function (beat) {
        return beat.at <= now;
      });
      this.nextBeatIndex = this.lastBeatIndex === null ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;

      // Preserve the progress through this beat when tempo changes mid-swipe.
      if (this.beatAnchor !== null) {
        var progress = Math.min(1, Math.max(0, (now - this.beatAnchor) / (this.beatSpan * 60 / previousTempo)));
        var interval = this.beatSpan * 60 / tempo;
        this.beatAnchor = now - progress * interval;
        this.nextBeatTime = now + (1 - progress) * interval;
      }
      var previous = this.presentation[this.presentation.length - 1];
      this.presentation.push({
        at: now,
        anchor: this.beatAnchor,
        span: this.beatSpan,
        index: this.lastBeatIndex,
        beat: (_previous$beat = previous === null || previous === void 0 ? void 0 : previous.beat) !== null && _previous$beat !== void 0 ? _previous$beat : null,
        tempo: tempo
      });
      this.schedule();
    }
  }, {
    key: "setBeats",
    value: function setBeats(value) {
      if (![1, 2, 3, 4].includes(value) || value === this.beatsPerBar) return;
      this.beatsPerBar = value;
      this.reschedulePending(true);
    }
  }, {
    key: "setSound",
    value: function setSound(value) {
      if (!(value === 'my-voice' && this.voiceBuffers) && !Object.prototype.hasOwnProperty.call(_settings__WEBPACK_IMPORTED_MODULE_1__.SOUND_PROFILES, value)) return;
      if (value === this.sound) return;
      this.sound = value;
      this.reschedulePending();
    }
  }, {
    key: "setVoiceBuffers",
    value: function setVoiceBuffers(buffers) {
      if (buffers !== this.voiceBuffers) this.preparedVoices = null;
      this.voiceBuffers = (buffers === null || buffers === void 0 ? void 0 : buffers.length) === 4 ? buffers : null;
      if (!this.voiceBuffers && this.sound === 'my-voice') this.setSound('click');else if (this.sound === 'my-voice') this.reschedulePending();
    }
  }, {
    key: "reschedulePending",
    value: function reschedulePending() {
      var restartBar = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      if (!this.ready) return;
      this.prepareVoices();
      var now = this.context.currentTime;
      this.updateBeat(now);
      if (this.beats.length) this.nextBeatTime = this.beats[0].time;
      var _iterator3 = _createForOfIteratorHelper(this.notes),
        _step3;
      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var note = _step3.value;
          if (note.time > now) this.cancelNote(note);
        }
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
      this.beats = [];
      this.presentation = this.presentation.filter(function (beat) {
        return beat.at <= now;
      });
      this.nextBeatIndex = restartBar || this.lastBeatIndex === null ? 0 : (this.lastBeatIndex + 1) % this.beatsPerBar;
      if (restartBar) this.lastBeatIndex = null;
      this.schedule();
    }
  }, {
    key: "getBeatPosition",
    value: function getBeatPosition() {
      var _state$index, _state$anchor, _state$tempo;
      var now = this.ready ? this.outputTime() : 0;
      var state = this.presentationAt(now);
      var beat = (_state$index = state === null || state === void 0 ? void 0 : state.index) !== null && _state$index !== void 0 ? _state$index : null;
      var anchor = (_state$anchor = state === null || state === void 0 ? void 0 : state.anchor) !== null && _state$anchor !== void 0 ? _state$anchor : null;
      var tempo = (_state$tempo = state === null || state === void 0 ? void 0 : state.tempo) !== null && _state$tempo !== void 0 ? _state$tempo : this.tempo;
      return {
        beat: beat !== null && beat !== void 0 ? beat : this.beatsPerBar - 1,
        preparing: beat === null,
        waiting: this.ready && (!state || now < state.at),
        tempo: tempo,
        progress: this.ready && anchor !== null ? Math.min(1, Math.max(0, (now - anchor) / (state.span * 60 / tempo))) : 0
      };
    }
  }, {
    key: "outputTime",
    value: function outputTime() {
      var _ref3, _context$outputLatenc;
      var context = this.context;
      // currentTime is the rendering head, ahead of the sound at the speaker.
      // Map the device's timestamp to now, including after a route change.
      try {
        var _context$getOutputTim;
        var stamp = (_context$getOutputTim = context.getOutputTimestamp) === null || _context$getOutputTim === void 0 ? void 0 : _context$getOutputTim.call(context);
        if (stamp && stamp.performanceTime > 0 && Number.isFinite(stamp.contextTime) && stamp.contextTime >= 0) {
          var age = (performance.now() - stamp.performanceTime) / 1000;
          if (age >= 0 && age < 1) return Math.min(context.currentTime, stamp.contextTime + age);
        }
      } catch (error) {/* Older browsers may not expose an output timestamp. */}
      var latency = (_ref3 = (_context$outputLatenc = context.outputLatency) !== null && _context$outputLatenc !== void 0 ? _context$outputLatenc : context.baseLatency) !== null && _ref3 !== void 0 ? _ref3 : 0;
      return context.currentTime - (Number.isFinite(latency) && latency > 0 ? latency : 0);
    }
  }, {
    key: "presentationAt",
    value: function presentationAt(now) {
      var state = null;
      var _iterator4 = _createForOfIteratorHelper(this.presentation),
        _step4;
      try {
        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
          var entry = _step4.value;
          if (entry.at > now) break;
          state = entry;
        }
      } catch (err) {
        _iterator4.e(err);
      } finally {
        _iterator4.f();
      }
      return state;
    }
  }, {
    key: "updatePresentation",
    value: function updatePresentation() {
      var notify = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      var now = this.outputTime();
      while (this.presentation.length > 1 && this.presentation[1].at <= now) this.presentation.shift();
      var state = this.presentationAt(now);
      if (state !== null && state !== void 0 && state.beat && state.beat !== this.presentedBeat) {
        this.presentedBeat = state.beat;
        this.onBeat(state.index);
      }
      if (notify) this.onTick();
    }
  }, {
    key: "updateBeat",
    value: function updateBeat(now) {
      while (this.beats.length && this.beats[0].time <= now) {
        var beat = this.beats.shift();
        this.beatAnchor = beat.time;
        this.beatSpan = 1;
        this.lastBeatIndex = beat.index;
      }
    }
  }, {
    key: "schedule",
    value: function schedule() {
      var notify = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      if (!this.ready) return;
      var now = this.context.currentTime;
      this.updateBeat(now);

      // Schedule against the audio clock, so rendering cannot move the clicks.
      // If a tab was stalled, resume once instead of replaying missed beats.
      if (this.nextBeatTime < now) this.nextBeatTime = now + 0.01;
      while (this.nextBeatTime < now + 0.1) {
        this.playClick(this.nextBeatTime, this.nextBeatIndex);
        this.beats.push({
          time: this.nextBeatTime,
          index: this.nextBeatIndex
        });
        this.presentation.push({
          at: this.nextBeatTime,
          anchor: this.nextBeatTime,
          index: this.nextBeatIndex,
          beat: {},
          span: 1,
          tempo: this.tempo
        });
        this.nextBeatIndex = (this.nextBeatIndex + 1) % this.beatsPerBar;
        this.nextBeatTime += 60 / this.tempo;
      }
      this.updatePresentation(notify);
    }
  }, {
    key: "playClick",
    value: function playClick(time, beatIndex) {
      var _this3 = this;
      if (this.sound === 'my-voice' && this.voiceBuffers) return this.playVoice(time, beatIndex);
      var profile = _settings__WEBPACK_IMPORTED_MODULE_1__.SOUND_PROFILES[this.sound];
      var pitch = beatIndex === 0 ? 1 : 0.82;
      var gain = this.context.createGain();
      // Percussive sounds decay immediately after a crisp attack. Only the
      // electronic beep holds its tone; high peaks retain the louder count
      // without stretching the ticks or clipping the combined voices.
      var peak = profile.volume / profile.ratios.length;
      gain.gain.setValueAtTime(0, time);
      gain.gain.linearRampToValueAtTime(peak, time + profile.attack);
      if (profile.hold) gain.gain.setValueAtTime(peak, time + profile.attack + profile.hold);
      gain.gain.exponentialRampToValueAtTime(0.001, time + profile.duration - 0.002);
      gain.gain.linearRampToValueAtTime(0, time + profile.duration);
      gain.connect(this.context.destination);
      var oscillators = profile.ratios.map(function (ratio) {
        var oscillator = _this3.context.createOscillator();
        oscillator.type = profile.type;
        var frequency = profile.frequency * pitch * ratio;
        oscillator.frequency.setValueAtTime(frequency, time);
        oscillator.connect(gain);
        return oscillator;
      });
      var note = {
        oscillators: oscillators,
        gain: gain,
        time: time
      };
      this.notes.add(note);
      var remaining = oscillators.length;
      oscillators.forEach(function (oscillator) {
        oscillator.onended = function () {
          oscillator.disconnect();
          if (--remaining === 0) {
            gain.disconnect();
            _this3.notes["delete"](note);
          }
        };
        oscillator.start(time);
        oscillator.stop(time + profile.duration);
      });
    }
  }, {
    key: "prepareVoices",
    value: function prepareVoices() {
      var _this4 = this;
      if (this.sound !== 'my-voice' || !this.voiceBuffers) return;
      if (this.preparedVoices && this.preparedVoiceTempo === this.tempo) return;
      // Prepare once before starting/rescheduling, never inside the beat
      // scheduler. Keep only this tempo's four clips to bound memory usage.
      var duration = 60 / this.tempo * 0.94;
      this.preparedVoices = this.voiceBuffers.map(function (buffer) {
        return (0,_voice_stretch__WEBPACK_IMPORTED_MODULE_2__.compressVoice)(buffer, duration, function (channels, length, rate) {
          return _this4.context.createBuffer(channels, length, rate);
        });
      });
      this.preparedVoiceTempo = this.tempo;
    }
  }, {
    key: "playVoice",
    value: function playVoice(time, beatIndex) {
      var _this5 = this;
      var buffer = this.preparedVoices[beatIndex];
      var duration = buffer.duration;
      // Finish an already playing syllable before the next beat after a tempo
      // change; a stale long clip must never overlap the new count.
      var _iterator5 = _createForOfIteratorHelper(this.notes),
        _step5;
      try {
        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
          var _note = _step5.value;
          if (_note.voiceEnd > time) _note.oscillators.forEach(function (source) {
            return source.stop(time);
          });
        }
      } catch (err) {
        _iterator5.e(err);
      } finally {
        _iterator5.f();
      }
      var source = this.context.createBufferSource();
      source.buffer = buffer;
      source.playbackRate.setValueAtTime(1, time);
      var gain = this.context.createGain();
      gain.gain.setValueAtTime(0, time);
      gain.gain.linearRampToValueAtTime(1, time + 0.003);
      gain.gain.setValueAtTime(1, time + Math.max(0.003, duration - 0.005));
      gain.gain.linearRampToValueAtTime(0, time + duration);
      source.connect(gain);
      gain.connect(this.context.destination);
      var note = {
        oscillators: [source],
        gain: gain,
        time: time,
        voiceEnd: time + duration
      };
      this.notes.add(note);
      source.onended = function () {
        source.disconnect();
        gain.disconnect();
        _this5.notes["delete"](note);
      };
      source.start(time);
      source.stop(time + duration);
    }
  }, {
    key: "cancelNote",
    value: function cancelNote(note) {
      note.oscillators.forEach(function (oscillator) {
        oscillator.stop();
        oscillator.disconnect();
      });
      note.gain.disconnect();
      this.notes["delete"](note);
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/conductor.js"
/*!*********************************************!*\
  !*** ./resources/js/metronome/conductor.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CONDUCTING_PATTERNS: () => (/* binding */ CONDUCTING_PATTERNS),
/* harmony export */   ConductorView: () => (/* binding */ ConductorView),
/* harmony export */   conductorPoint: () => (/* binding */ conductorPoint),
/* harmony export */   conductorPreparationBeats: () => (/* binding */ conductorPreparationBeats),
/* harmony export */   conductorPreparationPoint: () => (/* binding */ conductorPreparationPoint)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
// Traced from the four supplied conducting-pattern screenshots.
// Motion follows the reference video's flowing half-beat gestures: ease through
// turns, carry momentum between them, and soften the top/bottom reversals.
// The four-beat side loops mirror each other around the center downstroke.
var CONDUCTING_PATTERNS = {
  1: [{
    point: [200, 540],
    curves: [{
      to: [208.85, 384.54],
      controls: [[173.1, 508.74], [200.34, 423.2]]
    }, {
      to: [235.85, 210.89],
      controls: [[221.33, 327.78], [235.85, 270.11]]
    }, {
      to: [200.83, 36],
      controls: [[235.85, 153.6], [211.94, 91.77]]
    }, {
      to: [200, 540],
      controls: [[215, 204.22], [213.91, 372.13]]
    }]
  }],
  2: [{
    point: [200, 540],
    curves: [
    // Match the three-beat turn's rounded, horizontal crest.
    {
      to: [247, 343],
      controls: [[312.81, 572.13], [301, 343]]
    }, {
      to: [178.55, 416.85],
      controls: [[212.81, 343], [239.55, 434.83]]
    }, {
      to: [218.96, 256.19],
      controls: [[216.22, 388.02], [218.96, 301.19]]
    }, {
      to: [200, 36],
      controls: [[218.96, 183.16], [211.01, 108.19]]
    }]
  }, {
    point: [200, 36],
    curves: [{
      to: [200, 540],
      controls: [[200, 204], [200, 372]]
    }]
  }],
  3: [{
    point: [200, 540],
    curves: [{
      to: [203.83, 326.33],
      controls: [[175.99, 514.59], [162.56, 326.33]]
    }, {
      to: [304.96, 450.7],
      controls: [[226.88, 326.33], [261.71, 450.7]]
    },
    // A shared horizontal tangent rounds the right-hand reversal.
    {
      to: [354, 299],
      controls: [[349.3, 450.7], [402, 299]]
    }]
  }, {
    point: [354, 299],
    curves: [{
      to: [306.18, 406.25],
      controls: [[313.64, 299], [337.04, 406.25]]
    }, {
      to: [200, 36],
      controls: [[215.55, 406.25], [197.12, 94.21]]
    }]
  }, {
    point: [200, 36],
    curves: [{
      to: [200, 540],
      controls: [[200, 204], [200, 372]]
    }]
  }],
  4: [{
    point: [200, 540],
    curves: [{
      to: [208.28, 402.99],
      controls: [[200, 481.8], [202.76, 453.12]]
    }, {
      to: [200.57, 305.53],
      controls: [[209.56, 391.32], [223.13, 305.53]]
    }, {
      to: [63.15, 464.16],
      controls: [[171.7, 305.53], [96.35, 464.16]]
    }]
  }, {
    point: [63.15, 464.16],
    curves: [{
      to: [79.46, 303.09],
      controls: [[17.47, 464.16], [32.63, 303.09]]
    }, {
      to: [336.85, 464.16],
      controls: [[129.84, 303.09], [303.65, 464.16]]
    }]
  }, {
    point: [336.85, 464.16],
    curves: [{
      to: [320.54, 303.09],
      controls: [[382.53, 464.16], [367.37, 303.09]]
    }, {
      to: [235, 384],
      controls: [[270.16, 303.09], [255, 384]]
    }, {
      to: [200, 36],
      controls: [[210, 384], [204.69, 88.9]]
    }]
  }, {
    point: [200, 36],
    curves: [{
      to: [200, 540],
      controls: [[200, 204], [200, 372]]
    }]
  }]
};
function curvePoint(start, curve, progress) {
  var t = Math.min(1, Math.max(0, progress));
  var u = 1 - t;
  return start.map(function (value, axis) {
    return Math.pow(u, 3) * value + 3 * Math.pow(u, 2) * t * curve.controls[0][axis] + 3 * u * Math.pow(t, 2) * curve.controls[1][axis] + Math.pow(t, 3) * curve.to[axis];
  });
}
var ARC_SAMPLES = 1024;
function measureStroke(segment) {
  var samples = [{
    point: segment.point,
    distance: 0
  }];
  var curveEnds = [];
  var start = segment.point;
  var previous = start;
  var length = 0;
  var _iterator = _createForOfIteratorHelper(segment.curves),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var curve = _step.value;
      // Cache a dense arc-length map once, rather than timing individual
      // Bézier parameters: equal parameter steps do not cover equal distances.
      for (var step = 1; step <= ARC_SAMPLES; step++) {
        var point = curvePoint(start, curve, step / ARC_SAMPLES);
        length += Math.hypot(point[0] - previous[0], point[1] - previous[1]);
        samples.push({
          point: point,
          distance: length,
          start: start,
          curve: curve,
          parameter: step / ARC_SAMPLES
        });
        previous = point;
      }
      curveEnds.push(length);
      start = curve.to;
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  return {
    samples: samples,
    curveEnds: curveEnds,
    length: length
  };
}

// Each pair marks the curve ending a half-beat gesture and its through-speed.
// Construction joins along a stroke do not get their own timing or easing.
// Zero is reserved for sharp reversals; rounded turns keep moving.
var FLOW_GESTURES = {
  1: [[2, 0], [3, 0]],
  2: [[0, 0.5], [1, 0], [3, 0], [4, 0]],
  3: [[0, 0.5], [1, 0.65], [2, 0.5], [3, 0.65], [4, 0], [5, 0]],
  4: [[1, 0.5], [2, 0.65], [3, 0.65], [4, 0.65], [5, 0.5], [6, 0.65], [7, 0], [8, 0]]
};
var conductingMotion = Object.fromEntries(Object.entries(CONDUCTING_PATTERNS).map(function (_ref) {
  var _ref2 = _slicedToArray(_ref, 2),
    beats = _ref2[0],
    pattern = _ref2[1];
  var motion = measureStroke({
    point: pattern[0].point,
    curves: pattern.flatMap(function (segment) {
      return segment.curves;
    })
  });
  var timing = [{
    time: 0,
    distance: 0,
    speed: 0
  }].concat(_toConsumableArray(FLOW_GESTURES[beats].map(function (_ref3, index) {
    var _ref4 = _slicedToArray(_ref3, 2),
      curve = _ref4[0],
      speed = _ref4[1];
    return {
      time: (index + 1) / 2,
      distance: motion.curveEnds[curve],
      speed: speed
    };
  })));
  timing.forEach(function (knot, index) {
    if (!knot.speed) return;
    var before = timing[index - 1];
    var after = timing[index + 1];
    var incoming = (knot.distance - before.distance) / (knot.time - before.time);
    var outgoing = (after.distance - knot.distance) / (after.time - knot.time);
    // A limited harmonic-mean tangent keeps distance monotonic and
    // shares exactly the same velocity on both sides of each turn.
    knot.speed *= 2 * incoming * outgoing / (incoming + outgoing);
  });
  return [beats, _objectSpread(_objectSpread({
    beats: Number(beats)
  }, motion), {}, {
    timing: timing
  })];
}));
function distanceAtTime(motion, time) {
  if (time <= 0) return 0;
  if (time >= motion.beats) return motion.length;
  var index = Math.min(Math.floor(time * 2), motion.timing.length - 2);
  var start = motion.timing[index];
  var end = motion.timing[index + 1];
  var duration = end.time - start.time;
  var t = (time - start.time) / duration;
  // Cubic Hermite interpolation eases distance, not the Bézier parameter.
  // Unlike separate ease-in/out animations, rounded joins never stop or
  // restart; both adjacent gestures use the same tangent speed.
  return (2 * Math.pow(t, 3) - 3 * Math.pow(t, 2) + 1) * start.distance + (Math.pow(t, 3) - 2 * Math.pow(t, 2) + t) * duration * start.speed + (-2 * Math.pow(t, 3) + 3 * Math.pow(t, 2)) * end.distance + (Math.pow(t, 3) - Math.pow(t, 2)) * duration * end.speed;
}
function pointAtDistance(_ref5, distance) {
  var samples = _ref5.samples,
    length = _ref5.length;
  if (distance <= 0 || length === 0) return samples[0].point;
  if (distance >= length) return samples[samples.length - 1].point;
  var low = 1;
  var high = samples.length - 1;
  while (low < high) {
    var middle = Math.floor((low + high) / 2);
    if (samples[middle].distance < distance) low = middle + 1;else high = middle;
  }
  var before = samples[low - 1];
  var after = samples[low];
  if (distance === after.distance) return after.point;
  var fraction = (distance - before.distance) / (after.distance - before.distance);
  // Use the length map to recover a parameter, then evaluate the actual
  // curve. Interpolating the sample positions instead creates tiny straight
  // chords and a small direction change at every sample boundary.
  return curvePoint(after.start, after.curve, after.parameter - (1 - fraction) / ARC_SAMPLES);
}
function conductorPoint(beats, beat, progress) {
  var motion = conductingMotion[beats] || conductingMotion[1];
  var index = (beat % motion.beats + motion.beats) % motion.beats;
  var t = Math.min(1, Math.max(0, progress));
  var distance = distanceAtTime(motion, index + t);
  return pointAtDistance(motion, distance);
}
function conductorPreparationPoint(beats, progress) {
  var motion = conductingMotion[beats] || conductingMotion[1];
  var t = Math.min(1, Math.max(0, progress));
  return pointAtDistance(motion, distanceAtTime(motion, motion.beats - 0.5 + t * 0.5));
}
function conductorPreparationBeats() {
  // The reference uses half a beat for the top-to-bottom gesture. Starting
  // playback uses that same gesture, reaching the bottom on the first click.
  return 0.5;
}
function ballTransform(_ref6) {
  var _ref7 = _slicedToArray(_ref6, 2),
    x = _ref7[0],
    y = _ref7[1];
  // The HTML ball is 44 viewBox units wide (11% of the 400-unit stage).
  // Percentage transforms keep it aligned with the SVG at any screen size,
  // without measuring layout when the viewport or device orientation changes.
  return "translate3d(".concat((x / 44 - 0.5) * 100, "%, ").concat((y / 44 - 0.5) * 100, "%, 0)");
}
var motionKeyframes = new Map();
function conductorKeyframes(beats) {
  if (!motionKeyframes.has(beats)) {
    var motion = conductingMotion[beats];
    // Bake the flow into one continuous compositor animation. Sampling
    // in time retains the shared turn velocities without per-beat seeks.
    var steps = 2048;
    motionKeyframes.set(beats, Array.from({
      length: steps + 1
    }, function (_, step) {
      return {
        transform: ballTransform(pointAtDistance(motion, distanceAtTime(motion, step / steps * motion.beats))),
        offset: step / steps
      };
    }));
  }
  return motionKeyframes.get(beats);
}
var ConductorView = /*#__PURE__*/function () {
  function ConductorView(stage, _ref8) {
    var pulseLayer = _ref8.pulseLayer,
      number = _ref8.number;
    _classCallCheck(this, ConductorView);
    this.path = stage.querySelector('[data-conductor-path]');
    this.ball = stage.querySelector('[data-conductor-ball]');
    this.beatNumbers = Array.from(stage.querySelectorAll('[data-conductor-count]'));
    this.pulseLayer = pulseLayer;
    this.number = number;
    this.animation = null;
    this.pulseAnimations = [];
    this.pulseTiming = null;
    this.duration = null;
    this.beats = 1;
    this.setBeats(1);
  }
  return _createClass(ConductorView, [{
    key: "setBeats",
    value: function setBeats(beats) {
      this.stop();
      this.beats = beats;
      var pattern = CONDUCTING_PATTERNS[beats];
      var segments = pattern.flatMap(function (segment) {
        return segment.curves.map(function (curve) {
          return "C ".concat(curve.controls[0].join(' '), " ").concat(curve.controls[1].join(' '), " ").concat(curve.to.join(' '));
        });
      });
      this.path.setAttribute('d', "M ".concat(pattern[0].point.join(' '), " ").concat(segments.join(' ')));
      this.prepare(0);
    }
  }, {
    key: "render",
    value: function render(beat, progress) {
      this.position(conductorPoint(this.beats, beat, progress));
    }
  }, {
    key: "prepare",
    value: function prepare(progress) {
      this.position(conductorPreparationPoint(this.beats, progress));
    }
  }, {
    key: "position",
    value: function position(_ref9) {
      var _ref0 = _slicedToArray(_ref9, 2),
        x = _ref0[0],
        y = _ref0[1];
      this.ball.style.transform = ballTransform([x, y]);
    }
  }, {
    key: "stop",
    value: function stop() {
      if (this.animation) this.animation.cancel();
      this.pulseAnimations.forEach(function (animation) {
        return animation.cancel();
      });
      this.animation = null;
      this.pulseAnimations = [];
      this.pulseTiming = null;
      this.duration = null;
    }
  }, {
    key: "syncPulses",
    value: function syncPulses(duration, preparing) {
      var _this = this;
      var interval = duration / this.beats;
      // Suppress the tail of the imaginary previous bar during the opening
      // drop. The first pulse starts exactly at the ball's first bottom hit.
      var delay = preparing ? duration : 0;
      var changed = !this.pulseTiming || this.pulseTiming.duration !== duration || this.pulseTiming.delay !== delay;
      var numberFrames = [{
        transform: 'scale(1.35)',
        opacity: 1,
        offset: 0,
        easing: 'ease-out'
      }, {
        transform: 'scale(1)',
        opacity: 0.88,
        offset: 180 / interval
      }, {
        transform: 'scale(1)',
        opacity: 0.88,
        offset: 1
      }];
      if (!this.pulseAnimations.length) {
        var backgroundFrames = [{
          opacity: 1,
          offset: 0,
          easing: 'ease-out'
        }, {
          opacity: 0,
          offset: 1 / this.beats
        }];
        if (this.beats > 1) backgroundFrames.push({
          opacity: 0,
          offset: 1
        });
        this.pulseAnimations = [this.pulseLayer.animate(backgroundFrames, {
          duration: duration,
          delay: delay,
          iterations: Infinity
        }), this.number.animate(numberFrames, {
          duration: interval,
          delay: delay,
          iterations: Infinity
        })].concat(_toConsumableArray(this.beatNumbers.slice(0, this.beats).map(function (number, beat) {
          var start = beat / _this.beats;
          var end = (beat + 1) / _this.beats;
          // Each digit owns one beat of the bar, fading completely
          // before the next appears, on the conductor's timeline.
          var frames = start > 0 ? [{
            opacity: 0,
            offset: 0
          }, {
            opacity: 0,
            offset: start
          }] : [];
          frames.push({
            opacity: 0.12,
            offset: start,
            easing: 'ease-out'
          }, {
            opacity: 0,
            offset: end
          });
          if (end < 1) frames.push({
            opacity: 0,
            offset: 1
          });
          return number.animate(frames, {
            duration: duration,
            delay: delay,
            iterations: Infinity
          });
        })));
      } else if (changed) {
        this.pulseAnimations[0].effect.updateTiming({
          duration: duration,
          delay: delay
        });
        this.pulseAnimations[1].effect.setKeyframes(numberFrames);
        this.pulseAnimations[1].effect.updateTiming({
          duration: interval,
          delay: delay
        });
        this.pulseAnimations.slice(2).forEach(function (animation) {
          return animation.effect.updateTiming({
            duration: duration,
            delay: delay
          });
        });
      }
      this.pulseTiming = {
        duration: duration,
        delay: delay
      };
      var _iterator2 = _createForOfIteratorHelper(this.pulseAnimations),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var animation = _step2.value;
          if (this.animation.startTime !== null) {
            // All effects use the exact same document-timeline
            // origin, including after tempo edits and drift corrections.
            // No timer callback is needed to start a downbeat flash.
            if (animation.startTime !== this.animation.startTime) animation.startTime = this.animation.startTime;
          } else {
            // Newly created animations resolve their pending start on the
            // same frame; give them identical elapsed time in the meantime.
            animation.currentTime = this.animation.currentTime;
          }
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
    }
  }, {
    key: "sync",
    value: function sync(position, tempo, animate) {
      var force = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      if (!animate) {
        this.stop();
        if (position.preparing) this.prepare(position.progress);else this.render(position.beat, position.progress);
        return;
      }
      var duration = this.beats * 60000 / tempo;
      var phase = position.preparing ? 1 - conductorPreparationBeats(this.beats) / this.beats * (1 - position.progress) : (position.beat + position.progress) / this.beats;
      var elapsed = phase % 1 * duration;
      var retime = force || this.duration !== duration;
      if (!this.animation) {
        // One browser-managed transform animation continues through beats
        // and bars, independently of JavaScript's 25ms audio scheduler.
        this.animation = this.ball.animate(conductorKeyframes(this.beats), {
          duration: duration,
          iterations: Infinity,
          easing: 'linear'
        });
      } else if (this.duration !== duration) {
        this.animation.effect.updateTiming({
          duration: duration
        });
      }
      this.duration = duration;
      var current = (this.animation.currentTime || 0) % duration;
      var difference = Math.abs(current - elapsed);
      var drift = Math.min(difference, duration - difference);
      // Ignore normal audio-block/frame rounding. Seeking on every click
      // would put the scheduler's jitter back into the otherwise smooth motion.
      // A real stall or a tempo edit still re-aligns with the audio clock.
      if (retime || drift > 80) this.animation.currentTime = elapsed;
      this.syncPulses(duration, position.preparing);
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/gesture.js"
/*!*******************************************!*\
  !*** ./resources/js/metronome/gesture.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DEFAULT_TEMPO: () => (/* binding */ DEFAULT_TEMPO),
/* harmony export */   MAX_TEMPO: () => (/* binding */ MAX_TEMPO),
/* harmony export */   MIN_TEMPO: () => (/* binding */ MIN_TEMPO),
/* harmony export */   TempoGesture: () => (/* binding */ TempoGesture),
/* harmony export */   clampTempo: () => (/* binding */ clampTempo)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
var MIN_TEMPO = 20;
var MAX_TEMPO = 300;
var DEFAULT_TEMPO = 120;
function clampTempo(value) {
  return Math.min(MAX_TEMPO, Math.max(MIN_TEMPO, value));
}
var TempoGesture = /*#__PURE__*/function () {
  function TempoGesture(tempo, x, y, time) {
    _classCallCheck(this, TempoGesture);
    this.tempo = tempo;
    this.startX = x;
    this.startY = y;
    this.lastY = y;
    this.lastTime = time;
    this.moved = false;
  }
  return _createClass(TempoGesture, [{
    key: "move",
    value: function move(x, y, time) {
      if (Math.hypot(x - this.startX, y - this.startY) >= 6) this.moved = true;
      if (!this.moved) return Math.round(this.tempo);
      var distance = this.lastY - y;
      var elapsed = Math.max(1, time - this.lastTime);
      // Slow movement gives fine control; a quick swipe covers a wider range.
      var speed = Math.abs(distance) / elapsed;
      var sensitivity = 0.2 + Math.min(speed, 2) * 0.3;
      this.tempo = clampTempo(this.tempo + distance * sensitivity);
      this.lastY = y;
      this.lastTime = time;
      return Math.round(this.tempo);
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/settings.js"
/*!********************************************!*\
  !*** ./resources/js/metronome/settings.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DEFAULT_SETTINGS: () => (/* binding */ DEFAULT_SETTINGS),
/* harmony export */   SETTINGS_KEY: () => (/* binding */ SETTINGS_KEY),
/* harmony export */   SOUND_PROFILES: () => (/* binding */ SOUND_PROFILES),
/* harmony export */   TEMPO_KEY: () => (/* binding */ TEMPO_KEY),
/* harmony export */   colorPalette: () => (/* binding */ colorPalette),
/* harmony export */   loadSettings: () => (/* binding */ loadSettings),
/* harmony export */   loadTempo: () => (/* binding */ loadTempo),
/* harmony export */   normalizeSettings: () => (/* binding */ normalizeSettings)
/* harmony export */ });
/* harmony import */ var _gesture__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./gesture */ "./resources/js/metronome/gesture.js");
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }

var SOUND_PROFILES = {
  click: {
    type: 'triangle',
    frequency: 1400,
    attack: 0.0005,
    duration: 0.03,
    volume: 0.95,
    ratios: [1]
  },
  woodblock: {
    type: 'sine',
    frequency: 800,
    attack: 0.0005,
    duration: 0.06,
    volume: 0.95,
    ratios: [1, 2.76]
  },
  beep: {
    type: 'sine',
    frequency: 1000,
    attack: 0.002,
    hold: 0.025,
    duration: 0.06,
    volume: 0.85,
    ratios: [1]
  },
  cowbell: {
    type: 'square',
    frequency: 540,
    attack: 0.0005,
    duration: 0.07,
    volume: 0.7,
    ratios: [1, 1.48]
  }
};
var DEFAULT_SETTINGS = {
  beats: 1,
  color: '#0055fe',
  sound: 'click',
  type: 'classic',
  showPath: false,
  conductorMarker: 'dot',
  font: 'nunito'
};
var SETTINGS_KEY = 'metronome.settings.v1';
var TEMPO_KEY = 'metronome.tempo.v1';
function loadTempo(storage) {
  try {
    var value = JSON.parse(storage.getItem(TEMPO_KEY));
    return Number.isInteger(value) && value >= _gesture__WEBPACK_IMPORTED_MODULE_0__.MIN_TEMPO && value <= _gesture__WEBPACK_IMPORTED_MODULE_0__.MAX_TEMPO ? value : _gesture__WEBPACK_IMPORTED_MODULE_0__.DEFAULT_TEMPO;
  } catch (error) {
    return _gesture__WEBPACK_IMPORTED_MODULE_0__.DEFAULT_TEMPO;
  }
}
function normalizeSettings(value) {
  var input = value && _typeof(value) === 'object' ? value : {};
  return {
    beats: [1, 2, 3, 4].includes(Number(input.beats)) ? Number(input.beats) : DEFAULT_SETTINGS.beats,
    color: typeof input.color === 'string' && /^#[0-9a-f]{6}$/i.test(input.color) ? input.color.toLowerCase() : DEFAULT_SETTINGS.color,
    sound: input.sound === 'my-voice' || Object.prototype.hasOwnProperty.call(SOUND_PROFILES, input.sound) ? input.sound : DEFAULT_SETTINGS.sound,
    type: input.type === 'conductor' ? 'conductor' : 'classic',
    showPath: typeof input.showPath === 'boolean' ? input.showPath : DEFAULT_SETTINGS.showPath,
    conductorMarker: input.conductorMarker === 'hand' ? 'hand' : 'dot',
    font: ['nunito', 'outfit', 'playfair', 'pattaya', 'sail'].includes(input.font) ? input.font : DEFAULT_SETTINGS.font
  };
}
function loadSettings(storage) {
  try {
    return normalizeSettings(JSON.parse(storage.getItem(SETTINGS_KEY)));
  } catch (error) {
    return _objectSpread({}, DEFAULT_SETTINGS);
  }
}
function luminance(channels) {
  var linear = channels.map(function (channel) {
    var value = channel / 255;
    return value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
  });
  return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
}
function hexColor(channels) {
  return "#".concat(channels.map(function (channel) {
    return channel.toString(16).padStart(2, '0');
  }).join(''));
}
function mixColor(from, to, amount) {
  return from.map(function (channel, index) {
    return Math.round(channel + (to[index] - channel) * amount);
  });
}
function colorPalette(color) {
  var channels = color.slice(1).match(/../g).map(function (channel) {
    return parseInt(channel, 16);
  });
  return {
    pulse: hexColor(mixColor(channels, [0, 0, 0], 0.42)),
    ink: luminance(channels) > 0.4 ? '#112037' : '#ffffff',
    headerInk: luminance(channels) > 0.179 ? '#000000' : '#ffffff'
  };
}

/***/ },

/***/ "./resources/js/metronome/sound-menu.js"
/*!**********************************************!*\
  !*** ./resources/js/metronome/sound-menu.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SoundMenu: () => (/* binding */ SoundMenu)
/* harmony export */ });
/* harmony import */ var bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap/js/dist/dropdown */ "./node_modules/bootstrap/js/dist/dropdown.js");
/* harmony import */ var bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_0__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var SoundMenu = /*#__PURE__*/function () {
  function SoundMenu(_ref) {
    var _this$list,
      _this = this;
    var root = _ref.root,
      input = _ref.input,
      onSelect = _ref.onSelect,
      onPlay = _ref.onPlay,
      onEdit = _ref.onEdit,
      onDelete = _ref.onDelete,
      onRecord = _ref.onRecord,
      onClose = _ref.onClose;
    _classCallCheck(this, SoundMenu);
    Object.assign(this, {
      root: root,
      input: input,
      onSelect: onSelect,
      onPlay: onPlay,
      onEdit: onEdit,
      onDelete: onDelete,
      onRecord: onRecord,
      onClose: onClose
    });
    this.trigger = root.querySelector('[data-sound-toggle]');
    this.label = root.querySelector('[data-sound-label]');
    this.list = root.querySelector('[data-sound-voices]');
    this.template = root.querySelector('[data-sound-template]');
    this.empty = root.querySelector('[data-sound-empty]');
    this.rows = new Map();
    (_this$list = this.list) === null || _this$list === void 0 || _this$list.querySelectorAll('[data-sound-row]').forEach(function (row) {
      return _this.rows.set(row.dataset.voiceId, row);
    });
    this.dropdown = new (bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_0___default())(this.trigger, {
      autoClose: 'outside'
    });
    this.trigger.addEventListener('shown.bs.dropdown', function () {
      return _this.size();
    });
    window.addEventListener('resize', function () {
      if (_this.trigger.getAttribute('aria-expanded') === 'true') _this.size();
    });
    this.trigger.addEventListener('hidden.bs.dropdown', function () {
      return _this.onClose();
    });
    root.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || _this.trigger.getAttribute('aria-expanded') !== 'true') return;
      event.preventDefault();
      event.stopPropagation();
      _this.close();
      _this.trigger.focus();
    });
    root.addEventListener('click', function (event) {
      if (event.target.closest('[data-voice-start]')) _this.close();
    }, true);
    root.addEventListener('click', function (event) {
      var _button$closest;
      var button = event.target.closest('button');
      if (!button || button.disabled) return;
      if (button.matches('[data-sound-empty]')) {
        _this.close();
        _this.onRecord();
        return;
      }
      if (button.dataset.soundValue) {
        _this.close();
        _this.onSelect(button.dataset.soundValue);
        _this.trigger.focus();
        return;
      }
      var id = (_button$closest = button.closest('[data-sound-row]')) === null || _button$closest === void 0 ? void 0 : _button$closest.dataset.voiceId;
      if (!id) return;
      if (button.matches('[data-sound-play]')) _this.onPlay(id);
      if (button.matches('[data-sound-edit]')) {
        _this.close();
        _this.onEdit(id);
      }
      if (button.matches('[data-sound-delete]')) {
        _this.close();
        _this.trigger.focus();
        _this.onDelete(id);
      }
    });
  }
  return _createClass(SoundMenu, [{
    key: "close",
    value: function close() {
      this.dropdown.hide();
    }
  }, {
    key: "size",
    value: function size() {
      var _this$trigger$closest, _bounds$bottom, _bounds$top;
      var options = this.root.querySelector('[data-sound-options]');
      var trigger = this.trigger.getBoundingClientRect();
      var bounds = (_this$trigger$closest = this.trigger.closest('[data-settings-scroll], .offcanvas')) === null || _this$trigger$closest === void 0 ? void 0 : _this$trigger$closest.getBoundingClientRect();
      var below = Math.min(window.innerHeight, (_bounds$bottom = bounds === null || bounds === void 0 ? void 0 : bounds.bottom) !== null && _bounds$bottom !== void 0 ? _bounds$bottom : window.innerHeight) - trigger.bottom - 12;
      var above = trigger.top - Math.max(0, (_bounds$top = bounds === null || bounds === void 0 ? void 0 : bounds.top) !== null && _bounds$top !== void 0 ? _bounds$top : 0) - 12;
      options.style.maxHeight = "".concat(Math.max(80, Math.min(256, Math.max(above, below) - 16)), "px");
      this.dropdown.update();
    }
  }, {
    key: "render",
    value: function render(recorder) {
      var _options$find,
        _this2 = this;
      var options = _toConsumableArray(this.input.options);
      this.label.textContent = ((_options$find = options.find(function (option) {
        return option.value === _this2.input.value;
      })) === null || _options$find === void 0 ? void 0 : _options$find.text) || 'Classic click';
      var voices = options.filter(function (option) {
        return option.value.startsWith('voice:');
      });
      if (this.list) {
        var ids = new Set(voices.map(function (option) {
          return option.value.slice(6);
        }));
        this.rows.forEach(function (row, id) {
          if (!ids.has(id)) {
            row.remove();
            _this2.rows["delete"](id);
          }
        });
        voices.forEach(function (option) {
          var id = option.value.slice(6);
          var row = _this2.rows.get(id);
          if (!row) {
            row = _this2.template.content.firstElementChild.cloneNode(true);
            row.dataset.voiceId = id;
            _this2.rows.set(id, row);
            _this2.list.append(row);
          }
          var choice = row.querySelector('[data-sound-choice]');
          choice.textContent = option.text;
          choice.dataset.soundValue = option.value;
          row.querySelectorAll('button').forEach(function (button) {
            button.disabled = !(recorder !== null && recorder !== void 0 && recorder.ready) || !!(recorder !== null && recorder !== void 0 && recorder.saving);
          });
          var playing = (recorder === null || recorder === void 0 ? void 0 : recorder.previewingId) === id;
          var play = row.querySelector('[data-sound-play]');
          play.setAttribute('aria-label', "".concat(playing ? 'Stop' : 'Play', " ").concat(option.text));
          play.setAttribute('aria-pressed', String(playing));
          play.title = playing ? 'Stop preview' : 'Play recording';
          row.querySelector('[data-sound-play-icon]').hidden = playing;
          row.querySelector('[data-sound-stop-icon]').hidden = !playing;
          row.querySelector('[data-sound-edit]').setAttribute('aria-label', "Rename ".concat(option.text));
          row.querySelector('[data-sound-delete]').setAttribute('aria-label', "Delete ".concat(option.text));
        });
        this.empty.hidden = !!voices.length || !(recorder !== null && recorder !== void 0 && recorder.ready);
        this.empty.disabled = !(recorder !== null && recorder !== void 0 && recorder.ready) || !(recorder !== null && recorder !== void 0 && recorder.supported) || !!(recorder !== null && recorder !== void 0 && recorder.saving);
      }
      this.root.querySelectorAll('[data-sound-value]').forEach(function (button) {
        var selected = button.dataset.soundValue === _this2.input.value;
        button.classList.toggle('active', selected);
        button.setAttribute('aria-pressed', String(selected));
      });
      this.dropdown.update();
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/voice-audio.js"
/*!***********************************************!*\
  !*** ./resources/js/metronome/voice-audio.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VOICE_RATE: () => (/* binding */ VOICE_RATE),
/* harmony export */   VoiceCapture: () => (/* binding */ VoiceCapture),
/* harmony export */   encodeVoiceWav: () => (/* binding */ encodeVoiceWav),
/* harmony export */   prepareVoiceClip: () => (/* binding */ prepareVoiceClip),
/* harmony export */   voiceBase64: () => (/* binding */ voiceBase64),
/* harmony export */   voiceBytes: () => (/* binding */ voiceBytes)
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
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
var VOICE_RATE = 24000;
var MIN_SPEECH_LEVEL = 0.001;
var rms = function rms(samples) {
  return Math.sqrt(samples.reduce(function (sum, value) {
    return sum + value * value;
  }, 0) / samples.length);
};
var noiseFloor = function noiseFloor(levels) {
  var fraction = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0.2;
  return levels.length ? _toConsumableArray(levels).sort(function (a, b) {
    return a - b;
  })[Math.floor(levels.length * fraction)] : 0;
};
function prepareVoiceClip(decoded) {
  var rate = decoded.sampleRate;
  var samples = new Float32Array(decoded.length);
  for (var channel = 0; channel < decoded.numberOfChannels; channel++) {
    var data = decoded.getChannelData(channel);
    for (var i = 0; i < samples.length; i++) samples[i] += data[i] / decoded.numberOfChannels;
  }
  var windowSize = Math.max(1, Math.round(rate * 0.01));
  var levels = [];
  for (var _start = 0; _start < samples.length; _start += windowSize) {
    var sum = 0;
    var _end = Math.min(samples.length, _start + windowSize);
    for (var _i = _start; _i < _end; _i++) sum += Math.pow(samples[_i], 2);
    levels.push(Math.sqrt(sum / (_end - _start)));
  }
  var peakLevel = Math.max.apply(Math, [0].concat(levels));
  // Quiet microphones still count; steady room noise does not become a word.
  var threshold = Math.max(MIN_SPEECH_LEVEL, noiseFloor(levels, 0.1) * 2.5, peakLevel * 0.12);
  var first = levels.findIndex(function (level) {
    return level >= threshold;
  });
  var last = levels.length - 1;
  while (last >= 0 && levels[last] < threshold) last--;
  if (first < 0 || (last - first + 1) * windowSize / rate < 0.06) {
    throw new Error('I couldn’t hear that number. Try again.');
  }
  var start = Math.max(0, first * windowSize - Math.round(rate * 0.02));
  var end = Math.min(samples.length, (last + 1) * windowSize + Math.round(rate * 0.04));
  if ((end - start) / rate > 2) throw new Error('A little shorter—say just the number.');
  var output = new Float32Array(Math.max(1440, Math.ceil((end - start) / rate * VOICE_RATE)));
  var peak = 0;
  for (var _i2 = start; _i2 < end; _i2++) peak = Math.max(peak, Math.abs(samples[_i2]));
  var gain = 0.85 / Math.max(peak, MIN_SPEECH_LEVEL);
  for (var _i3 = 0; _i3 < output.length; _i3++) {
    var at = start + _i3 * rate / VOICE_RATE;
    var left = Math.floor(at);
    var fraction = at - left;
    var value = (samples[left] || 0) * (1 - fraction) + (samples[left + 1] || 0) * fraction;
    var fade = Math.min(1, _i3 / 120, (output.length - 1 - _i3) / 120);
    output[_i3] = Math.max(-1, Math.min(1, value * gain)) * fade;
  }
  return encodeVoiceWav(output);
}
function encodeVoiceWav(samples) {
  var bytes = new ArrayBuffer(44 + samples.length * 2);
  var view = new DataView(bytes);
  var text = function text(offset, value) {
    return _toConsumableArray(value).forEach(function (letter, i) {
      return view.setUint8(offset + i, letter.charCodeAt(0));
    });
  };
  text(0, 'RIFF');
  view.setUint32(4, bytes.byteLength - 8, true);
  text(8, 'WAVE');
  text(12, 'fmt ');
  view.setUint32(16, 16, true);
  view.setUint16(20, 1, true);
  view.setUint16(22, 1, true);
  view.setUint32(24, VOICE_RATE, true);
  view.setUint32(28, VOICE_RATE * 2, true);
  view.setUint16(32, 2, true);
  view.setUint16(34, 16, true);
  text(36, 'data');
  view.setUint32(40, samples.length * 2, true);
  samples.forEach(function (value, i) {
    return view.setInt16(44 + i * 2, Math.round(Math.max(-1, Math.min(1, value)) * 32767), true);
  });
  return bytes;
}
function voiceBase64(bytes) {
  var text = '';
  var _iterator = _createForOfIteratorHelper(new Uint8Array(bytes)),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var _byte = _step.value;
      text += String.fromCharCode(_byte);
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  return btoa(text);
}
function voiceBytes(encoded) {
  return Uint8Array.from(atob(encoded), function (letter) {
    return letter.charCodeAt(0);
  }).buffer;
}
var VoiceCapture = /*#__PURE__*/function () {
  function VoiceCapture(_ref) {
    var mediaDevices = _ref.mediaDevices,
      Recorder = _ref.Recorder,
      createContext = _ref.createContext,
      _ref$now = _ref.now,
      now = _ref$now === void 0 ? function () {
        return performance.now();
      } : _ref$now;
    _classCallCheck(this, VoiceCapture);
    Object.assign(this, {
      mediaDevices: mediaDevices,
      Recorder: Recorder,
      createContext: createContext,
      now: now
    });
    this.generation = 0;
  }
  return _createClass(VoiceCapture, [{
    key: "open",
    value: function () {
      var _open = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this = this;
        var closing, generation, stream, samples;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              closing = this.cancel();
              generation = this.generation;
              _context.n = 1;
              return closing;
            case 1:
              if (!(generation !== this.generation)) {
                _context.n = 2;
                break;
              }
              throw new DOMException('Canceled', 'AbortError');
            case 2:
              this.context = this.createContext();
              _context.n = 3;
              return this.context.resume();
            case 3:
              if (!(generation !== this.generation)) {
                _context.n = 4;
                break;
              }
              throw new DOMException('Canceled', 'AbortError');
            case 4:
              _context.n = 5;
              return this.mediaDevices.getUserMedia({
                audio: {
                  echoCancellation: true,
                  noiseSuppression: true,
                  autoGainControl: true,
                  channelCount: 1
                },
                video: false
              });
            case 5:
              stream = _context.v;
              if (!(generation !== this.generation)) {
                _context.n = 6;
                break;
              }
              stream.getTracks().forEach(function (track) {
                return track.stop();
              });
              throw new DOMException('Canceled', 'AbortError');
            case 6:
              this.stream = stream;
              this.source = this.context.createMediaStreamSource(stream);
              this.analyser = this.context.createAnalyser();
              this.analyser.fftSize = 2048;
              this.source.connect(this.analyser);
              // Use the existing Get ready pause to learn this microphone's room level.
              this.noiseLevels = [];
              this.noiseFloor = 0;
              samples = new Float32Array(this.analyser.fftSize);
              this.calibrationTimer = setInterval(function () {
                _this.analyser.getFloatTimeDomainData(samples);
                _this.noiseLevels.push(rms(samples));
              }, 50);
            case 7:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function open() {
        return _open.apply(this, arguments);
      }
      return open;
    }()
  }, {
    key: "record",
    value: function record(onLevel) {
      var _this2 = this;
      clearInterval(this.calibrationTimer);
      if (this.noiseLevels.length) this.noiseFloor = noiseFloor(this.noiseLevels);
      var speechThreshold = Math.max(MIN_SPEECH_LEVEL, this.noiseFloor * 2.5);
      var generation = this.generation;
      var context = this.context;
      var mimeType = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus'].find(function (type) {
        return _this2.Recorder.isTypeSupported(type);
      });
      var recorder = new this.Recorder(this.stream, mimeType ? {
        mimeType: mimeType
      } : {});
      this.recorder = recorder;
      var chunks = [];
      var started = this.now();
      var speech = 0;
      var lastSpeech = started;
      var timer;
      var samples = new Float32Array(this.analyser.fftSize);
      return new Promise(function (resolve, reject) {
        var finish = function finish() {
          if (recorder.state !== 'inactive') recorder.stop();
        };
        recorder.ondataavailable = function (event) {
          if (event.data.size) chunks.push(event.data);
        };
        recorder.onerror = function () {
          clearInterval(timer);
          reject(new Error('Recording stopped. Please try again.'));
        };
        recorder.onstop = /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
          var blob, decoded, _t, _t2;
          return _regenerator().w(function (_context2) {
            while (1) switch (_context2.p = _context2.n) {
              case 0:
                clearInterval(timer);
                if (!(generation !== _this2.generation)) {
                  _context2.n = 1;
                  break;
                }
                return _context2.a(2, reject(new DOMException('Canceled', 'AbortError')));
              case 1:
                _context2.p = 1;
                blob = new Blob(chunks, {
                  type: recorder.mimeType
                });
                _t = context;
                _context2.n = 2;
                return blob.arrayBuffer();
              case 2:
                _context2.n = 3;
                return _t.decodeAudioData.call(_t, _context2.v);
              case 3:
                decoded = _context2.v;
                if (!(generation !== _this2.generation)) {
                  _context2.n = 4;
                  break;
                }
                throw new DOMException('Canceled', 'AbortError');
              case 4:
                resolve(prepareVoiceClip(decoded));
                _context2.n = 6;
                break;
              case 5:
                _context2.p = 5;
                _t2 = _context2.v;
                reject(_t2);
              case 6:
                return _context2.a(2);
            }
          }, _callee2, null, [[1, 5]]);
        }));
        recorder.start();
        _this2.timer = timer = setInterval(function () {
          if (generation !== _this2.generation) return finish();
          _this2.analyser.getFloatTimeDomainData(samples);
          var level = rms(samples);
          onLevel(Math.min(100, Math.round(Math.sqrt(Math.max(0, level - _this2.noiseFloor)) * 500)));
          var now = _this2.now();
          if (level >= speechThreshold) {
            speech += 50;
            lastSpeech = now;
          }
          if (speech >= 100 && now - lastSpeech >= 450 && now - started >= 600 || now - started >= 3500) finish();
        }, 50);
      });
    }
  }, {
    key: "cancel",
    value: function cancel() {
      var _this$recorder, _this$stream, _this$source, _this$analyser;
      this.generation++;
      clearInterval(this.timer);
      clearInterval(this.calibrationTimer);
      if (((_this$recorder = this.recorder) === null || _this$recorder === void 0 ? void 0 : _this$recorder.state) === 'recording') this.recorder.stop();
      this.recorder = null;
      (_this$stream = this.stream) === null || _this$stream === void 0 || _this$stream.getTracks().forEach(function (track) {
        return track.stop();
      });
      this.stream = null;
      (_this$source = this.source) === null || _this$source === void 0 || _this$source.disconnect();
      (_this$analyser = this.analyser) === null || _this$analyser === void 0 || _this$analyser.disconnect();
      this.source = this.analyser = null;
      if (this.context && this.context.state !== 'closed') this.closing = this.context.close()["catch"](function () {});
      this.context = null;
      return this.closing;
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/voice-recorder.js"
/*!**************************************************!*\
  !*** ./resources/js/metronome/voice-recorder.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VoiceRecorder: () => (/* binding */ VoiceRecorder)
/* harmony export */ });
/* harmony import */ var _voice_audio__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./voice-audio */ "./resources/js/metronome/voice-audio.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }

var WORDS = ['one', 'two', 'three', 'four'];
var VoiceRecorder = /*#__PURE__*/function () {
  function VoiceRecorder(_ref) {
    var _navigator$mediaDevic,
      _this = this;
    var root = _ref.root,
      dialog = _ref.dialog,
      clock = _ref.clock,
      soundInput = _ref.soundInput,
      getBeats = _ref.getBeats,
      useSound = _ref.useSound,
      _ref$onUpdate = _ref.onUpdate,
      onUpdate = _ref$onUpdate === void 0 ? function () {} : _ref$onUpdate;
    _classCallCheck(this, VoiceRecorder);
    Object.assign(this, {
      root: root,
      dialog: dialog,
      clock: clock,
      soundInput: soundInput,
      getBeats: getBeats,
      useSound: useSound,
      onUpdate: onUpdate
    });
    this.ui = function (name) {
      return root.querySelector("[data-voice-".concat(name, "]"));
    };
    this.emptyOption = soundInput.querySelector('[value="my-voice"]');
    this.emptyOption.disabled = true;
    this.emptyOption.hidden = true;
    this.voiceGroup = document.createElement('optgroup');
    this.voiceGroup.label = 'Recorded voices';
    this.sound = soundInput.value;
    this.flow = 0;
    this.selection = 0;
    this.loadVersion = 0;
    this.previewSources = [];
    this.saved = [];
    this.library = [];
    this.limit = 20;
    this.activeId = null;
    this.voiceVersion = 0;
    this.buffers = [];
    this.draft = [];
    this.revision = null;
    this.ready = false;
    this.saving = false;
    this.storageKey = "metronome.voice.".concat(root.dataset.owner, ".v1");
    this.selectedKey = "metronome.voice.".concat(root.dataset.owner, ".selected.v1");
    this.preferred = false;
    try {
      this.preferred = localStorage.getItem(this.storageKey) === '1';
      this.preferredId = localStorage.getItem(this.selectedKey);
    } catch (error) {/* Optional preference. */}
    this.nameInput = root.querySelector('[name="metronome_voice_name"]');
    this.renameInput = root.querySelector('[name="metronome_voice_rename"]');
    var AudioContext = window.AudioContext || window.webkitAudioContext;
    this.supported = !!((_navigator$mediaDevic = navigator.mediaDevices) !== null && _navigator$mediaDevic !== void 0 && _navigator$mediaDevic.getUserMedia && window.MediaRecorder && AudioContext);
    this.capture = new _voice_audio__WEBPACK_IMPORTED_MODULE_0__.VoiceCapture({
      mediaDevices: navigator.mediaDevices,
      Recorder: window.MediaRecorder,
      createContext: function createContext() {
        return new AudioContext({
          latencyHint: 'interactive'
        });
      }
    });
    this.ui('start').addEventListener('click', function () {
      return _this.record();
    });
    this.ui('retry').addEventListener('click', function () {
      return _this.record({
        resume: true
      });
    });
    this.ui('cancel').addEventListener('click', function () {
      return _this.cancel();
    });
    this.ui('save').addEventListener('click', function () {
      return _this.save();
    });
    this.ui('preview-all').addEventListener('click', function () {
      return _this.preview(_this.draftBuffers);
    });
    this.ui('redo').addEventListener('click', function () {
      return _this.record({
        redo: _this.selected || 0
      });
    });
    root.querySelectorAll('[data-voice-preview]').forEach(function (button) {
      return button.addEventListener('click', function () {
        _this.selected = Number(button.dataset.voicePreview);
        _this.ui('redo').textContent = "Redo ".concat(_this.selected + 1);
        _this.preview([_this.draftBuffers[_this.selected]]);
      });
    });
    this.ui('rename-cancel').addEventListener('click', function () {
      _this.ui('rename-panel').hidden = true;
    });
    this.ui('rename-save').addEventListener('click', function () {
      return _this.rename();
    });
    this.renameInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        _this.rename();
      }
    });
    this.nameInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        _this.save();
      }
    });
    this.ui('reload').addEventListener('click', function () {
      _this.cancel();
      _this.load();
    });
    this.load();
  }
  return _createClass(VoiceRecorder, [{
    key: "context",
    value: function context() {
      return this.clock.getContext();
    }
  }, {
    key: "decodeVoice",
    value: function decodeVoice(bytes) {
      // Loading saved counts should not activate a second output device or
      // carry a microphone-era playback context into the next practice run.
      var OfflineContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
      if (!OfflineContext) return this.context().decodeAudioData(bytes);
      if (!this.decoder) this.decoder = new OfflineContext(1, 1, _voice_audio__WEBPACK_IMPORTED_MODULE_0__.VOICE_RATE);
      return this.decoder.decodeAudioData(bytes);
    }
  }, {
    key: "remember",
    value: function remember(sound) {
      this.selection++;
      this.sound = sound;
      this.preferred = sound === 'my-voice';
      try {
        localStorage.setItem(this.storageKey, this.preferred ? '1' : '0');
        if (this.activeId) localStorage.setItem(this.selectedKey, this.activeId);
      } catch (error) {/* Optional preference. */}
      this.controls();
    }
  }, {
    key: "voiceValue",
    value: function voiceValue(id) {
      return "voice:".concat(id);
    }
  }, {
    key: "syncSound",
    value: function syncSound(sound) {
      this.sound = sound;
      this.soundInput.value = sound === 'my-voice' && this.activeId ? this.voiceValue(this.activeId) : sound;
      if (sound !== 'my-voice') this.ui('rename-panel').hidden = true;
      this.controls();
    }
  }, {
    key: "selectSound",
    value: function selectSound(value) {
      var _this2 = this;
      if (value === 'my-voice' && !this.library.length) {
        this.syncSound(this.sound);
        this.record();
        return true;
      }
      var recording = this.library.find(function (voice) {
        return _this2.voiceValue(voice.id) === value;
      });
      if (!recording) return false;
      this.selection++;
      this.choose(recording.id);
      return true;
    }
  }, {
    key: "soundOptions",
    value: function soundOptions() {
      var _this3 = this;
      if (this.library.length) {
        var _this$voiceGroup;
        this.emptyOption.remove();
        (_this$voiceGroup = this.voiceGroup).replaceChildren.apply(_this$voiceGroup, _toConsumableArray(this.library.map(function (voice) {
          return new Option(voice.name, _this3.voiceValue(voice.id));
        })));
        if (this.voiceGroup.parentNode !== this.soundInput) this.soundInput.append(this.voiceGroup);
      } else {
        this.voiceGroup.remove();
        if (this.emptyOption.parentNode !== this.soundInput) this.soundInput.append(this.emptyOption);
      }
      this.syncSound(this.sound);
    }
  }, {
    key: "request",
    value: function () {
      var _request = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var method,
          body,
          id,
          response,
          data,
          error,
          _args = arguments;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              method = _args.length > 0 && _args[0] !== undefined ? _args[0] : 'GET';
              body = _args.length > 1 ? _args[1] : undefined;
              id = _args.length > 2 && _args[2] !== undefined ? _args[2] : null;
              _context.n = 1;
              return fetch(this.root.dataset.url.replace(/\/$/, '') + (id ? '/' + encodeURIComponent(id) : ''), _objectSpread({
                method: method,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                  Accept: 'application/json',
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': this.root.dataset.csrf
                }
              }, body ? {
                body: JSON.stringify(body)
              } : {}));
            case 1:
              response = _context.v;
              if (response.ok) {
                _context.n = 3;
                break;
              }
              _context.n = 2;
              return response.json()["catch"](function () {
                return {};
              });
            case 2:
              data = _context.v;
              error = new Error(response.status === 409 ? 'Your recording changed in another tab. Reload it to continue.' : [401, 419].includes(response.status) ? 'Please sign in again to use your recording.' : response.status === 404 ? 'This recording was deleted. Reload your voices to continue.' : response.status === 422 ? Object.values(data.errors || {}).flat()[0] || 'Check the recording name and try again.' : response.status === 429 ? 'Wait a moment, then try again.' : 'Couldn’t save your recording. Please try again.');
              error.status = response.status;
              throw error;
            case 3:
              return _context.a(2, response.json());
          }
        }, _callee, this);
      }));
      function request() {
        return _request.apply(this, arguments);
      }
      return request;
    }()
  }, {
    key: "load",
    value: function () {
      var _load = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var _this4 = this;
        var version, selection, _this$library$, data, id, _t;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.p = _context2.n) {
            case 0:
              version = ++this.loadVersion;
              this.voiceVersion++;
              this.loadingVoice = false;
              selection = this.selection;
              this.ready = false;
              this.controls();
              this.ui('reload').hidden = true;
              this.ui('signin').hidden = true;
              this.ui('status').textContent = 'Loading your voices…';
              _context2.p = 1;
              _context2.n = 2;
              return this.request();
            case 2:
              data = _context2.v;
              if (!(version !== this.loadVersion)) {
                _context2.n = 3;
                break;
              }
              return _context2.a(2);
            case 3:
              this.library = data.recordings;
              this.limit = data.limit;
              this.ready = true;
              this.summary();
              id = [this.activeId, this.preferredId].find(function (value) {
                return _this4.library.some(function (voice) {
                  return voice.id === value;
                });
              }) || ((_this$library$ = this.library[0]) === null || _this$library$ === void 0 ? void 0 : _this$library$.id);
              if (!id) {
                _context2.n = 5;
                break;
              }
              _context2.n = 4;
              return this.choose(id, {
                activate: this.preferred && selection === this.selection
              });
            case 4:
              _context2.n = 6;
              break;
            case 5:
              this.clearVoice();
              this.summary();
            case 6:
              _context2.n = 9;
              break;
            case 7:
              _context2.p = 7;
              _t = _context2.v;
              if (!(version !== this.loadVersion)) {
                _context2.n = 8;
                break;
              }
              return _context2.a(2);
            case 8:
              this.ui('status').textContent = [401, 419].includes(_t.status) ? _t.message : 'Your voices couldn’t load. Try again.';
              this.ui('reload').hidden = false;
              this.invalidateSession(_t);
            case 9:
              return _context2.a(2);
          }
        }, _callee2, this, [[1, 7]]);
      }));
      function load() {
        return _load.apply(this, arguments);
      }
      return load;
    }()
  }, {
    key: "summary",
    value: function summary() {
      this.soundOptions();
      this.controls();
      this.ui('status').textContent = this.library.length >= this.limit ? 'You have 20 saved voices. Delete one to make room.' : this.supported ? '' : window.isSecureContext ? 'This browser can’t record audio. Try another browser.' : 'Open this page over HTTPS to record.';
    }
  }, {
    key: "current",
    value: function current() {
      var _this5 = this;
      return this.library.find(function (voice) {
        return voice.id === _this5.activeId;
      });
    }
  }, {
    key: "controls",
    value: function controls() {
      this.ui('start').disabled = !this.ready || !this.supported || this.saving || this.loadingVoice || this.library.length >= this.limit;
      this.voiceGroup.disabled = !this.ready || this.saving;
      this.emptyOption.disabled = !this.ready || !this.supported || this.saving;
      this.emptyOption.hidden = !this.ready || !!this.library.length;
      this.onUpdate(this);
    }
  }, {
    key: "edit",
    value: function edit(id) {
      var voice = this.library.find(function (voice) {
        return voice.id === id;
      });
      if (!voice || this.saving) return;
      this.renameId = id;
      this.renameInput.value = voice.name;
      this.ui('rename-panel').hidden = false;
      this.renameInput.focus();
    }
  }, {
    key: "confirmDelete",
    value: function confirmDelete(id) {
      var voice = this.library.find(function (voice) {
        return voice.id === id;
      });
      if (!voice || this.saving) return;
      if (!window.confirm("Delete \u201C".concat(voice.name, "\u201D?"))) return;
      this.ui('rename-panel').hidden = true;
      return this.remove(id);
    }
  }, {
    key: "clearVoice",
    value: function clearVoice() {
      this.activeId = this.revision = null;
      this.saved = this.buffers = [];
      this.clock.setVoiceBuffers(null);
      if (this.sound === 'my-voice') this.useSound('click');
    }
  }, {
    key: "choose",
    value: function () {
      var _choose = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(id) {
        var _this6 = this;
        var _ref2,
          _ref2$activate,
          activate,
          version,
          selection,
          data,
          buffers,
          _args3 = arguments,
          _t2;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              _ref2 = _args3.length > 1 && _args3[1] !== undefined ? _args3[1] : {}, _ref2$activate = _ref2.activate, activate = _ref2$activate === void 0 ? true : _ref2$activate;
              if (this.library.some(function (voice) {
                return voice.id === id;
              })) {
                _context3.n = 1;
                break;
              }
              return _context3.a(2);
            case 1:
              version = ++this.voiceVersion;
              selection = this.selection;
              this.stopPreview();
              this.clock.stop();
              this.activeId = id;
              this.loadingVoice = true;
              this.buffers = this.saved = [];
              this.clock.setVoiceBuffers(null);
              this.ui('rename-panel').hidden = true;
              this.controls();
              this.ui('status').textContent = 'Loading recording…';
              _context3.p = 2;
              _context3.n = 3;
              return this.request('GET', null, id);
            case 3:
              data = _context3.v;
              _context3.n = 4;
              return Promise.all(data.clips.map(function (clip) {
                return _this6.decodeVoice((0,_voice_audio__WEBPACK_IMPORTED_MODULE_0__.voiceBytes)(clip));
              }));
            case 4:
              buffers = _context3.v;
              if (!(version !== this.voiceVersion)) {
                _context3.n = 5;
                break;
              }
              return _context3.a(2);
            case 5:
              this.library = this.library.map(function (voice) {
                return voice.id === id ? {
                  id: id,
                  name: data.name,
                  revision: data.revision
                } : voice;
              });
              this.revision = data.revision;
              this.saved = data.clips;
              this.buffers = buffers;
              this.clock.setVoiceBuffers(buffers);
              this.preferredId = id;
              try {
                localStorage.setItem(this.selectedKey, id);
              } catch (error) {/* Optional preference. */}
              if (activate && selection === this.selection) {
                this.useSound('my-voice', false);
                this.remember('my-voice');
              }
              this.summary();
              _context3.n = 8;
              break;
            case 6:
              _context3.p = 6;
              _t2 = _context3.v;
              if (!(version !== this.voiceVersion)) {
                _context3.n = 7;
                break;
              }
              return _context3.a(2);
            case 7:
              if (this.sound === 'my-voice') this.useSound('click');
              this.failure(_t2);
              this.ui('reload').hidden = false;
            case 8:
              _context3.p = 8;
              if (version === this.voiceVersion) {
                this.loadingVoice = false;
                this.controls();
              }
              return _context3.f(8);
            case 9:
              return _context3.a(2);
          }
        }, _callee3, this, [[2, 6, 8, 9]]);
      }));
      function choose(_x) {
        return _choose.apply(this, arguments);
      }
      return choose;
    }()
  }, {
    key: "delay",
    value: function () {
      var _delay = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(milliseconds, flow) {
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              _context4.n = 1;
              return new Promise(function (resolve) {
                return setTimeout(resolve, milliseconds);
              });
            case 1:
              if (!(flow !== this.flow)) {
                _context4.n = 2;
                break;
              }
              throw new DOMException('Canceled', 'AbortError');
            case 2:
              return _context4.a(2);
          }
        }, _callee4, this);
      }));
      function delay(_x2, _x3) {
        return _delay.apply(this, arguments);
      }
      return delay;
    }()
  }, {
    key: "cue",
    value: function cue(state, prompt) {
      var countdown = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
      this.ui('cue').dataset.state = state;
      this.ui('cue-label').textContent = {
        waiting: 'Wait',
        speaking: 'Speak now',
        success: 'Recorded',
        error: 'Try again'
      }[state] || '';
      if (this.ui('prompt').textContent !== prompt) this.ui('prompt').textContent = prompt;
      this.ui('countdown').textContent = countdown;
      this.ui('countdown').hidden = !countdown;
      this.ui('visual').hidden = ['idle', 'review'].includes(state);
      this.ui('meter').hidden = state !== 'speaking';
    }
  }, {
    key: "record",
    value: function () {
      var _record = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5() {
        var _this7 = this;
        var _ref3,
          _ref3$resume,
          resume,
          _ref3$redo,
          redo,
          flow,
          index,
          remaining,
          draftBuffers,
          _args5 = arguments,
          _t3;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.p = _context5.n) {
            case 0:
              _ref3 = _args5.length > 0 && _args5[0] !== undefined ? _args5[0] : {}, _ref3$resume = _ref3.resume, resume = _ref3$resume === void 0 ? false : _ref3$resume, _ref3$redo = _ref3.redo, redo = _ref3$redo === void 0 ? null : _ref3$redo;
              if (!(!this.ready || !this.supported || this.saving || this.loadingVoice || this.library.length >= this.limit)) {
                _context5.n = 1;
                break;
              }
              return _context5.a(2);
            case 1:
              flow = ++this.flow;
              this.stopPreview();
              this.clock.stop();
              if (!resume) {
                if (redo === null) {
                  this.draft = [];
                  this.nameInput.value = "Voice ".concat(this.library.length + 1);
                }
                this.nextIndex = redo === null ? 0 : redo;
                this.endIndex = redo === null ? 4 : redo + 1;
              }
              this.dialog.dataset.recording = 'true';
              this.ui('summary').hidden = true;
              this.ui('panel').hidden = false;
              this.ui('rename-panel').hidden = true;
              this.ui('review').hidden = true;
              this.ui('save').hidden = this.ui('retry').hidden = true;
              this.ui('number').textContent = String(this.nextIndex + 1);
              this.ui('step').textContent = 'Microphone';
              this.cue('waiting', 'Allow microphone access');
              this.ui('status').textContent = '';
              this.ui('reload').hidden = true;
              this.ui('cancel').focus();
              _context5.p = 2;
              _context5.n = 3;
              return this.clock.releaseAudio();
            case 3:
              if (!(flow !== this.flow)) {
                _context5.n = 4;
                break;
              }
              return _context5.a(2);
            case 4:
              _context5.n = 5;
              return this.capture.open();
            case 5:
              if (!(this.nextIndex < this.endIndex)) {
                _context5.n = 13;
                break;
              }
              index = this.nextIndex;
              if (!(flow !== this.flow)) {
                _context5.n = 6;
                break;
              }
              return _context5.a(2);
            case 6:
              this.ui('number').textContent = String(index + 1);
              this.ui('step').textContent = "".concat(index + 1, " of 4");
              this.level(0);
              remaining = 3;
            case 7:
              if (!(remaining > 0)) {
                _context5.n = 9;
                break;
              }
              this.cue('waiting', 'Wait for green', "Ready in ".concat(remaining));
              _context5.n = 8;
              return this.delay(500, flow);
            case 8:
              remaining--;
              _context5.n = 7;
              break;
            case 9:
              this.cue('speaking', "Say \u201C".concat(WORDS[index], "\u201D"));
              _context5.n = 10;
              return this.capture.record(function (level) {
                return _this7.level(level);
              });
            case 10:
              this.draft[index] = _context5.v;
              if (!(flow !== this.flow)) {
                _context5.n = 11;
                break;
              }
              return _context5.a(2);
            case 11:
              this.cue('success', 'Got it!');
              _context5.n = 12;
              return this.delay(600, flow);
            case 12:
              this.nextIndex++;
              _context5.n = 5;
              break;
            case 13:
              _context5.n = 14;
              return this.capture.cancel();
            case 14:
              if (!(flow !== this.flow)) {
                _context5.n = 15;
                break;
              }
              return _context5.a(2);
            case 15:
              _context5.n = 16;
              return Promise.all(this.draft.map(function (bytes) {
                return _this7.decodeVoice(bytes.slice(0));
              }));
            case 16:
              draftBuffers = _context5.v;
              if (!(flow !== this.flow)) {
                _context5.n = 17;
                break;
              }
              return _context5.a(2);
            case 17:
              this.draftBuffers = draftBuffers;
              this.ui('step').textContent = '4 of 4 recorded';
              this.cue('review', 'How does it sound?');
              this.ui('review').hidden = this.ui('save').hidden = false;
              this.nameInput.focus();
              _context5.n = 20;
              break;
            case 18:
              _context5.p = 18;
              _t3 = _context5.v;
              if (!(flow !== this.flow)) {
                _context5.n = 19;
                break;
              }
              return _context5.a(2);
            case 19:
              this.capture.cancel();
              this.level(0);
              this.cue('error', "Let\u2019s try ".concat(WORDS[this.nextIndex], " again"));
              this.ui('status').textContent = _t3.name === 'NotAllowedError' ? 'Allow the microphone in your browser, then try again.' : _t3.name === 'NotFoundError' ? 'Connect a microphone, then try again.' : _t3.name === 'NotReadableError' ? 'Your microphone is busy. Close other recording apps and try again.' : _t3.message.startsWith('I couldn’t') || _t3.message.startsWith('A little') ? _t3.message : 'Recording stopped. Please try again.';
              this.ui('retry').hidden = false;
              this.ui('retry').focus();
            case 20:
              return _context5.a(2);
          }
        }, _callee5, this, [[2, 18]]);
      }));
      function record() {
        return _record.apply(this, arguments);
      }
      return record;
    }()
  }, {
    key: "level",
    value: function level(value) {
      this.ui('level').style.width = "".concat(value, "%");
      this.ui('meter').setAttribute('aria-valuenow', String(value));
    }
  }, {
    key: "cancel",
    value: function cancel() {
      this.flow++;
      this.capture.cancel();
      this.stopPreview();
      this.ui('panel').hidden = this.ui('rename-panel').hidden = true;
      this.ui('summary').hidden = false;
      delete this.dialog.dataset.recording;
      this.draft = [];
      this.draftBuffers = [];
      this.renameId = null;
      this.cue('idle', '');
      this.summary();
    }
  }, {
    key: "stopPreview",
    value: function stopPreview() {
      this.previewRequestVersion = (this.previewRequestVersion || 0) + 1;
      this.previewingId = null;
      this.previewGeneration = (this.previewGeneration || 0) + 1;
      this.previewSources.forEach(function (source) {
        source.stop();
        source.disconnect();
      });
      this.previewSources = [];
      if (!this.clock.running) this.clock.suspendAudio();
      this.onUpdate(this);
    }
  }, {
    key: "previewRecording",
    value: function () {
      var _previewRecording = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6(id) {
        var _this8 = this;
        var version, data, buffers, _t4, _t5, _t6;
        return _regenerator().w(function (_context6) {
          while (1) switch (_context6.p = _context6.n) {
            case 0:
              if (!(this.previewingId === id)) {
                _context6.n = 1;
                break;
              }
              this.stopPreview();
              return _context6.a(2);
            case 1:
              if (!(!this.ready || this.saving || !this.library.some(function (voice) {
                return voice.id === id;
              }))) {
                _context6.n = 2;
                break;
              }
              return _context6.a(2);
            case 2:
              this.stopPreview();
              version = this.previewRequestVersion;
              this.clock.stop();
              this.previewingId = id;
              this.onUpdate(this);
              _context6.p = 3;
              _context6.n = 4;
              return this.clock.resumeAudio();
            case 4:
              if (!(version !== this.previewRequestVersion)) {
                _context6.n = 5;
                break;
              }
              return _context6.a(2);
            case 5:
              if (!(id === this.activeId && this.buffers.length === 4)) {
                _context6.n = 6;
                break;
              }
              _t4 = null;
              _context6.n = 8;
              break;
            case 6:
              _context6.n = 7;
              return this.request('GET', null, id);
            case 7:
              _t4 = _context6.v;
            case 8:
              data = _t4;
              if (!data) {
                _context6.n = 10;
                break;
              }
              _context6.n = 9;
              return Promise.all(data.clips.map(function (clip) {
                return _this8.decodeVoice((0,_voice_audio__WEBPACK_IMPORTED_MODULE_0__.voiceBytes)(clip));
              }));
            case 9:
              _t5 = _context6.v;
              _context6.n = 11;
              break;
            case 10:
              _t5 = this.buffers;
            case 11:
              buffers = _t5;
              if (!(version !== this.previewRequestVersion)) {
                _context6.n = 12;
                break;
              }
              return _context6.a(2);
            case 12:
              _context6.n = 13;
              return this.preview(buffers.slice(0, this.getBeats()), id);
            case 13:
              _context6.n = 16;
              break;
            case 14:
              _context6.p = 14;
              _t6 = _context6.v;
              if (!(version !== this.previewRequestVersion)) {
                _context6.n = 15;
                break;
              }
              return _context6.a(2);
            case 15:
              this.stopPreview();
              this.failure(_t6);
            case 16:
              return _context6.a(2);
          }
        }, _callee6, this, [[3, 14]]);
      }));
      function previewRecording(_x4) {
        return _previewRecording.apply(this, arguments);
      }
      return previewRecording;
    }()
  }, {
    key: "preview",
    value: function () {
      var _preview = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee7(buffers) {
        var _this9 = this;
        var id,
          generation,
          audio,
          time,
          _args7 = arguments,
          _t7;
        return _regenerator().w(function (_context7) {
          while (1) switch (_context7.p = _context7.n) {
            case 0:
              id = _args7.length > 1 && _args7[1] !== undefined ? _args7[1] : null;
              if (buffers !== null && buffers !== void 0 && buffers.length) {
                _context7.n = 1;
                break;
              }
              return _context7.a(2);
            case 1:
              this.stopPreview();
              this.previewingId = id;
              this.onUpdate(this);
              this.clock.stop();
              generation = this.previewGeneration;
              _context7.p = 2;
              _context7.n = 3;
              return this.clock.resumeAudio();
            case 3:
              if (!(generation !== this.previewGeneration)) {
                _context7.n = 4;
                break;
              }
              return _context7.a(2);
            case 4:
              audio = this.context();
              time = audio.currentTime + 0.03;
              buffers.forEach(function (buffer) {
                var source = audio.createBufferSource();
                source.buffer = buffer;
                source.connect(audio.destination);
                source.onended = function () {
                  source.disconnect();
                  if (generation !== _this9.previewGeneration) return;
                  _this9.previewSources = _this9.previewSources.filter(function (item) {
                    return item !== source;
                  });
                  if (!_this9.previewSources.length) _this9.stopPreview();
                };
                source.start(time);
                _this9.previewSources.push(source);
                time += Math.max(0.65, buffer.duration + 0.12);
              });
              _context7.n = 6;
              break;
            case 5:
              _context7.p = 5;
              _t7 = _context7.v;
              this.stopPreview();
              this.ui('status').textContent = 'Sound couldn’t start. Tap Play to try again.';
            case 6:
              return _context7.a(2);
          }
        }, _callee7, this, [[2, 5]]);
      }));
      function preview(_x5) {
        return _preview.apply(this, arguments);
      }
      return preview;
    }()
  }, {
    key: "save",
    value: function () {
      var _save = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee8() {
        var name, flow, clips, data, _t8;
        return _regenerator().w(function (_context8) {
          while (1) switch (_context8.p = _context8.n) {
            case 0:
              if (!(this.saving || this.draft.length !== 4)) {
                _context8.n = 1;
                break;
              }
              return _context8.a(2);
            case 1:
              name = this.validName(this.nameInput);
              if (name) {
                _context8.n = 2;
                break;
              }
              return _context8.a(2);
            case 2:
              flow = this.flow;
              clips = this.draft.map(_voice_audio__WEBPACK_IMPORTED_MODULE_0__.voiceBase64);
              this.saving = true;
              this.controls();
              this.ui('save').disabled = true;
              this.ui('status').textContent = 'Saving your voice…';
              _context8.p = 3;
              _context8.n = 4;
              return this.clock.resumeAudio();
            case 4:
              if (!(flow !== this.flow)) {
                _context8.n = 5;
                break;
              }
              return _context8.a(2);
            case 5:
              _context8.n = 6;
              return this.request('PUT', {
                name: name,
                clips: clips
              });
            case 6:
              data = _context8.v;
              if (!(flow !== this.flow)) {
                _context8.n = 7;
                break;
              }
              this.ready = false;
              if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
              return _context8.a(2);
            case 7:
              this.revision = data.revision;
              this.activeId = this.preferredId = data.id;
              this.library.push({
                id: data.id,
                name: data.name,
                revision: data.revision
              });
              this.saved = data.clips;
              this.buffers = this.draftBuffers;
              this.clock.setVoiceBuffers(this.buffers);
              this.soundOptions();
              this.useSound('my-voice');
              this.remember('my-voice');
              this.cancel();
              this.ui('status').textContent = 'Saved to your account.';
              _context8.n = 8;
              return this.preview(this.buffers.slice(0, this.getBeats()));
            case 8:
              _context8.n = 10;
              break;
            case 9:
              _context8.p = 9;
              _t8 = _context8.v;
              if (flow === this.flow) this.failure(_t8);
            case 10:
              _context8.p = 10;
              this.saving = false;
              this.ui('save').disabled = false;
              if (!this.previewSources.length && !this.clock.running) this.clock.suspendAudio();
              this.controls();
              return _context8.f(10);
            case 11:
              return _context8.a(2);
          }
        }, _callee8, this, [[3, 9, 10, 11]]);
      }));
      function save() {
        return _save.apply(this, arguments);
      }
      return save;
    }()
  }, {
    key: "validName",
    value: function validName(input) {
      var name = input.value.trim();
      if (!name || _toConsumableArray(name).length > 60) {
        this.ui('status').textContent = name ? 'Keep the name to 60 characters.' : 'Give your recording a name.';
        input.focus();
        return null;
      }
      return name;
    }
  }, {
    key: "rename",
    value: function () {
      var _rename = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee9() {
        var _this0 = this;
        var target, name, flow, id, revision, data, _t9;
        return _regenerator().w(function (_context9) {
          while (1) switch (_context9.p = _context9.n) {
            case 0:
              target = this.library.find(function (voice) {
                return voice.id === (_this0.renameId || _this0.activeId);
              });
              if (!(this.saving || !target)) {
                _context9.n = 1;
                break;
              }
              return _context9.a(2);
            case 1:
              name = this.validName(this.renameInput);
              if (name) {
                _context9.n = 2;
                break;
              }
              return _context9.a(2);
            case 2:
              flow = this.flow;
              id = target.id, revision = target.revision;
              this.saving = true;
              this.controls();
              this.ui('rename-save').disabled = true;
              _context9.p = 3;
              _context9.n = 4;
              return this.request('PATCH', {
                name: name,
                revision: revision
              }, id);
            case 4:
              data = _context9.v;
              if (!(flow !== this.flow)) {
                _context9.n = 5;
                break;
              }
              this.ready = false;
              if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
              return _context9.a(2);
            case 5:
              this.library = this.library.map(function (voice) {
                return voice.id === id ? data : voice;
              });
              if (id === this.activeId) this.revision = data.revision;
              this.renameId = null;
              this.ui('rename-panel').hidden = true;
              this.summary();
              this.ui('status').textContent = 'Name saved.';
              _context9.n = 7;
              break;
            case 6:
              _context9.p = 6;
              _t9 = _context9.v;
              if (flow === this.flow) this.failure(_t9);
            case 7:
              _context9.p = 7;
              this.saving = false;
              this.ui('rename-save').disabled = false;
              this.controls();
              return _context9.f(7);
            case 8:
              return _context9.a(2);
          }
        }, _callee9, this, [[3, 6, 7, 8]]);
      }));
      function rename() {
        return _rename.apply(this, arguments);
      }
      return rename;
    }()
  }, {
    key: "remove",
    value: function () {
      var _remove = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee0() {
        var recordingId,
          target,
          flow,
          id,
          revision,
          active,
          activate,
          _args0 = arguments,
          _t0;
        return _regenerator().w(function (_context0) {
          while (1) switch (_context0.p = _context0.n) {
            case 0:
              recordingId = _args0.length > 0 && _args0[0] !== undefined ? _args0[0] : this.activeId;
              target = this.library.find(function (voice) {
                return voice.id === recordingId;
              });
              if (!(this.saving || !target)) {
                _context0.n = 1;
                break;
              }
              return _context0.a(2);
            case 1:
              flow = this.flow;
              id = target.id, revision = target.revision;
              active = id === this.activeId;
              this.saving = true;
              this.controls();
              this.stopPreview();
              if (active) this.clock.stop();
              _context0.p = 2;
              _context0.n = 3;
              return this.request('DELETE', {
                revision: revision
              }, id);
            case 3:
              if (!(flow !== this.flow)) {
                _context0.n = 4;
                break;
              }
              this.ready = false;
              if (this.dialog.getAttribute('aria-modal') === 'true') this.load();
              return _context0.a(2);
            case 4:
              activate = this.sound === 'my-voice';
              this.library = this.library.filter(function (voice) {
                return voice.id !== id;
              });
              if (active) this.clearVoice();
              this.cancel();
              if (!(active && this.library.length)) {
                _context0.n = 6;
                break;
              }
              _context0.n = 5;
              return this.choose(this.library[0].id, {
                activate: activate
              });
            case 5:
              _context0.n = 7;
              break;
            case 6:
              if (!this.library.length) this.remember('click');
            case 7:
              _context0.n = 9;
              break;
            case 8:
              _context0.p = 8;
              _t0 = _context0.v;
              if (flow === this.flow) this.failure(_t0);
            case 9:
              _context0.p = 9;
              this.saving = false;
              this.controls();
              return _context0.f(9);
            case 10:
              return _context0.a(2);
          }
        }, _callee0, this, [[2, 8, 9, 10]]);
      }));
      function remove() {
        return _remove.apply(this, arguments);
      }
      return remove;
    }()
  }, {
    key: "failure",
    value: function failure(error) {
      this.ui('status').textContent = error.message;
      this.ui('reload').hidden = ![404, 409, 401, 419].includes(error.status);
      this.invalidateSession(error);
    }
  }, {
    key: "invalidateSession",
    value: function invalidateSession(error) {
      if (![401, 419].includes(error.status)) return;
      this.flow++;
      this.voiceVersion++;
      this.loadVersion++;
      this.capture.cancel();
      this.stopPreview();
      this.clock.stop();
      this.clock.setVoiceBuffers(null);
      this.ready = false;
      this.buffers = this.saved = [];
      this.library = [];
      this.activeId = null;
      this.draft = this.draftBuffers = [];
      this.ui('panel').hidden = true;
      this.ui('summary').hidden = false;
      delete this.dialog.dataset.recording;
      if (this.sound === 'my-voice') this.useSound('click');
      this.summary();
      this.ui('status').textContent = error.message;
      this.ui('signin').hidden = false;
      this.ui('start').disabled = true;
    }
  }]);
}();

/***/ },

/***/ "./resources/js/metronome/voice-stretch.js"
/*!*************************************************!*\
  !*** ./resources/js/metronome/voice-stretch.js ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   compressVoice: () => (/* binding */ compressVoice)
/* harmony export */ });
// Compress a short count with waveform-similar overlap/add. Copying samples at
// their original spacing preserves pitch; only the distance between grains
// changes. Work on a new buffer so saved recordings and previews stay intact.
function compressVoice(buffer, maxDuration, createBuffer) {
  if (buffer.duration <= maxDuration) return buffer;
  var rate = buffer.sampleRate;
  var length = Math.max(1, Math.floor(maxDuration * rate));
  var frame = Math.min(Math.round(rate * 0.04), Math.floor(length / 2));
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

/***/ "./node_modules/bootstrap/js/dist/base-component.js"
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/base-component.js ***!
  \**********************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap base-component.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ./dom/data.js */ "./node_modules/bootstrap/js/dist/dom/data.js"), __webpack_require__(/*! ./dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./util/config.js */ "./node_modules/bootstrap/js/dist/util/config.js"), __webpack_require__(/*! ./util/index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (Data, EventHandler, Config, index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap base-component.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const VERSION = '5.3.8';

  /**
   * Class definition
   */

  class BaseComponent extends Config {
    constructor(element, config) {
      super();
      element = index_js.getElement(element);
      if (!element) {
        return;
      }
      this._element = element;
      this._config = this._getConfig(config);
      Data.set(this._element, this.constructor.DATA_KEY, this);
    }

    // Public
    dispose() {
      Data.remove(this._element, this.constructor.DATA_KEY);
      EventHandler.off(this._element, this.constructor.EVENT_KEY);
      for (const propertyName of Object.getOwnPropertyNames(this)) {
        this[propertyName] = null;
      }
    }

    // Private
    _queueCallback(callback, element, isAnimated = true) {
      index_js.executeAfterTransition(callback, element, isAnimated);
    }
    _getConfig(config) {
      config = this._mergeConfigObj(config, this._element);
      config = this._configAfterMerge(config);
      this._typeCheckConfig(config);
      return config;
    }

    // Static
    static getInstance(element) {
      return Data.get(index_js.getElement(element), this.DATA_KEY);
    }
    static getOrCreateInstance(element, config = {}) {
      return this.getInstance(element) || new this(element, typeof config === 'object' ? config : null);
    }
    static get VERSION() {
      return VERSION;
    }
    static get DATA_KEY() {
      return `bs.${this.NAME}`;
    }
    static get EVENT_KEY() {
      return `.${this.DATA_KEY}`;
    }
    static eventName(name) {
      return `${name}${this.EVENT_KEY}`;
    }
  }

  return BaseComponent;

}));
//# sourceMappingURL=base-component.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/dom/data.js"
/*!****************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/data.js ***!
  \****************************************************/
(module) {

/*!
  * Bootstrap data.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory() :
  0;
})(this, (function () { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap dom/data.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  /**
   * Constants
   */

  const elementMap = new Map();
  const data = {
    set(element, key, instance) {
      if (!elementMap.has(element)) {
        elementMap.set(element, new Map());
      }
      const instanceMap = elementMap.get(element);

      // make it clear we only want one instance per element
      // can be removed later when multiple key/instances are fine to be used
      if (!instanceMap.has(key) && instanceMap.size !== 0) {
        // eslint-disable-next-line no-console
        console.error(`Bootstrap doesn't allow more than one instance per element. Bound instance: ${Array.from(instanceMap.keys())[0]}.`);
        return;
      }
      instanceMap.set(key, instance);
    },
    get(element, key) {
      if (elementMap.has(element)) {
        return elementMap.get(element).get(key) || null;
      }
      return null;
    },
    remove(element, key) {
      if (!elementMap.has(element)) {
        return;
      }
      const instanceMap = elementMap.get(element);
      instanceMap.delete(key);

      // free up element references if there are no instances left for an element
      if (instanceMap.size === 0) {
        elementMap.delete(element);
      }
    }
  };

  return data;

}));
//# sourceMappingURL=data.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/dom/event-handler.js"
/*!*************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/event-handler.js ***!
  \*************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap event-handler.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../util/index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap dom/event-handler.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const namespaceRegex = /[^.]*(?=\..*)\.|.*/;
  const stripNameRegex = /\..*/;
  const stripUidRegex = /::\d+$/;
  const eventRegistry = {}; // Events storage
  let uidEvent = 1;
  const customEvents = {
    mouseenter: 'mouseover',
    mouseleave: 'mouseout'
  };
  const nativeEvents = new Set(['click', 'dblclick', 'mouseup', 'mousedown', 'contextmenu', 'mousewheel', 'DOMMouseScroll', 'mouseover', 'mouseout', 'mousemove', 'selectstart', 'selectend', 'keydown', 'keypress', 'keyup', 'orientationchange', 'touchstart', 'touchmove', 'touchend', 'touchcancel', 'pointerdown', 'pointermove', 'pointerup', 'pointerleave', 'pointercancel', 'gesturestart', 'gesturechange', 'gestureend', 'focus', 'blur', 'change', 'reset', 'select', 'submit', 'focusin', 'focusout', 'load', 'unload', 'beforeunload', 'resize', 'move', 'DOMContentLoaded', 'readystatechange', 'error', 'abort', 'scroll']);

  /**
   * Private methods
   */

  function makeEventUid(element, uid) {
    return uid && `${uid}::${uidEvent++}` || element.uidEvent || uidEvent++;
  }
  function getElementEvents(element) {
    const uid = makeEventUid(element);
    element.uidEvent = uid;
    eventRegistry[uid] = eventRegistry[uid] || {};
    return eventRegistry[uid];
  }
  function bootstrapHandler(element, fn) {
    return function handler(event) {
      hydrateObj(event, {
        delegateTarget: element
      });
      if (handler.oneOff) {
        EventHandler.off(element, event.type, fn);
      }
      return fn.apply(element, [event]);
    };
  }
  function bootstrapDelegationHandler(element, selector, fn) {
    return function handler(event) {
      const domElements = element.querySelectorAll(selector);
      for (let {
        target
      } = event; target && target !== this; target = target.parentNode) {
        for (const domElement of domElements) {
          if (domElement !== target) {
            continue;
          }
          hydrateObj(event, {
            delegateTarget: target
          });
          if (handler.oneOff) {
            EventHandler.off(element, event.type, selector, fn);
          }
          return fn.apply(target, [event]);
        }
      }
    };
  }
  function findHandler(events, callable, delegationSelector = null) {
    return Object.values(events).find(event => event.callable === callable && event.delegationSelector === delegationSelector);
  }
  function normalizeParameters(originalTypeEvent, handler, delegationFunction) {
    const isDelegated = typeof handler === 'string';
    // TODO: tooltip passes `false` instead of selector, so we need to check
    const callable = isDelegated ? delegationFunction : handler || delegationFunction;
    let typeEvent = getTypeEvent(originalTypeEvent);
    if (!nativeEvents.has(typeEvent)) {
      typeEvent = originalTypeEvent;
    }
    return [isDelegated, callable, typeEvent];
  }
  function addHandler(element, originalTypeEvent, handler, delegationFunction, oneOff) {
    if (typeof originalTypeEvent !== 'string' || !element) {
      return;
    }
    let [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction);

    // in case of mouseenter or mouseleave wrap the handler within a function that checks for its DOM position
    // this prevents the handler from being dispatched the same way as mouseover or mouseout does
    if (originalTypeEvent in customEvents) {
      const wrapFunction = fn => {
        return function (event) {
          if (!event.relatedTarget || event.relatedTarget !== event.delegateTarget && !event.delegateTarget.contains(event.relatedTarget)) {
            return fn.call(this, event);
          }
        };
      };
      callable = wrapFunction(callable);
    }
    const events = getElementEvents(element);
    const handlers = events[typeEvent] || (events[typeEvent] = {});
    const previousFunction = findHandler(handlers, callable, isDelegated ? handler : null);
    if (previousFunction) {
      previousFunction.oneOff = previousFunction.oneOff && oneOff;
      return;
    }
    const uid = makeEventUid(callable, originalTypeEvent.replace(namespaceRegex, ''));
    const fn = isDelegated ? bootstrapDelegationHandler(element, handler, callable) : bootstrapHandler(element, callable);
    fn.delegationSelector = isDelegated ? handler : null;
    fn.callable = callable;
    fn.oneOff = oneOff;
    fn.uidEvent = uid;
    handlers[uid] = fn;
    element.addEventListener(typeEvent, fn, isDelegated);
  }
  function removeHandler(element, events, typeEvent, handler, delegationSelector) {
    const fn = findHandler(events[typeEvent], handler, delegationSelector);
    if (!fn) {
      return;
    }
    element.removeEventListener(typeEvent, fn, Boolean(delegationSelector));
    delete events[typeEvent][fn.uidEvent];
  }
  function removeNamespacedHandlers(element, events, typeEvent, namespace) {
    const storeElementEvent = events[typeEvent] || {};
    for (const [handlerKey, event] of Object.entries(storeElementEvent)) {
      if (handlerKey.includes(namespace)) {
        removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
      }
    }
  }
  function getTypeEvent(event) {
    // allow to get the native events from namespaced events ('click.bs.button' --> 'click')
    event = event.replace(stripNameRegex, '');
    return customEvents[event] || event;
  }
  const EventHandler = {
    on(element, event, handler, delegationFunction) {
      addHandler(element, event, handler, delegationFunction, false);
    },
    one(element, event, handler, delegationFunction) {
      addHandler(element, event, handler, delegationFunction, true);
    },
    off(element, originalTypeEvent, handler, delegationFunction) {
      if (typeof originalTypeEvent !== 'string' || !element) {
        return;
      }
      const [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction);
      const inNamespace = typeEvent !== originalTypeEvent;
      const events = getElementEvents(element);
      const storeElementEvent = events[typeEvent] || {};
      const isNamespace = originalTypeEvent.startsWith('.');
      if (typeof callable !== 'undefined') {
        // Simplest case: handler is passed, remove that listener ONLY.
        if (!Object.keys(storeElementEvent).length) {
          return;
        }
        removeHandler(element, events, typeEvent, callable, isDelegated ? handler : null);
        return;
      }
      if (isNamespace) {
        for (const elementEvent of Object.keys(events)) {
          removeNamespacedHandlers(element, events, elementEvent, originalTypeEvent.slice(1));
        }
      }
      for (const [keyHandlers, event] of Object.entries(storeElementEvent)) {
        const handlerKey = keyHandlers.replace(stripUidRegex, '');
        if (!inNamespace || originalTypeEvent.includes(handlerKey)) {
          removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
        }
      }
    },
    trigger(element, event, args) {
      if (typeof event !== 'string' || !element) {
        return null;
      }
      const $ = index_js.getjQuery();
      const typeEvent = getTypeEvent(event);
      const inNamespace = event !== typeEvent;
      let jQueryEvent = null;
      let bubbles = true;
      let nativeDispatch = true;
      let defaultPrevented = false;
      if (inNamespace && $) {
        jQueryEvent = $.Event(event, args);
        $(element).trigger(jQueryEvent);
        bubbles = !jQueryEvent.isPropagationStopped();
        nativeDispatch = !jQueryEvent.isImmediatePropagationStopped();
        defaultPrevented = jQueryEvent.isDefaultPrevented();
      }
      const evt = hydrateObj(new Event(event, {
        bubbles,
        cancelable: true
      }), args);
      if (defaultPrevented) {
        evt.preventDefault();
      }
      if (nativeDispatch) {
        element.dispatchEvent(evt);
      }
      if (evt.defaultPrevented && jQueryEvent) {
        jQueryEvent.preventDefault();
      }
      return evt;
    }
  };
  function hydrateObj(obj, meta = {}) {
    for (const [key, value] of Object.entries(meta)) {
      try {
        obj[key] = value;
      } catch (_unused) {
        Object.defineProperty(obj, key, {
          configurable: true,
          get() {
            return value;
          }
        });
      }
    }
    return obj;
  }

  return EventHandler;

}));
//# sourceMappingURL=event-handler.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/dom/manipulator.js"
/*!***********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/manipulator.js ***!
  \***********************************************************/
(module) {

/*!
  * Bootstrap manipulator.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory() :
  0;
})(this, (function () { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap dom/manipulator.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  function normalizeData(value) {
    if (value === 'true') {
      return true;
    }
    if (value === 'false') {
      return false;
    }
    if (value === Number(value).toString()) {
      return Number(value);
    }
    if (value === '' || value === 'null') {
      return null;
    }
    if (typeof value !== 'string') {
      return value;
    }
    try {
      return JSON.parse(decodeURIComponent(value));
    } catch (_unused) {
      return value;
    }
  }
  function normalizeDataKey(key) {
    return key.replace(/[A-Z]/g, chr => `-${chr.toLowerCase()}`);
  }
  const Manipulator = {
    setDataAttribute(element, key, value) {
      element.setAttribute(`data-bs-${normalizeDataKey(key)}`, value);
    },
    removeDataAttribute(element, key) {
      element.removeAttribute(`data-bs-${normalizeDataKey(key)}`);
    },
    getDataAttributes(element) {
      if (!element) {
        return {};
      }
      const attributes = {};
      const bsKeys = Object.keys(element.dataset).filter(key => key.startsWith('bs') && !key.startsWith('bsConfig'));
      for (const key of bsKeys) {
        let pureKey = key.replace(/^bs/, '');
        pureKey = pureKey.charAt(0).toLowerCase() + pureKey.slice(1);
        attributes[pureKey] = normalizeData(element.dataset[key]);
      }
      return attributes;
    },
    getDataAttribute(element, key) {
      return normalizeData(element.getAttribute(`data-bs-${normalizeDataKey(key)}`));
    }
  };

  return Manipulator;

}));
//# sourceMappingURL=manipulator.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"
/*!***************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/selector-engine.js ***!
  \***************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap selector-engine.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../util/index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap dom/selector-engine.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  const getSelector = element => {
    let selector = element.getAttribute('data-bs-target');
    if (!selector || selector === '#') {
      let hrefAttribute = element.getAttribute('href');

      // The only valid content that could double as a selector are IDs or classes,
      // so everything starting with `#` or `.`. If a "real" URL is used as the selector,
      // `document.querySelector` will rightfully complain it is invalid.
      // See https://github.com/twbs/bootstrap/issues/32273
      if (!hrefAttribute || !hrefAttribute.includes('#') && !hrefAttribute.startsWith('.')) {
        return null;
      }

      // Just in case some CMS puts out a full URL with the anchor appended
      if (hrefAttribute.includes('#') && !hrefAttribute.startsWith('#')) {
        hrefAttribute = `#${hrefAttribute.split('#')[1]}`;
      }
      selector = hrefAttribute && hrefAttribute !== '#' ? hrefAttribute.trim() : null;
    }
    return selector ? selector.split(',').map(sel => index_js.parseSelector(sel)).join(',') : null;
  };
  const SelectorEngine = {
    find(selector, element = document.documentElement) {
      return [].concat(...Element.prototype.querySelectorAll.call(element, selector));
    },
    findOne(selector, element = document.documentElement) {
      return Element.prototype.querySelector.call(element, selector);
    },
    children(element, selector) {
      return [].concat(...element.children).filter(child => child.matches(selector));
    },
    parents(element, selector) {
      const parents = [];
      let ancestor = element.parentNode.closest(selector);
      while (ancestor) {
        parents.push(ancestor);
        ancestor = ancestor.parentNode.closest(selector);
      }
      return parents;
    },
    prev(element, selector) {
      let previous = element.previousElementSibling;
      while (previous) {
        if (previous.matches(selector)) {
          return [previous];
        }
        previous = previous.previousElementSibling;
      }
      return [];
    },
    // TODO: this is now unused; remove later along with prev()
    next(element, selector) {
      let next = element.nextElementSibling;
      while (next) {
        if (next.matches(selector)) {
          return [next];
        }
        next = next.nextElementSibling;
      }
      return [];
    },
    focusableChildren(element) {
      const focusables = ['a', 'button', 'input', 'textarea', 'select', 'details', '[tabindex]', '[contenteditable="true"]'].map(selector => `${selector}:not([tabindex^="-"])`).join(',');
      return this.find(focusables, element).filter(el => !index_js.isDisabled(el) && index_js.isVisible(el));
    },
    getSelectorFromElement(element) {
      const selector = getSelector(element);
      if (selector) {
        return SelectorEngine.findOne(selector) ? selector : null;
      }
      return null;
    },
    getElementFromSelector(element) {
      const selector = getSelector(element);
      return selector ? SelectorEngine.findOne(selector) : null;
    },
    getMultipleElementsFromSelector(element) {
      const selector = getSelector(element);
      return selector ? SelectorEngine.find(selector) : [];
    }
  };

  return SelectorEngine;

}));
//# sourceMappingURL=selector-engine.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/dropdown.js"
/*!****************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dropdown.js ***!
  \****************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap dropdown.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! @popperjs/core */ "./node_modules/@popperjs/core/lib/index.js"), __webpack_require__(/*! ./base-component.js */ "./node_modules/bootstrap/js/dist/base-component.js"), __webpack_require__(/*! ./dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./dom/manipulator.js */ "./node_modules/bootstrap/js/dist/dom/manipulator.js"), __webpack_require__(/*! ./dom/selector-engine.js */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./util/index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (Popper, BaseComponent, EventHandler, Manipulator, SelectorEngine, index_js) { 'use strict';

  function _interopNamespaceDefault(e) {
    const n = Object.create(null, { [Symbol.toStringTag]: { value: 'Module' } });
    if (e) {
      for (const k in e) {
        if (k !== 'default') {
          const d = Object.getOwnPropertyDescriptor(e, k);
          Object.defineProperty(n, k, d.get ? d : {
            enumerable: true,
            get: () => e[k]
          });
        }
      }
    }
    n.default = e;
    return Object.freeze(n);
  }

  const Popper__namespace = /*#__PURE__*/_interopNamespaceDefault(Popper);

  /**
   * --------------------------------------------------------------------------
   * Bootstrap dropdown.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const NAME = 'dropdown';
  const DATA_KEY = 'bs.dropdown';
  const EVENT_KEY = `.${DATA_KEY}`;
  const DATA_API_KEY = '.data-api';
  const ESCAPE_KEY = 'Escape';
  const TAB_KEY = 'Tab';
  const ARROW_UP_KEY = 'ArrowUp';
  const ARROW_DOWN_KEY = 'ArrowDown';
  const RIGHT_MOUSE_BUTTON = 2; // MouseEvent.button value for the secondary button, usually the right button

  const EVENT_HIDE = `hide${EVENT_KEY}`;
  const EVENT_HIDDEN = `hidden${EVENT_KEY}`;
  const EVENT_SHOW = `show${EVENT_KEY}`;
  const EVENT_SHOWN = `shown${EVENT_KEY}`;
  const EVENT_CLICK_DATA_API = `click${EVENT_KEY}${DATA_API_KEY}`;
  const EVENT_KEYDOWN_DATA_API = `keydown${EVENT_KEY}${DATA_API_KEY}`;
  const EVENT_KEYUP_DATA_API = `keyup${EVENT_KEY}${DATA_API_KEY}`;
  const CLASS_NAME_SHOW = 'show';
  const CLASS_NAME_DROPUP = 'dropup';
  const CLASS_NAME_DROPEND = 'dropend';
  const CLASS_NAME_DROPSTART = 'dropstart';
  const CLASS_NAME_DROPUP_CENTER = 'dropup-center';
  const CLASS_NAME_DROPDOWN_CENTER = 'dropdown-center';
  const SELECTOR_DATA_TOGGLE = '[data-bs-toggle="dropdown"]:not(.disabled):not(:disabled)';
  const SELECTOR_DATA_TOGGLE_SHOWN = `${SELECTOR_DATA_TOGGLE}.${CLASS_NAME_SHOW}`;
  const SELECTOR_MENU = '.dropdown-menu';
  const SELECTOR_NAVBAR = '.navbar';
  const SELECTOR_NAVBAR_NAV = '.navbar-nav';
  const SELECTOR_VISIBLE_ITEMS = '.dropdown-menu .dropdown-item:not(.disabled):not(:disabled)';
  const PLACEMENT_TOP = index_js.isRTL() ? 'top-end' : 'top-start';
  const PLACEMENT_TOPEND = index_js.isRTL() ? 'top-start' : 'top-end';
  const PLACEMENT_BOTTOM = index_js.isRTL() ? 'bottom-end' : 'bottom-start';
  const PLACEMENT_BOTTOMEND = index_js.isRTL() ? 'bottom-start' : 'bottom-end';
  const PLACEMENT_RIGHT = index_js.isRTL() ? 'left-start' : 'right-start';
  const PLACEMENT_LEFT = index_js.isRTL() ? 'right-start' : 'left-start';
  const PLACEMENT_TOPCENTER = 'top';
  const PLACEMENT_BOTTOMCENTER = 'bottom';
  const Default = {
    autoClose: true,
    boundary: 'clippingParents',
    display: 'dynamic',
    offset: [0, 2],
    popperConfig: null,
    reference: 'toggle'
  };
  const DefaultType = {
    autoClose: '(boolean|string)',
    boundary: '(string|element)',
    display: 'string',
    offset: '(array|string|function)',
    popperConfig: '(null|object|function)',
    reference: '(string|element|object)'
  };

  /**
   * Class definition
   */

  class Dropdown extends BaseComponent {
    constructor(element, config) {
      super(element, config);
      this._popper = null;
      this._parent = this._element.parentNode; // dropdown wrapper
      // TODO: v6 revert #37011 & change markup https://getbootstrap.com/docs/5.3/forms/input-group/
      this._menu = SelectorEngine.next(this._element, SELECTOR_MENU)[0] || SelectorEngine.prev(this._element, SELECTOR_MENU)[0] || SelectorEngine.findOne(SELECTOR_MENU, this._parent);
      this._inNavbar = this._detectNavbar();
    }

    // Getters
    static get Default() {
      return Default;
    }
    static get DefaultType() {
      return DefaultType;
    }
    static get NAME() {
      return NAME;
    }

    // Public
    toggle() {
      return this._isShown() ? this.hide() : this.show();
    }
    show() {
      if (index_js.isDisabled(this._element) || this._isShown()) {
        return;
      }
      const relatedTarget = {
        relatedTarget: this._element
      };
      const showEvent = EventHandler.trigger(this._element, EVENT_SHOW, relatedTarget);
      if (showEvent.defaultPrevented) {
        return;
      }
      this._createPopper();

      // If this is a touch-enabled device we add extra
      // empty mouseover listeners to the body's immediate children;
      // only needed because of broken event delegation on iOS
      // https://www.quirksmode.org/blog/archives/2014/02/mouse_event_bub.html
      if ('ontouchstart' in document.documentElement && !this._parent.closest(SELECTOR_NAVBAR_NAV)) {
        for (const element of [].concat(...document.body.children)) {
          EventHandler.on(element, 'mouseover', index_js.noop);
        }
      }
      this._element.focus();
      this._element.setAttribute('aria-expanded', true);
      this._menu.classList.add(CLASS_NAME_SHOW);
      this._element.classList.add(CLASS_NAME_SHOW);
      EventHandler.trigger(this._element, EVENT_SHOWN, relatedTarget);
    }
    hide() {
      if (index_js.isDisabled(this._element) || !this._isShown()) {
        return;
      }
      const relatedTarget = {
        relatedTarget: this._element
      };
      this._completeHide(relatedTarget);
    }
    dispose() {
      if (this._popper) {
        this._popper.destroy();
      }
      super.dispose();
    }
    update() {
      this._inNavbar = this._detectNavbar();
      if (this._popper) {
        this._popper.update();
      }
    }

    // Private
    _completeHide(relatedTarget) {
      const hideEvent = EventHandler.trigger(this._element, EVENT_HIDE, relatedTarget);
      if (hideEvent.defaultPrevented) {
        return;
      }

      // If this is a touch-enabled device we remove the extra
      // empty mouseover listeners we added for iOS support
      if ('ontouchstart' in document.documentElement) {
        for (const element of [].concat(...document.body.children)) {
          EventHandler.off(element, 'mouseover', index_js.noop);
        }
      }
      if (this._popper) {
        this._popper.destroy();
      }
      this._menu.classList.remove(CLASS_NAME_SHOW);
      this._element.classList.remove(CLASS_NAME_SHOW);
      this._element.setAttribute('aria-expanded', 'false');
      Manipulator.removeDataAttribute(this._menu, 'popper');
      EventHandler.trigger(this._element, EVENT_HIDDEN, relatedTarget);
    }
    _getConfig(config) {
      config = super._getConfig(config);
      if (typeof config.reference === 'object' && !index_js.isElement(config.reference) && typeof config.reference.getBoundingClientRect !== 'function') {
        // Popper virtual elements require a getBoundingClientRect method
        throw new TypeError(`${NAME.toUpperCase()}: Option "reference" provided type "object" without a required "getBoundingClientRect" method.`);
      }
      return config;
    }
    _createPopper() {
      if (typeof Popper__namespace === 'undefined') {
        throw new TypeError('Bootstrap\'s dropdowns require Popper (https://popper.js.org/docs/v2/)');
      }
      let referenceElement = this._element;
      if (this._config.reference === 'parent') {
        referenceElement = this._parent;
      } else if (index_js.isElement(this._config.reference)) {
        referenceElement = index_js.getElement(this._config.reference);
      } else if (typeof this._config.reference === 'object') {
        referenceElement = this._config.reference;
      }
      const popperConfig = this._getPopperConfig();
      this._popper = Popper__namespace.createPopper(referenceElement, this._menu, popperConfig);
    }
    _isShown() {
      return this._menu.classList.contains(CLASS_NAME_SHOW);
    }
    _getPlacement() {
      const parentDropdown = this._parent;
      if (parentDropdown.classList.contains(CLASS_NAME_DROPEND)) {
        return PLACEMENT_RIGHT;
      }
      if (parentDropdown.classList.contains(CLASS_NAME_DROPSTART)) {
        return PLACEMENT_LEFT;
      }
      if (parentDropdown.classList.contains(CLASS_NAME_DROPUP_CENTER)) {
        return PLACEMENT_TOPCENTER;
      }
      if (parentDropdown.classList.contains(CLASS_NAME_DROPDOWN_CENTER)) {
        return PLACEMENT_BOTTOMCENTER;
      }

      // We need to trim the value because custom properties can also include spaces
      const isEnd = getComputedStyle(this._menu).getPropertyValue('--bs-position').trim() === 'end';
      if (parentDropdown.classList.contains(CLASS_NAME_DROPUP)) {
        return isEnd ? PLACEMENT_TOPEND : PLACEMENT_TOP;
      }
      return isEnd ? PLACEMENT_BOTTOMEND : PLACEMENT_BOTTOM;
    }
    _detectNavbar() {
      return this._element.closest(SELECTOR_NAVBAR) !== null;
    }
    _getOffset() {
      const {
        offset
      } = this._config;
      if (typeof offset === 'string') {
        return offset.split(',').map(value => Number.parseInt(value, 10));
      }
      if (typeof offset === 'function') {
        return popperData => offset(popperData, this._element);
      }
      return offset;
    }
    _getPopperConfig() {
      const defaultBsPopperConfig = {
        placement: this._getPlacement(),
        modifiers: [{
          name: 'preventOverflow',
          options: {
            boundary: this._config.boundary
          }
        }, {
          name: 'offset',
          options: {
            offset: this._getOffset()
          }
        }]
      };

      // Disable Popper if we have a static display or Dropdown is in Navbar
      if (this._inNavbar || this._config.display === 'static') {
        Manipulator.setDataAttribute(this._menu, 'popper', 'static'); // TODO: v6 remove
        defaultBsPopperConfig.modifiers = [{
          name: 'applyStyles',
          enabled: false
        }];
      }
      return {
        ...defaultBsPopperConfig,
        ...index_js.execute(this._config.popperConfig, [undefined, defaultBsPopperConfig])
      };
    }
    _selectMenuItem({
      key,
      target
    }) {
      const items = SelectorEngine.find(SELECTOR_VISIBLE_ITEMS, this._menu).filter(element => index_js.isVisible(element));
      if (!items.length) {
        return;
      }

      // if target isn't included in items (e.g. when expanding the dropdown)
      // allow cycling to get the last item in case key equals ARROW_UP_KEY
      index_js.getNextActiveElement(items, target, key === ARROW_DOWN_KEY, !items.includes(target)).focus();
    }

    // Static
    static jQueryInterface(config) {
      return this.each(function () {
        const data = Dropdown.getOrCreateInstance(this, config);
        if (typeof config !== 'string') {
          return;
        }
        if (typeof data[config] === 'undefined') {
          throw new TypeError(`No method named "${config}"`);
        }
        data[config]();
      });
    }
    static clearMenus(event) {
      if (event.button === RIGHT_MOUSE_BUTTON || event.type === 'keyup' && event.key !== TAB_KEY) {
        return;
      }
      const openToggles = SelectorEngine.find(SELECTOR_DATA_TOGGLE_SHOWN);
      for (const toggle of openToggles) {
        const context = Dropdown.getInstance(toggle);
        if (!context || context._config.autoClose === false) {
          continue;
        }
        const composedPath = event.composedPath();
        const isMenuTarget = composedPath.includes(context._menu);
        if (composedPath.includes(context._element) || context._config.autoClose === 'inside' && !isMenuTarget || context._config.autoClose === 'outside' && isMenuTarget) {
          continue;
        }

        // Tab navigation through the dropdown menu or events from contained inputs shouldn't close the menu
        if (context._menu.contains(event.target) && (event.type === 'keyup' && event.key === TAB_KEY || /input|select|option|textarea|form/i.test(event.target.tagName))) {
          continue;
        }
        const relatedTarget = {
          relatedTarget: context._element
        };
        if (event.type === 'click') {
          relatedTarget.clickEvent = event;
        }
        context._completeHide(relatedTarget);
      }
    }
    static dataApiKeydownHandler(event) {
      // If not an UP | DOWN | ESCAPE key => not a dropdown command
      // If input/textarea && if key is other than ESCAPE => not a dropdown command

      const isInput = /input|textarea/i.test(event.target.tagName);
      const isEscapeEvent = event.key === ESCAPE_KEY;
      const isUpOrDownEvent = [ARROW_UP_KEY, ARROW_DOWN_KEY].includes(event.key);
      if (!isUpOrDownEvent && !isEscapeEvent) {
        return;
      }
      if (isInput && !isEscapeEvent) {
        return;
      }
      event.preventDefault();

      // TODO: v6 revert #37011 & change markup https://getbootstrap.com/docs/5.3/forms/input-group/
      const getToggleButton = this.matches(SELECTOR_DATA_TOGGLE) ? this : SelectorEngine.prev(this, SELECTOR_DATA_TOGGLE)[0] || SelectorEngine.next(this, SELECTOR_DATA_TOGGLE)[0] || SelectorEngine.findOne(SELECTOR_DATA_TOGGLE, event.delegateTarget.parentNode);
      const instance = Dropdown.getOrCreateInstance(getToggleButton);
      if (isUpOrDownEvent) {
        event.stopPropagation();
        instance.show();
        instance._selectMenuItem(event);
        return;
      }
      if (instance._isShown()) {
        // else is escape and we check if it is shown
        event.stopPropagation();
        instance.hide();
        getToggleButton.focus();
      }
    }
  }

  /**
   * Data API implementation
   */

  EventHandler.on(document, EVENT_KEYDOWN_DATA_API, SELECTOR_DATA_TOGGLE, Dropdown.dataApiKeydownHandler);
  EventHandler.on(document, EVENT_KEYDOWN_DATA_API, SELECTOR_MENU, Dropdown.dataApiKeydownHandler);
  EventHandler.on(document, EVENT_CLICK_DATA_API, Dropdown.clearMenus);
  EventHandler.on(document, EVENT_KEYUP_DATA_API, Dropdown.clearMenus);
  EventHandler.on(document, EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
    event.preventDefault();
    Dropdown.getOrCreateInstance(this).toggle();
  });

  /**
   * jQuery
   */

  index_js.defineJQueryPlugin(Dropdown);

  return Dropdown;

}));
//# sourceMappingURL=dropdown.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/offcanvas.js"
/*!*****************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/offcanvas.js ***!
  \*****************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap offcanvas.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ./base-component.js */ "./node_modules/bootstrap/js/dist/base-component.js"), __webpack_require__(/*! ./dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./dom/selector-engine.js */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./util/backdrop.js */ "./node_modules/bootstrap/js/dist/util/backdrop.js"), __webpack_require__(/*! ./util/component-functions.js */ "./node_modules/bootstrap/js/dist/util/component-functions.js"), __webpack_require__(/*! ./util/focustrap.js */ "./node_modules/bootstrap/js/dist/util/focustrap.js"), __webpack_require__(/*! ./util/index.js */ "./node_modules/bootstrap/js/dist/util/index.js"), __webpack_require__(/*! ./util/scrollbar.js */ "./node_modules/bootstrap/js/dist/util/scrollbar.js")) :
  0;
})(this, (function (BaseComponent, EventHandler, SelectorEngine, Backdrop, componentFunctions_js, FocusTrap, index_js, ScrollBarHelper) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap offcanvas.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const NAME = 'offcanvas';
  const DATA_KEY = 'bs.offcanvas';
  const EVENT_KEY = `.${DATA_KEY}`;
  const DATA_API_KEY = '.data-api';
  const EVENT_LOAD_DATA_API = `load${EVENT_KEY}${DATA_API_KEY}`;
  const ESCAPE_KEY = 'Escape';
  const CLASS_NAME_SHOW = 'show';
  const CLASS_NAME_SHOWING = 'showing';
  const CLASS_NAME_HIDING = 'hiding';
  const CLASS_NAME_BACKDROP = 'offcanvas-backdrop';
  const OPEN_SELECTOR = '.offcanvas.show';
  const EVENT_SHOW = `show${EVENT_KEY}`;
  const EVENT_SHOWN = `shown${EVENT_KEY}`;
  const EVENT_HIDE = `hide${EVENT_KEY}`;
  const EVENT_HIDE_PREVENTED = `hidePrevented${EVENT_KEY}`;
  const EVENT_HIDDEN = `hidden${EVENT_KEY}`;
  const EVENT_RESIZE = `resize${EVENT_KEY}`;
  const EVENT_CLICK_DATA_API = `click${EVENT_KEY}${DATA_API_KEY}`;
  const EVENT_KEYDOWN_DISMISS = `keydown.dismiss${EVENT_KEY}`;
  const SELECTOR_DATA_TOGGLE = '[data-bs-toggle="offcanvas"]';
  const Default = {
    backdrop: true,
    keyboard: true,
    scroll: false
  };
  const DefaultType = {
    backdrop: '(boolean|string)',
    keyboard: 'boolean',
    scroll: 'boolean'
  };

  /**
   * Class definition
   */

  class Offcanvas extends BaseComponent {
    constructor(element, config) {
      super(element, config);
      this._isShown = false;
      this._backdrop = this._initializeBackDrop();
      this._focustrap = this._initializeFocusTrap();
      this._addEventListeners();
    }

    // Getters
    static get Default() {
      return Default;
    }
    static get DefaultType() {
      return DefaultType;
    }
    static get NAME() {
      return NAME;
    }

    // Public
    toggle(relatedTarget) {
      return this._isShown ? this.hide() : this.show(relatedTarget);
    }
    show(relatedTarget) {
      if (this._isShown) {
        return;
      }
      const showEvent = EventHandler.trigger(this._element, EVENT_SHOW, {
        relatedTarget
      });
      if (showEvent.defaultPrevented) {
        return;
      }
      this._isShown = true;
      this._backdrop.show();
      if (!this._config.scroll) {
        new ScrollBarHelper().hide();
      }
      this._element.setAttribute('aria-modal', true);
      this._element.setAttribute('role', 'dialog');
      this._element.classList.add(CLASS_NAME_SHOWING);
      const completeCallBack = () => {
        if (!this._config.scroll || this._config.backdrop) {
          this._focustrap.activate();
        }
        this._element.classList.add(CLASS_NAME_SHOW);
        this._element.classList.remove(CLASS_NAME_SHOWING);
        EventHandler.trigger(this._element, EVENT_SHOWN, {
          relatedTarget
        });
      };
      this._queueCallback(completeCallBack, this._element, true);
    }
    hide() {
      if (!this._isShown) {
        return;
      }
      const hideEvent = EventHandler.trigger(this._element, EVENT_HIDE);
      if (hideEvent.defaultPrevented) {
        return;
      }
      this._focustrap.deactivate();
      this._element.blur();
      this._isShown = false;
      this._element.classList.add(CLASS_NAME_HIDING);
      this._backdrop.hide();
      const completeCallback = () => {
        this._element.classList.remove(CLASS_NAME_SHOW, CLASS_NAME_HIDING);
        this._element.removeAttribute('aria-modal');
        this._element.removeAttribute('role');
        if (!this._config.scroll) {
          new ScrollBarHelper().reset();
        }
        EventHandler.trigger(this._element, EVENT_HIDDEN);
      };
      this._queueCallback(completeCallback, this._element, true);
    }
    dispose() {
      this._backdrop.dispose();
      this._focustrap.deactivate();
      super.dispose();
    }

    // Private
    _initializeBackDrop() {
      const clickCallback = () => {
        if (this._config.backdrop === 'static') {
          EventHandler.trigger(this._element, EVENT_HIDE_PREVENTED);
          return;
        }
        this.hide();
      };

      // 'static' option will be translated to true, and booleans will keep their value
      const isVisible = Boolean(this._config.backdrop);
      return new Backdrop({
        className: CLASS_NAME_BACKDROP,
        isVisible,
        isAnimated: true,
        rootElement: this._element.parentNode,
        clickCallback: isVisible ? clickCallback : null
      });
    }
    _initializeFocusTrap() {
      return new FocusTrap({
        trapElement: this._element
      });
    }
    _addEventListeners() {
      EventHandler.on(this._element, EVENT_KEYDOWN_DISMISS, event => {
        if (event.key !== ESCAPE_KEY) {
          return;
        }
        if (this._config.keyboard) {
          this.hide();
          return;
        }
        EventHandler.trigger(this._element, EVENT_HIDE_PREVENTED);
      });
    }

    // Static
    static jQueryInterface(config) {
      return this.each(function () {
        const data = Offcanvas.getOrCreateInstance(this, config);
        if (typeof config !== 'string') {
          return;
        }
        if (data[config] === undefined || config.startsWith('_') || config === 'constructor') {
          throw new TypeError(`No method named "${config}"`);
        }
        data[config](this);
      });
    }
  }

  /**
   * Data API implementation
   */

  EventHandler.on(document, EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
    const target = SelectorEngine.getElementFromSelector(this);
    if (['A', 'AREA'].includes(this.tagName)) {
      event.preventDefault();
    }
    if (index_js.isDisabled(this)) {
      return;
    }
    EventHandler.one(target, EVENT_HIDDEN, () => {
      // focus on trigger when it is closed
      if (index_js.isVisible(this)) {
        this.focus();
      }
    });

    // avoid conflict when clicking a toggler of an offcanvas, while another is open
    const alreadyOpen = SelectorEngine.findOne(OPEN_SELECTOR);
    if (alreadyOpen && alreadyOpen !== target) {
      Offcanvas.getInstance(alreadyOpen).hide();
    }
    const data = Offcanvas.getOrCreateInstance(target);
    data.toggle(this);
  });
  EventHandler.on(window, EVENT_LOAD_DATA_API, () => {
    for (const selector of SelectorEngine.find(OPEN_SELECTOR)) {
      Offcanvas.getOrCreateInstance(selector).show();
    }
  });
  EventHandler.on(window, EVENT_RESIZE, () => {
    for (const element of SelectorEngine.find('[aria-modal][class*=show][class*=offcanvas-]')) {
      if (getComputedStyle(element).position !== 'fixed') {
        Offcanvas.getOrCreateInstance(element).hide();
      }
    }
  });
  componentFunctions_js.enableDismissTrigger(Offcanvas);

  /**
   * jQuery
   */

  index_js.defineJQueryPlugin(Offcanvas);

  return Offcanvas;

}));
//# sourceMappingURL=offcanvas.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/backdrop.js"
/*!*********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/backdrop.js ***!
  \*********************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap backdrop.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./config.js */ "./node_modules/bootstrap/js/dist/util/config.js"), __webpack_require__(/*! ./index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (EventHandler, Config, index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/backdrop.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const NAME = 'backdrop';
  const CLASS_NAME_FADE = 'fade';
  const CLASS_NAME_SHOW = 'show';
  const EVENT_MOUSEDOWN = `mousedown.bs.${NAME}`;
  const Default = {
    className: 'modal-backdrop',
    clickCallback: null,
    isAnimated: false,
    isVisible: true,
    // if false, we use the backdrop helper without adding any element to the dom
    rootElement: 'body' // give the choice to place backdrop under different elements
  };
  const DefaultType = {
    className: 'string',
    clickCallback: '(function|null)',
    isAnimated: 'boolean',
    isVisible: 'boolean',
    rootElement: '(element|string)'
  };

  /**
   * Class definition
   */

  class Backdrop extends Config {
    constructor(config) {
      super();
      this._config = this._getConfig(config);
      this._isAppended = false;
      this._element = null;
    }

    // Getters
    static get Default() {
      return Default;
    }
    static get DefaultType() {
      return DefaultType;
    }
    static get NAME() {
      return NAME;
    }

    // Public
    show(callback) {
      if (!this._config.isVisible) {
        index_js.execute(callback);
        return;
      }
      this._append();
      const element = this._getElement();
      if (this._config.isAnimated) {
        index_js.reflow(element);
      }
      element.classList.add(CLASS_NAME_SHOW);
      this._emulateAnimation(() => {
        index_js.execute(callback);
      });
    }
    hide(callback) {
      if (!this._config.isVisible) {
        index_js.execute(callback);
        return;
      }
      this._getElement().classList.remove(CLASS_NAME_SHOW);
      this._emulateAnimation(() => {
        this.dispose();
        index_js.execute(callback);
      });
    }
    dispose() {
      if (!this._isAppended) {
        return;
      }
      EventHandler.off(this._element, EVENT_MOUSEDOWN);
      this._element.remove();
      this._isAppended = false;
    }

    // Private
    _getElement() {
      if (!this._element) {
        const backdrop = document.createElement('div');
        backdrop.className = this._config.className;
        if (this._config.isAnimated) {
          backdrop.classList.add(CLASS_NAME_FADE);
        }
        this._element = backdrop;
      }
      return this._element;
    }
    _configAfterMerge(config) {
      // use getElement() with the default "body" to get a fresh Element on each instantiation
      config.rootElement = index_js.getElement(config.rootElement);
      return config;
    }
    _append() {
      if (this._isAppended) {
        return;
      }
      const element = this._getElement();
      this._config.rootElement.append(element);
      EventHandler.on(element, EVENT_MOUSEDOWN, () => {
        index_js.execute(this._config.clickCallback);
      });
      this._isAppended = true;
    }
    _emulateAnimation(callback) {
      index_js.executeAfterTransition(callback, this._getElement(), this._config.isAnimated);
    }
  }

  return Backdrop;

}));
//# sourceMappingURL=backdrop.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/component-functions.js"
/*!********************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/component-functions.js ***!
  \********************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {

/*!
  * Bootstrap component-functions.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? factory(exports, __webpack_require__(/*! ../dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ../dom/selector-engine.js */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (exports, EventHandler, SelectorEngine, index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/component-functions.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  const enableDismissTrigger = (component, method = 'hide') => {
    const clickEvent = `click.dismiss${component.EVENT_KEY}`;
    const name = component.NAME;
    EventHandler.on(document, clickEvent, `[data-bs-dismiss="${name}"]`, function (event) {
      if (['A', 'AREA'].includes(this.tagName)) {
        event.preventDefault();
      }
      if (index_js.isDisabled(this)) {
        return;
      }
      const target = SelectorEngine.getElementFromSelector(this) || this.closest(`.${name}`);
      const instance = component.getOrCreateInstance(target);

      // Method argument is left, for Alert and only, as it doesn't implement the 'hide' method
      instance[method]();
    });
  };

  exports.enableDismissTrigger = enableDismissTrigger;

  Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });

}));
//# sourceMappingURL=component-functions.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/config.js"
/*!*******************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/config.js ***!
  \*******************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap config.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../dom/manipulator.js */ "./node_modules/bootstrap/js/dist/dom/manipulator.js"), __webpack_require__(/*! ./index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (Manipulator, index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/config.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Class definition
   */

  class Config {
    // Getters
    static get Default() {
      return {};
    }
    static get DefaultType() {
      return {};
    }
    static get NAME() {
      throw new Error('You have to implement the static method "NAME", for each component!');
    }
    _getConfig(config) {
      config = this._mergeConfigObj(config);
      config = this._configAfterMerge(config);
      this._typeCheckConfig(config);
      return config;
    }
    _configAfterMerge(config) {
      return config;
    }
    _mergeConfigObj(config, element) {
      const jsonConfig = index_js.isElement(element) ? Manipulator.getDataAttribute(element, 'config') : {}; // try to parse

      return {
        ...this.constructor.Default,
        ...(typeof jsonConfig === 'object' ? jsonConfig : {}),
        ...(index_js.isElement(element) ? Manipulator.getDataAttributes(element) : {}),
        ...(typeof config === 'object' ? config : {})
      };
    }
    _typeCheckConfig(config, configTypes = this.constructor.DefaultType) {
      for (const [property, expectedTypes] of Object.entries(configTypes)) {
        const value = config[property];
        const valueType = index_js.isElement(value) ? 'element' : index_js.toType(value);
        if (!new RegExp(expectedTypes).test(valueType)) {
          throw new TypeError(`${this.constructor.NAME.toUpperCase()}: Option "${property}" provided type "${valueType}" but expected type "${expectedTypes}".`);
        }
      }
    }
  }

  return Config;

}));
//# sourceMappingURL=config.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/focustrap.js"
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/focustrap.js ***!
  \**********************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap focustrap.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../dom/event-handler.js */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ../dom/selector-engine.js */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./config.js */ "./node_modules/bootstrap/js/dist/util/config.js")) :
  0;
})(this, (function (EventHandler, SelectorEngine, Config) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/focustrap.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const NAME = 'focustrap';
  const DATA_KEY = 'bs.focustrap';
  const EVENT_KEY = `.${DATA_KEY}`;
  const EVENT_FOCUSIN = `focusin${EVENT_KEY}`;
  const EVENT_KEYDOWN_TAB = `keydown.tab${EVENT_KEY}`;
  const TAB_KEY = 'Tab';
  const TAB_NAV_FORWARD = 'forward';
  const TAB_NAV_BACKWARD = 'backward';
  const Default = {
    autofocus: true,
    trapElement: null // The element to trap focus inside of
  };
  const DefaultType = {
    autofocus: 'boolean',
    trapElement: 'element'
  };

  /**
   * Class definition
   */

  class FocusTrap extends Config {
    constructor(config) {
      super();
      this._config = this._getConfig(config);
      this._isActive = false;
      this._lastTabNavDirection = null;
    }

    // Getters
    static get Default() {
      return Default;
    }
    static get DefaultType() {
      return DefaultType;
    }
    static get NAME() {
      return NAME;
    }

    // Public
    activate() {
      if (this._isActive) {
        return;
      }
      if (this._config.autofocus) {
        this._config.trapElement.focus();
      }
      EventHandler.off(document, EVENT_KEY); // guard against infinite focus loop
      EventHandler.on(document, EVENT_FOCUSIN, event => this._handleFocusin(event));
      EventHandler.on(document, EVENT_KEYDOWN_TAB, event => this._handleKeydown(event));
      this._isActive = true;
    }
    deactivate() {
      if (!this._isActive) {
        return;
      }
      this._isActive = false;
      EventHandler.off(document, EVENT_KEY);
    }

    // Private
    _handleFocusin(event) {
      const {
        trapElement
      } = this._config;
      if (event.target === document || event.target === trapElement || trapElement.contains(event.target)) {
        return;
      }
      const elements = SelectorEngine.focusableChildren(trapElement);
      if (elements.length === 0) {
        trapElement.focus();
      } else if (this._lastTabNavDirection === TAB_NAV_BACKWARD) {
        elements[elements.length - 1].focus();
      } else {
        elements[0].focus();
      }
    }
    _handleKeydown(event) {
      if (event.key !== TAB_KEY) {
        return;
      }
      this._lastTabNavDirection = event.shiftKey ? TAB_NAV_BACKWARD : TAB_NAV_FORWARD;
    }
  }

  return FocusTrap;

}));
//# sourceMappingURL=focustrap.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/index.js"
/*!******************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/index.js ***!
  \******************************************************/
(__unused_webpack_module, exports) {

/*!
  * Bootstrap index.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? factory(exports) :
  0;
})(this, (function (exports) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/index.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  const MAX_UID = 1000000;
  const MILLISECONDS_MULTIPLIER = 1000;
  const TRANSITION_END = 'transitionend';

  /**
   * Properly escape IDs selectors to handle weird IDs
   * @param {string} selector
   * @returns {string}
   */
  const parseSelector = selector => {
    if (selector && window.CSS && window.CSS.escape) {
      // document.querySelector needs escaping to handle IDs (html5+) containing for instance /
      selector = selector.replace(/#([^\s"#']+)/g, (match, id) => `#${CSS.escape(id)}`);
    }
    return selector;
  };

  // Shout-out Angus Croll (https://goo.gl/pxwQGp)
  const toType = object => {
    if (object === null || object === undefined) {
      return `${object}`;
    }
    return Object.prototype.toString.call(object).match(/\s([a-z]+)/i)[1].toLowerCase();
  };

  /**
   * Public Util API
   */

  const getUID = prefix => {
    do {
      prefix += Math.floor(Math.random() * MAX_UID);
    } while (document.getElementById(prefix));
    return prefix;
  };
  const getTransitionDurationFromElement = element => {
    if (!element) {
      return 0;
    }

    // Get transition-duration of the element
    let {
      transitionDuration,
      transitionDelay
    } = window.getComputedStyle(element);
    const floatTransitionDuration = Number.parseFloat(transitionDuration);
    const floatTransitionDelay = Number.parseFloat(transitionDelay);

    // Return 0 if element or transition duration is not found
    if (!floatTransitionDuration && !floatTransitionDelay) {
      return 0;
    }

    // If multiple durations are defined, take the first
    transitionDuration = transitionDuration.split(',')[0];
    transitionDelay = transitionDelay.split(',')[0];
    return (Number.parseFloat(transitionDuration) + Number.parseFloat(transitionDelay)) * MILLISECONDS_MULTIPLIER;
  };
  const triggerTransitionEnd = element => {
    element.dispatchEvent(new Event(TRANSITION_END));
  };
  const isElement = object => {
    if (!object || typeof object !== 'object') {
      return false;
    }
    if (typeof object.jquery !== 'undefined') {
      object = object[0];
    }
    return typeof object.nodeType !== 'undefined';
  };
  const getElement = object => {
    // it's a jQuery object or a node element
    if (isElement(object)) {
      return object.jquery ? object[0] : object;
    }
    if (typeof object === 'string' && object.length > 0) {
      return document.querySelector(parseSelector(object));
    }
    return null;
  };
  const isVisible = element => {
    if (!isElement(element) || element.getClientRects().length === 0) {
      return false;
    }
    const elementIsVisible = getComputedStyle(element).getPropertyValue('visibility') === 'visible';
    // Handle `details` element as its content may falsie appear visible when it is closed
    const closedDetails = element.closest('details:not([open])');
    if (!closedDetails) {
      return elementIsVisible;
    }
    if (closedDetails !== element) {
      const summary = element.closest('summary');
      if (summary && summary.parentNode !== closedDetails) {
        return false;
      }
      if (summary === null) {
        return false;
      }
    }
    return elementIsVisible;
  };
  const isDisabled = element => {
    if (!element || element.nodeType !== Node.ELEMENT_NODE) {
      return true;
    }
    if (element.classList.contains('disabled')) {
      return true;
    }
    if (typeof element.disabled !== 'undefined') {
      return element.disabled;
    }
    return element.hasAttribute('disabled') && element.getAttribute('disabled') !== 'false';
  };
  const findShadowRoot = element => {
    if (!document.documentElement.attachShadow) {
      return null;
    }

    // Can find the shadow root otherwise it'll return the document
    if (typeof element.getRootNode === 'function') {
      const root = element.getRootNode();
      return root instanceof ShadowRoot ? root : null;
    }
    if (element instanceof ShadowRoot) {
      return element;
    }

    // when we don't find a shadow root
    if (!element.parentNode) {
      return null;
    }
    return findShadowRoot(element.parentNode);
  };
  const noop = () => {};

  /**
   * Trick to restart an element's animation
   *
   * @param {HTMLElement} element
   * @return void
   *
   * @see https://www.harrytheo.com/blog/2021/02/restart-a-css-animation-with-javascript/#restarting-a-css-animation
   */
  const reflow = element => {
    element.offsetHeight; // eslint-disable-line no-unused-expressions
  };
  const getjQuery = () => {
    if (window.jQuery && !document.body.hasAttribute('data-bs-no-jquery')) {
      return window.jQuery;
    }
    return null;
  };
  const DOMContentLoadedCallbacks = [];
  const onDOMContentLoaded = callback => {
    if (document.readyState === 'loading') {
      // add listener on the first call when the document is in loading state
      if (!DOMContentLoadedCallbacks.length) {
        document.addEventListener('DOMContentLoaded', () => {
          for (const callback of DOMContentLoadedCallbacks) {
            callback();
          }
        });
      }
      DOMContentLoadedCallbacks.push(callback);
    } else {
      callback();
    }
  };
  const isRTL = () => document.documentElement.dir === 'rtl';
  const defineJQueryPlugin = plugin => {
    onDOMContentLoaded(() => {
      const $ = getjQuery();
      /* istanbul ignore if */
      if ($) {
        const name = plugin.NAME;
        const JQUERY_NO_CONFLICT = $.fn[name];
        $.fn[name] = plugin.jQueryInterface;
        $.fn[name].Constructor = plugin;
        $.fn[name].noConflict = () => {
          $.fn[name] = JQUERY_NO_CONFLICT;
          return plugin.jQueryInterface;
        };
      }
    });
  };
  const execute = (possibleCallback, args = [], defaultValue = possibleCallback) => {
    return typeof possibleCallback === 'function' ? possibleCallback.call(...args) : defaultValue;
  };
  const executeAfterTransition = (callback, transitionElement, waitForTransition = true) => {
    if (!waitForTransition) {
      execute(callback);
      return;
    }
    const durationPadding = 5;
    const emulatedDuration = getTransitionDurationFromElement(transitionElement) + durationPadding;
    let called = false;
    const handler = ({
      target
    }) => {
      if (target !== transitionElement) {
        return;
      }
      called = true;
      transitionElement.removeEventListener(TRANSITION_END, handler);
      execute(callback);
    };
    transitionElement.addEventListener(TRANSITION_END, handler);
    setTimeout(() => {
      if (!called) {
        triggerTransitionEnd(transitionElement);
      }
    }, emulatedDuration);
  };

  /**
   * Return the previous/next element of a list.
   *
   * @param {array} list    The list of elements
   * @param activeElement   The active element
   * @param shouldGetNext   Choose to get next or previous element
   * @param isCycleAllowed
   * @return {Element|elem} The proper element
   */
  const getNextActiveElement = (list, activeElement, shouldGetNext, isCycleAllowed) => {
    const listLength = list.length;
    let index = list.indexOf(activeElement);

    // if the element does not exist in the list return an element
    // depending on the direction and if cycle is allowed
    if (index === -1) {
      return !shouldGetNext && isCycleAllowed ? list[listLength - 1] : list[0];
    }
    index += shouldGetNext ? 1 : -1;
    if (isCycleAllowed) {
      index = (index + listLength) % listLength;
    }
    return list[Math.max(0, Math.min(index, listLength - 1))];
  };

  exports.defineJQueryPlugin = defineJQueryPlugin;
  exports.execute = execute;
  exports.executeAfterTransition = executeAfterTransition;
  exports.findShadowRoot = findShadowRoot;
  exports.getElement = getElement;
  exports.getNextActiveElement = getNextActiveElement;
  exports.getTransitionDurationFromElement = getTransitionDurationFromElement;
  exports.getUID = getUID;
  exports.getjQuery = getjQuery;
  exports.isDisabled = isDisabled;
  exports.isElement = isElement;
  exports.isRTL = isRTL;
  exports.isVisible = isVisible;
  exports.noop = noop;
  exports.onDOMContentLoaded = onDOMContentLoaded;
  exports.parseSelector = parseSelector;
  exports.reflow = reflow;
  exports.toType = toType;
  exports.triggerTransitionEnd = triggerTransitionEnd;

  Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });

}));
//# sourceMappingURL=index.js.map


/***/ },

/***/ "./node_modules/bootstrap/js/dist/util/scrollbar.js"
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/scrollbar.js ***!
  \**********************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

/*!
  * Bootstrap scrollbar.js v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../dom/manipulator.js */ "./node_modules/bootstrap/js/dist/dom/manipulator.js"), __webpack_require__(/*! ../dom/selector-engine.js */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./index.js */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  0;
})(this, (function (Manipulator, SelectorEngine, index_js) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap util/scrollBar.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */


  /**
   * Constants
   */

  const SELECTOR_FIXED_CONTENT = '.fixed-top, .fixed-bottom, .is-fixed, .sticky-top';
  const SELECTOR_STICKY_CONTENT = '.sticky-top';
  const PROPERTY_PADDING = 'padding-right';
  const PROPERTY_MARGIN = 'margin-right';

  /**
   * Class definition
   */

  class ScrollBarHelper {
    constructor() {
      this._element = document.body;
    }

    // Public
    getWidth() {
      // https://developer.mozilla.org/en-US/docs/Web/API/Window/innerWidth#usage_notes
      const documentWidth = document.documentElement.clientWidth;
      return Math.abs(window.innerWidth - documentWidth);
    }
    hide() {
      const width = this.getWidth();
      this._disableOverFlow();
      // give padding to element to balance the hidden scrollbar width
      this._setElementAttributes(this._element, PROPERTY_PADDING, calculatedValue => calculatedValue + width);
      // trick: We adjust positive paddingRight and negative marginRight to sticky-top elements to keep showing fullwidth
      this._setElementAttributes(SELECTOR_FIXED_CONTENT, PROPERTY_PADDING, calculatedValue => calculatedValue + width);
      this._setElementAttributes(SELECTOR_STICKY_CONTENT, PROPERTY_MARGIN, calculatedValue => calculatedValue - width);
    }
    reset() {
      this._resetElementAttributes(this._element, 'overflow');
      this._resetElementAttributes(this._element, PROPERTY_PADDING);
      this._resetElementAttributes(SELECTOR_FIXED_CONTENT, PROPERTY_PADDING);
      this._resetElementAttributes(SELECTOR_STICKY_CONTENT, PROPERTY_MARGIN);
    }
    isOverflowing() {
      return this.getWidth() > 0;
    }

    // Private
    _disableOverFlow() {
      this._saveInitialAttribute(this._element, 'overflow');
      this._element.style.overflow = 'hidden';
    }
    _setElementAttributes(selector, styleProperty, callback) {
      const scrollbarWidth = this.getWidth();
      const manipulationCallBack = element => {
        if (element !== this._element && window.innerWidth > element.clientWidth + scrollbarWidth) {
          return;
        }
        this._saveInitialAttribute(element, styleProperty);
        const calculatedValue = window.getComputedStyle(element).getPropertyValue(styleProperty);
        element.style.setProperty(styleProperty, `${callback(Number.parseFloat(calculatedValue))}px`);
      };
      this._applyManipulationCallback(selector, manipulationCallBack);
    }
    _saveInitialAttribute(element, styleProperty) {
      const actualValue = element.style.getPropertyValue(styleProperty);
      if (actualValue) {
        Manipulator.setDataAttribute(element, styleProperty, actualValue);
      }
    }
    _resetElementAttributes(selector, styleProperty) {
      const manipulationCallBack = element => {
        const value = Manipulator.getDataAttribute(element, styleProperty);
        // We only want to remove the property if the value is `null`; the value can also be zero
        if (value === null) {
          element.style.removeProperty(styleProperty);
          return;
        }
        Manipulator.removeDataAttribute(element, styleProperty);
        element.style.setProperty(styleProperty, value);
      };
      this._applyManipulationCallback(selector, manipulationCallBack);
    }
    _applyManipulationCallback(selector, callBack) {
      if (index_js.isElement(selector)) {
        callBack(selector);
        return;
      }
      for (const sel of SelectorEngine.find(selector, this._element)) {
        callBack(sel);
      }
    }
  }

  return ScrollBarHelper;

}));
//# sourceMappingURL=scrollbar.js.map


/***/ },

/***/ "./node_modules/vanilla-tilt/lib/vanilla-tilt.js"
/*!*******************************************************!*\
  !*** ./node_modules/vanilla-tilt/lib/vanilla-tilt.js ***!
  \*******************************************************/
(module) {

"use strict";


var classCallCheck = function (instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
};

/**
 * Created by Sergiu Șandor (micku7zu) on 1/27/2017.
 * Original idea: https://github.com/gijsroge/tilt.js
 * MIT License.
 * Version 1.8.1
 */

var VanillaTilt = function () {
  function VanillaTilt(element) {
    var settings = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    classCallCheck(this, VanillaTilt);

    if (!(element instanceof Node)) {
      throw "Can't initialize VanillaTilt because " + element + " is not a Node.";
    }

    this.width = null;
    this.height = null;
    this.clientWidth = null;
    this.clientHeight = null;
    this.left = null;
    this.top = null;

    // for Gyroscope sampling
    this.gammazero = null;
    this.betazero = null;
    this.lastgammazero = null;
    this.lastbetazero = null;

    this.transitionTimeout = null;
    this.updateCall = null;
    this.event = null;

    this.updateBind = this.update.bind(this);
    this.resetBind = this.reset.bind(this);

    this.element = element;
    this.settings = this.extendSettings(settings);

    this.reverse = this.settings.reverse ? -1 : 1;
    this.resetToStart = VanillaTilt.isSettingTrue(this.settings["reset-to-start"]);
    this.glare = VanillaTilt.isSettingTrue(this.settings.glare);
    this.glarePrerender = VanillaTilt.isSettingTrue(this.settings["glare-prerender"]);
    this.fullPageListening = VanillaTilt.isSettingTrue(this.settings["full-page-listening"]);
    this.gyroscope = VanillaTilt.isSettingTrue(this.settings.gyroscope);
    this.gyroscopeSamples = this.settings.gyroscopeSamples;

    this.elementListener = this.getElementListener();

    if (this.glare) {
      this.prepareGlare();
    }

    if (this.fullPageListening) {
      this.updateClientSize();
    }

    this.addEventListeners();
    this.reset();

    if (this.resetToStart === false) {
      this.settings.startX = 0;
      this.settings.startY = 0;
    }
  }

  VanillaTilt.isSettingTrue = function isSettingTrue(setting) {
    return setting === "" || setting === true || setting === 1;
  };

  /**
   * Method returns element what will be listen mouse events
   * @return {Node}
   */


  VanillaTilt.prototype.getElementListener = function getElementListener() {
    if (this.fullPageListening) {
      return window.document;
    }

    if (typeof this.settings["mouse-event-element"] === "string") {
      var mouseEventElement = document.querySelector(this.settings["mouse-event-element"]);

      if (mouseEventElement) {
        return mouseEventElement;
      }
    }

    if (this.settings["mouse-event-element"] instanceof Node) {
      return this.settings["mouse-event-element"];
    }

    return this.element;
  };

  /**
   * Method set listen methods for this.elementListener
   * @return {Node}
   */


  VanillaTilt.prototype.addEventListeners = function addEventListeners() {
    this.onMouseEnterBind = this.onMouseEnter.bind(this);
    this.onMouseMoveBind = this.onMouseMove.bind(this);
    this.onMouseLeaveBind = this.onMouseLeave.bind(this);
    this.onWindowResizeBind = this.onWindowResize.bind(this);
    this.onDeviceOrientationBind = this.onDeviceOrientation.bind(this);

    this.elementListener.addEventListener("mouseenter", this.onMouseEnterBind);
    this.elementListener.addEventListener("mouseleave", this.onMouseLeaveBind);
    this.elementListener.addEventListener("mousemove", this.onMouseMoveBind);

    if (this.glare || this.fullPageListening) {
      window.addEventListener("resize", this.onWindowResizeBind);
    }

    if (this.gyroscope) {
      window.addEventListener("deviceorientation", this.onDeviceOrientationBind);
    }
  };

  /**
   * Method remove event listeners from current this.elementListener
   */


  VanillaTilt.prototype.removeEventListeners = function removeEventListeners() {
    this.elementListener.removeEventListener("mouseenter", this.onMouseEnterBind);
    this.elementListener.removeEventListener("mouseleave", this.onMouseLeaveBind);
    this.elementListener.removeEventListener("mousemove", this.onMouseMoveBind);

    if (this.gyroscope) {
      window.removeEventListener("deviceorientation", this.onDeviceOrientationBind);
    }

    if (this.glare || this.fullPageListening) {
      window.removeEventListener("resize", this.onWindowResizeBind);
    }
  };

  VanillaTilt.prototype.destroy = function destroy() {
    clearTimeout(this.transitionTimeout);
    if (this.updateCall !== null) {
      cancelAnimationFrame(this.updateCall);
    }

    this.element.style.willChange = "";
    this.element.style.transition = "";
    this.element.style.transform = "";
    this.resetGlare();

    this.removeEventListeners();
    this.element.vanillaTilt = null;
    delete this.element.vanillaTilt;

    this.element = null;
  };

  VanillaTilt.prototype.onDeviceOrientation = function onDeviceOrientation(event) {
    if (event.gamma === null || event.beta === null) {
      return;
    }

    this.updateElementPosition();

    if (this.gyroscopeSamples > 0) {
      this.lastgammazero = this.gammazero;
      this.lastbetazero = this.betazero;

      if (this.gammazero === null) {
        this.gammazero = event.gamma;
        this.betazero = event.beta;
      } else {
        this.gammazero = (event.gamma + this.lastgammazero) / 2;
        this.betazero = (event.beta + this.lastbetazero) / 2;
      }

      this.gyroscopeSamples -= 1;
    }

    var totalAngleX = this.settings.gyroscopeMaxAngleX - this.settings.gyroscopeMinAngleX;
    var totalAngleY = this.settings.gyroscopeMaxAngleY - this.settings.gyroscopeMinAngleY;

    var degreesPerPixelX = totalAngleX / this.width;
    var degreesPerPixelY = totalAngleY / this.height;

    var angleX = event.gamma - (this.settings.gyroscopeMinAngleX + this.gammazero);
    var angleY = event.beta - (this.settings.gyroscopeMinAngleY + this.betazero);

    var posX = angleX / degreesPerPixelX;
    var posY = angleY / degreesPerPixelY;

    if (this.updateCall !== null) {
      cancelAnimationFrame(this.updateCall);
    }

    this.event = {
      clientX: posX + this.left,
      clientY: posY + this.top
    };

    this.updateCall = requestAnimationFrame(this.updateBind);
  };

  VanillaTilt.prototype.onMouseEnter = function onMouseEnter() {
    this.updateElementPosition();
    this.element.style.willChange = "transform";
    this.setTransition();
  };

  VanillaTilt.prototype.onMouseMove = function onMouseMove(event) {
    if (this.updateCall !== null) {
      cancelAnimationFrame(this.updateCall);
    }

    this.event = event;
    this.updateCall = requestAnimationFrame(this.updateBind);
  };

  VanillaTilt.prototype.onMouseLeave = function onMouseLeave() {
    this.setTransition();

    if (this.settings.reset) {
      requestAnimationFrame(this.resetBind);
    }
  };

  VanillaTilt.prototype.reset = function reset() {
    this.onMouseEnter();

    if (this.fullPageListening) {
      this.event = {
        clientX: (this.settings.startX + this.settings.max) / (2 * this.settings.max) * this.clientWidth,
        clientY: (this.settings.startY + this.settings.max) / (2 * this.settings.max) * this.clientHeight
      };
    } else {
      this.event = {
        clientX: this.left + (this.settings.startX + this.settings.max) / (2 * this.settings.max) * this.width,
        clientY: this.top + (this.settings.startY + this.settings.max) / (2 * this.settings.max) * this.height
      };
    }

    var backupScale = this.settings.scale;
    this.settings.scale = 1;
    this.update();
    this.settings.scale = backupScale;
    this.resetGlare();
  };

  VanillaTilt.prototype.resetGlare = function resetGlare() {
    if (this.glare) {
      this.glareElement.style.transform = "rotate(180deg) translate(-50%, -50%)";
      this.glareElement.style.opacity = "0";
    }
  };

  VanillaTilt.prototype.getValues = function getValues() {
    var x = void 0,
        y = void 0;

    if (this.fullPageListening) {
      x = this.event.clientX / this.clientWidth;
      y = this.event.clientY / this.clientHeight;
    } else {
      x = (this.event.clientX - this.left) / this.width;
      y = (this.event.clientY - this.top) / this.height;
    }

    x = Math.min(Math.max(x, 0), 1);
    y = Math.min(Math.max(y, 0), 1);

    var tiltX = (this.reverse * (this.settings.max - x * this.settings.max * 2)).toFixed(2);
    var tiltY = (this.reverse * (y * this.settings.max * 2 - this.settings.max)).toFixed(2);
    var angle = Math.atan2(this.event.clientX - (this.left + this.width / 2), -(this.event.clientY - (this.top + this.height / 2))) * (180 / Math.PI);

    return {
      tiltX: tiltX,
      tiltY: tiltY,
      percentageX: x * 100,
      percentageY: y * 100,
      angle: angle
    };
  };

  VanillaTilt.prototype.updateElementPosition = function updateElementPosition() {
    var rect = this.element.getBoundingClientRect();

    this.width = this.element.offsetWidth;
    this.height = this.element.offsetHeight;
    this.left = rect.left;
    this.top = rect.top;
  };

  VanillaTilt.prototype.update = function update() {
    var values = this.getValues();

    this.element.style.transform = "perspective(" + this.settings.perspective + "px) " + "rotateX(" + (this.settings.axis === "x" ? 0 : values.tiltY) + "deg) " + "rotateY(" + (this.settings.axis === "y" ? 0 : values.tiltX) + "deg) " + "scale3d(" + this.settings.scale + ", " + this.settings.scale + ", " + this.settings.scale + ")";

    if (this.glare) {
      this.glareElement.style.transform = "rotate(" + values.angle + "deg) translate(-50%, -50%)";
      this.glareElement.style.opacity = "" + values.percentageY * this.settings["max-glare"] / 100;
    }

    this.element.dispatchEvent(new CustomEvent("tiltChange", {
      "detail": values
    }));

    this.updateCall = null;
  };

  /**
   * Appends the glare element (if glarePrerender equals false)
   * and sets the default style
   */


  VanillaTilt.prototype.prepareGlare = function prepareGlare() {
    // If option pre-render is enabled we assume all html/css is present for an optimal glare effect.
    if (!this.glarePrerender) {
      // Create glare element
      var jsTiltGlare = document.createElement("div");
      jsTiltGlare.classList.add("js-tilt-glare");

      var jsTiltGlareInner = document.createElement("div");
      jsTiltGlareInner.classList.add("js-tilt-glare-inner");

      jsTiltGlare.appendChild(jsTiltGlareInner);
      this.element.appendChild(jsTiltGlare);
    }

    this.glareElementWrapper = this.element.querySelector(".js-tilt-glare");
    this.glareElement = this.element.querySelector(".js-tilt-glare-inner");

    if (this.glarePrerender) {
      return;
    }

    Object.assign(this.glareElementWrapper.style, {
      "position": "absolute",
      "top": "0",
      "left": "0",
      "width": "100%",
      "height": "100%",
      "overflow": "hidden",
      "pointer-events": "none",
      "border-radius": "inherit"
    });

    Object.assign(this.glareElement.style, {
      "position": "absolute",
      "top": "50%",
      "left": "50%",
      "pointer-events": "none",
      "background-image": "linear-gradient(0deg, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 100%)",
      "transform": "rotate(180deg) translate(-50%, -50%)",
      "transform-origin": "0% 0%",
      "opacity": "0"
    });

    this.updateGlareSize();
  };

  VanillaTilt.prototype.updateGlareSize = function updateGlareSize() {
    if (this.glare) {
      var glareSize = (this.element.offsetWidth > this.element.offsetHeight ? this.element.offsetWidth : this.element.offsetHeight) * 2;

      Object.assign(this.glareElement.style, {
        "width": glareSize + "px",
        "height": glareSize + "px"
      });
    }
  };

  VanillaTilt.prototype.updateClientSize = function updateClientSize() {
    this.clientWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

    this.clientHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
  };

  VanillaTilt.prototype.onWindowResize = function onWindowResize() {
    this.updateGlareSize();
    this.updateClientSize();
  };

  VanillaTilt.prototype.setTransition = function setTransition() {
    var _this = this;

    clearTimeout(this.transitionTimeout);
    this.element.style.transition = this.settings.speed + "ms " + this.settings.easing;
    if (this.glare) this.glareElement.style.transition = "opacity " + this.settings.speed + "ms " + this.settings.easing;

    this.transitionTimeout = setTimeout(function () {
      _this.element.style.transition = "";
      if (_this.glare) {
        _this.glareElement.style.transition = "";
      }
    }, this.settings.speed);
  };

  /**
   * Method return patched settings of instance
   * @param {boolean} settings.reverse - reverse the tilt direction
   * @param {number} settings.max - max tilt rotation (degrees)
   * @param {startX} settings.startX - the starting tilt on the X axis, in degrees. Default: 0
   * @param {startY} settings.startY - the starting tilt on the Y axis, in degrees. Default: 0
   * @param {number} settings.perspective - Transform perspective, the lower the more extreme the tilt gets
   * @param {string} settings.easing - Easing on enter/exit
   * @param {number} settings.scale - 2 = 200%, 1.5 = 150%, etc..
   * @param {number} settings.speed - Speed of the enter/exit transition
   * @param {boolean} settings.transition - Set a transition on enter/exit
   * @param {string|null} settings.axis - What axis should be enabled. Can be "x" or "y"
   * @param {boolean} settings.glare - if it should have a "glare" effect
   * @param {number} settings.max-glare - the maximum "glare" opacity (1 = 100%, 0.5 = 50%)
   * @param {boolean} settings.glare-prerender - false = VanillaTilt creates the glare elements for you, otherwise
   * @param {boolean} settings.full-page-listening - If true, parallax effect will listen to mouse move events on the whole document, not only the selected element
   * @param {string|object} settings.mouse-event-element - String selector or link to HTML-element what will be listen mouse events
   * @param {boolean} settings.reset - false = If the tilt effect has to be reset on exit
   * @param {boolean} settings.reset-to-start - true = On reset event (mouse leave) will return to initial start angle (if startX or startY is set)
   * @param {gyroscope} settings.gyroscope - Enable tilting by deviceorientation events
   * @param {gyroscopeSensitivity} settings.gyroscopeSensitivity - Between 0 and 1 - The angle at which max tilt position is reached. 1 = 90deg, 0.5 = 45deg, etc..
   * @param {gyroscopeSamples} settings.gyroscopeSamples - How many gyroscope moves to decide the starting position.
   */


  VanillaTilt.prototype.extendSettings = function extendSettings(settings) {
    var defaultSettings = {
      reverse: false,
      max: 15,
      startX: 0,
      startY: 0,
      perspective: 1000,
      easing: "cubic-bezier(.03,.98,.52,.99)",
      scale: 1,
      speed: 300,
      transition: true,
      axis: null,
      glare: false,
      "max-glare": 1,
      "glare-prerender": false,
      "full-page-listening": false,
      "mouse-event-element": null,
      reset: true,
      "reset-to-start": true,
      gyroscope: true,
      gyroscopeMinAngleX: -45,
      gyroscopeMaxAngleX: 45,
      gyroscopeMinAngleY: -45,
      gyroscopeMaxAngleY: 45,
      gyroscopeSamples: 10
    };

    var newSettings = {};
    for (var property in defaultSettings) {
      if (property in settings) {
        newSettings[property] = settings[property];
      } else if (this.element.hasAttribute("data-tilt-" + property)) {
        var attribute = this.element.getAttribute("data-tilt-" + property);
        try {
          newSettings[property] = JSON.parse(attribute);
        } catch (e) {
          newSettings[property] = attribute;
        }
      } else {
        newSettings[property] = defaultSettings[property];
      }
    }

    return newSettings;
  };

  VanillaTilt.init = function init(elements, settings) {
    if (elements instanceof Node) {
      elements = [elements];
    }

    if (elements instanceof NodeList) {
      elements = [].slice.call(elements);
    }

    if (!(elements instanceof Array)) {
      return;
    }

    elements.forEach(function (element) {
      if (!("vanillaTilt" in element)) {
        element.vanillaTilt = new VanillaTilt(element, settings);
      }
    });
  };

  return VanillaTilt;
}();

if (typeof document !== "undefined") {
  /* expose the class to window */
  window.VanillaTilt = VanillaTilt;

  /**
   * Auto load
   */
  VanillaTilt.init(document.querySelectorAll("[data-tilt]"));
}

module.exports = VanillaTilt;


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
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!***********************************!*\
  !*** ./resources/js/metronome.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var bootstrap_js_dist_offcanvas__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap/js/dist/offcanvas */ "./node_modules/bootstrap/js/dist/offcanvas.js");
/* harmony import */ var bootstrap_js_dist_offcanvas__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dist_offcanvas__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! bootstrap/js/dist/dropdown */ "./node_modules/bootstrap/js/dist/dropdown.js");
/* harmony import */ var bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _metronome_clock__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./metronome/clock */ "./resources/js/metronome/clock.js");
/* harmony import */ var _metronome_gesture__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./metronome/gesture */ "./resources/js/metronome/gesture.js");
/* harmony import */ var _metronome_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./metronome/settings */ "./resources/js/metronome/settings.js");
/* harmony import */ var _metronome_conductor__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./metronome/conductor */ "./resources/js/metronome/conductor.js");
/* harmony import */ var _metronome_voice_recorder__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./metronome/voice-recorder */ "./resources/js/metronome/voice-recorder.js");
/* harmony import */ var _metronome_sound_menu__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./metronome/sound-menu */ "./resources/js/metronome/sound-menu.js");
/* harmony import */ var _controls_font_select__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./controls/font-select */ "./resources/js/controls/font-select.js");
/* harmony import */ var _components_logo_tilt__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/logo-tilt */ "./resources/js/components/logo-tilt.js");
/* harmony import */ var _components_logo_tilt__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_components_logo_tilt__WEBPACK_IMPORTED_MODULE_9__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }










_components_logo_tilt__WEBPACK_IMPORTED_MODULE_9___default()(window);
var surface = document.querySelector('[data-metronome]');
var pulseLayer = document.querySelector('[data-metronome-pulse]');
var number = document.querySelector('[data-tempo]');
var message = document.querySelector('[data-metronome-message]');
var edgeButtons = document.querySelectorAll('[data-tempo-step]');
var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
var settingsButton = document.querySelector('[data-settings-open]');
var settingsPanel = document.querySelector('[data-settings-panel]');
var settingsOffcanvas = new (bootstrap_js_dist_offcanvas__WEBPACK_IMPORTED_MODULE_0___default())(settingsPanel);
var hubMenu = document.querySelector('#public-hub-menu');
var beatInputs = document.querySelectorAll('[name="metronome-beats"]');
var typeInputs = document.querySelectorAll('[name="metronome-type"]');
var markerInputs = document.querySelectorAll('[name="metronome-marker"]');
var conductorOptions = document.querySelector('[data-conductor-options]');
var colorInput = document.querySelector('[data-settings-color]');
var colorValue = document.querySelector('[data-color-value]');
var colorReset = document.querySelector('[data-settings-color-reset]');
var soundInput = document.querySelector('#metronome-sound');
var fontInput = document.querySelector('#metronome-font');
var fontMenuRoot = document.querySelector('[data-font-select]');
var fontMenu = fontMenuRoot ? new _controls_font_select__WEBPACK_IMPORTED_MODULE_8__.FontSelect(fontMenuRoot, (bootstrap_js_dist_dropdown__WEBPACK_IMPORTED_MODULE_1___default())) : null;
var pathInput = document.querySelector('[name="metronome-path"]');
var conductor = new _metronome_conductor__WEBPACK_IMPORTED_MODULE_5__.ConductorView(document.querySelector('[data-conductor]'), {
  pulseLayer: pulseLayer,
  number: number
});
var tempo = _metronome_gesture__WEBPACK_IMPORTED_MODULE_3__.DEFAULT_TEMPO;
var tempoSaveTimer = null;
var gesture = null;
var pointerId = null;
var suppressClick = false;
var pulse = null;
var backgroundPulse = null;
var backgroundPulseTempo = _metronome_gesture__WEBPACK_IMPORTED_MODULE_3__.DEFAULT_TEMPO;
var edgePress = null;
var edgeRepeatTimer = null;
var settingsOpen = false;
var navigationOpen = false;
var settings = _objectSpread({}, _metronome_settings__WEBPACK_IMPORTED_MODULE_4__.DEFAULT_SETTINGS);
var voiceRecorder = null;
var soundMenu = null;
try {
  settings = (0,_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.loadSettings)(window.localStorage);
  tempo = (0,_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.loadTempo)(window.localStorage);
} catch (error) {
  // Private browsing can make storage unavailable; all controls still work.
}
var palette = (0,_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.colorPalette)(settings.color);
var clock = new _metronome_clock__WEBPACK_IMPORTED_MODULE_2__.MetronomeClock({
  createContext: function createContext() {
    var AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) throw new Error('Web Audio is unavailable.');
    return new AudioContext({
      latencyHint: 'interactive'
    });
  },
  onStateChange: function onStateChange(running) {
    surface.setAttribute('aria-pressed', String(running));
    updateLabel();
    if (!running && pulse) pulse.cancel();
    if (!running && backgroundPulse) backgroundPulse.cancel();
    syncConductor();
  },
  onBeat: function onBeat(beat) {
    if (settings.type === 'conductor') {
      return;
    }
    if (reducedMotion.matches) return;
    if (beat === 0) {
      if (backgroundPulse) backgroundPulse.cancel();
      backgroundPulseTempo = tempo;
      backgroundPulse = pulseLayer.animate([{
        opacity: 1
      }, {
        opacity: 0
      }], {
        duration: 60000 / tempo,
        easing: 'ease-out'
      });
    }
    if (pulse) pulse.cancel();
    pulse = number.animate([{
      transform: 'scale(1.35)',
      opacity: 1
    }, {
      transform: 'scale(1)',
      opacity: 0.88
    }], {
      duration: 180,
      easing: 'ease-out'
    });
  },
  onReady: function onReady() {
    return syncConductor(true);
  },
  onTick: function onTick() {
    if (settings.type === 'conductor') syncConductor();
  },
  onError: function onError() {
    message.textContent = 'Sound couldn’t start. Tap to try again.';
    message.hidden = false;
  }
});
function updateLabel() {
  surface.setAttribute('aria-label', "".concat(clock.running ? 'Stop' : 'Start', " metronome. ").concat(tempo, " beats per minute."));
  edgeButtons.forEach(function (button) {
    var action = Number(button.dataset.tempoStep) > 0 ? 'Increase' : 'Decrease';
    button.setAttribute('aria-label', "".concat(action, " tempo by 1. ").concat(tempo, " beats per minute."));
  });
}
function setTempo(value) {
  var previousTempo = tempo;
  tempo = Math.round((0,_metronome_gesture__WEBPACK_IMPORTED_MODULE_3__.clampTempo)(value));
  number.textContent = String(tempo);
  fontMenu === null || fontMenu === void 0 || fontMenu.render();
  if (backgroundPulse && backgroundPulse.playState === 'running') {
    // Keep the current fade in step with tempo changes during a swipe.
    backgroundPulse.updatePlaybackRate(tempo / backgroundPulseTempo);
  }
  clock.setTempo(tempo);
  if (tempo !== previousTempo) syncConductor(true);
  updateLabel();
  if (tempo !== previousTempo) {
    clearTimeout(tempoSaveTimer);
    // Only save a tempo that has settled; settings edits and playback
    // controls must not save an intermediate swipe value early.
    tempoSaveTimer = setTimeout(function () {
      tempoSaveTimer = null;
      try {
        window.localStorage.setItem(_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.TEMPO_KEY, JSON.stringify(tempo));
      } catch (error) {
        // Tempo controls remain usable when storage is unavailable.
      }
    }, 5000);
  }
}
function toggle() {
  if (settingsOpen || navigationOpen) return;
  message.hidden = true;
  if (clock.running) clock.stop();else clock.start({
    leadInBeats: settings.type === 'conductor' ? (0,_metronome_conductor__WEBPACK_IMPORTED_MODULE_5__.conductorPreparationBeats)(settings.beats) : 0
  });
}
function syncConductor() {
  var force = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  if (settings.type !== 'conductor') {
    conductor.stop();
    return;
  }
  var position = clock.getBeatPosition();
  if (reducedMotion.matches) position.progress = 0;
  var animate = clock.ready && !position.waiting && !settingsOpen && !navigationOpen && !document.hidden && !reducedMotion.matches;
  // After a scheduler stall, align to the next real click, not an imagined
  // beat beyond the last sound that was actually scheduled.
  if (animate && position.progress === 1 && !force) return;
  conductor.sync(position, position.tempo, animate, force);
}
function applySettings(value) {
  var _soundMenu;
  var persist = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  var previous = settings;
  settings = (0,_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.normalizeSettings)(value);
  if (settings.sound === 'my-voice' && !clock.voiceBuffers) settings.sound = 'click';
  palette = (0,_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.colorPalette)(settings.color);
  if (previous.type !== settings.type) {
    if (pulse) pulse.cancel();
    if (backgroundPulse) backgroundPulse.cancel();
  }
  if (previous.color !== settings.color && backgroundPulse) backgroundPulse.cancel();
  document.documentElement.style.setProperty('--metronome-color', settings.color);
  document.documentElement.style.setProperty('--metronome-pulse', palette.pulse);
  document.documentElement.style.setProperty('--metronome-ink', palette.ink);
  document.documentElement.style.setProperty('--site-header-ink', palette.headerInk);
  document.querySelector('meta[name="theme-color"]').setAttribute('content', settings.color);
  surface.dataset.mode = settings.type;
  surface.dataset.showPath = String(settings.showPath);
  surface.dataset.conductorMarker = settings.conductorMarker;
  number.dataset.numberFont = settings.font;
  fontInput.value = settings.font;
  fontMenu === null || fontMenu === void 0 || fontMenu.render();
  clock.setBeats(settings.beats);
  clock.setSound(settings.sound);
  if (conductor.beats !== settings.beats) conductor.setBeats(settings.beats);
  beatInputs.forEach(function (input) {
    input.checked = Number(input.value) === settings.beats;
  });
  typeInputs.forEach(function (input) {
    input.checked = input.value === settings.type;
  });
  markerInputs.forEach(function (input) {
    input.checked = input.value === settings.conductorMarker;
  });
  conductorOptions.hidden = settings.type !== 'conductor';
  colorInput.value = settings.color;
  colorValue.textContent = settings.color.toUpperCase();
  if (voiceRecorder) voiceRecorder.syncSound(settings.sound);else soundInput.value = settings.sound;
  (_soundMenu = soundMenu) === null || _soundMenu === void 0 || _soundMenu.render(voiceRecorder);
  pathInput.checked = settings.showPath;
  syncConductor();
  if (persist) {
    try {
      // The voice choice belongs to this account; guests and another
      // account on the same browser retain an ordinary instrument sound.
      window.localStorage.setItem(_metronome_settings__WEBPACK_IMPORTED_MODULE_4__.SETTINGS_KEY, JSON.stringify(_objectSpread(_objectSpread({}, settings), {}, {
        sound: settings.sound === 'my-voice' ? 'click' : settings.sound
      })));
    } catch (error) {
      // Settings remain usable for the current visit.
    }
  }
}
settingsButton.addEventListener('click', function () {
  if (!settingsOpen) settingsOffcanvas.show(settingsButton);
});
settingsPanel.addEventListener('show.bs.offcanvas', function () {
  settingsOpen = true;
  stopEdgeRepeat();
  gesture = null;
  pointerId = null;
  surface.classList.remove('is-dragging');
  if (voiceRecorder && !voiceRecorder.ready) voiceRecorder.load();
  settingsButton.setAttribute('aria-expanded', 'true');
  syncConductor();
});
settingsPanel.addEventListener('hide.bs.offcanvas', function () {
  var _soundMenu2, _voiceRecorder;
  (_soundMenu2 = soundMenu) === null || _soundMenu2 === void 0 || _soundMenu2.close();
  (_voiceRecorder = voiceRecorder) === null || _voiceRecorder === void 0 || _voiceRecorder.cancel();
});
settingsPanel.addEventListener('hidden.bs.offcanvas', function () {
  settingsOpen = false;
  settingsButton.setAttribute('aria-expanded', 'false');
  settingsButton.focus();
  syncConductor();
});
hubMenu === null || hubMenu === void 0 || hubMenu.addEventListener('show.bs.offcanvas', function () {
  navigationOpen = true;
  stopEdgeRepeat();
  gesture = null;
  pointerId = null;
  surface.classList.remove('is-dragging');
  syncConductor();
});
hubMenu === null || hubMenu === void 0 || hubMenu.addEventListener('hidden.bs.offcanvas', function () {
  navigationOpen = false;
  syncConductor();
});
beatInputs.forEach(function (input) {
  return input.addEventListener('change', function () {
    if (input.checked) applySettings(_objectSpread(_objectSpread({}, settings), {}, {
      beats: Number(input.value)
    }));
  });
});
typeInputs.forEach(function (input) {
  return input.addEventListener('change', function () {
    if (input.checked) applySettings(_objectSpread(_objectSpread({}, settings), {}, {
      type: input.value
    }));
  });
});
markerInputs.forEach(function (input) {
  return input.addEventListener('change', function () {
    if (input.checked) applySettings(_objectSpread(_objectSpread({}, settings), {}, {
      conductorMarker: input.value
    }));
  });
});
colorInput.addEventListener('input', function () {
  return applySettings(_objectSpread(_objectSpread({}, settings), {}, {
    color: colorInput.value
  }));
});
fontInput.addEventListener('change', function () {
  return applySettings(_objectSpread(_objectSpread({}, settings), {}, {
    font: fontInput.value
  }));
});
colorReset.addEventListener('click', function () {
  return applySettings(_objectSpread(_objectSpread({}, settings), {}, {
    color: _metronome_settings__WEBPACK_IMPORTED_MODULE_4__.DEFAULT_SETTINGS.color
  }));
});
soundInput.addEventListener('change', function () {
  var _voiceRecorder2, _voiceRecorder3;
  if ((_voiceRecorder2 = voiceRecorder) !== null && _voiceRecorder2 !== void 0 && _voiceRecorder2.selectSound(soundInput.value)) return;
  applySettings(_objectSpread(_objectSpread({}, settings), {}, {
    sound: soundInput.value
  }));
  (_voiceRecorder3 = voiceRecorder) === null || _voiceRecorder3 === void 0 || _voiceRecorder3.remember(settings.sound);
});
pathInput.addEventListener('change', function () {
  return applySettings(_objectSpread(_objectSpread({}, settings), {}, {
    showPath: pathInput.checked
  }));
});
reducedMotion.addEventListener('change', function () {
  if (reducedMotion.matches) {
    if (pulse) pulse.cancel();
    if (backgroundPulse) backgroundPulse.cancel();
  }
  syncConductor();
});
surface.addEventListener('pointerdown', function (event) {
  if (settingsOpen || navigationOpen || !event.isPrimary || event.button !== 0 || pointerId !== null || edgePress) return;
  pointerId = event.pointerId;
  suppressClick = false;
  gesture = new _metronome_gesture__WEBPACK_IMPORTED_MODULE_3__.TempoGesture(tempo, event.clientX, event.clientY, event.timeStamp);
  surface.setPointerCapture(pointerId);
});
function movePointer(event) {
  if (event.pointerId !== pointerId || !gesture) return;
  setTempo(gesture.move(event.clientX, event.clientY, event.timeStamp));
  suppressClick = gesture.moved;
  surface.classList.toggle('is-dragging', gesture.moved);
}
surface.addEventListener('pointermove', movePointer);
surface.addEventListener('pointerup', function (event) {
  if (event.pointerId !== pointerId) return;
  movePointer(event);
  gesture = null;
  pointerId = null;
  surface.classList.remove('is-dragging');
});
function cancelGesture(event) {
  if (event.pointerId !== pointerId) return;
  suppressClick = true;
  gesture = null;
  pointerId = null;
  surface.classList.remove('is-dragging');
}
surface.addEventListener('pointercancel', cancelGesture);
surface.addEventListener('lostpointercapture', cancelGesture);
surface.addEventListener('click', function (event) {
  // Keyboard and assistive-technology clicks have no pointer click count.
  if (event.detail > 0 && suppressClick) {
    event.preventDefault();
    return;
  }
  toggle();
});
function stopEdgeRepeat() {
  clearTimeout(edgeRepeatTimer);
  edgeRepeatTimer = null;
  edgePress = null;
}
function repeatEdgeStep() {
  if (!edgePress) return;
  setTempo(tempo + edgePress.step);
  edgeRepeatTimer = setTimeout(repeatEdgeStep, 75);
}
edgeButtons.forEach(function (button) {
  var step = Number(button.dataset.tempoStep);
  button.addEventListener('pointerdown', function (event) {
    if (settingsOpen || navigationOpen || !event.isPrimary || event.button !== 0 || pointerId !== null || edgePress) return;
    edgePress = {
      button: button,
      pointerId: event.pointerId,
      step: step
    };
    button.setPointerCapture(event.pointerId);
    setTempo(tempo + step);
    edgeRepeatTimer = setTimeout(repeatEdgeStep, 350);
  });
  var release = function release(event) {
    if (edgePress && edgePress.button === button && edgePress.pointerId === event.pointerId) {
      stopEdgeRepeat();
    }
  };
  button.addEventListener('pointerup', release);
  button.addEventListener('pointercancel', release);
  button.addEventListener('lostpointercapture', release);
  button.addEventListener('contextmenu', function (event) {
    return event.preventDefault();
  });
  button.addEventListener('click', function (event) {
    // Pointer presses already step immediately; keep native keyboard/AT clicks.
    if (!settingsOpen && !navigationOpen && event.detail === 0) setTempo(tempo + step);
  });
});
document.addEventListener('keydown', function (event) {
  if (settingsOpen || navigationOpen || event.altKey || event.ctrlKey || event.metaKey) return;
  if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
    event.preventDefault();
    setTempo(tempo + (event.key === 'ArrowUp' ? 1 : -1) * (event.shiftKey ? 10 : 1));
  } else if (event.code === 'Space' || event.key === 'Enter') {
    var _event$target;
    if ((_event$target = event.target) !== null && _event$target !== void 0 && _event$target.closest('a[href], [data-tempo-step], [data-settings-open], button:not([data-metronome])')) return;
    event.preventDefault();
    if (!event.repeat) toggle();
  } else if (event.key === 'Escape') {
    stopEdgeRepeat();
    clock.stop();
  }
});
window.addEventListener('blur', stopEdgeRepeat);
document.addEventListener('visibilitychange', function () {
  var _soundMenu3, _voiceRecorder4;
  if (document.hidden) (_soundMenu3 = soundMenu) === null || _soundMenu3 === void 0 || _soundMenu3.close();
  if (document.hidden) (_voiceRecorder4 = voiceRecorder) === null || _voiceRecorder4 === void 0 || _voiceRecorder4.cancel();
  if (document.hidden) stopEdgeRepeat();
  syncConductor();
});
window.addEventListener('pagehide', function () {
  var _soundMenu4, _voiceRecorder5;
  (_soundMenu4 = soundMenu) === null || _soundMenu4 === void 0 || _soundMenu4.close();
  (_voiceRecorder5 = voiceRecorder) === null || _voiceRecorder5 === void 0 || _voiceRecorder5.cancel();
  stopEdgeRepeat();
  clock.releaseAudio();
});
applySettings(settings, false);
setTempo(tempo);
var soundMenuRoot = document.querySelector('[data-sound-menu]');
if (soundMenuRoot) {
  soundMenu = new _metronome_sound_menu__WEBPACK_IMPORTED_MODULE_7__.SoundMenu({
    root: soundMenuRoot,
    input: soundInput,
    onSelect: function onSelect(value) {
      soundInput.value = value;
      soundInput.dispatchEvent(new Event('change', {
        bubbles: true
      }));
    },
    onPlay: function onPlay(id) {
      var _voiceRecorder6;
      return (_voiceRecorder6 = voiceRecorder) === null || _voiceRecorder6 === void 0 ? void 0 : _voiceRecorder6.previewRecording(id);
    },
    onEdit: function onEdit(id) {
      var _voiceRecorder7;
      return (_voiceRecorder7 = voiceRecorder) === null || _voiceRecorder7 === void 0 ? void 0 : _voiceRecorder7.edit(id);
    },
    onDelete: function onDelete(id) {
      var _voiceRecorder8;
      return (_voiceRecorder8 = voiceRecorder) === null || _voiceRecorder8 === void 0 ? void 0 : _voiceRecorder8.confirmDelete(id);
    },
    onRecord: function onRecord() {
      var _voiceRecorder9;
      return (_voiceRecorder9 = voiceRecorder) === null || _voiceRecorder9 === void 0 ? void 0 : _voiceRecorder9.record();
    },
    onClose: function onClose() {
      var _voiceRecorder0;
      return (_voiceRecorder0 = voiceRecorder) === null || _voiceRecorder0 === void 0 ? void 0 : _voiceRecorder0.stopPreview();
    }
  });
  soundMenu.render();
}
var voiceRoot = document.querySelector('[data-voice-recorder]');
if (voiceRoot) {
  voiceRecorder = new _metronome_voice_recorder__WEBPACK_IMPORTED_MODULE_6__.VoiceRecorder({
    root: voiceRoot,
    dialog: settingsPanel,
    clock: clock,
    soundInput: soundInput,
    getBeats: function getBeats() {
      return settings.beats;
    },
    onUpdate: function onUpdate(recorder) {
      var _soundMenu5;
      return (_soundMenu5 = soundMenu) === null || _soundMenu5 === void 0 ? void 0 : _soundMenu5.render(recorder);
    },
    useSound: function useSound(sound) {
      var persist = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      return applySettings(_objectSpread(_objectSpread({}, settings), {}, {
        sound: sound
      }), persist);
    }
  });
  if (new URLSearchParams(window.location.search).get('record-voice') === '1') settingsButton.click();
}
})();

/******/ })()
;