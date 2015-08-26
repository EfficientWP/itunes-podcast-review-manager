

window.onload = Init;

var pieData = [];
var barData = [];

function drawPieChart() {

		pieData.shift();  //remove show all option from array
		
			
		var dataArray = [['Country', 'Review count']];
		
		for (var i=0; i<pieData.length; i++) {
			pieData[i][0] = pieData[i][0].trim();
			pieData[i][0] =  pieData[i][0].replace(/\\n/g, "");
			dataArray.push(pieData[i]);
		}

        var data = google.visualization.arrayToDataTable(dataArray);

        var options = {
          title: 'Reviews by Country'
        };

        var chart = new google.visualization.PieChart(document.getElementById('iprm_piechart'));

        chart.draw(data, options);
	
}

function drawBarChart() {
		
		barData.shift();  //remove show all option from array
		
		barData.reverse();  //5,4,3,2,1 order
		
		var dataArray = [['Rating', 'Review count']];
		
		for (var i=0; i<barData.length; i++) {
			dataArray.push(barData[i]);
		}
		
        var data = google.visualization.arrayToDataTable(dataArray);

        var options = {
          chart: {
            title: 'Reviews by Rating',
			
          },
		  legend: { position: 'none' },
          bars: 'horizontal' // Required for Material Bar Charts.
        };

        var chart = new google.charts.Bar(document.getElementById('iprm_barchart_material'));

        chart.draw(data, options);
   
}	
	
function Init() {
	
	/* SET UP VARIABLES --PHP SHOULD HAVE IDS FOR SOME THINGS (TABLE HEADINGS, BUTTONS, ETC) */
	var reviewsTable = document.getElementById("iprm_main_table_body");
	var sortTableBtn = document.getElementById("sortTableBtn");
	
	
	/* LOAD SELECT BOXES: NOTE FUNCTIONS HAVE SIDE EFFECT OF FILLING PIEDATA AND BAR DATA FOR GOOGLE CHARTS */
	loadCountriesSelectBox(reviewsTable);
	loadRatingsSelectBox(reviewsTable);
	

    /* ADD EVENT LISTERNERS */
	sortTableBtn.onclick = function(){
	var countrySelectList = document.getElementById("countrySelect");
	var selectList = document.getElementById("ratingSelect");
	var countrySearchValues = getSelectedOptions(countrySelectList);
	var ratingSearchValues = getSelectedOptions(selectList);
	var rowsArray  = reviewsTable.rows;
	var rowCount = rowsArray.length;
	var i;

	if ((countrySearchValues[0] == "Show All") && (ratingSearchValues[0] == "Show All")) {

		for (i = 1; i < rowCount; i++) {
			rowsArray[i].style.display =  "table-row";
		}

	} else {
		
		for (i = 1; i < rowCount; i++) {
			
			/* HIDE IF NOT IN SELECTED ITEMS, AND SELECTED ITEMS ARE NOT EMPTY OR EQUAL TO "SHOW ALL"*/
						
			if (((countrySearchValues.indexOf(rowsArray[i].children[2].innerHTML) == -1) && (countrySearchValues.length != 0 &&countrySearchValues[0] != "Show All" ) ) || ((ratingSearchValues.indexOf(rowsArray[i].children[4].innerHTML) == -1) && (ratingSearchValues.length != 0 && ratingSearchValues[0] != "Show All"))){
				rowsArray[i].style.display = "none";
			}else {
				rowsArray[i].style.display =  "table-row";
			}
		}
			
		}
	}
	
	
	/* LOAD GOOGLE CHARTS */
	
	google.load("visualization", "1.1", {packages:["bar", "corechart"], callback: function() { drawPieChart(); drawBarChart();} });

	
} /* END INIT FUNCTION */

	
function createButton(parent, value, func){
    var button = document.createElement("input");
    button.type = "button";
    button.value = value;
    button.onclick = func;
    parent.appendChild(button);
}

