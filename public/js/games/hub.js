/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@mojs/core/dist/mo.umd.js"
/*!************************************************!*\
  !*** ./node_modules/@mojs/core/dist/mo.umd.js ***!
  \************************************************/
(module) {

/*!
  @mojs/core – The motion graphics toolbelt for the web
  Oleg Solomka @legomushroom 2023 MIT
  1.7.1
*/
var t,e;t=self,e=()=>(()=>{var t={50:(t,e,s)=>{"use strict";s.d(e,{Z:()=>n});var r=s(2),i={_sample:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:4,s=(0,r.Z)(e),n={};if("number"===s){var a=0,o=Math.pow(10,e),l=1/o;n[0]=t(0);for(var h=0;h<o-1;h++)a+=l,n[parseFloat(a.toFixed(e))]=t(a);n[1]=t(1),n.base=e}else"object"===s?n=e:"string"===s&&(n=JSON.parse(e));return i._sample._proximate(n)},_proximate:function(t){var e=t.base,s=1/Math.pow(10,e),r=function(r){var i,n,a=function(t,e){e=+e||0;var s=Math.pow(10,e);return Math.round(t*s)/s}(r,e),o=t[a.toString()];if(Math.abs(r-a)<s)return o;var l=(n=r>a?t[i=a+s]:t[i=a-s])-o;return l<s?o:o+(r-a)/(i-a)*(n>o?-1:1)*l};return r.getSamples=function(){return t},r}};i._sample._proximate=i._proximate;const n=i._sample},973:(t,e,s)=>{"use strict";s.d(e,{Z:()=>l});var r=s(2),i=s(671),n=s(144),a=s(52),o=s.n(a);const l=function(){function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};(0,i.Z)(this,t),this._o=e,this._index=this._o.index||0,this._arrayPropertyMap={strokeDashoffset:1,strokeDasharray:1,origin:1},this._skipPropsDelta={timeline:1,prevChainModule:1,callbacksContext:1},this._declareDefaults(),this._extendDefaults(),this._vars(),this._render()}return(0,n.Z)(t,[{key:"_declareDefaults",value:function(){this._defaults={}}},{key:"_vars",value:function(){this._progress=0,this._strokeDasharrayBuffer=[]}},{key:"_render",value:function(){}},{key:"_setProp",value:function(t,e){if("object"===(0,r.Z)(t))for(var s in t)this._assignProp(s,t[s]);else this._assignProp(t,e)}},{key:"_assignProp",value:function(t,e){this._props[t]=e}},{key:"_show",value:function(){var t=this._props;this.el&&(t.isSoftHide?this._showByTransform():this.el.style.display="block",this._isShown=!0)}},{key:"_hide",value:function(){this.el&&(this._props.isSoftHide?o().setPrefixedStyle(this.el,"transform","scale(0)"):this.el.style.display="none",this._isShown=!1)}},{key:"_showByTransform",value:function(){}},{key:"_parseOptionString",value:function(t){return"string"==typeof t&&t.match(/stagger/)&&(t=o().parseStagger(t,this._index)),"string"==typeof t&&t.match(/rand/)&&(t=o().parseRand(t)),t}},{key:"_parsePositionOption",value:function(t,e){return o().unitOptionMap[t]&&(e=o().parseUnit(e).string),e}},{key:"_parseStrokeDashOption",value:function(t,e){var s=e;if(this._arrayPropertyMap[t])switch(s=[],(0,r.Z)(e)){case"number":s.push(o().parseUnit(e));break;case"string":for(var i=e.split(" "),n=0;n<i.length;n++)s.push(o().parseUnit(i[n]))}return s}},{key:"_isDelta",value:function(t){var e=o().isObject(t);return!(!(e=e&&!t.unit)||o().isArray(t)||o().isDOM(t))}},{key:"_getDelta",value:function(t,e){var s;if("left"!==t&&"top"!==t||this._o.ctx||o().warn("Consider to animate x/y properties instead of left/top,\n        as it would be much more performant",e),!this._skipPropsDelta||!this._skipPropsDelta[t]){null!=(s=o().parseDelta(t,e,this._index)).type&&(this._deltas[t]=s);var i="object"===(0,r.Z)(s.end)?0===s.end.value?0:s.end.string:s.end;this._props[t]=i}}},{key:"_extendDefaults",value:function(){for(var t in this._props={},this._deltas={},this._defaults){var e=null!=this._o[t]?this._o[t]:this._defaults[t];this._parseOption(t,e)}}},{key:"_tuneNewOptions",value:function(t){for(var e in this._hide(),t)t&&delete this._deltas[e],this._o[e]=t[e],this._parseOption(e,t[e])}},{key:"_parseOption",value:function(t,e){if(this._isDelta(e)&&!this._skipPropsDelta[t]){this._getDelta(t,e);var s=o().getDeltaEnd(e);return this._assignProp(t,this._parseProperty(t,s))}this._assignProp(t,this._parseProperty(t,e))}},{key:"_parsePreArrayProperty",value:function(t,e){return e=this._parseOptionString(e),this._parsePositionOption(t,e)}},{key:"_parseProperty",value:function(t,e){return"parent"===t?o().parseEl(e):(e=this._parsePreArrayProperty(t,e),this._parseStrokeDashOption(t,e))}},{key:"_parseDeltaValues",value:function(t,e){var s={};for(var r in e){var i=e[r],n=this._parsePreArrayProperty(t,i);s[this._parsePreArrayProperty(t,r)]=n}return s}},{key:"_preparsePropValue",value:function(t,e){return this._isDelta(e)?this._parseDeltaValues(t,e):this._parsePreArrayProperty(t,e)}},{key:"_calcCurrentProps",value:function(t,e){for(var s in this._deltas){var r=this._deltas[s],i=!!r.curve,n=null==r.easing||i?t:r.easing(e);if("array"===r.type){var a;o().isArray(this._props[s])?(a=this._props[s]).length=0:a=[];for(var l=i?r.curve(e):null,h=0;h<r.delta.length;h++){var u=r.delta[h],p=i?l*(r.start[h].value+e*u.value):r.start[h].value+n*u.value;a.push({string:"".concat(p).concat(u.unit),value:p,unit:u.unit})}this._props[s]=a}else if("number"===r.type)this._props[s]=i?r.curve(e)*(r.start+e*r.delta):r.start+n*r.delta;else if("unit"===r.type){var c=i?r.curve(e)*(r.start.value+e*r.delta):r.start.value+n*r.delta;this._props[s]="".concat(c).concat(r.end.unit)}else if("color"===r.type){var d,_,f,v;if(i){var y=r.curve(e);d=parseInt(y*(r.start.r+e*r.delta.r),10),_=parseInt(y*(r.start.g+e*r.delta.g),10),f=parseInt(y*(r.start.b+e*r.delta.b),10),v=parseFloat(y*(r.start.a+e*r.delta.a))}else d=parseInt(r.start.r+n*r.delta.r,10),_=parseInt(r.start.g+n*r.delta.g,10),f=parseInt(r.start.b+n*r.delta.b,10),v=parseFloat(r.start.a+n*r.delta.a);this._props[s]="rgba(".concat(d,",").concat(_,",").concat(f,",").concat(v,")")}}}},{key:"_setProgress",value:function(t,e){this._progress=t,this._calcCurrentProps(t,e)}}]),t}()},623:(t,e,s)=>{"use strict";s.d(e,{Z:()=>c});var r=s(2),i=s(671),n=s(144),a=s(340),o=s(963),l=s(120),h=s(52),u=s.n(h);function p(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,l.Z)(t);if(e){var i=(0,l.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,o.Z)(this,s)}}const c=function(t){(0,a.Z)(s,t);var e=p(s);function s(){return(0,i.Z)(this,s),e.apply(this,arguments)}return(0,n.Z)(s,[{key:"_declareDefaults",value:function(){this._defaults={ns:"http://www.w3.org/2000/svg",tag:"ellipse",parent:document.body,ratio:1,radius:50,radiusX:null,radiusY:null,stroke:"hotpink","stroke-dasharray":"","stroke-dashoffset":"","stroke-linecap":"","stroke-width":2,"stroke-opacity":1,fill:"transparent","fill-opacity":1,width:0,height:0},this._drawMap=["stroke","stroke-width","stroke-opacity","stroke-dasharray","fill","stroke-dashoffset","stroke-linecap","fill-opacity","transform"]}},{key:"_vars",value:function(){this._state={},this._drawMapLength=this._drawMap.length}},{key:"_render",value:function(){this._isRendered||(this._isRendered=!0,this._createSVGCanvas(),this._setCanvasSize(),this._props.parent.appendChild(this._canvas))}},{key:"_createSVGCanvas",value:function(){var t=this._props;this._canvas=document.createElementNS(t.ns,"svg"),this.el=document.createElementNS(t.ns,t.tag),this._canvas.appendChild(this.el)}},{key:"_setCanvasSize",value:function(){var t=this._canvas.style;t.display="block",t.width="100%",t.height="100%",t.left="0px",t.top="0px"}},{key:"_draw",value:function(){this._props.length=this._getLength();for(var t=this._drawMapLength;t--;){var e=this._drawMap[t];switch(e){case"stroke-dasharray":case"stroke-dashoffset":this.castStrokeDash(e)}this._setAttrIfChanged(e,this._props[e])}this._state.radius=this._props.radius}},{key:"castStrokeDash",value:function(t){var e=this._props;if(u().isArray(e[t])){for(var s="",i=0;i<e[t].length;i++){var n=e[t][i],a="%"===n.unit?this.castPercent(n.value):n.value;s+="".concat(a," ")}return e[t]="0 "===s?s="":s,e[t]=s}"object"===(0,r.Z)(e[t])&&(s="%"===e[t].unit?this.castPercent(e[t].value):e[t].value,e[t]=0===s?s="":s)}},{key:"castPercent",value:function(t){return t*(this._props.length/100)}},{key:"_setAttrIfChanged",value:function(t,e){this._state[t]!==e&&(this.el.setAttribute(t,e),this._state[t]=e)}},{key:"_getLength",value:function(){var t=this._props;return this.el&&this.el.getTotalLength&&this.el.getAttribute("d")?this.el.getTotalLength():2*(null!=t.radiusX?t.radiusX:t.radius)}},{key:"_getPointsPerimiter",value:function(t){for(var e=0,s=1;s<t.length;s++)e+=this._pointsDelta(t[s-1],t[s]);return e+=this._pointsDelta(t[0],u().getLastItem(t))}},{key:"_pointsDelta",value:function(t,e){var s=Math.abs(t.x-e.x),r=Math.abs(t.y-e.y);return Math.sqrt(s*s+r*r)}},{key:"_setSize",value:function(t,e){var s=this._props;s.width=t,s.height=e,this._draw()}}]),s}(s(973).Z)},472:(t,e,s)=>{"use strict";s.d(e,{Z:()=>u});var r=s(671),i=s(144),n=s(752),a=s(340),o=s(963),l=s(120);function h(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,l.Z)(t);if(e){var i=(0,l.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,o.Z)(this,s)}}const u=function(t){(0,a.Z)(s,t);var e=h(s);function s(){return(0,r.Z)(this,s),e.apply(this,arguments)}return(0,i.Z)(s,[{key:"_declareDefaults",value:function(){(0,n.Z)((0,l.Z)(s.prototype),"_declareDefaults",this).call(this),this._defaults.tag="path"}},{key:"_draw",value:function(){(0,n.Z)((0,l.Z)(s.prototype),"_draw",this).call(this);var t=this._props,e=null!=t.radiusX?t.radiusX:t.radius,r=null!=t.radiusY?t.radiusY:t.radius,i=e===this._prevRadiusX,a=r===this._prevRadiusY,o=t.points===this._prevPoints;if(!(i&&a&&o)){var h=t.width/2,u=t.height/2,p=h+e,c="M".concat(h-e," ").concat(u," Q ").concat(h," ").concat(u-2*r," ").concat(p," ").concat(u);this.el.setAttribute("d",c),this._prevPoints=t.points,this._prevRadiusX=e,this._prevRadiusY=r}}},{key:"_getLength",value:function(){var t=this._props,e=null!=t.radiusX?t.radiusX:t.radius,s=null!=t.radiusY?t.radiusY:t.radius,r=e+s,i=Math.sqrt((3*e+s)*(e+3*s));return.5*Math.PI*(3*r-i)}}]),s}(s(623).Z)},854:(t,e,s)=>{"use strict";s.d(e,{Z:()=>u});var r=s(671),i=s(144),n=s(752),a=s(340),o=s(963),l=s(120);function h(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,l.Z)(t);if(e){var i=(0,l.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,o.Z)(this,s)}}const u=function(t){(0,a.Z)(s,t);var e=h(s);function s(){return(0,r.Z)(this,s),e.apply(this,arguments)}return(0,i.Z)(s,[{key:"_declareDefaults",value:function(){(0,n.Z)((0,l.Z)(s.prototype),"_declareDefaults",this).call(this),this._defaults.tag="path",this._defaults.parent=null;for(var t=0;t<this._drawMap.length;t++)"stroke-width"===this._drawMap[t]&&this._drawMap.splice(t,1)}},{key:"getShape",value:function(){return""}},{key:"getLength",value:function(){return 100}},{key:"_draw",value:function(){var t=this._props,e=this._state,r=e.radiusX!==t.radiusX,i=e.radiusY!==t.radiusY,a=e.radius!==t.radius;(r||i||a)&&(this.el.setAttribute("transform",this._getScale()),e.radiusX=t.radiusX,e.radiusY=t.radiusY,e.radius=t.radius),this._setAttrIfChanged("stroke-width",t["stroke-width"]/t.maxScale),(0,n.Z)((0,l.Z)(s.prototype),"_draw",this).call(this)}},{key:"_render",value:function(){if(!this._isRendered){this._isRendered=!0,this._length=this.getLength();var t=this._props;t.parent.innerHTML='<svg id="js-mojs-shape-canvas" xmlns="http://www.w3.org/2000/svg" xlink="http://www.w3.org/1999/xlink"><g id="js-mojs-shape-el">'.concat(this.getShape(),"</g></svg>"),this._canvas=t.parent.querySelector("#js-mojs-shape-canvas"),this.el=t.parent.querySelector("#js-mojs-shape-el"),this._setCanvasSize()}}},{key:"_getScale",value:function(){var t=this._props,e=t.radiusX?t.radiusX:t.radius,s=t.radiusY?t.radiusY:t.radius;t.scaleX=2*e/100,t.scaleY=2*s/100,t.maxScale=Math.max(t.scaleX,t.scaleY),t.shiftX=t.width/2-50*t.scaleX,t.shiftY=t.height/2-50*t.scaleY;var r="translate(".concat(t.shiftX,", ").concat(t.shiftY,")");return"".concat(r," scale(").concat(t.scaleX,", ").concat(t.scaleY,")")}},{key:"_getLength",value:function(){return this._length}}]),s}(s(623).Z)},342:(t,e,s)=>{"use strict";s.d(e,{Z:()=>d});var r=s(671),i=s(144),n=s(340),a=s(963),o=s(752),l=s(120),h=s(52),u=s.n(h),p=s(755);function c(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,l.Z)(t);if(e){var i=(0,l.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,a.Z)(this,s)}}const d=function(t){(0,n.Z)(s,t);var e=c(s);function s(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return(0,r.Z)(this,s),e.call(this,t)}return(0,i.Z)(s,[{key:"add",value:function(){for(var t=arguments.length,e=new Array(t),s=0;s<t;s++)e[s]=arguments[s];return this._pushTimelineArray(e),this._calcDimentions(),this}},{key:"append",value:function(){for(var t=arguments.length,e=new Array(t),s=0;s<t;s++)e[s]=arguments[s];for(var r=0,i=e;r<i.length;r++){var n=i[r];u().isArray(n)?this._appendTimelineArray(n):this._appendTimeline(n,this._timelines.length),this._calcDimentions()}return this}},{key:"stop",value:function(t){return(0,o.Z)((0,l.Z)(s.prototype),"stop",this).call(this,t),this._stopChildren(t),this}},{key:"reset",value:function(){return(0,o.Z)((0,l.Z)(s.prototype),"reset",this).call(this),this._resetChildren(),this}},{key:"_resetChildren",value:function(){for(var t=0;t<this._timelines.length;t++)this._timelines[t].reset()}},{key:"_stopChildren",value:function(t){for(var e=this._timelines.length-1;e>=0;e--)this._timelines[e].stop(t)}},{key:"_appendTimelineArray",value:function(t){for(var e=t.length,s=this._props.repeatTime-this._props.delay,r=this._timelines.length;e--;)this._appendTimeline(t[e],r,s)}},{key:"_appendTimeline",value:function(t,e,r){t.timeline instanceof s&&(t=t.timeline),t.tween instanceof p.Z&&(t=t.tween);var i=null!=r?r:this._props.duration;i+=t._props.shiftTime||0,t.index=e,this._pushTimeline(t,i)}},{key:"_pushTimelineArray",value:function(t){for(var e=0;e<t.length;e++){var s=t[e];u().isArray(s)?this._pushTimelineArray(s):this._pushTimeline(s)}}},{key:"_pushTimeline",value:function(t,e){t.timeline instanceof s&&(t=t.timeline),t.tween instanceof p.Z&&(t=t.tween),null!=e&&t._setProp({shiftTime:e}),this._timelines.push(t),this._recalcDuration(t)}},{key:"_setProgress",value:function(t,e,s){this._updateChildren(t,e,s),p.Z.prototype._setProgress.call(this,t,e)}},{key:"_updateChildren",value:function(t,e,s){var r=e>this._prevTime?-1:1;this._props.isYoyo&&s&&(r*=-1);for(var i=this._props.startTime+t*this._props.duration,n=i+r,a=this._timelines.length,o=0;o<a;o++){var l=i>n?o:a-1-o;this._timelines[l]._update(i,n,this._prevYoyo,this._onEdge)}this._prevYoyo=s}},{key:"_recalcDuration",value:function(t){var e=t._props,s=e.repeatTime/e.speed+(e.shiftTime||0)+t._negativeShift;this._props.duration=Math.max(s,this._props.duration)}},{key:"_recalcTotalDuration",value:function(){var t=this._timelines.length;for(this._props.duration=0;t--;){var e=this._timelines[t];e._recalcTotalDuration&&e._recalcTotalDuration(),this._recalcDuration(e)}this._calcDimentions()}},{key:"_setStartTime",value:function(t){var e=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];(0,o.Z)((0,l.Z)(s.prototype),"_setStartTime",this).call(this,t),this._startTimelines(this._props.startTime,e)}},{key:"_startTimelines",value:function(t){var e=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],s="stop"===this._state;null==t&&(t=this._props.startTime);for(var r=0;r<this._timelines.length;r++){var i=this._timelines[r];i._setStartTime(t,e),e||null==i._prevTime||s||(i._prevTime=i._normPrevTimeForward())}}},{key:"_refresh",value:function(t){for(var e=this._timelines.length,r=0;r<e;r++)this._timelines[r]._refresh(t);(0,o.Z)((0,l.Z)(s.prototype),"_refresh",this).call(this,t)}},{key:"_declareDefaults",value:function(){null!=this._o.duration&&(u().error('Duration can not be declared on Timeline, but "'.concat(this._o.duration,'" is. You probably want to use Tween instead.')),this._o.duration=0),(0,o.Z)((0,l.Z)(s.prototype),"_declareDefaults",this).call(this),this._defaults.duration=0,this._defaults.easing="Linear.None",this._defaults.backwardEasing="Linear.None",this._defaults.nameBase="Timeline"}},{key:"_vars",value:function(){this._timelines=[],(0,o.Z)((0,l.Z)(s.prototype),"_vars",this).call(this)}}]),s}(p.Z)},755:(t,e,s)=>{"use strict";s.d(e,{Z:()=>_});var r=s(671),i=s(144),n=s(326),a=s(752),o=s(340),l=s(963),h=s(120),u=s(47),p=s(283),c=s.n(p);function d(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,h.Z)(t);if(e){var i=(0,h.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,l.Z)(this,s)}}const _=function(t){(0,o.Z)(s,t);var e=d(s);function s(){var t,i=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return(0,r.Z)(this,s),null==(t=e.call(this,i))._props.name&&t._setSelfName(),(0,l.Z)(t,(0,n.Z)(t))}return(0,i.Z)(s,[{key:"_declareDefaults",value:function(){this._defaults={duration:350,delay:0,repeat:0,speed:1,isYoyo:!1,easing:"Sin.Out",backwardEasing:null,name:null,nameBase:"Tween",onProgress:null,onStart:null,onRefresh:null,onComplete:null,onRepeatStart:null,onRepeatComplete:null,onFirstUpdate:null,onUpdate:null,isChained:!1,onPlaybackStart:null,onPlaybackPause:null,onPlaybackStop:null,onPlaybackComplete:null,callbacksContext:null}}},{key:"play",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;return"play"===this._state&&this._isRunning||(this._props.isReversed=!1,this._subPlay(t,"play"),this._setPlaybackState("play")),this}},{key:"playBackward",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;return"reverse"===this._state&&this._isRunning||(this._props.isReversed=!0,this._subPlay(t,"reverse"),this._setPlaybackState("reverse")),this}},{key:"pause",value:function(){return"pause"===this._state||"stop"===this._state||(this._removeFromTweener(),this._setPlaybackState("pause")),this}},{key:"stop",value:function(t){if("stop"===this._state)return this;this._wasUknownUpdate=void 0;var e=null!=t?t:"reverse"===this._state?1:0;return this.setProgress(e),this.reset(),this}},{key:"replay",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;return this.reset(),this.play(t),this}},{key:"replayBackward",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;return this.reset(),this.playBackward(t),this}},{key:"resume",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;if("pause"!==this._state)return this;switch(this._prevState){case"play":this.play(t);break;case"reverse":this.playBackward(t)}return this}},{key:"setProgress",value:function(t){var e=this._props;return!e.startTime&&this._setStartTime(),this._playTime=null,t<0&&(t=0),t>1&&(t=1),this._update(e.startTime-e.delay+t*e.repeatTime),this}},{key:"setSpeed",value:function(t){return this._props.speed=t,"play"!==this._state&&"reverse"!==this._state||this._setResumeTime(this._state),this}},{key:"reset",value:function(){return this._removeFromTweener(),this._setPlaybackState("stop"),this._progressTime=0,this._isCompleted=!1,this._isStarted=!1,this._isFirstUpdate=!1,this._wasUknownUpdate=void 0,this._prevTime=void 0,this._prevYoyo=void 0,this._props.isReversed=!1,this}},{key:"_subPlay",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0,e=arguments.length>1?arguments[1]:void 0,s=this._props,r=this._state,i=this._prevState,n="pause"===r,a=("play"===r||n&&"play"===i)&&"reverse"===e||("reverse"===r||n&&"reverse"===i)&&"play"===e;return this._progressTime=this._progressTime>=s.repeatTime?0:this._progressTime,a&&(this._progressTime=s.repeatTime-this._progressTime),this._setResumeTime(e,t),u.Z.add(this),this}},{key:"_setResumeTime",value:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0;this._resumeTime=performance.now();var s=this._resumeTime-Math.abs(e)-this._progressTime;this._setStartTime(s,!1),null!=this._prevTime&&(this._prevTime="play"===t?this._normPrevTimeForward():this._props.endTime-this._progressTime)}},{key:"_normPrevTimeForward",value:function(){var t=this._props;return t.startTime+this._progressTime-t.delay}},{key:"_setSelfName",value:function(){var t="_".concat(this._props.nameBase,"s");u.Z[t]=null==u.Z[t]?1:++u.Z[t],this._props.name="".concat(this._props.nameBase," ").concat(u.Z[t])}},{key:"_setPlaybackState",value:function(t){this._prevState=this._state,this._state=t;var e="pause"===this._prevState,s="stop"===this._prevState,r="play"===this._prevState,i="reverse"===this._prevState,n=r||i;"play"!==t&&"reverse"!==t||!(s||e)||this._playbackStart(),"pause"===t&&n&&this._playbackPause(),"stop"===t&&(n||e)&&this._playbackStop()}},{key:"_vars",value:function(){return this.progress=0,this._prevTime=void 0,this._progressTime=0,this._negativeShift=0,this._state="stop",this._props.delay<0&&(this._negativeShift=this._props.delay,this._props.delay=0),this._calcDimentions()}},{key:"_calcDimentions",value:function(){this._props.time=this._props.duration+this._props.delay,this._props.repeatTime=this._props.time*(this._props.repeat+1)}},{key:"_extendDefaults",value:function(){this._callbackOverrides=this._o.callbackOverrides||{},delete this._o.callbackOverrides,(0,a.Z)((0,h.Z)(s.prototype),"_extendDefaults",this).call(this);var t=this._props;t.easing=c().parseEasing(t.easing),t.easing._parent=this,null!=t.backwardEasing&&(t.backwardEasing=c().parseEasing(t.backwardEasing),t.backwardEasing._parent=this)}},{key:"_setStartTime",value:function(t){var e=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],s=this._props,r=s.shiftTime||0;e&&(this._isCompleted=!1,this._isRepeatCompleted=!1,this._isStarted=!1);var i=null==t?performance.now():t;return s.startTime=i+s.delay+this._negativeShift+r,s.endTime=s.startTime+s.repeatTime-s.delay,this._playTime=null!=this._resumeTime?this._resumeTime:i+r,this._resumeTime=null,this}},{key:"_update",value:function(t,e,s,r){var i=this._props;null==this._prevTime&&null!=e&&(this._props.speed&&this._playTime&&(this._prevTime=this._playTime+this._props.speed*(e-this._playTime)),this._wasUknownUpdate=!0);var n=i.startTime-i.delay;if(i.speed&&this._playTime&&(t=this._playTime+i.speed*(t-this._playTime)),Math.abs(i.endTime-t)<1e-8&&(t=i.endTime),r&&null!=s){var a=this._getPeriod(t),o=!(!i.isYoyo||!this._props.repeat||a%2!=1);if(this._timelines)for(var l=0;l<this._timelines.length;l++)this._timelines[l]._update(t,e,s,r);1===r?s?(this._prevTime=t+1,this._repeatStart(t,o),this._start(t,o)):(this._prevTime=t-1,this._repeatComplete(t,o),this._complete(t,o)):-1===r&&(s?(this._prevTime=t-1,this._repeatComplete(t,o),this._complete(t,o)):this._prevTime>=i.startTime&&this._prevTime<=i.endTime&&(this._prevTime=t+1,this._repeatStart(t,o),this._start(t,o),this._isCompleted=!0)),this._prevTime=void 0}return t>n&&t<i.endTime?this._progressTime=t-n:t<=n?this._progressTime=0:t>=i.endTime&&(this._progressTime=i.repeatTime+1e-11),i.isReversed&&(t=i.endTime-this._progressTime),null==this._prevTime?(this._prevTime=t,this._wasUknownUpdate=!0,!1):(t>=n&&t<=i.endTime&&this._progress((t-n)/i.repeatTime,t),t>=i.startTime&&t<=i.endTime?this._updateInActiveArea(t):this._isInActiveArea?this._updateInInactiveArea(t):this._isRefreshed||t<i.startTime&&0!==this.progress&&(this._refresh(!0),this._isRefreshed=!0),this._prevTime=t,t>=i.endTime||t<=n)}},{key:"_updateInInactiveArea",value:function(t){if(this._isInActiveArea){var e=this._props;if(t>e.endTime&&!this._isCompleted){this._progress(1,t);var s=this._getPeriod(e.endTime),r=e.isYoyo&&s%2==0;this._setProgress(r?0:1,t,r),this._repeatComplete(t,r),this._complete(t,r)}t<this._prevTime&&t<e.startTime&&!this._isStarted&&!this._isCompleted&&(this._progress(0,t,!1),this._setProgress(0,t,!1),this._isRepeatStart=!1,this._repeatStart(t,!1),this._start(t,!1)),this._isInActiveArea=!1}}},{key:"_updateInActiveArea",value:function(t){var e=this._props,s=e.delay+e.duration,r=e.startTime-e.delay,i=(t-e.startTime+e.delay)%s,n=Math.round((e.endTime-e.startTime+e.delay)/s),a=this._getPeriod(t),o=this._delayT,l=this._getPeriod(this._prevTime),h=this._delayT,u=e.isYoyo&&a%2==1,p=e.isYoyo&&l%2==1,c=u?1:0;if(t===e.endTime)return this._wasUknownUpdate=!1,u=e.isYoyo&&(a-1)%2==1,this._setProgress(u?0:1,t,u),t>this._prevTime&&(this._isRepeatCompleted=!1),this._repeatComplete(t,u),this._complete(t,u);if(this._isCompleted=!1,this._isRefreshed=!1,r+i>=e.startTime){this._isInActiveArea=!0,this._isRepeatCompleted=!1,this._isRepeatStart=!1,this._isStarted=!1;var d=(t-e.startTime)%s/e.duration,_=a>0&&l<a,f=l>a;if(this._onEdge=0,_&&(this._onEdge=1),f&&(this._onEdge=-1),this._wasUknownUpdate&&(t>this._prevTime&&(this._start(t,u),this._repeatStart(t,u),this._firstUpdate(t,u)),t<this._prevTime&&(this._complete(t,u),this._repeatComplete(t,u),this._firstUpdate(t,u),this._isCompleted=!1)),_){if(1!==this.progress){var v=e.isYoyo&&(a-1)%2==1;this._repeatComplete(t,v)}l>=0&&this._repeatStart(t,u)}t>this._prevTime&&(!this._isStarted&&this._prevTime<=e.startTime&&(this._start(t,u),this._repeatStart(t,u),this._isStarted=!1,this._isRepeatStart=!1),this._firstUpdate(t,u)),f&&(0!==this.progress&&1!==this.progress&&l!=n&&this._repeatStart(t,p),l!==n||this._wasUknownUpdate||(this._complete(t,u),this._repeatComplete(t,u),this._firstUpdate(t,u),this._isCompleted=!1),this._repeatComplete(t,u)),"delay"===l&&(a<h&&this._repeatComplete(t,u),a===h&&a>0&&this._repeatStart(t,u)),t>this._prevTime?(0===d&&this._repeatStart(t,u),t!==e.endTime&&this._setProgress(u?1-d:d,t,u)):(t!==e.endTime&&this._setProgress(u?1-d:d,t,u),0===d&&this._repeatStart(t,u)),t===e.startTime&&this._start(t,u)}else if(this._isInActiveArea){var y="delay"===a?o:a,m=t>this._prevTime;m&&y--,c=e.isYoyo&&y%2==1?1:0,t<this._prevTime&&(this._setProgress(c,t,1===c),this._repeatStart(t,1===c)),this._setProgress(m?1-c:c,t,1===c),t>this._prevTime&&(0===this.progress&&1!==c||this._repeatComplete(t,1===c)),this._isInActiveArea=!1}this._wasUknownUpdate=!1}},{key:"_removeFromTweener",value:function(){return u.Z.remove(this),this}},{key:"_getPeriod",value:function(t){var e=this._props,s=e.delay+e.duration,r=e.delay+t-e.startTime,i=r/s,n=t<e.endTime?r%s:0;return i=t>=e.endTime?Math.round(i):Math.floor(i),t>e.endTime?i=Math.round((e.endTime-e.startTime+e.delay)/s):n>0&&n<e.delay&&(this._delayT=i,i="delay"),i}},{key:"_setProgress",value:function(t,e,s){var r=this._props,i=r.wasYoyo!==s,n=e>this._prevTime;if(this.progress=t,n&&!s||!n&&s)this.easedProgress=r.easing(t);else if(!n&&!s||n&&s){var a=null!=r.backwardEasing?r.backwardEasing:r.easing;this.easedProgress=a(t)}return(r.prevEasedProgress!==this.easedProgress||i)&&null!=r.onUpdate&&"function"==typeof r.onUpdate&&r.onUpdate.call(r.callbacksContext||this,this.easedProgress,this.progress,n,s),r.prevEasedProgress=this.easedProgress,r.wasYoyo=s,this}},{key:"_start",value:function(t,e){if(!this._isStarted){var s=this._props;null!=s.onStart&&"function"==typeof s.onStart&&s.onStart.call(s.callbacksContext||this,t>this._prevTime,e),this._isCompleted=!1,this._isStarted=!0,this._isFirstUpdate=!1}}},{key:"_playbackStart",value:function(){var t=this._props;null!=t.onPlaybackStart&&"function"==typeof t.onPlaybackStart&&t.onPlaybackStart.call(t.callbacksContext||this)}},{key:"_playbackPause",value:function(){var t=this._props;null!=t.onPlaybackPause&&"function"==typeof t.onPlaybackPause&&t.onPlaybackPause.call(t.callbacksContext||this)}},{key:"_playbackStop",value:function(){var t=this._props;null!=t.onPlaybackStop&&"function"==typeof t.onPlaybackStop&&t.onPlaybackStop.call(t.callbacksContext||this)}},{key:"_playbackComplete",value:function(){var t=this._props;null!=t.onPlaybackComplete&&"function"==typeof t.onPlaybackComplete&&t.onPlaybackComplete.call(t.callbacksContext||this)}},{key:"_complete",value:function(t,e){if(!this._isCompleted){var s=this._props;null!=s.onComplete&&"function"==typeof s.onComplete&&s.onComplete.call(s.callbacksContext||this,t>this._prevTime,e),this._isCompleted=!0,this._isStarted=!1,this._isFirstUpdate=!1,this._prevYoyo=void 0}}},{key:"_firstUpdate",value:function(t,e){if(!this._isFirstUpdate){var s=this._props;null!=s.onFirstUpdate&&"function"==typeof s.onFirstUpdate&&(s.onFirstUpdate.tween=this,s.onFirstUpdate.call(s.callbacksContext||this,t>this._prevTime,e)),this._isFirstUpdate=!0}}},{key:"_repeatComplete",value:function(t,e){if(!this._isRepeatCompleted){var s=this._props;null!=s.onRepeatComplete&&"function"==typeof s.onRepeatComplete&&s.onRepeatComplete.call(s.callbacksContext||this,t>this._prevTime,e),this._isRepeatCompleted=!0}}},{key:"_repeatStart",value:function(t,e){if(!this._isRepeatStart){var s=this._props;null!=s.onRepeatStart&&"function"==typeof s.onRepeatStart&&s.onRepeatStart.call(s.callbacksContext||this,t>this._prevTime,e),this._isRepeatStart=!0}}},{key:"_progress",value:function(t,e){var s=this._props;null!=s.onProgress&&"function"==typeof s.onProgress&&s.onProgress.call(s.callbacksContext||this,t,e>this._prevTime)}},{key:"_refresh",value:function(t){var e=this._props;if(null!=e.onRefresh){var s=e.callbacksContext||this,r=t?0:1;e.onRefresh.call(s,t,e.easing(r),r)}}},{key:"_onTweenerRemove",value:function(){}},{key:"_onTweenerFinish",value:function(){this._setPlaybackState("stop"),this._playbackComplete()}},{key:"_setProp",value:function(t,e){(0,a.Z)((0,h.Z)(s.prototype),"_setProp",this).call(this,t,e),this._calcDimentions()}},{key:"_assignProp",value:function(t,e){null==e&&(e=this._defaults[t]),"easing"===t&&((e=c().parseEasing(e))._parent=this);var r=this._callbackOverrides[t],i=!e||!e.isMojsCallbackOverride;r&&i&&(e=this._overrideCallback(e,r)),(0,a.Z)((0,h.Z)(s.prototype),"_assignProp",this).call(this,t,e)}},{key:"_overrideCallback",value:function(t,e){var s=t&&"function"==typeof t,r=function(){s&&t.apply(this,arguments),e.apply(this,arguments)};return r.isMojsCallbackOverride=!0,r}}]),s}(s(973).Z)},47:(t,e,s)=>{"use strict";s.d(e,{Z:()=>n});var r=s(671),i=s(144);const n=new(function(){function t(){return(0,r.Z)(this,t),this._vars(),this._listenVisibilityChange(),this}return(0,i.Z)(t,[{key:"_vars",value:function(){this.tweens=[],this._savedTweens=[],this._loop=this._loop.bind(this),this._onVisibilityChange=this._onVisibilityChange.bind(this)}},{key:"_loop",value:function(){return!!this._isRunning&&(this._update(window.performance.now()),this.tweens.length?(requestAnimationFrame(this._loop),this):this._isRunning=!1)}},{key:"_startLoop",value:function(){this._isRunning||(this._isRunning=!0,requestAnimationFrame(this._loop))}},{key:"_stopLoop",value:function(){this._isRunning=!1}},{key:"_update",value:function(t){for(var e=this.tweens.length;e--;){var s=this.tweens[e];s&&!0===s._update(t)&&(this.remove(s),s._onTweenerFinish(),s._prevTime=void 0)}}},{key:"add",value:function(t){t._isRunning||(t._isRunning=!0,this.tweens.push(t),this._startLoop())}},{key:"removeAll",value:function(){this.tweens.length=0}},{key:"remove",value:function(t){var e="number"==typeof t?t:this.tweens.indexOf(t);-1!==e&&(t=this.tweens[e])&&(t._isRunning=!1,this.tweens.splice(e,1),t._onTweenerRemove())}},{key:"_listenVisibilityChange",value:function(){void 0!==document.hidden?(this._visibilityHidden="hidden",this._visibilityChange="visibilitychange"):void 0!==document.mozHidden?(this._visibilityHidden="mozHidden",this._visibilityChange="mozvisibilitychange"):void 0!==document.msHidden?(this._visibilityHidden="msHidden",this._visibilityChange="msvisibilitychange"):void 0!==document.webkitHidden&&(this._visibilityHidden="webkitHidden",this._visibilityChange="webkitvisibilitychange"),document.addEventListener(this._visibilityChange,this._onVisibilityChange,!1)}},{key:"_onVisibilityChange",value:function(){document[this._visibilityHidden]?this._savePlayingTweens():this._restorePlayingTweens()}},{key:"_savePlayingTweens",value:function(){this._savedTweens=this.tweens.slice(0);for(var t=0;t<this._savedTweens.length;t++)this._savedTweens[t].pause()}},{key:"_restorePlayingTweens",value:function(){for(var t=0;t<this._savedTweens.length;t++)this._savedTweens[t].resume()}}]),t}())},977:(t,e,s)=>{var r,i;i=s(52),r=new class{constructor(t){return this.vars(),this.generate}vars(){return this.generate=i.bind(this.generate,this)}generate(t,e,s,r){var i,n,a,o,l,h,u,p,c,d,_,f,v,y,m,g,k,w;if(arguments.length<4)return this.error("Bezier function expects 4 arguments");for(f=v=0;v<4;f=++v)if("number"!=typeof(o=arguments[f])||isNaN(o)||!isFinite(o))return this.error("Bezier function expects 4 arguments");return t<0||t>1||s<0||s>1?this.error("Bezier x values should be > 0 and < 1"):(y=.1,c=!!Float32Array,i=function(t,e){return 1-3*e+3*t},n=function(t,e){return 3*e-6*t},a=function(t){return 3*t},h=function(t,e,s){return((i(e,s)*t+n(e,s))*t+a(e))*t},d=function(t,e,s){return 3*i(e,s)*t*t+2*n(e,s)*t+a(e)},g=function(e,r){var i;for(f=0;f<4;){if(0===(i=d(r,t,s)))return r;r-=(h(r,t,s)-e)/i,++f}return r},u=function(){for(f=0;f<11;)m[f]=h(f*y,t,s),++f},l=function(e,r,i){var n,a;for(a=void 0,n=void 0,f=0;(a=h(n=r+(i-r)/2,t,s)-e)>0?i=n:r=n,Math.abs(a)>1e-7&&++f<10;);return n},_=function(e){var r,i,n,a,o,h;for(h=0,r=1;10!==r&&m[r]<=e;)h+=y,++r;return--r,i=m[r+1]-m[r],n=(e-m[r])/i,(o=d(a=h+n*y,t,s))>=.001?g(e,a):0===o?a:l(e,h,h+y)},k=function(){if(t!==e||s!==r)return u()},m=c?new Float32Array(11):new Array(11),w="bezier("+[t,e,s,r]+")",(p=function(i){return k(),t===e&&s===r?i:0===i?0:1===i?1:h(_(i),e,r)}).toStr=function(){return w},p)}error(t){return i.error(t)}},t.exports=r},283:function(t,e,s){var r,i,n,a,o,l,h,u,p;h=s(52),o=s(977),n=s(162),u=s(440),a=s(50).Z,p=Math.sin,i=Math.PI,r=function(){class t{inverse(t){return 1-t}parseEasing(t){var e;return null==t&&(t="linear.none"),"string"==typeof t?"m"===t.charAt(0).toLowerCase()?this.path(t):(e=this[(t=this._splitEasing(t))[0]])?e[t[1]]:(h.error(`Easing with name "${t[0]}" was not found, fallback to "linear.none" instead`),this.linear.none):h.isArray(t)?this.bezier.apply(this,t):t}_splitEasing(t){var e;return"function"==typeof t?t:"string"==typeof t&&t.length?[(e=t.split("."))[0].toLowerCase()||"linear",e[1].toLowerCase()||"none"]:["linear","none"]}}return t.prototype.bezier=o,t.prototype.PathEasing=n,t.prototype.path=new n("creator").create,t.prototype.approximate=a,t.prototype.linear={none:function(t){return t}},t.prototype.ease={in:o.apply(t,[.42,0,1,1]),out:o.apply(t,[0,0,.58,1]),inout:o.apply(t,[.42,0,.58,1])},t.prototype.sin={in:function(t){return 1-Math.cos(t*i/2)},out:function(t){return p(t*i/2)},inout:function(t){return.5*(1-Math.cos(i*t))}},t.prototype.quad={in:function(t){return t*t},out:function(t){return t*(2-t)},inout:function(t){return(t*=2)<1?.5*t*t:-.5*(--t*(t-2)-1)}},t.prototype.cubic={in:function(t){return t*t*t},out:function(t){return--t*t*t+1},inout:function(t){return(t*=2)<1?.5*t*t*t:.5*((t-=2)*t*t+2)}},t.prototype.quart={in:function(t){return t*t*t*t},out:function(t){return 1- --t*t*t*t},inout:function(t){return(t*=2)<1?.5*t*t*t*t:-.5*((t-=2)*t*t*t-2)}},t.prototype.quint={in:function(t){return t*t*t*t*t},out:function(t){return--t*t*t*t*t+1},inout:function(t){return(t*=2)<1?.5*t*t*t*t*t:.5*((t-=2)*t*t*t*t+2)}},t.prototype.expo={in:function(t){return 0===t?0:Math.pow(1024,t-1)},out:function(t){return 1===t?1:1-Math.pow(2,-10*t)},inout:function(t){return 0===t?0:1===t?1:(t*=2)<1?.5*Math.pow(1024,t-1):.5*(2-Math.pow(2,-10*(t-1)))}},t.prototype.circ={in:function(t){return 1-Math.sqrt(1-t*t)},out:function(t){return Math.sqrt(1- --t*t)},inout:function(t){return(t*=2)<1?-.5*(Math.sqrt(1-t*t)-1):.5*(Math.sqrt(1-(t-=2)*t)+1)}},t.prototype.back={in:function(t){var e;return t*t*((1+(e=1.70158))*t-e)},out:function(t){var e;return--t*t*((1+(e=1.70158))*t+e)+1},inout:function(t){var e;return e=2.5949095,(t*=2)<1?t*t*((e+1)*t-e)*.5:.5*((t-=2)*t*((e+1)*t+e)+2)}},t.prototype.elastic={in:function(t){return 0===t?0:1===t?1:-1*Math.pow(2,10*(t-=1))*Math.sin((t-.1)*(2*Math.PI)/.4)},out:function(t){return 0===t?0:1===t?1:1*Math.pow(2,-10*t)*Math.sin((t-.1)*(2*Math.PI)/.4)+1},inout:function(t){return 0===t?0:1===t?1:(t*=2)<1?1*Math.pow(2,10*(t-=1))*Math.sin((t-.1)*(2*Math.PI)/.4)*-.5:1*Math.pow(2,-10*(t-=1))*Math.sin((t-.1)*(2*Math.PI)/.4)*.5+1}},t.prototype.bounce={in:function(t){return 1-l.bounce.out(1-t)},out:function(t){return t<1/2.75?7.5625*t*t:t<2/2.75?7.5625*(t-=1.5/2.75)*t+.75:t<2.5/2.75?7.5625*(t-=2.25/2.75)*t+.9375:7.5625*(t-=2.625/2.75)*t+.984375},inout:function(t){return t<.5?.5*l.bounce.in(2*t):.5*l.bounce.out(2*t-1)+.5}},t}.call(this),(l=new r).mix=u(l),t.exports=l},440:t=>{var e,s,r,i,n,a;s=null,n=function(t){return"number"==typeof t.value?t.value:s.parseEasing(t.value)},a=function(t,e){var s;return t.value=n(t),e.value=n(e),s=0,t.to<e.to&&(s=-1),t.to>e.to&&(s=1),s},r=function(t,e){var s,r,i;for(s=r=0,i=t.length;r<i;s=++r)if(t[s].to>e)return s},i=function(...t){return t.length>1?t=t.sort(a):t[0].value=n(t[0]),function(e){var s,i;return void 0===(s=r(t,e))?1:-1!==s?(i=t[s].value,s===t.length-1&&e>t[s].to?1:"function"==typeof i?i(e):i):void 0}},e=function(t){return s=t,i},t.exports=e},162:(t,e,s)=>{var r,i;i=s(52),r=class t{_vars(){return this._precompute=i.clamp(this.o.precompute||1450,100,1e4),this._step=1/this._precompute,this._rect=this.o.rect||100,this._approximateMax=this.o.approximateMax||5,this._eps=this.o.eps||.001,this._boundsPrevProgress=-1}constructor(t,e={}){if(this.o=e,"creator"!==t){if(this.path=i.parsePath(t),null==this.path)return i.error("Error while parsing the path");this._vars(),this.path.setAttribute("d",this._normalizePath(this.path.getAttribute("d"))),this.pathLength=this.path.getTotalLength(),this.sample=i.bind(this.sample,this),this._hardSample=i.bind(this._hardSample,this),this._preSample()}}_preSample(){var t,e,s,r,i,n,a;for(this._samples=[],a=[],t=e=0,n=this._precompute;0<=n?e<=n:e>=n;t=0<=n?++e:--e)i=t*this._step,s=this.pathLength*i,r=this.path.getPointAtLength(s),a.push(this._samples[t]={point:r,length:s,progress:i});return a}_findBounds(t,e){var s,r,i,n,a,o,l,h,u,p,c,d,_;if(e===this._boundsPrevProgress)return this._prevBounds;for(null==this._boundsStartIndex&&(this._boundsStartIndex=0),o=t.length,this._boundsPrevProgress>e?(l=0,r="reverse"):(l=o,r="forward"),"forward"===r?(d=t[0],i=t[t.length-1]):(d=t[t.length-1],i=t[0]),n=a=p=this._boundsStartIndex,c=l;p<=c?a<c:a>c;n=p<=c?++a:--a){if(u=(_=t[n]).point.x/this._rect,h=e,"reverse"===r&&(s=u,u=h,h=s),!(u<h)){i=_;break}d=_,this._boundsStartIndex=n}return this._boundsPrevProgress=e,this._prevBounds={start:d,end:i}}sample(t){var e,s;return t=i.clamp(t,0,1),e=this._findBounds(this._samples,t),null!=(s=this._checkIfBoundsCloseEnough(t,e))?s:this._findApproximate(t,e.start,e.end)}_checkIfBoundsCloseEnough(t,e){var s;return null!=(s=this._checkIfPointCloseEnough(t,e.start.point))?s:this._checkIfPointCloseEnough(t,e.end.point)}_checkIfPointCloseEnough(t,e){if(i.closeEnough(t,e.x/this._rect,this._eps))return this._resolveY(e)}_approximate(t,e,s){var r,i;return r=e.point.x-t.point.x,i=(s-t.point.x/this._rect)/(r/this._rect),t.length+i*(e.length-t.length)}_findApproximate(t,e,s,r=this._approximateMax){var n,a,o,l,h;return n=this._approximate(e,s,t),h=(l=this.path.getPointAtLength(n)).x/this._rect,i.closeEnough(t,h,this._eps)||--r<1?this._resolveY(l):(o={point:l,length:n},a=t<h?[t,e,o,r]:[t,o,s,r],this._findApproximate.apply(this,a))}_resolveY(t){return 1-t.y/this._rect}_normalizePath(t){var e,s,r,i;return i=/[M|L|H|V|C|S|Q|T|A]/gim,(r=t.split(i)).shift(),e=t.match(i),r[0]=this._normalizeSegment(r[0]),r[s=r.length-1]=this._normalizeSegment(r[s],this._rect||100),this._joinNormalizedPath(e,r)}_joinNormalizedPath(t,e){var s,r,i,n;for(n="",s=r=0,i=t.length;r<i;s=++r)n+=`${0===s?"":" "}${t[s]}${e[s].trim()}`;return n}_normalizeSegment(t,e=0){var s,r,i,n,a,o,l,h;if(t=t.trim(),a=/(-|\+)?((\d+(\.(\d|\e(-|\+)?)+)?)|(\.?(\d|\e|(\-|\+))+))/gim,h=(i=(o=this._getSegmentPairs(t.match(a)))[o.length-1])[0],Number(h)!==e)for(t="",i[0]=e,s=r=0,n=o.length;r<n;s=++r)t+=`${0===s?"":" "}${(l=o[s])[0]},${l[1]}`;return t}_getSegmentPairs(t){var e,s,r,n,a;for(t.length%2!=0&&i.error("Failed to parse the path - segment pairs are not even.",t),n=[],e=s=0,r=t.length;s<r;e=s+=2)t[e],a=[t[e],t[e+1]],n.push(a);return n}create(e,s){var r;return(r=new t(e,s)).sample.path=r.path,r.sample}},t.exports=r},52:function(t){var e,s;e=function(){class t{constructor(){this.vars()}vars(){var t;return this.prefix=this.getPrefix(),this.getRemBase(),this.isFF="moz"===this.prefix.lowercase,this.isIE="ms"===this.prefix.lowercase,t=navigator.userAgent,this.isOldOpera=t.match(/presto/gim),this.isSafari=t.indexOf("Safari")>-1,this.isChrome=t.indexOf("Chrome")>-1,this.isOpera=t.toLowerCase().indexOf("op")>-1,this.isChrome&&this.isSafari&&(this.isSafari=!1),t.match(/PhantomJS/gim)&&(this.isSafari=!1),this.isChrome&&this.isOpera&&(this.isChrome=!1),this.is3d=this.checkIf3d(),this.uniqIDs=-1,this.div=document.createElement("div"),document.body.appendChild(this.div),this.defaultStyles=this.computedStyle(this.div)}cloneObj(t,e){var s,r,i,n;for(n={},s=(i=Object.keys(t)).length;s--;)r=i[s],null!=e&&e[r]||(n[r]=t[r]);return n}extend(t,e){var s;for(s in e)e[s],null==t[s]&&(t[s]=e[s]);return t}getRemBase(){var t,e;return t=document.querySelector("html"),e=getComputedStyle(t),this.remBase=parseFloat(e.fontSize)}clamp(t,e,s){return t<e?e:t>s?s:t}setPrefixedStyle(t,e,s){return"transform"===e&&(t.style[`${this.prefix.css}${e}`]=s),t.style[e]=s}style(t,e,s){var r,i,n,a;if("object"==typeof e){for(n=(i=Object.keys(e)).length,a=[];n--;)s=e[r=i[n]],a.push(this.setPrefixedStyle(t,r,s));return a}return this.setPrefixedStyle(t,e,s)}prepareForLog(t){return(t=Array.prototype.slice.apply(t)).unshift("::"),t.unshift(this.logBadgeCss),t.unshift("%cmo·js%c"),t}log(){if(!1!==mojs.isDebug)return console.log.apply(console,this.prepareForLog(arguments))}warn(){if(!1!==mojs.isDebug)return console.warn.apply(console,this.prepareForLog(arguments))}error(){if(!1!==mojs.isDebug)return console.error.apply(console,this.prepareForLog(arguments))}parseUnit(t){var e,s,r,i,n;return"number"==typeof t?{unit:"px",isStrict:!1,value:t,string:0===t?`${t}`:`${t}px`}:"string"==typeof t?(i=/px|%|rem|em|ex|cm|ch|mm|in|pt|pc|vh|vw|vmin|deg/gim,s=!0,(n=null!=(r=t.match(i))?r[0]:void 0)||(n="px",s=!1),{unit:n,isStrict:s,value:e=parseFloat(t),string:0===e?`${e}`:`${e}${n}`}):t}bind(t,e){var s,r;return r=function(){var r,i;return r=Array.prototype.slice.call(arguments),i=s.concat(r),t.apply(e,i)},s=Array.prototype.slice.call(arguments,2),r}getRadialPoint(t={}){var e,s,r;return e=.017453292519943295*(t.rotate-90),s=null!=t.radiusX?t.radiusX:t.radius,r=null!=t.radiusY?t.radiusY:t.radius,{x:t.center.x+Math.cos(e)*s,y:t.center.y+Math.sin(e)*r}}getPrefix(){var t,e,s;return s=window.getComputedStyle(document.documentElement,""),t=(Array.prototype.slice.call(s).join("").match(/-(moz|webkit|ms)-/)||""===s.OLink&&["","o"])[1],{dom:null!=(e="WebKit|Moz|MS|O".match(new RegExp("("+t+")","i")))?e[1]:void 0,lowercase:t,css:"-"+t+"-",js:(null!=t?t[0].toUpperCase():void 0)+(null!=t?t.substr(1):void 0)}}strToArr(t){var e;return e=[],"number"!=typeof t||isNaN(t)?(t.trim().split(/\s+/gim).forEach((t=>e.push(this.parseUnit(this.parseIfRand(t))))),e):(e.push(this.parseUnit(t)),e)}calcArrDelta(t,e){var s,r,i,n;for(s=[],r=i=0,n=t.length;i<n;r=++i)t[r],s[r]=this.parseUnit(`${e[r].value-t[r].value}${e[r].unit}`);return s}isArray(t){return t instanceof Array}normDashArrays(t,e){var s,r,i,n,a,o,l,h,u,p;if((s=t.length)>(r=e.length))for(l=s-r,p=e.length,n=a=0,h=l;0<=h?a<h:a>h;n=0<=h?++a:--a)i=n+p,e.push(this.parseUnit(`0${t[i].unit}`));else if(r>s)for(l=r-s,p=t.length,n=o=0,u=l;0<=u?o<u:o>u;n=0<=u?++o:--o)i=n+p,t.push(this.parseUnit(`0${e[i].unit}`));return[t,e]}makeColorObj(t){var e,s,r,i,n,a,o,l;return"#"===t[0]&&(r={},(o=/^#?([a-f\d]{1,2})([a-f\d]{1,2})([a-f\d]{1,2})$/i.exec(t))&&(a=2===o[1].length?o[1]:o[1]+o[1],i=2===o[2].length?o[2]:o[2]+o[2],s=2===o[3].length?o[3]:o[3]+o[3],r={r:parseInt(a,16),g:parseInt(i,16),b:parseInt(s,16),a:1})),"#"!==t[0]&&((n="r"===t[0]&&"g"===t[1]&&"b"===t[2])&&(l=t),n||(l=this.shortColors[t]?this.shortColors[t]:(this.div.style.color=t,this.computedStyle(this.div).color)),o=new RegExp("^rgba?\\((\\d{1,3}),\\s?(\\d{1,3}),\\s?(\\d{1,3}),?\\s?(\\d{1}|0?\\.\\d{1,})?\\)$","gi").exec(l),r={},e=parseFloat(o[4]||1),o&&(r={r:parseInt(o[1],10),g:parseInt(o[2],10),b:parseInt(o[3],10),a:null==e||isNaN(e)?1:e})),r}computedStyle(t){return getComputedStyle(t)}capitalize(t){if("string"!=typeof t)throw Error("String expected - nothing to capitalize");return t.charAt(0).toUpperCase()+t.substring(1)}parseRand(t){var e,s,r;return s=t.split(/rand\(|\,|\)/),r=this.parseUnit(s[2]),e=this.rand(parseFloat(s[1]),parseFloat(s[2])),r.unit&&s[2].match(r.unit)?e+r.unit:e}parseStagger(t,e){var s,r,i,n,a,o;return i=(o=t.split(/stagger\(|\)$/)[1].toLowerCase()).split(/(rand\(.*?\)|[^\(,\s]+)(?=\s*,|\s*$)/gim),o=i.length>3?(s=this.parseUnit(this.parseIfRand(i[1])),i[3]):(s=this.parseUnit(0),i[1]),o=this.parseIfRand(o),r=e*(a=this.parseUnit(o)).value+s.value,(n=s.isStrict?s.unit:a.isStrict?a.unit:"")?`${r}${n}`:r}parseIfStagger(t,e){return"string"==typeof t&&t.match(/stagger/g)?this.parseStagger(t,e):t}parseIfRand(t){return"string"==typeof t&&t.match(/rand\(/)?this.parseRand(t):t}parseDelta(t,e,s){var r,i,n,a,o,l,h,u,p,c,d,_;if(null!=(n=(e=this.cloneObj(e)).easing)&&(n=mojs.easing.parseEasing(n)),delete e.easing,null!=(r=e.curve)&&(r=mojs.easing.parseEasing(r)),delete e.curve,a=e[c=Object.keys(e)[0]],i={start:c},!isNaN(parseFloat(c))||c.match(/rand\(/)||c.match(/stagger\(/))if("strokeDasharray"===t||"strokeDashoffset"===t||"origin"===t){for(d=this.strToArr(c),o=this.strToArr(a),this.normDashArrays(d,o),h=u=0,p=d.length;u<p;h=++u)c=d[h],a=o[h],this.mergeUnits(c,a,t);i={type:"array",name:t,start:d,end:o,delta:this.calcArrDelta(d,o),easing:n,curve:r}}else this.callbacksMap[t]||this.tweenOptionMap[t]||(this.unitOptionMap[t]?(a=this.parseUnit(this.parseStringOption(a,s)),c=this.parseUnit(this.parseStringOption(c,s)),this.mergeUnits(c,a,t),i={type:"unit",name:t,start:c,end:a,delta:a.value-c.value,easing:n,curve:r}):(a=parseFloat(this.parseStringOption(a,s)),i={type:"number",name:t,start:c=parseFloat(this.parseStringOption(c,s)),end:a,delta:a-c,easing:n,curve:r}));else{if("strokeLinecap"===t)return this.warn(`Sorry, stroke-linecap property is not animatable yet, using the start(${c}) value instead`,e),i;i={type:"color",name:t,start:_=this.makeColorObj(c),end:l=this.makeColorObj(a),easing:n,curve:r,delta:{r:l.r-_.r,g:l.g-_.g,b:l.b-_.b,a:l.a-_.a}}}return i}mergeUnits(t,e,s){return!e.isStrict&&t.isStrict?(e.unit=t.unit,e.string=`${e.value}${e.unit}`):e.isStrict&&!t.isStrict?(t.unit=e.unit,t.string=`${t.value}${t.unit}`):e.isStrict&&t.isStrict&&e.unit!==t.unit?(t.unit=e.unit,t.string=`${t.value}${t.unit}`,this.warn(`Two different units were specified on "${s}" delta property, mo · js will fallback to end "${e.unit}" unit `)):void 0}rand(t,e){return Math.random()*(e-t)+t}isDOM(t){var e;return null!=t&&(e="number"==typeof t.nodeType&&"string"==typeof t.nodeName,"object"==typeof t&&e)}getChildElements(t){var e,s,r;for(s=[],r=(e=t.childNodes).length;r--;)1===e[r].nodeType&&s.unshift(e[r]);return s}delta(t,e){var s,r,i,n,a;if(a=typeof e,s="string"==(n=typeof t)||"number"===n&&!isNaN(t),r="string"===a||"number"===a&&!isNaN(e),s&&r)return(i={})[t]=e,i;this.error(`delta method expects Strings or Numbers at input but got - ${t}, ${e}`)}getUniqID(){return++this.uniqIDs}parsePath(t){var e;return"string"==typeof t?"m"===t.charAt(0).toLowerCase()?((e=document.createElementNS(this.NS,"path")).setAttributeNS(null,"d",t),e):document.querySelector(t):t.style?t:void 0}closeEnough(t,e,s){return Math.abs(t-e)<s}checkIf3d(){var t,e,s;return t=document.createElement("div"),this.style(t,"transform","translateZ(0)"),""!==(null!=(s=t.style)[e=`${this.prefix.css}transform`]?s[e]:s.transform)}isObject(t){return null!==t&&"object"==typeof t}getDeltaEnd(t){return t[Object.keys(t)[0]]}getDeltaStart(t){return Object.keys(t)[0]}isTweenProp(t){return this.tweenOptionMap[t]||this.callbacksMap[t]}parseStringOption(t,e=0){return"string"==typeof t&&(t=this.parseIfStagger(t,e),t=this.parseIfRand(t)),t}getLastItem(t){return t[t.length-1]}parseEl(t){return s.isDOM(t)||("string"==typeof t&&(t=document.querySelector(t)),null===t&&s.error("Can't parse HTML element: ",t)),t}force3d(t){return this.setPrefixedStyle(t,"backface-visibility","hidden"),t}isDelta(t){return!(!this.isObject(t)||t.unit||this.isArray(t)||this.isDOM(t))}}return t.prototype.NS="http://www.w3.org/2000/svg",t.prototype.logBadgeCss="background:#3A0839;color:#FF512F;border-radius:5px; padding: 1px 5px 2px; border: 1px solid #FF512F;",t.prototype.shortColors={transparent:"rgba(0,0,0,0)",none:"rgba(0,0,0,0)",aqua:"rgb(0,255,255)",black:"rgb(0,0,0)",blue:"rgb(0,0,255)",fuchsia:"rgb(255,0,255)",gray:"rgb(128,128,128)",green:"rgb(0,128,0)",lime:"rgb(0,255,0)",maroon:"rgb(128,0,0)",navy:"rgb(0,0,128)",olive:"rgb(128,128,0)",purple:"rgb(128,0,128)",red:"rgb(255,0,0)",silver:"rgb(192,192,192)",teal:"rgb(0,128,128)",white:"rgb(255,255,255)",yellow:"rgb(255,255,0)",orange:"rgb(255,128,0)"},t.prototype.chainOptionMap={},t.prototype.callbacksMap={onRefresh:1,onStart:1,onComplete:1,onFirstUpdate:1,onUpdate:1,onProgress:1,onRepeatStart:1,onRepeatComplete:1,onPlaybackStart:1,onPlaybackPause:1,onPlaybackStop:1,onPlaybackComplete:1},t.prototype.tweenOptionMap={duration:1,delay:1,speed:1,repeat:1,easing:1,backwardEasing:1,isYoyo:1,shiftTime:1,isReversed:1,callbacksContext:1},t.prototype.unitOptionMap={left:1,top:1,x:1,y:1,rx:1,ry:1},t.prototype.RAD_TO_DEG=180/Math.PI,t}.call(this),s=new e,t.exports=s},291:function(t,e,s){var r,i,n,a,o;a=s(52),o=s(247),n=s(755).Z,i=s(342).Z,r=function(){class t{constructor(t={}){this.calcHeight=this.calcHeight.bind(this),this.o=t,this.vars()||this.createTween()}vars(){return this.getScaler=a.bind(this.getScaler,this),this.resize=o,this.props=a.cloneObj(this.defaults),this.extendOptions(this.o),this.isMotionBlurReset=a.isSafari||a.isIE,this.isMotionBlurReset&&(this.props.motionBlur=0),this.history=[a.cloneObj(this.props)],this.postVars()}curveToPath(t){var e,s,r,i,n,o,l,h,u,p,c,d;return u=document.createElementNS(a.NS,"path"),h={x:(d=t.start).x+t.shift.x,y:d.x+t.shift.y},e=t.curvature,o=t.shift.x,l=t.shift.y,p=Math.sqrt(o*o+l*l)/100,c=Math.atan(l/o)*(180/Math.PI)+90,t.shift.x<0&&(c+=180),s="%"===(s=a.parseUnit(e.x)).unit?s.value*p:s.value,n=a.getRadialPoint({center:{x:d.x,y:d.y},radius:s,rotate}),r="%"===(r=a.parseUnit(e.y)).unit?r.value*p:r.value,i=a.getRadialPoint({center:{x:n.x,y:n.y},radius:r,rotate:c+90}),u.setAttribute("d",`M${d.x},${d.y} Q${i.x},${i.y} ${h.x},${h.y}`),u}postVars(){return this.props.pathStart=a.clamp(this.props.pathStart,0,1),this.props.pathEnd=a.clamp(this.props.pathEnd,this.props.pathStart,1),this.rotate=0,this.speedX=0,this.speedY=0,this.blurX=0,this.blurY=0,this.prevCoords={},this.blurAmount=20,this.props.motionBlur=a.clamp(this.props.motionBlur,0,1),this.onUpdate=this.props.onUpdate,this.o.el?(this.el=this.parseEl(this.props.el),this.props.motionBlur>0&&this.createFilter(),this.path=this.getPath(),this.path.getAttribute("d")?(this.len=this.path.getTotalLength(),this.slicedLen=this.len*(this.props.pathEnd-this.props.pathStart),this.startLen=this.props.pathStart*this.len,this.fill=this.props.fill,null!=this.fill&&(this.container=this.parseEl(this.props.fill.container),this.fillRule=this.props.fill.fillRule||"all",this.getScaler(),null!=this.container)?(this.removeEvent(this.container,"onresize",this.getScaler),this.addEvent(this.container,"onresize",this.getScaler)):void 0):(a.error("Path has no coordinates to work with, aborting"),!0)):(a.error('Missed "el" option. It could be a selector, DOMNode or another module.'),!0)}addEvent(t,e,s){return t.addEventListener(e,s,!1)}removeEvent(t,e,s){return t.removeEventListener(e,s,!1)}createFilter(){var t,e;return t=document.createElement("div"),this.filterID=`filter-${a.getUniqID()}`,t.innerHTML=`<svg id="svg-${this.filterID}"\n    style="visibility:hidden; width:0px; height:0px">\n  <filter id="${this.filterID}" y="-20" x="-20" width="40" height="40">\n    <feOffset\n      id="blur-offset" in="SourceGraphic"\n      dx="0" dy="0" result="offset2"></feOffset>\n    <feGaussianblur\n      id="blur" in="offset2"\n      stdDeviation="0,0" result="blur2"></feGaussianblur>\n    <feMerge>\n      <feMergeNode in="SourceGraphic"></feMergeNode>\n      <feMergeNode in="blur2"></feMergeNode>\n    </feMerge>\n  </filter>\n</svg>`,e=t.querySelector(`#svg-${this.filterID}`),this.filter=e.querySelector("#blur"),this.filterOffset=e.querySelector("#blur-offset"),document.body.insertBefore(e,document.body.firstChild),this.el.style.filter=`url(#${this.filterID})`,this.el.style[`${a.prefix.css}filter`]=`url(#${this.filterID})`}parseEl(t){return"string"==typeof t?document.querySelector(t):t instanceof HTMLElement?t:null!=t._setProp?(this.isModule=!0,t):void 0}getPath(){return a.parsePath(this.props.path)||(this.props.path.x||this.props.path.y?this.curveToPath({start:{x:0,y:0},shift:{x:this.props.path.x||0,y:this.props.path.y||0},curvature:{x:this.props.curvature.x||this.defaults.curvature.x,y:this.props.curvature.y||this.defaults.curvature.y}}):void 0)}getScaler(){var t,e,s;switch(this.cSize={width:this.container.offsetWidth||0,height:this.container.offsetHeight||0},s=this.path.getPointAtLength(0),t=this.path.getPointAtLength(this.len),e={},this.scaler={},e.width=t.x>=s.x?t.x-s.x:s.x-t.x,e.height=t.y>=s.y?t.y-s.y:s.y-t.y,this.fillRule){case"all":return this.calcWidth(e),this.calcHeight(e);case"width":return this.calcWidth(e),this.scaler.y=this.scaler.x;case"height":return this.calcHeight(e),this.scaler.x=this.scaler.y}}calcWidth(t){return this.scaler.x=this.cSize.width/t.width,!isFinite(this.scaler.x)&&(this.scaler.x=1)}calcHeight(t){return this.scaler.y=this.cSize.height/t.height,!isFinite(this.scaler.y)&&(this.scaler.y=1)}run(t){var e,s;if(t){for(e in this.history[0],t)s=t[e],a.callbacksMap[e]||a.tweenOptionMap[e]?(a.warn(`the property "${e}" property can not be overridden on run yet`),delete t[e]):this.history[0][e]=s;this.tuneOptions(t)}return this.startTween()}createTween(){return this.tween=new n({duration:this.props.duration,delay:this.props.delay,yoyo:this.props.yoyo,repeat:this.props.repeat,easing:this.props.easing,onStart:()=>{var t;return null!=(t=this.props.onStart)?t.apply(this):void 0},onComplete:()=>{var t;return this.props.motionBlur&&this.setBlur({blur:{x:0,y:0},offset:{x:0,y:0}}),null!=(t=this.props.onComplete)?t.apply(this):void 0},onUpdate:t=>this.setProgress(t),onFirstUpdate:(t,e)=>{if(!t)return this.history.length>1&&this.tuneOptions(this.history[0])}}),this.timeline=new i,this.timeline.add(this.tween),!this.props.isRunLess&&this.startTween(),this.props.isPresetPosition&&this.setProgress(0,!0)}startTween(){return setTimeout((()=>{var t;return null!=(t=this.timeline)?t.play():void 0}),1)}setProgress(t,e){var s,r,i,n;return s=this.startLen+(this.props.isReverse?(1-t)*this.slicedLen:t*this.slicedLen),i=(r=this.path.getPointAtLength(s)).x+this.props.offsetX,n=r.y+this.props.offsetY,this._getCurrentRotation(r,s,t),this._setTransformOrigin(t),this._setTransform(i,n,t,e),this.props.motionBlur&&this.makeMotionBlur(i,n)}setElPosition(t,e,s){var r;return r=`translate(${t}px,${e}px) ${0!==this.rotate?`rotate(${this.rotate}deg)`:""} ${this.props.isCompositeLayer&&a.is3d?"translateZ(0)":""}`,a.setPrefixedStyle(this.el,"transform",r)}setModulePosition(t,e){return this.el._setProp({shiftX:`${t}px`,shiftY:`${e}px`,rotate:this.rotate}),this.el._draw()}_getCurrentRotation(t,e,s){var r,i,n,o,l;return i="function"==typeof this.props.transformOrigin,this.props.isRotation||null!=this.props.rotationOffset||i?(n=this.path.getPointAtLength(e-1),o=t.y-n.y,l=t.x-n.x,r=Math.atan(o/l),!isFinite(r)&&(r=0),this.rotate=r*a.RAD_TO_DEG,"function"!=typeof this.props.rotationOffset?this.rotate+=this.props.rotationOffset||0:this.rotate=this.props.rotationOffset.call(this,this.rotate,s)):this.rotate=0}_setTransform(t,e,s,r){var i;return this.scaler&&(t*=this.scaler.x,e*=this.scaler.y),i=null,r||(i="function"==typeof this.onUpdate?this.onUpdate(s,{x:t,y:e,rotate:this.rotate}):void 0),this.isModule?this.setModulePosition(t,e):"string"!=typeof i?this.setElPosition(t,e,s):a.setPrefixedStyle(this.el,"transform",i)}_setTransformOrigin(t){var e;if(this.props.transformOrigin)return e="function"==typeof this.props.transformOrigin?this.props.transformOrigin(this.rotate,t):this.props.transformOrigin,a.setPrefixedStyle(this.el,"transform-origin",e)}makeMotionBlur(t,e){var s,r,i,n,o,l,h;return h=0,o=1,l=1,null==this.prevCoords.x||null==this.prevCoords.y?(this.speedX=0,this.speedY=0):(i=t-this.prevCoords.x,n=e-this.prevCoords.y,i>0&&(o=-1),o<0&&(l=-1),this.speedX=Math.abs(i),this.speedY=Math.abs(n),h=Math.atan(n/i)*(180/Math.PI)+90),s=h-this.rotate,r=this.rotToCoords(s),this.blurX=a.clamp(this.speedX/16*this.props.motionBlur,0,1),this.blurY=a.clamp(this.speedY/16*this.props.motionBlur,0,1),this.setBlur({blur:{x:3*this.blurX*this.blurAmount*Math.abs(r.x),y:3*this.blurY*this.blurAmount*Math.abs(r.y)},offset:{x:3*o*this.blurX*r.x*this.blurAmount,y:3*l*this.blurY*r.y*this.blurAmount}}),this.prevCoords.x=t,this.prevCoords.y=e}setBlur(t){if(!this.isMotionBlurReset)return this.filter.setAttribute("stdDeviation",`${t.blur.x},${t.blur.y}`),this.filterOffset.setAttribute("dx",t.offset.x),this.filterOffset.setAttribute("dy",t.offset.y)}extendDefaults(t){var e,s,r;for(e in s=[],t)r=t[e],s.push(this[e]=r);return s}extendOptions(t){var e,s,r;for(e in s=[],t)r=t[e],s.push(this.props[e]=r);return s}then(t){var e,s,r,i,o;for(s in r={},i=this.history[this.history.length-1])o=i[s],!a.callbacksMap[s]&&!a.tweenOptionMap[s]||"duration"===s?null==t[s]&&(t[s]=o):null==t[s]&&(t[s]=void 0),a.tweenOptionMap[s]&&(r[s]="duration"!==s||null!=t[s]?t[s]:i[s]);return this.history.push(t),e=this,r.onUpdate=t=>this.setProgress(t),r.onStart=()=>{var t;return null!=(t=this.props.onStart)?t.apply(this):void 0},r.onComplete=()=>{var t;return null!=(t=this.props.onComplete)?t.apply(this):void 0},r.onFirstUpdate=function(){return e.tuneOptions(e.history[this.index])},r.isChained=!t.delay,this.timeline.append(new n(r)),this}tuneOptions(t){return this.extendOptions(t),this.postVars()}rotToCoords(t){var e,s,r;return e=((t%=360)-90)*Math.PI/180,s=Math.cos(e),r=Math.sin(e),{x:1.428571429*(s=s<0?Math.max(s,-.7):Math.min(s,.7)),y:1.428571429*(r=r<0?Math.max(r,-.7):Math.min(r,.7))}}}return t.prototype.defaults={path:null,curvature:{x:"75%",y:"50%"},isCompositeLayer:!0,delay:0,duration:1e3,easing:null,repeat:0,yoyo:!1,onStart:null,onComplete:null,onUpdate:null,offsetX:0,offsetY:0,rotationOffset:null,pathStart:0,pathEnd:1,motionBlur:0,transformOrigin:null,isRotation:!1,isReverse:!1,isRunLess:!1,isPresetPosition:!0},t}.call(this),t.exports=r},785:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.shape="ellipse"}_draw(){var t,e;return t=null!=this._props.radiusX?this._props.radiusX:this._props.radius,e=null!=this._props.radiusY?this._props.radiusY:this._props.radius,this._setAttrIfChanged("rx",t),this._setAttrIfChanged("ry",e),this._setAttrIfChanged("cx",this._props.width/2),this._setAttrIfChanged("cy",this._props.height/2),super._draw()}_getLength(){var t,e;return t=null!=this._props.radiusX?this._props.radiusX:this._props.radius,e=null!=this._props.radiusY?this._props.radiusY:this._props.radius,2*Math.PI*Math.sqrt((t*t+e*e)/2)}},t.exports=i},158:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="path"}_draw(){var t,e,s,r,i,n,a;if(super._draw(),this._props,r=null!=this._props.radiusX?this._props.radiusX:this._props.radius,i=null!=this._props.radiusY?this._props.radiusY:this._props.radius,e=r===this._prevRadiusX,s=i===this._prevRadiusY,!e||!s)return t=`M${(n=this._props.width/2)-r},${a=this._props.height/2} L${n+r},${a} M${n},${a-i} L${n},${a+i}`,this.el.setAttribute("d",t),this._prevRadiusX=r,this._prevRadiusY=i}_getLength(){return 2*((null!=this._props.radiusX?this._props.radiusX:this._props.radius)+(null!=this._props.radiusY?this._props.radiusY:this._props.radius))}},t.exports=i},153:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="path",this._defaults.points=2}_draw(){var t,e,s,r,i,n,a,o,l,h,u,p,c,d,_,f;if(super._draw(),a=this._props,this._props.points&&(o=null!=this._props.radiusX?this._props.radiusX:this._props.radius,l=null!=this._props.radiusY?this._props.radiusY:this._props.radius,r=o===this._prevRadiusX,i=l===this._prevRadiusY,s=a.points===this._prevPoints,!(r&&i&&s))){for(u=this._props.width/2,d=this._props.height/2,p=u-o,c=u+o,t="",f=2*l/(this._props.points-1),_=d-l,e=n=0,h=this._props.points;0<=h?n<h:n>h;e=0<=h?++n:--n)t+=`M${p}, ${d=`${e*f+_}`} L${c}, ${d} `;return this.el.setAttribute("d",t),this._prevPoints=a.points,this._prevRadiusX=o,this._prevRadiusY=l}}_getLength(){return 2*(null!=this._props.radiusX?this._props.radiusX:this._props.radius)}},t.exports=i},786:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="line"}_draw(){var t,e,s;return t=null!=this._props.radiusX?this._props.radiusX:this._props.radius,e=this._props.width/2,s=this._props.height/2,this._setAttrIfChanged("x1",e-t),this._setAttrIfChanged("x2",e+t),this._setAttrIfChanged("y1",s),this._setAttrIfChanged("y2",s),super._draw()}},t.exports=i},878:(t,e,s)=>{var r,i,n;n=s(52),r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="path",this._defaults.points=3}_draw(){var t,e,s,r,i,a,o,l,h,u,p,c,d,_,f;if(h=this._props,p=null!=this._props.radiusX?this._props.radiusX:this._props.radius,c=null!=this._props.radiusY?this._props.radiusY:this._props.radius,r=p===this._prevRadiusX,i=c===this._prevRadiusY,s=h.points===this._prevPoints,!(r&&i&&s)){for(f=360/this._props.points,null==this._radialPoints?this._radialPoints=[]:this._radialPoints.length=0,e=a=0,d=this._props.points;0<=d?a<d:a>d;e=0<=d?++a:--a)this._radialPoints.push(n.getRadialPoint({radius:this._props.radius,radiusX:this._props.radiusX,radiusY:this._props.radiusY,rotate:e*f,center:{x:h.width/2,y:h.height/2}}));for(t="",e=o=0,l=(_=this._radialPoints).length;o<l;e=++o)t+=`${0===e?"M":"L"}${(u=_[e]).x.toFixed(4)},${u.y.toFixed(4)} `;this._prevPoints=h.points,this._prevRadiusX=p,this._prevRadiusY=c,this.el.setAttribute("d",t+="z")}return super._draw()}_getLength(){return this._getPointsPerimiter(this._radialPoints)}},t.exports=i},979:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="rect",this._defaults.rx=0,this._defaults.ry=0}_draw(){var t,e,s;return super._draw(),e=null!=(t=this._props).radiusX?t.radiusX:t.radius,s=null!=t.radiusY?t.radiusY:t.radius,this._setAttrIfChanged("width",2*e),this._setAttrIfChanged("height",2*s),this._setAttrIfChanged("x",t.width/2-e),this._setAttrIfChanged("y",t.height/2-s),this._setAttrIfChanged("rx",t.rx),this._setAttrIfChanged("ry",t.ry)}_getLength(){return 2*(2*(null!=this._props.radiusX?this._props.radiusX:this._props.radius)+2*(null!=this._props.radiusY?this._props.radiusY:this._props.radius))}},t.exports=i},806:function(t,e,s){var r,i,n,a,o,l,h,u,p,c,d,_;_=s(52),r=s(623).Z,l=s(854).Z,n=s(785),u=s(786),d=s(322),c=s(979),p=s(878),a=s(158),o=s(472).Z,h=s(153),i=function(){class t{constructor(){this.addShape=_.bind(this.addShape,this)}getShape(t){return this[t]||_.error(`no "${t}" shape available yet, please choose from this list:`,["circle","line","zigzag","rect","polygon","cross","equal","curve"])}addShape(t,e){return this[t]=e}}return t.prototype.bit=r,t.prototype.custom=l,t.prototype.circle=n,t.prototype.line=u,t.prototype.zigzag=d,t.prototype.rect=c,t.prototype.polygon=p,t.prototype.cross=a,t.prototype.equal=h,t.prototype.curve=o,t}.call(this),t.exports=new i},322:(t,e,s)=>{var r,i;r=s(623).Z,i=class extends r{_declareDefaults(){return super._declareDefaults(),this._defaults.tag="path",this._defaults.points=3}_draw(){var t,e,s,r,i,n,a,o,l,h,u,p,c,d,_,f;if(super._draw(),l=this._props,this._props.points&&(u=null!=this._props.radiusX?this._props.radiusX:this._props.radius,p=null!=this._props.radiusY?this._props.radiusY:this._props.radius,i=u===this._prevRadiusX,n=p===this._prevRadiusY,r=l.points===this._prevPoints,!(i&&n&&r))){for(t=l.width/2-u,e=_=l.height/2,d=2*u/(l.points-1),f=-1,o=-(s=Math.sqrt(d*d+p*p)),h=`M${t}, ${_} `,a=0,c=l.points;0<=c?a<c:a>c;0<=c?++a:--a)h+=`L${t}, ${e} `,t+=d,o+=s,e=-1===f?_-p:_,f=-f;return this._length=o,this.el.setAttribute("d",h),this._prevPoints=l.points,this._prevRadiusX=u,this._prevRadiusY=p}}_getLength(){return this._length}},t.exports=i},247:(t,e)=>{var s,r;r=class{constructor(t={}){this.o=t,window.isAnyResizeEventInited||(this.vars(),this.redefineProto())}vars(){return window.isAnyResizeEventInited=!0,this.allowedProtos=[HTMLDivElement,HTMLFormElement,HTMLLinkElement,HTMLBodyElement,HTMLParagraphElement,HTMLFieldSetElement,HTMLLegendElement,HTMLLabelElement,HTMLButtonElement,HTMLUListElement,HTMLOListElement,HTMLLIElement,HTMLHeadingElement,HTMLQuoteElement,HTMLPreElement,HTMLBRElement,HTMLFontElement,HTMLHRElement,HTMLModElement,HTMLParamElement,HTMLMapElement,HTMLTableElement,HTMLTableCaptionElement,HTMLImageElement,HTMLTableCellElement,HTMLSelectElement,HTMLInputElement,HTMLTextAreaElement,HTMLAnchorElement,HTMLObjectElement,HTMLTableColElement,HTMLTableSectionElement,HTMLTableRowElement],this.timerElements={img:1,textarea:1,input:1,embed:1,object:1,svg:1,canvas:1,tr:1,tbody:1,thead:1,tfoot:1,a:1,select:1,option:1,optgroup:1,dl:1,dt:1,br:1,basefont:1,font:1,col:1,iframe:1}}redefineProto(){var t,e,s;return e=this,function(){var r,i,n,a;for(n=this.allowedProtos,a=[],t=r=0,i=n.length;r<i;t=++r)null!=(s=n[t]).prototype&&a.push(function(t){return function(s){var r;r=function(){return this===window&&this===document||"onresize"===arguments[0]&&!this.isAnyResizeEventInited&&e.handleResize({args:arguments,that:this}),s.apply(this,arguments)},t.prototype.addEventListener?t.prototype.addEventListener=r:t.prototype.attachEvent&&(t.prototype.attachEvent=r)}(t.prototype.addEventListener||t.prototype.attachEvent),function(e){var s;return s=function(){return this.isAnyResizeEventInited=!1,this.iframe&&this.removeChild(this.iframe),e.apply(this,arguments)},t.prototype.removeEventListener?t.prototype.removeEventListener=s:t.prototype.detachEvent?t.prototype.detachEvent=wrappedListener:void 0}(t.prototype.removeEventListener||t.prototype.detachEvent)}(s));return a}.call(this)}handleResize(t){var e,s,r,i,n,a,o;return s=t.that,this.timerElements[s.tagName.toLowerCase()]?this.initTimer(s):(r=document.createElement("iframe"),s.appendChild(r),r.style.width="100%",r.style.height="100%",r.style.position="absolute",r.style.zIndex=-999,r.style.opacity=0,r.style.top=0,r.style.left=0,e=window.getComputedStyle?getComputedStyle(s):s.currentStyle,n=""===s.style.position,a="static"===e.position&&n,i=""===e.position&&""===s.style.position,(a||i)&&(s.style.position="relative"),null!=(o=r.contentWindow)&&(o.onresize=t=>this.dispatchEvent(s)),s.iframe=r),s.isAnyResizeEventInited=!0}initTimer(t){var e,s;return s=0,e=0,this.interval=setInterval((()=>{var r,i;if(i=t.offsetWidth,r=t.offsetHeight,i!==s||r!==e)return this.dispatchEvent(t),s=i,e=r}),this.o.interval||62.5)}dispatchEvent(t){var e;return document.createEvent?((e=document.createEvent("HTMLEvents")).initEvent("onresize",!1,!1),t.dispatchEvent(e)):!!document.createEventObject&&(e=document.createEventObject(),t.fireEvent("onresize",e))}destroy(){var t,e,s,r,i,n;for(clearInterval(this.interval),this.interval=null,window.isAnyResizeEventInited=!1,n=[],t=e=0,s=(i=this.allowedProtos).length;e<s;t=++e)null!=(r=i[t]).prototype&&n.push(function(t){return t.prototype.addEventListener||t.prototype.attachEvent,t.prototype.addEventListener?t.prototype.addEventListener=Element.prototype.addEventListener:t.prototype.attachEvent&&(t.prototype.attachEvent=Element.prototype.attachEvent),t.prototype.removeEventListener?t.prototype.removeEventListener=Element.prototype.removeEventListener:t.prototype.detachEvent?t.prototype.detachEvent=Element.prototype.detachEvent:void 0}(r));return n}},void 0===(s=function(){return new r}.apply(e,[]))||(t.exports=s)},326:(t,e,s)=>{"use strict";function r(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}s.d(e,{Z:()=>r})},671:(t,e,s)=>{"use strict";function r(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}s.d(e,{Z:()=>r})},144:(t,e,s)=>{"use strict";s.d(e,{Z:()=>n});var r=s(142);function i(t,e){for(var s=0;s<e.length;s++){var i=e[s];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,(0,r.Z)(i.key),i)}}function n(t,e,s){return e&&i(t.prototype,e),s&&i(t,s),Object.defineProperty(t,"prototype",{writable:!1}),t}},752:(t,e,s)=>{"use strict";s.d(e,{Z:()=>i});var r=s(120);function i(){return i="undefined"!=typeof Reflect&&Reflect.get?Reflect.get.bind():function(t,e,s){var i=function(t,e){for(;!Object.prototype.hasOwnProperty.call(t,e)&&null!==(t=(0,r.Z)(t)););return t}(t,e);if(i){var n=Object.getOwnPropertyDescriptor(i,e);return n.get?n.get.call(arguments.length<3?t:s):n.value}},i.apply(this,arguments)}},120:(t,e,s)=>{"use strict";function r(t){return r=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(t){return t.__proto__||Object.getPrototypeOf(t)},r(t)}s.d(e,{Z:()=>r})},340:(t,e,s)=>{"use strict";function r(t,e){return r=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t},r(t,e)}function i(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),Object.defineProperty(t,"prototype",{writable:!1}),e&&r(t,e)}s.d(e,{Z:()=>i})},963:(t,e,s)=>{"use strict";s.d(e,{Z:()=>n});var r=s(2),i=s(326);function n(t,e){if(e&&("object"===(0,r.Z)(e)||"function"==typeof e))return e;if(void 0!==e)throw new TypeError("Derived constructors may only return object or undefined");return(0,i.Z)(t)}},142:(t,e,s)=>{"use strict";s.d(e,{Z:()=>i});var r=s(2);function i(t){var e=function(t,e){if("object"!==(0,r.Z)(t)||null===t)return t;var s=t[Symbol.toPrimitive];if(void 0!==s){var i=s.call(t,e||"default");if("object"!==(0,r.Z)(i))return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"===(0,r.Z)(e)?e:String(e)}},2:(t,e,s)=>{"use strict";function r(t){return r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},r(t)}s.d(e,{Z:()=>r})}},e={};function s(r){var i=e[r];if(void 0!==i)return i.exports;var n=e[r]={exports:{}};return t[r].call(n.exports,n,n.exports,s),n.exports}s.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return s.d(e,{a:e}),e},s.d=(t,e)=>{for(var r in e)s.o(e,r)&&!s.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:e[r]})},s.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e);var r={};return(()=>{"use strict";s.d(r,{default:()=>ht});var t=s(52),e=s.n(t),i=s(806),n=s.n(i),a=s(671),o=s(144),l=s(340),h=s(963),u=s(120),p=s(342),c=s(142);function d(t,e,s){return(e=(0,c.Z)(e))in t?Object.defineProperty(t,e,{value:s,enumerable:!0,configurable:!0,writable:!0}):t[e]=s,t}var _=s(752),f=s(755),v=s(973);function y(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}const m=function(t){(0,l.Z)(s,t);var e=y(s);function s(){var t,r=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return(0,a.Z)(this,s),(t=e.call(this,r))._transformTweenOptions(),!t._o.isTweenLess&&t._makeTween(),!t._o.isTimelineLess&&t._makeTimeline(),t}return(0,o.Z)(s,[{key:"play",value:function(){return this.timeline.play.apply(this.timeline,arguments),this}},{key:"playBackward",value:function(){return this.timeline.playBackward.apply(this.timeline,arguments),this}},{key:"pause",value:function(){return this.timeline.pause.apply(this.timeline,arguments),this}},{key:"stop",value:function(){return this.timeline.stop.apply(this.timeline,arguments),this}},{key:"reset",value:function(){return this.timeline.reset.apply(this.timeline,arguments),this}},{key:"replay",value:function(){return this.timeline.replay.apply(this.timeline,arguments),this}},{key:"replayBackward",value:function(){return this.timeline.replayBackward.apply(this.timeline,arguments),this}},{key:"resume",value:function(){return this.timeline.resume.apply(this.timeline,arguments),this}},{key:"setProgress",value:function(){return this.timeline.setProgress.apply(this.timeline,arguments),this}},{key:"setSpeed",value:function(){return this.timeline.setSpeed.apply(this.timeline,arguments),this}},{key:"_transformTweenOptions",value:function(){}},{key:"_makeTween",value:function(){this._o.callbacksContext=this._o.callbacksContext||this,this.tween=new f.Z(this._o),this._o.isTimelineLess&&(this.timeline=this.tween)}},{key:"_makeTimeline",value:function(){this._o.timeline=this._o.timeline||{},this._o.timeline.callbacksContext=this._o.callbacksContext||this,this.timeline=new p.Z(this._o.timeline),this._isTimeline=!0,this.tween&&this.timeline.add(this.tween)}}]),s}(v.Z);function g(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var k=function(t){(0,l.Z)(r,t);var s=g(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"then",value:function(t){if(null==t||!Object.keys(t).length)return 1;var e=this._history[this._history.length-1],s=this._mergeThenOptions(e,t);this._resetMergedFlags(s);var r=new this.constructor(s);return r._masterModule=this,this._modules.push(r),this.timeline.append(r),this}},{key:"_resetMergedFlags",value:function(t){return t.isTimelineLess=!0,t.isShowStart=!1,t.isRefreshState=!1,t.callbacksContext=this._props.callbacksContext||this,t.prevChainModule=e().getLastItem(this._modules),t.masterModule=this,t}},{key:"_vars",value:function(){(0,_.Z)((0,u.Z)(r.prototype),"_vars",this).call(this),this._masterModule=this._o.masterModule,this._isChained=!!this._masterModule;var t=e().cloneObj(this._props);for(var s in this._arrayPropertyMap)if(this._o[s]){var i=this._parsePreArrayProperty(s,this._o[s]);t[s]=i}this._history=[t],this._modules=[this],this._nonMergeProps={shape:1}}},{key:"_mergeThenOptions",value:function(t,e){var s={};return this._mergeStartLoop(s,t),this._mergeEndLoop(s,t,e),this._history.push(s),s}},{key:"_checkStartValue",value:function(t,e){return e}},{key:"_mergeStartLoop",value:function(t,s){for(var r in s){var i=s[r];null!=s[r]&&(e().isTweenProp(r)&&"duration"!==r||(this._isDelta(i)?t[r]=e().getDeltaEnd(i):t[r]=i))}}},{key:"_mergeEndLoop",value:function(t,e,s){for(var r in s)if("parent"!=r){var i=s[r],n=null!=e[r]?e[r]:this._defaults[r];if(n=this._checkStartValue(r,n),null!=i){var a="radiusX"===r||"radiusY"===r;a&&null==n&&(n=e.radius),(a="scaleX"===r||"scaleY"===r)&&null==n&&(n=e.scale),t[r]=this._mergeThenProperty(r,n,i)}}else t[r]=s[r]}},{key:"_mergeThenProperty",value:function(t,s,r){var i,n,a="boolean"==typeof r;if(e().isTweenProp(t)||this._nonMergeProps[t]||a)return r;if(e().isObject(r)&&null!=r.to&&(i=r.curve,n=r.easing,r=r.to),this._isDelta(r))return this._parseDeltaValues(t,r);var o,l,h=this._parsePreArrayProperty(t,r);return this._isDelta(s)?(d(o={},e().getDeltaEnd(s),h),d(o,"easing",n),d(o,"curve",i),o):(d(l={},s,h),d(l,"easing",n),d(l,"curve",i),l)}},{key:"_getArrayLength",value:function(t){return e().isArray(t)?t.length:-1}},{key:"_isDelta",value:function(t){var s=e().isObject(t);return!(!(s=s&&!t.unit)||e().isArray(t)||e().isDOM(t))}},{key:"_isFirstInChain",value:function(){return!this._masterModule}},{key:"_isLastInChain",value:function(){var t=this._masterModule;return t?this===e().getLastItem(t._modules):1===this._modules.length}}]),r}(m);const w=k;function b(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var S=function(t){(0,l.Z)(r,t);var s=b(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"tune",value:function(t){if(t&&Object.keys(t).length){for(var s in this._transformHistory(t),this._tuneNewOptions(t),this._history[0]=e().cloneObj(this._props),this._arrayPropertyMap)null!=t[s]&&(this._history[0][s]=this._preparsePropValue(s,t[s]));this._tuneSubModules(),this._resetTweens()}return this}},{key:"generate",value:function(){return this.tune(this._o)}},{key:"_transformHistory",value:function(t){for(var e in t){var s=t[e];this._transformHistoryFor(e,this._preparsePropValue(e,s))}}},{key:"_transformHistoryFor",value:function(t,e){for(var s=0;s<this._history.length&&null!=(e=this._transformHistoryRecord(s,t,e));s++);}},{key:"_transformHistoryRecord",value:function(t,s,r,i,n){if(null==r)return null;i=null==i?this._history[t]:i,n=null==n?this._history[t+1]:n;var a=i[s],o=null==n?null:n[s];if(0===t){if(i[s]=r,e().isTweenProp(s)&&"duration"!==s)return null;var l=this._isRewriteNext(a,o),h=this._isDelta(r)?e().getDeltaEnd(r):r;return l?h:null}return this._isDelta(a)?(i[s]=d({},r,e().getDeltaEnd(a)),null):(i[s]=r,this._isRewriteNext(a,o)?r:null)}},{key:"_isRewriteNext",value:function(t,s){if(null==s&&null!=t)return!1;var r=t===s,i=this._isDelta(s),n=!1,a=!1;return this._isDelta(t)&&i?e().getDeltaEnd(t)==e().getDeltaStart(s)&&(a=!0):i&&(n=e().getDeltaStart(s)==="".concat(t)),r||n||a}},{key:"_tuneSubModules",value:function(){for(var t=1;t<this._modules.length;t++)this._modules[t]._tuneNewOptions(this._history[t])}},{key:"_resetTweens",value:function(){var t=0,e=this.timeline._timelines;if(null!=e){for(var s=0;s<e.length;s++){var r=e[s],i=e[s-1];t+=i?i._props.repeatTime:0,this._resetTween(r,this._history[s],t)}this.timeline._setProp(this._props.timeline),this.timeline._recalcTotalDuration()}}},{key:"_resetTween",value:function(t,e){var s=arguments.length>2&&void 0!==arguments[2]?arguments[2]:0;e.shiftTime=s,t._setProp(e)}}]),r}(w);const P=S;function T(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var x=function(t){(0,l.Z)(r,t);var s=T(r);function r(t,e){var i;return(0,a.Z)(this,r),i=s.call(this),(0,h.Z)(i,i._init(t,e))}return(0,o.Z)(r,[{key:"then",value:function(t){if(null==t)return this;for(var e=0;e<this._modules.length;e++)this._modules[e].then(this._getOptionByIndex(e,t));return this.timeline._recalcTotalDuration(),this}},{key:"tune",value:function(t){if(null==t)return this;for(var e=0;e<this._modules.length;e++)this._modules[e].tune(this._getOptionByIndex(e,t));return this.timeline._recalcTotalDuration(),this}},{key:"generate",value:function(){for(var t=0;t<this._modules.length;t++)this._modules[t].generate();return this.timeline._recalcTotalDuration(),this}},{key:"_getOptionByMod",value:function(t,s,r){var i=r[t];i+""!="[object NodeList]"&&i+""!="[object HTMLCollection]"||(i=Array.prototype.slice.call(i,0));var n=e().isArray(i)?i[s%i.length]:i;return e().parseIfStagger(n,s)}},{key:"_getOptionByIndex",value:function(t,e){var s=this,r={};return Object.keys(e).forEach((function(i){return r[i]=s._getOptionByMod(i,t,e)})),r}},{key:"_getChildQuantity",value:function(t,s){if("number"==typeof t)return t;var r=s[t];return e().isArray(r)||r+""=="[object NodeList]"?r.length:r+""=="[object HTMLCollection]"?Array.prototype.slice.call(r,0).length:r instanceof HTMLElement||"string"==typeof r?1:void 0}},{key:"_init",value:function(t,e){var s=this._getChildQuantity(t.quantifier||"el",t);this._createTimeline(t),this._modules=[];for(var r=0;r<s;r++){var i=this._getOptionByIndex(r,t);i.isRunLess=!0,i.index=r;var n=new e(i);this._modules.push(n),this.timeline.add(n)}return this}},{key:"_createTimeline",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.timeline=new p.Z(t.timeline)}},{key:"_makeTween",value:function(){}},{key:"_makeTimeline",value:function(){}}]),r}(P),C=s(47),O=s(283),M=s.n(O);function Z(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var D=function(t){(0,l.Z)(r,t);var s=Z(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"_declareDefaults",value:function(){this._defaults={parent:document.body,className:"",shape:"circle",stroke:"transparent",strokeOpacity:1,strokeLinecap:"",strokeWidth:2,strokeDasharray:0,strokeDashoffset:0,fill:"deeppink",fillOpacity:1,isSoftHide:!0,isForce3d:!1,left:"50%",top:"50%",x:0,y:0,rotate:0,scale:1,scaleX:null,scaleY:null,origin:"50% 50%",opacity:1,rx:0,ry:0,points:3,radius:50,radiusX:null,radiusY:null,isShowStart:!1,isShowEnd:!0,isRefreshState:!0,duration:400,width:null,height:null,isWithShape:!0,callbacksContext:this}}},{key:"tune",value:function(t){return(0,_.Z)((0,u.Z)(r.prototype),"tune",this).call(this,t),this._getMaxSizeInChain(),this}},{key:"then",value:function(t){return(0,_.Z)((0,u.Z)(r.prototype),"then",this).call(this,t),this._getMaxSizeInChain(),this}},{key:"_vars",value:function(){return(0,_.Z)((0,u.Z)(r.prototype),"_vars",this).call(this),this._lastSet={},this._prevChainModule=this._o.prevChainModule,this.isForeign=!!this._o.ctx,this.isForeignBit=!!this._o.shape}},{key:"_render",value:function(){return this._isRendered||this._isChained?this._isChained&&(this.el=this._masterModule.el,this.shapeModule=this._masterModule.shapeModule):(this.el=document.createElement("div"),this.el.setAttribute("data-name","mojs-shape"),this.el.setAttribute("class",this._props.className),this._createShape(),this._props.parent.appendChild(this.el),this._setElStyles(),this._setProgress(0,0),this._props.isShowStart?this._show():this._hide(),this._isRendered=!0),this}},{key:"_setElStyles",value:function(){if(this.el){var t=this._props,s=this.el.style,r=t.shapeWidth,i=t.shapeHeight;if(s.position="absolute",this._setElSizeStyles(r,i),t.isForce3d){var n="backface-visibility";s["".concat(n)]="hidden",s["".concat(e().prefix.css).concat(n)]="hidden"}}}},{key:"_setElSizeStyles",value:function(t,e){var s=this.el.style;s.width="".concat(t,"px"),s.height="".concat(e,"px"),s["margin-left"]="".concat(-t/2,"px"),s["margin-top"]="".concat(-e/2,"px")}},{key:"_draw",value:function(){if(this.shapeModule){var t=this._props,e=this.shapeModule._props;e.rx=t.rx,e.ry=t.ry,e.stroke=t.stroke,e["stroke-width"]=t.strokeWidth,e["stroke-opacity"]=t.strokeOpacity,e["stroke-dasharray"]=t.strokeDasharray,e["stroke-dashoffset"]=t.strokeDashoffset,e["stroke-linecap"]=t.strokeLinecap,e.fill=t.fill,e["fill-opacity"]=t.fillOpacity,e.radius=t.radius,e.radiusX=t.radiusX,e.radiusY=t.radiusY,e.points=t.points,this.shapeModule._draw(),this._drawEl()}}},{key:"_drawEl",value:function(){if(null==this.el)return!0;var t=this._props,s=this.el.style;if(this._isPropChanged("opacity")&&(s.opacity=t.opacity),!this.isForeign){this._isPropChanged("left")&&(s.left=t.left),this._isPropChanged("top")&&(s.top=t.top);var r=this._isPropChanged("x"),i=this._isPropChanged("y"),n=r||i,a=this._isPropChanged("scaleX"),o=this._isPropChanged("scaleY"),l=this._isPropChanged("scale"),h=this._isPropChanged("rotate");if(l=l||a||o,n||l||h){var u=this._fillTransform();s["".concat(e().prefix.css,"transform")]=u,s.transform=u}if(this._isPropChanged("origin")||this._deltas.origin){var p=this._fillOrigin();s["".concat(e().prefix.css,"transform-origin")]=p,s["transform-origin"]=p}}}},{key:"_isPropChanged",value:function(t){return null==this._lastSet[t]&&(this._lastSet[t]={}),this._lastSet[t].value!==this._props[t]&&(this._lastSet[t].value=this._props[t],!0)}},{key:"_tuneNewOptions",value:function(t){if((0,_.Z)((0,u.Z)(r.prototype),"_tuneNewOptions",this).call(this,t),null==t||!Object.keys(t).length)return 1;this._setElStyles()}},{key:"_getMaxRadius",value:function(t){var e;return e=this._getRadiusSize("radius"),this._getRadiusSize(t,e)}},{key:"_increaseSizeWithEasing",value:function(){var t=this._props,e=this._o.easing;switch(e&&"string"==typeof e&&e.toLowerCase()){case"elastic.out":case"elastic.inout":t.size*=1.25;break;case"back.out":case"back.inout":t.size*=1.1}}},{key:"_getRadiusSize",value:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0,s=this._deltas[t];return null!=s?Math.max(Math.abs(s.end),Math.abs(s.start)):null!=this._props[t]?parseFloat(this._props[t]):e}},{key:"_getShapeSize",value:function(){var t=this._props,e=this._getMaxStroke();t.shapeWidth=null!=t.width?t.width:2*this._getMaxRadius("radiusX")+e,t.shapeHeight=null!=t.height?t.height:2*this._getMaxRadius("radiusY")+e}},{key:"_createShape",value:function(){if(this._getShapeSize(),this._props.isWithShape){var t=this._props,e=n().getShape(this._props.shape);this.shapeModule=new e({width:t.shapeWidth,height:t.shapeHeight,parent:this.el})}}},{key:"_getMaxSizeInChain",value:function(){for(var t=0,e=0,s=0;s<this._modules.length;s++)this._modules[s]._getShapeSize(),t=Math.max(t,this._modules[s]._props.shapeWidth),e=Math.max(e,this._modules[s]._props.shapeHeight);this.shapeModule&&this.shapeModule._setSize(t,e),this._setElSizeStyles(t,e)}},{key:"_getMaxStroke",value:function(){var t=this._props,e=this._deltas.strokeWidth;return null!=e?Math.max(e.start,e.end):t.strokeWidth}},{key:"_setProgress",value:function(t,e){v.Z.prototype._setProgress.call(this,t,e),this._draw(t)}},{key:"_applyCallbackOverrides",value:function(t){var e=this,s=this._props;t.callbackOverrides={onUpdate:function(t,s){return e._setProgress(t,s)},onStart:function(t){e._isChained||(t?e._show():s.isShowStart||e._hide())},onComplete:function(t){e._isLastInChain()&&(t?s.isShowEnd||e._hide():e._show())},onRefresh:function(t){s.isRefreshState&&t&&e._refreshBefore()}}}},{key:"_transformTweenOptions",value:function(){this._applyCallbackOverrides(this._o)}},{key:"_fillTransform",value:function(){var t=this._props,e=null!=t.scaleX?t.scaleX:t.scale,s=null!=t.scaleY?t.scaleY:t.scale,r="".concat(e,", ").concat(s);return"translate(".concat(t.x,", ").concat(t.y,") rotate(").concat(t.rotate,"deg) scale(").concat(r,")")}},{key:"_fillOrigin",value:function(){for(var t=this._props,e="",s=0;s<t.origin.length;s++)e+="".concat(t.origin[s].string," ");return e}},{key:"_refreshBefore",value:function(){this._setProgress(this.tween._props.easing(0),0),this._props.isShowStart?this._show():this._hide()}},{key:"_showByTransform",value:function(){this._lastSet.scale=null,this._drawEl()}}]),r}(P);const R=D;function E(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}const L=function(t){(0,l.Z)(r,t);var s=E(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"_declareDefaults",value:function(){(0,_.Z)((0,u.Z)(r.prototype),"_declareDefaults",this).call(this),this._defaults.isSwirl=!0,this._defaults.swirlSize=10,this._defaults.swirlFrequency=3,this._defaults.pathScale=1,this._defaults.degreeShift=0,this._defaults.radius=5,this._defaults.x=0,this._defaults.y=0,this._defaults.scale={1:0},this._defaults.direction=1}},{key:"_extendDefaults",value:function(){(0,_.Z)((0,u.Z)(r.prototype),"_extendDefaults",this).call(this),this._calcPosData()}},{key:"_tuneNewOptions",value:function(t){null!=t&&((0,_.Z)((0,u.Z)(r.prototype),"_tuneNewOptions",this).call(this,t),null==t.x&&null==t.y||this._calcPosData())}},{key:"_calcPosData",value:function(){var t=this._getPosValue("x"),s=this._getPosValue("y"),r=90+Math.atan(s.delta/t.delta||0)*e().RAD_TO_DEG;this._posData={radius:Math.sqrt(t.delta*t.delta+s.delta*s.delta),rotate:t.delta<0?r+180:r,x:t,y:s}}},{key:"_getPosValue",value:function(t){var s=this._deltas[t];if(s)return delete this._deltas[t],{start:s.start.value,end:s.end.value,delta:s.delta,units:s.end.unit};var r=e().parseUnit(this._props[t]);return{start:r.value,end:r.value,delta:0,units:r.unit}}},{key:"_setProgress",value:function(t,e){this._progress=t,this._calcCurrentProps(t,e),this._calcSwirlXY(t),this._draw(t)}},{key:"_calcSwirlXY",value:function(t){var s=this._props,r=this._posData.rotate+s.degreeShift,i=e().getRadialPoint({rotate:s.isSwirl?r+this._getSwirl(t):r,radius:t*this._posData.radius*s.pathScale,center:{x:this._posData.x.start,y:this._posData.y.start}}),n=i.x,a=i.y,o=1e-6;n>0&&n<o&&(n=o),a>0&&a<o&&(a=o),n<0&&n>-1e-6&&(n=-1e-6),a<0&&a>-1e-6&&(a=-1e-6),s.x=this._o.ctx?n:"".concat(n).concat(this._posData.x.units),s.y=this._o.ctx?a:"".concat(a).concat(this._posData.y.units)}},{key:"_getSwirl",value:function(t){var e=this._props;return e.direction*e.swirlSize*Math.sin(e.swirlFrequency*t)}},{key:"_draw",value:function(){var t=this._props.isWithShape?"_draw":"_drawEl";R.prototype[t].call(this)}}]),r}(R);function A(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var I=function(t){(0,l.Z)(r,t);var s=A(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"_declareDefaults",value:function(){this._defaults={count:5,degree:360,radius:{0:50},radiusX:null,radiusY:null,width:0,height:0}}},{key:"then",value:function(t){this._removeTweenProperties(t);var e=this._masterThen(t),s=this._childThen(t);return this._setSwirlDuration(e,this._calcPackTime(s)),this.timeline._recalcTotalDuration(),this}},{key:"tune",value:function(t){return null==t||(this._saveTimelineOptions(t),this.timeline._setProp(this._timelineOptions),this._removeTweenProperties(t),this._tuneNewOptions(t),this.masterSwirl.tune(t),this._tuneSwirls(t),this._recalcModulesTime()),this}},{key:"_extendDefaults",value:function(){this._removeTweenProperties(this._o),(0,_.Z)((0,u.Z)(r.prototype),"_extendDefaults",this).call(this)}},{key:"_removeTweenProperties",value:function(t){for(var s in e().tweenOptionMap)null==this._defaults[s]&&delete t[s]}},{key:"_recalcModulesTime",value:function(){for(var t=this.masterSwirl._modules,e=this._swirls,s=0,r=0;r<t.length;r++){var i=t[r].tween,n=this._calcPackTime(e[r]);i._setProp({duration:n,shiftTime:s}),s+=n}this.timeline._recalcTotalDuration()}},{key:"_tuneSwirls",value:function(t){for(var e=this._swirls[0],s=0;s<e.length;s++){var r=e[s],i=this._getChildOption(t||{},s),n=null!=i.degreeShift;n||(i.degreeShift=this._swirls[0][s]._props.degreeShift),this._addBurstProperties(i,s),n||delete i.degreeShift,r.tune(i),this._refreshBurstOptions(r._modules,s)}}},{key:"_refreshBurstOptions",value:function(t,e){for(var s=1;s<t.length;s++){var r=t[s],i={};this._addBurstProperties(i,e,s),r._tuneNewOptions(i)}}},{key:"_masterThen",value:function(t){this.masterSwirl.then(t);var s=e().getLastItem(this.masterSwirl._modules);return this._masterSwirls.push(s),s}},{key:"_childThen",value:function(t){for(var s=this._swirls[0],r=[],i=0;i<s.length;i++){var n=this._getChildOption(t,i),a=s[i];n.parent=this.el,this._addBurstProperties(n,i,this._masterSwirls.length-1),a.then(n),r.push(e().getLastItem(a._modules))}return this._swirls[this._masterSwirls.length-1]=r,r}},{key:"_vars",value:function(){(0,_.Z)((0,u.Z)(r.prototype),"_vars",this).call(this),this._bufferTimeline=new p.Z}},{key:"_render",value:function(){this._o.isWithShape=!1,this._o.isSwirl=this._props.isSwirl,this._o.callbacksContext=this,this._saveTimelineOptions(this._o),this.masterSwirl=new Y(this._o),this._masterSwirls=[this.masterSwirl],this.el=this.masterSwirl.el,this._renderSwirls()}},{key:"_renderSwirls",value:function(){for(var t=this._props,e=[],s=0;s<t.count;s++){var r=this._getChildOption(this._o,s);e.push(new j(this._addOptionalProps(r,s)))}this._swirls={0:e},this._setSwirlDuration(this.masterSwirl,this._calcPackTime(e))}},{key:"_saveTimelineOptions",value:function(t){this._timelineOptions=t.timeline,delete t.timeline}},{key:"_calcPackTime",value:function(t){for(var e=0,s=0;s<t.length;s++){var r=t[s].tween._props;e=Math.max(r.repeatTime/r.speed,e)}return e}},{key:"_setSwirlDuration",value:function(t,e){t.tween._setProp("duration",e),t.timeline&&t.timeline._recalcTotalDuration&&t.timeline._recalcTotalDuration()}},{key:"_getChildOption",value:function(t,e){var s={};for(var r in t.children)s[r]=this._getPropByMod(r,e,t.children);return s}},{key:"_getPropByMod",value:function(t,s){var r=(arguments.length>2&&void 0!==arguments[2]?arguments[2]:{})[t];return e().isArray(r)?r[s%r.length]:r}},{key:"_addOptionalProps",value:function(t,e){return t.index=e,t.parent=this.masterSwirl.el,this._addBurstProperties(t,e),t}},{key:"_addBurstProperties",value:function(t,e,s){var r=this._index;this._index=e;var i=this._parseProperty("degreeShift",t.degreeShift||0);this._index=r;var n=this._props,a=n.degree%360==0?n.count:n.count-1||1,o=n.degree/a,l=this._getSidePoint("start",e*o+i,s),h=this._getSidePoint("end",e*o+i,s);t.x=this._getDeltaFromPoints("x",l,h),t.y=this._getDeltaFromPoints("y",l,h),t.rotate=this._getBitRotation(t.rotate||0,i,e)}},{key:"_getBitRotation",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0,s=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0,r=arguments.length>2?arguments[2]:void 0,i=this._props,n=i.degree%360==0?i.count:i.count-1||1,a=r*(i.degree/n)+90;if(a+=s,this._isDelta(t)){var o={},l=Object.keys(t)[0],h=t[l];l=e().parseStringOption(l,r),h=e().parseStringOption(h,r),o[parseFloat(l)+a]=parseFloat(h)+a,t=o}else t+=a;return t}},{key:"_getSidePoint",value:function(t,s,r){var i=this._getSideRadius(t,r);return e().getRadialPoint({radius:i.radius,radiusX:i.radiusX,radiusY:i.radiusY,rotate:s,center:{x:0,y:0}})}},{key:"_getSideRadius",value:function(t,e){return{radius:this._getRadiusByKey("radius",t,e),radiusX:this._getRadiusByKey("radiusX",t,e),radiusY:this._getRadiusByKey("radiusY",t,e)}}},{key:"_getRadiusByKey",value:function(t,e){var s=arguments.length>2&&void 0!==arguments[2]?arguments[2]:0,r=this._masterSwirls[s],i=r._deltas,n=r._props;return null!=i[t]?i[t][e]:null!=n[t]?n[t]:void 0}},{key:"_getDeltaFromPoints",value:function(t,e,s){var r={};return e[t]===s[t]?r=e[t]:r[e[t]]=s[t],r}},{key:"_makeTimeline",value:function(){this._o.timeline=this._timelineOptions,(0,_.Z)((0,u.Z)(r.prototype),"_makeTimeline",this).call(this),this.timeline.add(this.masterSwirl,this._swirls[0])}},{key:"_makeTween",value:function(){}},{key:"_hide",value:function(){}},{key:"_show",value:function(){}}]),r}(P),j=function(t){(0,l.Z)(s,t);var e=A(s);function s(){return(0,a.Z)(this,s),e.apply(this,arguments)}return(0,o.Z)(s,[{key:"_declareDefaults",value:function(){(0,_.Z)((0,u.Z)(s.prototype),"_declareDefaults",this).call(this),this._defaults.isSwirl=!1,this._o.duration=null!=this._o.duration?this._o.duration:700}},{key:"_calcSwirlXY",value:function(t){var e=this._props.degreeShift;this._props.degreeShift=0,(0,_.Z)((0,u.Z)(s.prototype),"_calcSwirlXY",this).call(this,t),this._props.degreeShift=e}}]),s}(L),Y=function(t){(0,l.Z)(s,t);var e=A(s);function s(){return(0,a.Z)(this,s),e.apply(this,arguments)}return(0,o.Z)(s,[{key:"_declareDefaults",value:function(){(0,_.Z)((0,u.Z)(s.prototype),"_declareDefaults",this).call(this),this._defaults.scale=1,this._defaults.width=0,this._defaults.height=0,this._defaults.radius={25:75}}}]),s}(j);I.ChildSwirl=j,I.MainSwirl=Y;const B=I;var X=function(){function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};(0,a.Z)(this,t),this._o=e,this._createTween(e.tweenOptions),!this._o.isChained&&this.refresh(!0)}return(0,o.Z)(t,[{key:"refresh",value:function(t){this._previousValues=[];for(var e=this._o.deltas,s=0;s<e.length;s++){var r=e[s].name;this._previousValues.push({name:r,value:this._o.props[r]})}return this.tween._refresh(t),this}},{key:"restore",value:function(){for(var t=this._previousValues,e=0;e<t.length;e++){var s=t[e];this._o.props[s.name]=s.value}return this}},{key:"_createTween",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},e=this;t.callbackOverrides={onUpdate:function(t,s){e._calcCurrentProps(t,s)}},this._o.isChained||(t.callbackOverrides.onRefresh=function(t,s,r){e._calcCurrentProps(s,r)}),t.callbacksContext=this._o.callbacksContext,this.tween=new f.Z(t)}},{key:"_calcCurrentProps",value:function(t,e){for(var s=this._o.deltas,r=0;r<s.length;r++){var i=s[r].type;this["_calcCurrent_".concat(i)](s[r],t,e)}}},{key:"_calcCurrent_color",value:function(t,e,s){var r,i,n,a,o=t.start,l=t.delta;if(t.curve){var h=t.curve(s);r=parseInt(h*(o.r+s*l.r),10),i=parseInt(h*(o.g+s*l.g),10),n=parseInt(h*(o.b+s*l.b),10),a=parseFloat(h*(o.a+s*l.a))}else r=parseInt(o.r+e*l.r,10),i=parseInt(o.g+e*l.g,10),n=parseInt(o.b+e*l.b,10),a=parseFloat(o.a+e*l.a);this._o.props[t.name]="rgba(".concat(r,",").concat(i,",").concat(n,",").concat(a,")")}},{key:"_calcCurrent_number",value:function(t,e,s){this._o.props[t.name]=t.curve?t.curve(s)*(t.start+s*t.delta):t.start+e*t.delta}},{key:"_calcCurrent_unit",value:function(t,e,s){var r=t.curve?t.curve(s)*(t.start.value+s*t.delta):t.start.value+e*t.delta;this._o.props[t.name]="".concat(r).concat(t.end.unit)}},{key:"_calcCurrent_array",value:function(t,e,s){for(var r=t.name,i=this._o.props,n="",a=t.curve?t.curve(s):null,o=0;o<t.delta.length;o++){var l=t.delta[o],h=t.curve?a*(t.start[o].value+s*l.value):t.start[o].value+e*l.value;n+="".concat(h).concat(l.unit," ")}i[r]=n}}]),t}();const F=X;function U(t,e){var s=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),s.push.apply(s,r)}return s}function $(t){for(var e=1;e<arguments.length;e++){var s=null!=arguments[e]?arguments[e]:{};e%2?U(Object(s),!0).forEach((function(e){d(t,e,s[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(s)):U(Object(s)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(s,e))}))}return t}var H={};f.Z.prototype._declareDefaults.call(H);for(var z=Object.keys(H._defaults),N=0;N<z.length;N++)H._defaults[z[N]]=1;H._defaults.timeline=1;var q=H._defaults,V=function(){function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};(0,a.Z)(this,t),this._o=e,this._shortColors={transparent:"rgba(0,0,0,0)",none:"rgba(0,0,0,0)",aqua:"rgb(0,255,255)",black:"rgb(0,0,0)",blue:"rgb(0,0,255)",fuchsia:"rgb(255,0,255)",gray:"rgb(128,128,128)",green:"rgb(0,128,0)",lime:"rgb(0,255,0)",maroon:"rgb(128,0,0)",navy:"rgb(0,0,128)",olive:"rgb(128,128,0)",purple:"rgb(128,0,128)",red:"rgb(255,0,0)",silver:"rgb(192,192,192)",teal:"rgb(0,128,128)",white:"rgb(255,255,255)",yellow:"rgb(255,255,0)",orange:"rgb(255,128,0)"},this._ignoreDeltasMap={prevChainModule:1,masterModule:1},this._parseDeltas(e.options),this._createDeltas(),this._createTimeline(this._mainTweenOptions)}return(0,o.Z)(t,[{key:"refresh",value:function(t){for(var e=0;e<this._deltas.length;e++)this._deltas[e].refresh(t);return this}},{key:"restore",value:function(){for(var t=0;t<this._deltas.length;t++)this._deltas[t].restore();return this}},{key:"_createTimeline",value:function(){this.timeline=new p.Z,this.timeline.add(this._deltas)}},{key:"_createDeltas",value:function(){this._deltas=[],this._deltas.push(this._createDelta(this._mainDeltas,this._mainTweenOptions));for(var t=0;t<this._childDeltas.length;t++){var e=this._childDeltas[t];this._deltas.push(this._createDelta([e.delta],e.tweenOptions))}}},{key:"_createDelta",value:function(t,e){var s=this._o;return new F({deltas:t,tweenOptions:e,props:s.props,isChained:s.isChained,callbacksContext:s.callbacksContext})}},{key:"_parseDeltas",value:function(t){var e=this._splitTweenOptions(t),s=e.delta;this._mainTweenOptions=e.tweenOptions,this._mainDeltas=[],this._childDeltas=[];for(var r=Object.keys(s),i=0;i<r.length;i++){var n=r[i];if(this._isDelta(s[n])&&!this._ignoreDeltasMap[n]){var a=this._splitAndParseDelta(n,s[n]);a.tweenOptions?this._childDeltas.push(a):this._mainDeltas.push(a.delta)}}}},{key:"_splitAndParseDelta",value:function(t,e){var s=this._splitTweenOptions(e);return s.delta=this._parseDelta(t,s.delta),s}},{key:"_parseDelta",value:function(t,e,s){return this._o.customProps&&null!=this._o.customProps[t]?this._parseDeltaByCustom(t,e,s):this._parseDeltaByGuess(t,e,s)}},{key:"_parseDeltaByCustom",value:function(t,e,s){return this._parseNumberDelta(t,e,s)}},{key:"_parseDeltaByGuess",value:function(t,e,s){var r=this._preparseDelta(e).start,i=this._o;return!isNaN(parseFloat(r))||r.match(/rand\(/)||r.match(/stagger\(/)?i.arrayPropertyMap&&i.arrayPropertyMap[t]?this._parseArrayDelta(t,e):i.numberPropertyMap&&i.numberPropertyMap[t]?this._parseNumberDelta(t,e,s):this._parseUnitDelta(t,e,s):this._parseColorDelta(t,e)}},{key:"_splitTweenOptions",value:function(t){t=$({},t);for(var e=Object.keys(t),s={},r=null,i=0;i<e.length;i++){var n=e[i];q[n]&&(null!=t[n]&&(s[n]=t[n],r=!0),delete t[n])}return{delta:t,tweenOptions:r?s:void 0}}},{key:"_isDelta",value:function(t){var s=e().isObject(t);return!(!(s=s&&!t.unit)||e().isArray(t)||e().isDOM(t))}},{key:"_parseColorDelta",value:function(t,s){if("strokeLinecap"===t)return e().warn("Sorry, stroke-linecap property is not animatable yet, using the start(#{start}) value instead",s),{};var r=this._preparseDelta(s),i=this._makeColorObj(r.start),n=this._makeColorObj(r.end);return{type:"color",name:t,start:i,end:n,curve:r.curve,delta:{r:n.r-i.r,g:n.g-i.g,b:n.b-i.b,a:n.a-i.a}}}},{key:"_parseArrayDelta",value:function(t,s){var r=this._preparseDelta(s),i=this._strToArr(r.start),n=this._strToArr(r.end);e().normDashArrays(i,n);for(var a=0;a<i.length;a++){var o=n[a];e().mergeUnits(i[a],o,t)}return{type:"array",name:t,start:i,end:n,delta:e().calcArrDelta(i,n),curve:r.curve}}},{key:"_parseUnitDelta",value:function(t,s,r){var i=this._preparseDelta(s),n=e().parseUnit(e().parseStringOption(i.end,r)),a=e().parseUnit(e().parseStringOption(i.start,r));return e().mergeUnits(a,n,t),{type:"unit",name:t,start:a,end:n,delta:n.value-a.value,curve:i.curve}}},{key:"_parseNumberDelta",value:function(t,s,r){var i=this._preparseDelta(s),n=parseFloat(e().parseStringOption(i.end,r)),a=parseFloat(e().parseStringOption(i.start,r));return{type:"number",name:t,start:a,end:n,delta:n-a,curve:i.curve}}},{key:"_preparseDelta",value:function(t){var e=(t=$({},t)).curve;null!=e&&((e=M().parseEasing(e))._parent=this),delete t.curve;var s=Object.keys(t)[0];return{start:s,end:t[s],curve:e}}},{key:"_makeColorObj",value:function(t){var s={};if("#"===t[0]){var r=/^#?([a-f\d]{1,2})([a-f\d]{1,2})([a-f\d]{1,2})$/i.exec(t);if(r){var i=2===r[1].length?r[1]:r[1]+r[1],n=2===r[2].length?r[2]:r[2]+r[2],a=2===r[3].length?r[3]:r[3]+r[3];s={r:parseInt(i,16),g:parseInt(n,16),b:parseInt(a,16),a:1}}}if("#"!==t[0]){var o,l="r"===t[0]&&"g"===t[1]&&"b"===t[2];l&&(o=t),l||(this._shortColors[t]?o=this._shortColors[t]:(e().div.style.color=t,o=e().computedStyle(e().div).color));var h=new RegExp("^rgba?\\((\\d{1,3}),\\s?(\\d{1,3}),\\s?(\\d{1,3}),?\\s?(\\d{1}|0?\\.\\d{1,})?\\)$","gi").exec(o),u=parseFloat(h[4]||1);h&&(s={r:parseInt(h[1],10),g:parseInt(h[2],10),b:parseInt(h[3],10),a:null==u||isNaN(u)?1:u})}return s}},{key:"_strToArr",value:function(t){var s=[];return"number"!=typeof t||isNaN(t)?(t.trim().split(/\s+/gim).forEach((function(t){s.push(e().parseUnit(e().parseIfRand(t)))})),s):(s.push(e().parseUnit(t)),s)}}]),t}();const W=V;function G(t,e){var s=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),s.push.apply(s,r)}return s}function Q(t){for(var e=1;e<arguments.length;e++){var s=null!=arguments[e]?arguments[e]:{};e%2?G(Object(s),!0).forEach((function(e){d(t,e,s[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(s)):G(Object(s)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(s,e))}))}return t}function K(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var s,r=(0,u.Z)(t);if(e){var i=(0,u.Z)(this).constructor;s=Reflect.construct(r,arguments,i)}else s=r.apply(this,arguments);return(0,h.Z)(this,s)}}var J={};f.Z.prototype._declareDefaults.call(J);for(var tt=Object.keys(J._defaults),et=0;et<tt.length;et++)J._defaults[tt[et]]=1;J._defaults.timeline=1;var st=J._defaults,rt=function(t){(0,l.Z)(r,t);var s=K(r);function r(){return(0,a.Z)(this,r),s.apply(this,arguments)}return(0,o.Z)(r,[{key:"_declareDefaults",value:function(){this._defaults={x:0,y:0,z:0,skewX:0,skewY:0,rotateX:0,rotateY:0,rotateZ:0,scale:1,scaleX:1,scaleY:1,isSoftHide:!0,isShowStart:!0,isShowEnd:!0,isForce3d:!1,isRefreshState:!0},this._drawExclude={el:1},this._3dProperties=["rotateX","rotateY","z"],this._arrayPropertyMap={transformOrigin:1,backgroundPosition:1},this._numberPropertyMap={opacity:1,scale:1,scaleX:1,scaleY:1,rotateX:1,rotateY:1,rotateZ:1,skewX:1,skewY:1},this._prefixPropertyMap={transform:1,transformOrigin:1},this._prefix=e().prefix.css}},{key:"then",value:function(t){if(null==t||!Object.keys(t).length)return 1;var s=e().getLastItem(this._modules);return s.deltas.refresh(!1),this._history[this._history.length-1]=s._o,(0,_.Z)((0,u.Z)(r.prototype),"then",this).call(this,t),s.deltas.restore(),this}},{key:"_checkStartValue",value:function(t,s){return null==s?null!=this._defaults[t]?this._defaults[t]:null!=this._customProps[t]?this._customProps[t]:null!=e().defaultStyles[t]?e().defaultStyles[t]:0:s}},{key:"_draw",value:function(){for(var t=this._props,e=0;e<this._drawProps.length;e++){var s=this._drawProps[e];this._setStyle(s,t[s])}this._drawTransform(),this._customDraw&&this._customDraw(this._props.el,this._props)}},{key:"_drawTransform",value:function(){var t=this._props,e=this._is3d?"translate3d(".concat(t.x,", ").concat(t.y,", ").concat(t.z,") rotateX(").concat(t.rotateX,"deg) rotateY(").concat(t.rotateY,"deg) rotateZ(").concat(t.rotateZ,"deg) skew(").concat(t.skewX,"deg, ").concat(t.skewY,"deg) scale(").concat(t.scaleX,", ").concat(t.scaleY,")"):"translate(".concat(t.x,", ").concat(t.y,") rotate(").concat(t.rotateZ,"deg) skew(").concat(t.skewX,"deg, ").concat(t.skewY,"deg) scale(").concat(t.scaleX,", ").concat(t.scaleY,")");this._setStyle("transform",e)}},{key:"_render",value:function(){if(!this._o.prevChainModule){for(var t=this._props,e=0;e<this._renderProps.length;e++){var s=this._renderProps[e],r=t[s];r="number"==typeof r?"".concat(r,"px"):r,this._setStyle(s,r)}this._draw(),t.isShowStart||this._hide()}}},{key:"_setStyle",value:function(t,e){if(this._state[t]!==e){var s=this._props.el.style;s[t]=e,this._prefixPropertyMap[t]&&(s["".concat(this._prefix).concat(t)]=e),this._state[t]=e}}},{key:"_extendDefaults",value:function(){this._props=this._o.props||{},this._renderProps=[],this._drawProps=[],this._saveCustomProperties(this._o);var t=Q({},this._o);t=this._addDefaults(t);for(var s=Object.keys(t),r=0;r<s.length;r++){var i=s[r],n=!this._drawExclude[i]&&null==this._defaults[i]&&!st[i],a=this._customProps[i];e().isDelta(t[i])||st[i]?n&&!a&&this._drawProps.push(i):(this._parseOption(i,t[i]),"el"===i&&(this._props.el=e().parseEl(t.el),this.el=this._props.el),n&&!a&&this._renderProps.push(i))}this._createDeltas(t)}},{key:"_saveCustomProperties",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this._customProps=t.customProperties||{},this._customProps=Q({},this._customProps),this._customDraw=this._customProps.draw,delete this._customProps.draw,delete t.customProperties,this._copyDefaultCustomProps()}},{key:"_copyDefaultCustomProps",value:function(){for(var t in this._customProps)null==this._o[t]&&(this._o[t]=this._customProps[t])}},{key:"_resetMergedFlags",value:function(t){return(0,_.Z)((0,u.Z)(r.prototype),"_resetMergedFlags",this).call(this,t),t.props=this._props,t.customProperties=this._customProps,t}},{key:"_parseOption",value:function(t,s){(0,_.Z)((0,u.Z)(r.prototype),"_parseOption",this).call(this,t,s);var i=this._props[t];e().isArray(i)&&(this._props[t]=this._arrToString(i))}},{key:"_arrToString",value:function(t){for(var e="",s=0;s<t.length;s++)e+="".concat(t[s].string," ");return e}},{key:"_addDefaults",value:function(t){for(var e in this._is3d=!1,this._defaults)null==t[e]?t[e]="scaleX"===e||"scaleY"===e?null!=t.scale?t.scale:this._defaults.scale:this._defaults[e]:-1!==this._3dProperties.indexOf(e)&&(this._is3d=!0);return this._o.isForce3d&&(this._is3d=!0),t}},{key:"_vars",value:function(){this.deltas.refresh(!1),(0,_.Z)((0,u.Z)(r.prototype),"_vars",this).call(this),this._state={},this.deltas.restore(!1)}},{key:"_createDeltas",value:function(t){this.deltas=new W({options:t,props:this._props,arrayPropertyMap:this._arrayPropertyMap,numberPropertyMap:this._numberPropertyMap,customProps:this._customProps,callbacksContext:t.callbacksContext||this,isChained:!!this._o.prevChainModule}),this._o.prevChainModule&&(this.timeline=this.deltas.timeline)}},{key:"_makeTween",value:function(){}},{key:"_makeTimeline",value:function(){this._o.prevChainModule||(this._o.timeline=this._o.timeline||{},this._addCallbackOverrides(this._o.timeline),(0,_.Z)((0,u.Z)(r.prototype),"_makeTimeline",this).call(this),this.timeline.add(this.deltas))}},{key:"_addCallbackOverrides",value:function(t){var e=this,s=this._props;t.callbackOverrides={onUpdate:this._draw,onRefresh:this._props.isRefreshState?this._draw:void 0,onStart:function(t){e._isChained||(t&&!s.isShowStart?e._show():s.isShowStart||e._hide())},onComplete:function(t){e._isChained||(t?s.isShowEnd||e._hide():s.isShowEnd||e._show())}}}},{key:"_showByTransform",value:function(){this._drawTransform()}},{key:"_mergeThenProperty",value:function(t,s,r){var i="boolean"==typeof r;if(e().isTweenProp(t)||this._nonMergeProps[t]||i)return r;var n={};if(e().isObject(r)&&null!=r.to){for(var a in r)(st[a]||"curve"===a)&&(n[a]=r[a],delete r[a]);r=r.to}if(this._isDelta(r)){var o={};for(var l in r)(st[l]||"curve"===l)&&(o[l]=r[l],delete r[l]);return Q(Q({},this._parseDeltaValues(t,r)),o)}var h=this._parsePreArrayProperty(t,r);return this._isDelta(s)?Q(d({},e().getDeltaEnd(s),h),n):Q(d({},s,h),n)}}]),r}(w);const it=rt,nt=function(){function t(){var s=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return(0,a.Z)(this,t),this.o=s,this.o.el?(this._vars(),this._declareDefaults(),this._extendDefaults(),this._parseFrames(),this._frames.length<=2&&e().warn("Spriter: only ".concat(this._frames.length," frames found")),this._frames.length<1&&e().error("Spriter: there is no frames to animate, aborting"),this._createTween(),this):e().error('No "el" option specified, aborting')}return(0,o.Z)(t,[{key:"_declareDefaults",value:function(){this._defaults={duration:500,delay:0,easing:"linear.none",repeat:0,yoyo:!1,isRunLess:!1,isShowEnd:!1,onStart:null,onUpdate:null,onComplete:null}}},{key:"_vars",value:function(){this._props=e().cloneObj(this.o),this.el=this.o.el,this._frames=[]}},{key:"run",value:function(){return this.timeline.play()}},{key:"_extendDefaults",value:function(){return e().extend(this._props,this._defaults)}},{key:"_parseFrames",value:function(){this._frames=Array.prototype.slice.call(this.el.children,0),this._frames.forEach((function(t){return t.style.opacity=0})),this._frameStep=1/this._frames.length}},{key:"_createTween",value:function(){var t=this;this._tween=new f.Z({duration:this._props.duration,delay:this._props.delay,yoyo:this._props.yoyo,repeat:this._props.repeat,easing:this._props.easing,onStart:function(){return t._props.onStart&&t._props.onStart()},onComplete:function(){return t._props.onComplete&&t._props.onComplete()},onUpdate:function(e){return t._setProgress(e)}}),this.timeline=new p.Z,this.timeline.add(this._tween),this._props.isRunLess||this._startTween()}},{key:"_startTween",value:function(){var t=this;setTimeout((function(){return t.timeline.play()}),1)}},{key:"_setProgress",value:function(t){var e=Math.floor(t/this._frameStep);if(this._prevFrame!=this._frames[e]){this._prevFrame&&(this._prevFrame.style.opacity=0);var s=1===t&&this._props.isShowEnd?e-1:e;this._frames[s]&&(this._frames[s].style.opacity=1),this._prevFrame=this._frames[e]}this._props.onUpdate&&this._props.onUpdate(t)}}]),t}();var at=s(291),ot=s.n(at),lt={revision:"1.7.1",isDebug:!1,helpers:e(),Shape:R,ShapeSwirl:L,Burst:B,Html:it,stagger:function(t){return function(e){return new x(e,t)}},Spriter:nt,MotionPath:ot(),Tween:f.Z,Timeline:p.Z,Tweenable:m,Thenable:w,Tunable:P,Module:v.Z,tweener:C.Z,easing:M(),shapesMap:n(),_pool:{Delta:F,Deltas:W},h:e(),delta:e().delta,addShape:n().addShape,CustomShape:n().custom,Transit:R,Swirl:L};"undefined"!=typeof window&&(window.mojs=lt);const ht=lt})(),r=r.default})(), true?module.exports=e():0;

/***/ },

/***/ "./resources/js/games/animate.js"
/*!***************************************!*\
  !*** ./resources/js/games/animate.js ***!
  \***************************************/
() {

// Promise-based animate.css helper
// Usage:
//   await $el.animateCSS('headShake');
//   await $el.animateCSS('bounce', { speed: 'fast' });
//   $el.animateCSS('tada').then(() => { ... });

(function ($) {
  $.fn.animateCSS = function (animation, options) {
    // Back-compat: animateCSS(animation, speedString)
    if (typeof options === "string") options = {
      speed: options
    };
    var opts = $.extend({
      speed: null,
      // 'slow'|'slower'|'fast'|'faster'|null
      keepClasses: false,
      // keep animate classes after finishing
      namespace: ".animateCSS"
    }, options || {});
    var animClass = "animate__" + animation;
    var baseClass = "animate__animated";
    var speedClass = opts.speed ? "animate__" + opts.speed : null;
    var endEvents = "animationend" + opts.namespace + " webkitAnimationEnd" + opts.namespace + " oAnimationEnd" + opts.namespace + " MSAnimationEnd" + opts.namespace;
    var $els = this;
    return new Promise(function (resolve) {
      if (!$els.length) return resolve($els);
      var remaining = $els.length;
      function doneOne() {
        remaining -= 1;
        if (remaining <= 0) resolve($els);
      }
      $els.each(function () {
        var el = this;
        var $el = $(el);

        // Prevent stacking handlers if called repeatedly
        $el.off(endEvents);

        // Remove classes so we can restart the same animation reliably
        $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));

        // Force reflow to restart animation
        // eslint-disable-next-line no-unused-expressions
        el.offsetWidth;

        // Add classes to trigger animation
        $el.addClass(baseClass);
        if (speedClass) $el.addClass(speedClass);
        $el.addClass(animClass);

        // Resolve on animation end (and cleanup)
        $el.one(endEvents, function () {
          if (!opts.keepClasses) {
            $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));
          }
          doneOne();
        });

        // Safety: if animationend never fires (rare), resolve anyway
        // Uses computed duration if available, otherwise fallback.
        var cs = window.getComputedStyle(el);
        var dur = parseFloat(cs.animationDuration) || 0;
        var delay = parseFloat(cs.animationDelay) || 0;
        var totalMs = Math.max(50, (dur + delay) * 1000 + 50);
        setTimeout(function () {
          // If still has animClass, we likely missed animationend
          if ($el.hasClass(animClass)) {
            if (!opts.keepClasses) {
              $el.removeClass(baseClass + " " + animClass + (speedClass ? " " + speedClass : ""));
            }
            // remove handler to avoid double-callback
            $el.off(endEvents);
            doneOne();
          }
        }, totalMs);
      });
    });
  };
})(jQuery);

/***/ },

/***/ "./resources/js/games/duel/DuelLobby.js"
/*!**********************************************!*\
  !*** ./resources/js/games/duel/DuelLobby.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   serializeSettings: () => (/* binding */ serializeSettings)
