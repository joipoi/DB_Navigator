<?php
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES", ARRAY_N); // all database tables which will go in 'tableSelect' 
?>
<div id="optionElems">
    <button id="getDataButton">Get Table Data</button>
    <fieldset style="display:inline">
        <legend>Tables</legend>
        <select id="tableSelect">
            <option value="-1">Select a table</option>
            <?php foreach ($tables as $table): ?>
                <option value="<?php echo htmlspecialchars($table[0]); ?>"><?php echo htmlspecialchars($table[0]); ?></option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <div style="display:inline" id="filterDiv"> 
        <fieldset style="display:inline">
        <legend>Filter by Column</legend>
        <select id="columnSelect">
                <option value="-1">No Filter</option>
        </select>
            <input type="text" id="columnInput">
        </fieldset>
    </div>

    <fieldset style="display:inline">
            <legend>Sort by Column</legend>
            <select id="columnSortSelect">
                <option value="-1">No Sort</option>
            </select>
            <select id="sortDirectionSelect">
                <option value="ASC">Ascending</option>
                <option value="DESC">Descending</option>
            </select>
        </fieldset>
        <fieldset style="display:inline">
            <legend>Download as CSV</legend>
            <button id="downloadButton">Download</button>
        </fieldset>
</div>

<div id="dataDiv"></div>
<div class="pagination"></div>