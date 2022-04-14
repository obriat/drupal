!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.drupalMedia=t())}(self,(function(){return(()=>{var e={"ckeditor5/src/core.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/engine.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/engine.js")},"ckeditor5/src/ui.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/utils.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/utils.js")},"ckeditor5/src/widget.js":(e,t,i)=>{e.exports=i("dll-reference CKEditor5.dll")("./src/widget.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function i(n){var a=t[n];if(void 0!==a)return a.exports;var r=t[n]={exports:{}};return e[n](r,r.exports,i),r.exports}i.d=(e,t)=>{for(var n in t)i.o(t,n)&&!i.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var n={};return(()=>{"use strict";i.d(n,{default:()=>ue});var e=i("ckeditor5/src/core.js"),t=i("ckeditor5/src/widget.js");function a(e){return!!e&&e.is("element","drupalMedia")}function r(e){return(0,t.isWidget)(e)&&!!e.getCustomProperty("drupalMedia")}function o(e){const t=e.getSelectedElement();return a(t)?t:e.getFirstPosition().findAncestor("drupalMedia")}function s(e){const t=e.getSelectedElement();if(t&&r(t))return t;let i=e.getFirstPosition().parent;for(;i;){if(i.is("element")&&r(i))return i;i=i.parent}return null}function l(e){const t=typeof e;return null!=e&&("object"===t||"function"===t)}function d(e){for(const t of e){if(t.hasAttribute("data-drupal-media-preview"))return t;if(t.childCount){const e=d(t.getChildren());if(e)return e}}return null}function u(e){return`drupalElementStyle${e[0].toUpperCase()+e.substring(1)}`}class c extends e.Command{execute(e){const t=this.editor.plugins.get("DrupalMediaEditing"),i=Object.entries(t.attrs).reduce(((e,[t,i])=>(e[i]=t,e)),{}),n=Object.keys(e).reduce(((t,n)=>(i[n]&&(t[i[n]]=e[n]),t)),{});if(this.editor.plugins.has("DrupalElementStyleEditing")){const t=this.editor.plugins.get("DrupalElementStyleEditing"),{normalizedStyles:i}=t;for(const a of Object.keys(i))for(const i of t.normalizedStyles[a])if(e[i.attributeName]&&i.attributeValue===e[i.attributeName]){const e=u(a);n[e]=i.name}}this.editor.model.change((e=>{this.editor.model.insertContent(function(e,t){return e.createElement("drupalMedia",t)}(e,n))}))}refresh(){const e=this.editor.model,t=e.document.selection,i=e.schema.findAllowedParent(t.getFirstPosition(),"drupalMedia");this.isEnabled=null!==i}}const m="METADATA_ERROR";class p extends e.Plugin{static get requires(){return[t.Widget]}init(){this.attrs={drupalMediaAlt:"alt",drupalMediaEntityType:"data-entity-type",drupalMediaEntityUuid:"data-entity-uuid"};const e=this.editor.config.get("drupalMedia");if(!e)return;const{previewURL:t,themeError:i}=e;this.previewUrl=t,this.labelError=Drupal.t("Preview failed"),this.themeError=i||`\n      <p>${Drupal.t("An error occurred while trying to preview the media. Please save your work and reload this page.")}<p>\n    `,this._defineSchema(),this._defineConverters(),this._defineListeners(),this.editor.commands.add("insertDrupalMedia",new c(this.editor))}upcastDrupalMediaIsImage(e){const{model:t,plugins:i}=this.editor;i.get("DrupalMediaMetadataRepository").getMetadata(e).then((i=>{e&&t.enqueueChange({isUndoable:!1},(t=>{t.setAttribute("drupalMediaIsImage",!!i.imageSourceMetadata,e)}))})).catch((i=>{e&&(console.warn(i.toString()),t.enqueueChange({isUndoable:!1},(t=>{t.setAttribute("drupalMediaIsImage",m,e)})))}))}upcastDrupalMediaType(e){this.editor.plugins.get("DrupalMediaMetadataRepository").getMetadata(e).then((t=>{e&&this.editor.model.enqueueChange({isUndoable:!1},(i=>{i.setAttribute("drupalMediaType",t.type,e)}))})).catch((t=>{e&&(console.warn(t.toString()),this.editor.model.enqueueChange({isUndoable:!1},(t=>{t.setAttribute("drupalMediaType",m,e)})))}))}async _fetchPreview(e){const t={text:this._renderElement(e),uuid:e.getAttribute("drupalMediaEntityUuid")},i=await fetch(`${this.previewUrl}?${new URLSearchParams(t)}`,{headers:{"X-Drupal-MediaPreview-CSRF-Token":this.editor.config.get("drupalMedia").previewCsrfToken}});if(i.ok){return{label:i.headers.get("drupal-media-label"),preview:await i.text()}}return{label:this.labelError,preview:this.themeError}}_defineSchema(){this.editor.model.schema.register("drupalMedia",{allowWhere:"$block",isObject:!0,isContent:!0,isBlock:!0,allowAttributes:Object.keys(this.attrs)}),this.editor.editing.view.domConverter.blockElements.push("drupal-media")}_defineConverters(){const e=this.editor.conversion,i=this.editor.plugins.get("DrupalMediaMetadataRepository");e.for("upcast").elementToElement({view:{name:"drupal-media"},model:"drupalMedia"}).add((e=>{e.on("element:drupal-media",((e,t)=>{const[n]=t.modelRange.getItems();i.getMetadata(n).then((e=>{n&&(this.upcastDrupalMediaIsImage(n),this.editor.model.enqueueChange({isUndoable:!1},(t=>{t.setAttribute("drupalMediaType",e.type,n)})))})).catch((e=>{console.warn(e.toString())}))}),{priority:"lowest"})})),e.for("dataDowncast").elementToElement({model:"drupalMedia",view:{name:"drupal-media"}}),e.for("editingDowncast").elementToElement({model:"drupalMedia",view:(e,{writer:i})=>{const n=i.createContainerElement("figure",{class:"drupal-media"});if(!this.previewUrl){const e=i.createRawElement("div",{"data-drupal-media-preview":"unavailable"});i.insert(i.createPositionAt(n,0),e)}return i.setCustomProperty("drupalMedia",!0,n),(0,t.toWidget)(n,i,{label:Drupal.t("Media widget")})}}).add((e=>{const t=(e,t,i)=>{const n=i.writer,a=t.item,r=i.mapper.toViewElement(t.item);let o=d(r.getChildren());if(o){if("ready"!==o.getAttribute("data-drupal-media-preview"))return;n.setAttribute("data-drupal-media-preview","loading",o)}else o=n.createRawElement("div",{"data-drupal-media-preview":"loading"}),n.insert(n.createPositionAt(r,0),o);this._fetchPreview(a).then((({label:e,preview:t})=>{o&&this.editor.editing.view.change((i=>{const n=i.createRawElement("div",{"data-drupal-media-preview":"ready","aria-label":e},(e=>{e.innerHTML=t}));i.insert(i.createPositionBefore(o),n),i.remove(o)}))}))};return e.on("attribute:drupalMediaEntityUuid:drupalMedia",t),e.on("attribute:drupalElementStyleViewMode:drupalMedia",t),e.on("attribute:drupalMediaEntityType:drupalMedia",t),e.on("attribute:drupalMediaAlt:drupalMedia",t),e})),e.for("editingDowncast").add((e=>{e.on("attribute:drupalElementStyleAlign:drupalMedia",((e,t,i)=>{const n={left:"drupal-media-style-align-left",right:"drupal-media-style-align-right",center:"drupal-media-style-align-center"},a=i.mapper.toViewElement(t.item),r=i.writer;n[t.attributeOldValue]&&r.removeClass(n[t.attributeOldValue],a),n[t.attributeNewValue]&&i.consumable.consume(t.item,e.name)&&r.addClass(n[t.attributeNewValue],a)}))})),Object.keys(this.attrs).forEach((t=>{const i={model:{key:t,name:"drupalMedia"},view:{name:"drupal-media",key:this.attrs[t]}};e.for("dataDowncast").attributeToAttribute(i),e.for("upcast").attributeToAttribute(i)}))}_defineListeners(){this.editor.model.on("insertContent",((e,[t])=>{a(t)&&(this.upcastDrupalMediaIsImage(t),this.upcastDrupalMediaType(t))}))}_renderElement(e){const t=this.editor.model.change((t=>{const i=t.createDocumentFragment(),n=t.cloneElement(e,!1);return["linkHref"].forEach((e=>{t.removeAttribute(e,n)})),t.append(n,i),i}));return this.editor.data.stringify(t)}static get pluginName(){return"DrupalMediaEditing"}}var g=i("ckeditor5/src/ui.js");class h extends e.Plugin{init(){const e=this.editor,t=this.editor.config.get("drupalMedia");if(!t)return;const{libraryURL:i,openDialog:n,dialogSettings:a={}}=t;i&&"function"==typeof n&&e.ui.componentFactory.add("drupalMedia",(t=>{const r=e.commands.get("insertDrupalMedia"),o=new g.ButtonView(t);return o.set({label:Drupal.t("Insert Drupal Media"),icon:'<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.1873 4.86414L10.2509 6.86414V7.02335H10.2499V15.5091C9.70972 15.1961 9.01793 15.1048 8.34069 15.3136C7.12086 15.6896 6.41013 16.8967 6.75322 18.0096C7.09631 19.1226 8.3633 19.72 9.58313 19.344C10.6666 19.01 11.3484 18.0203 11.2469 17.0234H11.2499V9.80173L18.1803 8.25067V14.3868C17.6401 14.0739 16.9483 13.9825 16.2711 14.1913C15.0513 14.5674 14.3406 15.7744 14.6836 16.8875C15.0267 18.0004 16.2937 18.5978 17.5136 18.2218C18.597 17.8877 19.2788 16.8982 19.1773 15.9011H19.1803V8.02687L19.1873 8.0253V4.86414Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13.5039 0.743652H0.386932V12.1603H13.5039V0.743652ZM12.3379 1.75842H1.55289V11.1454H1.65715L4.00622 8.86353L6.06254 10.861L9.24985 5.91309L11.3812 9.22179L11.7761 8.6676L12.3379 9.45621V1.75842ZM6.22048 4.50869C6.22048 5.58193 5.35045 6.45196 4.27722 6.45196C3.20398 6.45196 2.33395 5.58193 2.33395 4.50869C2.33395 3.43546 3.20398 2.56543 4.27722 2.56543C5.35045 2.56543 6.22048 3.43546 6.22048 4.50869Z" fill="black"/></svg>\n',tooltip:!0}),o.bind("isOn","isEnabled").to(r,"value","isEnabled"),this.listenTo(o,"execute",(()=>{n(i,(({attributes:t})=>{e.execute("insertDrupalMedia",t)}),a)})),o}))}}class f extends e.Plugin{static get requires(){return[t.WidgetToolbarRepository]}static get pluginName(){return"DrupalMediaToolbar"}afterInit(){const{editor:e}=this;var i;e.plugins.get(t.WidgetToolbarRepository).register("drupalMedia",{ariaLabel:Drupal.t("Drupal Media toolbar"),items:(i=e.config.get("drupalMedia.toolbar"),i.map((e=>l(e)?e.name:e))||[]),getRelatedElement:e=>s(e)})}}class b extends e.Command{refresh(){const e=o(this.editor.model.document.selection);this.isEnabled=!!e&&e.getAttribute("drupalMediaIsImage")&&e.getAttribute("drupalMediaIsImage")!==m,this.isEnabled?this.value=e.getAttribute("drupalMediaAlt"):this.value=!1}execute(e){const{model:t}=this.editor,i=o(t.document.selection);e.newValue=e.newValue.trim(),t.change((t=>{e.newValue.length>0?t.setAttribute("drupalMediaAlt",e.newValue,i):t.removeAttribute("drupalMediaAlt",i)}))}}class w extends e.Plugin{init(){this._data=new WeakMap}getMetadata(e){if(this._data.get(e))return new Promise((t=>{t(this._data.get(e))}));const t=this.editor.config.get("drupalMedia");if(!t)return new Promise(((e,t)=>{t(new Error("drupalMedia configuration is required for parsing metadata."))}));if(!e.hasAttribute("drupalMediaEntityUuid"))return new Promise(((e,t)=>{t(new Error("drupalMedia element must have drupalMediaEntityUuid attribute to retrieve metadata."))}));const{metadataUrl:i}=t;return(async e=>{const t=await fetch(e);if(t.ok)return JSON.parse(await t.text());throw new Error("Fetching media embed metadata from the server failed.")})(`${i}&${new URLSearchParams({uuid:e.getAttribute("drupalMediaEntityUuid")})}`).then((t=>(this._data.set(e,t),t)))}static get pluginName(){return"DrupalMediaMetadataRepository"}}class y extends e.Plugin{static get requires(){return[w]}static get pluginName(){return"MediaImageTextAlternativeEditing"}init(){const{editor:e,editor:{model:t,conversion:i}}=this;t.schema.extend("drupalMedia",{allowAttributes:["drupalMediaIsImage"]}),i.for("editingDowncast").add((e=>{e.on("attribute:drupalMediaIsImage",((e,t,i)=>{const{writer:n,mapper:a}=i,r=a.toViewElement(t.item);if(t.attributeNewValue!==m){const e=Array.from(r.getChildren()).find((e=>e.getCustomProperty("drupalMediaMetadataError")));return void(e&&(n.setCustomProperty("widgetLabel",e.getCustomProperty("drupalMediaOriginalWidgetLabel"),e),n.removeElement(e)))}const o=Drupal.t("Not all functionality may be available because some information could not be retrieved."),s=new g.TooltipView;s.text=o,s.position="sw";const l=new g.Template({tag:"span",children:[{tag:"span",attributes:{class:"drupal-media__metadata-error-icon"}},s]}).render(),d=n.createRawElement("div",{class:"drupal-media__metadata-error"},((e,t)=>{t.setContentOf(e,l.outerHTML)}));n.setCustomProperty("drupalMediaMetadataError",!0,d);const u=r.getCustomProperty("widgetLabel");n.setCustomProperty("drupalMediaOriginalWidgetLabel",u,d),n.setCustomProperty("widgetLabel",`${u} (${o})`,r),n.insert(n.createPositionAt(r,0),d)}),{priority:"low"})})),e.commands.add("mediaImageTextAlternative",new b(this.editor))}}function v(e){const t=e.editing.view,i=g.BalloonPanelView.defaultPositions;return{target:t.domConverter.viewToDom(t.document.selection.getSelectedElement()),positions:[i.northArrowSouth,i.northArrowSouthWest,i.northArrowSouthEast,i.southArrowNorth,i.southArrowNorthWest,i.southArrowNorthEast]}}var E=i("ckeditor5/src/utils.js");class M extends g.View{constructor(t){super(t),this.focusTracker=new E.FocusTracker,this.keystrokes=new E.KeystrokeHandler,this.labeledInput=this._createLabeledInputView(),this.set("defaultAltText",void 0),this.defaultAltTextView=this._createDefaultAltTextView(),this.saveButtonView=this._createButton(Drupal.t("Save"),e.icons.check,"ck-button-save"),this.saveButtonView.type="submit",this.cancelButtonView=this._createButton(Drupal.t("Cancel"),e.icons.cancel,"ck-button-cancel","cancel"),this._focusables=new g.ViewCollection,this._focusCycler=new g.FocusCycler({focusables:this._focusables,focusTracker:this.focusTracker,keystrokeHandler:this.keystrokes,actions:{focusPrevious:"shift + tab",focusNext:"tab"}}),this.setTemplate({tag:"form",attributes:{class:["ck","ck-media-alternative-text-form","ck-vertical-form"],tabindex:"-1"},children:[this.defaultAltTextView,this.labeledInput,this.saveButtonView,this.cancelButtonView]}),(0,g.injectCssTransitionDisabler)(this)}render(){super.render(),this.keystrokes.listenTo(this.element),(0,g.submitHandler)({view:this}),[this.labeledInput,this.saveButtonView,this.cancelButtonView].forEach((e=>{this._focusables.add(e),this.focusTracker.add(e.element)}))}_createButton(e,t,i,n){const a=new g.ButtonView(this.locale);return a.set({label:e,icon:t,tooltip:!0}),a.extendTemplate({attributes:{class:i}}),n&&a.delegate("execute").to(this,n),a}_createLabeledInputView(){const e=new g.LabeledFieldView(this.locale,g.createLabeledInputText);return e.label=Drupal.t("Alternative text override"),e}_createDefaultAltTextView(){const e=g.Template.bind(this,this);return new g.Template({tag:"div",attributes:{class:["ck-media-alternative-text-form__default-alt-text",e.if("defaultAltText","ck-hidden",(e=>!e))]},children:[{tag:"strong",attributes:{class:"ck-media-alternative-text-form__default-alt-text-label"},children:[Drupal.t("Default alternative text:")]}," ",{tag:"span",attributes:{class:"ck-media-alternative-text-form__default-alt-text-value"},children:[{text:[e.to("defaultAltText")]}]}]})}}class k extends e.Plugin{static get requires(){return[g.ContextualBalloon]}static get pluginName(){return"MediaImageTextAlternativeUi"}init(){this._createButton(),this._createForm()}destroy(){super.destroy(),this._form.destroy()}_createButton(){const t=this.editor;t.ui.componentFactory.add("mediaImageTextAlternative",(i=>{const n=t.commands.get("mediaImageTextAlternative"),a=new g.ButtonView(i);return a.set({label:Drupal.t("Override media image alternative text"),icon:e.icons.lowVision,tooltip:!0}),a.bind("isVisible").to(n,"isEnabled"),this.listenTo(a,"execute",(()=>{this._showForm()})),a}))}_createForm(){const e=this.editor,t=e.editing.view.document;this._balloon=this.editor.plugins.get("ContextualBalloon"),this._form=new M(e.locale),this._form.render(),this.listenTo(this._form,"submit",(()=>{e.execute("mediaImageTextAlternative",{newValue:this._form.labeledInput.fieldView.element.value}),this._hideForm(!0)})),this.listenTo(this._form,"cancel",(()=>{this._hideForm(!0)})),this._form.keystrokes.set("Esc",((e,t)=>{this._hideForm(!0),t()})),this.listenTo(e.ui,"update",(()=>{s(t.selection)?this._isVisible&&function(e){const t=e.plugins.get("ContextualBalloon");if(s(e.editing.view.document.selection)){const i=v(e);t.updatePosition(i)}}(e):this._hideForm(!0)})),(0,g.clickOutsideHandler)({emitter:this._form,activator:()=>this._isVisible,contextElements:[this._balloon.view.element],callback:()=>this._hideForm()})}_showForm(){if(this._isVisible)return;const e=this.editor,t=e.commands.get("mediaImageTextAlternative"),i=e.plugins.get("DrupalMediaMetadataRepository"),n=this._form.labeledInput;this._form.disableCssTransitions(),this._isInBalloon||this._balloon.add({view:this._form,position:v(e)}),n.fieldView.element.value=t.value||"",n.fieldView.value=n.fieldView.element.value,this._form.defaultAltText="";const r=e.model.document.selection.getSelectedElement();a(r)&&i.getMetadata(r).then((e=>{this._form.defaultAltText=e.imageSourceMetadata?e.imageSourceMetadata.alt:""})).catch((e=>{console.warn(e.toString())})),this._form.labeledInput.fieldView.select(),this._form.enableCssTransitions()}_hideForm(e){this._isInBalloon&&(this._form.focusTracker.isFocused&&this._form.saveButtonView.focus(),this._balloon.remove(this._form),e&&this.editor.editing.view.focus())}get _isVisible(){return this._balloon.visibleView===this._form}get _isInBalloon(){return this._balloon.hasView(this._form)}}class A extends e.Plugin{static get requires(){return[y,k]}static get pluginName(){return"MediaImageTextAlternative"}}function D(e,t,i){if(t.attributes)for(const[n,a]of Object.entries(t.attributes))e.setAttribute(n,a,i);t.styles&&e.setStyle(t.styles,i),t.classes&&e.addClass(t.classes,i)}function C(e,t,i){if(!i.consumable.consume(t.item,e.name))return;const n=i.mapper.toViewElement(t.item);D(i.writer,t.attributeNewValue,n)}class _ extends e.Plugin{constructor(e){if(super(e),!e.plugins.has("GeneralHtmlSupport"))return;e.plugins.has("DataFilter")&&e.plugins.has("DataSchema")||console.error("DataFilter and DataSchema plugins are required for Drupal Media to integrate with General HTML Support plugin.");const{schema:t}=e.model,{conversion:i}=e,n=this.editor.plugins.get("DataFilter");this.editor.plugins.get("DataSchema").registerBlockElement({model:"drupalMedia",view:"drupal-media"}),n.on("register:drupal-media",((e,a)=>{"drupalMedia"===a.model&&(t.extend("drupalMedia",{allowAttributes:["htmlLinkAttributes","htmlAttributes"]}),i.for("upcast").add(function(e){return t=>{t.on("element:drupal-media",((t,i,n)=>{function a(t,a){const r=e._consumeAllowedAttributes(t,n);r&&n.writer.setAttribute(a,r,i.modelRange)}const r=i.viewItem,o=r.parent;a(r,"htmlAttributes"),o.is("element","a")&&a(o,"htmlLinkAttributes")}),{priority:"low"})}}(n)),i.for("editingDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{if(!i.consumable.consume(t.item,"attribute:htmlLinkAttributes:drupalMedia"))return;const n=i.mapper.toViewElement(t.item),a=function(e,t,i){const n=e.createRangeOn(t);for(const{item:e}of n.getWalker())if(e.is("element",i))return e}(i.writer,n,"a");D(i.writer,t.item.getAttribute("htmlLinkAttributes"),a)}),{priority:"low"})})),i.for("dataDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{if(!i.consumable.consume(t.item,"attribute:htmlLinkAttributes:drupalMedia"))return;const n=i.mapper.toViewElement(t.item).parent;D(i.writer,t.item.getAttribute("htmlLinkAttributes"),n)}),{priority:"low"}),e.on("attribute:htmlAttributes:drupalMedia",C,{priority:"low"})})),e.stop())}))}static get pluginName(){return"DrupalMediaGeneralHtmlSupport"}}class x extends e.Plugin{static get requires(){return[p,_,h,f,A]}static get pluginName(){return"DrupalMedia"}}var V=i("ckeditor5/src/engine.js");function S(e){return Array.from(e.getChildren()).find((e=>"drupal-media"===e.name))}function T(e){return t=>{t.on(`attribute:${e.id}:drupalMedia`,((t,i,n)=>{const a=n.mapper.toViewElement(i.item);let r=Array.from(a.getChildren()).find((e=>"a"===e.name));if(r=!r&&a.is("element","a")?a:Array.from(a.getAncestors()).find((e=>"a"===e.name)),r){for(const[t,i]of(0,E.toMap)(e.attributes))n.writer.setAttribute(t,i,r);e.classes&&n.writer.addClass(e.classes,r);for(const t in e.styles)Object.prototype.hasOwnProperty.call(e.styles,t)&&n.writer.setStyle(t,e.styles[t],r)}}))}}function I(e,t){return e=>{e.on("element:a",((e,i,n)=>{const a=i.viewItem;if(!S(a))return;const r=new V.Matcher(t._createPattern()).match(a);if(!r)return;if(!n.consumable.consume(a,r.match))return;const o=i.modelCursor.nodeBefore;n.writer.setAttribute(t.id,!0,o)}),{priority:"high"})}}class L extends e.Plugin{static get requires(){return["LinkEditing","DrupalMediaEditing"]}static get pluginName(){return"DrupalLinkMediaEditing"}init(){const{editor:e}=this;e.model.schema.extend("drupalMedia",{allowAttributes:["linkHref"]}),e.conversion.for("upcast").add((e=>{e.on("element:a",((e,t,i)=>{const n=t.viewItem,a=S(n);if(!a)return;if(!i.consumable.consume(n,{attributes:["href"],name:!0}))return;const r=n.getAttribute("href");if(!r)return;const o=i.convertItem(a,t.modelCursor);t.modelRange=o.modelRange,t.modelCursor=o.modelCursor;const s=t.modelCursor.nodeBefore;s&&s.is("element","drupalMedia")&&i.writer.setAttribute("linkHref",r,s)}),{priority:"high"})})),e.conversion.for("editingDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{const{writer:n}=i;if(!i.consumable.consume(t.item,e.name))return;const a=i.mapper.toViewElement(t.item),r=Array.from(a.getChildren()).find((e=>"a"===e.name));if(r)t.attributeNewValue?n.setAttribute("href",t.attributeNewValue,r):(n.move(n.createRangeIn(r),n.createPositionAt(a,0)),n.remove(r));else{const e=Array.from(a.getChildren()).find((e=>e.getAttribute("data-drupal-media-preview"))),i=n.createContainerElement("a",{href:t.attributeNewValue});n.insert(n.createPositionAt(a,0),i),n.move(n.createRangeOn(e),n.createPositionAt(i,0))}}),{priority:"high"})})),e.conversion.for("dataDowncast").add((e=>{e.on("attribute:linkHref:drupalMedia",((e,t,i)=>{const{writer:n}=i;if(!i.consumable.consume(t.item,e.name))return;const a=i.mapper.toViewElement(t.item),r=n.createContainerElement("a",{href:t.attributeNewValue});n.insert(n.createPositionBefore(a),r),n.move(n.createRangeOn(a),n.createPositionAt(r,0))}),{priority:"high"})})),this._enableManualDecorators()}_enableManualDecorators(){const e=this.editor,t=e.commands.get("link");for(const i of t.manualDecorators)e.model.schema.extend("drupalMedia",{allowAttributes:i.id}),e.conversion.for("downcast").add(T(i)),e.conversion.for("upcast").add(I(0,i))}}class P extends e.Plugin{static get requires(){return["LinkEditing","LinkUI","DrupalMediaEditing"]}static get pluginName(){return"DrupalLinkMediaUi"}init(){const{editor:e}=this,t=e.editing.view.document;this.listenTo(t,"click",((t,i)=>{this._isSelectedLinkedMedia(e.model.document.selection)&&(i.preventDefault(),t.stop())}),{priority:"high"}),this._createToolbarLinkMediaButton()}_createToolbarLinkMediaButton(){const{editor:e}=this;e.ui.componentFactory.add("drupalLinkMedia",(t=>{const i=new g.ButtonView(t),n=e.plugins.get("LinkUI"),a=e.commands.get("link");return i.set({isEnabled:!0,label:Drupal.t("Link media"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m11.077 15 .991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955.741.741 0 0 1 .12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184z"/></svg>\n',keystroke:"Ctrl+K",tooltip:!0,isToggleable:!0}),i.bind("isEnabled").to(a,"isEnabled"),i.bind("isOn").to(a,"value",(e=>!!e)),this.listenTo(i,"execute",(()=>{this._isSelectedLinkedMedia(e.model.document.selection)?n._addActionsView():n._showUI(!0)})),i}))}_isSelectedLinkedMedia(e){const t=e.getSelectedElement();return!!t&&t.is("element","drupalMedia")&&t.hasAttribute("linkHref")}}class B extends e.Plugin{static get requires(){return[L,P]}static get pluginName(){return"DrupalLinkMedia"}}const{objectFullWidth:O,objectInline:N,objectLeft:R,objectRight:j,objectCenter:F,objectBlockLeft:U,objectBlockRight:H}=e.icons,q={get inline(){return{name:"inline",title:"In line",icon:N,modelElements:["imageInline"],isDefault:!0}},get alignLeft(){return{name:"alignLeft",title:"Left aligned image",icon:R,modelElements:["imageBlock","imageInline"],className:"image-style-align-left"}},get alignBlockLeft(){return{name:"alignBlockLeft",title:"Left aligned image",icon:U,modelElements:["imageBlock"],className:"image-style-block-align-left"}},get alignCenter(){return{name:"alignCenter",title:"Centered image",icon:F,modelElements:["imageBlock"],className:"image-style-align-center"}},get alignRight(){return{name:"alignRight",title:"Right aligned image",icon:j,modelElements:["imageBlock","imageInline"],className:"image-style-align-right"}},get alignBlockRight(){return{name:"alignBlockRight",title:"Right aligned image",icon:H,modelElements:["imageBlock"],className:"image-style-block-align-right"}},get block(){return{name:"block",title:"Centered image",icon:F,modelElements:["imageBlock"],isDefault:!0}},get side(){return{name:"side",title:"Side image",icon:j,modelElements:["imageBlock"],className:"image-style-side"}}},$={full:O,left:U,right:H,center:F,inlineLeft:R,inlineRight:j,inline:N},W=[{name:"imageStyle:wrapText",title:"Wrap text",defaultItem:"imageStyle:alignLeft",items:["imageStyle:alignLeft","imageStyle:alignRight"]},{name:"imageStyle:breakText",title:"Break text",defaultItem:"imageStyle:block",items:["imageStyle:alignBlockLeft","imageStyle:block","imageStyle:alignBlockRight"]}];function K(e){(0,E.logWarning)("image-style-configuration-definition-invalid",e)}const z={normalizeStyles:function(e){return(e.configuredStyles.options||[]).map((e=>function(e){e="string"==typeof e?q[e]?{...q[e]}:{name:e}:function(e,t){const i={...t};for(const n in e)Object.prototype.hasOwnProperty.call(t,n)||(i[n]=e[n]);return i}(q[e.name],e);"string"==typeof e.icon&&(e.icon=$[e.icon]||e.icon);return e}(e))).filter((t=>function(e,{isBlockPluginLoaded:t,isInlinePluginLoaded:i}){const{modelElements:n,name:a}=e;if(!(n&&n.length&&a))return K({style:e}),!1;{const a=[t?"imageBlock":null,i?"imageInline":null];if(!n.some((e=>a.includes(e))))return(0,E.logWarning)("image-style-missing-dependency",{style:e,missingPlugins:n.map((e=>"imageBlock"===e?"ImageBlockEditing":"ImageInlineEditing"))}),!1}return!0}(t,e)))},getDefaultStylesConfiguration:function(e,t){return e&&t?{options:["inline","alignLeft","alignRight","alignCenter","alignBlockLeft","alignBlockRight","block","side"]}:e?{options:["block","side"]}:t?{options:["inline","alignLeft","alignRight"]}:{}},getDefaultDropdownDefinitions:function(e){return e.has("ImageBlockEditing")&&e.has("ImageInlineEditing")?[...W]:[]},warnInvalidStyle:K,DEFAULT_OPTIONS:q,DEFAULT_ICONS:$,DEFAULT_DROPDOWN_DEFINITIONS:W};function Z(e,t,i){for(const n of t)if(i.checkAttribute(e,n))return!0;return!1}function G(e,t,i){const n=e.getSelectedElement();if(n&&Z(n,i,t))return n;let{parent:a}=e.getFirstPosition();for(;a;){if(a.is("element")&&Z(a,i,t))return a;a=a.parent}return null}class J extends e.Command{constructor(e,t){super(e),this.styles={},Object.keys(t).forEach((e=>{this.styles[e]=new Map(t[e].map((e=>[e.name,e])))})),this.modelAttributes=[];for(const e of Object.keys(t)){const t=u(e);this.modelAttributes.push(t)}}refresh(){const{editor:e}=this,t=G(e.model.document.selection,e.model.schema,this.modelAttributes);this.isEnabled=!!t,this.isEnabled?this.value=this.getValue(t):this.value=!1}getValue(e){const t={};return Object.keys(this.styles).forEach((i=>{const n=u(i);if(e.hasAttribute(n))t[i]=e.getAttribute(n);else for(const[,e]of this.styles[i])e.isDefault&&(t[i]=e.name)})),t}execute(e={}){const{editor:{model:t}}=this,{value:i,group:n}=e,a=u(n);t.change((e=>{const r=G(t.document.selection,t.schema,this.modelAttributes);!i||this.styles[n].get(i).isDefault?e.removeAttribute(a,r):e.setAttribute(a,i,r)}))}}function X(e,t){for(const i of t)if(i.name===e)return i}class Q extends e.Plugin{init(){const{editor:t}=this,i=t.config.get("drupalElementStyles");this.normalizedStyles={},Object.keys(i).forEach((t=>{this.normalizedStyles[t]=i[t].map((t=>("string"==typeof t.icon&&e.icons[t.icon]&&(t.icon=e.icons[t.icon]),t.name&&(t.name=`${t.name}`),t))).filter((e=>e.isDefault||e.attributeName&&e.attributeValue?e.modelElements&&Array.isArray(e.modelElements)?!!e.name||(console.warn("drupalElementStyles options must include a name."),!1):(console.warn("drupalElementStyles options must include an array of supported modelElements."),!1):(console.warn(`${e.attributeValue} drupalElementStyles options must include attributeName and attributeValue.`),!1)))})),this._setupConversion(),t.commands.add("drupalElementStyle",new J(t,this.normalizedStyles))}_setupConversion(){const{editor:e}=this,{schema:t}=e.model;Object.keys(this.normalizedStyles).forEach((i=>{const n=u(i),a=(r=this.normalizedStyles[i],(e,t,i)=>{if(!i.consumable.consume(t.item,e.name))return;const n=X(t.attributeNewValue,r),a=X(t.attributeOldValue,r),o=i.mapper.toViewElement(t.item),s=i.writer;a&&("class"===a.attributeName?s.removeClass(a.attributeValue,o):s.removeAttribute(a.attributeName,o)),n&&("class"===n.attributeName?s.addClass(n.attributeValue,o):n.isDefault||s.setAttribute(n.attributeName,n.attributeValue,o))});var r;const o=function(e,t){const i=e.filter((e=>!e.isDefault));return(e,n,a)=>{if(!n.modelRange)return;const r=n.viewItem,o=(0,E.first)(n.modelRange.getItems());if(o&&a.schema.checkAttribute(o,t))for(const e of i)if("class"===e.attributeName)a.consumable.consume(r,{classes:e.attributeValue})&&a.writer.setAttribute(t,e.name,o);else if(a.consumable.consume(r,{attributes:[e.attributeName]}))for(const e of i)e.attributeValue===r.getAttribute(e.attributeName)&&a.writer.setAttribute(t,e.name,o)}}(this.normalizedStyles[i],n);e.editing.downcastDispatcher.on(`attribute:${n}`,a),e.data.downcastDispatcher.on(`attribute:${n}`,a);[...new Set(this.normalizedStyles[i].map((e=>e.modelElements)).flat())].forEach((e=>{t.extend(e,{allowAttributes:n})})),e.data.upcastDispatcher.on("element",o,{priority:"low"})}))}static get pluginName(){return"DrupalElementStyleEditing"}}const Y=e=>e,ee=(e,t)=>(e?`${e}: `:"")+t;function te(e,t){return`drupalElementStyle:${t}:${e}`}class ie extends e.Plugin{static get requires(){return[Q]}init(){const{plugins:e}=this.editor,t=this.editor.config.get("drupalMedia.toolbar")||[],i=e.get("DrupalElementStyleEditing").normalizedStyles;Object.keys(i).forEach((e=>{i[e].forEach((t=>{this._createButton(t,e,i[e])}))}));t.filter(l).filter((e=>{const t=[];if(!e.display)return console.warn("dropdown configuration must include a display key specifying either listDropdown or splitButton."),!1;e.items.includes(e.defaultItem)||console.warn("defaultItem must be part of items in the dropdown configuration.");for(const i of e.items){const e=i.split(":")[1];t.push(e)}return!!t.every((e=>e===t[0]))||(console.warn("dropdown configuration should only contain buttons from one group."),!1)})).forEach((e=>{if(e.items.length>=2){const t=e.name.split(":")[1];switch(e.display){case"splitButton":this._createDropdown(e,i[t]);break;case"listDropdown":this._createListDropdown(e,i[t])}}}))}updateOptionVisibility(e,t,i,n){const{selection:a}=this.editor.model.document,r={};r[n]=e;const o=a?a.getSelectedElement():G(a,this.editor.model.schema,r),s=e.filter((function(e){for(const[t,i]of(0,E.toMap)(e.modelAttributes))if(o&&o.hasAttribute(t))return i.includes(o.getAttribute(t));return!0}));i.hasOwnProperty("model")?s.includes(t)?i.model.set({class:""}):i.model.set({class:"ck-hidden"}):s.includes(t)?i.set({class:""}):i.set({class:"ck-hidden"})}_createDropdown(e,t){const i=this.editor.ui.componentFactory;i.add(e.name,(n=>{let a;const{defaultItem:r,items:o,title:s}=e,l=o.filter((e=>{const i=e.split(":")[1];return t.find((({name:t})=>te(t,i)===e))})).map((e=>{const t=i.create(e);return e===r&&(a=t),t}));o.length!==l.length&&z.warnInvalidStyle({dropdown:e});const d=(0,g.createDropdown)(n,g.SplitButtonView),u=d.buttonView;return(0,g.addToolbarToDropdown)(d,l),u.set({label:ee(s,a.label),class:null,tooltip:!0}),u.bind("icon").toMany(l,"isOn",((...e)=>{const t=e.findIndex(Y);return t<0?a.icon:l[t].icon})),u.bind("label").toMany(l,"isOn",((...e)=>{const t=e.findIndex(Y);return ee(s,t<0?a.label:l[t].label)})),u.bind("isOn").toMany(l,"isOn",((...e)=>e.some(Y))),u.bind("class").toMany(l,"isOn",((...e)=>e.some(Y)?"ck-splitbutton_flatten":null)),u.on("execute",(()=>{l.some((({isOn:e})=>e))?d.isOpen=!d.isOpen:a.fire("execute")})),d.bind("isEnabled").toMany(l,"isEnabled",((...e)=>e.some(Y))),d}))}_createButton(e,t,i){const n=e.name;this.editor.ui.componentFactory.add(te(n,t),(a=>{const r=this.editor.commands.get("drupalElementStyle"),o=new g.ButtonView(a);return o.set({label:e.title,icon:e.icon,tooltip:!0,isToggleable:!0}),o.bind("isEnabled").to(r,"isEnabled"),o.bind("isOn").to(r,"value",(e=>e&&e[t]===n)),o.on("execute",this._executeCommand.bind(this,n,t)),this.listenTo(this.editor.ui,"update",(()=>{this.updateOptionVisibility(i,e,o,t)})),o}))}getDropdownListItemDefinitions(e,t,i){const n=new E.Collection;return e.forEach((t=>{const a={type:"button",model:new g.Model({group:i,commandValue:t.name,label:t.title,withText:!0,class:""})};n.add(a),this.listenTo(this.editor.ui,"update",(()=>{this.updateOptionVisibility(e,t,a,i)}))})),n}_createListDropdown(e,t){const i=this.editor.ui.componentFactory;i.add(e.name,(n=>{let a;const{defaultItem:r,items:o,title:s,defaultText:l}=e,d=e.name.split(":")[1],u=o.filter((e=>t.find((({name:t})=>te(t,d)===e)))).map((e=>{const t=i.create(e);return e===r&&(a=t),t}));o.length!==u.length&&z.warnInvalidStyle({dropdown:e});const c=(0,g.createDropdown)(n,g.DropdownButtonView),m=c.buttonView;m.set({label:ee(s,a.label),class:null,tooltip:l,withText:!0});const p=this.editor.commands.get("drupalElementStyle");return m.bind("label").to(p,"value",(e=>{if(e&&e[d])for(const i of t)if(i.name===e[d])return i.title;return l})),c.bind("isOn").to(p),c.bind("isEnabled").to(this),(0,g.addListToDropdown)(c,this.getDropdownListItemDefinitions(t,p,d)),this.listenTo(c,"execute",(e=>{this._executeCommand(e.source.commandValue,e.source.group)})),c}))}_executeCommand(e,t){this.editor.execute("drupalElementStyle",{value:e,group:t}),this.editor.editing.view.focus()}static get pluginName(){return"DrupalElementStyleUi"}}class ne extends e.Plugin{static get requires(){return[Q,ie]}static get pluginName(){return"DrupalElementStyle"}}function ae(e){const t=e.getFirstPosition().findAncestor("caption");return t&&a(t.parent)?t:null}function re(e){for(const t of e.getChildren())if(t&&t.is("element","caption"))return t;return null}class oe extends e.Command{refresh(){const e=this.editor.model.document.selection,t=e.getSelectedElement();if(!t)return this.isEnabled=!!o(e),void(this.value=!!ae(e));this.isEnabled=a(t),this.isEnabled?this.value=!!re(t):this.value=!1}execute(e={}){const{focusCaptionOnShow:t}=e;this.editor.model.change((e=>{this.value?this._hideDrupalMediaCaption(e):this._showDrupalMediaCaption(e,t)}))}_showDrupalMediaCaption(e,t){const i=this.editor.model.document.selection,n=this.editor.plugins.get("DrupalMediaCaptionEditing"),a=o(i),r=n._getSavedCaption(a)||e.createElement("caption");e.append(r,a),t&&e.setSelection(r,"in")}_hideDrupalMediaCaption(e){const t=this.editor,i=t.model.document.selection,n=t.plugins.get("DrupalMediaCaptionEditing");let a,r=i.getSelectedElement();r?a=re(r):(a=ae(i),r=o(i)),n._saveCaption(r,a),e.setSelection(r,"on"),e.remove(a)}}class se extends e.Plugin{static get requires(){return[]}static get pluginName(){return"DrupalMediaCaptionEditing"}constructor(e){super(e),this._savedCaptionsMap=new WeakMap}init(){const e=this.editor,t=e.model.schema;t.isRegistered("caption")?t.extend("caption",{allowIn:"drupalMedia"}):t.register("caption",{allowIn:"drupalMedia",allowContentOf:"$block",isLimit:!0}),e.commands.add("toggleMediaCaption",new oe(e)),this._setupConversion()}_setupConversion(){const e=this.editor,i=e.editing.view;var n;e.conversion.for("upcast").add(function(e){const t=(t,i,n)=>{const{viewItem:a}=i,{writer:r,consumable:o}=n;if(!i.modelRange||!o.consume(a,{attributes:["data-caption"]}))return;const s=r.createElement("caption"),l=i.modelRange.start.nodeAfter,d=e.data.processor.toView(a.getAttribute("data-caption")),u=r.createDocumentFragment();n.consumable.constructor.createFrom(d,n.consumable),n.convertChildren(d,u);for(const e of Array.from(u.getChildren()))r.append(e,s);r.append(s,l)};return e=>{e.on("element:drupal-media",t,{priority:"low"})}}(e)),e.conversion.for("editingDowncast").elementToElement({model:"caption",view:(e,{writer:n})=>{if(!a(e.parent))return null;const r=n.createEditableElement("figcaption");return(0,V.enablePlaceholder)({view:i,element:r,text:Drupal.t("Enter media caption"),keepOnFocus:!0}),(0,t.toWidgetEditable)(r,n)}}),e.editing.mapper.on("modelToViewPosition",(n=i,(e,t)=>{const i=t.modelPosition,r=i.parent;if(!a(r))return;const o=t.mapper.toViewElement(r);t.viewPosition=n.createPositionAt(o,i.offset+1)})),e.conversion.for("dataDowncast").add(function(e){return t=>{t.on("insert:caption",((t,i,n)=>{const{consumable:r,writer:o,mapper:s}=n;if(!a(i.item.parent)||!r.consume(i.item,"insert"))return;const l=e.model.createRangeIn(i.item),d=o.createDocumentFragment();s.bindElements(i.item,d);for(const{item:t}of Array.from(l)){const i={item:t,range:e.model.createRangeOn(t)},a=`insert:${t.name||"$text"}`;e.data.downcastDispatcher.fire(a,i,n);for(const a of t.getAttributeKeys())Object.assign(i,{attributeKey:a,attributeOldValue:null,attributeNewValue:i.item.getAttribute(a)}),e.data.downcastDispatcher.fire(`attribute:${a}`,i,n)}for(const e of o.createRangeIn(d).getItems())s.unbindViewElement(e);s.unbindViewElement(d);const u=e.data.processor.toData(d);if(u){const e=s.toViewElement(i.item.parent);o.setAttribute("data-caption",u,e)}}))}}(e))}_getSavedCaption(e){const t=this._savedCaptionsMap.get(e);return t?V.Element.fromJSON(t):null}_saveCaption(e,t){this._savedCaptionsMap.set(e,t.toJSON())}}class le extends e.Plugin{static get requires(){return[]}static get pluginName(){return"DrupalMediaCaptionUI"}init(){const{editor:t}=this,i=t.editing.view;t.ui.componentFactory.add("toggleDrupalMediaCaption",(n=>{const a=new g.ButtonView(n),r=t.commands.get("toggleMediaCaption");return a.set({label:Drupal.t("Caption media"),icon:e.icons.caption,tooltip:!0,isToggleable:!0}),a.bind("isOn","isEnabled").to(r,"value","isEnabled"),a.bind("label").to(r,"value",(e=>e?Drupal.t("Toggle caption off"):Drupal.t("Toggle caption on"))),this.listenTo(a,"execute",(()=>{t.execute("toggleMediaCaption",{focusCaptionOnShow:!0});const e=ae(t.model.document.selection);if(e){const n=t.editing.mapper.toViewElement(e);i.scrollToTheSelection(),i.change((e=>{e.addClass("drupal-media__caption_highlighted",n)}))}})),a}))}}class de extends e.Plugin{static get requires(){return[se,le]}static get pluginName(){return"DrupalMediaCaption"}}const ue={DrupalMedia:x,MediaImageTextAlternative:A,MediaImageTextAlternativeEditing:y,MediaImageTextAlternativeUi:k,DrupalLinkMedia:B,DrupalMediaCaption:de,DrupalElementStyle:ne}})(),n=n.default})()}));