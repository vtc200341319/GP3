<!DOCTYPE html>
<html>
    <head>
        <title>Academic Calendar</title>
        <style>
            .non-print{
                border: 0;
            }
            
            table {
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid black;
                padding: 10px;
            }
            #print-button {
                width:25px;
                height:25px;
            }
            .actext
            {
                font-size:35px;
            }

        </style>
    </head>
    <body>
        <table class="non-print">
            <tr class="non-print" ><td class="non-print">
                    <span class="actext">Academic Calendar</span>
                   </td>
                <td class="non-print">
                    <input type="image" src="../../../img/icon/printer.png" alt="Print" onclick="printTable()" id="print-button" />
                </td>
            </tr>
        </table>

        <P>

            <?php
            // Define the calendar data for the year
            $calendar_data = array(
                array("Spring Semester", "2023-01-09", "2023-05-05"),
                array("Spring Break", "2023-03-12", "2023-03-19"),
                array("Finals Week", "2023-05-08", "2023-05-12"),
                array("Summer Semester", "2023-06-05", "2023-08-04"),
                array("Independence Day", "2023-07-04", "2023-07-04"),
                array("Fall Semester", "2023-08-28", "2023-12-15"),
                array("Labor Day", "2023-09-04", "2023-09-04"),
                array("Thanksgiving Break", "2023-11-22", "2023-11-24"),
                array("Finals Week", "2023-12-11", "2023-12-15")
            );

            // Create a table to display the calendar data
            echo '<div id="print-table">';           
            echo '<table>';
            echo "<tr><th>Event</th><th>Start Date</th><th>End Date</th></tr>";
            foreach ($calendar_data as $event) {
                echo "<tr><td>{$event[0]}</td><td>{$event[1]}</td><td>{$event[2]}</td></tr>";
            }
            echo "</table>";
            echo "</div>";
            ?>


            <script>
                function printTable() {
                    var printContents = document.getElementById("print-table").innerHTML;
                    var originalContents = document.body.innerHTML;

                    document.body.innerHTML = printContents;
                    window.print();

                    document.body.innerHTML = originalContents;
                }
            </script>
    </body>
</html>