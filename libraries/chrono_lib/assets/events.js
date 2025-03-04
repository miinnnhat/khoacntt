function HideField(field){
	if(field.closest(".field.holder")){
		field.closest(".field.holder").classList.add("hidden");
	}else{
		field.classList.add("hidden");
	}
}
function ShowField(field){
	if(field.closest(".field.holder")){
		field.closest(".field.holder").classList.remove("hidden");
	}else{
		field.classList.remove("hidden");
	}
}
function EnableField(field){
	if(field.closest(".field.holder")){
		field.closest(".field.holder").classList.remove("disabled");
	}else{
		field.classList.remove("disabled");
	}
	field.disabled = false;
}
function DisableField(field){
	if(field.closest(".field.holder")){
		field.closest(".field.holder").classList.add("disabled");
	}else{
		field.classList.add("disabled");
	}
	field.disabled = true;
}
function DisableValidation(field){
	field.setAttribute('disable-validations', '1');
	field.closest(".field.holder").classList.remove("error");
	if(field.closest(".field.holder").querySelector('.errormsg')){
		field.closest(".field.holder").querySelector('.errormsg').remove();
	};
	if(field.closest(".field.holder").querySelector('.errormark')){
		field.closest(".field.holder").querySelector('.errormark').classList.add('hidden');
	};
}
function EnableValidation(field){
	field.removeAttribute('disable-validations');
	if(field.closest(".field.holder").querySelector('.errormark')){
		field.closest(".field.holder").querySelector('.errormark').classList.remove('hidden');
	};
}
function CallFunction(name, field){
	if(typeof window[name] === 'function'){
		window[name](field)
	}
}
function SetValue(field, values){
	field.value = values[0]
}
function ClearValue(field){
	field.value = ""
}
function SubmitForm(field){
	field.closest("form").submit()
}
function SelectAll(checksone){
	document.querySelectorAll("input[name='"+checksone.getAttribute("name")+"']").forEach(check => {
		Nui.Checkbox.getInstance(check.closest(".nui.checkbox")).toggle()
	})
}
function AJAX(field, url){
	field.closest("form").classList.add("loading");

	let postBody = new FormData(field.closest("form"))
	postBody.delete("chronopage")
	const xhttp = new XMLHttpRequest();

	xhttp.addEventListener("readystatechange", (e) => {
		field.closest("form").classList.remove("loading");
		if (e.target.readyState == 4 && e.target.status == 200) {
			
		}
	})

	xhttp.open("POST", url);
	xhttp.send(postBody);
}
function Reload(field, url){
	field.closest("form").classList.add("loading");

	let postBody = new FormData(field.closest("form"))
	postBody.delete("chronopage")
	const xhttp = new XMLHttpRequest();

	xhttp.addEventListener("readystatechange", (e) => {
		field.closest("form").classList.remove("loading");

		if (e.target.readyState == 4 && e.target.status == 200) {
			let results = Nui.Core.create_element(e.target.responseText, true)
			
			if(field.closest(".field.holder")){
				Array.from(results).forEach(item => {
					field.closest(".field.holder").before(item)
					Nui_boot(item)
				})
				Nui.Form.getInstance(field.closest(".nui.form")).init()
				field.closest(".field.holder").remove()
			}else{
				Array.from(results).forEach(item => {
					field.before(item)
					Nui_boot(item)
				})
				Nui.Form.getInstance(field.closest(".nui.form")).init()
				field.remove()
			}
		}
	})

	xhttp.open("POST", url);
	xhttp.send(postBody);
}
function LoadOptions(field, url){
	field.closest("form").classList.add("loading");

	let postBody = new FormData(field.closest("form"))
	postBody.delete("chronopage")
	const xhttp = new XMLHttpRequest();

	xhttp.addEventListener("readystatechange", (e) => {
		field.closest("form").classList.remove("loading");
		if (e.target.readyState == 4 && e.target.status == 200) {
			let options = JSON.parse(e.target.responseText)
			
			field.querySelectorAll("option").forEach((option) => {
				option.remove()
			})
			if (Array.isArray(options)) {
				Object.keys(options).forEach(key => {
					let opt = Nui.Core.create_element('<option value="'+options[key]["value"]+'">'+options[key]["text"]+'</option>')
					field.append(opt)
				})
				Nui.Dropdown.getInstance(field).init()
			} else {
				
			}
		}
	})

	xhttp.open("POST", url);
	xhttp.send(postBody);
}

function SetupEvent(field, event, fn){
	let fields = field.closest("form").querySelectorAll("[name='"+field.getAttribute("name")+"']");
	if(fields){
		fields.forEach(f => {
			f.addEventListener(event, e => {
				fn();
			})
		});
	}
}

function GetValues(field){
	let fields = field.closest("form").querySelectorAll("[name='"+field.getAttribute("name")+"']");
	if(fields){
		let values = [];
		fields.forEach(f => {
			if(field.getAttribute('type') == 'checkbox' || field.getAttribute('type') == 'radio'){
				if(f.checked){
					values.push(f.value);
				}
			}else{
				if(f.value.length > 0){
					values.push(f.value);
				}
			}
		});
		return values;
	}

	return [];
}

function isEmpty(field){
	let values = GetValues(field);
	return (values.length == 0);
}

function HasValue(field, tvalues){
	let values = GetValues(field);
	let result = false;
	tvalues.forEach(v => {
		if(values.includes(v)){
			result = true;
			return;
		}
	});
	return result;
}

function Matches(field, regex){
	let values = GetValues(field);
	let r = new RegExp(regex);
    return r.test(values[0]);
}