function loadCountriesSelectBox(reviewsTable){

	var countrySelectList = document.getElementById("countrySelect");
	
     /* CREATE ARRAY OF OPTIONS TO BE ADDED */
	var uniqueCountries = []; 
    var OptsArray = [];  /* 2D ARRAY: 1ST ITEM IS NAME TO DISPLAY, 2ND IS COUNT */
	var tableRowCount = reviewsTable.rows.length;
	var currentInnerHTML;
	for (var i = 1; i < tableRowCount; i++) { /* USE 1 TO SKIP FIRST ROW (HEADER) */
	
		currentInnerHTML = reviewsTable.rows[i].children[2].innerHTML;

		
		if (uniqueCountries.indexOf(currentInnerHTML) == -1){/* PUSH NEW COUNTRY INTO ARRAY */
			OptsArray.push([currentInnerHTML,1]);
			uniqueCountries.push(currentInnerHTML);
		}else{ /* COUNTRY ALREADY IN ARRAY, INCREMENT COUNT */
			OptsArray[(uniqueCountries.indexOf(currentInnerHTML))][1]++;
		}
		
    }
	
	/* SORT ARRAY BY MOST POPULAR http://stackoverflow.com/questions/6490343/sorting-2-dimensional-javascript-array */
	
	OptsArray.sort(function(a, b) { return (a[1] > b[1] ? -1 : (a[1] < b[1] ? 1 : 0)); });
	OptsArray.unshift(["Show All",-1]);
	
	pieData = OptsArray;
 
    /* CREATE AND APPEND THE OPTIONS */
    for (var i = 0; i < OptsArray.length; i++) {
        var option = document.createElement("option");
        option.value = OptsArray[i][0];
		
		if (OptsArray[i][1] != -1) { /* DON'T ADD THE NUMBER FOR DEFAULT (SHOW ALL)		*/
			option.text = OptsArray[i][0] + " (" + OptsArray[i][1] + ")";
		}else{
			option.text = OptsArray[i][0];
		}
		
        countrySelectList.appendChild(option);
    }

}

function loadRatingsSelectBox(reviewsTable){

	var selectList = document.getElementById("ratingSelect");
	
   /* CREATE ARRAY OF OPTIONS TO BE ADDED */
    var OptsArray = [["Show All",-1],["1-star",0],["2-star",0],["3-star",0],["4-star",0],["5-star",0]];
	
	var tableRowCount = reviewsTable.rows.length;
	var currentInnerHTML;
	
	/* POPULATE NUMBER OF VALUES FOR EACH OPTION */
	for (var i = 1; i < tableRowCount; i++) { /* USE 1 TO SKIP FIRST ROW (HEADER) */
	
		currentInnerHTML = parseInt(reviewsTable.rows[i].children[4].innerHTML, 10);	
		OptsArray[currentInnerHTML][1]++;
			
    }
	
	barData = OptsArray;
	
    /* CREATE AND APPEND THE OPTIONS */
    for (var i = 0; i < OptsArray.length; i++) {
        var option = document.createElement("option");
		
		if (i == 0){ /* A SPECIAL VALUE FOR SHOW ALL */
			option.value = "Show All";
		}else {
			option.value = parseInt(OptsArray[i][0],10);
		}
		
		if (OptsArray[i][1] != -1) { /* DON'T ADD THE NUMBER FOR DEFAULT (SHOW ALL)	*/	
			option.text = OptsArray[i][0] + " (" + OptsArray[i][1] + ")";
		}else{
			option.text = OptsArray[i][0];
		}
     
        selectList.appendChild(option);
    }
	
}


function getSelectedOptions(sel) {
    var opts = new Array();
    
    /* LOOP THROUGH OPTIONS IN SELECT LIST */
    for (var i=0, len=sel.options.length; i<len; i++) {
        opt = sel.options[i];
        
        /* CHECK IF SELECTED */
        if ( opt.selected ) {
            /* ADD TO ARRAY OF OPTION ELEMENTS TO RETURN FROM THIS FUNCTION */
            opts.push(opt.value);
          
        }
    }
    
    /* RETURN ARRAY CONTAINING REFERENCES TO SELECTED OPTION ELEMENTS */
    return opts;
}


   	