/* harmony export */ });
/* harmony import */ var _transport__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./transport */ "./resources/js/games/duel/transport.js");
/* harmony import */ var _dialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./dialog */ "./resources/js/games/duel/dialog.js");
/* harmony import */ var _presence__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./presence */ "./resources/js/games/duel/presence.js");
/* harmony import */ var _idle__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./idle */ "./resources/js/games/duel/idle.js");
/* harmony import */ var _codeInputs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./codeInputs */ "./resources/js/games/duel/codeInputs.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }





var ROOM_KEY = 'pianolit.games.duel.waiting';
function saveRoom(id) {
  try {
    sessionStorage.setItem(ROOM_KEY, id);
  } catch (_) {}
}
function forgetRoom() {
  try {
    sessionStorage.removeItem(ROOM_KEY);
  } catch (_) {}
}
function savedRoom() {
  try {
    return sessionStorage.getItem(ROOM_KEY);
  } catch (_) {
    return null;
  }
}
function serializeSettings(form) {
  var settings = {};
  var _iterator = _createForOfIteratorHelper(form.elements),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var _input$name;
      var input = _step.value;
      var name = (_input$name = input.name) === null || _input$name === void 0 ? void 0 : _input$name.replace(/\[\]$/, '');
      if (!name || input.type === 'submit' || input.type === 'button') continue;
      if (input.name.endsWith('[]')) {
        settings[name] || (settings[name] = []);
        if (input.checked) settings[name].push(input.value);
      } else if (input.type === 'checkbox') settings[name] = input.checked;else if (input.type === 'radio') {
        if (input.checked) settings[name] = input.value;
      } else settings[name] = input.type === 'number' || input.type === 'range' ? Number(input.value) : input.value;
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  return settings;
}
$(function () {
  if (!window.__duelConfig || window.__duelState) return;
  var transport = new _transport__WEBPACK_IMPORTED_MODULE_0__.DuelTransport();
  var joinForm = document.querySelector('[data-duel-join-form]');
  var codeInputs = (0,_codeInputs__WEBPACK_IMPORTED_MODULE_4__.bindDuelCodeInputs)(joinForm);
  var room = null;
  var pending = false;
  var navigating = false;
  var expiryTimer = null;
  var presence = null;
  var idle = null;
  var showError = function showError(error) {
    $('[data-duel-error]').text(error.message).prop('hidden', false).show();
  };
  var receive = function receive(state) {
    var _idle2, _presence2;
    if (!room || state.id !== room.id || state.revision < room.revision) return;
    room = _objectSpread(_objectSpread({}, room), state);
    if (room.status === 'waiting_for_opponent') return;
    if (['expired', 'cancelled'].includes(room.status)) {
      var _idle, _presence;
      (_idle = idle) === null || _idle === void 0 || _idle.stop();
      (_presence = presence) === null || _presence === void 0 || _presence.stop();
      forgetRoom();
      clearTimeout(expiryTimer);
      (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
        phase: room.left_by ? 'opponent-left' : room.status,
        message: "This Duel has ".concat(room.status === 'expired' ? 'expired' : 'been cancelled', "."),
        exit: true
      });
      return;
    }
    if (navigating) return;
    navigating = true;
    (_idle2 = idle) === null || _idle2 === void 0 || _idle2.stop();
    (_presence2 = presence) === null || _presence2 === void 0 || _presence2.stop();
    forgetRoom();
    clearTimeout(expiryTimer);
    (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
      phase: 'joined',
      message: 'Opponent joined ✓ Opening your game…'
    });
    setTimeout(function () {
      location.href = room.game_url;
    }, 500);
  };
  var waitForOpponent = function waitForOpponent(state) {
    var _idle3, _presence4;
    room = state;
    saveRoom(room.id);
    (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
      message: 'Your duel code is · Waiting for opponent…',
      code: room.code,
      cancel: true
    });
    (_idle3 = idle) === null || _idle3 === void 0 || _idle3.stop();
    idle = new _idle__WEBPACK_IMPORTED_MODULE_3__.DuelIdle({
      onExpire: function onExpire() {
        var _presence3, _transport$echo;
        navigating = true;
        (_presence3 = presence) === null || _presence3 === void 0 || _presence3.stop();
        clearTimeout(expiryTimer);
        forgetRoom();
        transport.leaveOnExit(room.id);
        (_transport$echo = transport.echo) === null || _transport$echo === void 0 || _transport$echo.disconnect();
        location.href = window.__duelConfig.home;
      },
      onResume: function onResume() {
        return (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
          message: 'Your duel code is · Waiting for opponent…',
          code: room.code,
          cancel: true
        });
      }
    }).start();
    (_presence4 = presence) === null || _presence4 === void 0 || _presence4.stop();
    presence = new _presence__WEBPACK_IMPORTED_MODULE_2__.DuelPresence(transport, room.id, function (state) {
      // The transport refreshes authoritative state independently of presence heartbeats.
      if (['expired', 'cancelled'].includes(state.status)) receive(state);
    }, showError);
    presence.start();
    // Subscribe after the initial response so joins cannot be missed.
    transport.subscribe(room.id, {
      update: receive,
      connected: function connected() {
        return transport.request("/".concat(room.id)).then(receive)["catch"](showError);
      },
      disconnected: function disconnected() {
        return showError(new Error('Reconnecting to the Duel server…'));
      },
      error: function error() {
        return showError(new Error('Could not connect to multiplayer. Please try again.'));
      }
    });
    expiryTimer = setTimeout(function () {
      return transport.request("/".concat(room.id)).then(receive)["catch"](showError);
    }, Math.max(0, Date.parse(room.expires_at) - Date.now() - transport.offset) + 50);
  };
  document.addEventListener('click', /*#__PURE__*/function () {
    var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(event) {
      var create, join, form, state, _t;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.p = _context.n) {
          case 0:
            create = event.target.closest('[data-duel-create]');
            join = event.target.closest('[data-duel-join]');
            if (join) {
              $('#duel-modal').one('shown.bs.modal', function () {
                return codeInputs.focus();
              });
              (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
                join: true
              });
            }
            if (!(!create || pending)) {
              _context.n = 1;
              break;
            }
            return _context.a(2);
          case 1:
            pending = true;
            create.disabled = true;
            _context.p = 2;
            form = create.closest('form');
            _context.n = 3;
            return transport.request('', {
              game: create.dataset.duelCreate,
              settings: serializeSettings(form)
            });
          case 3:
            state = _context.v;
            $(form.closest('.modal')).modal('hide');
            waitForOpponent(state);
            _context.n = 5;
            break;
          case 4:
            _context.p = 4;
            _t = _context.v;
            (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.dialog)({
              join: false,
              error: _t.message,
              exit: true
            });
          case 5:
            _context.p = 5;
            pending = false;
            create.disabled = false;
            return _context.f(5);
          case 6:
            return _context.a(2);
        }
      }, _callee, null, [[2, 4, 5, 6]]);
    }));
    return function (_x) {
      return _ref.apply(this, arguments);
    };
  }());
  joinForm.addEventListener('submit', /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(event) {
      var code, button, state, _t2;
      return _regenerator().w(function (_context2) {
        while (1) switch (_context2.p = _context2.n) {
          case 0:
            event.preventDefault();
            code = codeInputs.value();
            if (!(pending || !/^\d{4}$/.test(code))) {
              _context2.n = 1;
              break;
            }
            return _context2.a(2);
          case 1:
            pending = true;
            button = event.target.querySelector('button');
            button.disabled = true;
            _context2.p = 2;
            _context2.n = 3;
            return transport.request('/join', {
              code: code
            });
          case 3:
            state = _context2.v;
            forgetRoom();
            location.href = state.game_url;
            _context2.n = 5;
            break;
          case 4:
            _context2.p = 4;
            _t2 = _context2.v;
            showError(_t2);
          case 5:
            _context2.p = 5;
            pending = false;
            button.disabled = false;
            return _context2.f(5);
          case 6:
            return _context2.a(2);
        }
      }, _callee2, null, [[2, 4, 5, 6]]);
    }));
    return function (_x2) {
      return _ref2.apply(this, arguments);
    };
  }());
  document.querySelector('[data-duel-cancel]').addEventListener('click', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
    var _transport$echo2, _t3, _t4;
    return _regenerator().w(function (_context3) {
      while (1) switch (_context3.p = _context3.n) {
        case 0:
          if (!(pending || !room)) {
            _context3.n = 1;
            break;
          }
          return _context3.a(2);
        case 1:
          pending = true;
          _context3.p = 2;
          _t3 = receive;
          _context3.n = 3;
          return transport.request("/".concat(room.id, "/cancel"), {});
        case 3:
          _t3(_context3.v);
          forgetRoom();
          (0,_dialog__WEBPACK_IMPORTED_MODULE_1__.closeDialog)();
          (_transport$echo2 = transport.echo) === null || _transport$echo2 === void 0 || _transport$echo2.disconnect();
          _context3.n = 5;
          break;
        case 4:
          _context3.p = 4;
          _t4 = _context3.v;
          showError(_t4);
        case 5:
          _context3.p = 5;
          pending = false;
          return _context3.f(5);
        case 6:
          return _context3.a(2);
      }
    }, _callee3, null, [[2, 4, 5, 6]]);
  })));
  var previous = savedRoom();
  if (previous) transport.request("/".concat(previous)).then(function (state) {
    if (state.status === 'waiting_for_opponent') waitForOpponent(state);else {
      room = state;
      receive(state);
    }
  })["catch"](forgetRoom);
});

