<?php

chdir("../");
include_once("atk.inc");

/*
* Achievo Convert Script
* for converting the database of Achievo 0.9.2 to Achievo 0.9.3
* See the doc/UPGRADE file for more detailed instructions on how
* to use this conversion script.
*
*/

// If you set this to 0, no logfile will be generated.
// It is useful to leave this at 1 though. If you encounter errors
// during the conversion, you can mail the logfile to ivo@achievo.org
// to check out what the errors are.
$write_log = 1; // 0 = no  / 1 = yes
$log_file = "/tmp/achievo_convert.log";

// End of the settings......

// This script could take a while.. (if you encounter 'maximum execution
// time exceeded' errors, set this to a higher value
// and try again.
set_time_limit("180"); // 3 minutes should be enough..

atksession();
atksecure();

// Check if the administrator is logged in
if($g_user["name"]<>"administrator")
{
  echo "Sorry, only the administrator can execute this script.";
  exit;
}


/*
* Small function to write logfile
*/
function write_log($msg)
{
  global $log_file, $write_log, $g_layout;

  $message="[".date("d-m-Y H:i:s")."] - ";
  $message.=$msg;
  $message.="\n";

  if($write_log==1)
  {
    $fp = fopen($log_file, "a");
    fputs($fp, strip_tags($message));
    fclose($fp);
  }
  $g_layout->output($message."<br>");

}

function handleError($query="")
{
  global $g_db;
  $error = $g_db->getError();

  if ($error!="")
  {
    $GLOBAL["errors"]=true;
    write_log('<font color="#ff0000">ERROR</font>: '.$error);
    if ($query!="")
    {
      write_log('Last query was: '.$query);
    }
  }
}

function update_sequence($seq_name,$new_name)
{
  global $g_db;
  $sql = "UPDATE db_sequence SET seq_name='".$new_name."'
          WHERE seq_name='".$seq_name."'";
  $res = $g_db->query($sql);
  handleError($sql);
}

$errors = false;

$g_layout->output("<html>");
$g_layout->head("Achievo Convert Script");
$g_layout->body();

