<?php

  // TODO: this script uses installNode calls.
  // This is not allowed for conversion script, since the nodes refered to 
  // might disappear or change in later releases, leaving this conversion 
  // script crippled.
  // The calls must be replaced by create table and/or alter table statements.

  $setup->report("Start conversion of 0.9.1 database -> 0.9.2 database");
  
  // Declare a utility function for converting varchar userid's to the new int
  // foreign keys for users.
  function useridConvert($table, $field, $setup)
  {
    static $s_users="";
    global $g_db;
    
    // One time retrieval of all employees.
    if ($s_users=="")
    {
      $sql = "SELECT id, userid FROM person WHERE role='employee'";
      $s_users = $g_db->getrows($sql);
    }
    
    if (count($s_users) > 0)
    {
      for ($i=0;$i<count($s_users);$i++)
      {                
        $g_db->query("UPDATE $table SET $field='".$s_users[$i]["id"]."' WHERE $field = '".$s_users[$i]["userid"]."'");
      }
    }    
    $setup->alterColumn($table, $field, $field, "int(10)", false);    
    
  }
  
  $setup->installNode("setup.versioninfo");
 
  // Create the schedule_notes table.
  $setup->installNode("calendar.schedule_notes");

  //convert contact table to person table and copy employees to person table
  $setup->renameTable("contact", "person");
  
  // The following calls will add new fields to the person table.
  $setup->installNode("employee.employee");
  $setup->installNode("employee.userprefs");
  $setup->installNode("organization.contact");
  
  // Also update the usercontract node.
  $setup->installNode("employee.usercontracts"); 
  
  // Create project_person table with all necessary fields.
  $setup->installNode("project.project_personcontact");
  $setup->installNode("project.project_personemployee");
 
  //set role = "contact" for all persons in person table
  //these persons are only contacts at this moment  
  $g_db->query("UPDATE person SET role = 'contact'");  

  //copy employees to persons table  
  $employees = $g_db->getrows("SELECT * FROM employee");

  if (count($employees)>0)
  {
    for ($j=0;$j<count($employees);$j++)
    {      
      $id = $g_db->nextid("person");
      
      // Assume names are in the format 'Jay Random Hacker'.
      // Firstname will become 'Jay', Lastname will become 'Random Hacker'.
      $names = explode(" ", $employees[$j]["name"]);
      $firstname = array_shift($names);
      $lastname = implode(" ", $names);
      
      $g_db->query("INSERT INTO person (id, 
                                        userid, 
                                        password, 
                                        lastname, 
                                        firstname, 
                                        email, 
                                        supervisor, 
                                        status, 
                                        theme, 
                                        entity, 
                                        role)
                                VALUES ('$id', 
                                        '".$employees[$j]["userid"]."', 
                                        '".$employees[$j]["password"]."', 
                                        '".$lastname."', 
                                        '".$firstname."',
                                        '".$employees[$j]["email"]."', 
                                        '".$employees[$j]["supervisor"]."', 
                                        '".$employees[$j]["status"]."', 
                                        '".$employees[$j]["theme"]."',
                                        '".$employees[$j]["entity"]."', 
                                        'employee')");      
    }

  }

  //drop employee table
  $setup->dropTable("employee");  

  //rename customer table to organization
  $setup->renameTable("customer", "organization");
    
  // Update the organization node with new fields.
  $setup->installNode("organization.organization");

  $setup->installNode("project.role");  

  //insert roles in table role  
  $res = $g_db->query("INSERT INTO role (id, name) VALUES (1, 'Developer')");    
  $res = $g_db->query("INSERT INTO role (id, name) VALUES (2, 'Customer')");  
  $res = $g_db->query("INSERT INTO db_sequence (seq_name, nextid) VALUES ('role', 2)");  

  //copy contact of a project into project_person
  $sql = "SELECT project.* FROM project";
  $projects = $g_db->getrows($sql);
 
  $customer_defaultcontacts = array(); 
  
  for ($i=0;$i<count($projects);$i++)
  {  
    if ($projects[$i]["contact"] != 0)
    {     
      $g_db->query("INSERT INTO project_person (personid, projectid, role)
                         VALUES ('".$projects[$i]["contact"]."', '".$projects[$i]["id"]."', 2)");      
    }
    else
    {    
      if ($projects[$i]["customer"]!=0) // if customer is not set, we don't need to convert
      {
        // project has a customer, but a contact person was not set. Create a default contact person.        
        if (empty($customer_defaultcontacts[$projects[$i]["customer"]])) // check if we already added a default contact for this customer.
        {
        
          $id = $g_db->nextid("person");
          $g_db->query("INSERT INTO person (id, 
                                            firstname,
                                            lastname, 
                                            company, 
                                            role)
                                    VALUES ('$id', 
                                            '(please update)',
                                            'Auto-generated default contact', 
                                            '".$projects[$i]["customer"]."', 
                                            'contact')");      
          $customer_defaultcontacts[$projects[$i]["customer"]] = $id;          
        }
        $g_db->query("INSERT INTO project_person (personid, projectid, role)
                       VALUES ('".$customer_defaultcontacts[$projects[$i]["customer"]]."', '".$projects[$i]["id"]."', 2)");          
      }      
    }
  }
  
  $defaultcnt = count($customer_defaultcontacts);
  if ($defaultcnt>0)
  {  
    $setup->report("For ".$defaultcnt." customer".($defaultcnt>1?"s":"")." a 
                    new auto-generated contact person was added to be part of 
                    projects that did not have a contactperson yet. These 
                    contacts have been named  'Auto-generated default 
                    contact'. Please update these to real contacts.", "warning");
  }

  $setup->dropColumn("project", "contact");
  $setup->dropColumn("project", "customer");
  
  //add new fields to schedule table
  $setup->installNode("calendar.schedule");
     
  $setup->renameTable("schedule_attendees", "schedule_attendee");
  
  $setup->alterColumn("schedule_attendee", "scheduleid", "schedule_id", "int(10)", false);
  $setup->alterColumn("schedule_attendee", "userid", "person_id", "int(10)", false);    

  $setup->renameTable("schedule_types", "schedule_type");
    
  // employee_project records will be converted to project_person
  $sql = "SELECT employeeid FROM employee_project";
  $res = $g_db->getrows($sql);
  if (count($res) > 0)
  {
    for ($i=0;$i<count($res);$i++)
    {
      //search employee id with given userid      
      $result = $g_db->getrows("SELECT id FROM person WHERE userid ='".$res[$i]["employeeid"]."' AND role='employee'");
      $g_db->query("UPDATE employee_project SET employeeid='".$result[0]["id"]."' WHERE employeeid = '".$res[$i]["employeeid"]."'");
    }
  }

  //copy all employees to the project_person table  
  $projects = $g_db->getrows("SELECT * FROM employee_project");
  for ($i=0;$i<count($projects);$i++)
  {
    $g_db->query("INSERT INTO project_person (personid, projectid, role)
                       VALUES ('".$projects[$i]["employeeid"]."', '".$projects[$i]["projectid"]."', 1)");    
  }
  
  $setup->dropTable("employee_project");    
  
  //convert planning, employee_project, rate, usercontract, person, todo, todo_history and project table because this version
  //does not use userid as primary key for employees    
  useridConvert("hours", "userid", $setup);
  useridConvert("project", "coordinator", $setup);
  useridConvert("project_person", "personid", $setup);
  useridConvert("usercontract", "userid", $setup);  
  useridConvert("person", "supervisor", $setup); 
  useridConvert("todo", "assigned_to", $setup);  
  useridConvert("todo_history", "assigned_to", $setup);
  useridConvert("schedule", "owner", $setup);
  
  // Add new id field to hours_lock table.
  $setup->addColumn("hours_lock", "id", "int(10)", false);
 
  // We must give all records a unique value to be able to use the id
  // field as a primary key.
  $recs = $g_db->getrows("SELECT * FROM hours_lock");
  for ($i=0, $_i=count($recs); $i<$_i; $i++)
  {
    $g_db->query("UPDATE hours_lock 
                     SET id=".($i+1)." 
                   WHERE userid='".$recs[$i]["userid"]."' 
                     AND week='".$recs[$i]["week"]."'");
  }
  $res = $g_db->query("INSERT INTO db_sequence (seq_name, nextid) VALUES ('hours_lock', $i)");  
  
  $g_db->query("ALTER TABLE hours_lock DROP PRIMARY KEY, ADD PRIMARY KEY(id)");  
  $g_db->query("UPDATE hours_lock SET userid = '' WHERE userid='*'"); // We can't set it to null
                                                                      // yet, but we must remove
                                                                      // the '*' values to be
                                                                      // able to convert it to int
  useridConvert("hours_lock", "userid", $setup);    
  $setup->alterColumn("hours_lock", "userid", "userid", "int(10)", true); // for hours_lock, the userid field may be null  
  $g_db->query("UPDATE hours_lock SET userid = NULL WHERE userid=''");
  
 
  //change the node rights in the accessright table to this format: modulename.nodename
  $g_db->query("UPDATE accessright SET node = CONCAT('calendar.', node) WHERE node IN ('schedule', 'schedule_types', 'schedule_notes', 'schedule_attendees')");    
  $g_db->query("UPDATE accessright SET node = CONCAT('costreg.', node) WHERE node = 'costregistration'");  
  $g_db->query("UPDATE accessright SET node = CONCAT('employee.', node) WHERE node IN ('employee', 'profile', 'usercontracts', 'userprefs')");    
  $g_db->query("UPDATE accessright SET node = CONCAT('finance.', node) WHERE node IN ('rates', 'finance_customer', 'finance_project', 'billing_project', 'currency', 'bill', 'bill_line')");  
  $g_db->query("UPDATE accessright SET node = CONCAT('notes.', node) WHERE node IN ('project_notes', 'project_notesview')");    
  $g_db->query("UPDATE accessright SET node = 'organization.organization' WHERE node = 'customer'");    
  $g_db->query("UPDATE accessright SET node = CONCAT('organization.', node) WHERE node IN ('contact', 'contracts', 'contracttype')");    
  $g_db->query("UPDATE accessright SET node = CONCAT('project.', node) WHERE node IN ('project', 'phase', 'activity', 'tpl_phase', 'tpl_project')");  
  $g_db->query("UPDATE accessright SET node = 'reports.hoursurvey', action='report' WHERE node='hours' AND action='hoursurvey'");
  $g_db->query("UPDATE accessright SET node = CONCAT('reports.', node) WHERE node IN ('weekreport')");      
  $g_db->query("UPDATE accessright SET node = CONCAT('resource_planning.', node) WHERE node = 'resource_planning'");    
  $g_db->query("UPDATE accessright SET node = CONCAT('timereg.', node) WHERE node IN ('hours', 'hours_lock')");  
  $g_db->query("UPDATE accessright SET node = CONCAT('todo.', node) WHERE node = 'todo'");  
  
  // As of this release, we use version numbers. After running this conversion script, the 
  // database is fully up to date with version 1 of all modules.
  $setup->setVersion("1", "calendar");
  $setup->setVersion("1", "employee");
  $setup->setVersion("1", "notes");
  $setup->setVersion("1", "organization");
  $setup->setVersion("1", "person");
  $setup->setVersion("1", "pim");
  $setup->setVersion("1", "project");
  $setup->setVersion("1", "reports");
  $setup->setVersion("1", "search");
  $setup->setVersion("1", "setup");
  $setup->setVersion("1", "timereg");
  $setup->setVersion("1", "todo");  
  
?>