/***/ },

/***/ "./resources/js/games/duel/codeInputs.js"
/*!***********************************************!*\
  !*** ./resources/js/games/duel/codeInputs.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   bindDuelCodeInputs: () => (/* binding */ bindDuelCodeInputs)
/* harmony export */ });
function bindDuelCodeInputs(form) {
  var inputs = Array.from(form.querySelectorAll('[data-duel-digit]'));
  var _focus = function focus(index) {
    inputs[index].focus();
    inputs[index].select();
  };
  var insert = function insert(index, text) {
    var digits = text.replace(/\D/g, '').slice(0, inputs.length);
    if (!digits) return;
    // A complete pasted or autofilled code replaces the whole code.
    var start = digits.length === inputs.length ? 0 : index;
    var count = Math.min(digits.length, inputs.length - start);
    for (var offset = 0; offset < count; offset++) {
      inputs[start + offset].value = digits[offset];
    }
    _focus(Math.min(start + count, inputs.length - 1));
  };
  inputs.forEach(function (input, index) {
    input.addEventListener('focus', function () {
      return input.select();
    });
    input.addEventListener('input', function () {
      var text = input.value;
      input.value = '';
      insert(index, text);
    });
    input.addEventListener('paste', function (event) {
      event.preventDefault();
      insert(index, event.clipboardData.getData('text'));
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Backspace' && !input.value && index > 0) {
        event.preventDefault();
        inputs[index - 1].value = '';
        _focus(index - 1);
      } else if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        _focus(index - 1);
      } else if (event.key === 'ArrowRight' && index < inputs.length - 1) {
        event.preventDefault();
        _focus(index + 1);
      }
    });
  });
  return {
    value: function value() {
      return inputs.map(function (input) {
        return input.value;
      }).join('');
    },
    focus: function focus() {
      return _focus(Math.max(0, inputs.findIndex(function (input) {
        return !input.value;
      })));
    }
  };
}