if(!empty($convert)&&$convert==1)
{
  $g_layout->ui_top("Convert log");
  $g_layout->output('<div align="left">');
  write_log("Start conversion of 0.9.2 database -> 0.9.3 database");

  //first execute the sql queries to make the database up to date

  //create table schedule_notes
  $sql = "CREATE TABLE schedule_notes (
            id int(10) PRIMARY KEY NOT NULL,
            title varchar(50) NOT NULL,
            description text NOT NULL,
            entrydate date NOT NULL,
            owner varchar(50) NOT NULL,
            schedule_id int(10) NOT NULL
          )";
  $res = $g_db->query($sql);
  handleError($sql);

  //convert contact table to person table and copy employees to person table
  $sql = "ALTER TABLE contact RENAME person";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD userid VARCHAR(20) DEFAULT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD password VARCHAR(50) DEFAULT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD function VARCHAR(25) DEFAULT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD supervisor VARCHAR(50) DEFAULT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD status VARCHAR(15) NOT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD theme VARCHAR(50) DEFAULT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD entity INT(10) DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE person ADD role VARCHAR(15) NOT NULL";
  $res = $g_db->query($sql);
  handleError($sql);

  //set role = "contact" for all persons in person table
  //these persons are only contacts at this moment
  $sql = "UPDATE person SET role = 'contact'";
  $res = $g_db->query($sql);
  handleError($sql);

  //change db_sequence name
  update_sequence("contact", "person");

  //copy employees to persons table
  $sql = "SELECT * FROM employee";
  $employees = $g_db->getrows($sql);
  $sql = "SELECT * FROM db_sequence WHERE seq_name = 'person'";
  $res = $g_db->getrows($sql);
  $i = $res[0]["nextid"];
  $j = 1;
  for ($j=0;$j<count($employees);$j++)
  {
    $i++;
    $sql = "INSERT INTO person (id, userid, password, lastname, email, supervisor, status, theme, entity, role)
            VALUES ('".$i."', '".$employees[$j]["userid"]."', '".$employees[$j]["password"]."', '".$employees[$j]["name"]."',
            '".$employees[$j]["email"]."', '".$employees[$j]["supervisor"]."', '".$employees[$j]["status"]."', '".$employees[$j]["theme"]."',
            '".$employees[$j]["entity"]."', 'employee')";
    $res = $g_db->query($sql);
    handleError($sql);
  }

  //change db_sequence nextid
  $sql = "UPDATE db_sequence SET nextid = ".$i." WHERE seq_name = 'person'";
  $res = $g_db->query($sql);
  handleError($sql);

  //drop employee table
  $sql = "DROP TABLE employee";
  $res = $g_db->query($sql);

  //rename customer table to organization
  $sql = "ALTER TABLE customer RENAME organization";
  $res = $g_db->query($sql);
  handleError($sql);

  //create table project_organization
  $sql = "CREATE TABLE project_organization (
            organizationid INT(10) NOT NULL,
            projectid INT(10) NOT NULL,
            role INT(10) NOT NULL,
            contact INT(10) NOT NULL,
            PRIMARY KEY(organizationid, projectid, role)
          )";
  $res = $g_db->query($sql);
  handleError($sql);

  //create table role
  $sql = "CREATE TABLE role (
            id INT(10) PRIMARY KEY NOT NULL,
            name VARCHAR(50) NOT NULL
          )";
  $res = $g_db->query($sql);
  handleError($sql);

  //insert roles in table role
  $sql = "INSERT INTO role (id, name) VALUES (1, 'enduser')";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "INSERT INTO role (id, name) VALUES (2, 'reseller')";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "INSERT INTO role (id, name) VALUES (3, 'supplier')";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "INSERT INTO role (id, name) VALUES (4, 'partner')";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "INSERT INTO role (id, name) VALUES (5, 'employer')";
  $res = $g_db->query($sql);
  handleError($sql);

  //drop contact and customer field from table project
  $sql = "ALTER TABLE project DROP contact";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE project DROP customer";
  $res = $g_db->query($sql);
  handleError($sql);

  //add status field to schedule table
  $sql = "ALTER TABLE schedule ADD status VARCHAR(20) NOT NULL";
  $res = $g_db->query($sql);
  handleError($sql);
  //add all_users field to schedule table
  $sql = "ALTER TABLE schedule ADD all_users TINYINT(1) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  //rename schedule_attendees to schedule_attendee and rename fields
  $sql = "ALTER TABLE schedule_attendees RENAME schedule_attendee";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE schedule_attendee CHANGE scheduleid schedule_id INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);
  $sql = "ALTER TABLE schedule_attendee CHANGE userid person_id INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  //convert planning, employee_project, rate, usercontract, person and project table because this version
  //does not use userid as primary key for employees
  $sql = "SELECT id, employeeid FROM planning";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["employeeid"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE planning SET employeeid='".$result[0]["id"]."' WHERE id = ".$res[$i]["id"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE planning CHANGE employeeid employeeid INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  $sql = "SELECT id, coordinator FROM project";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["coordinator"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE project SET coordinator='".$result[0]["id"]."' WHERE id = ".$res[$i]["id"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE project CHANGE coordinator coordinator INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  $sql = "SELECT employeeid FROM employee_project";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["employeeid"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE employee_project SET employeeid='".$result[0]["id"]."' WHERE employeeid = ".$res[$i]["employeeid"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE employee_project CHANGE employeeid employeeid INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  $sql = "SELECT id, userid FROM rate";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["userid"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE rate SET userid='".$result[0]["id"]."' WHERE id = ".$res[$i]["id"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE rate CHANGE userid userid INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  $sql = "SELECT id, userid FROM usercontract";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["userid"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE usercontract SET userid='".$result[0]["id"]."' WHERE id = ".$res[$i]["id"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE usercontract CHANGE userid userid INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  $sql = "SELECT id, supervisor FROM person WHERE role='employee'";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid
      $sql = "SELECT id FROM person WHERE userid ='".$res[$i]["supervisor"]."' AND role='employee'";
      $result = $g_db->getrows($sql);
      $sql = "UPDATE person SET supervisor='".$result[0]["id"]."' WHERE id = ".$res[$i]["id"]."";
      $update = $g_db->query($sql);
      handleError($sql);
    }
  }
  $sql = "ALTER TABLE person CHANGE supervisor supervisor INT(10) NOT NULL DEFAULT 0";
  $res = $g_db->query($sql);
  handleError($sql);

  write_log("Conversion complete!");

  if ($errors==true)
  {
    $g_layout->output('<br><b>There were errors during the conversion!!</b>');
    $g_layout->output('<br><br>It can not be guaranteed that your new database contains all information of the old database.');
    $g_layout->output('Please mail the exact error messages to <a href="mailto:ivo@achievo.org">ivo@achievo.org</a>. We will try to find out what the problem is so you can try a new conversion.');
  }
  else
  {
    $g_layout->output('<br><a href="../index.php">Click here</a> to start Achievo!');
  }
  $g_layout->output('</div>');
}
else
{
  $g_layout->ui_top("Achievo Convert Script");
  $g_layout->output("This script will convert database <b>".$config_databasename."</b>.<br>This could take a while for large databases.<br><br>Press the 'Convert' button to start the procedure.<br>");
  $g_layout->output('<form name="convert" action="convert-0.9.2-to-0.9.3.php" method="post">');
  $g_layout->output('<input type="hidden" name="convert" value="1">');
  $g_layout->output('<input type="submit" name="submit" value="Convert">');

  $g_layout->output('</form>');
}
$g_layout->ui_bottom();
$g_layout->output("</body>");
$g_layout->output("</html>");
$g_layout->outputFlush();

?>
