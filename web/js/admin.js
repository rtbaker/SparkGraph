$( document ).ready(function() {
	coreoverview();
	
	$("#addcore").submit(coresubmit);
	
	$('#corestable').delegate('tr', 'click', function(e){ 
		var id = $(this).find("td.coreid").text();
    showCoreDetail(id);
	});
});

function coreoverview(){
	setMenuHighlight("coreoverview");
	clear();
	
	$("#overviewblock").removeClass("hidden");
	
	$.getJSON( "/admin/overview.json", function( data ) {
		overview = data.no_of_cores + " core(s) and " + data.no_of_graphs + " graph(s) in the database.";
		$("#overview").text(overview);
	});
}

baseSparkUrl = "https://api.spark.io/v1/";

function cores(){
	setMenuHighlight("cores");
	clear();
	
	$("#coresblock").removeClass("hidden");
	
	$.getJSON( "/admin/listcores.json", function( data ) {
		$("#corestable tbody").html("");
		
	//	console.log(data);
		for (var num in data){
			core = data[num];
//			console.log(core);
			html = "<tr><td class=\"coreid\">" + core.id + "</td><td>" + core.name + "</td></tr>";
			$("#corestable tbody").append(html); 
		}
	});
	
	$('#corestable tr').click(function() {
		alert('hello');
	        var id = $(this).find("td").text();
	        showCoreDetail(id);
	 });
	
}

currentCore = "";

function showCoreDetail(id){
	$("#coredetailblock").removeClass("hidden");
	$("#detailspinner").removeClass("hidden");
	
	$.getJSON("/admin/coredetail/" + id)
		.done(function(data) {
			console.log(data);
			name = data.core.name;
			currentCore = data.core.id;
			$("#coredetailname").text(name);
			
			if (!data.cloud.connected){
				$("#coredetailconnected").text("not connected !");
			}
			
			vars = data.cloud.variables;

			$('#corevars tbody').html("");
			
			if (vars !== null){
				knownvars = data.vars;
				len = knownvars.length;
			
				jQuery.each(vars, function(key, value){
					checked = "";
					freq = 10;
					
					for (i = 0; i < len; i++){
						v = knownvars[i];
						if (v.name == key){
							if (v.collect == "1"){ checked = "checked"; }
							freq = v.frequency;
						}
					}
				
					html = "<tr><td class=\"varname\">" + key + "</td><td class=\"vartype\">" +
						value + "</td><td><input class=\"texttbl\" type=\"text\" value=\"" + freq +
						"\"/></td><td><input type=\"checkbox\" class=\"chcktbl\" " + checked +
						"/></td><td><input type=\"submit\" value=\"Update\" class=\"submittbl\"/></td></tr>";
					$("#corevars tbody").append(html);
				});
				
				$('.submittbl').click(varClicked);
			} else {
				// Show any already stored vars
			}
		})
		.fail(function(jqxhr, textStatus, error){
				
		})
		.always(function(){
				$("#detailspinner").addClass("hidden");
		});
}

function varClicked(){
	row = $(this).closest("tr");
	
	name = $(row).find(".varname").text();
	type = $(row).find(".vartype").text();
	frequency = $(row).find(".texttbl").val();
	checked = $(row).find(".chcktbl").is(':checked');
	
	console.log(name + " " + type + " " + checked + " " + frequency);
	
	$.post("/admin/checkVar", { 'id': currentCore, 'name': name, 'type': type, 'checked': checked, 'frequency': frequency})
		.done(function() {
			
		})
		.fail(function(jqxhr, textStatus, error){
			$("#checkvarerror").text("Error: " + jqxhr.responseText);
		})
		.always(function(){

		});
}

dolookup=1;

function coresubmit(event){
	$("#spinner").removeClass("hidden");
	
	if (dolookup){
		$("#addcoreerror").text("");
		
		id = $("input:text[name=coreid]").val();
		token = $("input:text[name=apikey]").val();
		
		$.getJSON(baseSparkUrl + "devices/" + id + "?access_token=" + token)
			.done(function(data) {
				console.log(data);
				dolookup = 0;
				$("#addcoresubmit").val("Add to DB");
				$("#addcoreinfo").text("Core found ! Name: " + data.name);
				$("input:hidden[name=corename]").val(data.name);
			})
			.fail(function(jqxhr, textStatus, error){
				console.log(jqxhr);
				response = jQuery.parseJSON(jqxhr.responseText);
				
				err = "Unknown";

				if (jqxhr.status == "403"){ err = response.info; }
				else if (jqxhr.status == "400"){ err = response.error_description; }
				
				$("#addcoreerror").text("Error: " + err);
			})
			.always(function(){
				$("#spinner").addClass("hidden");
			});
		
	} else {
		// Successfuly looked up core so add to db
		id = $("input:text[name=coreid]").val();
		token = $("input:text[name=apikey]").val();
		name = $("input:hidden[name=corename]").val();
		
		console.log("adding: \"" + id + "\", \"" + token + "\", \"" + name + "\"");
		
		$.post("/admin/addcore", { 'id': id, 'name': name, 'token': token})
			.done(function() {
				// reset
				$("input:text[name=coreid]").val("");
				$("input:text[name=apikey]").val("");
				$("#addcoresubmit").val("Lookup");
				$("#addcoreinfo").text("");
				$("#addcoreerror").text("");
				dolookup = 1;
				
				// reload the table
				cores();
			})
			.fail(function(jqxhr, textStatus, error){
				$("#addcoreerror").text("Error: " + jqxhr.responseText);
			})
			.always(function(){
				$("#spinner").addClass("hidden");
			});
	}

	event.preventDefault();
}

function graphs(){
	setMenuHighlight("graphs");
	clear();
	
	$("#graphsblock").removeClass("hidden");
}

function setMenuHighlight(current){
	$("#menu_coreoverview").removeClass("active");
	$("#menu_cores").removeClass("active");
	$("#menu_graphs").removeClass("active");
	
	full = "menu_" + current;
	$("#" + full).addClass("active");
}

function clear(){
	$("#overviewblock").addClass("hidden");
	$("#coresblock").addClass("hidden");
	$("#graphsblock").addClass("hidden");
	
	
}