/***/ },

/***/ "./resources/js/games/duel/dialog.js"
/*!*******************************************!*\
  !*** ./resources/js/games/duel/dialog.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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

"use strict";
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

"use strict";
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

/***/ "./resources/js/games/duel/transport.js"
/*!**********************************************!*\
  !*** ./resources/js/games/duel/transport.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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

/***/ "./resources/js/games/gameSettingsMemory.js"
/*!**************************************************!*\
  !*** ./resources/js/games/gameSettingsMemory.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   GAME_SETTINGS_KEY: () => (/* binding */ GAME_SETTINGS_KEY),
/* harmony export */   refreshGameCards: () => (/* binding */ refreshGameCards),
/* harmony export */   rememberGameSettings: () => (/* binding */ rememberGameSettings),
/* harmony export */   replayGameUrl: () => (/* binding */ replayGameUrl),
/* harmony export */   restoreGameSettingsForm: () => (/* binding */ restoreGameSettingsForm),
/* harmony export */   savedGameSettings: () => (/* binding */ savedGameSettings)
/* harmony export */ });
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
var GAME_SETTINGS_KEY = 'pianolit.games.game.settings.v1';
function readSettings(getStorage) {
  try {
    var saved = JSON.parse(getStorage().getItem(GAME_SETTINGS_KEY));
    return saved && _typeof(saved) === 'object' && !Array.isArray(saved) ? saved : {};
  } catch (_) {
    return {};
  }
}
function validValue(value) {
  return typeof value === 'boolean' || typeof value === 'number' && Number.isFinite(value) || typeof value === 'string' && value.length <= 2000;
}
function cleanOptions(options) {
  var defaults = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : options;
  if (!options || _typeof(options) !== 'object' || Array.isArray(options)) return null;
  var entries = Object.entries(options).filter(function (_ref) {
    var _ref2 = _slicedToArray(_ref, 2),
      name = _ref2[0],
      value = _ref2[1];
    return Object.hasOwn(defaults, name) && (validValue(value) || Array.isArray(value) && value.length <= 128 && value.every(validValue));
  });
  return entries.length ? Object.fromEntries(entries) : null;
}
function rememberGameSettings(game, options) {
  var getStorage = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function () {
    return window.localStorage;
  };
  var clean = cleanOptions(options);
  if (!clean) return false;
  try {
    getStorage().setItem(GAME_SETTINGS_KEY, JSON.stringify(_objectSpread(_objectSpread({}, readSettings(getStorage)), {}, _defineProperty({}, game, clean))));
    return true;
  } catch (_) {
    return false;
  }
}
function savedGameSettings(game, defaults) {
  var getStorage = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : function () {
    return window.localStorage;
  };
  var saved = cleanOptions(readSettings(getStorage)[game], defaults);
  return saved ? _objectSpread(_objectSpread({}, defaults), saved) : null;
}
function replayGameUrl(url, options) {
  var params = new URLSearchParams();
  Object.entries(options).forEach(function (_ref3) {
    var _ref4 = _slicedToArray(_ref3, 2),
      name = _ref4[0],
      value = _ref4[1];
    if (Array.isArray(value)) {
      // An explicit empty selection must not silently restore the server's defaults.
      (value.length ? value : ['']).forEach(function (item) {
        return params.append("".concat(name, "[]"), String(item));
      });
    } else {
      params.set(name, typeof value === 'boolean' ? value ? '1' : '0' : String(value));
    }
  });
  return "".concat(url.split('?')[0], "?").concat(params);
}
function restoreGameSettingsForm(form, options) {
  _toConsumableArray(form.elements).forEach(function (input) {
    var _input$name;
    var name = (_input$name = input.name) === null || _input$name === void 0 ? void 0 : _input$name.replace(/\[\]$/, '');
    if (!name || !Object.hasOwn(options, name) || input.type === 'hidden') return;
    var value = options[name];
    if (input.type === 'checkbox' || input.type === 'radio') {
      input.checked = Array.isArray(value) ? value.map(String).includes(input.value) : input.type === 'radio' ? String(value) === input.value : !!value && value !== '0';
      if (input.hasAttribute('multichoice') || input.hasAttribute('singlechoice')) {
        var label = input.parentElement.querySelector('label');
        label === null || label === void 0 || label.classList.toggle('btn-secondary', input.checked);
        label === null || label === void 0 || label.classList.toggle('btn-white', !input.checked);
      }
    } else {
      input.value = String(value);
      if (input.type === 'range') input.dispatchEvent(new Event('input', {
        bubbles: true
      }));
    }
  });
}
function refreshGameCards() {
  var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
  var getStorage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {
    return window.localStorage;
  };
  root.querySelectorAll('[data-game-card]').forEach(function (card) {
    var defaults;
    try {
      defaults = JSON.parse(card.dataset.gameDefaults);
    } catch (_) {
      return;
    }
    var saved = savedGameSettings(card.dataset.gameCard, defaults, getStorage);
    card.querySelector('[data-card-setup]').classList.toggle('d-none', !!saved);
    card.querySelector('[data-card-replay]').classList.toggle('d-none', !saved);
    card.querySelector('[data-card-settings]').classList.toggle('d-none', !saved);
    var form = card.querySelector('form[method="GET"]');
    if (form) restoreGameSettingsForm(form, saved || defaults);
    var replay = card.querySelector('[data-card-replay]');
    if (saved) replay.href = replayGameUrl(card.dataset.gameUrl, saved);else replay.removeAttribute('href');
  });
}

