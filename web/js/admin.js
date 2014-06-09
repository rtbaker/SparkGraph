$( document ).ready(function() {
	coreoverview();
	
	$("#addcore").submit(coresubmit);
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
			html = "<tr><td>" + core.id + "</td><td>" + core.name + "</td></tr>";
			$("#corestable tbody").append(html); 
		}
	});
}

dolookup=1;

function coresubmit(event){
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
			});
	}

	event.preventDefault();
}

function graphs(){
	setMenuHighlight("graphs");
	clear();
	
	$("#graphsblock").removeClass("hidden")
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