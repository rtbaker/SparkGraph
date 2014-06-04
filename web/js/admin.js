$( document ).ready(function() {
	coreoverview();
});

function coreoverview(){
	setMenuHighlight("coreoverview");
	clear();
	
	$.getJSON( "/admin/overview.json", function( data ) {
		overview = data.no_of_cores + " core(s) and " + data.no_of_graphs + " graph(s) in the database.";
		$("#overview").text(overview);
	});
}

function cores(){
	setMenuHighlight("cores");
	clear();
	
	html = "<h2>Spark Cores</h2>";
	html += "<table class=\"table table-striped\">"
	              html += "<thead>"
	                html += "<tr>"
	                  html += "<th>ID</th>"
	                  html += "<th>Name</th>"
	                html += "</tr>"
	              html += "</thead>"
	              html += "<tbody>"
	               html += "</tbody>"
								html += "</table>";
								
	$("#cores").html(html);
}

function graphs(){
	setMenuHighlight("graphs");
	clear();
	
	html = "<h2>Graphs</h2>";
	html += "<table class=\"table table-striped\">"
	              html += "<thead>"
	                html += "<tr>"
	                  html += "<th>Graph</th>"
	                  html += "<th>SparkCore</th>"
	                html += "</tr>"
	              html += "</thead>"
	              html += "<tbody>"
	               html += "</tbody>"
								html += "</table>";
								
	$("#graphs").html(html);
}

function setMenuHighlight(current){
	$("#menu_coreoverview").removeClass("active");
	$("#menu_cores").removeClass("active");
	$("#menu_graphs").removeClass("active");
	
	full = "menu_" + current;
	$("#" + full).addClass("active");
}

function clear(){
	$("#overview").text("");
	$("#cores").html("");
	$("#graphs").html("");
}