/***/ },

/***/ "./resources/js/games/hub.js"
/*!***********************************!*\
  !*** ./resources/js/games/hub.js ***!
  \***********************************/
(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
__webpack_require__(/*! ./animate */ "./resources/js/games/animate.js");
__webpack_require__(/*! ./valueThumbRange */ "./resources/js/games/valueThumbRange.js");
window.CountUp = __webpack_require__(/*! countup.js */ "./node_modules/countup.js/dist/countUp.min.js");
window.mojs = __webpack_require__(/*! @mojs/core */ "./node_modules/@mojs/core/dist/mo.umd.js");
window.Typed = (__webpack_require__(/*! typed.js */ "./node_modules/typed.js/dist/typed.cjs")["default"]) || __webpack_require__(/*! typed.js */ "./node_modules/typed.js/dist/typed.cjs");
window.Confetti = (__webpack_require__(/*! canvas-confetti */ "./node_modules/canvas-confetti/dist/confetti.module.mjs")["default"]);
// PianoLIT already supplies jQuery, Bootstrap, axios, draggable and common helpers.
var _require = __webpack_require__(/*! ./leaderboardProfile */ "./resources/js/games/leaderboardProfile.js"),
  rememberLeaderboardProfile = _require.rememberLeaderboardProfile,
  renderLeaderboardProfile = _require.renderLeaderboardProfile,
  deleteLeaderboardProfile = _require.deleteLeaderboardProfile,
  postSavedLeaderboardScore = _require.postSavedLeaderboardScore,
  leaderboardProfileKey = _require.STORAGE_KEY;
var _require2 = __webpack_require__(/*! ./gameSettingsMemory */ "./resources/js/games/gameSettingsMemory.js"),
  rememberGameSettings = _require2.rememberGameSettings,
  refreshGameCards = _require2.refreshGameCards,
  GAME_SETTINGS_KEY = _require2.GAME_SETTINGS_KEY;
document.addEventListener("touchstart", function () {}, {
  passive: true
});
$(window).on('load', function () {
  $('.modal.modal-autoshow').modal('show');
});
$(function () {
  var gameSettings = document.querySelector('[data-game-settings]');
  if (gameSettings) {
    try {
      rememberGameSettings(gameSettings.dataset.gameSettings, JSON.parse(gameSettings.dataset.gameOptions));
    } catch (_) {/* Keep play available without saved settings. */}
  }
  refreshGameCards();
  window.addEventListener('pageshow', function () {
    return refreshGameCards();
  });
  window.addEventListener('storage', function (event) {
    if (event.key === GAME_SETTINGS_KEY || event.key === null) refreshGameCards();
  });
  document.querySelectorAll('form[data-leaderboard-profile]').forEach(function (form) {
    return rememberLeaderboardProfile(form);
  });
  var joinLeaderboard = document.querySelector('[data-join-leaderboard]');
  var scoreForm = document.querySelector('#save-results-modal form[data-leaderboard-profile]');
  if (joinLeaderboard && scoreForm) {
    $(scoreForm.closest('.modal')).on('show.bs.modal.leaderboardProfile', function (event) {
      if (event.relatedTarget !== joinLeaderboard) return;
      if (!postSavedLeaderboardScore(scoreForm)) return;
      event.preventDefault();
      joinLeaderboard.disabled = true;
    });
    window.addEventListener('pageshow', function () {
      delete scoreForm.dataset.profilePosting;
      joinLeaderboard.disabled = false;
    });
  }
  var profile = document.querySelector('[data-games-profile]');
  if (profile) {
    var refreshProfile = function refreshProfile() {
      return renderLeaderboardProfile(profile);
    };
    refreshProfile();
    profile.querySelector('[data-profile-delete]').addEventListener('click', function () {
      if (!window.confirm('Reset your name, avatar, scores and all game settings on this browser?')) return;
      if (deleteLeaderboardProfile()) {
        refreshGameCards();
        refreshProfile();
      } else window.alert('Could not delete your records. Please try again.');
    });
    window.addEventListener('pageshow', refreshProfile);
    window.addEventListener('storage', function (event) {
      if (event.key === leaderboardProfileKey || event.key === GAME_SETTINGS_KEY || event.key === null) refreshProfile();
    });
  }
  $('.leaderboard-modal').on('show.bs.modal', function () {
    var $players = $(this).find('.leaderboard-player');
    $players.stop(true, true).css({
      opacity: 0,
      top: '6px',
      position: 'relative'
    });
    $players.each(function (index) {
      $(this).delay(index * 100).animate({
        opacity: 1,
        top: 0
      }, 180);
    });
  });
});
$(document).on('change', '[name$="-leaderboard-range"]', function () {
  var url = $(this).data('url');
  var $players = $(this).closest('.leaderboard-wrapper').find('.leaderboard-players');
  axios.get(url).then(function (response) {
    $players.html(response.data);
  })["catch"](function () {
    alert('Something went wrong...');
  });
});
document.addEventListener("DOMContentLoaded", function () {
  var ranges = document.querySelectorAll('input[type="range"][data-range-labels]');
  var _iterator = _createForOfIteratorHelper(ranges),
    _step;
  try {
    var _loop = function _loop() {
      var _range$closest, _ref, _ref2, _range$nextElementSib;
      var range = _step.value;
      var wrap = (_range$closest = range.closest(".mb-4")) !== null && _range$closest !== void 0 ? _range$closest : range.parentElement;
      var labelsSel = range.dataset.rangeLabels; // e.g. "#speedLabels"
      var labelsWrap = (_ref = (_ref2 = labelsSel ? wrap === null || wrap === void 0 ? void 0 : wrap.querySelector(labelsSel) : null) !== null && _ref2 !== void 0 ? _ref2 : labelsSel ? document.querySelector(labelsSel) : null) !== null && _ref !== void 0 ? _ref : labelsSel !== null && labelsSel !== void 0 && labelsSel.startsWith("#") && ((_range$nextElementSib = range.nextElementSibling) === null || _range$nextElementSib === void 0 ? void 0 : _range$nextElementSib.id) === labelsSel.slice(1) ? range.nextElementSibling : null;
      if (!labelsWrap) return 1; // continue
      var labels = Array.from(labelsWrap.querySelectorAll("label"));
      var updateLabels = function updateLabels() {
        var idx = Number(range.value);
        labels.forEach(function (label, i) {
          label.classList.toggle("text-black", i === idx);
          label.classList.toggle("text-light", i !== idx);
        });
      };
      updateLabels();
      range.addEventListener("input", updateLabels);
      range.addEventListener("change", updateLabels);
    };
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      if (_loop()) continue;
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
});
$('.form-number button').on('click', function () {
  var $input = $(this).siblings('input');
  var current = parseInt($input.val(), 10) || 0;
  if ($(this).data('direction') === 'down' && current > $input.attr('min')) {
    $input.val(current - 1);
  } else if ($(this).data('direction') === 'up' && current < $input.attr('max')) {
    $input.val(current + 1);
  }
});
$(document).on('click', '.avatar-picker .player-avatar', function () {
  var $clickedAvatar = $(this);
  var $option = $clickedAvatar.closest('.avatar-option');
  var $container = $clickedAvatar.closest('.avatar-picker');
  var $radio = $option.find('input[type="radio"][name="avatar_url"]');
  if (!$radio.length) return;
  $radio.prop('checked', true).trigger('change');
  $container.find('.player-avatar').addClass('opacity-2');
  $clickedAvatar.removeClass('opacity-2');
});

// /public/js/settings-toggles.js (example path)
// settings/toggles.js
$(document).off("change.settingsToggles").on("change.settingsToggles", 'input[multichoice], input[singlechoice]', function () {
  var $this = $(this);
  var isSingle = $this.is("[singlechoice]");
  var name = $this.attr("name");
  if (isSingle && $this.prop("checked")) {
    $("input[singlechoice][name=\"".concat(CSS.escape(name), "\"]")).not(this).prop("checked", false);
  }
  var $scope = isSingle ? $("input[singlechoice][name=\"".concat(CSS.escape(name), "\"]")) : $this;
  $scope.each(function () {
    var $label = $(this).siblings("label");
    $label.removeClass("btn-white btn-secondary");
    $label.addClass(this.checked ? "btn-secondary" : "btn-white");
  });
});
$(document).on('click', 'button[data-form]', function () {
  var formID = $(this).data('form');
  $('form' + formID).submit();
});
__webpack_require__(/*! ./duel/DuelLobby */ "./resources/js/games/duel/DuelLobby.js");

/***/ },

/***/ "./resources/js/games/leaderboardProfile.js"
/*!**************************************************!*\
  !*** ./resources/js/games/leaderboardProfile.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   STORAGE_KEY: () => (/* binding */ STORAGE_KEY),
