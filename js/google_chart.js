if(typeof google.visualization =="undefined") 
         google.load("visualization", "1", {packages:["corechart"]});

function AreaChart(domId, input) {
        var data = google.visualization.arrayToDataTable(input['data']);

        var options = {
          title: input['title'],
          hAxis: input['hAxis'],
          vAxis: input['vAxis'],
          colors:input['colors'],
          orientation: input['orientation'],
        };

        var chart = new google.visualization.AreaChart(document.getElementById(domId));
        chart.draw(data, options);
      }

function BarChart(domId, input) {
  var data = google.visualization.arrayToDataTable(input['data']);
  var options = {
    title: input['title'],
    isStacked: input['isStacked'],
     colors: input['colors'],
      hAxis: input['hAxis'],
      vAxis: input['vAxis'],
     orientation: input['orientation'],
  };
  var chart = new google.visualization.BarChart(document.getElementById(domId));
  chart.draw(data, options);
}

      function Histogram(domId, input) {
        var data = google.visualization.arrayToDataTable(input['data']);

        var options = {
          title: input['title'],
          //legend: { position: 'none' },
           colors: input['colors'],
           hAxis: input['hAxis'],
           vAxis: input['vAxis'],
            histogram:{bucketSize:5},
        };

        var chart = new google.visualization.Histogram(document.getElementById(domId));
        chart.draw(data, options);
      }

       function LineChart(domId, input) {
        var data = google.visualization.arrayToDataTable(input['data']);

        var options = {
          title: input['title'],
          colors: input['colors'],
          orientation: input['orientation'],
          hAxis: input['hAxis'],
          vAxis: input['vAxis']
        };

        var chart = new google.visualization.LineChart(document.getElementById(domId));
        chart.draw(data, options);
      }

      function PieChart(domId, input) {
        var data = google.visualization.arrayToDataTable(input['data']);

        var options = {
          title: input['title'],
          //pieHole: 0.5,
          pieSliceText: 'label',
           colors:input['color'],
           /*slices: {  
                    1: {offset: 0.2},
                    2: {offset: 0.3},
          },*/
          //is3D: true,
        };

        var chart = new google.visualization.PieChart(document.getElementById(domId));
        chart.draw(data, options);
      }
    function ScatterChart(domId, input) {
        var data = google.visualization.arrayToDataTable(input['data']);

        var options = {
          title: input['title'],
          colors:input['colors'],
          orientation: input['orientation'],
          hAxis: input['hAxis'],
          vAxis: input['vAxis'],
          legend: 'none',
          //pointShape: 'star',
        };

        var chart = new google.visualization.ScatterChart(document.getElementById(domId));
        chart.draw(data, options);
      }