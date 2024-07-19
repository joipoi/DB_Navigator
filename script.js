let tableSelect;
let columnSelect;
let columnInput;
let columnSortSelect;
let sortDirectionSelect;

//inits the program with variables and eventListeners
jQuery( document ).ready(function() {
 tableSelect = document.getElementById("tableSelect");
 if(tableSelect){
     let getDataButton = document.getElementById("getDataButton");
     let downloadButton = document.getElementById("downloadButton");
     columnInput = document.getElementById("columnInput");
    columnSelect = document.getElementById("columnSelect");
     columnSortSelect = document.getElementById("columnSortSelect");
     sortDirectionSelect = document.getElementById("sortDirectionSelect");
     

     downloadButton.addEventListener('click', function(){
        //only runs if a table is selected
        if(tableSelect.value != -1){
        downloadTableAjax(tableSelect.value, columnSelect.value, columnInput.value, columnSortSelect.value, sortDirectionSelect.value);
        }
     });

     tableSelect.addEventListener('change', function(){
        getTableAjax(tableSelect.value);
     });

     getDataButton.addEventListener('click', function(){
        //only runs if a table is selected
        if(tableSelect.value != -1){
           filterByColumnAjax(tableSelect.value, columnSelect.value, columnInput.value, columnSortSelect.value, sortDirectionSelect.value, 1);
        }
     });
 }
 
});
//gets database data depening on various user selected metrics
function filterByColumnAjax(tableName, columnName, columnValue, sortColumn, sortDirection, page) {
    jQuery.post(
        ajaxurl,
        {
            'action': 'filterTable',
            'columnName': columnName,
            'columnValue': columnValue,
            'tableName': tableName,
            'sortColumn': sortColumn,
            'sortDirection': sortDirection,
            'page': page,
            'nonce': your_plugin_ajax_object.nonce
        },
        function (response) {
            if (response.success) {
                let tableRows = response.data.rows;
                let pageAmount = response.data.totalPages;
                createTableFromData(tableRows, pageAmount, page);
            } else {
                alert('Error: ' + response.data.message);
            }
        }
    ).fail(function (jqXHR, textStatus, errorThrown) {
        alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
    });
}

//creates a list of a tables columns, also populates html selects with the column names
function getTableAjax(pageName){
    jQuery.post(
        ajaxurl, 
        {
            'action': 'getTable',
            'pageName':   pageName
        }, 
        function(response) {
            //resets the html elements before adding new data to them
            document.querySelector('.pagination').innerHTML = "";
            columnSelect.innerHTML = '<select id="columnSelect"><option value="-1">No Filter</option>';
            columnSortSelect.innerHTML = '<select id="columnSortSelect"><option value="-1">No Sort</option>';
            let dataDiv = document.getElementById('dataDiv');
            dataDiv.innerHTML = '<ul id="myUL">';
            
            //adding the data
            for(let i = 0; i < response.data.length; i++){
                dataDiv.innerHTML += '<li>' + response.data[i] + '</li>';
                columnSelect.innerHTML += `<option value="${response.data[i]}">${response.data[i]}</option>`;
                columnSortSelect.innerHTML += `<option value="${response.data[i]}">${response.data[i]}</option>`;
            }
            dataDiv.innerHTML += '<ul>';
            columnSelect.innerHTML += '</select>';
            columnSortSelect.innerHTML += '</select>';
        }
    );
  }
  //sends an ajax request to download the html table as a csv file
  function downloadTableAjax(tableName, columnName, columnValue, sortColumn, sortDirection){
    jQuery.post(
        ajaxurl, 
        {
            'action': 'downloadTable',
            'columnName': columnName,
            'columnValue': columnValue,
            'tableName' : tableName,
            'sortColumn': sortColumn,
            'sortDirection': sortDirection
        }, 
        function(response) {
            location.reload();
        }
    );
  }
  
  function createTableFromData(tableData, pageAmount, page){
    let dataDiv = document.getElementById('dataDiv');
    //creating the html table from variable tableData
    var tableHtml = '<table> <tr>';
    //if tableData is empty, there is no data for that table
    if (tableData.length === 0) {
        tableHtml = '<p>No Data in Table</p>';
      } else {
        //the first row of tableData will be the names of the columns
        Object.keys(tableData[0]).forEach(function(key) {
            tableHtml += '<th>' + key + '</th>';
        });
        tableHtml += '</tr>';
        
        //creates the main table rows
        tableData.forEach(function(row) {
            tableHtml += '<tr>';
            Object.values(row).forEach(function(value) {
                tableHtml += '<td><div style="max-height:50px; overflow:hidden">' + value + '</div></td>';
            });
            tableHtml += '</tr>'; 
        });
        tableHtml += '</table>';
    }//end of table

    //creating the pagination buttons
    document.querySelector('.pagination').innerHTML = '';
    for(let i = 1; i < pageAmount+1; i++){
        var button = document.createElement('button');
        button.addEventListener('click', function(e){
            if(e.currentTarget.className !== 'pagination-button active'){
                filterByColumnAjax(tableSelect.value, columnSelect.value, columnInput.value, columnSortSelect.value, sortDirectionSelect.value, i);
            }
        });
        //the active page button gets the class 'active'
        if(i === page){
            button.className = 'pagination-button active';
        }else{
            button.className = 'pagination-button';
        }
        button.textContent = i.toString();
        document.querySelector('.pagination').append(button);
      }
      dataDiv.innerHTML = tableHtml;
 }