/* harmony export */   deleteLeaderboardProfile: () => (/* binding */ deleteLeaderboardProfile),
/* harmony export */   postSavedLeaderboardScore: () => (/* binding */ postSavedLeaderboardScore),
/* harmony export */   rememberLeaderboardProfile: () => (/* binding */ rememberLeaderboardProfile),
/* harmony export */   renderLeaderboardProfile: () => (/* binding */ renderLeaderboardProfile),
/* harmony export */   renderLeaderboardScores: () => (/* binding */ renderLeaderboardScores)
/* harmony export */ });
/* harmony import */ var _gameSettingsMemory__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./gameSettingsMemory */ "./resources/js/games/gameSettingsMemory.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, e) { if ("object" != _typeof(t) || !t) return t; var r; if ("undefined" != typeof Symbol && void 0 !== (r = t[Symbol.toPrimitive])) { var i = r.call(t, e || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === e ? String : Number)(t); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }

var STORAGE_KEY = 'pianolit.games.leaderboard.profile.v1';
function deleteLeaderboardProfile() {
  var getStorage = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : function () {
    return window.localStorage;
  };
  try {
    var storage = getStorage();
    [STORAGE_KEY, _gameSettingsMemory__WEBPACK_IMPORTED_MODULE_0__.GAME_SETTINGS_KEY, 'pianolit.games.note-python.wardrobe.v1'].forEach(function (key) {
      return storage.removeItem(key);
    });
    return true;
  } catch (_) {
    return false;
  }
}
function postSavedLeaderboardScore(form) {
  var _saved;
  var getStorage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {
    return window.localStorage;
  };
  if (form.dataset.profilePosting === 'true') return true;
  var saved;
  try {
    saved = JSON.parse(getStorage().getItem(STORAGE_KEY));
  } catch (_) {
    return false;
  }
  if (typeof ((_saved = saved) === null || _saved === void 0 ? void 0 : _saved.name) !== 'string' || !saved.name.trim()) return false;
  var avatars = _toConsumableArray(form.querySelectorAll('input[name="avatar_url"]'));
  var avatar = avatars.find(function (option) {
    return option.id === saved.avatar;
  });
  if (!avatar) return false;
  if (['game', 'rounds', 'score', 'accuracy', 'duration'].some(function (name) {
    var _form$elements$namedI;
    return !((_form$elements$namedI = form.elements.namedItem(name)) !== null && _form$elements$namedI !== void 0 && _form$elements$namedI.value.trim());
  })) return false;
  form.elements.namedItem('username').value = saved.name;
  avatars.forEach(function (option) {
    option.checked = option === avatar;
  });
  if (!form.checkValidity()) return false;
  form.dataset.profilePosting = 'true';
  form.requestSubmit();
  return true;
}
function savedGames(saved) {
  return Array.isArray(saved === null || saved === void 0 ? void 0 : saved.games) ? saved.games.filter(function (game) {
    return game && typeof game.name === 'string' && Number.isFinite(game.score) && game.score >= 0;
  }).slice(0, 50) : [];
}
function renderLeaderboardScores(menu, saved) {
  menu.querySelector('[data-profile-details]').hidden = !saved;
  menu.querySelector('[data-profile-intro]').hidden = !!saved;
  var name = typeof (saved === null || saved === void 0 ? void 0 : saved.name) === 'string' ? saved.name : '';
  menu.querySelector('[data-profile-name]').textContent = name;
  menu.querySelector('[data-profile-name]').title = name;
  var games = savedGames(saved);
  var count = 0;
  menu.querySelectorAll('[data-profile-game]').forEach(function (row) {
    var result = games.find(function (game) {
      return game.name === row.dataset.profileGame;
    });
    row.hidden = !result;
    row.querySelector('[data-profile-score]').textContent = result ? Math.round(result.score).toLocaleString() : '';
    if (result) count++;
  });
  menu.querySelector('[data-profile-empty]').hidden = count > 0;
}
function renderLeaderboardProfile(menu) {
  var _saved2;
  var getStorage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {
    return window.localStorage;
  };
  var saved;
  var hasSettings = false;
  try {
    var storage = getStorage();
    hasSettings = !!storage.getItem(_gameSettingsMemory__WEBPACK_IMPORTED_MODULE_0__.GAME_SETTINGS_KEY);
    saved = JSON.parse(storage.getItem(STORAGE_KEY));
  } catch (_) {/* Keep the menu usable without browser storage. */}
  var image = menu.querySelector('[data-saved-avatar]');
  var valid = typeof ((_saved2 = saved) === null || _saved2 === void 0 ? void 0 : _saved2.avatar) === 'string' && /^avatar-([1-9]|[1-6][0-9]|7[0-8])$/.test(saved.avatar);
  if (valid) image.src = "".concat(menu.dataset.avatarBase).concat(saved.avatar, ".svg");
  image.hidden = !valid;
  renderLeaderboardScores(menu, valid ? saved : null);
  var reset = menu.querySelector('[data-profile-reset]');
  if (reset) reset.hidden = !saved && !hasSettings;
}
function rememberLeaderboardProfile(form) {
  var getStorage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : function () {
    return window.localStorage;
  };
  var username = form.elements.namedItem('username');
  var avatars = _toConsumableArray(form.querySelectorAll('input[name="avatar_url"]'));
  var storage;
  var saved;
  try {
    storage = getStorage();
    saved = JSON.parse(storage.getItem(STORAGE_KEY));
  } catch (_) {/* Remembering a player is optional when storage is unavailable. */}

  // These attributes exist only after the server has successfully posted a score.
  var postedAvatar = avatars.find(function (avatar) {
    return avatar.value === form.dataset.postedAvatar;
  });
  if (form.dataset.postedName && postedAvatar) {
    var _form$dataset$postedS;
    var games = savedGames(saved);
    var game = form.dataset.postedGame;
    var score = Number(form.dataset.postedScore);
    if (game && (_form$dataset$postedS = form.dataset.postedScore) !== null && _form$dataset$postedS !== void 0 && _form$dataset$postedS.trim() && Number.isFinite(score) && score >= 0) {
      var previous = games.find(function (result) {
        return result.name === game;
      });
      if (previous) previous.score = Math.max(previous.score, score);else games.push({
        name: game,
        score: score
      });
    }
    saved = _objectSpread({
      name: form.dataset.postedName,
      avatar: postedAvatar.id
    }, games.length ? {
      games: games.slice(-50)
    } : {});
    try {
      var _storage;
      (_storage = storage) === null || _storage === void 0 || _storage.setItem(STORAGE_KEY, JSON.stringify(saved));
    } catch (_) {/* Keep play available. */}
  }
  if (!saved || typeof saved.name !== 'string' || !saved.name.trim()) return;
  var avatar = avatars.find(function (option) {
    return option.id === saved.avatar;
  });
  if (!avatar) return;
  if (!username.value) username.value = saved.name;
  if (!avatars.some(function (option) {
    return option.checked;
  })) {
    avatar.checked = true;
    avatars.forEach(function (option) {
      option.closest('.avatar-option').querySelector('.player-avatar').classList.toggle('opacity-2', option !== avatar);
    });
  }
}

