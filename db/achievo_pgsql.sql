CREATE TABLE accessright (
   node varchar(25) NOT NULL,
   action varchar(25) NOT NULL,
   entity integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (node, action, entity)
);

CREATE TABLE activity (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   description varchar(50) NOT NULL,
   remarkrequired smallint DEFAULT '0' NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE customer (
   name varchar(100) NOT NULL,
   address varchar(100) NOT NULL,
   zipcode varchar(20) NOT NULL,
   city varchar(100) NOT NULL,
   country varchar(100) NOT NULL,
   phone varchar(20) NOT NULL,
   fax varchar(20) NOT NULL,
   email varchar(50) NOT NULL,
   bankaccount varchar(30) NOT NULL,
   id integer DEFAULT '0' NOT NULL,
   remark text NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE contact (
   lastname varchar(50) NOT NULL,
   firstname varchar(50) NOT NULL,
   address varchar(100) NOT NULL,
   zipcode varchar(20) NOT NULL,
   city varchar(100) NOT NULL,
   country varchar(100) NOT NULL,
   phone varchar(20) NOT NULL,
   fax varchar(20) NOT NULL,
   email varchar(50) NOT NULL,
   id integer DEFAULT '0' NOT NULL,
   remark text NOT NULL,
   company integer DEFAULT '0' NOT NULL,
   owner varchar(15) NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE dependency (
   phaseid_row integer DEFAULT '0' NOT NULL,
   phaseid_col integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (phaseid_row, phaseid_col)
);

CREATE TABLE employee (
   name varchar(40) NOT NULL,
   userid varchar(15) NOT NULL,
   email varchar(100) NOT NULL,
   password varchar(40) NOT NULL,
   status varchar(9) DEFAULT 'active' NOT NULL,
   theme varchar(50) NOT NULL,
   entity integer DEFAULT '0' NOT NULL,
   CHECK (status = 'active' OR status = 'nonactive')
);

CREATE TABLE hours (
   id integer DEFAULT '0' NOT NULL,
   entrydate date NOT NULL,
   date date NOT NULL,
   phaseid integer DEFAULT '0' NOT NULL,
   activityid integer DEFAULT '0' NOT NULL,
   remark text,
   time smallint DEFAULT '0' NOT NULL,
   userid varchar(15) NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE phase (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   projectid integer DEFAULT '0' NOT NULL,
   status varchar(9) DEFAULT 'active' NOT NULL,
   description text NOT NULL,
   max_phasetime integer DEFAULT '0' NOT NULL,
   max_hours integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (id),
   CHECK (status = 'active' OR status = 'nonactive')
);

CREATE TABLE phase_activity (
   phaseid integer DEFAULT '0' NOT NULL,
   activityid integer DEFAULT '0' NOT NULL,
   billable smallint DEFAULT '0' NOT NULL,
   PRIMARY KEY (phaseid, activityid)
);

CREATE TABLE profile (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE project (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   coordinator varchar(20),
   status varchar(9) DEFAULT 'active' NOT NULL,
   description text NOT NULL,
   startdate varchar(20) NOT NULL,
   customer varchar(100),
   PRIMARY KEY (id),
   CHECK (status = 'active' OR status = 'nonactive' OR status = 'archived')
);

CREATE TABLE tpl_dependency (
   phaseid_row integer DEFAULT '0' NOT NULL,
   phaseid_col integer DEFAULT '0' NOT NULL,
   projectid integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (phaseid_row, phaseid_col, projectid)
);

CREATE TABLE tpl_phase (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE tpl_phase_activity (
   phaseid integer DEFAULT '0' NOT NULL,
   activityid integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (phaseid, activityid)
);

CREATE TABLE tpl_project (
   id integer DEFAULT '0' NOT NULL,
   name varchar(50) NOT NULL,
   description text NOT NULL,
   PRIMARY KEY (id)
);

CREATE TABLE tpl_project_phase (
   projectid integer DEFAULT '0' NOT NULL,
   phaseid integer DEFAULT '0' NOT NULL,
   PRIMARY KEY (projectid, phaseid)
);

CREATE SEQUENCE seq_node;
CREATE SEQUENCE seq_hours;
CREATE SEQUENCE seq_project;
CREATE SEQUENCE seq_activity;
CREATE SEQUENCE seq_customer;
CREATE SEQUENCE seq_contact;
CREATE SEQUENCE seq_employee;
CREATE SEQUENCE seq_phase;
CREATE SEQUENCE seq_profile;
CREATE SEQUENCE seq_tpl_project;
CREATE SEQUENCE seq_tpl_phase;
