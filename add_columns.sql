ALTER TABLE users 
ADD COLUMN firmuserall char(1) DEFAULT '',
ADD COLUMN website varchar(25) DEFAULT '',
ADD COLUMN user text,
ADD COLUMN description text,
ADD COLUMN foto2 varchar(200) DEFAULT '',
ADD COLUMN foto3 varchar(200) DEFAULT '',
ADD COLUMN foto4 varchar(200) DEFAULT '',
ADD COLUMN foto5 varchar(200) DEFAULT '',
ADD COLUMN domen varchar(100) DEFAULT '',
ADD COLUMN msg smallint(1) DEFAULT 0;