/***/ },

/***/ "./resources/js/games/valueThumbRange.js"
/*!***********************************************!*\
  !*** ./resources/js/games/valueThumbRange.js ***!
  \***********************************************/
() {

var RANGE_SELECTOR = ".music-games input[data-value-thumb-range]";
function updateValueThumbRange(range) {
  var wrapper = range.closest(".value-thumb-range");
  var output = wrapper === null || wrapper === void 0 ? void 0 : wrapper.querySelector(".value-thumb-range__value");
  if (!wrapper || !output) return;
  var min = Number(range.min);
  var max = Number(range.max);
  var value = Number(range.value);
  var progress = max === min ? 0 : (value - min) / (max - min);
  var thumbWidth = Number(range.dataset.valueThumbWidth || 52);
  var thumbOffset = thumbWidth / 2 - progress * thumbWidth;
  output.textContent = range.value;
  output.style.left = "calc(".concat(progress * 100, "% + ").concat(thumbOffset, "px)");
}
function initializeValueThumbRanges() {
  var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
  root.querySelectorAll(RANGE_SELECTOR).forEach(function (range) {
    updateValueThumbRange(range);
    if (range.dataset.valueThumbRangeReady) return;
    range.dataset.valueThumbRangeReady = "true";
    range.addEventListener("input", function () {
      return updateValueThumbRange(range);
    });
    range.addEventListener("change", function () {
      return updateValueThumbRange(range);
    });
  });
}
document.addEventListener("DOMContentLoaded", function () {
  return initializeValueThumbRanges();
});

/***/ },

/***/ "./node_modules/countup.js/dist/countUp.min.js"
/*!*****************************************************!*\
  !*** ./node_modules/countup.js/dist/countUp.min.js ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CountUp: () => (/* binding */ i)
/* harmony export */ });
var t=function(){return t=Object.assign||function(t){for(var i,n=1,s=arguments.length;n<s;n++)for(var a in i=arguments[n])Object.prototype.hasOwnProperty.call(i,a)&&(t[a]=i[a]);return t},t.apply(this,arguments)},i=function(){function i(i,n,s){var a=this;this.endVal=n,this.options=s,this.version="2.8.0",this.defaults={startVal:0,decimalPlaces:0,duration:2,useEasing:!0,useGrouping:!0,useIndianSeparators:!1,smartEasingThreshold:999,smartEasingAmount:333,separator:",",decimal:".",prefix:"",suffix:"",enableScrollSpy:!1,scrollSpyDelay:200,scrollSpyOnce:!1},this.finalEndVal=null,this.useEasing=!0,this.countDown=!1,this.error="",this.startVal=0,this.paused=!0,this.once=!1,this.count=function(t){a.startTime||(a.startTime=t);var i=t-a.startTime;a.remaining=a.duration-i,a.useEasing?a.countDown?a.frameVal=a.startVal-a.easingFn(i,0,a.startVal-a.endVal,a.duration):a.frameVal=a.easingFn(i,a.startVal,a.endVal-a.startVal,a.duration):a.frameVal=a.startVal+(a.endVal-a.startVal)*(i/a.duration);var n=a.countDown?a.frameVal<a.endVal:a.frameVal>a.endVal;a.frameVal=n?a.endVal:a.frameVal,a.frameVal=Number(a.frameVal.toFixed(a.options.decimalPlaces)),a.printValue(a.frameVal),i<a.duration?a.rAF=requestAnimationFrame(a.count):null!==a.finalEndVal?a.update(a.finalEndVal):a.options.onCompleteCallback&&a.options.onCompleteCallback()},this.formatNumber=function(t){var i,n,s,e,o=t<0?"-":"";i=Math.abs(t).toFixed(a.options.decimalPlaces);var r=(i+="").split(".");if(n=r[0],s=r.length>1?a.options.decimal+r[1]:"",a.options.useGrouping){e="";for(var l=3,h=0,u=0,p=n.length;u<p;++u)a.options.useIndianSeparators&&4===u&&(l=2,h=1),0!==u&&h%l==0&&(e=a.options.separator+e),h++,e=n[p-u-1]+e;n=e}return a.options.numerals&&a.options.numerals.length&&(n=n.replace(/[0-9]/g,(function(t){return a.options.numerals[+t]})),s=s.replace(/[0-9]/g,(function(t){return a.options.numerals[+t]}))),o+a.options.prefix+n+s+a.options.suffix},this.easeOutExpo=function(t,i,n,s){return n*(1-Math.pow(2,-10*t/s))*1024/1023+i},this.options=t(t({},this.defaults),s),this.formattingFn=this.options.formattingFn?this.options.formattingFn:this.formatNumber,this.easingFn=this.options.easingFn?this.options.easingFn:this.easeOutExpo,this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.endVal=this.validateValue(n),this.options.decimalPlaces=Math.max(this.options.decimalPlaces),this.resetDuration(),this.options.separator=String(this.options.separator),this.useEasing=this.options.useEasing,""===this.options.separator&&(this.options.useGrouping=!1),this.el="string"==typeof i?document.getElementById(i):i,this.el?this.printValue(this.startVal):this.error="[CountUp] target is null or undefined","undefined"!=typeof window&&this.options.enableScrollSpy&&(this.error?console.error(this.error,i):(window.onScrollFns=window.onScrollFns||[],window.onScrollFns.push((function(){return a.handleScroll(a)})),window.onscroll=function(){window.onScrollFns.forEach((function(t){return t()}))},this.handleScroll(this)))}return i.prototype.handleScroll=function(t){if(t&&window&&!t.once){var i=window.innerHeight+window.scrollY,n=t.el.getBoundingClientRect(),s=n.top+window.pageYOffset,a=n.top+n.height+window.pageYOffset;a<i&&a>window.scrollY&&t.paused?(t.paused=!1,setTimeout((function(){return t.start()}),t.options.scrollSpyDelay),t.options.scrollSpyOnce&&(t.once=!0)):(window.scrollY>a||s>i)&&!t.paused&&t.reset()}},i.prototype.determineDirectionAndSmartEasing=function(){var t=this.finalEndVal?this.finalEndVal:this.endVal;this.countDown=this.startVal>t;var i=t-this.startVal;if(Math.abs(i)>this.options.smartEasingThreshold&&this.options.useEasing){this.finalEndVal=t;var n=this.countDown?1:-1;this.endVal=t+n*this.options.smartEasingAmount,this.duration=this.duration/2}else this.endVal=t,this.finalEndVal=null;null!==this.finalEndVal?this.useEasing=!1:this.useEasing=this.options.useEasing},i.prototype.start=function(t){this.error||(this.options.onStartCallback&&this.options.onStartCallback(),t&&(this.options.onCompleteCallback=t),this.duration>0?(this.determineDirectionAndSmartEasing(),this.paused=!1,this.rAF=requestAnimationFrame(this.count)):this.printValue(this.endVal))},i.prototype.pauseResume=function(){this.paused?(this.startTime=null,this.duration=this.remaining,this.startVal=this.frameVal,this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count)):cancelAnimationFrame(this.rAF),this.paused=!this.paused},i.prototype.reset=function(){cancelAnimationFrame(this.rAF),this.paused=!0,this.resetDuration(),this.startVal=this.validateValue(this.options.startVal),this.frameVal=this.startVal,this.printValue(this.startVal)},i.prototype.update=function(t){cancelAnimationFrame(this.rAF),this.startTime=null,this.endVal=this.validateValue(t),this.endVal!==this.frameVal&&(this.startVal=this.frameVal,null==this.finalEndVal&&this.resetDuration(),this.finalEndVal=null,this.determineDirectionAndSmartEasing(),this.rAF=requestAnimationFrame(this.count))},i.prototype.printValue=function(t){var i;if(this.el){var n=this.formattingFn(t);if(null===(i=this.options.plugin)||void 0===i?void 0:i.render)this.options.plugin.render(this.el,n);else if("INPUT"===this.el.tagName)this.el.value=n;else"text"===this.el.tagName||"tspan"===this.el.tagName?this.el.textContent=n:this.el.innerHTML=n}},i.prototype.ensureNumber=function(t){return"number"==typeof t&&!isNaN(t)},i.prototype.validateValue=function(t){var i=Number(t);return this.ensureNumber(i)?i:(this.error="[CountUp] invalid start or end value: ".concat(t),null)},i.prototype.resetDuration=function(){this.startTime=null,this.duration=1e3*Number(this.options.duration),this.remaining=this.duration},i}();


/***/ },

/***/ "./resources/sass/musicgames.scss"
/*!****************************************!*\
  !*** ./resources/sass/musicgames.scss ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/sass/metronome.scss"
/*!***************************************!*\
  !*** ./resources/sass/metronome.scss ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/sass/app.scss"
/*!*********************************!*\
  !*** ./resources/sass/app.scss ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/sass/admin.scss"
/*!***********************************!*\
  !*** ./resources/sass/admin.scss ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/sass/shared.scss"
/*!************************************!*\
  !*** ./resources/sass/shared.scss ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./node_modules/typed.js/dist/typed.cjs"
/*!**********************************************!*\
  !*** ./node_modules/typed.js/dist/typed.cjs ***!
  \**********************************************/
(module) {

function t(){return t=Object.assign?Object.assign.bind():function(t){for(var s=1;s<arguments.length;s++){var e=arguments[s];for(var n in e)({}).hasOwnProperty.call(e,n)&&(t[n]=e[n])}return t},t.apply(null,arguments)}var s={strings:["These are the default values...","You know what you should do?","Use your own!","Have a great day!"],stringsElement:null,typeSpeed:0,startDelay:0,backSpeed:0,smartBackspace:!0,shuffle:!1,backDelay:700,shouldBackspace:!0,fadeOut:!1,fadeOutClass:"typed-fade-out",fadeOutDelay:500,loop:!1,loopCount:Infinity,showCursor:!0,cursorChar:"|",autoInsertCss:!0,attr:null,bindInputFocusEvents:!1,contentType:"html",onBegin:function(t){},onComplete:function(t){},preStringTyped:function(t,s){},onStringTyped:function(t,s){},onLastStringBackspaced:function(t){},onTypingPaused:function(t,s){},onTypingResumed:function(t,s){},onReset:function(t){},onStop:function(t,s){},onStart:function(t,s){},onDestroy:function(t){}},e=new(/*#__PURE__*/function(){function e(){}var n=e.prototype;return n.load=function(e,n,i){if(e.el="string"==typeof i?document.querySelector(i):i,e.options=t({},s,n),e.isInput="input"===e.el.tagName.toLowerCase(),e.attr=e.options.attr,e.bindInputFocusEvents=e.options.bindInputFocusEvents,e.showCursor=!e.isInput&&e.options.showCursor,e.cursorChar=e.options.cursorChar,e.cursorBlinking=!0,e.elContent=e.attr?e.el.getAttribute(e.attr):e.el.textContent,e.contentType=e.options.contentType,e.typeSpeed=e.options.typeSpeed,e.startDelay=e.options.startDelay,e.backSpeed=e.options.backSpeed,e.smartBackspace=e.options.smartBackspace,e.backDelay=e.options.backDelay,e.shouldBackspace=e.options.shouldBackspace,e.fadeOut=e.options.fadeOut,e.fadeOutClass=e.options.fadeOutClass,e.fadeOutDelay=e.options.fadeOutDelay,e.isPaused=!1,e.strings=e.options.strings.map(function(t){return t.trim()}),e.stringsElement="string"==typeof e.options.stringsElement?document.querySelector(e.options.stringsElement):e.options.stringsElement,e.stringsElement){e.strings=[],e.stringsElement.style.cssText="clip: rect(0 0 0 0);clip-path:inset(50%);height:1px;overflow:hidden;position:absolute;white-space:nowrap;width:1px;";var r=Array.prototype.slice.apply(e.stringsElement.children),o=r.length;if(o)for(var a=0;a<o;a+=1)e.strings.push(r[a].innerHTML.trim())}for(var u in e.strPos=0,e.currentElContent=this.getCurrentElContent(e),e.currentElContent&&e.currentElContent.length>0&&(e.strPos=e.currentElContent.length-1,e.strings.unshift(e.currentElContent)),e.sequence=[],e.strings)e.sequence[u]=u;e.arrayPos=0,e.stopNum=0,e.loop=e.options.loop,e.loopCount=e.options.loopCount,e.curLoop=0,e.shuffle=e.options.shuffle,e.pause={status:!1,typewrite:!0,curString:"",curStrPos:0},e.typingComplete=!1,e.autoInsertCss=e.options.autoInsertCss,e.autoInsertCss&&(this.appendCursorAnimationCss(e),this.appendFadeOutAnimationCss(e))},n.getCurrentElContent=function(t){return t.attr?t.el.getAttribute(t.attr):t.isInput?t.el.value:"html"===t.contentType?t.el.innerHTML:t.el.textContent},n.appendCursorAnimationCss=function(t){var s="data-typed-js-cursor-css";if(t.showCursor&&!document.querySelector("["+s+"]")){var e=document.createElement("style");e.setAttribute(s,"true"),e.innerHTML="\n        .typed-cursor{\n          opacity: 1;\n        }\n        .typed-cursor.typed-cursor--blink{\n          animation: typedjsBlink 0.7s infinite;\n          -webkit-animation: typedjsBlink 0.7s infinite;\n                  animation: typedjsBlink 0.7s infinite;\n        }\n        @keyframes typedjsBlink{\n          50% { opacity: 0.0; }\n        }\n        @-webkit-keyframes typedjsBlink{\n          0% { opacity: 1; }\n          50% { opacity: 0.0; }\n          100% { opacity: 1; }\n        }\n      ",document.body.appendChild(e)}},n.appendFadeOutAnimationCss=function(t){var s="data-typed-fadeout-js-css";if(t.fadeOut&&!document.querySelector("["+s+"]")){var e=document.createElement("style");e.setAttribute(s,"true"),e.innerHTML="\n        .typed-fade-out{\n          opacity: 0;\n          transition: opacity .25s;\n        }\n        .typed-cursor.typed-cursor--blink.typed-fade-out{\n          -webkit-animation: 0;\n          animation: 0;\n        }\n      ",document.body.appendChild(e)}},e}()),n=new(/*#__PURE__*/function(){function t(){}var s=t.prototype;return s.typeHtmlChars=function(t,s,e){if("html"!==e.contentType)return s;var n=t.substring(s).charAt(0);if("<"===n||"&"===n){var i;for(i="<"===n?">":";";t.substring(s+1).charAt(0)!==i&&!(1+ ++s>t.length););s++}return s},s.backSpaceHtmlChars=function(t,s,e){if("html"!==e.contentType)return s;var n=t.substring(s).charAt(0);if(">"===n||";"===n){var i;for(i=">"===n?"<":"&";t.substring(s-1).charAt(0)!==i&&!(--s<0););s--}return s},t}());module.exports=/*#__PURE__*/function(){function t(t,s){e.load(this,s,t),this.begin()}var s=t.prototype;return s.toggle=function(){this.pause.status?this.start():this.stop()},s.stop=function(){this.typingComplete||this.pause.status||(this.toggleBlinking(!0),this.pause.status=!0,this.options.onStop(this.arrayPos,this))},s.start=function(){this.typingComplete||this.pause.status&&(this.pause.status=!1,this.pause.typewrite?this.typewrite(this.pause.curString,this.pause.curStrPos):this.backspace(this.pause.curString,this.pause.curStrPos),this.options.onStart(this.arrayPos,this))},s.destroy=function(){this.reset(!1),this.options.onDestroy(this)},s.reset=function(t){void 0===t&&(t=!0),clearInterval(this.timeout),this.replaceText(""),this.cursor&&this.cursor.parentNode&&(this.cursor.parentNode.removeChild(this.cursor),this.cursor=null),this.strPos=0,this.arrayPos=0,this.curLoop=0,t&&(this.insertCursor(),this.options.onReset(this),this.begin())},s.append=function(t){var s=this,e=this.strings[this.strings.length-1];this.strings.push(t),this.sequence=this.strings.map(function(t,s){return s}),this.typingComplete&&(this.timeout=this.shouldBackspace?setTimeout(function(){s.backspace(e,e.length-1)},this.backDelay):setTimeout(function(){s.arrayPos++,s.typewrite(s.strings[s.sequence[s.arrayPos]],0)},this.backDelay))},s.begin=function(){var t=this;this.options.onBegin(this),this.typingComplete=!1,this.shuffleStringsIfNeeded(),this.insertCursor(),this.bindInputFocusEvents&&this.bindFocusEvents(),this.timeout=setTimeout(function(){0===t.strPos?t.typewrite(t.strings[t.sequence[t.arrayPos]],t.strPos):t.backspace(t.strings[t.sequence[t.arrayPos]],t.strPos)},this.startDelay)},s.typewrite=function(t,s){var e=this;this.fadeOut&&this.el.classList.contains(this.fadeOutClass)&&(this.el.classList.remove(this.fadeOutClass),this.cursor&&this.cursor.classList.remove(this.fadeOutClass));var i=this.humanizer(this.typeSpeed),r=1;!0!==this.pause.status?this.timeout=setTimeout(function(){s=n.typeHtmlChars(t,s,e);var i=0,o=t.substring(s);if("^"===o.charAt(0)&&/^\^\d+/.test(o)){var a=1;a+=(o=/\d+/.exec(o)[0]).length,i=parseInt(o),e.temporaryPause=!0,e.options.onTypingPaused(e.arrayPos,e),t=t.substring(0,s)+t.substring(s+a),e.toggleBlinking(!0)}if("`"===o.charAt(0)){for(;"`"!==t.substring(s+r).charAt(0)&&(r++,!(s+r>t.length)););var u=t.substring(0,s),c=t.substring(u.length+1,s+r),p=t.substring(s+r+1);t=u+c+p,r--}e.timeout=setTimeout(function(){e.toggleBlinking(!1),s>=t.length?e.doneTyping(t,s):e.keepTyping(t,s,r),e.temporaryPause&&(e.temporaryPause=!1,e.options.onTypingResumed(e.arrayPos,e))},i)},i):this.setPauseStatus(t,s,!0)},s.keepTyping=function(t,s,e){if(0===s&&(this.toggleBlinking(!1),this.options.preStringTyped(this.arrayPos,this)),this.shouldBackspace){var n=t.substring(0,s+=e);this.replaceText(n)}else{var i=t.substring(s,s+e);s+=e,this.replaceText(i)}this.typewrite(t,s)},s.doneTyping=function(t,s){var e=this;this.options.onStringTyped(this.arrayPos,this),this.toggleBlinking(!0),this.isFinalString()&&(this.complete(),!1===this.loop||this.curLoop===this.loopCount)||(this.timeout=this.shouldBackspace?setTimeout(function(){e.backspace(t,s)},this.backDelay):setTimeout(function(){e.arrayPos++,e.typewrite(e.strings[e.sequence[e.arrayPos]],0)},this.backDelay))},s.backspace=function(t,s){var e=this;if(!0!==this.pause.status){if(this.fadeOut)return this.initFadeOut();this.toggleBlinking(!1);var i=this.humanizer(this.backSpeed);this.timeout=setTimeout(function(){s=n.backSpaceHtmlChars(t,s,e);var i=t.substring(0,s);if(e.replaceText(i),e.smartBackspace){var r=e.strings[e.sequence[e.arrayPos+1]];e.stopNum=r&&i===r.substring(0,s)?s:0}s>e.stopNum?(s--,e.backspace(t,s)):s<=e.stopNum&&(e.isFinalString()?e.lastStringBackspaced():(e.arrayPos++,e.typewrite(e.strings[e.sequence[e.arrayPos]],s)))},i)}else this.setPauseStatus(t,s,!1)},s.isFinalString=function(){return this.arrayPos===this.strings.length-1},s.lastStringBackspaced=function(){this.arrayPos=0,this.options.onLastStringBackspaced(),this.loop&&(this.shuffleStringsIfNeeded(),this.begin())},s.complete=function(){this.options.onComplete(this),this.loop?this.curLoop++:this.typingComplete=!0},s.setPauseStatus=function(t,s,e){this.pause.typewrite=e,this.pause.curString=t,this.pause.curStrPos=s},s.toggleBlinking=function(t){this.cursor&&(this.pause.status||this.cursorBlinking!==t&&(this.cursorBlinking=t,t?this.cursor.classList.add("typed-cursor--blink"):this.cursor.classList.remove("typed-cursor--blink")))},s.humanizer=function(t){return Math.round(Math.random()*t/2)+t},s.shuffleStringsIfNeeded=function(){this.shuffle&&(this.sequence=this.sequence.sort(function(){return Math.random()-.5}))},s.initFadeOut=function(){var t=this;return this.el.className+=" "+this.fadeOutClass,this.cursor&&(this.cursor.className+=" "+this.fadeOutClass),setTimeout(function(){t.arrayPos++,t.replaceText(""),t.strings.length>t.arrayPos?t.typewrite(t.strings[t.sequence[t.arrayPos]],0):(t.typewrite(t.strings[0],0),t.arrayPos=0)},this.fadeOutDelay)},s.replaceText=function(t){this.attr?this.el.setAttribute(this.attr,t):this.isInput?this.el.value=t:"html"===this.contentType?this.shouldBackspace?this.el.innerHTML=t:this.el.innerHTML+=t:this.el.textContent=t},s.bindFocusEvents=function(){var t=this;this.isInput&&(this.el.addEventListener("focus",function(s){t.stop()}),this.el.addEventListener("blur",function(s){t.el.value&&0!==t.el.value.length||t.start()}))},s.insertCursor=function(){this.showCursor&&(this.cursor||(this.cursor=document.createElement("span"),this.cursor.className="typed-cursor",this.cursor.setAttribute("aria-hidden",!0),this.cursor.innerHTML=this.cursorChar,this.el.parentNode&&this.el.parentNode.insertBefore(this.cursor,this.el.nextSibling)))},t}();
//# sourceMappingURL=typed.cjs.map


/***/ },

