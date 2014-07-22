$( document ).ready(function() {
	graphoverview();
	
});

function graphoverview(){
	$.getJSON( "/listgraphs.json")
		.done(function(data) {
			for (var num in data){
				graph = data[num];
				console.log(graph);
				html = "<li><span class=\"hidden graphid\">" + graph.id + "</span>" + graph.title + "</li>";
				$("#graphlist").append(html); 
			}
		})
		.fail(function(jqxhr, textStatus, error){
			console.log(error);
		})
		.always(function(){

		});
		
		$('#graphlist').delegate('li', 'click', function(e){ 
			var id = $(this).find("span.graphid").text();
	    showGraph(id);
		});
}

function showGraph(id){
	console.log("show graph " + id);
}