$( document ).ready(function() {
	graphoverview();
	
});

function graphoverview(){
	$.getJSON( "/listgraphs.json")
		.done(function(data) {
			for (var num in data){
				graph = data[num];
			//	console.log(graph);
				html = "<li><span class=\"hidden graphid\">" + graph.id + "</span><span class=\"graphtitle\">" + graph.title + "</span></li>";
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
			var title = $(this).find("span.graphtitle").text();
	    showGraph(id, title);
		});
}

function showGraph(id, title){
	console.log("show graph " + id);
	
	var jsonData = $.ajax({
          url: "graphdata.json?id=" + id,
          dataType:"json",
          async: false
          }).responseText;

console.log(jsonData);
      // Create our data table out of JSON data loaded from server.
    var data = new google.visualization.DataTable(jsonData)

	  var options = {
	    title: 'Graph: ' + title,
			  //  curveType: 'function',
	  };
    
	  var chart = new google.visualization.AnnotationChart(document.getElementById('chart_div'));
    
	  chart.draw(data, options);
}