/***/ "./node_modules/canvas-confetti/dist/confetti.module.mjs"
/*!***************************************************************!*\
  !*** ./node_modules/canvas-confetti/dist/confetti.module.mjs ***!
  \***************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   create: () => (/* binding */ create),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// canvas-confetti v1.9.4 built on 2025-10-25T05:14:56.640Z
var module = {};

// source content
/* globals Map */

(function main(global, module, isWorker, workerSize) {
  var canUseWorker = !!(
    global.Worker &&
    global.Blob &&
    global.Promise &&
    global.OffscreenCanvas &&
    global.OffscreenCanvasRenderingContext2D &&
    global.HTMLCanvasElement &&
    global.HTMLCanvasElement.prototype.transferControlToOffscreen &&
    global.URL &&
    global.URL.createObjectURL);

  var canUsePaths = typeof Path2D === 'function' && typeof DOMMatrix === 'function';
  var canDrawBitmap = (function () {
    // this mostly supports ssr
    if (!global.OffscreenCanvas) {
      return false;
    }

    try {
      var canvas = new OffscreenCanvas(1, 1);
      var ctx = canvas.getContext('2d');
      ctx.fillRect(0, 0, 1, 1);
      var bitmap = canvas.transferToImageBitmap();
      ctx.createPattern(bitmap, 'no-repeat');
    } catch (e) {
      return false;
    }

    return true;
  })();

  function noop() {}

  // create a promise if it exists, otherwise, just
  // call the function directly
  function promise(func) {
    var ModulePromise = module.exports.Promise;
    var Prom = ModulePromise !== void 0 ? ModulePromise : global.Promise;

    if (typeof Prom === 'function') {
      return new Prom(func);
    }

    func(noop, noop);

    return null;
  }

  var bitmapMapper = (function (skipTransform, map) {
    // see https://github.com/catdad/canvas-confetti/issues/209
    // creating canvases is actually pretty expensive, so we should create a
    // 1:1 map for bitmap:canvas, so that we can animate the confetti in
    // a performant manner, but also not store them forever so that we don't
    // have a memory leak
    return {
      transform: function(bitmap) {
        if (skipTransform) {
          return bitmap;
        }

        if (map.has(bitmap)) {
          return map.get(bitmap);
        }

        var canvas = new OffscreenCanvas(bitmap.width, bitmap.height);
        var ctx = canvas.getContext('2d');
        ctx.drawImage(bitmap, 0, 0);

        map.set(bitmap, canvas);

        return canvas;
      },
      clear: function () {
        map.clear();
      }
    };
  })(canDrawBitmap, new Map());

  var raf = (function () {
    var TIME = Math.floor(1000 / 60);
    var frame, cancel;
    var frames = {};
    var lastFrameTime = 0;

    if (typeof requestAnimationFrame === 'function' && typeof cancelAnimationFrame === 'function') {
      frame = function (cb) {
        var id = Math.random();

        frames[id] = requestAnimationFrame(function onFrame(time) {
          if (lastFrameTime === time || lastFrameTime + TIME - 1 < time) {
            lastFrameTime = time;
            delete frames[id];

            cb();
          } else {
            frames[id] = requestAnimationFrame(onFrame);
          }
        });

        return id;
      };
      cancel = function (id) {
        if (frames[id]) {
          cancelAnimationFrame(frames[id]);
        }
      };
    } else {
      frame = function (cb) {
        return setTimeout(cb, TIME);
      };
      cancel = function (timer) {
        return clearTimeout(timer);
      };
    }

    return { frame: frame, cancel: cancel };
  }());

  var getWorker = (function () {
    var worker;
    var prom;
    var resolves = {};

    function decorate(worker) {
      function execute(options, callback) {
        worker.postMessage({ options: options || {}, callback: callback });
      }
      worker.init = function initWorker(canvas) {
        var offscreen = canvas.transferControlToOffscreen();
        worker.postMessage({ canvas: offscreen }, [offscreen]);
      };

      worker.fire = function fireWorker(options, size, done) {
        if (prom) {
          execute(options, null);
          return prom;
        }

        var id = Math.random().toString(36).slice(2);

        prom = promise(function (resolve) {
          function workerDone(msg) {
            if (msg.data.callback !== id) {
              return;
            }

            delete resolves[id];
            worker.removeEventListener('message', workerDone);

            prom = null;

            bitmapMapper.clear();

            done();
            resolve();
          }

          worker.addEventListener('message', workerDone);
          execute(options, id);

          resolves[id] = workerDone.bind(null, { data: { callback: id }});
        });

        return prom;
      };

      worker.reset = function resetWorker() {
        worker.postMessage({ reset: true });

        for (var id in resolves) {
          resolves[id]();
          delete resolves[id];
        }
      };
    }

    return function () {
      if (worker) {
        return worker;
      }

      if (!isWorker && canUseWorker) {
        var code = [
          'var CONFETTI, SIZE = {}, module = {};',
          '(' + main.toString() + ')(this, module, true, SIZE);',
          'onmessage = function(msg) {',
          '  if (msg.data.options) {',
          '    CONFETTI(msg.data.options).then(function () {',
          '      if (msg.data.callback) {',
          '        postMessage({ callback: msg.data.callback });',
          '      }',
          '    });',
          '  } else if (msg.data.reset) {',
          '    CONFETTI && CONFETTI.reset();',
          '  } else if (msg.data.resize) {',
          '    SIZE.width = msg.data.resize.width;',
          '    SIZE.height = msg.data.resize.height;',
          '  } else if (msg.data.canvas) {',
          '    SIZE.width = msg.data.canvas.width;',
          '    SIZE.height = msg.data.canvas.height;',
          '    CONFETTI = module.exports.create(msg.data.canvas);',
          '  }',
          '}',
        ].join('\n');
        try {
          worker = new Worker(URL.createObjectURL(new Blob([code])));
        } catch (e) {
          // eslint-disable-next-line no-console
          typeof console !== 'undefined' && typeof console.warn === 'function' ? console.warn('🎊 Could not load worker', e) : null;

          return null;
        }

        decorate(worker);
      }

      return worker;
    };
  })();

  var defaults = {
    particleCount: 50,
    angle: 90,
    spread: 45,
    startVelocity: 45,
    decay: 0.9,
    gravity: 1,
    drift: 0,
    ticks: 200,
    x: 0.5,
    y: 0.5,
    shapes: ['square', 'circle'],
    zIndex: 100,
    colors: [
      '#26ccff',
      '#a25afd',
      '#ff5e7e',
      '#88ff5a',
      '#fcff42',
      '#ffa62d',
      '#ff36ff'
    ],
    // probably should be true, but back-compat
    disableForReducedMotion: false,
    scalar: 1
  };

  function convert(val, transform) {
    return transform ? transform(val) : val;
  }

  function isOk(val) {
    return !(val === null || val === undefined);
  }

  function prop(options, name, transform) {
    return convert(
      options && isOk(options[name]) ? options[name] : defaults[name],
      transform
    );
  }

  function onlyPositiveInt(number){
    return number < 0 ? 0 : Math.floor(number);
  }

  function randomInt(min, max) {
    // [min, max)
    return Math.floor(Math.random() * (max - min)) + min;
  }

  function toDecimal(str) {
    return parseInt(str, 16);
  }

  function colorsToRgb(colors) {
    return colors.map(hexToRgb);
  }

  function hexToRgb(str) {
    var val = String(str).replace(/[^0-9a-f]/gi, '');

    if (val.length < 6) {
        val = val[0]+val[0]+val[1]+val[1]+val[2]+val[2];
    }

    return {
      r: toDecimal(val.substring(0,2)),
      g: toDecimal(val.substring(2,4)),
      b: toDecimal(val.substring(4,6))
    };
  }

  function getOrigin(options) {
    var origin = prop(options, 'origin', Object);
    origin.x = prop(origin, 'x', Number);
    origin.y = prop(origin, 'y', Number);

    return origin;
  }

  function setCanvasWindowSize(canvas) {
    canvas.width = document.documentElement.clientWidth;
    canvas.height = document.documentElement.clientHeight;
  }

  function setCanvasRectSize(canvas) {
    var rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
  }

  function getCanvas(zIndex) {
    var canvas = document.createElement('canvas');

    canvas.style.position = 'fixed';
    canvas.style.top = '0px';
    canvas.style.left = '0px';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = zIndex;

    return canvas;
  }

  function ellipse(context, x, y, radiusX, radiusY, rotation, startAngle, endAngle, antiClockwise) {
    context.save();
    context.translate(x, y);
    context.rotate(rotation);
    context.scale(radiusX, radiusY);
    context.arc(0, 0, 1, startAngle, endAngle, antiClockwise);
    context.restore();
  }

  function randomPhysics(opts) {
    var radAngle = opts.angle * (Math.PI / 180);
    var radSpread = opts.spread * (Math.PI / 180);

    return {
      x: opts.x,
      y: opts.y,
      wobble: Math.random() * 10,
      wobbleSpeed: Math.min(0.11, Math.random() * 0.1 + 0.05),
      velocity: (opts.startVelocity * 0.5) + (Math.random() * opts.startVelocity),
      angle2D: -radAngle + ((0.5 * radSpread) - (Math.random() * radSpread)),
      tiltAngle: (Math.random() * (0.75 - 0.25) + 0.25) * Math.PI,
      color: opts.color,
      shape: opts.shape,
      tick: 0,
      totalTicks: opts.ticks,
      decay: opts.decay,
      drift: opts.drift,
      random: Math.random() + 2,
      tiltSin: 0,
      tiltCos: 0,
      wobbleX: 0,
      wobbleY: 0,
      gravity: opts.gravity * 3,
      ovalScalar: 0.6,
      scalar: opts.scalar,
      flat: opts.flat
    };
  }

  function updateFetti(context, fetti) {
    fetti.x += Math.cos(fetti.angle2D) * fetti.velocity + fetti.drift;
    fetti.y += Math.sin(fetti.angle2D) * fetti.velocity + fetti.gravity;
    fetti.velocity *= fetti.decay;

    if (fetti.flat) {
      fetti.wobble = 0;
      fetti.wobbleX = fetti.x + (10 * fetti.scalar);
      fetti.wobbleY = fetti.y + (10 * fetti.scalar);

      fetti.tiltSin = 0;
      fetti.tiltCos = 0;
      fetti.random = 1;
    } else {
      fetti.wobble += fetti.wobbleSpeed;
      fetti.wobbleX = fetti.x + ((10 * fetti.scalar) * Math.cos(fetti.wobble));
      fetti.wobbleY = fetti.y + ((10 * fetti.scalar) * Math.sin(fetti.wobble));

      fetti.tiltAngle += 0.1;
      fetti.tiltSin = Math.sin(fetti.tiltAngle);
      fetti.tiltCos = Math.cos(fetti.tiltAngle);
      fetti.random = Math.random() + 2;
    }

    var progress = (fetti.tick++) / fetti.totalTicks;

    var x1 = fetti.x + (fetti.random * fetti.tiltCos);
    var y1 = fetti.y + (fetti.random * fetti.tiltSin);
    var x2 = fetti.wobbleX + (fetti.random * fetti.tiltCos);
    var y2 = fetti.wobbleY + (fetti.random * fetti.tiltSin);

    context.fillStyle = 'rgba(' + fetti.color.r + ', ' + fetti.color.g + ', ' + fetti.color.b + ', ' + (1 - progress) + ')';

    context.beginPath();

    if (canUsePaths && fetti.shape.type === 'path' && typeof fetti.shape.path === 'string' && Array.isArray(fetti.shape.matrix)) {
      context.fill(transformPath2D(
        fetti.shape.path,
        fetti.shape.matrix,
        fetti.x,
        fetti.y,
        Math.abs(x2 - x1) * 0.1,
        Math.abs(y2 - y1) * 0.1,
        Math.PI / 10 * fetti.wobble
      ));
    } else if (fetti.shape.type === 'bitmap') {
      var rotation = Math.PI / 10 * fetti.wobble;
      var scaleX = Math.abs(x2 - x1) * 0.1;
      var scaleY = Math.abs(y2 - y1) * 0.1;
      var width = fetti.shape.bitmap.width * fetti.scalar;
      var height = fetti.shape.bitmap.height * fetti.scalar;

      var matrix = new DOMMatrix([
        Math.cos(rotation) * scaleX,
        Math.sin(rotation) * scaleX,
        -Math.sin(rotation) * scaleY,
        Math.cos(rotation) * scaleY,
        fetti.x,
        fetti.y
      ]);

      // apply the transform matrix from the confetti shape
      matrix.multiplySelf(new DOMMatrix(fetti.shape.matrix));

      var pattern = context.createPattern(bitmapMapper.transform(fetti.shape.bitmap), 'no-repeat');
      pattern.setTransform(matrix);

      context.globalAlpha = (1 - progress);
      context.fillStyle = pattern;
      context.fillRect(
        fetti.x - (width / 2),
        fetti.y - (height / 2),
        width,
        height
      );
      context.globalAlpha = 1;
    } else if (fetti.shape === 'circle') {
      context.ellipse ?
        context.ellipse(fetti.x, fetti.y, Math.abs(x2 - x1) * fetti.ovalScalar, Math.abs(y2 - y1) * fetti.ovalScalar, Math.PI / 10 * fetti.wobble, 0, 2 * Math.PI) :
        ellipse(context, fetti.x, fetti.y, Math.abs(x2 - x1) * fetti.ovalScalar, Math.abs(y2 - y1) * fetti.ovalScalar, Math.PI / 10 * fetti.wobble, 0, 2 * Math.PI);
    } else if (fetti.shape === 'star') {
      var rot = Math.PI / 2 * 3;
      var innerRadius = 4 * fetti.scalar;
      var outerRadius = 8 * fetti.scalar;
      var x = fetti.x;
      var y = fetti.y;
      var spikes = 5;
      var step = Math.PI / spikes;

      while (spikes--) {
        x = fetti.x + Math.cos(rot) * outerRadius;
        y = fetti.y + Math.sin(rot) * outerRadius;
        context.lineTo(x, y);
        rot += step;

        x = fetti.x + Math.cos(rot) * innerRadius;
        y = fetti.y + Math.sin(rot) * innerRadius;
        context.lineTo(x, y);
        rot += step;
      }
    } else {
      context.moveTo(Math.floor(fetti.x), Math.floor(fetti.y));
      context.lineTo(Math.floor(fetti.wobbleX), Math.floor(y1));
      context.lineTo(Math.floor(x2), Math.floor(y2));
      context.lineTo(Math.floor(x1), Math.floor(fetti.wobbleY));
    }

    context.closePath();
    context.fill();

    return fetti.tick < fetti.totalTicks;
  }

  function animate(canvas, fettis, resizer, size, done) {
    var animatingFettis = fettis.slice();
    var context = canvas.getContext('2d');
    var animationFrame;
    var destroy;

    var prom = promise(function (resolve) {
      function onDone() {
        animationFrame = destroy = null;

        context.clearRect(0, 0, size.width, size.height);
        bitmapMapper.clear();

        done();
        resolve();
      }

      function update() {
        if (isWorker && !(size.width === workerSize.width && size.height === workerSize.height)) {
          size.width = canvas.width = workerSize.width;
          size.height = canvas.height = workerSize.height;
        }

        if (!size.width && !size.height) {
          resizer(canvas);
          size.width = canvas.width;
          size.height = canvas.height;
        }

        context.clearRect(0, 0, size.width, size.height);

        animatingFettis = animatingFettis.filter(function (fetti) {
          return updateFetti(context, fetti);
        });

        if (animatingFettis.length) {
          animationFrame = raf.frame(update);
        } else {
          onDone();
        }
      }

      animationFrame = raf.frame(update);
      destroy = onDone;
    });

    return {
      addFettis: function (fettis) {
        animatingFettis = animatingFettis.concat(fettis);

        return prom;
      },
      canvas: canvas,
      promise: prom,
      reset: function () {
        if (animationFrame) {
          raf.cancel(animationFrame);
        }

        if (destroy) {
          destroy();
        }
      }
    };
  }

  function confettiCannon(canvas, globalOpts) {
    var isLibCanvas = !canvas;
    var allowResize = !!prop(globalOpts || {}, 'resize');
    var hasResizeEventRegistered = false;
    var globalDisableForReducedMotion = prop(globalOpts, 'disableForReducedMotion', Boolean);
    var shouldUseWorker = canUseWorker && !!prop(globalOpts || {}, 'useWorker');
    var worker = shouldUseWorker ? getWorker() : null;
    var resizer = isLibCanvas ? setCanvasWindowSize : setCanvasRectSize;
    var initialized = (canvas && worker) ? !!canvas.__confetti_initialized : false;
    var preferLessMotion = typeof matchMedia === 'function' && matchMedia('(prefers-reduced-motion)').matches;
    var animationObj;

    function fireLocal(options, size, done) {
      var particleCount = prop(options, 'particleCount', onlyPositiveInt);
      var angle = prop(options, 'angle', Number);
      var spread = prop(options, 'spread', Number);
      var startVelocity = prop(options, 'startVelocity', Number);
      var decay = prop(options, 'decay', Number);
      var gravity = prop(options, 'gravity', Number);
      var drift = prop(options, 'drift', Number);
      var colors = prop(options, 'colors', colorsToRgb);
      var ticks = prop(options, 'ticks', Number);
      var shapes = prop(options, 'shapes');
      var scalar = prop(options, 'scalar');
      var flat = !!prop(options, 'flat');
      var origin = getOrigin(options);

      var temp = particleCount;
      var fettis = [];

      var startX = canvas.width * origin.x;
      var startY = canvas.height * origin.y;

      while (temp--) {
        fettis.push(
          randomPhysics({
            x: startX,
            y: startY,
            angle: angle,
            spread: spread,
            startVelocity: startVelocity,
            color: colors[temp % colors.length],
            shape: shapes[randomInt(0, shapes.length)],
            ticks: ticks,
            decay: decay,
            gravity: gravity,
            drift: drift,
            scalar: scalar,
            flat: flat
          })
        );
      }

      // if we have a previous canvas already animating,
      // add to it
      if (animationObj) {
        return animationObj.addFettis(fettis);
      }

      animationObj = animate(canvas, fettis, resizer, size , done);

      return animationObj.promise;
    }

    function fire(options) {
      var disableForReducedMotion = globalDisableForReducedMotion || prop(options, 'disableForReducedMotion', Boolean);
      var zIndex = prop(options, 'zIndex', Number);

      if (disableForReducedMotion && preferLessMotion) {
        return promise(function (resolve) {
          resolve();
        });
      }

      if (isLibCanvas && animationObj) {
        // use existing canvas from in-progress animation
        canvas = animationObj.canvas;
      } else if (isLibCanvas && !canvas) {
        // create and initialize a new canvas
        canvas = getCanvas(zIndex);
        document.body.appendChild(canvas);
      }

      if (allowResize && !initialized) {
        // initialize the size of a user-supplied canvas
        resizer(canvas);
      }

      var size = {
        width: canvas.width,
        height: canvas.height
      };

      if (worker && !initialized) {
        worker.init(canvas);
      }

      initialized = true;

      if (worker) {
        canvas.__confetti_initialized = true;
      }

      function onResize() {
        if (worker) {
          // TODO this really shouldn't be immediate, because it is expensive
          var obj = {
            getBoundingClientRect: function () {
              if (!isLibCanvas) {
                return canvas.getBoundingClientRect();
              }
            }
          };

          resizer(obj);

          worker.postMessage({
            resize: {
              width: obj.width,
              height: obj.height
            }
          });
          return;
        }

        // don't actually query the size here, since this
        // can execute frequently and rapidly
        size.width = size.height = null;
      }

      function done() {
        animationObj = null;

        if (allowResize) {
          hasResizeEventRegistered = false;
          global.removeEventListener('resize', onResize);
        }

        if (isLibCanvas && canvas) {
          if (document.body.contains(canvas)) {
            document.body.removeChild(canvas);
          }
          canvas = null;
          initialized = false;
        }
      }

      if (allowResize && !hasResizeEventRegistered) {
        hasResizeEventRegistered = true;
        global.addEventListener('resize', onResize, false);
      }

      if (worker) {
        return worker.fire(options, size, done);
      }

      return fireLocal(options, size, done);
    }

    fire.reset = function () {
      if (worker) {
        worker.reset();
      }

      if (animationObj) {
        animationObj.reset();
      }
    };

    return fire;
  }

  // Make default export lazy to defer worker creation until called.
  var defaultFire;
  function getDefaultFire() {
    if (!defaultFire) {
      defaultFire = confettiCannon(null, { useWorker: true, resize: true });
    }
    return defaultFire;
  }

  function transformPath2D(pathString, pathMatrix, x, y, scaleX, scaleY, rotation) {
    var path2d = new Path2D(pathString);

    var t1 = new Path2D();
    t1.addPath(path2d, new DOMMatrix(pathMatrix));

    var t2 = new Path2D();
    // see https://developer.mozilla.org/en-US/docs/Web/API/DOMMatrix/DOMMatrix
    t2.addPath(t1, new DOMMatrix([
      Math.cos(rotation) * scaleX,
      Math.sin(rotation) * scaleX,
      -Math.sin(rotation) * scaleY,
      Math.cos(rotation) * scaleY,
      x,
      y
    ]));

    return t2;
  }

  function shapeFromPath(pathData) {
    if (!canUsePaths) {
      throw new Error('path confetti are not supported in this browser');
    }

    var path, matrix;

    if (typeof pathData === 'string') {
      path = pathData;
    } else {
      path = pathData.path;
      matrix = pathData.matrix;
    }

    var path2d = new Path2D(path);
    var tempCanvas = document.createElement('canvas');
    var tempCtx = tempCanvas.getContext('2d');

    if (!matrix) {
      // attempt to figure out the width of the path, up to 1000x1000
      var maxSize = 1000;
      var minX = maxSize;
      var minY = maxSize;
      var maxX = 0;
      var maxY = 0;
      var width, height;

      // do some line skipping... this is faster than checking
      // every pixel and will be mostly still correct
      for (var x = 0; x < maxSize; x += 2) {
        for (var y = 0; y < maxSize; y += 2) {
          if (tempCtx.isPointInPath(path2d, x, y, 'nonzero')) {
            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x);
            maxY = Math.max(maxY, y);
          }
        }
      }

      width = maxX - minX;
      height = maxY - minY;

      var maxDesiredSize = 10;
      var scale = Math.min(maxDesiredSize/width, maxDesiredSize/height);

      matrix = [
        scale, 0, 0, scale,
        -Math.round((width/2) + minX) * scale,
        -Math.round((height/2) + minY) * scale
      ];
    }

    return {
      type: 'path',
      path: path,
      matrix: matrix
    };
  }

  function shapeFromText(textData) {
    var text,
        scalar = 1,
        color = '#000000',
        // see https://nolanlawson.com/2022/04/08/the-struggle-of-using-native-emoji-on-the-web/
        fontFamily = '"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", "EmojiOne Color", "Android Emoji", "Twemoji Mozilla", "system emoji", sans-serif';

    if (typeof textData === 'string') {
      text = textData;
    } else {
      text = textData.text;
      scalar = 'scalar' in textData ? textData.scalar : scalar;
      fontFamily = 'fontFamily' in textData ? textData.fontFamily : fontFamily;
      color = 'color' in textData ? textData.color : color;
    }

    // all other confetti are 10 pixels,
    // so this pixel size is the de-facto 100% scale confetti
    var fontSize = 10 * scalar;
    var font = '' + fontSize + 'px ' + fontFamily;

    var canvas = new OffscreenCanvas(fontSize, fontSize);
    var ctx = canvas.getContext('2d');

    ctx.font = font;
    var size = ctx.measureText(text);
    var width = Math.ceil(size.actualBoundingBoxRight + size.actualBoundingBoxLeft);
    var height = Math.ceil(size.actualBoundingBoxAscent + size.actualBoundingBoxDescent);

    var padding = 2;
    var x = size.actualBoundingBoxLeft + padding;
    var y = size.actualBoundingBoxAscent + padding;
    width += padding + padding;
    height += padding + padding;

    canvas = new OffscreenCanvas(width, height);
    ctx = canvas.getContext('2d');
    ctx.font = font;
    ctx.fillStyle = color;

    ctx.fillText(text, x, y);

    var scale = 1 / scalar;

    return {
      type: 'bitmap',
      // TODO these probably need to be transfered for workers
      bitmap: canvas.transferToImageBitmap(),
      matrix: [scale, 0, 0, scale, -width * scale / 2, -height * scale / 2]
    };
  }

  module.exports = function() {
    return getDefaultFire().apply(this, arguments);
  };
  module.exports.reset = function() {
    getDefaultFire().reset();
  };
  module.exports.create = confettiCannon;
  module.exports.shapeFromPath = shapeFromPath;
  module.exports.shapeFromText = shapeFromText;
}((function () {
  if (typeof window !== 'undefined') {
    return window;
  }

  if (typeof self !== 'undefined') {
    return self;
  }

  return this || {};
})(), module, false));

// end source content

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (module.exports);
var create = module.exports.create;


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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
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
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/games/hub": 0,
/******/ 			"css/musicgames": 0,
/******/ 			"css/shared": 0,
/******/ 			".mix/css/admin": 0,
/******/ 			".mix/css/app": 0,
/******/ 			"css/metronome": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk"] = self["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/js/games/hub.js")))
/******/ 	__webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/sass/musicgames.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/sass/metronome.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/sass/app.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/sass/admin.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/musicgames","css/shared",".mix/css/admin",".mix/css/app","css/metronome"], () => (__webpack_require__("./resources/sass/shared.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;