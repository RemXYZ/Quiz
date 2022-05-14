/*
AsaJS jQuery Addon v.0.4.9

(c) 2021 by RemXYZ. All rights reserved.
This source code is licensed under the MIT license found in the
LICENSE file in the root directory of this source tree.

https://github.com/RemXYZ/AsaJS
*/

function asaPluginTest() {
	console.log("Hello this is Asa :)");
}


//MODIFIED 29.08.21
//CREATE(MAKE) A ELEMENT
function ASAcrEl (tag, att, callback, options) {
var settings = $.extend({
	// These are the defaults.
	noMod: false,
	multi:false
}, options );


let outNode;

function createElement (el, tag, att, callback, options) {
	//att mast be a object
	//expample {"class":"input","type":"number"}
	let node = document.createElement(tag);
	if (typeof att == 'object') {
		for(var key in att) {
			if (att.hasOwnProperty(key)){
				node.setAttribute(key,att[key]);
			}
		}
	}
	if (typeof att == "string") {
		const isClass = new RegExp ("^\\.","i");
		if(isClass.test(att)){
			let text = replaceAt(att,0); 
			let splited = text.split(" ");
			splited.map((e)=>node.classList.add(e));	
		}

		const isId = new RegExp ("^\\#");
		if(isId.test(att)) {
			let text = replaceAt(att,0); 
			node.id = text;
		}
	}

	if (!options.noAdding) {
		node = $(node);
	}
	

	if (callback !== undefined && callback != "" && callback != false) {
		callback(node);
	}

	if (el instanceof Element & typeof el == "object") {
		el.append(node[0]);
	}

	return node
}


if (settings.multi) {
	this.each((i, el)=>{
		outNode = createElement(el, tag, att, callback, settings)
	})
}else {
	el = this[this.length - 1];
	outNode = createElement(el, tag, att, callback, settings)
}
	
return outNode;
}


//Added 26.08.21 v0.4.3
//Changing the default innerHTML method
const ASAhtml = function (txt,el) {
	if (this.tagName == "INPUT"
	||this.tagName == "TEXTAREA"
	){
		if (txt === undefined) {
			return this.value;
		}
		this.value = txt;
		return this;
	}

	if (txt === undefined) {
		return this.innerHTML;
	}
	this.innerHTML = txt;
	return this;
}
//Added 27.09.21
const ASAahtml = function (txt) {
	return this.html(this.html() + txt);
}

//Added 26.08.21 v0.4.4
const ASAinto = function (obj,prepend) {
	if (obj instanceof Element & typeof obj == "object") {
		if (prepend == true) {
			obj.prepend(this);
		}else {
			obj.append(this);
		}
	}else {
		console.error(`${obj} is not DOM element`)
	}

	return this;
}

/////////////////TIME PART///////////////////////////////

const ASAget_my_time = function (lg) {
	const date = {};

	let NewDate = new Date();
	date.yr = NewDate.getFullYear();
	date.s_yr = date.yr;
	date.mo = NewDate.getMonth()+1;
	date.wk = NewDate.getDay();
	date.wk_name = false;
	date.d = NewDate.getDate();
	date.h = NewDate.getHours();
	date.min = NewDate.getMinutes();
	date.s = NewDate.getSeconds();
	date.unix = +NewDate;

	if (lg !== undefined) {
		if (week_names[lg] !== undefined) {
			date.wk_name = week_names[lg][date.wk];
		}
	}

	if(date.mo > 12) {
		date.mo = 1;
	}
	
	//Check for correct DD.MM.YYYY format
	for (let key of Object.keys(date)) {
		if (key == "r") {
			continue;
		}
		if (key == "wk_name") {
			continue;
		}
		if (key == "s_yr") {
			let str = String(date.s_yr);
			date.s_yr = str.substr(2);
			continue;
		}
		date[key] = if_lt_10(date[key])
	}

	return date;
}

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////END TIME PART///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////

//jQuery plugin tutorial: https://learn.jquery.com/plugins/basic-plugin-creation/

(function ( $ ) {
	$.fn.into = ASAinto;
	$.fn.crEl = ASAcrEl;
	$.fn.asaPluginTest = asaPluginTest;
 
}( jQuery ));
